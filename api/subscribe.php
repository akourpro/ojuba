<?php
/**
 * اشتراك عام (بدون تسجيل دخول) بالنشرة البريدية — يُستخدم من نموذج "النشرة
 * البريدية" بالموقع العام (id="newsletterForm"). يُدرج المشترك مباشرة داخل نظام
 * المراسلة الحقيقي الموجود أصلاً بلوحة التحكم (قوائم/جهات اتصال بريدية تحت
 * "الإضافات > مراسلة البريد")، بدل أي تخزين منفصل — بحيث تظهر كل اشتراكات
 * الموقع مباشرة في نفس مكان إدارة القوائم والحملات دون أي عمل إضافي من صاحب
 * الموقع.
 *
 * القائمة المستهدفة: تُحدَّد عبر إعداد settings باسم newsletter_list_id (يُدار
 * من صفحة "مراسلة البريد > القوائم" بلوحة التحكم عبر زر "تعيين كقائمة الاشتراك
 * العام"). إن لم يوجد إعداد بعد (أول اشتراك على الإطلاق)، يُنشأ تلقائياً قائمة
 * افتراضية باسم "مشتركو النشرة البريدية للموقع" ويُحفظ رقمها كإعداد — حتى يعمل
 * النموذج مباشرة دون أي خطوة إعداد يدوية مسبقة من صاحب الموقع.
 *
 * تفعيل/تعطيل الميزة بالكامل: من "لوحة التحكم > الإضافات" (زر "مراسلة البريد") —
 * موجود مسبقاً ويتحكم بنفس moduleEnabled('mailing') المستخدمة هنا.
 */
include_once 'includes/config.php';
include_once 'includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!moduleEnabled('mailing')) {
    echo json_encode(['status' => false, 'message' => $lang['please_try_again_later']]);
    die();
}

$request_body = file_get_contents('php://input');
$data = json_decode($request_body);
$action = safer($data->action ?? '');

if ($action !== 'subscribe') {
    echo json_encode(['status' => false]);
    die();
}

// Honeypot: حقل مخفي لا يفترض أن يملأه إنسان
$honeypot = trim((string) ($data->website ?? ''));
if (!empty($honeypot)) {
    // نرجع رسالة نجاح وهمية حتى لا يعرف البوت أنه تم رصده
    echo json_encode(['status' => true, 'message' => $lang['newsletter_subscribed_success']]);
    die();
}

// فحص التوقيت: إرسال فوري جداً بعد تحميل الفورم يدل غالباً على بوت
$loadedAt = (int) ($data->loaded_at ?? 0);
if ($loadedAt > 0 && (time() - $loadedAt) < 2) {
    echo json_encode(['status' => false, 'message' => $lang['please_try_again_later']]);
    die();
}

// تحديد معدل الاشتراك حسب الـ IP: بحد أقصى 5 محاولات كل 10 دقائق. عمود ip
// أُضيف عبر ترحيل mailing/migrate.php — نتحقق من وجوده أولاً (بدل افتراض أنه
// موجود) كي لا ينكسر النموذج على تركيبات لم تُرحَّل بعد؛ في تلك الحالة يستمر
// الحماية بالـ honeypot وفحص التوقيت فقط دون تحديد معدل بالـ IP.
global $con;
$ip = getIP();
$hasIpColumn = false;
try {
    $chk = $con->query("SHOW COLUMNS FROM email_list_contacts LIKE 'ip'");
    $hasIpColumn = $chk && $chk->rowCount() > 0;
} catch (Exception $e) {
    $hasIpColumn = false;
}

if ($hasIpColumn) {
    dbSelect("email_list_contacts", "id", "WHERE ip = ? AND date >= (NOW() - INTERVAL 10 MINUTE)", [$ip]);
    if ($countrows >= 5) {
        echo json_encode(['status' => false, 'message' => $lang['please_try_again_later']]);
        die();
    }
}

$email = safer($data->email ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => false, 'message' => $lang['please_fill_in_all_fields']]);
    die();
}

// كل إيميل يمكن أن ينتمي لقائمة بريدية واحدة فقط بهذا النظام (نفس القاعدة
// المطبَّقة عند إضافة جهة اتصال يدوياً من لوحة التحكم)
$existingListName = mailingFindEmailListName($email);
if ($existingListName) {
    echo json_encode(['status' => true, 'message' => $lang['email_already_exists']]);
    die();
}

// تحديد/تجهيز قائمة الاشتراك العام للموقع
dbSelect("settings", "value", "WHERE name = 'newsletter_list_id' LIMIT 1");
$listId = $countrows ? (int) $rows[0]['value'] : 0;

if ($listId > 0) {
    dbSelect("email_lists", "id", "WHERE id = ? AND status = 1 LIMIT 1", [$listId]);
    if ($countrows === 0) {
        $listId = 0; // القائمة محذوفة أو مُوقَفة — يُعاد إنشاء واحدة جديدة تلقائياً
    }
}

if ($listId <= 0) {
    $listId = (int) dbInsert(
        "email_lists",
        "name, description, fields, status, date",
        [
            "مشتركو النشرة البريدية للموقع",
            "أُنشئت تلقائياً كقائمة الاشتراك العام من نموذج النشرة البريدية بالموقع",
            serialize([]),
            1,
            date('Y-m-d H:i:s'),
        ]
    );
    saveSetting('newsletter_list_id', $listId);
}

if ($hasIpColumn) {
    dbInsert(
        "email_list_contacts",
        "list_id, email, data, status, ip, date",
        [$listId, $email, serialize([]), 1, $ip, date('Y-m-d H:i:s')]
    );
} else {
    dbInsert(
        "email_list_contacts",
        "list_id, email, data, status, date",
        [$listId, $email, serialize([]), 1, date('Y-m-d H:i:s')]
    );
}

echo json_encode(['status' => true, 'message' => $lang['newsletter_subscribed_success']]);
die();
