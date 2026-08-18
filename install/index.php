<?php

session_start();

// $installDir: مجلد هذا الملف نفسه (install/) — يُستخدم فقط لقراءة db.sql المجاور.
// $rootDir: جذر الموقع الفعلي (مجلد واحد للأعلى) — كل شيء آخر (config.php,
// includes/, files/, .htaccess) يُقرأ/يُكتَب بالنسبة له.
$installDir = __DIR__ . '/';
$rootDir = dirname(__DIR__) . '/';
$configPath = $rootDir . 'includes/config.php';

// ===== حارس: رفض العمل إن كان السكربت مثبَّتاً بالفعل =====
if (is_file($configPath)) {
	require_once $configPath;
	try {
		$guardCon = new PDO("mysql:host=" . HOST . ";dbname=" . DATABASE . ";charset=" . CHARSET, USER, PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]);
		$hasAdminsTable = $guardCon->query("SHOW TABLES LIKE 'admins'")->rowCount() > 0;
		if ($hasAdminsTable) {
			$adminCount = (int) $guardCon->query("SELECT COUNT(*) FROM admins")->fetchColumn();
			if ($adminCount > 0) {
				http_response_code(403);
				die('<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><title>مثبَّت بالفعل</title></head><body style="font-family:sans-serif;text-align:center;padding:100px 20px;background:#f6f5fb"><h2>السكربت مثبَّت بالفعل</h2><p style="color:#666">لأسباب أمنية، لا يمكن إعادة تشغيل معالج التثبيت على تركيبة قائمة.</p><p><b>يُنصح بشدة بحذف مجلد install/ بالكامل من الاستضافة الآن.</b></p><p><a href="../abma/auth/login">الذهاب لتسجيل الدخول</a></p></body></html>');
			}
		}
	} catch (\Throwable $e) {
		// فشل الاتصال ببيانات config.php الحالية (أو الجدول غير موجود بعد) — على
		// الأرجح تثبيت سابق متوقّف في منتصف الطريق، نسمح بالمتابعة/إعادة المحاولة.
	}
}

$step = max(1, min(6, (int) ($_GET['step'] ?? 1)));
$errors = [];
$dbFormValues = ['host' => 'localhost', 'user' => 'root', 'pass' => '', 'name' => 'ojuba'];

/**
 * استخراج جمل CREATE TABLE / ALTER TABLE (المفاتيح الأساسية وAUTO_INCREMENT)
 * فقط من db.sql — تتجاهل عمداً كل جمل INSERT INTO (بيانات تجريبية شخصية
 * لمطوّر السكربت لا يجب أن تصل لأي تركيبة جديدة). تُنظِّف أيضاً أي قيمة
 * AUTO_INCREMENT=N رقمية موروثة من بيانات المطوّر (التركيبة الجديدة يجب أن
 * تبدأ ترقيمها من 1 دائماً بما أننا لا نستورد أي صف).
 *
 * ملاحظة مهمة (خطأ اكتُشِف وأُصلِح): كل جملة بملف تصدير phpMyAdmin مسبوقة
 * بأسطر تعليق SQL (تبدأ بـ --) ضمن نفس القطعة المفصولة بالفاصلة المنقوطة —
 * مثال: "--\n-- Table structure for table `admins`\n--\n\nCREATE TABLE...".
 * التحقق من بداية القطعة مباشرة بـ CREATE/ALTER TABLE (بدون تجريد أسطر
 * التعليق أولاً) كان يفشل دائماً لكل جدول، ما يعني عملياً **صفر جداول تُستورَد
 * بأي تثبيت جديد**. الإصلاح: تجريد أسطر التعليق (والأسطر الفارغة) من بداية كل
 * قطعة عبر installerStripLeadingComments() قبل فحص البادئة.
 */
function installerStripLeadingComments($chunk)
{
	$lines = explode("\n", $chunk);
	$i = 0;
	while ($i < count($lines) && (trim($lines[$i]) === '' || strpos(trim($lines[$i]), '--') === 0)) {
		$i++;
	}
	return trim(implode("\n", array_slice($lines, $i)));
}

function installerExtractSchemaStatements($sql)
{
	$statements = preg_split('/;\s*\n/', $sql);
	$out = [];
	foreach ($statements as $stmt) {
		$trimmed = installerStripLeadingComments(trim($stmt));
		if ($trimmed === '') {
			continue;
		}
		if (preg_match('/^CREATE TABLE/i', $trimmed)) {
			$out[] = $trimmed . ';';
		} elseif (preg_match('/^ALTER TABLE/i', $trimmed) && (stripos($trimmed, 'ADD PRIMARY KEY') !== false || stripos($trimmed, 'AUTO_INCREMENT') !== false)) {
			$cleaned = preg_replace('/,\s*AUTO_INCREMENT\s*=\s*\d+/i', '', $trimmed);
			$out[] = $cleaned . ';';
		}
	}
	return $out;
}

