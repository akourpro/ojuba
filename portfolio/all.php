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
if (!moduleEnabled('portfolio')) {
    echo safeRender('404.twig', [
        "error_type"        => "404",
        "error_message"     => $lang['page_not_found'],
        "error_description" => $lang['page_not_found_desc'],
    ]);
    die();
}

dbSelect("portfolio", "COUNT(*) as cnt", "WHERE status = ?", ["active"]);
$totalPortfolio = (int) ($rows[0]['cnt'] ?? 0);

$pagination = paginate($totalPortfolio, 9);

dbSelect("portfolio", "*", "WHERE status = ? ORDER BY id DESC LIMIT " . (int) $pagination['per_page'] . " OFFSET " . (int) $pagination['offset'], ["active"]);
$portfolios = $rows;
foreach ($portfolios as &$portfolio) {
    if ($portfolio['category'] >= 1) {
        dbSelect("categories", "*", "WHERE id = ? LIMIT 1", [$portfolio['category']]);
        if ($countrows == 1) {
            if ($_COOKIE['lang'] == "ar") {
                $portfolio['category'] = $rows[0]['name'];
            } else {
                $portfolio['category'] = $rows[0]['name_en'];
            }
        } else {
            $portfolio['category'] = "";
        }
    }
    if ($_COOKIE['lang'] == "ar") {
        $portfolio['name'] = $portfolio['name'];
        $portfolio['description'] = $portfolio['description'];
    } else {
        $portfolio['name'] = $portfolio['name_en'];
        $portfolio['description'] = $portfolio['description_en'] ?? $portfolio['description'];
    }
    $portfolio['image'] = !empty($portfolio['image'])
        ? $site['site_url'] . "files/portfolio/" . $portfolio['image']
        : "";
    $portfolio['demo'] = $portfolio['url'];
    $portfolio['url'] = routeUrl('portfolio', $portfolio['id']);
}

dbSelect("categories", "*");
$categories = $rows;
foreach ($categories as &$category) {
    if ($_COOKIE['lang'] == "ar") {
        $category['name'] = $category['name'];
    } else {
        $category['name'] = $category['name_en'];
    }
}

echo safeRender('portfolio/all.twig', [
    'portfolios' => $portfolios,
    'projects' => $portfolios,
    'categories' => $categories,
    'pagination' => $pagination,
]);
