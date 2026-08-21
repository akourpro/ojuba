<?php

/**
 * نظام التحقق من التحديثات والتحديث التلقائي (بضغطة واحدة) — أُعجوبة
 * ====================================================================
 *
 * مصدر التحديثات: إصدارات GitHub الرسمية (Releases) للمستودع العام
 * https://github.com/akourpro/ojuba — كل نشر جديد يجب أن يكون Release حقيقي
 * (وليس مجرد Push/Commit) بوسم إصدار بصيغة Semantic Versioning (مثل v1.1.0)
 * حتى تتعرّف عليه هذه الآلية. جسم الـ Release (Release notes/body) يُعرض
 * كسجل تغييرات (changelog) بصفحة "الإصدار والتحديثات" بلوحة التحكم.
 *
 * آلية التحديث تحمي: includes/config.php (بيانات الاتصال بقاعدة البيانات)،
 * مجلد files/ (كل الوسائط المرفوعة من صاحب الموقع)، وأي قوالب مستوردة يدوياً
 * غير موجودة أصلاً بمستودع GitHub (لا تُحذف لأن النسخ إضافي فقط: يُكتب فوق
 * الملفات الموجودة بالإصدار الجديد، ولا يُحذف أي ملف زائد بالموقع الحي) —
 * راجع updaterDefaultExcludes() أدناه للقائمة الكاملة. أي تعديل يدوي مباشر
 * على ملفات قالب رسمي (مثل templates/workup) سيُستبدَل بالتحديث لأن هذا
 * القالب جزء من المستودع نفسه — هذا سلوك متوقّع ويجب توضيحه للمستخدم بواجهة
 * صفحة التحديثات قبل أي تطبيق.
 *
 * .htaccess/.user.ini/js/functions.js **ليست** مستثناة من النسخ (تُستبدَل
 * بالنسخة الخام من GitHub في كل تحديث، لتصل أي قواعد Rewrite/حماية جديدة
 * فعلاً لكل المواقع المثبَّتة) — لكن مباشرة بعد النسخ تُستدعى
 * updaterReapplySiteEnvironmentPaths() لإعادة كتابة القيم الخاصة بهذا الموقع
 * تحديداً (المسار الحقيقي على القرص، مجلد الموقع الفرعي) فوق النسخة الخام،
 * تماماً كما تُعاد كتابة كتلة روابط المسارات المخصَّصة عبر
 * regenerateRouteHtaccess() بعدها مباشرة. **بدون هذه الخطوة يُعيد كل تحديث
 * سكربت ضبط `php_value include_path` على مسار بيئة تطوير السكربت نفسه بدل
 * مسار الاستضافة الفعلي — خطأ حقيقي اكتُشِف بواسطة مستخدم بعد أول تحديث حي.**
 */

const UPDATER_REPO = 'akourpro/ojuba';
const UPDATER_CHECK_INTERVAL_SECONDS = 6 * 3600; // فحص تلقائي دوري كل 6 ساعات كحد أقصى
const UPDATER_MAX_ZIP_BYTES = 120 * 1024 * 1024;  // 120 ميجا كحد أقصى لأرشيف الإصدار

function updaterGithubApiGet($path)
{
	$url = 'https://api.github.com/repos/' . UPDATER_REPO . '/' . ltrim($path, '/');
	$headers = [
		'Accept: application/vnd.github+json',
		'X-GitHub-Api-Version: 2022-11-28',
		'User-Agent: OjubaUpdater/1.0 (+https://github.com/' . UPDATER_REPO . ')',
	];

	if (function_exists('curl_init')) {
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 5,
			CURLOPT_TIMEOUT => 12,
			CURLOPT_CONNECTTIMEOUT => 8,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_SSL_VERIFYPEER => true,
		]);
		$body = curl_exec($ch);
		$err = curl_error($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if ($body === false || $err) {
			return ['ok' => false, 'error' => $err ?: 'فشل الاتصال بـ GitHub'];
		}
		if ($httpCode >= 400) {
			return ['ok' => false, 'error' => 'رمز استجابة GitHub: ' . $httpCode];
		}
		$decoded = json_decode($body, true);
		if (!is_array($decoded)) {
			return ['ok' => false, 'error' => 'استجابة GitHub غير صالحة'];
		}
		return ['ok' => true, 'data' => $decoded];
	}

	$context = stream_context_create([
		'http' => [
			'timeout' => 12,
			'header' => implode("\r\n", $headers) . "\r\n",
		],
	]);
	$body = @file_get_contents($url, false, $context);
	if ($body === false) {
		return ['ok' => false, 'error' => 'فشل الاتصال بـ GitHub (file_get_contents)'];
	}
	$decoded = json_decode($body, true);
	if (!is_array($decoded)) {
		return ['ok' => false, 'error' => 'استجابة GitHub غير صالحة'];
	}
	return ['ok' => true, 'data' => $decoded];
}

/**
 * جلب بيانات آخر Release رسمي منشور على المستودع، بشكل موحَّد وجاهز للمقارنة/العرض.
 */
function updaterFetchLatestRelease()
{
	$res = updaterGithubApiGet('releases/latest');
	if (!$res['ok']) {
		return ['ok' => false, 'error' => $res['error']];
	}
	$data = $res['data'];
	$tag = (string) ($data['tag_name'] ?? '');
	if ($tag === '') {
		return ['ok' => false, 'error' => 'لا يوجد إصدار (Release) منشور على المستودع بعد'];
	}
	$version = ltrim($tag, 'vV');

	return [
		'ok' => true,
		'tag' => $tag,
		'version' => $version,
		'name' => (string) ($data['name'] ?? $tag),
		'notes' => (string) ($data['body'] ?? ''),
		'published_at' => (string) ($data['published_at'] ?? ''),
		'html_url' => (string) ($data['html_url'] ?? ('https://github.com/' . UPDATER_REPO . '/releases/tag/' . $tag)),
		'zip_url' => (string) ($data['zipball_url'] ?? ('https://github.com/' . UPDATER_REPO . '/archive/refs/tags/' . $tag . '.zip')),
	];
}

