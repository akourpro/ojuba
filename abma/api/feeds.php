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
    dbSelect("feed_sources", "id", "WHERE id=? LIMIT 1", [$id]);
    if ($countrows === 1) {
      dbDelete("feed_sources", "WHERE id=? LIMIT 1", [$id]);
      // سجلّ العناصر المستوردة (feed_imported_items) يبقى كما هو عمداً — المقالات
      // المستوردة فعلاً بقاعدة blogs لا تُحذف مع حذف المصدر (نفس مبدأ عدم الحذف
      // المتسلسل المستخدم مع بقية الوحدات بهذا السكربت).
      echo json_encode(['status' => true, 'message' => 'تم حذف المصدر (المقالات المستوردة سابقاً تبقى محفوظة)']);
      exit;
    }
    echo json_encode(['status' => false, 'message' => 'العنصر غير موجود']);
    exit;
  }

  if ($action === 'pull') {
    $csrf->verify('ajax');
    $id = numer($data['id'] ?? 0);
    if ($id <= 0) {
      echo json_encode(['status' => false, 'message' => 'مصدر غير صالح']);
      exit;
    }
    require_once 'includes/feed_importer.php';
    $result = runFeedImport((int) $id, 1, 15);
    $message = !empty($result['details']) ? implode(' — ', $result['details']) : 'تم التشغيل';
    echo json_encode(['status' => true, 'message' => $message, 'imported' => $result['imported']]);
    exit;
  }
} else {
  echo json_encode(['status' => false, 'message' => 'طلب غير صالح']);
  exit;
}
