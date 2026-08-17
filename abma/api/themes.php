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
