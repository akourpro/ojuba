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
