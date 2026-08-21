<?php
// بوتستراب صريح بدل الاعتماد على auto_prepend_file (بعض الاستضافات لا تدعمه
// إطلاقاً — راجع تعليق abma/autoload.php لتفاصيل كاملة). آمن حتى لو نجح
// auto_prepend_file أيضاً على استضافات أخرى، لأن require_once يتجاهل أي
// تحميل مكرَّر لنفس الملف تلقائياً.
$__d = __DIR__;
while (!is_file($__d . '/autoload.php') && $__d !== dirname($__d)) {
    $__d = dirname($__d);
}
require_once $__d . '/autoload.php';
?>
<?php
header('Content-Type: application/json; charset=utf-8');

/**
 * وحدة مراسلة البريد — للمالك (owner) فقط، بنفس منطق باقي الصفحات الحساسة
 */
if (!isOwner()) {
    http_response_code(403);
    echo json_encode(['status' => false, 'message' => 'غير مصرح لك بالوصول']);
    exit;
}
if (!moduleEnabled('mailing')) {
    http_response_code(403);
    echo json_encode(['status' => false, 'message' => 'وحدة مراسلة البريد غير مفعّلة حالياً']);
    exit;
}

const MAILING_TEMPLATES_DIR_REL = 'files/mailing/templates/';
const MAILING_ATTACHMENTS_DIR_REL = 'files/mailing/attachments/';

// ===== إعدادات التحكم بمعدل الإرسال (throttling) =====
// عدّل هاتين القيمتين حسب حد الإرسال المسموح به لدى مزود SMTP/الاستضافة لديك:
// - MAILING_BATCH_SIZE: عدد الرسائل في كل طلب دفعة واحدة (يبقى صغيراً حتى لا
//   يتجاوز وقت التنفيذ المسموح به من الاستضافة، خصوصاً الاستضافة المشتركة)
// - MAILING_DELAY_MICROSECONDS: التأخير بين كل رسالة والتالية داخل نفس الدفعة
//   (1,000,000 = ثانية واحدة). كلما كان حد الاستضافة أصرم، زد هذه القيمة.
// مثال: استضافة مشتركة بحد ~300 رسالة/ساعة → التأخير الحالي (0.8s) + زمن
// الإرسال نفسه يعطي معدلاً أبطأ من ذلك بأمان.
const MAILING_BATCH_SIZE = 5;
const MAILING_DELAY_MICROSECONDS = 800000;

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$action = safer($data['action'] ?? ($_POST['action'] ?? ($_GET['action'] ?? '')));

// تنزيل نموذج CSV جاهز لاستيراد جهات اتصال قائمة معيّنة — رابط GET مباشر
// (بدون CSRF لأنه إجراء قراءة فقط بدون أي تعديل على البيانات)
if ($action === 'import_template') {
    $listId = numer($_GET['list_id'] ?? 0);
    dbSelect("email_lists", "*", "WHERE id = ? LIMIT 1", [$listId]);
    if ($countrows === 0) {
        http_response_code(404);
        echo json_encode(['status' => false, 'message' => 'القائمة غير موجودة']);
        exit;
    }
    $list = $rows[0];
    $fields = @unserialize($list['fields']);
    if (!is_array($fields)) $fields = [];

    $header = ['email'];
    $example = ['example@domain.com'];
    foreach ($fields as $f) {
        $header[] = $f['label'];
        $example[] = '';
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="mailing-import-template-' . $listId . '.csv"');
    header('Cache-Control: no-store, no-cache');
    $out = fopen('php://output', 'w');
    // BOM حتى يفتح إكسل الملف بترميز UTF-8 صحيح مباشرة دون الحاجة لتحديد الترميز يدوياً
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $header);
    fputcsv($out, $example);
    fclose($out);
    exit;
}

