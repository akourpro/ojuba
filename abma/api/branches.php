<?php
header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$data  = json_decode($raw, true);

if (isset($data['action']) and !empty($data['action'])) {
  $action = safer($data['action'] ?? '');

  if ($action === 'delete') {
    $csrf->verify('ajax');
    $id = numer($data['id'] ?? 0);
    dbSelect("branches", "id", "WHERE id=? LIMIT 1", [$id]);
    if ($countrows === 1) {
      dbDelete("branches", "WHERE id=? LIMIT 1", [$id]);
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