/**
 * فحص متزامن مع حد معدّل داخلي (rate limit) — لا يتصل فعلياً بـ GitHub إلا إن
 * مضى UPDATER_CHECK_INTERVAL_SECONDS على آخر فحص ناجح، أو $force = true (فحص
 * يدوي صريح من زر "تحقق الآن"). يخزّن النتيجة كإعدادات settings عادية عبر
 * saveSetting() لتكون متاحة فوراً بكل صفحات لوحة التحكم عبر $site[...] دون أي
 * اتصال شبكة إضافي (راجع updaterAvailableInfo() للقراءة الخفيفة).
 */
function updaterCheckForUpdate($force = false)
{
	global $site;

	$lastChecked = !empty($site['update_last_checked_at']) ? strtotime($site['update_last_checked_at']) : 0;
	if (!$force && $lastChecked && (time() - $lastChecked) < UPDATER_CHECK_INTERVAL_SECONDS) {
		return ['ok' => true, 'skipped' => true, 'message' => 'تم التخطي (آخر فحص حديث)'];
	}

	$release = updaterFetchLatestRelease();
	saveSetting('update_last_checked_at', date('Y-m-d H:i:s'));
	$site['update_last_checked_at'] = date('Y-m-d H:i:s');

	if (!$release['ok']) {
		saveSetting('update_last_error', $release['error']);
		$site['update_last_error'] = $release['error'];
		return ['ok' => false, 'error' => $release['error']];
	}

	saveSetting('update_latest_version', $release['version']);
	saveSetting('update_latest_tag', $release['tag']);
	saveSetting('update_latest_name', $release['name']);
	saveSetting('update_latest_notes', $release['notes']);
	saveSetting('update_latest_published_at', $release['published_at']);
	saveSetting('update_latest_html_url', $release['html_url']);
	saveSetting('update_latest_zip_url', $release['zip_url']);
	saveSetting('update_last_error', '');

	foreach ($release as $k => $v) {
		$site['update_latest_' . $k] = $v;
	}

	return ['ok' => true, 'skipped' => false, 'release' => $release];
}

/**
 * قراءة خفيفة (بدون أي اتصال شبكة) لحالة التحديث بالاعتماد على آخر نتيجة
 * مخزَّنة بـsettings — تُستخدم بأي مكان يحتاج فقط معرفة "هل يوجد تحديث؟" بسرعة
 * (مثل شارة الإشعار بالقائمة الجانبية) دون إبطاء تحميل الصفحة بطلب HTTP خارجي.
 */
function updaterAvailableInfo()
{
	global $site;
	$current = installedVersion();
	$latest = trim((string) ($site['update_latest_version'] ?? ''));
	$available = false;
	if ($latest !== '' && preg_match('/^\d+(\.\d+){0,3}$/', $latest) && preg_match('/^\d+(\.\d+){0,3}$/', $current)) {
		$available = version_compare($current, $latest, '<');
	}
	return [
		'current' => $current,
		'latest' => $latest,
		'available' => $available,
		'name' => $site['update_latest_name'] ?? '',
		'notes' => $site['update_latest_notes'] ?? '',
		'published_at' => $site['update_latest_published_at'] ?? '',
		'html_url' => $site['update_latest_html_url'] ?? '',
		'checked_at' => $site['update_last_checked_at'] ?? '',
		'last_error' => $site['update_last_error'] ?? '',
		'last_applied_at' => $site['update_last_applied_at'] ?? '',
		'last_applied_version' => $site['update_last_applied_version'] ?? '',
	];
}

/**
 * حذف مجلد بالكامل (ملفات + مجلدات فرعية) — نفس منطق rrmdir() المستخدم أصلاً
 * بـ abma/api/templates_import.php، بنسخة خاصة هنا لأن هذا الملف مستقل ولا
 * يُحمَّل تلقائياً مع كل الصفحات.
 */
function updaterRemoveDir($dir)
{
	if (!is_dir($dir)) {
		return;
	}
	$items = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ($items as $item) {
		if ($item->isDir()) {
			@rmdir($item->getPathname());
		} else {
			@unlink($item->getPathname());
		}
	}
	@rmdir($dir);
}

/**
 * مجلد العمل المؤقت المحمي لعمليات التحديث (تنزيل/استخراج) — نفس اصطلاح
 * abma/tmp/theme_import المستخدم لاستيراد القوالب: محمي بـ.htaccess يمنع أي
 * وصول مباشر عبر الرابط، ويُنظَّف تلقائياً من أي بقايا أقدم من ساعتين.
 */
function updaterTmpRoot()
{
	$root = getpath() . 'abma/tmp/updates/';
	if (!is_dir($root)) {
		@mkdir($root, 0755, true);
	}
	$htaccess = $root . '.htaccess';
	if (!is_file($htaccess)) {
		@file_put_contents($htaccess, "Require all denied\n");
	}
	foreach ((glob($root . '*', GLOB_ONLYDIR) ?: []) as $oldDir) {
		if (@filemtime($oldDir) < time() - 7200) {
			updaterRemoveDir($oldDir);
		}
	}
	return $root;
}

