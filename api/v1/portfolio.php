<?php
include_once dirname(__DIR__, 2) . '/includes/config.php';
include_once dirname(__DIR__, 2) . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if (!moduleEnabled('portfolio')) {
	http_response_code(404);
	echo json_encode(['ok' => false, 'error' => 'وحدة الأعمال غير مفعّلة'], JSON_UNESCAPED_UNICODE);
	exit;
}

// أعمال المعرض تُعرَّف بـid رقمي وليس slug (نفس اصطلاح portfolio/view.php)
$apiLang = in_array($_GET['lang'] ?? '', ['ar', 'en'], true) ? $_GET['lang'] : ((($_COOKIE['lang'] ?? 'ar') === 'en') ? 'en' : 'ar');

if (isset($_GET['id'])) {
	$id = numer($_GET['id']);
	dbSelect("portfolio", "*", "WHERE id = ? AND status = ? LIMIT 1", [$id, 'active']);
	if ($countrows !== 1) {
		http_response_code(404);
		echo json_encode(['ok' => false, 'error' => 'العمل غير موجود'], JSON_UNESCAPED_UNICODE);
		exit;
	}
	$p = $rows[0];
	$categoryName = '';
	if ((int) $p['category'] >= 1) {
		dbSelect("categories", "name, name_en", "WHERE id = ? LIMIT 1", [$p['category']]);
		if ($countrows === 1) {
			$categoryName = $apiLang === 'en' ? $rows[0]['name_en'] : $rows[0]['name'];
		}
	}
	echo json_encode([
		'ok' => true,
		'data' => [
			'id' => (int) $p['id'],
			'title' => unescapeSafe($apiLang === 'en' ? $p['name_en'] : $p['name']),
			'description' => unescapeSafe($apiLang === 'en' ? ($p['description_en'] ?? $p['description']) : $p['description']),
			'image' => !empty($p['image']) ? ($site['site_url'] . 'files/portfolio/' . $p['image']) : '',
			'category' => $categoryName,
			'demo_url' => $p['url'] ?? '',
			'url' => routeUrl('portfolio', $p['id']),
		],
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = min(50, max(1, (int) ($_GET['per_page'] ?? 12)));
$offset = ($page - 1) * $perPage;

dbSelect("portfolio", "COUNT(*) as cnt", "WHERE status = ?", ["active"]);
$total = (int) ($rows[0]['cnt'] ?? 0);

dbSelect("portfolio", "*", "WHERE status = ? ORDER BY id DESC LIMIT $perPage OFFSET $offset", ["active"]);
$items = [];
foreach ($rows as $p) {
	$items[] = [
		'id' => (int) $p['id'],
		'title' => unescapeSafe($apiLang === 'en' ? $p['name_en'] : $p['name']),
		'image' => !empty($p['image']) ? ($site['site_url'] . 'files/portfolio/' . $p['image']) : '',
		'url' => routeUrl('portfolio', $p['id']),
	];
}

echo json_encode([
	'ok' => true,
	'data' => $items,
	'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'total_pages' => (int) ceil($total / $perPage)],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
