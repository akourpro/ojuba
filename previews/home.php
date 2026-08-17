<?php

use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;

if (!login_check_admin()) {
    header("HTTP/1.0 404 Not Found");
    die();
} else {

    // القالب المُعدل
    $liveTemplate = $_POST['content'] ?? '';

    // Loader من النص
    $liveLoader = new ArrayLoader(['live_template' => $liveTemplate]);

    // دمج مع اللودر الأصلي الموجود مسبقًا في $twig
    $existingLoader = $twig->getLoader();
    $chain = new ChainLoader([$liveLoader, $existingLoader]);

    $twig->setLoader($chain);

    // الآن استخدم Twig كالمعتاد
    echo safeRender('live_template');
}