function installerDetectSiteUrl()
{
	$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
	$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
	// dirname(SCRIPT_NAME) لهذا الملف يُعيد مسار مجلد install/ نفسه (لأن الملف
	// أصبح install/index.php) — نحذف لاحقة "/install" لنحصل على رابط جذر
	// الموقع الفعلي، وليس رابط مجلد المعالج.
	$dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
	$dir = preg_replace('#/install$#i', '', $dir);
	return $scheme . $host . $dir . '/';
}

/**
 * تحديث سطرَي .htaccess اللذين يعتمدان على المسار الفعلي للتركيبة على القرص
 * (وليس على رابط الموقع نفسه):
 * - php_value include_path — المسار المطلق الحقيقي لجذر المشروع على السيرفر
 *   (يختلف حتماً عن مسار مطوّر السكربت الأصلي المُثبَّت افتراضياً بالملف).
 * - ErrorDocument (404/500/401/403) — يجب أن تبدأ بمسار المجلد الفرعي
 *   (site_folder) إن كان السكربت مثبَّتاً داخل مجلد فرعي، أو بدون أي بادئة إن
 *   كان مثبَّتاً بجذر النطاق مباشرة.
 * لا تلمس أي سطر آخر بالملف (بما فيها كتلة BEGIN/END CUSTOM ROUTES المُدارة
 * بدالة أخرى تماماً — regenerateRouteHtaccess() بـ includes/functions.php).
 */
function installerUpdateHtaccess($rootDir, $siteFolder)
{
	$htaccessPath = $rootDir . '.htaccess';
	$content = @file_get_contents($htaccessPath);
	if ($content === false) {
		return false;
	}

	$real = realpath($rootDir);
	$realPath = rtrim(str_replace('\\', '/', $real !== false ? $real : $rootDir), '/') . '/';
	$prefix = $siteFolder !== '' ? '/' . trim($siteFolder, '/') : '';

	$content = preg_replace('/^php_value include_path ".*"$/m', 'php_value include_path "' . $realPath . '"', $content, 1);
	$content = preg_replace('/^ErrorDocument 404 .*$/m', 'ErrorDocument 404 ' . $prefix . '/errors/404.php', $content, 1);
	$content = preg_replace('/^ErrorDocument 500 .*$/m', 'ErrorDocument 500 ' . $prefix . '/errors/500.php', $content, 1);
	$content = preg_replace('/^ErrorDocument 401 .*$/m', 'ErrorDocument 401 ' . $prefix . '/errors/401.php', $content, 1);
	$content = preg_replace('/^ErrorDocument 403 .*$/m', 'ErrorDocument 403 ' . $prefix . '/errors/404.php', $content, 1);

	return @file_put_contents($htaccessPath, $content) !== false;
}

/**
 * js/functions.js يحمل مساراً مطلقاً ثابتاً لملف ترجمة نصوص JS
 * (SweetAlert2...): `url: "/cms/includes/lang/" + languFile,` — "cms" هنا هو
 * اسم مجلد بيئة تطوير السكربت الأصلية، وليس شيئاً عاماً. إن بقي كما هو، ستفشل
 * كل تنبيهات SweetAlert2 (تظهر بلا نص) في أي تركيبة لا تحمل هذا الاسم بالضبط.
 * نفس منطق installerUpdateHtaccess() بالضبط: نستبدله بمسار المجلد الفرعي
 * الصحيح (site_folder) إن وُجد، أو نزيله بالكامل إن كان السكربت مثبَّتاً بجذر
 * النطاق مباشرة.
 */
function installerUpdateFunctionsJs($rootDir, $siteFolder)
{
	$jsPath = $rootDir . 'js/functions.js';
	$content = @file_get_contents($jsPath);
	if ($content === false) {
		return false;
	}

	$prefix = $siteFolder !== '' ? '/' . trim($siteFolder, '/') : '';
	$content = str_replace('"/cms/includes/lang/"', '"' . $prefix . '/includes/lang/"', $content);

	return @file_put_contents($jsPath, $content) !== false;
}

/**
 * .user.ini (بجذر المشروع) — يُستخدَم على استضافات PHP-FPM/CGI حيث php_value
 * بـ.htaccess لا يُطبَّق (خلافاً لـmod_php)، فهو يكرّر نفس القيم بصيغة .ini:
 * open_basedir (قصر وصول PHP على مجلد الموقع + /tmp فقط، أمان أساسي) و
 * include_path — كلاهما يحتاج المسار الحقيقي المطلق لجذر التركيبة على القرص،
 * تماماً مثل سطر include_path بـ.htaccess (installerUpdateHtaccess() أعلاه) —
 * نفس $realPath يُستخدَم هنا لضمان تطابق القيمتين دائماً. auto_prepend_file/
 * auto_append_file لا يحتاجان تعديلاً (قيمتاهما عامة أصلاً). الملف قد لا يكون
 * موجوداً على كل الاستضافات (مثل بيئات mod_php المحلية) — لا نعتبر غيابه خطأ.
 */
