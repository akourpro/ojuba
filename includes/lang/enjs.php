<?php
// بوتستراب صريح بدل الاعتماد على auto_prepend_file (بعض الاستضافات لا تدعمه
// إطلاقاً — راجع تعليق abma/minimal.php وCLAUDE.md لتفاصيل كاملة).
require_once dirname(__DIR__) . '/minimal.php';

require_once __DIR__ . '/en.php';

echo json_encode($lang);
