<?php
/**
 * نقطة عامة (بدون تسجيل دخول، GET فقط) تُشغِّل الفحص الدوري التلقائي لوجود
 * إصدار جديد من السكربت على GitHub — تُستدعى كـ"نبضة" خفيفة (fetch بلا
 * انتظار) من abma/footer.php في كل مرة يتصفّح فيها صاحب الموقع (owner) لوحة
 * التحكم، بنفس فلسفة api/feeds-cron.php بالضبط.
 *
 * هذه النقطة لا تُطبّق أي تحديث فعلياً — قراءة فقط (فحص GitHub API + تخزين
 * النتيجة كإعدادات settings)، لذلك لا حاجة لتوكن سري كما بـfeeds-cron (لا
 * يوجد خطر إغراق طرف ثالث بطلبات، فقط استهلاك حصة GitHub API الخاصة بهذا
 * الموقع نفسه — وهي محمية أصلاً بحد المعدل الداخلي بـupdaterCheckForUpdate()).
 */
include_once 'includes/config.php';
include_once 'includes/functions.php';
require_once 'includes/updater.php';

header('Content-Type: application/json; charset=utf-8');

$result = updaterCheckForUpdate(false);

// قناة "التحديثات الأمنية/التصحيحية" (اختيارية، settings.update_auto_apply_patches)
// — إن كانت مفعّلة والإصدار الجديد رفع Patch فقط (نفس major.minor)، يُطبَّق
// تلقائياً دون انتظار ضغطة "تحديث الآن" اليدوية. راجع updaterIsPatchOnlyBump()
// بـ includes/updater.php لتفسير هذا القرار بالتفصيل.
if (($site['update_auto_apply_patches'] ?? '0') === '1') {
	$info = updaterAvailableInfo();
	if ($info['available'] && updaterIsPatchOnlyBump($info['current'], $info['latest'])) {
		updaterApplyUpdate();
	}
}

// نسخ احتياطية دورية تلقائية (اختيارية، settings.scheduled_backup_enabled) —
// محمية داخلياً بحد معدّل خاص بها، فآمنة للاستدعاء بكل نبضة.
updaterMaybeRunScheduledBackup();

echo json_encode($result, JSON_UNESCAPED_UNICODE);