function installerUpdateUserIni($rootDir)
{
	$iniPath = $rootDir . '.user.ini';
	$content = @file_get_contents($iniPath);
	if ($content === false) {
		return false;
	}

	$real = realpath($rootDir);
	$realPath = rtrim(str_replace('\\', '/', $real !== false ? $real : $rootDir), '/') . '/';

	$content = preg_replace('/^open_basedir=.*$/m', 'open_basedir=' . $realPath . ':/tmp/', $content, 1);
	$content = preg_replace('/^include_path=".*"$/m', 'include_path="' . $realPath . '"', $content, 1);

	return @file_put_contents($iniPath, $content) !== false;
}

/**
 * نسخة محلية من businessTypeOptions() (includes/functions.php) — مكرَّرة عمداً
 * هنا بدل تحميل functions.php كاملاً بهذه المرحلة المبكرة، لأن functions.php
 * يستدعي gsite() فوراً عند تحميله وهي تفشل بخطأ قاتل قبل وجود جدول settings.
 * يجب إبقاء هذه القائمة مطابقة تماماً لنظيرتها بـ includes/functions.php عند
 * أي تعديل مستقبلي على أنواع الأنشطة المتاحة.
 */
function installerBusinessTypeOptions()
{
	return [
		'organization'             => 'مؤسسة / شركة عامة',
		'local_business'           => 'نشاط تجاري له فرع أو موقع فعلي (محل، صالون، ورشة...)',
		'professional_service'     => 'شركة استشارات / خدمات مهنية (محاماة، محاسبة، تسويق...)',
		'restaurant'               => 'مطعم / مقهى',
		'store'                    => 'متجر / تجارة تجزئة',
		'medical_business'         => 'عيادة / مركز طبي',
		'educational_organization' => 'مؤسسة تعليمية / أكاديمية / مركز تدريب',
		'news_site'                => 'موقع إخباري',
		'sports_site'              => 'موقع / نادي رياضي',
		'blog_site'                => 'موقع مقالات / مدونة',
		'person'                   => 'معرض أعمال شخصي / فريلانسر',
		'other'                    => 'أخرى',
	];
}

