<?php

/**
 * AUTO LOAD WITHOUT HEADER
 */

$__ojubaRoot = dirname(__DIR__);
set_include_path($__ojubaRoot . PATH_SEPARATOR . get_include_path());

if (!is_file($__ojubaRoot . '/includes/config.php')) {
    $__docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $__siteRoot = rtrim(str_replace('\\', '/', $__ojubaRoot), '/');
    $__basePath = '';
    if ($__docRoot !== '' && strpos($__siteRoot, $__docRoot) === 0) {
        $__basePath = substr($__siteRoot, strlen($__docRoot));
    }
    header('Location: ' . $__basePath . '/install/');
    exit;
}

require_once $__ojubaRoot . '/includes/config.php';

require_once $__ojubaRoot . '/includes/functions.php';

include_once $__ojubaRoot . '/includes/csrf.php';
$csrf = new CSRF_Protect("_csrf", "OJUBA-abma");
