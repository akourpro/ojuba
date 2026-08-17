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
    dbSelect("sport_videos", "id, thumbnail", "WHERE id=? LIMIT 1", [$id]);
    if ($countrows === 1) {
      $row = $rows[0];
      if (!empty($row['thumbnail']) and file_exists('../../files/videos/' . $row['thumbnail'])) {
        unlink('../../files/videos/' . $row['thumbnail']);
      }
      dbDelete("sport_videos", "WHERE id=? LIMIT 1", [$id]);
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