/**
 * تنزيل ملف عن بعد مباشرة لملف على القرص (وليس بالذاكرة) — مهم هنا لأن أرشيف
 * الإصدار قد يكون عدة ميجابايتات، بعكس fetchRemoteUrl() العادية بـ
 * feed_importer.php المصمَّمة لصور/نصوص أصغر بكثير. يقبل فقط روابط GitHub
 * الرسمية (دفاع إضافي، رغم أن الرابط دائماً قادم من استجابة GitHub API نفسها
 * وليس من أي مُدخَل خارجي).
 */
function updaterDownloadZip($url, $destPath)
{
	$host = parse_url($url, PHP_URL_HOST);
	$allowedHosts = ['github.com', 'codeload.github.com', 'api.github.com'];
	if (!$host || !in_array(strtolower($host), $allowedHosts, true)) {
		return ['ok' => false, 'error' => 'مصدر تنزيل غير موثوق: ' . $host];
	}

	if (!function_exists('curl_init')) {
		return ['ok' => false, 'error' => 'إضافة cURL غير مفعّلة على السيرفر، لا يمكن تنزيل التحديث'];
	}

	$fp = @fopen($destPath, 'wb');
	if (!$fp) {
		return ['ok' => false, 'error' => 'تعذّر إنشاء ملف مؤقت لتنزيل التحديث'];
	}

	$ch = curl_init($url);
	curl_setopt_array($ch, [
		CURLOPT_FILE => $fp,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_MAXREDIRS => 5,
		CURLOPT_TIMEOUT => 180,
		CURLOPT_CONNECTTIMEOUT => 10,
		CURLOPT_USERAGENT => 'OjubaUpdater/1.0 (+https://github.com/' . UPDATER_REPO . ')',
		CURLOPT_SSL_VERIFYPEER => true,
	]);
	$success = curl_exec($ch);
	$err = curl_error($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	fclose($fp);

	if (!$success || $err) {
		@unlink($destPath);
		return ['ok' => false, 'error' => $err ?: 'فشل تنزيل أرشيف التحديث'];
	}
	if ($httpCode >= 400) {
		@unlink($destPath);
		return ['ok' => false, 'error' => 'رمز استجابة HTTP عند التنزيل: ' . $httpCode];
	}
	$size = @filesize($destPath);
	if (!$size) {
		@unlink($destPath);
		return ['ok' => false, 'error' => 'الملف المُنزَّل فارغ'];
	}
	if ($size > UPDATER_MAX_ZIP_BYTES) {
		@unlink($destPath);
		return ['ok' => false, 'error' => 'حجم أرشيف التحديث أكبر من الحد المسموح'];
	}
	return ['ok' => true, 'size' => $size];
}

/**
 * استخراج أرشيف الإصدار + اكتشاف المجلد اللاف الوحيد الذي تولّده GitHub تلقائياً
 * لأي zipball (بصيغة "{owner}-{repo}-{sha}/") وإرجاع المسار الفعلي لجذر ملفات
 * السكربت بعد تجاوز هذا المجلد — نفس منطق اكتشاف "المجلد اللاف" المستخدم أصلاً
 * باستيراد القوالب (abma/api/templates_import.php).
 */
function updaterExtractZip($zipPath, $destDir)
{
	if (!class_exists('ZipArchive')) {
		return ['ok' => false, 'error' => 'إضافة ZipArchive غير مفعّلة على السيرفر'];
	}
	$zip = new ZipArchive();
	if ($zip->open($zipPath) !== true) {
		return ['ok' => false, 'error' => 'تعذّر فتح أرشيف التحديث، قد يكون تالفاً'];
	}
	if ($zip->numFiles <= 0) {
		$zip->close();
		return ['ok' => false, 'error' => 'أرشيف التحديث فارغ'];
	}
	// حماية Zip Slip قبل الاستخراج الفعلي
	for ($i = 0; $i < $zip->numFiles; $i++) {
		$name = $zip->statIndex($i)['name'];
		if (strpos($name, '..') !== false || (isset($name[0]) && $name[0] === '/') || preg_match('#^[a-zA-Z]:#', $name)) {
			$zip->close();
			return ['ok' => false, 'error' => 'اسم ملف غير آمن داخل أرشيف التحديث: ' . $name];
		}
	}
	if (!@mkdir($destDir, 0755, true) && !is_dir($destDir)) {
		$zip->close();
		return ['ok' => false, 'error' => 'تعذّر إنشاء مجلد الاستخراج'];
	}
	if (!$zip->extractTo($destDir)) {
		$zip->close();
		return ['ok' => false, 'error' => 'فشل استخراج أرشيف التحديث'];
	}
	$zip->close();

	$entries = array_values(array_diff(scandir($destDir), ['.', '..']));
	$root = rtrim($destDir, '/');
	if (count($entries) === 1 && is_dir($destDir . '/' . $entries[0])) {
		$root = rtrim($destDir . '/' . $entries[0], '/');
	}
	if (!is_dir($root)) {
		return ['ok' => false, 'error' => 'تعذّر تحديد مجلد الملفات داخل الأرشيف'];
	}
	return ['ok' => true, 'root' => $root];
}

/**
 * المسارات (نسبية لجذر الموقع) التي لا يجب على التحديث لمسها إطلاقاً — بيانات
 * خاصة بهذا الموقع تحديداً وليست جزءاً من "كود السكربت" الذي يُحدَّث من
 * GitHub. أي مسار جديد يحمل بيانات خاصة بالموقع (وليس كوداً) يجب إضافته هنا.
 */
function updaterDefaultExcludes()
{
	return [
		'includes/config.php',   // بيانات الاتصال بقاعدة البيانات الخاصة بهذا الموقع
		'files/',                // كل الوسائط المرفوعة (شعارات، صور مقالات، مرفقات...)
		'.git/',                 // إن كان الموقع مُثبَّتاً عبر git clone
		'.github/',
		'backups/',              // نسخ التحديث الاحتياطية نفسها
		'abma/tmp/',             // مجلدات مؤقتة (استيراد قوالب/تحديثات جارية)
		'VERSION.local',         // احتياطي: لو رغب مطوّر مستقبلاً بتخصيص محلي منفصل
	];
}

function updaterPathIsExcluded($relativePath, $excludes)
{
	$relativePath = str_replace('\\', '/', $relativePath);
	foreach ($excludes as $ex) {
		$ex = rtrim($ex, '/');
		if ($relativePath === $ex || strpos($relativePath, $ex . '/') === 0) {
			return true;
		}
	}
	return false;
}

/**
 * إعادة تطبيق القيم الخاصة بهذا الموقع تحديداً على .htaccess/.user.ini/
 * js/functions.js مباشرة بعد نسخ ملفات التحديث — نفس فلسفة
 * regenerateRouteHtaccess() (إعادة تطبيق روابط المسارات المخصَّصة فوق أي
 * تحديث لقواعد .htaccess الأساسية) لكن لثلاثة ملفات إضافية اكتُشِفت المشكلة
 * فيها بواسطة مستخدم حقيقي: نسخ ملفات التحديث (updaterCopyTree) يستبدل هذه
 * الملفات بالنسخة الخام كما هي بمستودع GitHub — وهي تحمل مساراً افتراضياً
 * (بيئة تطوير السكربت نفسه، مثل `/Applications/XAMPP/htdocs/ojuba/`)، وليس
 * المسار الحقيقي لهذا الموقع على استضافته الفعلية. بدون إعادة التطبيق هذه،
 * أي تحديث سكربت يُعيد ضبط `php_value include_path` (وما يعادلها بـ
 * .user.ini) لمسار خاطئ تماماً، فيكسر تحميل الملفات (`autoload.php` وغيره)
 * فوراً بعد كل تحديث حتى لو نجحت كل خطوات النسخ الأخرى بلا أي خطأ ظاهر.
 * لا تفشل عملية التحديث كاملة إن تعذّر أيّ من هذه الثلاث (صلاحيات كتابة
 * مثلاً، أو عدم وجود .user.ini أصلاً على بعض البيئات) — نفس اصطلاح استدعاءات
 * installerUpdateHtaccess()/installerUpdateUserIni()/installerUpdateFunctionsJs()
 * غير الفاشلة بخطوة "معلومات الموقع" بمعالج التثبيت (install/index.php)، التي
 * تكرَّر منطقها هنا عمداً (وليس عبر require) لأن install/index.php مستقل
 * تماماً عن سلسلة تحميل functions.php/updater.php حتى خطواته الأخيرة فقط.
 */
function updaterReapplySiteEnvironmentPaths($rootDir)
{
	$rootDir = rtrim($rootDir, '/') . '/';

	global $site;
	$siteFolder = trim($site['site_folder'] ?? '', '/');
	$prefix = $siteFolder !== '' ? '/' . $siteFolder : '';

	$real = realpath($rootDir);
	$realPath = rtrim(str_replace('\\', '/', $real !== false ? $real : $rootDir), '/') . '/';

	// .htaccess: أسطر php_value include_path (مكرَّرة 3 مرات داخل كتل
	// <IfModule mod_php.c>/mod_php7.c/mod_php8.c>) + أسطر ErrorDocument الأربعة
	$htaccessPath = $rootDir . '.htaccess';
	$htaccessContent = @file_get_contents($htaccessPath);
	if ($htaccessContent !== false) {
		$htaccessContent = preg_replace('/^\s*php_value include_path ".*"$/m', '    php_value include_path "' . $realPath . '"', $htaccessContent);
		$htaccessContent = preg_replace('/^ErrorDocument 404 .*$/m', 'ErrorDocument 404 ' . $prefix . '/errors/404.php', $htaccessContent, 1);
		$htaccessContent = preg_replace('/^ErrorDocument 500 .*$/m', 'ErrorDocument 500 ' . $prefix . '/errors/500.php', $htaccessContent, 1);
		$htaccessContent = preg_replace('/^ErrorDocument 401 .*$/m', 'ErrorDocument 401 ' . $prefix . '/errors/401.php', $htaccessContent, 1);
		$htaccessContent = preg_replace('/^ErrorDocument 403 .*$/m', 'ErrorDocument 403 ' . $prefix . '/errors/404.php', $htaccessContent, 1);
		@file_put_contents($htaccessPath, $htaccessContent);
	}

	// .user.ini: open_basedir + include_path (النظير المتوافق مع استضافات PHP-FPM/CGI)
	$iniPath = $rootDir . '.user.ini';
	$iniContent = @file_get_contents($iniPath);
	if ($iniContent !== false) {
		$iniContent = preg_replace('/^open_basedir=.*$/m', 'open_basedir=' . $realPath . ':/tmp/', $iniContent, 1);
		$iniContent = preg_replace('/^include_path=".*"$/m', 'include_path="' . $realPath . '"', $iniContent, 1);
		@file_put_contents($iniPath, $iniContent);
	}

	// js/functions.js: مسار ترجمة SweetAlert2 الثابت — يعود دائماً لصيغته
	// الأصلية "/cms/includes/lang/" بعد كل نسخ تحديث لأنه الملف الخام كما هو
	// بمستودع GitHub، لذا الاستبدال الحرفي (وليس regex) يعمل بأمان في كل مرة.
	$jsPath = $rootDir . 'js/functions.js';
	$jsContent = @file_get_contents($jsPath);
	if ($jsContent !== false) {
		$jsContent = str_replace('"/cms/includes/lang/"', '"' . $prefix . '/includes/lang/"', $jsContent);
		@file_put_contents($jsPath, $jsContent);
	}

	return true;
}

/**
 * نسخ شجرة ملفات الإصدار الجديد فوق جذر الموقع الحالي: يكتب فوق كل ملف موجود
 * بالأرشيف الجديد، وينشئ أي ملف/مجلد جديد غير موجود سابقاً، لكنه **لا يحذف**
 * أي ملف موجود بالموقع الحي وغير موجود بالأرشيف (نسخ إضافي وليس مطابقة كاملة
 * mirror) — يحمي هذا أي قالب مستورَد يدوياً أو تعديل خاص أضافه صاحب الموقع
 * بملفات غير رسمية، على حساب عدم حذف ملفات قديمة أُزيلت فعلياً من إصدار لاحق
 * (اختيار مقصود لتفادي حذف بيانات بالخطأ).
 */
function updaterCopyTree($srcRoot, $destRoot, $excludes)
{
	$srcRoot = rtrim($srcRoot, '/');
	$destRoot = rtrim($destRoot, '/');
	$baseLen = strlen($srcRoot) + 1;

	$copied = 0;
	$skipped = 0;
	$errors = [];

	$items = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($srcRoot, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::SELF_FIRST
	);
	foreach ($items as $item) {
		$relative = substr($item->getPathname(), $baseLen);
		if ($relative === false || $relative === '') {
			continue;
		}
		if (updaterPathIsExcluded($relative, $excludes)) {
			$skipped++;
			continue;
		}
		$target = $destRoot . '/' . $relative;
		if ($item->isDir()) {
			if (!is_dir($target)) {
				@mkdir($target, 0755, true);
			}
			continue;
		}
		$targetDir = dirname($target);
		if (!is_dir($targetDir)) {
			@mkdir($targetDir, 0755, true);
		}
		if (@copy($item->getPathname(), $target)) {
			$copied++;
		} else {
			$errors[] = $relative;
		}
	}

	return ['copied' => $copied, 'skipped' => $skipped, 'errors' => $errors];
}

/**
 * تفريغ قاعدة البيانات الحالية إلى ملف SQL — نفس منطق type=db بـ
 * abma/api/backup.php بالضبط، لكن الكتابة لملف بدل الإرسال المباشر للمتصفح،
 * لتضمينه داخل أرشيف النسخة الاحتياطية التلقائية قبل التحديث.
 */
function updaterWriteDatabaseDump($destSqlPath)
{
	global $con;
	$fp = @fopen($destSqlPath, 'w');
	if (!$fp) {
		return false;
	}

	$dbName = defined('DATABASE') ? DATABASE : 'database';
	fwrite($fp, "-- نسخة احتياطية تلقائية قبل تحديث السكربت: $dbName\n");
	fwrite($fp, "-- تاريخ الإنشاء: " . date('Y-m-d H:i:s') . "\n");
	fwrite($fp, "SET FOREIGN_KEY_CHECKS=0;\n\n");

	$tables = $con->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
	foreach ($tables as $table) {
		fwrite($fp, "DROP TABLE IF EXISTS `$table`;\n");
		$createRow = $con->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
		fwrite($fp, $createRow['Create Table'] . ";\n\n");

		$rowCount = (int) $con->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
		if ($rowCount > 0) {
			$chunkSize = 500;
			$offset = 0;
			while ($offset < $rowCount) {
				$dataStmt = $con->query("SELECT * FROM `$table` LIMIT $chunkSize OFFSET $offset");
				$cols = '';
				$valuesList = [];
				while ($row = $dataStmt->fetch(PDO::FETCH_ASSOC)) {
					if ($cols === '') {
						$cols = implode('`, `', array_keys($row));
					}
					$vals = array_map(function ($v) use ($con) {
						return $v === null ? 'NULL' : $con->quote($v);
					}, array_values($row));
					$valuesList[] = '(' . implode(', ', $vals) . ')';
				}
				if (!empty($valuesList)) {
					fwrite($fp, "INSERT INTO `$table` (`$cols`) VALUES\n" . implode(",\n", $valuesList) . ";\n");
				}
				$offset += $chunkSize;
			}
			fwrite($fp, "\n");
		}
	}
	fwrite($fp, "SET FOREIGN_KEY_CHECKS=1;\n");
	fclose($fp);
	return true;
}

/**
 * إنشاء نسخة احتياطية كاملة (كل ملفات الموقع ما عدا files/ والمجلدات المؤقتة +
 * تفريغ كامل لقاعدة البيانات كملف database.sql) داخل أرشيف zip واحد — تُحفَظ
 * بمجلد backups/ (محمي بـ.htaccess) بجذر الموقع ليتمكن صاحب الموقع من تنزيلها
 * والرجوع إليها يدوياً عند الحاجة.
 *
 * تُستخدم من نقطتين: قبل أي تطبيق فعلي للتحديث (بادئة "pre-update-backup"،
 * فشلها يوقف عملية التحديث بالكامل ولا يُتابَع لنسخ أي ملف)، وأيضاً من النسخ
 * الاحتياطية الدورية التلقائية (بادئة "scheduled-backup"، راجع
 * updaterMaybeRunScheduledBackup() أدناه) — البادئتان منفصلتان تماماً حتى لا
 * تتداخل آلية تقليم كل نوع (pruning) مع الأخرى.
 */
function updaterCreateBackup($prefix = 'pre-update-backup')
{
	if (!class_exists('ZipArchive')) {
		return ['ok' => false, 'error' => 'إضافة ZipArchive غير مفعّلة، لا يمكن إنشاء نسخة احتياطية'];
	}

	$backupsDir = getpath() . 'backups/';
	if (!is_dir($backupsDir)) {
		@mkdir($backupsDir, 0755, true);
	}
	$htaccess = $backupsDir . '.htaccess';
	if (!is_file($htaccess)) {
		@file_put_contents($htaccess, "Require all denied\n");
	}

	$filename = $prefix . '-' . installedVersion() . '-' . date('Y-m-d_His') . '.zip';
	$zipPath = $backupsDir . $filename;

	$zip = new ZipArchive();
	if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
		return ['ok' => false, 'error' => 'تعذّر إنشاء ملف النسخة الاحتياطية'];
	}

	// تفريغ قاعدة البيانات لملف مؤقت ثم إضافته للأرشيف
	$tmpSql = updaterTmpRoot() . 'db-' . bin2hex(random_bytes(6)) . '.sql';
	if (updaterWriteDatabaseDump($tmpSql)) {
		$zip->addFile($tmpSql, 'database.sql');
	}

	$siteRoot = rtrim(getpath(), '/');
	$excludes = ['backups', 'files', '.git', '.github', 'abma/tmp'];
	$baseLen = strlen($siteRoot) + 1;
	$items = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($siteRoot, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::SELF_FIRST
	);
	foreach ($items as $item) {
		$relative = substr($item->getPathname(), $baseLen);
		if ($relative === false || $relative === '') {
			continue;
		}
		$skip = false;
		foreach ($excludes as $ex) {
			if ($relative === $ex || strpos($relative, $ex . '/') === 0) {
				$skip = true;
				break;
			}
		}
		if ($skip) {
			continue;
		}
		if ($item->isDir()) {
			$zip->addEmptyDir($relative);
		} else {
			$zip->addFile($item->getPathname(), $relative);
		}
	}
	$zip->close();
	if (is_file($tmpSql)) {
		@unlink($tmpSql);
	}

	if (!is_file($zipPath) || filesize($zipPath) <= 0) {
		return ['ok' => false, 'error' => 'فشل إنشاء أرشيف النسخة الاحتياطية'];
	}

	return ['ok' => true, 'file' => $filename, 'size' => filesize($zipPath)];
}

