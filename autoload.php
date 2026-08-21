<?php

/**
 * AUTO LOAD WITH HEADER
 */

set_include_path(__DIR__ . PATH_SEPARATOR . get_include_path());

if (!is_file(__DIR__ . '/includes/config.php')) {
	$__docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
	$__siteRoot = rtrim(str_replace('\\', '/', __DIR__), '/');
	$__basePath = '';
	if ($__docRoot !== '' && strpos($__siteRoot, $__docRoot) === 0) {
		$__basePath = substr($__siteRoot, strlen($__docRoot));
	}
	header('Location: ' . $__basePath . '/install/');
	exit;
}

require_once __DIR__ . '/includes/config.php';

require_once __DIR__ . '/includes/functions.php';

$maintenanceFlag = getpath() . '.maintenance';
$isApiRequest = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false;
if (is_file($maintenanceFlag) && !$isApiRequest) {
	http_response_code(503);
	header('Retry-After: 120');
	header('Content-Type: text/html; charset=utf-8');
	$maintenanceMessage = trim((string) @file_get_contents($maintenanceFlag));
	if ($maintenanceMessage === '') {
		$maintenanceMessage = 'الموقع قيد الصيانة حالياً، سنعود قريباً.';
	}
	echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>الموقع قيد الصيانة</title><style>body{font-family:-apple-system,Segoe UI,Tahoma,Arial,sans-serif;background:#f6f5fb;color:#1b1730;text-align:center;padding:100px 20px;margin:0}.box{max-width:520px;margin:0 auto}h1{font-size:26px;margin-bottom:12px}p{color:#5c5876;font-size:16px;line-height:1.8}.icon{font-size:44px;margin-bottom:18px}</style></head><body><div class="box"><div class="icon">🛠️</div><h1>الموقع قيد الصيانة حالياً</h1><p>' . htmlspecialchars($maintenanceMessage, ENT_QUOTES, 'UTF-8') . '</p></div></body></html>';
	exit;
}

previewModeInit();

require_once __DIR__ . '/includes/csrf.php';
$csrf = new CSRF_Protect('_csrf', "OJUBA-abma");



// Load the Twig functions
require_once __DIR__ . '/twigload.php';