// ===== الخطوة 2: استقبال بيانات قاعدة البيانات، الاختبار، ثم كتابة config.php =====
if ($step === 2 && isset($_POST['db_submit'])) {
	$dbFormValues['host'] = trim($_POST['db_host'] ?? 'localhost');
	$dbFormValues['user'] = trim($_POST['db_user'] ?? 'root');
	$dbFormValues['pass'] = (string) ($_POST['db_pass'] ?? '');
	$dbFormValues['name'] = trim($_POST['db_name'] ?? '');

	if ($dbFormValues['name'] === '') {
		$errors[] = 'اسم قاعدة البيانات مطلوب';
	} else {
		try {
			$testCon = new PDO(
				"mysql:host=" . $dbFormValues['host'] . ";charset=utf8mb4",
				$dbFormValues['user'],
				$dbFormValues['pass'],
				[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
			);
			// أنشئ قاعدة البيانات إن لم تكن موجودة أصلاً (بعض الاستضافات تجهّزها
			// مسبقاً ولا تسمح بإنشائها من المستخدم؛ نتجاهل فشل هذه الخطوة بصمت
			// ونحاول الاتصال المباشر بها مباشرة بعدها).
			try {
				$testCon->exec("CREATE DATABASE IF NOT EXISTS `" . str_replace('`', '', $dbFormValues['name']) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
			} catch (\Throwable $e) {
				// تجاهل — قد لا تملك بيانات الاتصال صلاحية CREATE DATABASE
			}
			$testCon->exec("USE `" . str_replace('`', '', $dbFormValues['name']) . "`");

			$configContents = "<?php\n\n"
				. "/**\n * SHOW OR HIDE ERRORS\n */\n"
				. "ini_set('display_errors', 1);\n"
				. "ini_set('display_startup_errors', 1);\n"
				. "ini_set('log_errors', 1);\n"
				. "error_reporting(E_ALL);\n\n"
				. "/**\n * DB CONFIG\n */\n"
				. "define(\"HOST\", " . var_export($dbFormValues['host'], true) . ");\n"
				. "define(\"USER\", " . var_export($dbFormValues['user'], true) . ");\n"
				. "define(\"PASSWORD\", " . var_export($dbFormValues['pass'], true) . ");\n"
				. "define(\"DATABASE\", " . var_export($dbFormValues['name'], true) . ");\n"
				. "define(\"CHARSET\", \"utf8mb4\");\n\n"
				. "\$options = [\n"
				. "    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,\n"
				. "    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n"
				. "    PDO::ATTR_EMULATE_PREPARES   => false,\n"
				. "    PDO::MYSQL_ATTR_FOUND_ROWS => true,\n"
				. "];\n"
				. "\$con = new PDO(\"mysql:host=\" . HOST . \";dbname=\" . DATABASE . \";charset=\" . CHARSET, USER, PASSWORD, \$options);\n";

			if (!is_dir($rootDir . 'includes')) {
				@mkdir($rootDir . 'includes', 0755, true);
			}
			if (@file_put_contents($configPath, $configContents) === false) {
				$errors[] = 'تعذّر الكتابة إلى includes/config.php — تأكد من صلاحيات الكتابة على المجلد';
			} else {
				header('Location: ?step=3');
				exit;
			}
		} catch (\Throwable $e) {
			$errors[] = 'فشل الاتصال بقاعدة البيانات: ' . $e->getMessage();
		}
	}
}

// ===== الخطوة 3: معلومات الموقع (رابط الموقع، اللغة، الاسم/الوصف/الكلمات
// الدلالية حسب اللغة المختارة، نوع النشاط) — تُخزَّن بالجلسة لحين تنفيذ
// الاستيراد الفعلي بالخطوة التالية =====
$siteInfoDefaults = [
	'site_url' => installerDetectSiteUrl(),
	'language_mode' => 'both',
	'name' => '',
	'name_en' => '',
	'description' => '',
	'description_en' => '',
	'site_metaTags' => '',
	'site_metaTags_en' => '',
	'business_type' => 'organization',
];
$siteInfoValues = $_SESSION['install_site_info'] ?? $siteInfoDefaults;

if ($step === 3 && isset($_POST['site_submit'])) {
	if (!is_file($configPath)) {
		header('Location: ?step=2');
		exit;
	}

	$siteInfoValues['site_url'] = trim((string) ($_POST['site_url'] ?? ''));
	$siteInfoValues['language_mode'] = in_array($_POST['language_mode'] ?? '', ['both', 'ar', 'en'], true) ? $_POST['language_mode'] : 'both';
	$siteInfoValues['name'] = trim((string) ($_POST['name'] ?? ''));
	$siteInfoValues['name_en'] = trim((string) ($_POST['name_en'] ?? ''));
	$siteInfoValues['description'] = trim((string) ($_POST['description'] ?? ''));
	$siteInfoValues['description_en'] = trim((string) ($_POST['description_en'] ?? ''));
	$siteInfoValues['site_metaTags'] = trim((string) ($_POST['site_metaTags'] ?? ''));
	$siteInfoValues['site_metaTags_en'] = trim((string) ($_POST['site_metaTags_en'] ?? ''));
	$businessTypeOptions = installerBusinessTypeOptions();
	$siteInfoValues['business_type'] = isset($businessTypeOptions[$_POST['business_type'] ?? '']) ? $_POST['business_type'] : 'organization';

	$needsAr = in_array($siteInfoValues['language_mode'], ['both', 'ar'], true);
	$needsEn = in_array($siteInfoValues['language_mode'], ['both', 'en'], true);

	if ($siteInfoValues['site_url'] === '' || !preg_match('#^https?://#i', $siteInfoValues['site_url'])) {
		$errors[] = 'رابط الموقع مطلوب، ويجب أن يبدأ بـ http:// أو https://';
	}
	if ($needsAr && ($siteInfoValues['name'] === '' || $siteInfoValues['description'] === '' || $siteInfoValues['site_metaTags'] === '')) {
		$errors[] = 'اسم الموقع ووصفه وكلماته الدلالية بالعربية مطلوبة (اخترت لغة عربية)';
	}
	if ($needsEn && ($siteInfoValues['name_en'] === '' || $siteInfoValues['description_en'] === '' || $siteInfoValues['site_metaTags_en'] === '')) {
		$errors[] = 'اسم الموقع ووصفه وكلماته الدلالية بالإنجليزية مطلوبة (اخترت لغة إنجليزية)';
	}

	if (empty($errors)) {
		// تأكد من انتهاء الرابط بشرطة مائلة، ثم اشتق اسم المجلد الفرعي (site_folder)
		// من مسار نفس الرابط الذي أدخله المستخدم — وليس من اكتشاف تلقائي للسيرفر،
		// حتى يعمل بشكل صحيح أيضاً خلف Reverse Proxy أو نطاق مختلف عن نطاق التثبيت.
		$siteUrl = rtrim($siteInfoValues['site_url'], '/') . '/';
		$siteInfoValues['site_url'] = $siteUrl;
		$siteFolder = trim(parse_url($siteUrl, PHP_URL_PATH) ?? '', '/');

		// تحديث .htaccess بالمسار الحقيقي على القرص + بادئة المجلد الفرعي الصحيحة
		// لأسطر ErrorDocument، تحديث مسار ملف ترجمة JS بنفس البادئة، وتحديث
		// .user.ini (open_basedir/include_path لاستضافات PHP-FPM) بنفس المسار
		// الحقيقي المستخدَم بـ.htaccess — لا يوقف التثبيت إن فشل أيّ منها
		// (صلاحيات كتابة مثلاً، أو عدم وجود .user.ini أصلاً على بعض البيئات).
		installerUpdateHtaccess($rootDir, $siteFolder);
		installerUpdateFunctionsJs($rootDir, $siteFolder);
		installerUpdateUserIni($rootDir);

		$_SESSION['install_site_info'] = $siteInfoValues;
		$_SESSION['install_site_folder'] = $siteFolder;

		header('Location: ?step=4');
		exit;
	}
}

// ===== الخطوة 4: استيراد بنية قاعدة البيانات (تلقائي عند تحميل الصفحة) =====
$schemaResults = null;
if ($step === 4) {
	if (!is_file($configPath)) {
		header('Location: ?step=2');
		exit;
	}
	if (!isset($_SESSION['install_site_info'])) {
		header('Location: ?step=3');
		exit;
	}
	require_once $configPath;
	try {
		$installCon = new PDO("mysql:host=" . HOST . ";dbname=" . DATABASE . ";charset=" . CHARSET, USER, PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
		$alreadyInstalled = $installCon->query("SHOW TABLES LIKE 'admins'")->rowCount() > 0;
		if (!$alreadyInstalled) {
			$sqlDump = @file_get_contents($installDir . 'db.sql');
			if ($sqlDump === false) {
				$errors[] = 'تعذّر قراءة ملف db.sql داخل مجلد install/';
			} else {
				$statements = installerExtractSchemaStatements($sqlDump);
				$schemaResults = ['ok' => 0, 'fail' => 0, 'errors' => []];
				foreach ($statements as $stmt) {
					try {
						$installCon->exec($stmt);
						$schemaResults['ok']++;
					} catch (\Throwable $e) {
						$schemaResults['fail']++;
						$schemaResults['errors'][] = $e->getMessage();
					}
				}

				// زرع صف إعدادات مبني على ما أدخله صاحب الموقع فعلياً بالخطوة السابقة
				// (رابط الموقع، اللغة، الاسم/الوصف/الكلمات الدلالية، نوع النشاط) — بدل
				// قيم افتراضية ثابتة. القيم المتبقية غير المُدخَلة تعتمد على fallback
				// الافتراضي بدالة gsite() (functions.php).
				$submitted = $_SESSION['install_site_info'];
				$siteFolder = $_SESSION['install_site_folder'] ?? trim(parse_url($submitted['site_url'], PHP_URL_PATH) ?? '', '/');
				$defaultSettings = [
					'name' => $submitted['name'] !== '' ? $submitted['name'] : 'موقعي الجديد',
					'name_en' => $submitted['name_en'] !== '' ? $submitted['name_en'] : 'My New Site',
					'description' => $submitted['description'],
					'description_en' => $submitted['description_en'],
					'site_metaTags' => $submitted['site_metaTags'],
					'site_metaTags_en' => $submitted['site_metaTags_en'],
					'site_url' => $submitted['site_url'],
					'site_folder' => $siteFolder,
					'theme' => 'default',
					'logo' => 'logo.png',
					'whatsapp_number' => '',
					'logo_color' => '',
					'location_en' => '',
					'facebook' => '',
					'instagram' => '',
					'snapchat' => '',
					'discord' => '',
					'twitter' => '',
					'github' => '',
					'linkedin' => '',
					'youtube' => '',
					'site_mail' => '',
					'site_phone' => '',
					'maps' => '',
					'pdf' => '',
					'indexnow' => '5e0b22602ab74773bbb99d19d0dbc4ab',
					// SMTP
					'smtp_host' => '',
					'smtp_user' => '',
					'smtp_pass' => '',
					// Telegram
					'tg_token' => '',
					'tg_id' => '',
					'tg_status' => '',
					// Whatsapp
					'wa_appkey' => '',
					'wa_authkey' => '',
					'language_mode' => $submitted['language_mode'],
					'business_type' => $submitted['business_type'],
				];
				$insertSetting = $installCon->prepare("INSERT INTO settings (name, value) VALUES (?, ?)");
				foreach ($defaultSettings as $k => $v) {
					try {
						$insertSetting->execute([$k, $v]);
					} catch (\Throwable $e) {
						// تجاهل صف فردي فاشل، الباقي يستمر
					}
				}

				// انتهى الغرض من بيانات الجلسة المؤقتة لهذه الخطوة
				unset($_SESSION['install_site_info'], $_SESSION['install_site_folder']);
			}
		}
	} catch (\Throwable $e) {
		$errors[] = 'تعذّر الاتصال بقاعدة البيانات ببيانات config.php الحالية: ' . $e->getMessage();
	}
}

// ===== الخطوة 5: إنشاء حساب المالك =====
if ($step === 5 && isset($_POST['owner_submit'])) {
	if (!is_file($configPath)) {
		header('Location: ?step=2');
		exit;
	}
	require_once $rootDir . 'includes/config.php';
	// تحقق من اكتمال استيراد البنية أولاً (جدول settings ضروري لأن functions.php
	// يستدعي gsite() فوراً عند تحميله، وهي تفشل بخطأ قاتل إن لم يوجد الجدول بعد)
	try {
		$preCheckCon = new PDO("mysql:host=" . HOST . ";dbname=" . DATABASE . ";charset=" . CHARSET, USER, PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
		if ($preCheckCon->query("SHOW TABLES LIKE 'settings'")->rowCount() === 0) {
			header('Location: ?step=4');
			exit;
		}
	} catch (\Throwable $e) {
		header('Location: ?step=2');
		exit;
	}
	require_once $rootDir . 'includes/functions.php';

	$username = trim($_POST['owner_username'] ?? '');
	$email = trim($_POST['owner_email'] ?? '');
	$password = (string) ($_POST['owner_password'] ?? '');
	$confirm = (string) ($_POST['owner_confirm'] ?? '');

	if ($username === '' || $email === '') {
		$errors[] = 'اسم المستخدم والبريد الإلكتروني مطلوبان';
	} elseif (check($email, "email")) {
		$errors[] = 'صيغة البريد الإلكتروني غير صحيحة';
	} elseif (!isPasswordStrong($password)) {
		$errors[] = 'كلمة المرور يجب ألا تقل عن 8 محارف وتحتوي على حرف ورقم على الأقل';
	} elseif ($password !== $confirm) {
		$errors[] = 'كلمتا المرور غير متطابقتين';
	} else {
		global $con;
		$existing = (int) $con->query("SELECT COUNT(*) FROM admins")->fetchColumn();
		if ($existing > 0) {
			// حساب مالك تم إنشاؤه بالفعل (على الأرجح صفحة أُعيد تحميلها) — تابع مباشرة
			header('Location: ?step=6');
			exit;
		}
		$hashed = password_hash(hash('sha512', $password), PASSWORD_BCRYPT);
		dbInsert("admins", "username, email, password, role, status, date", [safer($username), safer($email), $hashed, 'owner', 'active', date('Y-m-d H:i:s')]);
		header('Location: ?step=6');
		exit;
	}
}

// ===== فحوصات المتطلبات (الخطوة 1) =====
$requirements = [
	['label' => 'إصدار PHP 7.4 أو أحدث', 'ok' => version_compare(PHP_VERSION, '7.4.0', '>=')],
	['label' => 'امتداد PDO MySQL', 'ok' => extension_loaded('pdo_mysql')],
	['label' => 'امتداد mbstring', 'ok' => extension_loaded('mbstring')],
	['label' => 'امتداد cURL', 'ok' => extension_loaded('curl')],
	['label' => 'امتداد ZipArchive', 'ok' => class_exists('ZipArchive')],
	['label' => 'امتداد GD (معالجة الصور)', 'ok' => extension_loaded('gd')],
	['label' => 'إمكانية الكتابة على includes/', 'ok' => is_writable($rootDir . 'includes') || !is_dir($rootDir . 'includes')],
	['label' => 'إمكانية الكتابة على files/', 'ok' => is_dir($rootDir . 'files') ? is_writable($rootDir . 'files') : true],
];
$hardFail = false;
foreach ($requirements as $r) {
	if (!$r['ok']) {
		$hardFail = true;
	}
}

function installerLayout($step, $title, $bodyHtml, $errors = [], $extraHead = '')
{
	$steps = ['1' => 'المتطلبات', '2' => 'قاعدة البيانات', '3' => 'معلومات الموقع', '4' => 'استيراد البنية', '5' => 'حساب المالك'];
	ob_start();
?>
	<!doctype html>
	<html lang="ar" dir="rtl">

	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>تثبيت أُعجوبة — <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
		<style>
			body {
				font-family: -apple-system, Segoe UI, Tahoma, Arial, sans-serif;
				background: #f4f3fb;
				margin: 0;
				color: #1b1730
			}

			.wrap {
				max-width: 640px;
				margin: 40px auto;
				padding: 0 20px
			}

			.card {
				background: #fff;
				border-radius: 14px;
				box-shadow: 0 8px 30px rgba(30, 20, 80, .08);
				padding: 32px
			}

			h1 {
				font-size: 22px;
				margin: 0 0 4px
			}

			.sub {
				color: #847f9c;
				margin: 0 0 24px;
				font-size: 14px
			}

			.steps {
				display: flex;
				gap: 8px;
				margin-bottom: 28px;
				flex-wrap: wrap
			}

			.steps span {
				flex: 1;
				text-align: center;
				padding: 8px 4px;
				border-radius: 8px;
				font-size: 12px;
				background: #f0eefb;
				color: #847f9c
			}

			.steps span.active {
				background: #5b3df6;
				color: #fff;
				font-weight: bold
			}

			label {
				display: block;
				margin: 14px 0 6px;
				font-size: 14px;
				font-weight: 600
			}

			input[type=text],
			input[type=email],
			input[type=password],
			select,
			textarea {
				width: 100%;
				padding: 10px 12px;
				border: 1px solid #e2e0f0;
				border-radius: 8px;
				font-size: 14px;
				box-sizing: border-box;
				font-family: inherit
			}

			textarea {
				resize: vertical;
				min-height: 70px
			}

			button {
				margin-top: 22px;
				background: #5b3df6;
				color: #fff;
				border: 0;
				padding: 12px 22px;
				border-radius: 8px;
				font-size: 15px;
				cursor: pointer
			}

			button:hover {
				background: #4a2fe0
			}

			a.btn {
				display: inline-block;
				margin-top: 22px;
				background: #5b3df6;
				color: #fff;
				padding: 12px 22px;
				border-radius: 8px;
				text-decoration: none;
				font-size: 15px
			}

			.req {
				display: flex;
				justify-content: space-between;
				padding: 10px 0;
				border-bottom: 1px solid #f0eefb;
				font-size: 14px
			}

			.ok {
				color: #1fa971;
				font-weight: bold
			}

			.bad {
				color: #e5484d;
				font-weight: bold
			}

			.err {
				background: #fdecec;
				color: #c92a2a;
				padding: 10px 14px;
				border-radius: 8px;
				margin-bottom: 14px;
				font-size: 14px
			}

			code {
				background: #f0eefb;
				padding: 2px 6px;
				border-radius: 4px
			}

			.hint {
				color: #847f9c;
				font-size: 12px;
				margin-top: 4px
			}

			fieldset {
				border: 1px solid #f0eefb;
				border-radius: 10px;
				padding: 12px 14px 4px;
				margin-top: 18px
			}

			legend {
				padding: 0 6px;
				font-size: 13px;
				font-weight: 600;
				color: #5b3df6
			}

			.select2-container {
				margin-top: 2px
			}
		</style>
		<?php echo $extraHead; ?>
	</head>

	<body>
		<div class="wrap">
			<div class="card">
				<h1>تثبيت أُعجوبة</h1>
				<p class="sub"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></p>
				<div class="steps">
					<?php foreach ($steps as $n => $label): ?>
						<span class="<?php echo ((int) $n === (int) $step) ? 'active' : ''; ?>"><?php echo $n; ?>. <?php echo $label; ?></span>
					<?php endforeach; ?>
				</div>
				<?php foreach ($errors as $e): ?>
					<div class="err"><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></div>
				<?php endforeach; ?>
				<?php echo $bodyHtml; ?>
			</div>
		</div>
	</body>

	</html>
<?php
	return ob_get_clean();
}

// ===== عرض الخطوة الحالية =====
if ($step === 1) {
	ob_start();
?>
	<?php foreach ($requirements as $r): ?>
		<div class="req"><span><?php echo htmlspecialchars($r['label'], ENT_QUOTES, 'UTF-8'); ?></span><span class="<?php echo $r['ok'] ? 'ok' : 'bad'; ?>"><?php echo $r['ok'] ? 'جاهز ✓' : 'غير متوفر ✗'; ?></span></div>
	<?php endforeach; ?>
	<?php if ($hardFail): ?>
		<p style="color:#c92a2a;margin-top:16px">يجب توفير المتطلبات الناقصة أعلاه قبل المتابعة.</p>
	<?php else: ?>
		<a class="btn" href="?step=2">متابعة</a>
	<?php endif; ?>
<?php
	echo installerLayout(1, 'فحص متطلبات السيرفر', ob_get_clean(), $errors);
} elseif ($step === 2) {
	ob_start();
?>
	<form method="post" action="?step=2">
		<label>عنوان سيرفر قاعدة البيانات (Host)</label>
		<input type="text" name="db_host" value="<?php echo htmlspecialchars($dbFormValues['host'], ENT_QUOTES, 'UTF-8'); ?>" required>
		<label>اسم المستخدم</label>
		<input type="text" name="db_user" value="<?php echo htmlspecialchars($dbFormValues['user'], ENT_QUOTES, 'UTF-8'); ?>" required>
		<label>كلمة المرور</label>
		<input type="password" name="db_pass" value="">
		<label>اسم قاعدة البيانات</label>
		<input type="text" name="db_name" value="<?php echo htmlspecialchars($dbFormValues['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
		<button type="submit" name="db_submit">اختبار الاتصال والمتابعة</button>
	</form>
<?php
	echo installerLayout(2, 'بيانات الاتصال بقاعدة البيانات', ob_get_clean(), $errors);
} elseif ($step === 3) {
	$biz = installerBusinessTypeOptions();
	ob_start();
?>
	<form method="post" action="?step=3" id="siteInfoForm">
		<label>رابط الموقع الكامل</label>
		<input type="text" name="site_url" dir="ltr" value="<?php echo htmlspecialchars($siteInfoValues['site_url'], ENT_QUOTES, 'UTF-8'); ?>" required>
		<p class="hint">مثال: https://example.com/ — إن كان السكربت داخل مجلد فرعي أضِفه هنا (مثل https://example.com/site/)، وسيُشتَق اسم المجلد الفرعي تلقائياً من هذا الرابط.</p>

		<label>لغة الموقع</label>
		<select name="language_mode" id="language_mode_select">
			<option value="both" <?php echo $siteInfoValues['language_mode'] === 'both' ? 'selected' : ''; ?>>العربية والإنجليزية معاً</option>
			<option value="ar" <?php echo $siteInfoValues['language_mode'] === 'ar' ? 'selected' : ''; ?>>العربية فقط</option>
			<option value="en" <?php echo $siteInfoValues['language_mode'] === 'en' ? 'selected' : ''; ?>>الإنجليزية فقط</option>
		</select>

		<fieldset class="lang-field lang-ar">
			<legend>بيانات الموقع بالعربية</legend>
			<label>اسم الموقع</label>
			<input type="text" name="name" value="<?php echo htmlspecialchars($siteInfoValues['name'], ENT_QUOTES, 'UTF-8'); ?>">
			<label>وصف الموقع</label>
			<textarea name="description"><?php echo htmlspecialchars($siteInfoValues['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
			<label>الكلمات الدلالية (مفصولة بفاصلة)</label>
			<input type="text" name="site_metaTags" value="<?php echo htmlspecialchars($siteInfoValues['site_metaTags'], ENT_QUOTES, 'UTF-8'); ?>">
		</fieldset>

		<fieldset class="lang-field lang-en">
			<legend>Site info in English</legend>
			<label>Site name</label>
			<input type="text" name="name_en" dir="ltr" value="<?php echo htmlspecialchars($siteInfoValues['name_en'], ENT_QUOTES, 'UTF-8'); ?>">
			<label>Site description</label>
			<textarea name="description_en" dir="ltr"><?php echo htmlspecialchars($siteInfoValues['description_en'], ENT_QUOTES, 'UTF-8'); ?></textarea>
			<label>Meta keywords (comma separated)</label>
			<input type="text" name="site_metaTags_en" dir="ltr" value="<?php echo htmlspecialchars($siteInfoValues['site_metaTags_en'], ENT_QUOTES, 'UTF-8'); ?>">
		</fieldset>

		<label>نوع الموقع / النشاط</label>
		<select name="business_type" id="business_type_select">
			<?php foreach ($biz as $key => $label): ?>
				<option value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $siteInfoValues['business_type'] === $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
			<?php endforeach; ?>
		</select>
		<p class="hint">يُستخدم لتوليد بيانات Schema.org المناسبة لنوع نشاطك تلقائياً بكل صفحات الموقع.</p>

		<button type="submit" name="site_submit">متابعة لاستيراد بنية قاعدة البيانات</button>
	</form>
	<script>
		function ojubaUpdateLangFields() {
			var mode = document.getElementById('language_mode_select').value;
			var showAr = (mode === 'both' || mode === 'ar');
			var showEn = (mode === 'both' || mode === 'en');
			document.querySelectorAll('.lang-ar').forEach(function(el) {
				el.style.display = showAr ? '' : 'none';
				el.querySelectorAll('input,textarea').forEach(function(i) {
					i.required = showAr;
				});
			});
			document.querySelectorAll('.lang-en').forEach(function(el) {
				el.style.display = showEn ? '' : 'none';
				el.querySelectorAll('input,textarea').forEach(function(i) {
					i.required = showEn;
				});
			});
		}
		document.getElementById('language_mode_select').addEventListener('change', ojubaUpdateLangFields);
		ojubaUpdateLangFields();
		if (window.jQuery) {
			jQuery('#business_type_select').select2({
				width: '100%',
				dir: 'rtl'
			});
		}
	</script>
	<?php
	$extraHead = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">'
		. '<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>'
		. '<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>';
	echo installerLayout(3, 'معلومات الموقع', ob_get_clean(), $errors, $extraHead);
} elseif ($step === 4) {
	ob_start();
	if (!empty($errors)) {
		echo '<p>حدث خطأ أثناء استيراد بنية قاعدة البيانات — راجع الرسالة أعلاه، ثم أعد المحاولة.</p>';
		echo '<a class="btn" href="?step=4">إعادة المحاولة</a>';
	} else {
	?>
		<p>تم إنشاء جداول قاعدة البيانات بنجاح<?php echo $schemaResults ? ' (' . (int) $schemaResults['ok'] . ' جملة SQL نُفِّذت بنجاح)' : ' (كانت مُجهَّزة مسبقاً)'; ?>.</p>
		<a class="btn" href="?step=5">متابعة لإنشاء حساب المالك</a>
	<?php
	}
	echo installerLayout(4, 'استيراد بنية قاعدة البيانات', ob_get_clean(), []);
} elseif ($step === 5) {
	ob_start();
	?>
	<form method="post" action="?step=5">
		<label>اسم المستخدم</label>
		<input type="text" name="owner_username" required>
		<label>البريد الإلكتروني</label>
		<input type="email" name="owner_email" required>
		<label>كلمة المرور (8 محارف على الأقل، حرف ورقم)</label>
		<input type="password" name="owner_password" required>
		<label>تأكيد كلمة المرور</label>
		<input type="password" name="owner_confirm" required>
		<button type="submit" name="owner_submit">إنشاء الحساب وإنهاء التثبيت</button>
	</form>
<?php
	echo installerLayout(5, 'إنشاء حساب المالك (Owner)', ob_get_clean(), $errors);
} elseif ($step === 6) {
	echo installerLayout(6, 'اكتمل التثبيت 🎉', '<p>تم تثبيت السكربت بنجاح.</p><p><b>مهم: احذف مجلد install/ بالكامل من الاستضافة الآن لأسباب أمنية.</b></p><a class="btn" href="../abma/auth/login">الدخول إلى لوحة التحكم</a>', []);
}