/**
 * إعادة تشغيل كل ملفات abma/* /migrate.php المعروفة بالسكربت — آمنة تماماً
 * لإعادة التشغيل (CREATE TABLE IF NOT EXISTS / ALTER TABLE مع تجاهل صامت لخطأ
 * "العمود موجود مسبقاً"، حسب الاصطلاح الموثَّق بـCLAUDE.md لكل وحدة اختيارية)
 * — تضمن أن أي عمود/جدول جديد أضافته نسخة أحدث من السكربت يُهيَّأ تلقائياً
 * بقاعدة بيانات هذا الموقع فور اكتمال نسخ الملفات، دون أي خطوة يدوية إضافية
 * من صاحب الموقع (لا حاجة لزيارة صفحة "ترحيل" كل وحدة على حدة).
 *
 * كل ملف يُضمَّن داخل دالة معزولة (نطاق متغيرات مستقل) مع احتواء أي مخرجات
 * HTML يولّدها الملف (كل migrate.php مصمَّم كصفحة إدارية كاملة تعرض تقرير
 * النتيجة، وليس كدالة تُعيد قيمة) بمخزن مؤقت (ob_start) يُتجاهَل هنا، لأننا
 * نهتم فقط بتنفيذ استعلامات SQL الآمنة بداخله، لا بعرض HTML الناتج.
 */
