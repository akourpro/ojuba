<?php
// بوتستراب صريح خفيف (بدون تسجيل دخول تلقائي أو هيدر/فوتر إداري — هذا الملف
// صفحة دخول/خروج أو نقطة AJAX تتحقق من الصلاحيات بنفسها) بدل الاعتماد على
// auto_prepend_file. راجع تعليق abma/minimal.php لتفاصيل كاملة.
require_once dirname(__DIR__) . '/minimal.php';
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
