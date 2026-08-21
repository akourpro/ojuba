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

$raw = file_get_contents('php://input');
$data  = json_decode($raw, true);


if (isset($data['action']) and !empty($data['action'])) {
  $action = safer($data['action']);

  if ($action === "check_slug") {
    $slug = safer(strtolower($data['slug']) ?? NULL);
    $id = safer($data['id'] ?? 0);
    // Check msg Exist
    dbSelect("portfolio", "id", "WHERE slug = ? AND id != ? LIMIT 1", [$slug, $id]);
    if ($countrows == 0) {
      if (!empty($slug)) {
        echo json_encode(array('status' => true, "message" => "اسم الرابط متاح"));
        die();
      } else {
        echo json_encode(array('status' => true, "message" => "اسم  الرابط اختياري"));
        die();
      }
    } else {
      echo json_encode(array('status' => false, "message" => "اسم الرابط موجود مسبقًا"));
      die();
    }
  }

  if ($action === 'delete') {
    $csrf->verify('ajax');
    $id = numer($data['id'] ?? 0);
    dbSelect("portfolio", "id", "WHERE id=? LIMIT 1", [$id]);
    if ($countrows === 1) {
      dbDelete("portfolio", "WHERE id=? LIMIT 1", [$id]); // حذف نهائي
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
