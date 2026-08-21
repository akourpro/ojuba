<?php
// بوتستراب صريح بدل الاعتماد على auto_prepend_file (بعض الاستضافات لا تدعمه
// إطلاقاً — راجع تعليق autoload.php بجذر الموقع لتفاصيل كاملة). آمن حتى لو
// نجح auto_prepend_file أيضاً على استضافات أخرى، لأن require_once يتجاهل أي
// تحميل مكرَّر لنفس الملف تلقائياً.
$__d = __DIR__;
while (!is_file($__d . '/autoload.php') && $__d !== dirname($__d)) {
    $__d = dirname($__d);
}
require_once $__d . '/autoload.php';
?>
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
