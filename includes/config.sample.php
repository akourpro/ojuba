<?php

/**
 * نموذج ملف الإعدادات — انسخ هذا الملف باسم config.php بنفس المجلد وعدّل بيانات
 * الاتصال بقاعدة البيانات أدناه.
 *
 * ملاحظة: لست مضطراً لفعل هذا يدوياً — معالج التثبيت install/index.php يكتب
 * includes/config.php تلقائياً بالخطوة الثانية. استخدم هذا الملف فقط إن كنت
 * تفضّل الإعداد اليدوي بدون المعالج.
 */

/**
 * SHOW OR HIDE ERRORS
 * أوقف عرض الأخطاء (display_errors) على أي موقع حقيقي منشور للزوار — اتركها كما
 * هي فقط أثناء التطوير المحلي.
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

/**
 * DB CONFIG
 */
define("HOST", "localhost");     // عنوان سيرفر قاعدة البيانات
define("USER", "root");          // اسم مستخدم قاعدة البيانات
define("PASSWORD", "");          // كلمة مرور قاعدة البيانات
define("DATABASE", "ojuba");     // اسم قاعدة البيانات
define("CHARSET", "utf8mb4");


$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_FOUND_ROWS => true,
];
$con = new PDO("mysql:host=" . HOST . ";dbname=" . DATABASE . ";charset=" . CHARSET, USER, PASSWORD, $options);
