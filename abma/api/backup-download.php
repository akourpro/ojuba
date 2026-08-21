<?php
// بوتستراب صريح خفيف (بدون تسجيل دخول تلقائي أو هيدر/فوتر إداري — هذا الملف
// صفحة دخول/خروج أو نقطة AJAX تتحقق من الصلاحيات بنفسها) بدل الاعتماد على
// auto_prepend_file. راجع تعليق abma/minimal.php لتفاصيل كاملة.
require_once dirname(__DIR__) . '/minimal.php';
?>
<?php
/**
 * تنزيل نسخة احتياطية تلقائية سابقة (أُنشئت قبل تحديث) من مجلد backups/ —
 * owner فقط. المجلد نفسه محمي بـ.htaccess (Require all denied) فلا يمكن
 * الوصول لملفاته مباشرة عبر الرابط، لذلك هذا الملف هو المُمرِّر الوحيد
 * المسموح به لتنزيلها.
 */
requireOwner();

$file = basename((string) ($_GET['file'] ?? ''));
if ($file === '' || !preg_match('/^(pre-update-backup|scheduled-backup)-[A-Za-z0-9._-]+\.zip$/', $file)) {
  http_response_code(400);
  die('اسم ملف غير صالح');
}

$path = getpath() . 'backups/' . $file;
if (!is_file($path)) {
  http_response_code(404);
  die('الملف غير موجود');
}

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: no-store, no-cache');

logAction('backup_download', 'تم تحميل نسخة احتياطية تلقائية: ' . $file);

readfile($path);
