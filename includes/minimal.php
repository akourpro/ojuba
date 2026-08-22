<?php

/**
 * AUTO LOAD WITHOUT HEADER
 */

set_include_path(dirname(__DIR__) . PATH_SEPARATOR . get_include_path());

if (!is_file(__DIR__ . '/config.php')) {
	$__docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
	$__siteRoot = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
	$__basePath = '';
	if ($__docRoot !== '' && strpos($__siteRoot, $__docRoot) === 0) {
		$__basePath = substr($__siteRoot, strlen($__docRoot));
	}
	header('Location: ' . $__basePath . '/install/');
	exit;
}

include_once __DIR__ . '/config.php';

include_once __DIR__ . '/functions.php';

include_once __DIR__ . '/csrf.php';
$csrf = new CSRF_Protect("_csrf", "SEC_System");
