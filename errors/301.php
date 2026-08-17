<?php
echo safeRender('404.twig', [
    "error_type" => "301",
    "error_message" => $lang['error_301'],
    "error_description" => $lang['error_301_desc'],
]);
