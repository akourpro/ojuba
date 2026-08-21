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

    $name           = safer($_POST['name'] ?? '');
    $name_en        = safer($_POST['name_en'] ?? '');
    $description    = $_POST['description'] ?? '';
    $description_en = $_POST['description_en'] ?? '';
    $slug           = safer($_POST['slug'] ?? '');
    $status         = safer($_POST['status'] ?? 'disabled');

    // توحيد وتنظيف السلاگ
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '', $slug));
    $slug = $slug ?: 'preview-slug';

    // بناء مصفوفة الصفحة كما يتوقعها القالب
    $page = [
        'id'            => 0,
        'slug'          => $slug,
        'name'          => $name,
        'name_en'       => $name_en,
        'description'   => $description,
        'description_en' => $description_en,
        'status'        => $status,
        'date'          => date('Y-m-d H:i:s'),
        'last_update'   => date('Y-m-d H:i:s'),
        'url'           => routeUrl('page', $slug),
    ];

    // اختيار اللغة بنفس منطق الموقع
    if (($_COOKIE['lang'] ?? 'ar') === 'ar') {
        $page['name']        = $page['name'] ?: '[بدون عنوان]';
        $page['description'] = $page['description'] ?: '';
    } else {
        $page['name']        = $page['name_en'] ?: '[Untitled]';
        $page['description'] = $page['description_en'] ?: '';
    }

    // عرض نفس القالب العام للصفحات
    echo safeRender('pages.twig', [
        'page'        => $page,
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
