<?php
// DB CONFIG & FUNCTIONS
include_once 'includes/config.php';
include_once 'includes/functions.php';

// POST DATA
$request_body = file_get_contents('php://input');
$data = json_decode($request_body);
$action = safer($data->action);

if (!empty($action)) {

    if ($action == "contact") {
        // Honeypot: حقل مخفي لا يفترض أن يملأه إنسان، إن وصل معبّأً فهذا بوت
        $honeypot = trim((string)($data->website ?? ''));
        if (!empty($honeypot)) {
            // نرجع رسالة نجاح وهمية حتى لا يعرف البوت أنه تم رصده
            echo json_encode(array('status' => true, 'message' => $lang['message_sended_success']));
            die();
        }

        // فحص التوقيت: لو الإرسال تم بعد أقل من 3 ثوانٍ من تحميل الفورم، غالباً بوت
        $loadedAt = (int)($data->loaded_at ?? 0);
        if ($loadedAt > 0 && (time() - $loadedAt) < 3) {
            echo json_encode(array('status' => false, 'message' => $lang['please_try_again_later']));
            die();
        }

        // تحديد معدل الإرسال حسب الـ IP: بحد أقصى 5 رسائل كل 10 دقائق
        $ip = getIP();
        dbSelect("contact", "id", "WHERE ip = ? AND date >= (NOW() - INTERVAL 10 MINUTE)", [$ip]);
        if ($countrows >= 5) {
            echo json_encode(array('status' => false, 'message' => $lang['please_try_again_later']));
            die();
        }

        $fname = safer($data->fname);
        $lname = safer($data->lname);
        $phone = safer($data->phone);
        $email = safer($data->email);
        $message = safer($data->message);

        if (!empty($fname) && !empty($lname) && !empty($phone) && !empty($email) && !empty($message)) {
            $columns = 'first_name, last_name, phone, email, message, ip, date';
            $values = [$fname, $lname, $phone, $email, $message, $ip, date("Y-m-d H:i:s")];
            dbInsert("contact", $columns, $values);
            echo json_encode(array('status' => true, 'message' => $lang['message_sended_success']));
            $user_lang = $_COOKIE['lang'] ?? 'unknown';
            $waMessage = "رسالة جديدة في *" . $site['name'] . "*\n\n" .
                "*الاسم:* $fname $lname\n" .
                "*الجوال:* $phone\n" .
                "*البريد الإلكتروني:* $email\n" .
                "*لغة الموقع:* $user_lang\n\n" .
                "*الرسالة:* \n$message\n\n" .
                "*التاريخ:* " . date("Y-m-d H:i:s");
            if (!empty($site['whatsapp_number'])) {
                whatsapp($site['whatsapp_number'], $waMessage);
            }
            die();
        } else {
            echo json_encode(array('status' => false, 'message' => $lang['please_fill_in_all_fields']));
            die();
        }
    }
} else {
    echo json_encode(array('status' => false));
    die();
}
