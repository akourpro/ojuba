<?php
echo safeRender('404.twig', [
    "error_type" => "401",
    "error_message" => $lang['error_401'],
    "error_description" => $lang['error_401_desc'],
]);
