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
 * ترحيل قاعدة بيانات وحدة "جدول المباريات" — ينشئ جدول sport_matches إن لم
 * يكن موجوداً. آمن لإعادة التشغيل (CREATE TABLE IF NOT EXISTS) — نفس اصطلاح
 * abma/ads/migrate.php و abma/mailing/migrate.php.
 *
 * match_status: upcoming | live | finished
 */
global $con;

$queries = [
  "CREATE TABLE IF NOT EXISTS `sport_matches` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `competition` varchar(190) DEFAULT NULL,
    `team_home` varchar(190) NOT NULL,
    `team_home_logo` varchar(255) DEFAULT NULL,
    `team_away` varchar(190) NOT NULL,
    `team_away_logo` varchar(255) DEFAULT NULL,
    `match_date` datetime NOT NULL,
    `venue` varchar(190) DEFAULT NULL,
    `score_home` int(11) DEFAULT NULL,
    `score_away` int(11) DEFAULT NULL,
    `match_status` varchar(20) NOT NULL DEFAULT 'upcoming',
    `broadcast_channel` varchar(190) DEFAULT NULL,
    `ordering` int(11) NOT NULL DEFAULT 0,
    `status` varchar(20) NOT NULL DEFAULT 'active',
    `date` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `match_status` (`match_status`),
    KEY `match_date` (`match_date`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",
];

$results = [];
foreach ($queries as $q) {
  try {
    $con->exec($q);
    $results[] = ['ok' => true, 'text' => 'تم تجهيز جدول المباريات (sport_matches) بنجاح'];
  } catch (Exception $e) {
    $results[] = ['ok' => false, 'text' => 'خطأ: ' . $e->getMessage()];
  }
}
?>
<title>ترحيل قاعدة بيانات جدول المباريات</title>
<h4 class="py-3 mb-3">ترحيل قاعدة بيانات جدول المباريات</h4>

<div class="card">
  <div class="card-body">
    <ul class="mb-3">
      <?php foreach ($results as $r): ?>
        <li class="<?php echo $r['ok'] ? 'text-success' : 'text-danger' ?>"><?php echo htmlspecialchars($r['text'], ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ul>
    <a href="matches" class="btn btn-primary">الذهاب لجدول المباريات</a>
  </div>
</div>
