<?php

/**
 * AUTO LOAD WITHOUT HEADER
 */

include_once 'includes/config.php';

include_once 'includes/functions.php';

// include_once 'sec.php';

include_once 'includes/csrf.php';
$csrf = new CSRF_Protect('_csrf', "Ojuba-auth");
// Load the Twig functions
require_once 'twigload.php';

if (login_check()) {
    guser();
}
