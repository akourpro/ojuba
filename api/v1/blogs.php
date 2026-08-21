<?php
include_once dirname(__DIR__, 2) . '/includes/config.php';
include_once dirname(__DIR__, 2) . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if (!moduleEnabled('blogs')) {
	http_response_code(404);
	echo json_encode(['ok' => false, 'error' => 'وحدة المقالات غير مفعّلة'], JSON_UNESCAPED_UNICODE);
	exit;
}

// لغة المحتوى المطلوبة — عبر ?lang=ar|en صراحة، وإلا كوكي الزائر، وإلا العربية.
// مستقل عمداً عن آلية $language/$_COOKIE الخاصة بصفحات الموقع المعروضة بـTwig
// (هذه الواجهة JSON خالصة ولا تمر عبر twigload.php).
$apiLang = in_array($_GET['lang'] ?? '', ['ar', 'en'], true) ? $_GET['lang'] : ((($_COOKIE['lang'] ?? 'ar') === 'en') ? 'en' : 'ar');

function apiBlogShape($blog, $apiLang, $site, $blogCategoriesEnabled)
{
	$name = $apiLang === 'en' ? ($blog['name_en'] ?: $blog['name']) : $blog['name'];
	$description = $apiLang === 'en' ? ($blog['description_en'] ?: $blog['description']) : $blog['description'];
	$categoryName = '';
	if ($blogCategoriesEnabled && (int) $blog['category'] >= 1) {
		dbSelect("blog_categories", "name, name_en", "WHERE id = ? LIMIT 1", [$blog['category']]);
		global $rows, $countrows;
		if ($countrows === 1) {
			$categoryName = unescapeSafe($apiLang === 'en' ? $rows[0]['name_en'] : $rows[0]['name']);
		}
	}
	return [
		'id' => (int) $blog['id'],
		'slug' => $blog['slug'],
		'title' => unescapeSafe($name),
		'description' => unescapeSafe($description),
		'image' => !empty($blog['image']) ? ($site['site_url'] . 'files/blogs/' . $blog['image']) : '',
		'category' => $categoryName,
		'date' => $blog['date'] ?? '',
		'views' => (int) ($blog['views'] ?? 0),
		'reading_time' => function_exists('readingTimeMinutes') ? readingTimeMinutes($description) : null,
		'url' => routeUrl('blog', $blog['slug']),
	];
}

$blogCategoriesEnabled = moduleEnabled('blog_categories');

if (isset($_GET['slug'])) {
	// عنصر واحد
	$slug = preg_replace('/[^\p{Arabic}a-zA-Z0-9_-]+/u', '', safer($_GET['slug']));
	dbSelect("blogs", "*", "WHERE slug = ? AND status = ? LIMIT 1", [$slug, 'active']);
	if ($countrows !== 1) {
		http_response_code(404);
		echo json_encode(['ok' => false, 'error' => 'المقال غير موجود'], JSON_UNESCAPED_UNICODE);
		exit;
	}
	echo json_encode(['ok' => true, 'data' => apiBlogShape($rows[0], $apiLang, $site, $blogCategoriesEnabled)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

// قائمة (مع ترقيم صفحات وفلترة تصنيف اختيارية)
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = min(50, max(1, (int) ($_GET['per_page'] ?? 12)));
$offset = ($page - 1) * $perPage;
$categoryId = (int) ($_GET['category'] ?? 0);

if ($blogCategoriesEnabled && $categoryId > 0) {
	dbSelect("blogs", "COUNT(*) as cnt", "WHERE status = ? AND category = ?", ["active", $categoryId]);
} else {
	dbSelect("blogs", "COUNT(*) as cnt", "WHERE status = ?", ["active"]);
}
$total = (int) ($rows[0]['cnt'] ?? 0);

if ($blogCategoriesEnabled && $categoryId > 0) {
	dbSelect("blogs", "*", "WHERE status = ? AND category = ? ORDER BY id DESC LIMIT $perPage OFFSET $offset", ["active", $categoryId]);
} else {
	dbSelect("blogs", "*", "WHERE status = ? ORDER BY id DESC LIMIT $perPage OFFSET $offset", ["active"]);
}

$items = [];
foreach ($rows as $blog) {
	$items[] = apiBlogShape($blog, $apiLang, $site, $blogCategoriesEnabled);
}

echo json_encode([
	'ok' => true,
	'data' => $items,
	'meta' => [
		'page' => $page,
		'per_page' => $perPage,
		'total' => $total,
		'total_pages' => (int) ceil($total / $perPage),
	],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