function updaterRunCoreMigrations()
{
	$migrateFiles = [
		'abma/blogs/migrate.php',
		'abma/pricing/migrate.php',
		'abma/mailing/migrate.php',
		'abma/ads/migrate.php',
		'abma/matches/migrate.php',
		'abma/standings/migrate.php',
		'abma/videos/migrate.php',
		'abma/feeds/migrate.php',
		'abma/users/migrate.php',
	];

	$results = [];
	foreach ($migrateFiles as $rel) {
		$full = getpath() . $rel;
		if (!is_file($full)) {
			continue;
		}
		$ran = (function () use ($full) {
			ob_start();
			try {
				include $full;
				$ok = true;
			} catch (\Throwable $e) {
				$ok = false;
			}
			ob_end_clean();
			return $ok;
		})();
		$results[] = ['file' => $rel, 'ok' => $ran];
	}
	return $results;
}

/**
 * وضع "الصيانة" أثناء تطبيق التحديث — نقطة ضعف عملية بالنظام السابق: لو زائر
 * فتح الموقع أثناء نسخ ملفات الإصدار الجديد فعلياً (ولو لثوانٍ)، قد يواجه
 * أخطاء من ملفات نصف مُحدَّثة (مزيج ملفات قديمة/جديدة بنفس الطلب). الحل: ملف
 * علامة بسيط بجذر الموقع (.maintenance) يتحقق منه autoload.php (المُضمَّن
 * تلقائياً بكل صفحة عامة عبر auto_prepend_file) قبل تحميل Twig — لوحة التحكم
 * (abma/*) تستخدم سلسلة autoload منفصلة تماماً فلا تتأثر إطلاقاً، فصاحب الموقع
 * يبقى قادراً على متابعة تقدّم التحديث بصفحة "الإصدار والتحديثات" بينما الموقع
 * العام يعرض صفحة "قيد الصيانة" للزوار. نقاط api/*.php العامة (مثل نبضة
 * api/update-check.php نفسها) مستثناة أيضاً عمداً — راجع الفحص بـautoload.php.
 */
