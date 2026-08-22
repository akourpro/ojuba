<?php

$__d = __DIR__;
while (!is_file($__d . '/autoload.php') && $__d !== dirname($__d)) {
    $__d = dirname($__d);
}
require_once $__d . '/autoload.php';

echo safeRender('404.twig', [
    "error_type" => "404",
    "error_message" => $lang['page_not_found'],
    "error_description" => $lang['page_not_found_desc'],
]);
