<?php

/**
 * REST API خفيف للقراءة فقط (api/v1/*) — واجهة JSON عامة (بدون تسجيل دخول)
 * تتيح لأي واجهة أمامية منفصلة (headless/decoupled frontend، تطبيق جوال،
 * تكامل خارجي) قراءة محتوى الموقع دون المرور عبر Twig. لا يوجد أي endpoint
 * كتابة (POST/PUT/DELETE) — هذه الواجهة قراءة فقط عمداً.
 *
 * كل نقطة تحترم moduleEnabled() (تُعيد 404 إن كانت الوحدة معطَّلة) ونظام
 * routes.* (روابط المحتوى المُعادة تحترم أي تخصيص لمسارات الموقع). راجع
 * abma/developers.php قسم "14. REST API" للتوثيق الكامل.
 *
 * بوتستراب خفيف (نفس اصطلاح api/update-check.php وapi/live-scores.php): تحميل
 * config.php وfunctions.php فقط، بدون autoload.php/twigload.php الكاملين —
 * هذه الواجهة JSON فقط ولا تحتاج Twig.
 */
include_once 'includes/config.php';
include_once 'includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

$knownModules = [
	'blogs', 'blog_categories', 'services', 'portfolio', 'categories', 'testimonials',
	'pricing', 'branches', 'certificates', 'team', 'faq', 'stats', 'clients', 'contact',
	'search', 'mailing', 'ads', 'matches', 'standings', 'videos', 'feeds',
];
$enabledModules = array_values(array_filter($knownModules, 'moduleEnabled'));

echo json_encode([
	'ok' => true,
	'data' => [
		'name' => unescapeSafe($site['name'] ?? ''),
		'description' => unescapeSafe($site['description'] ?? ''),
		'url' => $site['site_url'] ?? '',
		'logo' => !empty($site['logo']) ? (($site['site_url'] ?? '') . 'files/images/' . $site['logo']) : '',
		'language_mode' => $site['language_mode'] ?? 'both',
		'business_type' => $site['business_type'] ?? 'organization',
		'modules' => $enabledModules,
	],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
