<?php
// بوتستراب صريح بدل الاعتماد على auto_prepend_file (بعض الاستضافات لا تدعمه
// إطلاقاً — راجع تعليق autoload.php بجذر الموقع لتفاصيل كاملة). آمن حتى لو
// نجح auto_prepend_file أيضاً على استضافات أخرى، لأن require_once يتجاهل أي
// تحميل مكرَّر لنفس الملف تلقائياً.
$__d = __DIR__;
while (!is_file($__d . '/autoload.php') && $__d !== dirname($__d)) {
    $__d = dirname($__d);
}
require_once $__d . '/autoload.php';
?>
<?php
header('X-Robots-Tag: noindex, nofollow', true);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true);

if (isset($_POST) and !empty($_POST)) {

    $csrf->verify();

    if (!login_check_admin()) {
        echo 'غير مسموح لك بالدخول الى هذه الصفحة';
        die();
    }

    $name            = safer($_POST['name'] ?? '');
    $name_en         = safer($_POST['name_en'] ?? '');
    $description     = $_POST['description'] ?? '';
    $description_en  = $_POST['description_en'] ?? '';
    $url             = safer($_POST['url'] ?? '');
    $completion_date = safer($_POST['completion_date'] ?? '');
    $status          = safer($_POST['status'] ?? 'disabled');
    $date            = date('Y-m-d H:i:s');

    // معالجة الصورة (عرض مؤقت)
    $image = $site['site_url'] . "files/images/placeholder.png";
    if (!empty($_FILES['image']['tmp_name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
        $mime  = mime_content_type($_FILES['image']['tmp_name']);
        $data  = base64_encode(file_get_contents($_FILES['image']['tmp_name']));
        $image = "data:$mime;base64,$data";
    }

    // بناء المصفوفة كما يتوقع القالب
    $work = [
        'id'              => 0,
        'name'            => $name,
        'name_en'         => $name_en,
        'description'     => $description,
        'description_en'  => $description_en,
        'image'           => $image,
        'url'             => $url,
        'demo'             => $url,
        'completion_date' => $completion_date,
        'status'          => $status,
        'date'            => $date,
        'last_update'     => $date,
    ];

    // اختيار اللغة الحالية من الكوكيز
    $langCode = $_COOKIE['lang'] ?? 'ar';
    if ($langCode === 'ar') {
        $work['name']        = $work['name'] ?: '[بدون عنوان]';
        $work['description'] = $work['description'] ?: '';
    } else {
        $work['name']        = $work['name_en'] ?: '[Untitled]';
        $work['description'] = $work['description_en'] ?: '';
    }

    // تمرير البيانات إلى القالب twig المستخدم في العرض العام
    echo safeRender('portfolio/view.twig', [
        'portfolio'        => $work,
        'schema_json' => null, // اختياري
    ]);
} else {
    echo safeRender('404.twig', [
        "error_type" => "404",
        "error_message" => $lang['page_not_found'],
        "error_description" => $lang['page_not_found_desc'],
    ]);
    die();
}
