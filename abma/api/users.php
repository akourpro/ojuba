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
requireOwner();

$raw = file_get_contents('php://input');
$data  = json_decode($raw, true);

if (isset($data['action']) and !empty($data['action'])) {
  $action = safer($data['action'] ?? '');

  if ($action === 'delete') {
    $csrf->verify('ajax');
    $id = numer($data['id'] ?? 0);

    if ((int)$id === (int)($_SESSION['user_id'] ?? 0)) {
      echo json_encode(['status' => false, 'message' => 'لا يمكنك حذف حسابك الحالي']);
      exit;
    }

    dbSelect("admins", "id, role, status", "WHERE id=? LIMIT 1", [$id]);
    if ($countrows === 1) {
      $target = $rows[0];
      if ($target['role'] === 'owner') {
        dbSelect("admins", "id", "WHERE role = 'owner' AND status = 'active' AND id != ? LIMIT 1", [$id]);
        if ($countrows === 0) {
          echo json_encode(['status' => false, 'message' => 'لا يمكن حذف آخر حساب owner نشط']);
          exit;
        }
      }
      dbDelete("admins", "WHERE id=? LIMIT 1", [$id]);
      logAction("user_delete", "تم حذف حساب أدمن #" . $id);
      echo json_encode(['status' => true, 'message' => 'تم الحذف']);
      exit;
    }
    echo json_encode(['status' => false, 'message' => 'الحساب غير موجود']);
    exit;
  }
} else {
  echo json_encode(['status' => false, 'message' => 'طلب غير صالح']);
  exit;
}
