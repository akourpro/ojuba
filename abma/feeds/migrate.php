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
 * ترحيل قاعدة بيانات وحدة "سحب المقالات" (Feed Import / RSS) — ينشئ جدولي
 * feed_sources و feed_imported_items إن لم يكونا موجودين. آمن لإعادة التشغيل
 * (CREATE TABLE IF NOT EXISTS) — نفس اصطلاح abma/ads/migrate.php و
 * abma/matches/migrate.php.
 *
 * feed_sources: مصادر الجلب (رابط RSS/Atom + التصنيف الهدف + قواعد الاستبدال).
 * feed_imported_items: سجل تتبّع لكل عنصر سُحب فعلياً (عبر guid/link) لمنع
 * الاستيراد المكرَّر لنفس المقال بكل تشغيلة جدولة تالية.
 */
global $con;

$queries = [
  "CREATE TABLE IF NOT EXISTS `feed_sources` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(190) NOT NULL,
    `feed_url` varchar(500) NOT NULL,
    `category_id` int(11) DEFAULT NULL,
    `replacements` text DEFAULT NULL,
    `auto_publish` tinyint(1) NOT NULL DEFAULT 0,
    `status` varchar(20) NOT NULL DEFAULT 'active',
    `last_fetched_at` datetime DEFAULT NULL,
    `last_status` varchar(255) DEFAULT NULL,
    `imported_count` int(11) NOT NULL DEFAULT 0,
    `ordering` int(11) NOT NULL DEFAULT 0,
    `date` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `status` (`status`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

  "CREATE TABLE IF NOT EXISTS `feed_imported_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `source_id` int(11) NOT NULL,
    `guid` varchar(500) NOT NULL,
    `blog_id` int(11) DEFAULT NULL,
    `date` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `source_guid` (`source_id`,`guid`(191)),
    KEY `blog_id` (`blog_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",
];

$results = [];
foreach ($queries as $q) {
  try {
    $con->exec($q);
    $results[] = ['ok' => true, 'text' => 'تم تجهيز جداول وحدة سحب المقالات بنجاح'];
  } catch (Exception $e) {
    $results[] = ['ok' => false, 'text' => 'خطأ: ' . $e->getMessage()];
  }
}
?>
<title>ترحيل قاعدة بيانات سحب المقالات</title>
<h4 class="py-3 mb-3">ترحيل قاعدة بيانات سحب المقالات</h4>

<div class="card">
  <div class="card-body">
    <ul class="mb-3">
      <?php foreach ($results as $r): ?>
        <li class="<?php echo $r['ok'] ? 'text-success' : 'text-danger' ?>"><?php echo htmlspecialchars($r['text'], ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ul>
    <a href="feeds" class="btn btn-primary">الذهاب لمصادر سحب المقالات</a>
  </div>
</div>
