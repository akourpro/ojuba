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

// DB CONFIG & FUNCTIONS
include_once 'includes/config.php';
include_once 'includes/functions.php';

$raw = file_get_contents('php://input');
$data  = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

$action = safer($data['action'] ?? '');

if ($action === 'list') {
    $q    = safer($data['q'] ?? '');
    $sort = safer($data['sort'] ?? 'id');
    $dir  = strtolower($data['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
    $per  = max(1, (int)($data['per'] ?? 20));
    $page = max(1, (int)($data['page'] ?? 1));
    $off  = ($page - 1) * $per;

    $allowedSort = ['id', 'first_name', 'last_name', 'email', 'phone', 'seen', 'date'];
    $searchCols = ['first_name', 'last_name', 'email', 'phone', 'message'];

    if (!in_array($sort, $allowedSort, true)) {
        $sort = 'id';
    }

    $binds = [];
    $parts = [];

    if ($q !== '') {
        $partsQ = [];
        foreach ($searchCols as $sc) {
            $partsQ[] = "$sc COLLATE utf8mb4_general_ci LIKE ?";
            $binds[]  = "%" . $q . "%";
        }
        if (!empty($partsQ)) {
            $parts[] = '(' . implode(' OR ', $partsQ) . ')';
        }
    }

    $where = empty($parts) ? '' : (' WHERE ' . implode(' AND ', $parts));

    dbSelect("contact", "COUNT(*) as c", $where, $binds);
    $total = (int)($rows[0]['c'] ?? 0);

    dbSelect("contact", "*", $where . " ORDER BY " . $sort . " " . $dir . " LIMIT " . $per . " OFFSET " . $off, $binds);
    $data = $rows;

    echo json_encode(['status' => true, 'data' => $data, 'total' => $total, 'per' => $per, 'page' => $page], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'reply') {
    $csrf->verify('ajax');
    $id = numer($data['id'] ?? 0);
    $subjectRaw = trim($data['subject'] ?? '');
    $messageRaw = trim($data['message'] ?? '');

    if (!$id || $subjectRaw === '' || $messageRaw === '') {
        echo json_encode(['status' => false, 'message' => 'يرجى تعبئة جميع الحقول']);
        exit;
    }

    if (empty($site['smtp_host']) || empty($site['smtp_user']) || empty($site['smtp_pass'])) {
        echo json_encode(['status' => false, 'message' => 'يرجى ضبط إعدادات SMTP أولًا']);
        exit;
    }

    dbSelect("contact", "id, first_name, last_name, phone, email, message, date", "WHERE id=? LIMIT 1", [$id]);
    if ($countrows !== 1) {
        echo json_encode(['status' => false, 'message' => 'الرسالة غير موجودة']);
        exit;
    }

    $row = $rows[0];
    $email = htmlspecialchars_decode($row['email'] ?? '');
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => false, 'message' => 'البريد الإلكتروني غير صالح']);
        exit;
    }

    $subject = trim(preg_replace('/[\r\n]+/', ' ', strip_tags($subjectRaw)));
    if ($subject === '') {
        echo json_encode(['status' => false, 'message' => 'عنوان الرسالة غير صالح']);
        exit;
    }
    $replyBodySafe = nl2br(safer($messageRaw));

    $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
    $nameSafe = safer($name ?: $email);

    $originalMessage = nl2br($row['message'] ?? '');
    $meta = [];
    if (!empty($row['phone'])) {
        $meta[] = 'الجوال: ' . safer($row['phone']);
    }
    if (!empty($row['date'])) {
        $meta[] = 'التاريخ: ' . safer($row['date']);
    }
    $metaHtml = empty($meta) ? '' : ('<p style="color:#666;font-size:13px;margin:0 0 10px">' . implode(' | ', $meta) . '</p>');

    $body =
        '<div style="font-family:Arial, sans-serif; line-height:1.7; color:#111">' .
        '<p>مرحبًا ' . $nameSafe . '</p>' .
        '<div style="margin:12px 0">' . $replyBodySafe . '</div>' .
        '<hr style="border:0;border-top:1px solid #eee;margin:20px 0">' .
        '<p style="color:#666;font-size:13px;margin:0 0 6px">تفاصيل رسالتك:</p>' .
        $metaHtml .
        '<div style="white-space:pre-wrap">' . $originalMessage . '</div>' .
        '</div>';

    $mail = mailer($email, $subject, $body, true);
    // mailer() تُفعّل استثناءات PHPMailer، لذا نلتقطها هنا للحفاظ على نفس رسالة الخطأ السابقة
    try {
        $sendOk = $mail && $mail->send();
    } catch (Exception $e) {
        $sendOk = false;
    }
    if (!$sendOk) {
        $err = $mail ? $mail->ErrorInfo : 'فشل غير معروف';
        echo json_encode(['status' => false, 'message' => 'تعذر إرسال البريد: ' . $err]);
        exit;
    }

    echo json_encode(['status' => true, 'message' => 'تم إرسال الرد بنجاح']);
    exit;
}

if ($action === 'mark_seen') {
    $csrf->verify('ajax');
    $id = numer($data['id'] ?? 0);
    if (!$id) {
        echo json_encode(['status' => false, 'message' => 'معرّف غير صالح']);
        exit;
    }
    dbSelect("contact", "id, seen", "WHERE id=? LIMIT 1", [$id]);
    if ($countrows !== 1) {
        echo json_encode(['status' => false, 'message' => 'الرسالة غير موجودة']);
        exit;
    }
    if ((int)($rows[0]['seen'] ?? 0) !== 1) {
        dbUpdate("contact", "seen=1", [$id], "WHERE id=? LIMIT 1");
    }
    echo json_encode(['status' => true]);
    exit;
}

echo json_encode(['status' => false, 'message' => 'طلب غير صالح']);
exit;
