<?php

/**
 * نظام Hooks/Actions بسيط — واجهة امتداد (extensibility API) ثابتة على نمط
 * WordPress (add_action/do_action للأحداث، add_filter/apply_filters لتعديل
 * البيانات) تسمح لمطوّري إضافات طرف ثالث بتوسيع السكربت **دون تعديل ملفات
 * النواة نفسها** — وهو شرط أساسي حتى يبقى تعديلهم آمناً عبر كل تحديث تلقائي
 * مستقبلي (راجع includes/updater.php: التحديث يستبدل ملفات النواة بالكامل).
 *
 * كيف يُسجِّل مطوّر إضافة كوده: يضع ملف PHP واحد أو أكثر داخل مجلد
 * includes/addons/ (يُحمَّل تلقائياً بالكامل، أبجدياً، عبر includes/functions.php
 * قبل أي استدعاء do_action()/apply_filters() لاحق بنفس الطلب) يحتوي فقط على
 * استدعاءات add_action()/add_filter() الخاصة به. لا حاجة لتعديل أي ملف نواة.
 *
 * نقاط الامتداد الحقيقية المتوفرة حالياً (موثَّقة بالتفصيل بـ abma/developers.php
 * قسم "13. نظام Hooks/Actions"):
 *   - do_action('ojuba_admin_login', $adminId, $email) — بعد نجاح أي تسجيل
 *     دخول للوحة التحكم (بما فيه الدخول عبر التحقق بخطوتين).
 *   - do_action('ojuba_blog_saved', $blogId, $isNew) — بعد إنشاء/تعديل مقال.
 *   - apply_filters('ojuba_render_vars', $vars, $templateName) — قبل عرض أي
 *     صفحة عامة عبر safeRender() (twigload.php) مباشرة: يسمح بتعديل/إضافة أي
 *     متغيّر Twig لأي صفحة (بما فيها الصفحة الرئيسية) دون تعديل أي ملف Twig
 *     أو أي ملف PHP جذري.
 */

$GLOBALS['ojuba_actions'] = [];
$GLOBALS['ojuba_filters'] = [];

/**
 * تسجيل دالة استجابة لحدث ($hook) — تُنفَّذ لاحقاً عند استدعاء do_action() لنفس
 * الاسم. $priority أقل = يُنفَّذ أولاً (نفس منطق WordPress).
 */
function add_action($hook, callable $callback, $priority = 10)
{
	$GLOBALS['ojuba_actions'][$hook][$priority][] = $callback;
	ksort($GLOBALS['ojuba_actions'][$hook]);
}

/**
 * تشغيل كل الدوال المسجَّلة لحدث معيَّن، بترتيب الأولوية، وتمرير أي وسائط
 * إضافية إليها كما هي (لا قيمة إرجاع — للأحداث/الإجراءات الجانبية فقط).
 */
function do_action($hook, ...$args)
{
	if (empty($GLOBALS['ojuba_actions'][$hook])) {
		return;
	}
	foreach ($GLOBALS['ojuba_actions'][$hook] as $callbacks) {
		foreach ($callbacks as $cb) {
			try {
				call_user_func_array($cb, $args);
			} catch (\Throwable $e) {
				error_log('[Hooks] فشل تنفيذ add_action لـ "' . $hook . '": ' . $e->getMessage());
			}
		}
	}
}

/**
 * تسجيل دالة تُعدِّل قيمة ($hook) — تستقبل القيمة الحالية كأول وسيط وتُعيد
 * القيمة المعدَّلة (يجب أن تُعيد قيمة دائماً، حتى لو لم تُغيّر شيئاً).
 */
function add_filter($hook, callable $callback, $priority = 10)
{
	$GLOBALS['ojuba_filters'][$hook][$priority][] = $callback;
	ksort($GLOBALS['ojuba_filters'][$hook]);
}

/**
 * تمرير قيمة عبر كل الفلاتر المسجَّلة لحدث معيَّن بالتتابع (خرج كل فلتر يصبح
 * دخل التالي)، وإعادة القيمة النهائية — تُستخدم لتعديل/إثراء بيانات موجودة
 * (بعكس do_action المخصَّصة للأحداث الجانبية بلا قيمة إرجاع).
 */
function apply_filters($hook, $value, ...$args)
{
	if (empty($GLOBALS['ojuba_filters'][$hook])) {
		return $value;
	}
	foreach ($GLOBALS['ojuba_filters'][$hook] as $callbacks) {
		foreach ($callbacks as $cb) {
			try {
				$value = call_user_func_array($cb, array_merge([$value], $args));
			} catch (\Throwable $e) {
				error_log('[Hooks] فشل تنفيذ add_filter لـ "' . $hook . '": ' . $e->getMessage());
			}
		}
	}
	return $value;
}

function has_action($hook)
{
	return !empty($GLOBALS['ojuba_actions'][$hook]);
}

function has_filter($hook)
{
	return !empty($GLOBALS['ojuba_filters'][$hook]);
}

/**
 * تحميل كل ملفات الإضافات المسجَّلة تلقائياً — أي ملف PHP مباشر داخل
 * includes/addons/ (وليس بمجلدات فرعية، لإبقاء الاصطلاح بسيطاً وواضحاً) يُضمَّن
 * هنا قبل أي فرصة لاستدعاء do_action()/apply_filters() بباقي الطلب.
 *
 * ملاحظة مهمة: لا تستخدم getpath() هنا رغم أنها الطريقة المعتادة بباقي الكود
 * لبناء مسار مطلق — hooks.php يُحمَّل من أعلى functions.php **قبل** استدعاء
 * gsite() (الذي يملأ $site من قاعدة البيانات)، فـ getpath() ستحاول قراءة
 * $site["site_folder"] بينما $site ما زال null بعد، مسبِّبة تحذير PHP. المسار
 * هنا يُبنى من __DIR__ (مجلد هذا الملف نفسه) بدلاً من ذلك، وهو مستقل تماماً
 * عن حالة $site.
 */
function ojubaLoadAddons()
{
	$dir = __DIR__ . '/addons/';
	if (!is_dir($dir)) {
		return;
	}
	$files = glob($dir . '*.php') ?: [];
	sort($files);
	foreach ($files as $file) {
		include_once $file;
	}
}
ojubaLoadAddons();