function updaterMaintenanceFlagPath()
{
	return getpath() . '.maintenance';
}

function updaterEnableMaintenanceMode($message = '')
{
	$message = $message !== '' ? $message : 'الموقع قيد الصيانة حالياً بسبب تحديث جارٍ للسكربت، سنعود خلال دقائق.';
	@file_put_contents(updaterMaintenanceFlagPath(), $message);
}

function updaterDisableMaintenanceMode()
{
	$flag = updaterMaintenanceFlagPath();
	if (is_file($flag)) {
		@unlink($flag);
	}
}

/**
 * تقليم النسخ الاحتياطية الدورية التلقائية (وليس نسخ ما-قبل-التحديث، التي
 * تبقى محفوظة دائماً كسجل تاريخي لكل تحديث تم تطبيقه) — يُبقي فقط على آخر
 * $keep نسخة، ويحذف الأقدم لتفادي امتلاء مساحة التخزين بالاستضافة بمرور الوقت.
 */
function updaterPruneScheduledBackups($keep)
{
	$backupsDir = getpath() . 'backups/';
	$files = glob($backupsDir . 'scheduled-backup-*.zip') ?: [];
	if (count($files) <= $keep) {
		return;
	}
	usort($files, function ($a, $b) {
		return filemtime($b) <=> filemtime($a);
	});
	$toDelete = array_slice($files, $keep);
	foreach ($toDelete as $f) {
		@unlink($f);
	}
}

