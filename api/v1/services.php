<?php
include_once 'includes/config.php';
include_once 'includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if (!moduleEnabled('services')) {
	http_response_code(404);
	echo json_encode(['ok' => false, 'error' => 'وحدة الخدمات غير مفعّلة'], JSON_UNESCAPED_UNICODE);
	exit;
}

$apiLang = in_array($_GET['lang'] ?? '', ['ar', 'en'], true) ? $_GET['lang'] : ((($_COOKIE['lang'] ?? 'ar') === 'en') ? 'en' : 'ar');

if (isset($_GET['slug'])) {
	$slug = preg_replace('/[^\p{Arabic}a-zA-Z0-9_-]+/u', '', safer($_GET['slug']));
	dbSelect("services", "*", "WHERE slug = ? AND status = ? LIMIT 1", [$slug, 'active']);
	if ($countrows !== 1) {
		http_response_code(404);
		echo json_encode(['ok' => false, 'error' => 'الخدمة غير موجودة'], JSON_UNESCAPED_UNICODE);
		exit;
	}
	$s = $rows[0];
	echo json_encode([
		'ok' => true,
		'data' => [
			'id' => (int) $s['id'],
			'slug' => $s['slug'],
			'title' => unescapeSafe($apiLang === 'en' ? $s['name_en'] : $s['name']),
			'description' => unescapeSafe($apiLang === 'en' ? ($s['description_en'] ?? '') : ($s['description'] ?? '')),
			'image' => !empty($s['image']) ? ($site['site_url'] . 'files/services/' . $s['image']) : '',
			'icon' => $s['icon'] ?? '',
			'url' => routeUrl('service', $s['slug']),
		],
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = min(50, max(1, (int) ($_GET['per_page'] ?? 12)));
$offset = ($page - 1) * $perPage;

dbSelect("services", "COUNT(*) as cnt", "WHERE status = ?", ["active"]);
$total = (int) ($rows[0]['cnt'] ?? 0);

dbSelect("services", "id, slug, name, name_en, image, icon", "WHERE status = ? ORDER BY id DESC LIMIT $perPage OFFSET $offset", ["active"]);
$items = [];
foreach ($rows as $s) {
	$items[] = [
		'id' => (int) $s['id'],
		'slug' => $s['slug'],
		'title' => unescapeSafe($apiLang === 'en' ? $s['name_en'] : $s['name']),
		'image' => !empty($s['image']) ? ($site['site_url'] . 'files/services/' . $s['image']) : '',
		'icon' => $s['icon'] ?? '',
		'url' => routeUrl('service', $s['slug']),
	];
}

echo json_encode([
	'ok' => true,
	'data' => $items,
	'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'total_pages' => (int) ceil($total / $perPage)],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
