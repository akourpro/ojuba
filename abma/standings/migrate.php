<?php requireOwner(); ?>
<?php
/**
 * ترحيل قاعدة بيانات وحدة "جدول الترتيب" — ينشئ جدول sport_standings إن لم
 * يكن موجوداً. آمن لإعادة التشغيل — نفس اصطلاح abma/matches/migrate.php.
 */
global $con;

$queries = [
  "CREATE TABLE IF NOT EXISTS `sport_standings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `team_name` varchar(190) NOT NULL,
    `team_logo` varchar(255) DEFAULT NULL,
    `played` int(11) NOT NULL DEFAULT 0,
    `won` int(11) NOT NULL DEFAULT 0,
    `drawn` int(11) NOT NULL DEFAULT 0,
    `lost` int(11) NOT NULL DEFAULT 0,
    `goals_for` int(11) NOT NULL DEFAULT 0,
    `goals_against` int(11) NOT NULL DEFAULT 0,
    `points` int(11) NOT NULL DEFAULT 0,
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
    $results[] = ['ok' => true, 'text' => 'تم تجهيز جدول الترتيب (sport_standings) بنجاح'];
  } catch (Exception $e) {
    $results[] = ['ok' => false, 'text' => 'خطأ: ' . $e->getMessage()];
  }
}
?>
<title>ترحيل قاعدة بيانات جدول الترتيب</title>
<h4 class="py-3 mb-3">ترحيل قاعدة بيانات جدول الترتيب</h4>

<div class="card">
  <div class="card-body">
    <ul class="mb-3">
      <?php foreach ($results as $r): ?>
        <li class="<?php echo $r['ok'] ? 'text-success' : 'text-danger' ?>"><?php echo htmlspecialchars($r['text'], ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ul>
    <a href="standings" class="btn btn-primary">الذهاب لجدول الترتيب</a>
  </div>
</div>