/**
 * نسخ احتياطية دورية تلقائية — منفصلة تماماً عن النسخة الاحتياطية التي تُؤخَذ
 * قبل كل تحديث (تلك مضمونة الحدوث دائماً، غير قابلة للتعطيل). هذه ميزة إضافية
 * اختيارية (settings.scheduled_backup_enabled، افتراضياً مفعّلة بفاصل أسبوعي)
 * لصاحب الموقع الذي يريد نسخاً احتياطية دورية حتى بدون تحديثات. تُستدعى من
 * "نبضة" api/update-check.php (لا حاجة لنبضة/Cron منفصلة) — محمية داخلياً بحد
 * معدّل يعتمد على settings.scheduled_backup_last_at، فآمنة للاستدعاء المتكرر.
 */
function updaterMaybeRunScheduledBackup()
{
	global $site;

	$enabled = ($site['scheduled_backup_enabled'] ?? '1') === '1';
	if (!$enabled) {
		return ['ok' => true, 'skipped' => true, 'reason' => 'disabled'];
	}

	$intervalDays = max(1, (int) ($site['scheduled_backup_interval_days'] ?? 7));
	$lastAt = !empty($site['scheduled_backup_last_at']) ? strtotime($site['scheduled_backup_last_at']) : 0;
	if ($lastAt && (time() - $lastAt) < ($intervalDays * 86400)) {
		return ['ok' => true, 'skipped' => true, 'reason' => 'rate_limited'];
	}

	$backup = updaterCreateBackup('scheduled-backup');
	saveSetting('scheduled_backup_last_at', date('Y-m-d H:i:s'));
	$site['scheduled_backup_last_at'] = date('Y-m-d H:i:s');
	if (!$backup['ok']) {
		saveSetting('scheduled_backup_last_error', $backup['error']);
		return $backup;
	}
	saveSetting('scheduled_backup_last_error', '');

	$keep = max(1, (int) ($site['scheduled_backup_keep'] ?? 5));
	updaterPruneScheduledBackups($keep);

	return $backup;
}

/**
 * هل الانتقال من $current إلى $latest هو رفع "رقم تصحيح فقط" (نفس major.minor،
 * زيادة بآخر رقم فقط)؟ تُستخدم لآلية "قناة التحديثات الأمنية/التصحيحية" —
 * GitHub Releases لا يملك علامة رسمية "أمني فقط" منفصلة عن أي Release آخر،
 * فالتفسير العملي المعتمَد هنا هو: أي رفع إصدار لا يغيّر إلا رقم Patch بمخطط
 * Semantic Versioning (مثل 1.2.3 ← 1.2.4) يُعتبر تحديثاً تصحيحياً/أمنياً
 * منخفض المخاطر يمكن تطبيقه تلقائياً دون تدخل يدوي، بعكس أي رفع Minor أو Major
 * (1.2.x ← 1.3.0 أو 2.0.0) الذي يبقى يتطلّب ضغط "تحديث الآن" يدوياً دائماً.
 */
function updaterIsPatchOnlyBump($current, $latest)
{
	$c = array_pad(explode('.', (string) $current), 3, '0');
	$l = array_pad(explode('.', (string) $latest), 3, '0');
	return $c[0] === $l[0] && $c[1] === $l[1] && version_compare($current, $latest, '<');
}

/**
 * الدالة المحورية: تُنفِّذ عملية التحديث الكاملة من أولها لآخرها بالترتيب
 * الآمن التالي: التحقق من وجود إصدار أحدث فعلياً ← تنزيل الأرشيف ← نسخة
 * احتياطية كاملة (تُوقِف العملية إن فشلت) ← استخراج الأرشيف ← نسخ الملفات
 * (مع استثناء المسارات الخاصة بالموقع) ← إعادة توليد الكتلة المُدارة بـ
 * .htaccess (لحفظ مسارات المستخدم المخصَّصة فوق أي تحديث لقواعد .htaccess
 * الأساسية) ← إعادة تشغيل كل ترحيلات قواعد البيانات المعروفة ← تنظيف الملفات
 * المؤقتة ← تسجيل النتيجة. تُعيد مصفوفة خطوات (log) مفصَّلة لعرضها بواجهة
 * لوحة التحكم، بنفس فلسفة $results بملفات migrate.php.
 */
