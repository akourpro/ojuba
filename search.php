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
if (!moduleEnabled('search')) {
    http_response_code(404);
    echo safeRender('404.twig', [
        "error_type" => "404",
        "error_message" => $lang['page_not_found'] ?? 'Page not found',
        "error_description" => $lang['page_not_found_desc'] ?? '',
    ]);
    die();
}

$search = safer($_GET['search'] ?? '');
$search = trim($search);
$search = preg_replace('/[^a-zA-Z0-9أ-ي\s]/u', '', $search);
$search = trim($search);

$results = [];
if ($search !== '') {
    dbSelect(
        "blogs",
        "id, slug, name, name_en, image, description, description_en",
        "WHERE status = ? AND (name LIKE ? OR name_en LIKE ? OR description LIKE ? OR description_en LIKE ?) ORDER BY id DESC LIMIT 30",
        ["active", "%$search%", "%$search%", "%$search%", "%$search%"]
    );
    if ($countrows >= 1) {
        foreach ($rows as $row) {
            if ($_COOKIE['lang'] == "ar") {
                $name = $row['name'];
                $description = $row['description'];
            } else {
                $name = $row['name_en'] ?? $row['name'];
                $description = $row['description_en'] ?? $row['description'];
            }
            $name = unescapeSafe($name);
            $results[] = [
                'id' => $row['id'],
                'slug' => $row['slug'],
                'name' => $name,
                'title' => $name, // توافق مع القوالب القديمة
                'description' => $description,
                'image' => $site['site_url'] . "files/blogs/" . $row['image'],
                'photo' => $row['image'], // توافق مع القوالب القديمة
            ];
        }
    }
}

$countResults = count($results);

dbInsert("search_logs", "keyword, results_count, ip, date", [$search, $countResults, getIP(), date("Y-m-d H:i:s")]);

if ($countResults >= 1) {
    $alert = '<div class="alert alert-success">' . str_replace('{count}', $countResults, $lang['search_results_found'] ?? 'تم العثور على {count} نتيجة') . '</div>';
} else {
    $alert = '<div class="alert alert-warning">' . ($lang['no_results'] ?? 'لم يتم العثور على أي نتيجة') . '</div>';
}

// تجهيز بيانات تُستخدمها بعض القوالب في الشريط الجانبي (تصنيفات + أحدث المقالات)
$header_cate = [];
if (moduleEnabled('categories')) {
    dbSelect("categories", "id, name, name_en", "ORDER BY id DESC LIMIT 10", []);
    if ($countrows >= 1) {
        foreach ($rows as $row) {
            $row['name'] = unescapeSafe(($_COOKIE['lang'] == "ar") ? $row['name'] : ($row['name_en'] ?? $row['name']));
            $header_cate[] = $row;
        }
    }
}

$header_articles = [];
if (moduleEnabled('blogs')) {
    dbSelect("blogs", "id, slug, name, name_en", "WHERE status = ? ORDER BY id DESC LIMIT 5", ["active"]);
    if ($countrows >= 1) {
        foreach ($rows as $row) {
            $name = unescapeSafe(($_COOKIE['lang'] == "ar") ? $row['name'] : ($row['name_en'] ?? $row['name']));
            $header_articles[] = [
                'id' => $row['id'],
                'slug' => $row['slug'],
                'name' => $name,
                'title' => $name,
            ];
        }
    }
}

echo safeRender('search.twig', [
    'results' => $results,
    'word_search' => unescapeSafe($search),
    'alert' => $alert,
    'header_cate' => $header_cate,
    'header_articles' => $header_articles,
]);
