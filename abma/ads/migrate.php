<?php requireOwner(); ?>
<?php
/**
 * ترحيل قاعدة بيانات وحدة "الإعلانات" — ينشئ جدول ads إن لم يكن موجوداً.
 * آمن لإعادة التشغيل (CREATE TABLE IF NOT EXISTS) — نفس اصطلاح
 * abma/mailing/migrate.php و abma/pricing/migrate.php.
 *
 * position: home_top | home_between | blog_sidebar | article_inline | footer
 * type: text_link | text | image_link | image | code
 */
global $con;

$queries = [
  "CREATE TABLE IF NOT EXISTS `ads` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(190) NOT NULL,
    `type` varchar(20) NOT NULL DEFAULT 'text',
    `text` text DEFAULT NULL,
    `link` varchar(500) DEFAULT NULL,
    `image` varchar(255) DEFAULT NULL,
    `code` text DEFAULT NULL,
    `position` varchar(50) NOT NULL DEFAULT 'home_top',
    `ordering` int(11) NOT NULL DEFAULT 0,
    `status` varchar(20) NOT NULL DEFAULT 'active',
    `date` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `position_status` (`position`,`status`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",
];

$results = [];
foreach ($queries as $q) {
  try {
    $con->exec($q);
    $results[] = ['ok' => true, 'text' => 'تم تجهيز جدول الإعلانات (ads) بنجاح'];
  } catch (Exception $e) {
    $results[] = ['ok' => false, 'text' => 'خطأ: ' . $e->getMessage()];
  }
}
?>
<title>ترحيل قاعدة بيانات الإعلانات</title>
<h4 class="py-3 mb-3">ترحيل قاعدة بيانات الإعلانات</h4>

<div class="card">
  <div class="card-body">
    <ul class="mb-3">
      <?php foreach ($results as $r): ?>
        <li class="<?php echo $r['ok'] ? 'text-success' : 'text-danger' ?>"><?php echo htmlspecialchars($r['text'], ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ul>
    <a href="ads" class="btn btn-primary">الذهاب للإعلانات</a>
  </div>
</div>
