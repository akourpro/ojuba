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
