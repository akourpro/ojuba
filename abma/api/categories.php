<?php
// بوتستراب صريح خفيف (بدون تسجيل دخول تلقائي أو هيدر/فوتر إداري — هذا الملف
// صفحة دخول/خروج أو نقطة AJAX تتحقق من الصلاحيات بنفسها) بدل الاعتماد على
// auto_prepend_file. راجع تعليق abma/minimal.php لتفاصيل كاملة.
require_once dirname(__DIR__) . '/minimal.php';
?>
<?php
if (!login_check_admin()) {
    http_response_code(403);
    die("غير مصرح لك الوصول إلى هذه الصفحة");
}

// POST DATA
$request_body = file_get_contents('php://input');
$data = json_decode($request_body);
$action = safer(@$data->action);

if (!empty($action)) {
    if ($action === "add") {
        $csrf->verify('ajax');
        $name = safer($data->name);
        $name_en = safer($data->name_en);

        if (!empty($name) && !empty($name_en)) {
            // Insert into DB
            $columns = "name, name_en";
            $values = [$name, $name_en];
            dbInsert("categories", $columns, $values);
            echo json_encode(array('status' => true, "message" => "تم إضافة الفئة بنجاح"));
            die();
        } else {
            echo json_encode(array('status' => false, "message" => "جميع الحقول الاجبارية يجب ملئها"));
            die();
        }
    }

    if ($action === "edit") {
        $csrf->verify('ajax');
        $id = numer($data->id);
        $name = safer($data->name);
        $name_en = safer($data->name_en);

        if (!empty($id) && !empty($name) && !empty($name_en)) {
            // Check if category exists
            dbSelect("categories", "id", "WHERE id=? LIMIT 1", [$id]);
            if ($countrows === 1) {
                $columns = "name = ?, name_en = ?";
                $values = [$name, $name_en, $id];
                dbUpdate("categories", $columns, $values, "WHERE id = ?");
                echo json_encode(array('status' => true, "message" => "تم تحديث الفئة بنجاح"));
                die();
            } else {
                echo json_encode(array('status' => false, "message" => "الفئة غير موجودة"));
                die();
            }
        } else {
            echo json_encode(array('status' => false, "message" => "جميع الحقول الاجبارية يجب ملئها"));
            die();
        }
    }

    if ($action === "get") {
        $id = numer($data->id);

        if (!empty($id)) {
            // Get category data
            dbSelect("categories", "*", "WHERE id=? LIMIT 1", [$id]);
            if ($countrows === 1) {
                $category = $rows[0];
                echo json_encode(array('status' => true, "data" => $category));
                die();
            } else {
                echo json_encode(array('status' => false, "message" => "الفئة غير موجودة"));
                die();
            }
        } else {
            echo json_encode(array('status' => false, "message" => "يجب تحديد الفئة"));
            die();
        }
    }

    if ($action === "delete") {
        $csrf->verify('ajax');
        $id = numer($data->id);

        if (!empty($id)) {
            // Check category Exist
            dbSelect("categories", "id", "WHERE id=? LIMIT 1", [$id]);
            if ($countrows === 1) {
                dbDelete("categories", "WHERE id = ? LIMIT 1", [$id]);
                echo json_encode(array('status' => true, "message" => "تم حذف الفئة بنجاح"));
                die();
            } else {
                echo json_encode(array('status' => false, "message" => "الفئة غير موجودة"));
                die();
            }
        } else {
            echo json_encode(array('status' => false, "message" => "يجب تحديد الفئة لحذفها"));
            die();
        }
    }
} else {
    echo json_encode(array('status' => false));
    die();
}
