<?php
echo safeRender('404.twig', [
    "error_type" => "500",
    "error_message" => $lang['error_500'],
    "error_description" => $lang['error_500_desc'],
]);
