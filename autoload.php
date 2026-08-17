<?php

/**
 * AUTO LOAD WITH HEADER
 */

include_once 'includes/config.php';

include_once 'includes/functions.php';

// وضع "الصيانة" أثناء تحديث السكربت: ملف علامة .maintenance بجذر الموقع
// (يُنشئه/يُزيله updaterApplyUpdate() تلقائياً بـ includes/updater.php أثناء
// نسخ ملفات الإصدار الجديد فعلياً). يخص الموقع العام فقط — لوحة التحكم
// (abma/*) تستخدم سلسلة auto_prepend_file منفصلة تماماً (abma/autoload.php)
// فتبقى تعمل دائماً حتى يتابع صاحب الموقع تقدّم التحديث. نقاط api/*.php
// العامة مستثناة أيضاً (JSON، ليست صفحات زوار، وبعضها جزء من آلية التحديث
// نفسها مثل api/update-check.php) — لا تلمس هذا الاستثناء بأي كود مستقبلي.
$maintenanceFlag = getpath() . '.maintenance';
$isApiRequest = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false;
if (is_file($maintenanceFlag) && !$isApiRequest) {
	http_response_code(503);
	header('Retry-After: 120');
	header('Content-Type: text/html; charset=utf-8');
	$maintenanceMessage = trim((string) @file_get_contents($maintenanceFlag));
	if ($maintenanceMessage === '') {
		$maintenanceMessage = 'الموقع قيد الصيانة حالياً، سنعود قريباً.';
	}
	echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>الموقع قيد الصيانة</title><style>body{font-family:-apple-system,Segoe UI,Tahoma,Arial,sans-serif;background:#f6f5fb;color:#1b1730;text-align:center;padding:100px 20px;margin:0}.box{max-width:520px;margin:0 auto}h1{font-size:26px;margin-bottom:12px}p{color:#5c5876;font-size:16px;line-height:1.8}.icon{font-size:44px;margin-bottom:18px}</style></head><body><div class="box"><div class="icon">🛠️</div><h1>الموقع قيد الصيانة حالياً</h1><p>' . htmlspecialchars($maintenanceMessage, ENT_QUOTES, 'UTF-8') . '</p></div></body></html>';
	exit;
}

// وضع معاينة القوالب: يبدّل $site['theme'] مؤقتاً (owner فقط) قبل تحميل Twig
// إن كانت المعاينة مفعّلة بجلسة لوحة التحكم — انظر previewModeInit() بـ functions.php
previewModeInit();

include_once 'includes/csrf.php';
$csrf = new CSRF_Protect('_csrf', "DOCS-abma");



// Load the Twig functions
require_once 'twigload.php';