function updaterApplyUpdate()
{
	// عملية التحديث (تنزيل + نسخة احتياطية كاملة + نسخ آلاف الملفات المحتملة)
	// قد تتجاوز max_execution_time الافتراضي (غالباً 30 ثانية) على استضافات
	// مشتركة كثيرة — نحاول رفعه هنا احتياطياً، مع تجاهل صامت إن كانت الدالة
	// معطَّلة عبر disable_functions بإعدادات PHP بالاستضافة.
	@set_time_limit(300);

	$log = [];
	$push = function ($ok, $text) use (&$log) {
		$log[] = ['ok' => $ok, 'text' => $text];
	};

	$release = updaterFetchLatestRelease();
	if (!$release['ok']) {
		$push(false, 'تعذّر جلب بيانات آخر إصدار: ' . $release['error']);
		return ['ok' => false, 'log' => $log];
	}
	if (!version_compare(installedVersion(), $release['version'], '<')) {
		$push(false, 'لا يوجد إصدار أحدث من الإصدار المثبَّت حالياً (' . installedVersion() . ')');
		return ['ok' => false, 'log' => $log];
	}
	$push(true, 'تم العثور على إصدار جديد: ' . $release['version']);

	// من هذه النقطة فصاعداً نلتزم فعلياً بتطبيق التحديث (تنزيل + نسخ ملفات) —
	// نُفعّل وضع الصيانة للزوار العامّين فوراً، ونضمن إلغاءه دائماً عبر finally
	// بغض النظر عن نجاح العملية أو فشلها بأي خطوة لاحقة، حتى لا يبقى الموقع
	// عالقاً بصفحة "قيد الصيانة" للأبد بسبب خطأ غير متوقَّع.
	updaterEnableMaintenanceMode('الموقع قيد الصيانة حالياً بسبب تحديث السكربت إلى الإصدار ' . $release['version'] . '، سنعود خلال دقائق.');

	try {
		$tmpRoot = updaterTmpRoot();
		$token = bin2hex(random_bytes(8));
		$zipPath = $tmpRoot . 'release-' . $token . '.zip';
		$extractDir = $tmpRoot . 'extract-' . $token;

		$download = updaterDownloadZip($release['zip_url'], $zipPath);
		if (!$download['ok']) {
			$push(false, 'فشل تنزيل أرشيف التحديث: ' . $download['error']);
			return ['ok' => false, 'log' => $log];
		}
		$push(true, 'تم تنزيل أرشيف الإصدار (' . round($download['size'] / 1024 / 1024, 2) . ' م.ب)');

		$backup = updaterCreateBackup();
		if (!$backup['ok']) {
			@unlink($zipPath);
			$push(false, 'تعذّر إنشاء نسخة احتياطية قبل التحديث — تم إلغاء العملية بأمان: ' . $backup['error']);
			return ['ok' => false, 'log' => $log];
		}
		$push(true, 'تم إنشاء نسخة احتياطية كاملة (backups/' . $backup['file'] . ')');

		$extract = updaterExtractZip($zipPath, $extractDir);
		@unlink($zipPath);
		if (!$extract['ok']) {
			$push(false, 'فشل استخراج أرشيف التحديث: ' . $extract['error']);
			updaterRemoveDir($extractDir);
			return ['ok' => false, 'log' => $log];
		}
		$push(true, 'تم استخراج ملفات الإصدار الجديد');

		$rootDir = rtrim(getpath(), '/');
		$copy = updaterCopyTree($extract['root'], $rootDir, updaterDefaultExcludes());
		updaterRemoveDir($extractDir);
		$push(true, 'تم نسخ ' . $copy['copied'] . ' ملفاً' . (!empty($copy['errors']) ? (' (تعذّر نسخ ' . count($copy['errors']) . ' ملف)') : ''));

		// إعادة تطبيق المسار الحقيقي لهذا الموقع تحديداً على .htaccess/.user.ini/
		// js/functions.js — النسخ أعلاه استبدلها بالنسخة الخام من GitHub التي تحمل
		// مساراً افتراضياً (بيئة تطوير السكربت)، وليس مسار هذه الاستضافة الفعلي.
		updaterReapplySiteEnvironmentPaths($rootDir);
		$push(true, 'تمت إعادة تطبيق المسار الحقيقي لهذا الموقع على .htaccess/.user.ini');

		if (function_exists('regenerateRouteHtaccess')) {
			regenerateRouteHtaccess();
			$push(true, 'تمت إعادة تطبيق روابط المسارات المخصَّصة على .htaccess');
		}

		$migrations = updaterRunCoreMigrations();
		$failedMigrations = array_filter($migrations, function ($m) {
			return !$m['ok'];
		});
		$push(empty($failedMigrations), 'تمت إعادة فحص/تحديث قاعدة البيانات لكل الوحدات (' . count($migrations) . ' وحدة)');

		$newVersion = installedVersion(true);
		saveSetting('update_last_applied_at', date('Y-m-d H:i:s'));
		saveSetting('update_last_applied_version', $newVersion);
		saveSetting('update_last_checked_at', ''); // إجبار فحص جديد بالمرة القادمة ليعكس الحالة الفعلية بدقة

		if (function_exists('logAction')) {
			logAction('script_update', 'تم تحديث السكربت من ' . $release['tag'] . ' إلى الإصدار ' . $newVersion);
		}

		$push(true, 'اكتمل التحديث بنجاح — الإصدار الحالي الآن: ' . $newVersion);

		return [
			'ok' => true,
			'log' => $log,
			'version' => $newVersion,
			'backup_file' => $backup['file'],
		];
	} finally {
		updaterDisableMaintenanceMode();
	}
}
