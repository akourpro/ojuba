<?php
include_once 'includes/config.php';
include_once 'includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if (!moduleEnabled('pages')) {
	http_response_code(404);
	echo json_encode(['ok' => false, 'error' => 'وحدة الصفحات غير مفعّلة'], JSON_UNESCAPED_UNICODE);
	exit;
}

$apiLang = in_array($_GET['lang'] ?? '', ['ar', 'en'], true) ? $_GET['lang'] : ((($_COOKIE['lang'] ?? 'ar') === 'en') ? 'en' : 'ar');

if (isset($_GET['slug'])) {
	$slug = preg_replace('/[^\p{Arabic}a-zA-Z0-9_-]+/u', '', safer($_GET['slug']));
	dbSelect("pages", "*", "WHERE slug = ? AND status = ? LIMIT 1", [$slug, 'active']);
	if ($countrows !== 1) {
		http_response_code(404);
		echo json_encode(['ok' => false, 'error' => 'الصفحة غير موجودة'], JSON_UNESCAPED_UNICODE);
		exit;
	}
	$page = $rows[0];
	echo json_encode([
		'ok' => true,
		'data' => [
			'id' => (int) $page['id'],
			'slug' => $page['slug'],
			'title' => unescapeSafe($apiLang === 'en' ? $page['name_en'] : $page['name']),
			'description' => unescapeSafe($apiLang === 'en' ? $page['description_en'] : $page['description']),
			'url' => routeUrl('page', $page['slug']),
		],
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

dbSelect("pages", "id, slug, name, name_en", "WHERE status = ? ORDER BY id DESC", ['active']);
$items = [];
foreach ($rows as $p) {
	$items[] = [
		'id' => (int) $p['id'],
		'slug' => $p['slug'],
		'title' => unescapeSafe($apiLang === 'en' ? $p['name_en'] : $p['name']),
		'url' => routeUrl('page', $p['slug']),
	];
}

echo json_encode(['ok' => true, 'data' => $items], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
