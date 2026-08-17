<?php
header('X-Robots-Tag: noindex, nofollow', true);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true);

if (isset($_POST) and !empty($_POST)) {

    $csrf->verify();

    if (!login_check_admin()) {
        echo 'غير مسموح لك بالدخول الى هذه الصفحة';
        die();
    }

    // سحب الحقول وحمايتها
    $name           = safer($_POST['name'] ?? '');
    $name_en        = safer($_POST['name_en'] ?? '');
    $description    = $_POST['description'] ?? '';
    $description_en = $_POST['description_en'] ?? '';
    $slug           = safer($_POST['slug'] ?? '');
    $slug           = strtolower(preg_replace('/[^\p{Arabic}a-zA-Z0-9_-]+/u', '', $slug));
    $tags           = safer($_POST['tags'] ?? '');
    $tags_en        = safer($_POST['tags_en'] ?? '');
    $status         = safer($_POST['status'] ?? 'disabled');

    // تجهيز صورة المعاينة (إن وُجدت)
    $img = $site['site_url'] . "files/images/placeholder.png";
    if (!empty($_FILES['image']['tmp_name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
        $mime = mime_content_type($_FILES['image']['tmp_name']);
        $data = base64_encode(file_get_contents($_FILES['image']['tmp_name']));
        $img  = "data:$mime;base64,$data";
    }

    // تجهيز عناصر المقال كما يتوقعها القالب/الموقع
    $blog = [
        'id'           => 0,
        'slug'         => $slug ?: 'preview-slug',
        'name'         => $name,
        'name_en'      => $name_en,
        'description'  => $description,
        'description_en' => $description_en,
        'tags'         => $tags,      // القالب لا يستخدمها هنا، لا بأس بسلسلة
        'tags_en'      => $tags_en,   // القالب لا يستخدمها هنا
        'status'       => $status ?: 'disabled',
        'date'         => date('Y-m-d H:i:s'),
        'last_update'  => date('Y-m-d H:i:s'),
        'image'        => $img,
        'url'          => routeUrl('blog', $slug ?: 'preview-slug'),
    ];

    // اختيار اللغة (مطابقة لطريقة العرض الفعلية)
    if (($_COOKIE['lang'] ?? 'ar') === 'ar') {
        $blog['name']        = $blog['name'] ?: '[بدون عنوان]';
        $blog['description'] = $blog['description'] ?: '';
    } else {
        $blog['name']        = $blog['name_en'] ?: '[Untitled]';
        $blog['description'] = $blog['description_en'] ?: '';
    }

    echo safeRender('blogs/view.twig', [
        'blog'        => $blog,
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
