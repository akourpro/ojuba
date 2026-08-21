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
<?php requireOwner(); ?>
<?php
/**
 * ترحيل قاعدة بيانات المستخدمين (admins) لدعم "التحقق بخطوتين" (2FA/TOTP) —
 * ثلاثة أعمدة اختيارية جديدة: totp_secret (السرّ الخاص بالحساب، Base32)،
 * totp_enabled (0/1)، totp_backup_codes (رموز استرجاع احتياطية، JSON مُشفَّر
 * بـ password_hash لكل رمز على حدة — راجع includes/totp.php).
 *
 * آمن لإعادة التشغيل — يتجاهل خطأ "العمود موجود مسبقاً" بصمت، نفس اصطلاح
 * abma/blogs/migrate.php. مسجَّل أيضاً بمصفوفة $migrateFiles داخل
 * updaterRunCoreMigrations() (includes/updater.php) ليعمل تلقائياً بعد أي
 * تحديث مستقبلي للسكربت دون أي خطوة يدوية.
 */
global $con;

$results = [];
$alterQueries = [
  "ALTER TABLE `admins` ADD COLUMN `totp_secret` varchar(64) DEFAULT NULL AFTER `status`" => 'تم تجهيز عمود سرّ التحقق بخطوتين بنجاح',
  "ALTER TABLE `admins` ADD COLUMN `totp_enabled` tinyint(1) NOT NULL DEFAULT 0 AFTER `totp_secret`" => 'تم تجهيز عمود تفعيل التحقق بخطوتين بنجاح',
  "ALTER TABLE `admins` ADD COLUMN `totp_backup_codes` text DEFAULT NULL AFTER `totp_enabled`" => 'تم تجهيز عمود رموز الاسترجاع الاحتياطية بنجاح',
];
foreach ($alterQueries as $aq => $successText) {
  try {
    $con->exec($aq);
    $results[] = ['ok' => true, 'text' => $successText];
  } catch (Exception $e) {
    if (stripos($e->getMessage(), 'duplicate column') === false) {
      $results[] = ['ok' => false, 'text' => 'خطأ: ' . $e->getMessage()];
    } else {
      $results[] = ['ok' => true, 'text' => 'الخاصية مجهّزة مسبقاً'];
    }
  }
}
?>
<title>ترحيل قاعدة بيانات المستخدمين</title>
<h4 class="py-3 mb-3">ترحيل قاعدة بيانات المستخدمين</h4>

<div class="card">
  <div class="card-body">
    <ul class="mb-3">
      <?php foreach ($results as $r): ?>
        <li class="<?php echo $r['ok'] ? 'text-success' : 'text-danger' ?>"><?php echo htmlspecialchars($r['text'], ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ul>
    <a href="account" class="btn btn-primary">الذهاب لحسابي</a>
  </div>
</div>
