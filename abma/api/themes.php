<?php
// بوتستراب صريح خفيف (بدون تسجيل دخول تلقائي أو هيدر/فوتر إداري — هذا الملف
// صفحة دخول/خروج أو نقطة AJAX تتحقق من الصلاحيات بنفسها) بدل الاعتماد على
// auto_prepend_file. راجع تعليق abma/minimal.php لتفاصيل كاملة.
require_once dirname(__DIR__) . '/minimal.php';
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

    dbUpdate("settings", "value = ?", [$name, "theme"], "WHERE name = ? LIMIT 1");
    logAction("theme_switch", "تم تفعيل قالب: " . $name);
    echo json_encode(array('status' => true, "message" => "تم تحديث القالب بنجاح"));
    die();
} else {
    echo json_encode(array('status' => false, "message" => "القالب غير موجود"));
    die();
}
