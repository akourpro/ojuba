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
 * ترحيل قاعدة بيانات المقالات لدعم خاصية "مقال مميّز/عاجل" (featured) — تُستخدم
 * لتمييز المقال في شريط الأخبار العاجلة وفي "المقال الرئيسي" بالصفحة الرئيسية
 * بقوالب الأخبار، بدل الاعتماد فقط على الأحدث تاريخياً.
 *
 * كما تضيف عمود "المباراة المرتبطة" (related_match_id) — رابط اختياري بين
 * المقال ومباراة من وحدة "جدول المباريات" (sport_matches)، يُستخدم لعرض صندوق
 * ملخص المباراة أعلى صفحة المقال بالقوالب الرياضية (مثل OjubaSport). قيمة
 * NULL تعني "غير مرتبط بأي مباراة" (الحالة الافتراضية لكل المقالات القديمة).
 *
 * آمن لإعادة التشغيل — يتجاهل خطأ "العمود موجود مسبقاً" بصمت. يمكن رفعه
 * وتشغيله على السيرفر الحقيقي بنفس الطريقة بعد رفع الملفات.
 */
global $con;

$results = [];
$alterQueries = [
  "ALTER TABLE `blogs` ADD COLUMN `featured` tinyint(1) NOT NULL DEFAULT 0 AFTER `category`" => 'تم تجهيز خاصية "مقال مميّز/عاجل" بنجاح',
  "ALTER TABLE `blogs` ADD COLUMN `related_match_id` int(11) DEFAULT NULL AFTER `featured`" => 'تم تجهيز خاصية "المباراة المرتبطة" بنجاح',
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
<title>ترحيل قاعدة بيانات المقالات</title>
<h4 class="py-3 mb-3">ترحيل قاعدة بيانات المقالات</h4>

<div class="card">
  <div class="card-body">
    <ul class="mb-3">
      <?php foreach ($results as $r): ?>
        <li class="<?php echo $r['ok'] ? 'text-success' : 'text-danger' ?>"><?php echo htmlspecialchars($r['text'], ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ul>
    <a href="blogs" class="btn btn-primary">الذهاب للمقالات</a>
  </div>
</div>
