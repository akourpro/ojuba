<?php requireOwner(); ?>
<?php
/**
 * ترحيل قاعدة بيانات وحدة "الفيديوهات" — ينشئ جدول sport_videos إن لم يكن
 * موجوداً. آمن لإعادة التشغيل — نفس اصطلاح abma/matches/migrate.php.
 */
global $con;

$queries = [
  "CREATE TABLE IF NOT EXISTS `sport_videos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(190) NOT NULL,
    `youtube_url` varchar(500) NOT NULL,
    `thumbnail` varchar(255) DEFAULT NULL,
    `ordering` int(11) NOT NULL DEFAULT 0,
    `status` varchar(20) NOT NULL DEFAULT 'active',
    `date` datetime DEFAULT NULL,
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",
];

$results = [];
foreach ($queries as $q) {
  try {
    $con->exec($q);
    $results[] = ['ok' => true, 'text' => 'تم تجهيز جدول الفيديوهات (sport_videos) بنجاح'];
  } catch (Exception $e) {
    $results[] = ['ok' => false, 'text' => 'خطأ: ' . $e->getMessage()];
  }
}
?>
<title>ترحيل قاعدة بيانات الفيديوهات</title>
<h4 class="py-3 mb-3">ترحيل قاعدة بيانات الفيديوهات</h4>

<div class="card">
  <div class="card-body">
    <ul class="mb-3">
      <?php foreach ($results as $r): ?>
        <li class="<?php echo $r['ok'] ? 'text-success' : 'text-danger' ?>"><?php echo htmlspecialchars($r['text'], ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ul>
    <a href="videos" class="btn btn-primary">الذهاب للفيديوهات</a>
  </div>
</div>
