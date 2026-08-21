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
    $csrf->verify('ajax');
    $id = numer($data['id'] ?? 0);
    dbSelect("sport_standings", "id, team_logo", "WHERE id=? LIMIT 1", [$id]);
    if ($countrows === 1) {
      $row = $rows[0];
      if (!empty($row['team_logo']) and file_exists('../../files/standings/' . $row['team_logo'])) {
        unlink('../../files/standings/' . $row['team_logo']);
      }
      dbDelete("sport_standings", "WHERE id=? LIMIT 1", [$id]);
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
