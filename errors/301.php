<?php

$__d = __DIR__;
while (!is_file($__d . '/autoload.php') && $__d !== dirname($__d)) {
    $__d = dirname($__d);
}
require_once $__d . '/autoload.php';

echo safeRender('404.twig', [
    "error_type" => "301",
    "error_message" => $lang['error_301'],
    "error_description" => $lang['error_301_desc'],
]);
