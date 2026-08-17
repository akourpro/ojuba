<?php
if (!moduleEnabled('blogs')) {
    echo safeRender('404.twig', [
        "error_type"        => "404",
        "error_message"     => $lang['page_not_found'],
        "error_description" => $lang['page_not_found_desc'],
    ]);
    die();
}

$blogCategoriesEnabled = moduleEnabled('blog_categories');
$activeCategory = ($blogCategoriesEnabled && isset($_GET['category'])) ? numer($_GET['category']) : 0;

if (!empty($activeCategory)) {
    dbSelect("blogs", "COUNT(*) as cnt", "WHERE status = ? AND category = ?", ["active", $activeCategory]);
} else {
    dbSelect("blogs", "COUNT(*) as cnt", "WHERE status = ?", ["active"]);
}
$totalBlogs = (int) ($rows[0]['cnt'] ?? 0);

$pagination = paginate($totalBlogs, 9);

if (!empty($activeCategory)) {
    dbSelect("blogs", "slug, name, name_en, image, category", "WHERE status = ? AND category = ? ORDER BY id DESC LIMIT " . (int) $pagination['per_page'] . " OFFSET " . (int) $pagination['offset'], ["active", $activeCategory]);
} else {
    dbSelect("blogs", "slug, name, name_en, image, category", "WHERE status = ? ORDER BY id DESC LIMIT " . (int) $pagination['per_page'] . " OFFSET " . (int) $pagination['offset'], ["active"]);
}
$blogs = $rows;
foreach ($blogs as &$blog) {
    if ($_COOKIE['lang'] == "ar") {
        $blog['name'] = $blog['name'];
    } else {
        $blog['name'] = $blog['name_en'];
    }
    $blog['name'] = unescapeSafe($blog['name']);
    if ($blogCategoriesEnabled && $blog['category'] >= 1) {
        dbSelect("blog_categories", "*", "WHERE id = ? LIMIT 1", [$blog['category']]);
        if ($countrows == 1) {
            $blog['category'] = unescapeSafe(($_COOKIE['lang'] == "ar") ? $rows[0]['name'] : $rows[0]['name_en']);
        } else {
            $blog['category'] = "";
        }
    } else {
        $blog['category'] = "";
    }
    $blog['image'] = $site['site_url'] . "files/blogs/" . $blog['image'];
    $blog['url'] = routeUrl('blog', $blog['slug']);
}

$categories = [];
if ($blogCategoriesEnabled) {
    dbSelect("blog_categories", "*");
    $categories = $rows;
    foreach ($categories as &$category) {
        $category['name'] = ($_COOKIE['lang'] == "ar") ? $category['name'] : $category['name_en'];
    }
}

echo safeRender('blogs/all.twig', [
    'blogs' => $blogs,
    'pagination' => $pagination,
    'categories' => $categories,
    'active_category' => $activeCategory,
]);