// تصدير جهات اتصال قائمة معيّنة إلى CSV بنفس حقول القائمة (البريد + الحقول
// المخصّصة) — رابط GET مباشر (بدون CSRF لأنه إجراء قراءة فقط بدون أي تعديل)
if ($action === 'export_contacts') {
    $listId = numer($_GET['list_id'] ?? 0);
    $q = trim($_GET['q'] ?? '');

    dbSelect("email_lists", "*", "WHERE id = ? LIMIT 1", [$listId]);
    if ($countrows === 0) {
        http_response_code(404);
        echo json_encode(['status' => false, 'message' => 'القائمة غير موجودة']);
        exit;
    }
    $list = $rows[0];
    $fields = @unserialize($list['fields']);
    if (!is_array($fields)) $fields = [];

    $where = "WHERE list_id = ?";
    $binds = [$listId];
    if ($q !== '') {
        $where .= " AND email COLLATE utf8mb4_general_ci LIKE ?";
        $binds[] = "%" . $q . "%";
    }
    dbSelect("email_list_contacts", "email, data, status", $where . " ORDER BY id ASC", $binds);
    $contacts = $rows;

    $header = ['email'];
    foreach ($fields as $f) {
        $header[] = $f['label'];
    }
    $header[] = 'الحالة';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="mailing-contacts-list-' . $listId . '-' . date('Y-m-d') . '.csv"');
    header('Cache-Control: no-store, no-cache');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $header);
    foreach ($contacts as $c) {
        $contactData = @unserialize($c['data']);
        if (!is_array($contactData)) $contactData = [];
        $row = [$c['email']];
        foreach ($fields as $f) {
            $row[] = $contactData[$f['key']] ?? '';
        }
        $row[] = ((int)$c['status'] === 1) ? 'مفعّلة' : 'موقوفة';
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

/**
 * يستبدل %key% داخل نص القالب بقيم جهة الاتصال (data + email)
 */
function mailing_render_template($templateHtml, $email, $contactData)
{
    $vars = is_array($contactData) ? $contactData : [];
    $vars['email'] = $email;
    $message = $templateHtml;
    foreach ($vars as $key => $value) {
        $message = str_replace('%' . $key . '%', safer((string)$value), $message);
    }
    return $message;
}

if ($action === 'delete_list') {
    $csrf->verify('ajax');
    $id = numer($data['id'] ?? 0);

    dbSelect("email_campaigns", "id", "WHERE list_id = ? LIMIT 1", [$id]);
    $legacyLinked = $countrows > 0;
    dbSelect("email_campaign_lists", "id", "WHERE list_id = ? LIMIT 1", [$id]);
    $linkedToCampaign = $countrows > 0;
    if ($legacyLinked || $linkedToCampaign) {
        echo json_encode(['status' => false, 'message' => 'لا يمكن حذف القائمة لوجود حملات مرتبطة بها، احذف الحملات أولاً']);
        exit;
    }

    dbDelete("email_list_contacts", "WHERE list_id = ?", [$id]);
    dbDelete("email_lists", "WHERE id = ? LIMIT 1", [$id]);
    logAction('mailing_list_delete', 'تم حذف قائمة بريدية #' . $id);
    echo json_encode(['status' => true, 'message' => 'تم حذف القائمة وجهات الاتصال التابعة لها']);
    exit;
}

if ($action === 'add_campaign_list') {
    $csrf->verify('ajax');
    $campaignId = numer($data['campaign_id'] ?? 0);
    $listId = numer($data['list_id'] ?? 0);

    if (!$campaignId || !$listId) {
        echo json_encode(['status' => false, 'message' => 'بيانات غير صالحة']);
        exit;
    }

    dbSelect("email_campaigns", "id", "WHERE id = ? LIMIT 1", [$campaignId]);
    if ($countrows === 0) {
        echo json_encode(['status' => false, 'message' => 'الحملة غير موجودة']);
        exit;
    }
    dbSelect("email_lists", "id", "WHERE id = ? LIMIT 1", [$listId]);
    if ($countrows === 0) {
        echo json_encode(['status' => false, 'message' => 'القائمة غير موجودة']);
        exit;
    }

    dbSelect("email_campaign_lists", "id", "WHERE campaign_id = ? AND list_id = ? LIMIT 1", [$campaignId, $listId]);
    if ($countrows > 0) {
        echo json_encode(['status' => false, 'message' => 'هذه القائمة مضافة مسبقاً لهذه الحملة']);
        exit;
    }

    dbInsert("email_campaign_lists", "campaign_id, list_id, date", [$campaignId, $listId, date('Y-m-d H:i:s')]);
    logAction('mailing_campaign_add_list', 'تمت إضافة قائمة بريدية #' . $listId . ' للحملة #' . $campaignId);
    echo json_encode(['status' => true, 'message' => 'تمت إضافة القائمة، الآن اختر المستلمين منها']);
    exit;
}

if ($action === 'remove_campaign_list') {
    $csrf->verify('ajax');
    $campaignId = numer($data['campaign_id'] ?? 0);
    $listId = numer($data['list_id'] ?? 0);

    // نحذف فقط المستلمين الذين لم تُرسل لهم رسالة بعد (pending)، ونحافظ على
    // سجل من استلم فعلاً حتى لا يفقد تتبّع الإرسال التاريخي
    dbDelete("email_campaign_recipients", "WHERE campaign_id = ? AND list_id = ? AND status = 'pending'", [$campaignId, $listId]);

    dbSelect("email_campaign_recipients", "id", "WHERE campaign_id = ? AND list_id = ? LIMIT 1", [$campaignId, $listId]);
    if ($countrows === 0) {
        dbDelete("email_campaign_lists", "WHERE campaign_id = ? AND list_id = ? LIMIT 1", [$campaignId, $listId]);
    }
    logAction('mailing_campaign_remove_list', 'تمت إزالة قائمة بريدية #' . $listId . ' من الحملة #' . $campaignId);
    echo json_encode(['status' => true, 'message' => 'تمت إزالة القائمة من الحملة']);
    exit;
}

if ($action === 'get_list_contacts_for_campaign') {
    $campaignId = numer($data['campaign_id'] ?? 0);
    $listId = numer($data['list_id'] ?? 0);

    dbSelect(
        "email_list_contacts c",
        "c.id, c.email",
        "WHERE c.list_id = ? AND c.status = 1
         AND NOT EXISTS (SELECT 1 FROM email_campaign_recipients r WHERE r.campaign_id = ? AND r.contact_id = c.id)
         ORDER BY c.id ASC",
        [$listId, $campaignId]
    );
    $available = $rows;

    dbSelect("email_campaign_recipients", "COUNT(*) as cnt", "WHERE campaign_id = ? AND list_id = ?", [$campaignId, $listId]);
    $alreadyAdded = (int)($rows[0]['cnt'] ?? 0);

    echo json_encode(['status' => true, 'contacts' => $available, 'already_added' => $alreadyAdded], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'add_recipients') {
    $csrf->verify('ajax');
    $campaignId = numer($data['campaign_id'] ?? 0);
    $listId = numer($data['list_id'] ?? 0);
    $contactIds = $data['contact_ids'] ?? [];
    $selectAll = !empty($data['select_all']);

    if (!$campaignId || !$listId) {
        echo json_encode(['status' => false, 'message' => 'بيانات غير صالحة']);
        exit;
    }

    if ($selectAll) {
        dbSelect(
            "email_list_contacts c",
            "c.id, c.email",
            "WHERE c.list_id = ? AND c.status = 1
             AND NOT EXISTS (SELECT 1 FROM email_campaign_recipients r WHERE r.campaign_id = ? AND r.contact_id = c.id)",
            [$listId, $campaignId]
        );
        $toAdd = $rows;
    } else {
        if (!is_array($contactIds) || empty($contactIds)) {
            echo json_encode(['status' => false, 'message' => 'يرجى اختيار جهة اتصال واحدة على الأقل']);
            exit;
        }
        $cleanIds = array_values(array_unique(array_map('intval', $contactIds)));
        if (empty($cleanIds)) {
            echo json_encode(['status' => false, 'message' => 'يرجى اختيار جهة اتصال واحدة على الأقل']);
            exit;
        }
        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
        dbSelect(
            "email_list_contacts c",
            "c.id, c.email",
            "WHERE c.list_id = ? AND c.status = 1 AND c.id IN ($placeholders)
             AND NOT EXISTS (SELECT 1 FROM email_campaign_recipients r WHERE r.campaign_id = ? AND r.contact_id = c.id)",
            array_merge([$listId], $cleanIds, [$campaignId])
        );
        $toAdd = $rows;
    }

    $added = 0;
    $skippedDupEmail = 0;
    foreach ($toAdd as $c) {
        // منع إرسال رسالتين لنفس البريد داخل نفس الحملة حتى لو كان مكرراً بين قائمتين مختلفتين
        dbSelect("email_campaign_recipients", "id", "WHERE campaign_id = ? AND email = ? LIMIT 1", [$campaignId, $c['email']]);
        if ($countrows > 0) {
            $skippedDupEmail++;
            continue;
        }
        dbInsert(
            "email_campaign_recipients",
            "campaign_id, list_id, contact_id, email, status, date",
            [$campaignId, $listId, $c['id'], $c['email'], 'pending', date('Y-m-d H:i:s')]
        );
        $added++;
    }

    logAction('mailing_campaign_add_recipients', 'تمت إضافة ' . $added . ' مستلم للحملة #' . $campaignId . ' من القائمة #' . $listId);
    $msg = 'تمت إضافة ' . $added . ' جهة اتصال كمستلمين لهذه الحملة';
    if ($skippedDupEmail > 0) {
        $msg .= ' (تم تجاهل ' . $skippedDupEmail . ' لوجود نفس البريد الإلكتروني مسبقاً ضمن مستلمي هذه الحملة)';
    }
    echo json_encode(['status' => true, 'message' => $msg, 'added' => $added]);
    exit;
}

if ($action === 'delete_contact') {
    $csrf->verify('ajax');
    $id = numer($data['id'] ?? 0);
    dbDelete("email_list_contacts", "WHERE id = ? LIMIT 1", [$id]);
    echo json_encode(['status' => true, 'message' => 'تم الحذف']);
    exit;
}

if ($action === 'delete_campaign') {
    $csrf->verify('ajax');
    $id = numer($data['id'] ?? 0);
    dbSelect("email_campaigns", "*", "WHERE id = ? LIMIT 1", [$id]);
    if ($countrows === 1) {
        $campaign = $rows[0];
        // نحذف فقط الملفات المملوكة حصراً لهذه الحملة (رفع مباشر)، ولا نلمس ملفات مكتبة الوسائط المشتركة
        if (!empty($campaign['template']) and str_starts_with($campaign['template'], MAILING_TEMPLATES_DIR_REL)) {
            $tplPath = getpath() . $campaign['template'];
            if (file_exists($tplPath)) @unlink($tplPath);
        }
        $attachments = @unserialize($campaign['attachments']);
        if (is_array($attachments)) {
            foreach ($attachments as $att) {
                $attRelPath = is_array($att) ? ($att['path'] ?? '') : $att;
                if ($attRelPath and str_starts_with($attRelPath, MAILING_ATTACHMENTS_DIR_REL)) {
                    $attPath = getpath() . $attRelPath;
                    if (file_exists($attPath)) @unlink($attPath);
                }
            }
        }
        dbDelete("email_campaign_logs", "WHERE campaign_id = ?", [$id]);
        dbDelete("email_campaign_recipients", "WHERE campaign_id = ?", [$id]);
        dbDelete("email_campaign_lists", "WHERE campaign_id = ?", [$id]);
        dbDelete("email_campaigns", "WHERE id = ? LIMIT 1", [$id]);
        logAction('mailing_campaign_delete', 'تم حذف حملة بريدية: ' . $campaign['name']);
    }
    echo json_encode(['status' => true, 'message' => 'تم الحذف']);
    exit;
}

if ($action === 'send_batch') {
    $csrf->verify('ajax');
    $campaignId = numer($data['campaign_id'] ?? 0);

    dbSelect("email_campaigns", "*", "WHERE id = ? LIMIT 1", [$campaignId]);
    if ($countrows === 0) {
        echo json_encode(['status' => false, 'message' => 'الحملة غير موجودة']);
        exit;
    }
    $campaign = $rows[0];

    if (empty($campaign['template'])) {
        echo json_encode(['status' => false, 'message' => 'لم يتم تحديد قالب البريد لهذه الحملة']);
        exit;
    }
    $templatePath = getpath() . $campaign['template'];
    if (!file_exists($templatePath)) {
        echo json_encode(['status' => false, 'message' => 'ملف القالب غير موجود على الخادم']);
        exit;
    }
    $templateHtml = file_get_contents($templatePath);

    // نبني قائمة مرفقات بصيغة ['path' => المسار المطلق, 'name' => الاسم الأصلي الذي سيظهر في الإيميل]
    // متوافقة مع التعديل الجديد في mailer() الذي يعرض name بدل اسم الملف العشوائي على القرص
    $attachmentsRel = @unserialize($campaign['attachments']);
    $attachmentsAbs = [];
    if (is_array($attachmentsRel)) {
        foreach ($attachmentsRel as $att) {
            $relPath = is_array($att) ? ($att['path'] ?? '') : $att;
            $displayName = is_array($att) ? ($att['name'] ?? basename($relPath)) : basename($att);
            if (!$relPath) continue;
            $absPath = getpath() . $relPath;
            if (file_exists($absPath)) {
                $attachmentsAbs[] = ['path' => $absPath, 'name' => $displayName];
            }
        }
    }

    // إجمالي المستلمين المحدَّدين فعلياً لهذه الحملة (من كل القوائم المضافة إليها)
    dbSelect("email_campaign_recipients", "COUNT(*) as cnt", "WHERE campaign_id = ?", [$campaignId]);
    $total = (int)($rows[0]['cnt'] ?? 0);

    // الدفعة التالية: مستلمون مضافون للحملة ولم يُرسل لهم بعد (status = pending)
    // — هذا يحترم الاختيار اليدوي؛ من لم يُضَف كمستلم أصلاً لن يظهر هنا أبداً
    dbSelect(
        "email_campaign_recipients",
        "id, contact_id, list_id, email",
        "WHERE campaign_id = ? AND status = 'pending' ORDER BY id ASC LIMIT " . MAILING_BATCH_SIZE,
        [$campaignId]
    );
    $batch = $rows;

    // نجهّز محتوى كل رسالة مسبقاً (استبدال %key% لكل جهة اتصال)، ثم نرسل الدفعة
    // كاملة عبر اتصال SMTP واحد يُعاد استخدامه + تأخير بين كل رسالة والتالية
    // (sendBulkBatch) بدل فتح اتصال جديد لكل رسالة — أكثر أماناً من حظر الاستضافة
    $recipientIdByEmail = [];
    $contactIdByEmail = [];
    $sendPayload = [];
    foreach ($batch as $recipient) {
        dbSelect("email_list_contacts", "data", "WHERE id = ? LIMIT 1", [$recipient['contact_id']]);
        $contactData = @unserialize($rows[0]['data'] ?? '');
        $body = mailing_render_template($templateHtml, $recipient['email'], is_array($contactData) ? $contactData : []);
        $sendPayload[] = ['email' => $recipient['email'], 'body' => $body];
        $recipientIdByEmail[$recipient['email']] = $recipient['id'];
        $contactIdByEmail[$recipient['email']] = $recipient['contact_id'];
    }

    $sentInBatch = 0;
    $failedInBatch = 0;
    if (!empty($sendPayload)) {
        $sendResults = sendBulkBatch($campaign['subject'], $sendPayload, $attachmentsAbs, MAILING_DELAY_MICROSECONDS);
        foreach ($sendResults as $r) {
            $recipientId = $recipientIdByEmail[$r['email']] ?? 0;
            $contactId = $contactIdByEmail[$r['email']] ?? 0;
            if ($r['ok']) {
                dbUpdate("email_campaign_recipients", "status = ?, sent_date = ?", ['sent', date('Y-m-d H:i:s'), $recipientId], "WHERE id = ? LIMIT 1");
                dbInsert("email_campaign_logs", "campaign_id, contact_id, email, status, date", [$campaignId, $contactId, $r['email'], 'sent', date('Y-m-d H:i:s')]);
                $sentInBatch++;
            } else {
                dbUpdate("email_campaign_recipients", "status = ?, error = ?, sent_date = ?", ['failed', $r['error'], date('Y-m-d H:i:s'), $recipientId], "WHERE id = ? LIMIT 1");
                dbInsert("email_campaign_logs", "campaign_id, contact_id, email, status, error, date", [$campaignId, $contactId, $r['email'], 'failed', $r['error'], date('Y-m-d H:i:s')]);
                $failedInBatch++;
            }
        }
    }

    // تحديث عدّاد الإرسال في سجل الحملة
    dbSelect("email_campaign_recipients", "COUNT(*) as cnt", "WHERE campaign_id = ? AND status = 'sent'", [$campaignId]);
    $sentTotal = (int)($rows[0]['cnt'] ?? 0);
    $isDone = empty($batch) || $sentTotal >= $total;
    $newStatus = $isDone ? 'sent' : 'sending';
    dbUpdate(
        "email_campaigns",
        "sent_count = ?, total_count = ?, status = ?" . ($isDone ? ", sent_date = ?" : ""),
        $isDone ? [$sentTotal, $total, $newStatus, date('Y-m-d H:i:s'), $campaignId] : [$sentTotal, $total, $newStatus, $campaignId],
        "WHERE id = ? LIMIT 1"
    );

    if ($isDone) {
        logAction('mailing_campaign_sent', 'اكتمل إرسال الحملة: ' . $campaign['name'] . ' (' . $sentTotal . '/' . $total . ')');
    }

    echo json_encode([
        'status' => true,
        'sent_in_batch' => $sentInBatch,
        'failed_in_batch' => $failedInBatch,
        'sent_total' => $sentTotal,
        'total' => $total,
        'done' => $isDone,
    ]);
    exit;
}

echo json_encode(['status' => false, 'message' => 'إجراء غير معروف']);
