<?php
/**
 * نقطة AJAX خاصة بلوحة التحكم لصفحة "الإصدار والتحديثات" (abma/updates) —
 * owner فقط. نفس اصطلاح abma/api/feeds.php بالضبط (isOwner() + $csrf->verify('ajax')
 * + JSON)، مع إضافة قفل بسيط على action=apply لمنع تشغيل عمليتي تحديث في نفس
 * الوقت (مثلاً بسبب نقر مزدوج على الزر).
 */
header('Content-Type: application/json; charset=utf-8');

if (!isOwner()) {
  http_response_code(403);
  echo json_encode(['status' => false, 'message' => 'غير مصرح لك بالوصول']);
  exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (empty($data['action'])) {
  echo json_encode(['status' => false, 'message' => 'طلب غير صالح']);
  exit;
}

$action = safer($data['action']);
require_once 'includes/updater.php';

if ($action === 'check') {
  $csrf->verify('ajax');
  $result = updaterCheckForUpdate(true);
  if (!$result['ok']) {
    echo json_encode(['status' => false, 'message' => 'تعذّر التحقق من التحديثات: ' . ($result['error'] ?? '')]);
    exit;
  }
  $info = updaterAvailableInfo();
  echo json_encode([
    'status' => true,
    'message' => $info['available'] ? ('يتوفر إصدار جديد: ' . $info['latest']) : 'لديك أحدث إصدار بالفعل',
    'info' => $info,
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($action === 'apply') {
  $csrf->verify('ajax');

  $lockDir = getpath() . 'abma/tmp/updates/';
  if (!is_dir($lockDir)) {
    @mkdir($lockDir, 0755, true);
  }
  $lockFile = $lockDir . 'apply.lock';

  if (is_file($lockFile) && (time() - (int) @filemtime($lockFile)) < 600) {
    echo json_encode(['status' => false, 'message' => 'يوجد تحديث آخر قيد التنفيذ حالياً — الرجاء الانتظار قليلاً ثم إعادة المحاولة']);
    exit;
  }
  @file_put_contents($lockFile, (string) time());

  try {
    $result = updaterApplyUpdate();
  } finally {
    @unlink($lockFile);
  }

  echo json_encode([
    'status' => (bool) $result['ok'],
    'message' => $result['ok'] ? ('تم التحديث بنجاح إلى الإصدار ' . $result['version']) : 'فشل تطبيق التحديث',
    'log' => $result['log'],
    'version' => $result['version'] ?? null,
    'backup_file' => $result['backup_file'] ?? null,
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

echo json_encode(['status' => false, 'message' => 'إجراء غير معروف']);
