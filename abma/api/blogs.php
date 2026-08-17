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
        dbSelect("blogs", "id", "WHERE slug = ? AND id != ? LIMIT 1", [$slug, $id]);
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
        dbSelect("blogs", "id, image", "WHERE id=? LIMIT 1", [$id]);
        if ($countrows === 1) { // 
            unlink("../../files/blogs/" . $rows[0]['image']); // حذف الصورة
            dbDelete("blogs", "WHERE id = ? LIMIT 1", [$id]);
            echo json_encode(array('status' => true, "message" => "تم حذف المقالة بنجاح"));
            die();
        } else {
            echo json_encode(array('status' => false, "message" => "المقالة غير موجودة"));
            die();
        }
    }
} else {
    echo json_encode(array('status' => false));
    die();
}
