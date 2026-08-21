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
// DB CONFIG & FUNCTIONS
include_once 'includes/config.php';
include_once 'includes/functions.php';

// POST DATA
$request_body = file_get_contents('php://input');
$data = json_decode($request_body);
$name = safer($data->name);

if (!empty($name)) {
    requireOwner();
    $csrf->verify('ajax');
    dbUpdate("settings", "value = ?", [$name, "theme"], "WHERE name = ? LIMIT 1");
    logAction("theme_switch", "تم تفعيل قالب: " . $name);
    echo json_encode(array('status' => true, "message" => "تم تحديث القالب بنجاح"));
    die();
} else {
    echo json_encode(array('status' => false, "message" => "القالب غير موجود"));
    die();
}
