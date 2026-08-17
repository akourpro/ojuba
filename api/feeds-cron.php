<?php
/**
 * نقطة عامة (بدون تسجيل دخول، GET فقط) تُشغِّل جدولة "سحب المقالات" التلقائية —
 * تُستدعى بطريقتين متكاملتين (راجع CLAUDE.md/قسم سحب المقالات للتفاصيل):
 * 1. تلقائياً عبر "نبضة" خفيفة (fetch بلا انتظار) تُطلقها abma/footer.php في كل
 *    مرة يتصفّح فيها صاحب الموقع لوحة التحكم — يضمن التشغيل التلقائي الكامل
 *    دون أي إعداد يدوي حتى بدون Cron حقيقي بالاستضافة.
 * 2. اختيارياً (موصى به للموثوقية القصوى) عبر Cron Job حقيقي بالاستضافة يزور
 *    نفس هذا الرابط كل 15-30 دقيقة — الرابط الكامل مع التوكن يظهر بصفحة
 *    "سحب المقالات" بلوحة التحكم.
 *
 * الحماية: توكن سري (settings.feeds_cron_token، يُولَّد تلقائياً) ضمن ?token=،
 * بالإضافة لتحديد معدل داخلي (لا يُشغِّل الاستيراد الفعلي إلا مرة كل 5 دقائق
 * كحد أقصى، بغض النظر عن عدد مرات استدعاء الرابط) — يحمي من إغراق المصادر
 * الخارجية بطلبات متكررة، ومن استهلاك موارد الاستضافة عند سوء إعداد Cron
 * (فترة أقصر من اللازم) أو تكرار "النبضة" التلقائية على كل تحميل صفحة إدارية.
 */
include_once 'includes/config.php';
include_once 'includes/functions.php';
require_once 'includes/feed_importer.php';

header('Content-Type: application/json; charset=utf-8');

if (!moduleEnabled('feeds') || !feedsTableExists()) {
    echo json_encode(['status' => false, 'message' => 'وحدة سحب المقالات غير مفعّلة أو غير مُجهَّزة']);
    exit;
}

$expectedToken = ensureFeedsCronToken();
$givenToken = safer($_GET['token'] ?? '');
if (empty($givenToken) || !hash_equals($expectedToken, $givenToken)) {
    http_response_code(403);
    echo json_encode(['status' => false, 'message' => 'توكن غير صحيح']);
    exit;
}

// تحديد المعدل: لا نُشغِّل الاستيراد الفعلي إلا مرة كل 5 دقائق كحد أقصى، بغض
// النظر عن عدد استدعاءات هذا الرابط — الاستدعاءات الزائدة تُعيد نجاحاً صامتاً
// (لا تُعتبر خطأً؛ متوقعة تماماً من "النبضة" التلقائية على كل تحميل صفحة إدارية).
$throttleSeconds = 300;
global $site;
$lastRun = !empty($site['feeds_cron_last_run']) ? strtotime($site['feeds_cron_last_run']) : 0;
if ($lastRun && (time() - $lastRun) < $throttleSeconds) {
    echo json_encode(['status' => true, 'message' => 'تم التخطي (آخر تشغيل حديث)', 'skipped' => true]);
    exit;
}

saveSetting('feeds_cron_last_run', date('Y-m-d H:i:s'));

$result = runFeedImport(null, 5, 8);

echo json_encode([
    'status' => true,
    'processed' => $result['processed'],
    'imported' => $result['imported'],
    'details' => $result['details'],
], JSON_UNESCAPED_UNICODE);
