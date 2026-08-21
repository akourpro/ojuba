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
$action = safer($data->action);

if (!empty($action)) {
    if ($action === "check_slug") {
        $slug = safer($data->slug) ?? NULL;
        $id = safer($data->id) ?? 0;
        // Check msg Exist
        dbSelect("pages", "id", "WHERE slug = ? AND id != ? LIMIT 1", [$slug, $id]);
        if ($countrows == 0) {
            if (!empty($slug)) {
                echo json_encode(array('status' => true, "message" => "اسم الرابط متاح"));
            } else {
                echo json_encode(array('status' => false, "message" => "اسم  الرابط فارغ"));
            }
        } else {
            echo json_encode(array('status' => false, "message" => "اسم الرابط موجود مسبقًا"));
            die();
        }
    }

    if ($action === "delete") {
        $csrf->verify('ajax');
        $id = safer($data->id);
        // Check msg Exist
        dbSelect("pages", "id", "WHERE id=? LIMIT 1", [$id]);
        if ($countrows === 1) { // 
            dbDelete("pages", "WHERE id = ? LIMIT 1", [$id]);
            echo json_encode(array('status' => true, "message" => "تم حذف الصفحة بنجاح"));
            die();
        } else {
            echo json_encode(array('status' => false, "message" => "الصفحة غير موجودة"));
            die();
        }
    }
} else {
    echo json_encode(array('status' => false));
    die();
}
