<?php
header('X-Robots-Tag: noindex, nofollow', true);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true);

if (isset($_POST) and !empty($_POST)) {

    $csrf->verify();

    if (!login_check_admin()) {
        echo 'غير مسموح لك بالدخول الى هذه الصفحة';
        die();
    }

    $name           = safer($_POST['name'] ?? '');
    $name_en        = safer($_POST['name_en'] ?? '');
    $icon           = safer($_POST['icon'] ?? '');
    $description    = $_POST['description'] ?? '';
    $description_en = $_POST['description_en'] ?? '';
    $slug           = safer($_POST['slug'] ?? '');
    $status         = safer($_POST['status'] ?? 'disabled');

    // تنظيف وتوحيد السلاگ للمعاينة
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '', $slug));
    $slug = $slug ?: 'preview-service';

    // تجهيز الصورة للمعاينة (Base64) أو Placeholder
    $image = $site['site_url'] . "files/images/placeholder.png";
    if (!empty($_FILES['image']['tmp_name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
        $mime  = mime_content_type($_FILES['image']['tmp_name']);
        $data  = base64_encode(file_get_contents($_FILES['image']['tmp_name']));
        $image = "data:$mime;base64,$data";
    }

    // بناء المصفوفة كما يتوقعها القالب
    $service = [
        'id'             => 0,
        'name'           => $name,
        'name_en'        => $name_en,
        'icon'           => $icon,
        'description'    => $description,
        'description_en' => $description_en,
        'image'          => $image,
        'slug'           => $slug,
        'status'         => $status,
        'url'            => routeUrl('service', $slug),
        'date'           => date('Y-m-d H:i:s'),
        'last_update'    => date('Y-m-d H:i:s'),
    ];

    // اختيار اللغة الحالية
    if (($_COOKIE['lang'] ?? 'ar') === 'ar') {
        $service['name']        = $service['name'] ?: '[بدون عنوان]';
        $service['description'] = $service['description'] ?: '';
    } else {
        $service['name']        = $service['name_en'] ?: '[Untitled]';
        $service['description'] = $service['description_en'] ?: '';
    }


    echo safeRender('services/view.twig', [
        'service'     => $service,
        'schema_json' => null,
    ]);
} else {
    echo safeRender('404.twig', [
        "error_type" => "404",
        "error_message" => $lang['page_not_found'],
        "error_description" => $lang['page_not_found_desc'],
    ]);
    die();
}
