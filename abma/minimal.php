<?php

/**
 * AUTO LOAD WITHOUT HEADER
 */

$__ojubaRoot = dirname(__DIR__);
set_include_path($__ojubaRoot . PATH_SEPARATOR . get_include_path());

require_once $__ojubaRoot . '/includes/config.php';

require_once $__ojubaRoot . '/includes/functions.php';

include_once $__ojubaRoot . '/includes/csrf.php';
$csrf = new CSRF_Protect("_csrf", "OJUBA-abma");
