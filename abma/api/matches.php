<?php
// بوتستراب صريح خفيف (بدون تسجيل دخول تلقائي أو هيدر/فوتر إداري — هذا الملف
// صفحة دخول/خروج أو نقطة AJAX تتحقق من الصلاحيات بنفسها) بدل الاعتماد على
// auto_prepend_file. راجع تعليق abma/minimal.php لتفاصيل كاملة.
require_once dirname(__DIR__) . '/minimal.php';
?>
<?php
header('Content-Type: application/json; charset=utf-8');

if (!isOwner()) {
  http_response_code(403);
  echo json_encode(['status' => false, 'message' => 'غير مصرح لك بالوصول']);
  exit;
}

$raw = file_get_contents('php://input');
$data  = json_decode($raw, true);

if (isset($data['action']) and !empty($data['action'])) {
  $action = safer($data['action'] ?? '');

  if ($action === 'delete') {

    $id = numer($data['id'] ?? 0);
    dbSelect("sport_matches", "id, team_home_logo, team_away_logo", "WHERE id=? LIMIT 1", [$id]);
    if ($countrows === 1) {
      $row = $rows[0];
      if (!empty($row['team_home_logo']) and file_exists('../../files/matches/' . $row['team_home_logo'])) {
        unlink('../../files/matches/' . $row['team_home_logo']);
      }
      if (!empty($row['team_away_logo']) and file_exists('../../files/matches/' . $row['team_away_logo'])) {
        unlink('../../files/matches/' . $row['team_away_logo']);
      }
      dbDelete("sport_matches", "WHERE id=? LIMIT 1", [$id]);
      echo json_encode(['status' => true, 'message' => 'تم الحذف']);
      exit;
    }
    echo json_encode(['status' => false, 'message' => 'العنصر غير موجود']);
    exit;
  }
} else {
  echo json_encode(['status' => false, 'message' => 'طلب غير صالح']);
  exit;
}
