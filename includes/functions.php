<?php

// نظام Hooks/Actions (add_action/do_action/add_filter/apply_filters) — يُحمَّل
// هنا أولاً (قبل أي شيء آخر) ليكون add_action()/add_filter() متاحتين لأي ملف
// إضافة بمجلد includes/addons/ يُحمَّل تلقائياً بنهاية هذا الملف، وقبل أي نقطة
// امتداد فعلية بباقي السكربت تستدعي do_action()/apply_filters(). راجع
// includes/hooks.php للتوثيق الكامل ونقاط الامتداد المتوفرة.
include_once 'includes/hooks.php';

include_once 'includes/libs/HTMLPurifier/HTMLPurifier.auto.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

include_once 'includes/libs/phpmailer/autoload.php';

/**
 * SELECT
 */
function dbSelect($table, $selects, $where = null, $vars = null)
{
	// db connect
	global $con;
	global $rows;
	global $countrows;
	// db query
	$stmt = $con->prepare("SELECT $selects FROM $table $where");
	$stmt->execute($vars);
	// return results
	$rows = $stmt->fetchAll();
	$countrows = $stmt->rowCount();
}

/**
 * INSERT
 */
function dbInsert($table, $columns, $vars)
{
	// db connect
	global $con;
	// db query
	$binds = substr(str_repeat('?,', count($vars)), 0, -1);
	$stmt = $con->prepare("INSERT INTO $table ($columns) VALUES ($binds)");
	$stmt->execute($vars);
	// return last Insert Id
	return $con->lastInsertId();
}

/**
 * UPDATE
 */
function dbUpdate($table, $columns, $vars, $where = null)
{
	// db connect
	global $con;
	// db query
	$stmt = $con->prepare("UPDATE $table SET $columns $where");
	$stmt->execute($vars);
	// return num rows affected
	return $stmt->rowCount();
}

/**
 * DELETE
 */
function dbDelete($table, $where = null, $vars = null)
{
	// db connect
	global $con;
	// db query
	$stmt = $con->prepare("DELETE FROM $table $where");
	$stmt->execute($vars);
	// return results
	return $stmt->rowCount();
}

// تحديد اللغة
if (isset($_GET['lang']) and ($_GET['lang'] == "ar" or $_GET['lang'] == "en")) {
	$language = safer($_GET['lang']);
	setcookie('lang', $language, time() + (86400 * 30), "/");
	$_COOKIE['lang'] = $language;
} elseif (!empty($_COOKIE['lang']) and ($_COOKIE['lang'] == "ar" or $_COOKIE['lang'] == "en")) {
	$language = $_COOKIE['lang'];
} else {
	$language = 'en';
	setcookie('lang', $language, time() + (86400 * 30), "/");
	$_COOKIE['lang'] = $language;
}
include_once 'includes/lang/' . $language . '.php';
$lango_country = $language;

/**
 * تنظيف المتغيرات من اكواد html
 * لمنع xss
 */
function safer($var)
{
	$config = HTMLPurifier_Config::createDefault();
	$purifier = new HTMLPurifier($config);
	$var_clean = $purifier->purify(trim($var));
	return htmlspecialchars($var_clean);
}

/**
 * الحقول التي تمر بدالة safer() تُخزَّن في قاعدة البيانات بعد ترميزها HTML-encoded (htmlspecialchars).
 * قوالب Twig تقوم بترميز HTML تلقائياً عند الطباعة {{ var }}، فينتج عن ذلك ترميز مزدوج
 * يظهر للزائر ككود خام مثل &#039;s بدلاً من علامة الاقتباس الفعلية.
 * هذه الدالة تعيد فك الترميز مرة واحدة فقط، بحيث يقوم Twig بترميزها بشكل صحيح ومرة واحدة عند العرض.
 */
function unescapeSafe($var)
{
	if (is_array($var)) {
		return array_map('unescapeSafe', $var);
	}
	return html_entity_decode((string) $var, ENT_QUOTES, 'UTF-8');
}

// Check if Var is only numbers
function numer($var)
{
	if (preg_match("/^[0-9]*\.?[0-9]*$/", $var)) {
		return $var;
	} else {
		return 0;
	}
}

/**
 * جلب اعدادت الموقع
 */
function gsite()
{
	global $rows;
	global $site;
	dbSelect("settings", "name,value");
	foreach ($rows as $site_setting) {
		//
		$site[$site_setting['name']] = $site_setting['value'];
	}
	// قيم افتراضية للإعدادات الجديدة (لغة الموقع / نوع الموقع) التي قد لا تملك بعد
	// صفاً بجدول settings في التركيبات القديمة — تحافظ على السلوك الحالي تماماً كما هو
	// (لغتان مفعّلتان دائماً، ونوع موقع "مؤسسة/شركة عامة" المطابق للقيمة الثابتة
	// "Organization" المُبرمَجة سلفاً في كل صفحات schema.org).
	if (empty($site['language_mode'])) {
		$site['language_mode'] = 'both';
	}
	if (empty($site['business_type'])) {
		$site['business_type'] = 'organization';
	}
	// إعدادات شريط الأخبار العاجلة (Ticker) — خاصة بالقوالب التي تعرضه (مثل قالب
	// الأخبار)، لكنها عامة على مستوى settings كي تتوفر كمتغيّرات Twig لأي قالب
	// يحتاجها دون الحاجة لتعديل PHP الجذر لاحقاً. القيم الافتراضية تطابق تماماً
	// ما كان مبرمجاً سلفاً بثبات (26 ثانية، مفعّل) قبل إضافة هذا الإعداد.
	if (!isset($site['ticker_speed']) || $site['ticker_speed'] === '') {
		$site['ticker_speed'] = 26;
	}
	if (!isset($site['ticker_enabled']) || $site['ticker_enabled'] === '') {
		$site['ticker_enabled'] = 1;
	}
}
gsite();

// إذا كانت لغة الموقع محصورة بلغة واحدة فقط (إعداد "لغة الموقع الرئيسية" بصفحة
// إعدادات الموقع)، نُلغي أي تفضيل مخزَّن بكوكي الزائر أو مُرسَل عبر ?lang= ونثبّت
// اللغة على اللغة المفعّلة الوحيدة دائماً — يجب أن يحدث هذا بعد gsite() مباشرة
// (قبل أي استخدام لاحق لـ $language/$lang) لأن $site['language_mode'] غير متاح
// إلا بعد استدعاء gsite().
if (in_array($site['language_mode'], ['ar', 'en'], true) && $language !== $site['language_mode']) {
	$language = $site['language_mode'];
	setcookie('lang', $language, time() + (86400 * 30), "/");
	$_COOKIE['lang'] = $language;
	include_once 'includes/lang/' . $language . '.php';
	$lango_country = $language;
}

/**
 * حفظ قيمة إعداد بجدول settings (إدراج إن لم يكن الصف موجوداً، وإلا تحديث).
 * dbUpdate() وحدها لا تكفي لإعدادات جديدة لم تُزرع بعد كصف بقاعدة البيانات، لأن
 * "UPDATE ... WHERE name = ?" لا يفعل شيئاً إن لم يوجد الصف أصلاً (0 rows affected)
 * بصمت دون أي خطأ — هذه الدالة تتحقق أولاً ثم تُدرِج أو تُحدِّث حسب الحالة.
 */
function saveSetting($name, $value)
{
	global $countrows;
	dbSelect("settings", "id", "WHERE name = ? LIMIT 1", [$name]);
	if ($countrows >= 1) {
		dbUpdate("settings", "value = ?", [$value, $name], "WHERE name = ?");
	} else {
		dbInsert("settings", "name, value", [$name, $value]);
	}
}

/**
 * نظام "روابط المسارات القابلة للتخصيص" (Custom Routes)
 * ====================================================
 * يسمح لصاحب الموقع بتغيير الكلمة الإنجليزية المستخدمة كمسار لكل نوع محتوى رئيسي
 * (مثلاً "blog" ← "articles") من لوحة التحكم (الإعدادات ▸ روابط المسارات)، دون أي
 * تعديل يدوي بملف .htaccess. القيمة تُخزَّن كصف settings باسم "route_{type}" عبر
 * saveSetting()، وتُقرأ دائماً عبر routeSlug()/routeUrl() أدناه — **ممنوع** تجميع
 * روابط "blog/" ونحوها يدوياً بأي كود PHP أو Twig جديد، لأن ذلك يتجاهل التخصيص.
 *
 * هذا النظام **إلزامي** لكل قالب حالي ومستقبلي (وليس اختيارياً كبقية المزايا التي
 * تُحدَّث "عند الطلب فقط") — راجع CLAUDE.md و abma/developers.php.
 */

/**
 * القائمة الكاملة لأنواع المسارات القابلة للتخصيص، مع الرابط الافتراضي (المطابق
 * تماماً للقيمة المبرمجة سلفاً بالكتلة المُدارة من .htaccess) لكل نوع. أي نوع
 * مسار جديد يُراد جعله قابلاً للتخصيص مستقبلاً يُضاف هنا فقط، ثم إلى الكتلة
 * المُدارة بـ.htaccess (regenerateRouteHtaccess) ونموذج الإعدادات.
 */
function customizableRoutes()
{
	return [
		'blog'      => 'blog',
		'service'   => 'service',
		'portfolio' => 'portfolio',
		'matches'   => 'matches',
		'page'      => 'page',
		'contact'   => 'contact',
		'search'    => 'search',
	];
}

/**
 * الكلمات المحجوزة التي لا يمكن استخدامها كرابط مسار مخصص — لتفادي تعارضها مع
 * مسارات ثابتة أخرى بالسكربت (لوحة التحكم، الملفات، الأقسام/الروابط البديلة
 * الثابتة التي تبقى تعمل دائماً بجانب الرابط الأساسي القابل للتخصيص).
 */
function reservedRouteSlugs()
{
	return [
		'dashboard',
		'index',
		'en',
		'ar',
		'home',
		'abma',
		'api',
		'includes',
		'templates',
		'files',
		'assets',
		'errors',
		'cron',
		'compilation_cache',
		'blogs',
		'services',
		'portfolios',
		'project',
		'projects',
		'match',
		'pages',
		'pricing',
		'preview-post',
		'preview-page',
		'preview-portfolio',
		'preview-service',
		'preview-home',
		'preview-pricing',
		'sitemap',
		'sitemap.xml',
		'feed',
		'rss',
		'blogs.rss',
		'robots',
		'robots.txt',
		'contact-us',
		'contact_us',
	];
}

/**
 * الرابط الحالي (المخصص إن وُجد بجدول settings، وإلا الافتراضي) لنوع مسار معيّن.
 */
function routeSlug($type)
{
	global $site;
	$defaults = customizableRoutes();
	if (!isset($defaults[$type])) {
		return $type; // نوع غير معروف — لا تخصيص، أعد الاسم كما هو (استخدام دفاعي)
	}
	$value = trim((string) ($site['route_' . $type] ?? ''));
	return $value !== '' ? $value : $defaults[$type];
}

/**
 * بناء رابط كامل (site_url + الرابط المخصص/الافتراضي + معرّف اختياري) لنوع مسار
 * معيّن. **يجب** استخدام هذه الدالة (وليس تجميع النص يدوياً "blog/" أو
 * "services/" ...) في أي كود PHP جديد يبني رابطاً لمقال/خدمة/عمل/مباراة/صفحة، وإلا
 * لن يعمل نظام تخصيص الروابط لذلك الرابط تحديداً.
 * مثال: routeUrl('blog', $slug) → "https://site.com/articles/my-post" (إن غيّر
 * صاحب الموقع رابط "blog" إلى "articles").
 */
function routeUrl($type, $identifier = '')
{
	global $site;
	$url = $site['site_url'] . routeSlug($type);
	if ($identifier !== '' && $identifier !== null) {
		$url .= '/' . $identifier;
	}
	return $url;
}

/**
 * التحقق من صلاحية قيمة مُدخَلة كرابط مسار مخصص: أحرف/أرقام/شرطة فقط، غير محجوزة،
 * وغير مكرَّرة مع أي نوع مسار آخر قابل للتخصيص (باستثناء نفس النوع الحالي).
 * تُعيد true عند الصلاحية، أو نص رسالة الخطأ بالعربية عند الرفض.
 */
function validateRouteSlug($type, $value, $allSubmittedSlugs = [])
{
	$value = trim((string) $value);
	if ($value === '') {
		return true; // فارغ = رجوع للافتراضي، مسموح دائماً
	}
	if (!preg_match('/^[a-zA-Z0-9\-]+$/', $value)) {
		return "الرابط \"$value\" غير صالح — يُسمح فقط بأحرف إنجليزية وأرقام والشرطة (-)";
	}
	if (in_array(strtolower($value), reservedRouteSlugs(), true)) {
		return "الرابط \"$value\" محجوز ولا يمكن استخدامه، اختر رابطاً آخر";
	}
	foreach ($allSubmittedSlugs as $otherType => $otherValue) {
		if ($otherType === $type) continue;
		$otherValue = trim((string) $otherValue);
		$otherDefault = customizableRoutes()[$otherType] ?? $otherType;
		$otherEffective = $otherValue !== '' ? $otherValue : $otherDefault;
		if (strtolower($otherEffective) === strtolower($value)) {
			return "الرابط \"$value\" مستخدم بالفعل لنوع مسار آخر — اختر رابطاً مختلفاً لكل نوع";
		}
	}
	return true;
}

/**
 * إعادة توليد الكتلة المُدارة تلقائياً بملف .htaccess الجذر (بين علامتي
 * "# BEGIN CUSTOM ROUTES" و"# END CUSTOM ROUTES") بناءً على الروابط المخصصة
 * الحالية بجدول settings. تُستدعى مباشرة بعد حفظ إعدادات روابط المسارات. لا تكسر
 * شيئاً إن لم يوجد الملف أو لم تُوجد العلامتان (حماية من تركيبة .htaccess غير
 * متوقعة على استضافات مخصَّصة) — تُعيد true عند النجاح، أو نص رسالة الخطأ.
 */
function regenerateRouteHtaccess()
{
	$path = getpath() . '.htaccess';
	if (!file_exists($path)) {
		return 'ملف .htaccess غير موجود بجذر الموقع';
	}
	if (!is_writable($path)) {
		return 'لا توجد صلاحية كتابة على ملف .htaccess — تحقق من صلاحيات الملف بالاستضافة (644 أو 664)';
	}
	$content = file_get_contents($path);
	$begin = '# BEGIN CUSTOM ROUTES';
	$end = '# END CUSTOM ROUTES';
	$beginPos = strpos($content, $begin);
	$endPos = strpos($content, $end);
	if ($beginPos === false || $endPos === false || $endPos < $beginPos) {
		return 'لم يتم العثور على الكتلة المُدارة بملف .htaccess — لا يمكن تحديث الروابط تلقائياً (راجع الدعم الفني)';
	}

	$s = routeSlug('search');
	$c = routeSlug('contact');
	$b = routeSlug('blog');
	$sv = routeSlug('service');
	$p = routeSlug('portfolio');
	$m = routeSlug('matches');
	$pg = routeSlug('page');

	$block = $begin . "\n";
	$block .= "# تُدار هذه الكتلة تلقائياً عبر regenerateRouteHtaccess() (includes/functions.php)\n";
	$block .= "# كل مرة يحفظ صاحب الموقع روابط المسارات المخصصة من لوحة التحكم (الإعدادات ▸ روابط\n";
	$block .= "# المسارات). لا تُعدّل هذا القسم يدوياً — أي تعديل يدوي سيُستبدَل بالكامل عند أول حفظ.\n\n";

	$block .= "# Search\n";
	$block .= "RewriteRule ^{$s}/?\$ search.php?lang= [QSA,L]\n";
	$block .= "RewriteRule ^{$s}/(.*)?\$ search.php?search=\$1&lang= [QSA,L]\n\n";

	$block .= "# Contact\n";
	$block .= "RewriteRule ^{$c}/?\$ contact.php?lang= [QSA,L]\n\n";

	$block .= "# Blogs\n";
	$block .= "RewriteRule ^{$b}/?\$ blogs/all.php?lang= [QSA,L]\n";
	$block .= "RewriteRule ^{$b}/([a-zA-Z0-9-]+)/?\$ blogs/view.php?slug=\$1&lang= [QSA,L]\n\n";

	$block .= "# Services\n";
	$block .= "RewriteRule ^{$sv}/?\$ services/all.php?lang= [QSA,L]\n";
	$block .= "RewriteRule ^{$sv}/([a-zA-Z0-9-]+)/?\$ services/view.php?slug=\$1&lang= [QSA,L]\n\n";

	$block .= "# Portfolio\n";
	$block .= "RewriteRule ^{$p}/?\$ portfolio/all.php?lang= [QSA,L]\n";
	$block .= "RewriteRule ^{$p}/([0-9]+)/?\$ portfolio/view.php?id=\$1&lang= [QSA,L]\n";
	$block .= "RewriteRule ^{$p}/([a-zA-Z0-9-]+)/?\$ portfolio/view.php?slug=\$1&lang= [QSA,L]\n\n";

	$block .= "# Matches\n";
	$block .= "RewriteRule ^{$m}/?\$ matches/all.php?lang= [QSA,L]\n";
	$block .= "RewriteRule ^{$m}/([0-9]+)/?\$ matches/view.php?id=\$1&lang= [QSA,L]\n\n";

	$block .= "# Pages\n";
	$block .= "RewriteRule ^{$pg}/([a-zA-Z0-9-]+)/?\$ pages.php?slug=\$1&lang= [QSA,L]\n\n";

	$block .= $end;

	$newContent = substr($content, 0, $beginPos) . $block . substr($content, $endPos + strlen($end));
	$result = @file_put_contents($path, $newContent);
	if ($result === false) {
		return 'فشلت الكتابة على ملف .htaccess';
	}
	return true;
}

/**
 * قائمة "أنواع الأعمال" المتاحة لإعداد "نوع الموقع" بصفحة إعدادات الموقع، مع قيمة
 * Schema.org @type المقابلة لكل نوع. تُستخدم لتوليد القائمة المنسدلة وبواسطة
 * businessSchemaType() لتحديد نوع الكيان الصحيح عند بناء بيانات schema.org
 * (JSON-LD) في home.php / pages.php / blogs/view.php / services/view.php /
 * portfolio/view.php بدل القيمة الثابتة "Organization" المستخدمة سابقاً في الجميع.
 */
function businessTypeOptions()
{
	return [
		'organization'              => ['label' => 'مؤسسة / شركة عامة',                              'schema' => 'Organization'],
		'local_business'            => ['label' => 'نشاط تجاري له فرع أو موقع فعلي (محل، صالون، ورشة...)', 'schema' => 'LocalBusiness'],
		'professional_service'      => ['label' => 'شركة استشارات / خدمات مهنية (محاماة، محاسبة، تسويق...)', 'schema' => 'ProfessionalService'],
		'restaurant'                => ['label' => 'مطعم / مقهى',                                     'schema' => 'Restaurant'],
		'store'                     => ['label' => 'متجر / تجارة تجزئة',                               'schema' => 'Store'],
		'medical_business'          => ['label' => 'عيادة / مركز طبي',                                 'schema' => 'MedicalBusiness'],
		'educational_organization'  => ['label' => 'مؤسسة تعليمية / أكاديمية / مركز تدريب',              'schema' => 'EducationalOrganization'],
		'news_site'                 => ['label' => 'موقع إخباري',                                    'schema' => 'NewsMediaOrganization'],
		'sports_site'                => ['label' => 'موقع / نادي رياضي',                               'schema' => 'SportsOrganization'],
		'blog_site'                  => ['label' => 'موقع مقالات / مدونة',                              'schema' => 'Organization'],
		'person'                    => ['label' => 'معرض أعمال شخصي / فريلانسر',                       'schema' => 'Person'],
		'other'                     => ['label' => 'أخرى',                                            'schema' => 'Organization'],
	];
}

/**
 * قيمة Schema.org @type المناسبة لنوع الموقع الحالي (إعداد business_type)،
 * تُستخدم بدل القيمة الثابتة "Organization" في كل صفحات schema.org.
 */
function businessSchemaType()
{
	global $site;
	$options = businessTypeOptions();
	$key = $site['business_type'] ?? 'organization';
	return $options[$key]['schema'] ?? 'Organization';
}

/**
 * تسجيل حدث في سجل النشاط (logs)
 * $action: معرّف مختصر للحدث بالإنجليزية (مثال: user_create, theme_save, backup_download)
 * $description: وصف تفصيلي مختصر للحدث
 */
function logAction($action, $description = '')
{
	$admin = currentAdmin();
	$userId = $admin ? (int) $admin['id'] : (isset($_SESSION['user_id']) ? numer($_SESSION['user_id']) : null);
	$sys = safer(getOS()) . " - " . safer(getBrowser());
	dbInsert(
		"logs",
		"user_id, date, ip, sys, action, description",
		[$userId ?: null, date("Y-m-d H:i:s"), getIP(), $sys, $action, $description]
	);
}

/**
 * إرجاع مفاتيح أقسام الصفحة الرئيسية المفعّلة، مرتبة حسب ترتيبها في لوحة التحكم
 * تُستخدم من القوالب التي تدعم النظام الديناميكي لترتيب الأقسام (مثل istishari)
 */
function getHomeSectionsOrder()
{
	static $order = null;
	if ($order !== null) return $order;

	$defaults = ['stats', 'services', 'pricing', 'portfolio', 'team', 'certificates', 'testimonials', 'clients', 'branches', 'faq', 'blog'];

	global $rows;
	global $countrows;
	dbSelect("home_sections", "section_key", "WHERE enabled = 1 ORDER BY ordering ASC, id ASC");
	if ($countrows >= 1) {
		$order = array_column($rows, 'section_key');
	} else {
		$order = $defaults;
	}
	return $order;
}

/**
 * حساب بيانات ترقيم الصفحات (Pagination)
 * $total: إجمالي عدد النتائج
 * $perPage: عدد النتائج في كل صفحة
 * يقرأ رقم الصفحة الحالية من $_GET['page']
 */
function paginate($total, $perPage = 9)
{
	$total = (int) $total;
	$perPage = max(1, (int) $perPage);
	$totalPages = max(1, (int) ceil($total / $perPage));

	$page = isset($_GET['page']) ? (int) numer($_GET['page']) : 1;
	if ($page < 1) $page = 1;
	if ($page > $totalPages) $page = $totalPages;

	$offset = ($page - 1) * $perPage;

	// نافذة أرقام الصفحات المعروضة (بحد أقصى 5 أرقام حول الصفحة الحالية)
	$window = 2;
	$start = max(1, $page - $window);
	$end = min($totalPages, $page + $window);
	$pages = range($start, $end);

	return [
		'page'        => $page,
		'per_page'    => $perPage,
		'total'       => $total,
		'total_pages' => $totalPages,
		'offset'      => $offset,
		'has_prev'    => $page > 1,
		'has_next'    => $page < $totalPages,
		'prev_page'   => max(1, $page - 1),
		'next_page'   => min($totalPages, $page + 1),
		'pages'       => $pages,
		'show_first'  => $start > 1,
		'show_last'   => $end < $totalPages,
	];
}

/**
 * get absoule path
 */
function getpath()
{
	global $site;
	if ($site["site_folder"]) {
		return $_SERVER["DOCUMENT_ROOT"] . "/" . $site["site_folder"] . "/";
	} else {
		return $_SERVER["DOCUMENT_ROOT"] . "/";
	}
}

/**
 * ================================
 * نظام التحقق من التحديثات (Auto-Update)
 * ================================
 * رقم الإصدار المثبَّت حالياً على هذا الموقع — يُقرأ من ملف VERSION بجذر
 * المشروع (نص عادي مثل "1.2.0")، وليس من قاعدة البيانات، لأن التحديث نفسه
 * يستبدل هذا الملف كجزء من نسخ ملفات الإصدار الجديد (راجع includes/updater.php)
 * فيتحدّث رقم الإصدار تلقائياً بمجرد اكتمال النسخ دون أي خطوة إضافية.
 * تُخزَّن النتيجة بذاكرة الطلب (static) لتفادي قراءة الملف أكثر من مرة —
 * باستثناء حالة $forceRefresh = true (يُستخدمها includes/updater.php فقط،
 * مباشرة بعد نسخ ملفات الإصدار الجديد فوق ملف VERSION الحالي، حتى لا يُعاد
 * نفس رقم الإصدار القديم المخزَّن مسبقاً بالذاكرة الثابتة لهذا الطلب).
 */
function installedVersion($forceRefresh = false)
{
	static $version = null;
	if ($version !== null && !$forceRefresh) {
		return $version;
	}
	$file = getpath() . 'VERSION';
	if (is_file($file)) {
		$contents = trim((string) @file_get_contents($file));
		$version = $contents !== '' ? $contents : '0.0.0';
	} else {
		$version = '0.0.0';
	}
	return $version;
}

/**
 * قراءة ملف theme.json الخاص بالقالب النشط (templates/{theme}/theme.json)
 * هذا الملف يحدد أي "وحدات" (Modules) مفعّلة في هذا القالب تحديداً،
 * حتى لا تظهر في لوحة التحكم خيارات لا يدعمها القالب الحالي (مثلاً قالب
 * بدون مقالات لا داعي لظهور "المقالات" في القائمة الجانبية)
 */
function themeManifest()
{
	global $site;
	static $manifest = null;
	if ($manifest !== null) {
		return $manifest;
	}

	// القيم الافتراضية: الوحدات القديمة (الأساسية) مفعّلة افتراضياً حفاظاً على
	// توافق القوالب القديمة التي لا تملك theme.json، والوحدات الجديدة معطّلة افتراضياً
	$defaults = [
		"pages"           => true,
		"blogs"           => true,
		"blog_categories" => true,
		"services"        => true,
		"portfolio"       => true,
		"categories"      => true,
		"contact"         => true,
		"search"          => true,
		"clients"         => true,
		"team"            => false,
		"testimonials"    => false,
		"faq"             => false,
		"stats"           => false,
		"pricing"         => false,
		"branches"        => false,
		"certificates"    => false,
		"mailing"         => false,
		"ads"             => false,
		"matches"         => false,
		"standings"       => false,
		"videos"          => false,
	];

	$manifest = [
		"name"          => $site["theme"] ?? "",
		"activity_type" => "",
		"modules"       => $defaults,
	];

	$file = getpath() . "templates/" . $site["theme"] . "/theme.json";
	if (is_file($file)) {
		$json = json_decode(file_get_contents($file), true);
		if (is_array($json)) {
			if (!empty($json["name"])) {
				$manifest["name"] = $json["name"];
			}
			if (!empty($json["activity_type"])) {
				$manifest["activity_type"] = $json["activity_type"];
			}
			if (!empty($json["modules"]) and is_array($json["modules"])) {
				// دمج ما هو معرّف في theme.json فوق القيم الافتراضية
				$manifest["modules"] = array_merge($defaults, $json["modules"]);
			}
		}
	}

	return $manifest;
}
/**
 * قراءة ملف theme.json 
 * لجلب اسم ووصف القالب (إن وُجِد)
 * وجلب بيانات المؤلف
 */
function themeData($template)
{
	global $site;

	$file = getpath() . "templates/" . $template . "/theme.json";

	if (!is_file($file)) {
		return false;
	}

	$json = json_decode(file_get_contents($file), true);

	if (!is_array($json)) {
		return false;
	}

	if (!isset($json['data']) || !is_array($json['data']) || empty($json['data'])) {
		return false;
	}

	return $json['data'];
}
/**
 * التحقق هل الوحدة (module) مفعّلة في القالب النشط حالياً
 * مثال: moduleEnabled('blogs')
 */
function moduleEnabled($module)
{
	$manifest = themeManifest();
	return !empty($manifest["modules"][$module]);
}

/**
 * يتأكد أن وحدة "مراسلة البريد" مفعّلة في القالب النشط قبل عرض أي صفحة من
 * صفحات الوحدة، وإلا يوقف الصفحة برسالة ويعيد التوجيه لصفحة الإضافات —
 * تحسّباً لدخول رابط الوحدة مباشرة بعد إيقافها من صفحة الإضافات
 */
function mailingRequireModule()
{
	if (!moduleEnabled('mailing')) {
		sweet("error", "الوحدة غير مفعّلة", "وحدة مراسلة البريد غير مفعّلة حالياً لهذا القالب. يمكنك تفعيلها من صفحة الإضافات.", "addons");
		exit;
	}
}

/**
 * يتأكد أن وحدة "الإعلانات" مفعّلة في القالب النشط قبل عرض أي صفحة من صفحات
 * الوحدة بلوحة التحكم، وإلا يوقف الصفحة برسالة ويعيد التوجيه لصفحة الإضافات —
 * نفس اصطلاح mailingRequireModule() أعلاه
 */
function adsRequireModule()
{
	if (!moduleEnabled('ads')) {
		sweet("error", "الوحدة غير مفعّلة", "وحدة الإعلانات غير مفعّلة حالياً لهذا القالب. يمكنك تفعيلها من صفحة الإضافات.", "addons");
		exit;
	}
}

/**
 * تتحقق هل جدول ads موجود فعلاً بقاعدة البيانات (يُنشأ عبر ترحيل
 * abma/ads/migrate.php). تحسّباً لتفعيل الإضافة من صفحة "الإضافات" قبل تشغيل
 * الترحيل — أي استعلام على الجدول (بلوحة التحكم أو بالموقع العام) يجب أن
 * يمر عبر هذه الدالة أولاً حتى لا يكسر الصفحة بخطأ "Table doesn't exist".
 * النتيجة مخزَّنة static لتفادي تكرار SHOW TABLES في نفس الطلب.
 */
function adsTableExists()
{
	static $exists = null;
	if ($exists !== null) {
		return $exists;
	}
	global $con;
	try {
		$chk = $con->query("SHOW TABLES LIKE 'ads'");
		$exists = $chk && $chk->rowCount() > 0;
	} catch (Exception $e) {
		$exists = false;
	}
	return $exists;
}

/**
 * تجلب الإعلانات النشطة لموضع مُعيَّن (position)، جاهزة للتمرير مباشرة لـ Twig.
 * تتحقق تلقائياً من تفعيل الوحدة ووجود الجدول، فيمكن استدعاؤها بأمان من أي
 * صفحة (home.php, blogs/view.php, twigload.php) دون تكرار هذا التحقق في كل
 * مكان. مواضع الإعلانات الثابتة بالقالب النشط (workup):
 * home_top, home_between, blog_sidebar, article_inline, footer
 */
function getAdsByPosition($position)
{
	global $site, $rows, $countrows;
	if (!moduleEnabled('ads') || !adsTableExists()) {
		return [];
	}
	dbSelect(
		"ads",
		"id, name, type, text, link, image, code, position",
		"WHERE position = ? AND status = ? ORDER BY ordering ASC, id ASC",
		[$position, 'active']
	);
	$list = [];
	if ($countrows >= 1) {
		foreach ($rows as $row) {
			$row['text'] = unescapeSafe($row['text']);
			if (!empty($row['image'])) {
				$row['image'] = $site['site_url'] . 'files/ads/' . $row['image'];
			}
			$list[] = $row;
		}
	}
	return $list;
}

/**
 * يتأكد أن وحدة "سحب المقالات" (feeds) مفعّلة في القالب النشط قبل عرض أي صفحة
 * من صفحات الوحدة بلوحة التحكم — نفس اصطلاح adsRequireModule()/mailingRequireModule().
 */
function feedsRequireModule()
{
	if (!moduleEnabled('feeds')) {
		sweet("error", "الوحدة غير مفعّلة", "وحدة سحب المقالات غير مفعّلة حالياً لهذا القالب. يمكنك تفعيلها من صفحة الإضافات.", "addons");
		exit;
	}
}

/**
 * تتحقق هل جدولا feed_sources و feed_imported_items موجودان فعلاً بقاعدة
 * البيانات (يُنشآن عبر ترحيل abma/feeds/migrate.php) — نفس اصطلاح
 * adsTableExists()/matchesTableExists(). النتيجة مخزَّنة static.
 */
function feedsTableExists()
{
	static $exists = null;
	if ($exists !== null) {
		return $exists;
	}
	global $con;
	try {
		$chk = $con->query("SHOW TABLES LIKE 'feed_sources'");
		$exists = $chk && $chk->rowCount() > 0;
	} catch (Exception $e) {
		$exists = false;
	}
	return $exists;
}

/**
 * ==================== وحدات "أُعجوبة" (matches / standings / videos) ====================
 * ثلاث وحدات جديدة عامة على مستوى السكربت (وليست حبيسة قالب OjubaSport) —
 * نفس اصطلاح ads: تحتاج تفعيلاً من الإضافات + تشغيل ترحيل مرة واحدة قبل
 * الاستخدام، وتتحقق دوالها المساعدة ذاتياً من التفعيل ووجود الجدول.
 */

function matchesRequireModule()
{
	if (!moduleEnabled('matches')) {
		sweet("error", "الوحدة غير مفعّلة", "وحدة جدول المباريات غير مفعّلة حالياً لهذا القالب. يمكنك تفعيلها من صفحة الإضافات.", "addons");
		exit;
	}
}

function matchesTableExists()
{
	static $exists = null;
	if ($exists !== null) {
		return $exists;
	}
	global $con;
	try {
		$chk = $con->query("SHOW TABLES LIKE 'sport_matches'");
		$exists = $chk && $chk->rowCount() > 0;
	} catch (Exception $e) {
		$exists = false;
	}
	return $exists;
}

/**
 * تجلب المباريات جاهزة للعرض: المباشرة أولاً، ثم القادمة (الأقرب موعداً
 * أولاً)، ثم المنتهية. $limit يحدد العدد الأقصى.
 */
function getMatchesForDisplay($limit = 6)
{
	global $site, $rows, $countrows;
	if (!moduleEnabled('matches') || !matchesTableExists()) {
		return [];
	}
	dbSelect(
		"sport_matches",
		"id, competition, team_home, team_home_logo, team_away, team_away_logo, match_date, venue, score_home, score_away, match_status, broadcast_channel",
		"WHERE status = ? ORDER BY CASE match_status WHEN 'live' THEN 0 WHEN 'upcoming' THEN 1 ELSE 2 END ASC, match_date ASC LIMIT " . (int) $limit,
		['active']
	);
	$list = [];
	if ($countrows >= 1) {
		foreach ($rows as $row) {
			$row['team_home_logo'] = !empty($row['team_home_logo']) ? $site['site_url'] . 'files/matches/' . $row['team_home_logo'] : '';
			$row['team_away_logo'] = !empty($row['team_away_logo']) ? $site['site_url'] . 'files/matches/' . $row['team_away_logo'] : '';
			$row['date_human'] = ago($row['match_date']);
			$list[] = $row;
		}
	}
	return $list;
}

/**
 * جلب مباراة واحدة بمعرّفها (id) — تُستخدم لعرض "صندوق ملخص المباراة" أعلى صفحة
 * المقال عند ربط مقال بمباراة عبر عمود blogs.related_match_id (راجع
 * blogsRelatedMatchColumnExists() و abma/blogs/migrate.php). نفس شكل العنصر
 * الذي تُعيده getMatchesForDisplay() بالضبط، فآمن استخدامها بنفس مقاطع Twig.
 * تتحقق ذاتياً من تفعيل الوحدة ووجود الجدول — لا تفترض وجودهما بأي قالب.
 */
function getMatchById($id)
{
	global $site, $rows, $countrows;
	$id = (int) $id;
	if ($id <= 0 || !moduleEnabled('matches') || !matchesTableExists()) {
		return null;
	}
	dbSelect(
		"sport_matches",
		"id, competition, team_home, team_home_logo, team_away, team_away_logo, match_date, venue, score_home, score_away, match_status, broadcast_channel",
		"WHERE id = ? AND status = ? LIMIT 1",
		[$id, 'active']
	);
	if ($countrows < 1) {
		return null;
	}
	$row = $rows[0];
	$row['team_home_logo'] = !empty($row['team_home_logo']) ? $site['site_url'] . 'files/matches/' . $row['team_home_logo'] : '';
	$row['team_away_logo'] = !empty($row['team_away_logo']) ? $site['site_url'] . 'files/matches/' . $row['team_away_logo'] : '';
	$row['date_human'] = ago($row['match_date']);
	return $row;
}

function standingsRequireModule()
{
	if (!moduleEnabled('standings')) {
		sweet("error", "الوحدة غير مفعّلة", "وحدة جدول الترتيب غير مفعّلة حالياً لهذا القالب. يمكنك تفعيلها من صفحة الإضافات.", "addons");
		exit;
	}
}

function standingsTableExists()
{
	static $exists = null;
	if ($exists !== null) {
		return $exists;
	}
	global $con;
	try {
		$chk = $con->query("SHOW TABLES LIKE 'sport_standings'");
		$exists = $chk && $chk->rowCount() > 0;
	} catch (Exception $e) {
		$exists = false;
	}
	return $exists;
}

/**
 * تجلب جدول الترتيب مرتَّباً حسب ordering (تحكّم يدوي من لوحة التحكم) ثم
 * النقاط تنازلياً، وتحسب فارق الأهداف لكل فريق.
 */
function getStandings($limit = 20)
{
	global $site, $rows, $countrows;
	if (!moduleEnabled('standings') || !standingsTableExists()) {
		return [];
	}
	dbSelect(
		"sport_standings",
		"id, team_name, team_logo, played, won, drawn, lost, goals_for, goals_against, points, ordering",
		"WHERE status = ? ORDER BY ordering ASC, points DESC, id ASC LIMIT " . (int) $limit,
		['active']
	);
	$list = [];
	if ($countrows >= 1) {
		foreach ($rows as $row) {
			$row['team_logo'] = !empty($row['team_logo']) ? $site['site_url'] . 'files/standings/' . $row['team_logo'] : '';
			$row['goal_diff'] = (int) $row['goals_for'] - (int) $row['goals_against'];
			$list[] = $row;
		}
	}
	return $list;
}

function videosRequireModule()
{
	if (!moduleEnabled('videos')) {
		sweet("error", "الوحدة غير مفعّلة", "وحدة الفيديوهات غير مفعّلة حالياً لهذا القالب. يمكنك تفعيلها من صفحة الإضافات.", "addons");
		exit;
	}
}

function videosTableExists()
{
	static $exists = null;
	if ($exists !== null) {
		return $exists;
	}
	global $con;
	try {
		$chk = $con->query("SHOW TABLES LIKE 'sport_videos'");
		$exists = $chk && $chk->rowCount() > 0;
	} catch (Exception $e) {
		$exists = false;
	}
	return $exists;
}

/**
 * تستخرج معرّف فيديو YouTube من أي صيغة رابط شائعة (watch?v=, youtu.be/,
 * embed/, shorts/) — تُستخدم لبناء رابط التضمين (embed) والصورة المصغّرة
 * التلقائية معاً دون الحاجة لرفع صورة يدوياً.
 */
function youtubeVideoId($url)
{
	$url = trim((string) $url);
	if ($url === '') {
		return '';
	}
	if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{6,})/', $url, $m)) {
		return $m[1];
	}
	return '';
}

/**
 * تجلب الفيديوهات جاهزة للعرض — الصورة المصغّرة تلقائية من YouTube إن لم
 * تُرفع صورة يدوياً، ورابط التضمين (embed_url) جاهز مباشرة لوسم iframe.
 */
function getVideos($limit = 6)
{
	global $site, $rows, $countrows;
	if (!moduleEnabled('videos') || !videosTableExists()) {
		return [];
	}
	dbSelect(
		"sport_videos",
		"id, title, youtube_url, thumbnail, ordering",
		"WHERE status = ? ORDER BY ordering ASC, id DESC LIMIT " . (int) $limit,
		['active']
	);
	$list = [];
	if ($countrows >= 1) {
		foreach ($rows as $row) {
			$row['title'] = unescapeSafe($row['title']);
			$videoId = youtubeVideoId($row['youtube_url']);
			$row['embed_url'] = $videoId ? 'https://www.youtube.com/embed/' . $videoId : '';
			if (!empty($row['thumbnail'])) {
				$row['thumbnail'] = $site['site_url'] . 'files/videos/' . $row['thumbnail'];
			} elseif ($videoId) {
				$row['thumbnail'] = 'https://img.youtube.com/vi/' . $videoId . '/hqdefault.jpg';
			} else {
				$row['thumbnail'] = '';
			}
			$list[] = $row;
		}
	}
	return $list;
}

/**
 * تقدير مدة القراءة بالدقائق من نص مقال (عربي أو إنجليزي). يستخدم preg_split
 * على المسافات بدل str_word_count() لأن الأخيرة لا تتعرّف على الحروف العربية
 * أصلاً (تُعيد صفراً لأي نص عربي بحت). معدّل 200 كلمة/دقيقة تقريبي وعام للغتين.
 */
function readingTimeMinutes($text)
{
	$text = trim(strip_tags((string) $text));
	if ($text === '') {
		return 1;
	}
	$words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
	$count = is_array($words) ? count($words) : 0;
	return max(1, (int) ceil($count / 200));
}

/**
 * يتحقق ما إذا كان عمود `featured` موجوداً بجدول blogs (خاصية "مقال مميّز/عاجل"،
 * أُضيفت عبر ترحيل abma/blogs/migrate.php). النتيجة تُخزَّن بمتغيّر ثابت (static)
 * لتفادي تكرار استعلام SHOW COLUMNS أكثر من مرة بنفس الطلب.
 */
function blogsFeaturedColumnExists()
{
	global $con;
	static $exists = null;
	if ($exists !== null) {
		return $exists;
	}
	try {
		$chk = $con->query("SHOW COLUMNS FROM blogs LIKE 'featured'");
		$exists = $chk && $chk->rowCount() > 0;
	} catch (Exception $e) {
		$exists = false;
	}
	return $exists;
}

/**
 * عمود "المباراة المرتبطة" (related_match_id) بجدول blogs — يُضاف عبر نفس ترحيل
 * abma/blogs/migrate.php أعلاه (raced/ safe to rerun). تحقق منه دائماً قبل قراءة
 * أو كتابة هذا العمود لأن التركيبات القديمة لن تملكه قبل تشغيل الترحيل.
 */
function blogsRelatedMatchColumnExists()
{
	global $con;
	static $exists = null;
	if ($exists !== null) {
		return $exists;
	}
	try {
		$chk = $con->query("SHOW COLUMNS FROM blogs LIKE 'related_match_id'");
		$exists = $chk && $chk->rowCount() > 0;
	} catch (Exception $e) {
		$exists = false;
	}
	return $exists;
}

/**
 * أعمدة "التحقق بخطوتين" (totp_secret/totp_enabled/totp_backup_codes) بجدول
 * admins — أُضيفت عبر ترحيل abma/users/migrate.php. تحقق منها دائماً قبل أي
 * قراءة/كتابة لهذه الأعمدة (لوحة حساب المستخدم، تسجيل الدخول) لأن التركيبات
 * القديمة لن تملكها قبل تشغيل الترحيل — نفس اصطلاح blogsFeaturedColumnExists().
 */
function adminsTotpColumnsExist()
{
	global $con;
	static $exists = null;
	if ($exists !== null) {
		return $exists;
	}
	try {
		$chk = $con->query("SHOW COLUMNS FROM admins LIKE 'totp_secret'");
		$exists = $chk && $chk->rowCount() > 0;
	} catch (Exception $e) {
		$exists = false;
	}
	return $exists;
}

/**
 * نسخة مُسمّاة (وليست closure) من nl2br مع تعطيل تحويل XHTML، لتُستخدم كفلتر/دالة
 * Twig باسم حقيقي بدل closure (انظر الملاحظة في twigload.php لسبب ذلك).
 */
function nl2br_raw($string)
{
	return nl2br($string, false);
}

/**
 * send emails with smtp
 */
function mailer($email, $subject, $body, $option = null, $attachments = [])
{
	global $site;
	//Server settings
	// نفعّل الاستثناءات (true) حتى يمكن التقاط أخطاء الإرسال عبر try/catch بدل فشل صامت
	$mail = new PHPMailer(true);
	$mail->isSMTP();
	// $mail->SMTPDebug = 2;
	$mail->SMTPAuth   = true;
	$mail->Host       = $site["smtp_host"];
	$mail->Username   = $site["smtp_user"];
	$mail->Password   = $site["smtp_pass"];
	$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
	$mail->Port       = 587;
	$mail->Priority   = 1;
	$mail->CharSet = 'UTF-8';
	//Recipients
	$mail->setFrom($site["smtp_user"], $site["name"]);
	$mail->addAddress($email);
	//Content
	$mail->isHTML(true);
	$mail->Subject = $subject;

	// Attachments
	// يدعم كل عنصر إما مساراً نصياً مباشراً (الاسم الظاهر = اسم الملف على القرص)
	// أو مصفوفة ['path' => ..., 'name' => ...] لعرض اسم الملف الأصلي بدل الاسم العشوائي المخزَّن به على القرص
	if (!empty($attachments)) {
		foreach ($attachments as $attachment) {
			if (is_array($attachment)) {
				$path = $attachment['path'] ?? '';
				$displayName = $attachment['name'] ?? '';
				if ($path && file_exists($path)) {
					$mail->addAttachment($path, $displayName);
				}
			} else {
				if (file_exists($attachment)) {
					$mail->addAttachment($attachment);
				}
			}
		}
	}
	// html
	$mail->Body = $body;
	if (!$option) {
		$mail->send();
	} else {
		return $mail;
	}
}

/**
 * إرسال دفعة رسائل (نفس الموضوع/المرفقات، محتوى مختلف لكل مستلم) عبر اتصال
 * SMTP واحد يُعاد استخدامه لكل الدفعة (SMTPKeepAlive) بدل فتح اتصال جديد لكل
 * رسالة، مع تأخير بسيط بين كل رسالة والتالية — لتقليل احتمال حظر/تقييد
 * الحساب من مزود الاستضافة عند إرسال رسائل جماعية (خصوصاً على الاستضافة
 * المشتركة التي غالباً تضع حد رسائل صارم بالساعة).
 *
 * $contacts: [['email' => '...', 'body' => '...'], ...]
 * $delayMicroseconds: التأخير بين كل رسالة والتالية (بالميكروثانية، 1000000 = ثانية واحدة)
 * يعيد: [['email' => '...', 'ok' => bool, 'error' => string|null], ...]
 */
function sendBulkBatch($subject, $contacts, $attachments = [], $delayMicroseconds = 700000)
{
	global $site;
	$results = [];
	$mail = new PHPMailer(true);
	try {
		$mail->isSMTP();
		$mail->SMTPAuth   = true;
		$mail->Host       = $site["smtp_host"];
		$mail->Username   = $site["smtp_user"];
		$mail->Password   = $site["smtp_pass"];
		$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
		$mail->Port       = 587;
		$mail->CharSet    = 'UTF-8';
		$mail->isHTML(true);
		$mail->SMTPKeepAlive = true; // إعادة استخدام نفس اتصال SMTP لكل رسائل هذه الدفعة
		$mail->setFrom($site["smtp_user"], $site["name"]);
		$mail->Subject = $subject;

		foreach ($attachments as $attachment) {
			if (is_array($attachment)) {
				$path = $attachment['path'] ?? '';
				$displayName = $attachment['name'] ?? '';
				if ($path && file_exists($path)) {
					$mail->addAttachment($path, $displayName);
				}
			} elseif (file_exists($attachment)) {
				$mail->addAttachment($attachment);
			}
		}

		$first = true;
		foreach ($contacts as $c) {
			if (!$first and $delayMicroseconds > 0) {
				usleep($delayMicroseconds);
			}
			$first = false;
			try {
				$mail->clearAddresses();
				$mail->addAddress($c['email']);
				$mail->Body = $c['body'];
				$mail->send();
				$results[] = ['email' => $c['email'], 'ok' => true, 'error' => null];
			} catch (Exception $e) {
				$results[] = ['email' => $c['email'], 'ok' => false, 'error' => $e->getMessage()];
			}
		}
	} finally {
		if ($mail->SMTPKeepAlive) {
			$mail->smtpClose();
		}
	}
	return $results;
}

/**
 * يبحث عن إيميل داخل جهات اتصال القوائم البريدية عبر كل القوائم (وليس قائمة
 * واحدة فقط) — يُستخدم لمنع تكرار نفس الإيميل في أكثر من قائمة بريدية.
 * $excludeContactId: تجاهل صف بعينه (يُستخدم عند تعديل جهة اتصال حتى لا يتعارض
 * الإيميل مع سجلّه الخاص).
 * يعيد اسم القائمة التي يوجد بها الإيميل حالياً، أو null إن لم يكن موجوداً بأي قائمة.
 */
function mailingFindEmailListName($email, $excludeContactId = null)
{
	global $rows, $countrows;
	$where = "JOIN email_lists el ON el.id = c.list_id WHERE c.email = ?";
	$vars = [$email];
	if ($excludeContactId) {
		$where .= " AND c.id != ?";
		$vars[] = $excludeContactId;
	}
	dbSelect("email_list_contacts c", "el.name as list_name", $where . " LIMIT 1", $vars);
	if ($countrows > 0) {
		return $rows[0]['list_name'];
	}
	return null;
}

/**
 * دوال عملية الدخول والتحقق
 */
function sec_session_start($workspace)
{
	global $site;
	$session_name = $workspace;   // Set a custom session name 
	$secure = true;
	// This stops JavaScript being able to access the session id.
	$httponly = true;
	// Forces sessions to only use cookies.
	if (ini_set('session.use_only_cookies', 1) === FALSE) {
		echo "<meta http-equiv='Refresh' content='0; url=" . safer($site["site_url"]) . "auth/login'>";
		exit();
	}
	// Gets current cookies params.
	ini_set("session.gc_maxlifetime", 86400);
	ini_set("session.cookie_lifetime", 86400);
	$cookieParams = session_get_cookie_params();
	session_set_cookie_params($cookieParams["lifetime"], $cookieParams["path"], $cookieParams["domain"], $secure, $httponly);
	// Sets the session name to the one set above.
	session_name($session_name);
	session_start();            // Start the PHP session 
	session_regenerate_id();    // regenerated the session, delete the old one. 
}

function login_check_admin()
{
	global $rows;
	global $countrows;
	// Check if all session variables are set 
	if (!empty($_SESSION['user_id'])) {
		$user_id = numer($_SESSION['user_id']);
		$login_string = $_SESSION['login_string'];
		// Get the user-agent string of the user.
		$user_browser = $_SERVER['HTTP_USER_AGENT'];
		$user_browser = 1;
		dbSelect("admins", "password", "WHERE id=? LIMIT 1", [$user_id]);
		$user_pass = $rows[0]["password"];
		if ($countrows == 1) {
			$login_check = hash('sha512', $user_pass . $user_browser);
			if (hash_equals($login_check, $login_string)) {
				// Logged In!!!! 
				return true;
			} else {
				// Not logged in 
				return false;
			}
		} else {
			// Not logged in
			return false;
		}
	} else {
		// Not logged in
		return false;
	}
}

/**
 * إرجاع بيانات الأدمن الحالي المسجل دخوله (id, username, email, role, status)
 */
function currentAdmin()
{
	static $admin = null;
	if ($admin !== null) {
		return $admin;
	}
	if (empty($_SESSION['user_id'])) {
		return null;
	}
	global $rows;
	global $countrows;
	dbSelect("admins", "id, username, email, role, status", "WHERE id = ? LIMIT 1", [numer($_SESSION['user_id'])]);
	if ($countrows === 1) {
		$admin = $rows[0];
		return $admin;
	}
	return null;
}

/**
 * هل الأدمن الحالي يملك صلاحية owner (وصول كامل: تحرير القالب/الإعدادات/إدارة المستخدمين)
 * حسابات role = editor لا تصل لهذه الصفحات الحساسة
 */
function isOwner()
{
	$admin = currentAdmin();
	return $admin && ($admin['role'] ?? '') === 'owner';
}

/**
 * التحقق من قوة كلمة المرور: 8 أحرف على الأقل، وتحتوي على حرف ورقم معاً
 */
function isPasswordStrong($password)
{
	if (strlen($password) < 8) return false;
	if (!preg_match('/[A-Za-z]/', $password)) return false;
	if (!preg_match('/[0-9]/', $password)) return false;
	return true;
}

/**
 * إجبار وصول owner فقط، وإلا إيقاف الصفحة برسالة رفض
 */
function requireOwner()
{
	if (!isOwner()) {
		http_response_code(403);
		die('<div style="padding:80px 20px;text-align:center;font-family:sans-serif;direction:rtl"><h2>غير مصرح لك بالوصول</h2><p style="color:#666">هذه الصفحة متاحة فقط لحساب المالك (Owner).</p></div>');
	}
}

/**
 * ================================
 * وضع معاينة القوالب (Theme Preview Mode)
 * ================================
 * يسمح لحساب owner بمعاينة كامل الموقع — كل الصفحات بمحتواها الفعلي المحفوظ —
 * بشكل قالب آخر غير القالب النشط فعلياً بقاعدة البيانات، دون تغيير settings.theme
 * إطلاقاً. الآلية:
 *   - ?start_preview=SLUG (من زر "معاينة" بصفحة abma/templates.php) يبدأ المعاينة.
 *   - يُخزَّن اختيار القالب المؤقت داخل جلسة لوحة التحكم نفسها (DOCS-abma)، لذلك
 *     يبقى فعّالاً أثناء تصفح كل صفحات الموقع (وليس الرئيسية فقط) إلى أن يُنهيها.
 *   - ?stop_preview=1 يُنهي المعاينة ويعيد $site['theme'] لقيمته الفعلية.
 *   - شريط عائم يُضاف تلقائياً لأي صفحة تُعرض عبر safeRender() أثناء المعاينة
 *     (انظر maybeInjectPreviewBanner في twigload.php) — بدون أي تعديل يدوي لأي
 *     قالب Twig، ويعمل مع الـ 14 قالباً كلها بالتساوي.
 * أمان: لا تُغيّر أي بيانات دائمة بقاعدة البيانات (لا تكتب settings.theme إطلاقاً)،
 * فقط تُبدّل $site['theme'] بالذاكرة لهذا الطلب + علم بجلسة الأدمن الحالية، ولا
 * تعمل إطلاقاً إلا لمن يملك كوكي جلسة لوحة التحكم (DOCS-abma) أصلاً ويتحقق أنه owner
 * فعلياً — لذلك اعتُمد رابط GET بسيط بدل نموذج CSRF كامل. يجب استدعاؤها من
 * autoload.php الجذر بعد تحميل functions.php وقبل twigload.php مباشرة.
 */
function previewModeInit()
{
	global $site;

	// لا نفتح أي جلسة لزوار الموقع العاديين (ليس لديهم كوكي جلسة لوحة التحكم أصلاً)
	// هذا يحافظ على أداء وسلوك الموقع العام كما هو تماماً بدون أي تأثير.
	if (!isset($_COOKIE['DOCS-abma'])) {
		return;
	}

	$cookieParams = session_get_cookie_params();
	session_set_cookie_params($cookieParams["lifetime"], $cookieParams["path"], $cookieParams["domain"], true, true);
	session_name("DOCS-abma");
	@session_start();

	// ملاحظة: لا نستدعي session_regenerate_id() هنا كما تفعل sec_session_start()،
	// لأن ذلك سيُبطل معرّف جلسة لوحة التحكم الحالية ويُخرج الأدمن من تسجيل الدخول.
	$authorized = login_check_admin() && isOwner();

	if (!$authorized) {
		unset($_SESSION['preview_theme']);
		session_write_close();
		return;
	}

	if (isset($_GET['stop_preview'])) {
		unset($_SESSION['preview_theme']);
		session_write_close();
		header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
		exit;
	}

	if (isset($_GET['start_preview'])) {
		$slug = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $_GET['start_preview']);
		if ($slug !== '' && is_dir(getpath() . 'templates/' . $slug)) {
			$_SESSION['preview_theme'] = $slug;
		}
		session_write_close();
		header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
		exit;
	}

	session_write_close();

	if (!empty($_SESSION['preview_theme']) && is_dir(getpath() . 'templates/' . $_SESSION['preview_theme'])) {
		$GLOBALS['previewOriginalTheme'] = $site['theme'];
		$site['theme'] = $_SESSION['preview_theme'];
		$GLOBALS['previewModeActive'] = true;
	}
}

// delete password from login function
function login($phone)
{
	global $rows;
	global $countrows;
	// Sanitize and validate the data passed in
	if (check(($phone), "num")) {
		return false;
	}
	dbSelect("users", "id, membership", "WHERE phone = ? LIMIT 1", [$phone]);
	if ($countrows == 1) {
		$user_id = $rows[0]["id"];
		$membership = $rows[0]["membership"];

		if ($membership != "0") {
			// Get the user-agent string of the user.
			$user_browser = $_SERVER['HTTP_USER_AGENT'];
			$user_browser = 1;
			// XSS protection as we might print this value
			$user_id = numer(preg_replace("/[^0-9]+/", "", $user_id));
			$_SESSION['user_id'] = $user_id;
			$_SESSION['login_string'] = hash('sha512', $user_browser);

			// Add logs to database
			$info = ["ip" => getIP(), "browser" => getBrowser(), "system" => getOS(), "user-agent" => $_SERVER['HTTP_USER_AGENT']];
			$columns = "user_id, info, date, status";
			$values = [$user_id, serialize($info), date("Y-m-d H:i:s"), "success"];
			dbInsert("user_logs", $columns, $values);

			// Login successful.
			return "success";
		} else {
			return "no_verify";
		}
		// } else {
		// 	// Password is not correct
		// 	// Add logs to database
		// 	$info = ["ip" => getIP(), "browser" => getBrowser(), "system" => getOS(), "user-agent" => $_SERVER['HTTP_USER_AGENT']];
		// 	$columns = "user_id, info, date, status";
		// 	$values = [$user_id, serialize($info), date("Y-m-d H:i:s"), "error"];
		// 	dbInsert("user_logs", $columns, $values);

		// 	return false;
		// }
	} else {
		// No user exists.
		return false;
	}
}

function login_check()
{
	global $rows;
	global $countrows;
	// Check if all session variables are set 
	if (!empty($_SESSION['user_id']) and !empty($_SESSION['login_string'])) {
		$user_id = numer($_SESSION['user_id']);
		$login_string = $_SESSION['login_string'];
		// Get the user-agent string of the user.
		$user_browser = $_SERVER['HTTP_USER_AGENT'];
		$user_browser = 1;
		dbSelect("users", "id", "WHERE id=? LIMIT 1", [$user_id]);
		// $user_pass = $rows[0]["password"];
		if ($countrows == 1) {
			$login_check = hash('sha512', $user_browser);
			if (hash_equals($login_check, $login_string)) {
				// Logged In!!!! 
				return true;
			} else {
				// Not logged in 
				return false;
			}
		} else {
			// Not logged in 
			return false;
		}
	} else {
		// Not logged in 
		return false;
	}
}

function login_admin($user_email, $user_pass)
{
	global $rows;
	global $countrows;
	// Sanitize and validate the data passed in
	if (check($user_email, "email")) {
		return false;
	}
	if (strlen(hash('sha512', $user_pass)) != 128) {
		return false;
	}
	$totpCol = adminsTotpColumnsExist() ? ", totp_enabled" : "";
	dbSelect("admins", "id, username, email, password" . $totpCol, "WHERE email = ? AND status = ? LIMIT 1", [$user_email, "active"]);
	if ($countrows == 1) {
		$user_id = $rows[0]["id"];
		$db_password = $rows[0]["password"];

		// Check if the password in the database matches
		// the password the user submitted. We are using
		// the password_verify function to avoid timing attacks.
		// The hashed password.
		$user_pass = hash('sha512', $user_pass);
		if (password_verify($user_pass, $db_password)) {
			// Password is correct!
			// XSS protection as we might print this value
			$user_id = numer(preg_replace("/[^0-9]+/", "", $user_id));

			// التحقق بخطوتين (2FA/TOTP) — إن كان مفعّلاً بهذا الحساب، لا تُنشئ
			// جلسة كاملة الآن؛ بل جلسة "معلَّقة" مؤقتة (5 دقائق) بانتظار رمز
			// تحقق صحيح من abma/auth/login.php (الخطوة الثانية). راجع
			// includes/totp.php لمنطق التحقق وإكمال الجلسة فعلياً.
			if (!empty($rows[0]['totp_enabled']) && (int) $rows[0]['totp_enabled'] === 1) {
				$_SESSION['pending_2fa_admin_id'] = $user_id;
				$_SESSION['pending_2fa_expires'] = time() + 300;
				return "2fa_required";
			}

			$user_browser = $_SERVER['HTTP_USER_AGENT'];
			$user_browser = 1;
			$_SESSION['user_id'] = $user_id;
			$_SESSION['login_string'] = hash('sha512', $db_password . $user_browser);
			// Login successful.
			if (function_exists('do_action')) {
				do_action('ojuba_admin_login', $user_id, $user_email);
			}
			return "success";
		} else {
			// Password is not correct
			return false;
		}
	} else {
		// No user exists.
		return false;
	}
}

/**
 * جلب معلومات المستخدم
 */
function guser()
{
	global $rows;
	global $user;
	dbSelect("users", "*", "WHERE id = ? LIMIT 1", [$_SESSION['user_id']]);
	$user = $rows[0];
}

/**
 * تقوم هذه الوظيفة بالتحقق من نوع المدخلات وتعود بالناتج false في حال كان خطأ
 */
function check($var, $type)
{
	if ($type == "num") {
		if (!preg_match("/^[0-9]+$/", $var)) {
			return false;
		}
	} elseif ($type == "email") {
		strtolower($type);
		if (!filter_var($var, FILTER_VALIDATE_EMAIL) or !preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $var)) {
			return false;
		}
	} elseif ($type == "txt") {
		if (!preg_match("/^[a-zA-Z-\p{Arabic} ']*$/u", $var)) {
			return false;
		}
	} elseif ($type == "ar") {
		if (!preg_match("/^[\p{Arabic} ]+$/u", $var)) {
			return false;
		}
	} elseif ($type == "en") {
		if (!preg_match("/^[a-zA-Z-' ]*$/", $var)) {
			return false;
		}
	} elseif ($type == "url") {
		if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $var)) {
			return false;
		}
	}
}

/**
 * انشاء كود توكن لعملية ما مثلا تحقق بريد او تحقق كلمة سر
 * يقوم الكود انشاء كود جديد ويتأكد هل موجود في قاعدة سابقا ام لا
 * $size: حجم توكن ممكن  8 او 16 او اكثر حسب صعوبة كلما كان حجم اكثر راح يكون طويل
 * $table: الجدول الذي تحقق منه
 * $col: العمود الذي يتحقق منه
 * $type: token,id,serial
 */
/**
 * انشاء كود رقمي وحيد نفس آلية عمل الدالة السابقة
 * لكن هذا يكون كود به ارقام فقط
 * مثلا لارقام طلبات وكذا
 * مكان $column سيتم وضع اسم الخلية
 */
function genCode($table, $column, $type, $size)
{
	global $countrows;
	if ($type == "token") {
		$code_id =  [bin2hex(random_bytes($size))];
	} elseif ($type == "serial") {
		$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		$code_id = '';

		for ($i = 0; $i < $size; $i++) {
			$part = '';
			for ($j = 0; $j < $size; $j++) {
				$index = rand(0, strlen($characters) - 1);
				$part .= $characters[$index];
			}
			$code_id .= $part;
			if ($i < $size - 1) {
				$code_id .= '-';
			}
		}
		$code_id =  [$code_id];
	} else {
		$longid = abs(crc32(uniqid())) . abs(crc32(uniqid())) . abs(crc32(uniqid())) . abs(crc32(uniqid()));
		$code_id =  [substr($longid, 0, $size)];
	}
	dbSelect($table, "id", "Where $column=? LIMIT 1", $code_id);
	if ($countrows === 1) {
		do {
			if ($type = "token") {
				$code_id =  [bin2hex(random_bytes($size))];
			} else {
				$longid = abs(crc32(uniqid())) . abs(crc32(uniqid())) . abs(crc32(uniqid())) . abs(crc32(uniqid()));
				$code_id =  [substr($longid, 0, $size)];
			}
			dbSelect($table, "id", "Where $column=? LIMIT 1", $code_id);
		} while ($countrows === 1);
	}
	return $code_id[0];
}

/**
 * جلب نظام المستخدم من خلال HTTP_USER_AGENT
 * لكن يجب عدم الاعتماد عليه لانه يمكن تغييره من طرف المستخدم
 * يجب تنظيم المتغير عند عرضه او ادخاله في القاعدة
 */
function getOS()
{
	$user_agent = $_SERVER['HTTP_USER_AGENT'];
	$os_platform =   "Unknown";
	$os_array =   array(
		'/windows nt 10/i'      =>  'Windows 10',
		'/windows nt 6.3/i'     =>  'Windows 8.1',
		'/windows nt 6.2/i'     =>  'Windows 8',
		'/windows nt 6.1/i'     =>  'Windows 7',
		'/windows nt 6.0/i'     =>  'Windows Vista',
		'/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
		'/windows nt 5.1/i'     =>  'Windows XP',
		'/windows xp/i'         =>  'Windows XP',
		'/windows nt 5.0/i'     =>  'Windows 2000',
		'/windows me/i'         =>  'Windows ME',
		'/win98/i'              =>  'Windows 98',
		'/win95/i'              =>  'Windows 95',
		'/win16/i'              =>  'Windows 3.11',
		'/macintosh|mac os x/i' =>  'Mac OS X',
		'/mac_powerpc/i'        =>  'Mac OS 9',
		'/linux/i'              =>  'Linux',
		'/ubuntu/i'             =>  'Ubuntu',
		'/iphone/i'             =>  'iPhone',
		'/ipod/i'               =>  'iPod',
		'/ipad/i'               =>  'iPad',
		'/android/i'            =>  'Android',
		'/blackberry/i'         =>  'BlackBerry',
		'/webos/i'              =>  'Mobile'
	);
	foreach ($os_array as $regex => $value) {
		if (preg_match($regex, $user_agent)) {
			$os_platform = $value;
		}
	}
	return $os_platform;
}

/**
 * جلب متصفح المستخدم من خلال HTTP_USER_AGENT
 * لكن يجب عدم الاعتماد عليه لانه يمكن تغييره من طرف المستخدم
 * يجب تنظيم المتغير عند عرضه او ادخاله في القاعدة
 */
function getBrowser()
{
	$user_agent = $_SERVER['HTTP_USER_AGENT'];
	$browser        = "Unknown";
	$browser_array  = array(
		'/msie/i'       =>  'Internet Explorer',
		'/firefox/i'    =>  'Firefox',
		'/safari/i'     =>  'Safari',
		'/chrome/i'     =>  'Chrome',
		'/edge/i'       =>  'Edge',
		'/opera/i'      =>  'Opera',
		'/netscape/i'   =>  'Netscape',
		'/maxthon/i'    =>  'Maxthon',
		'/konqueror/i'  =>  'Konqueror',
		'/mobile/i'     =>  'Handheld Browser'
	);
	foreach ($browser_array as $regex => $value) {
		if (preg_match($regex, $user_agent)) {
			$browser = $value;
		}
	}
	return $browser;
}

/**
 * جلب عنوان IP المستخدم
 * لكن يجب عدم الاعتماد عليه لانه يمكن تغييره من طرف المستخدم
 * يجب تنظيم المتغير عند عرضه او ادخاله في القاعدة
 */
function getIP()
{
	$ip_address = '';
	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		$ip_address = $_SERVER['HTTP_CLIENT_IP']; // Get the shared IP Address
	} else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		//Check if the proxy is used for IP/IPs
		// Split if multiple IP addresses exist and get the last IP address
		if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') !== false) {
			$multiple_ips = explode(",", $_SERVER['HTTP_X_FORWARDED_FOR']);
			$ip_address = trim(current($multiple_ips));
		} else {
			$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
	} else if (!empty($_SERVER['HTTP_X_FORWARDED'])) {
		$ip_address = $_SERVER['HTTP_X_FORWARDED'];
	} else if (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
		$ip_address = $_SERVER['HTTP_FORWARDED_FOR'];
	} else if (!empty($_SERVER['HTTP_FORWARDED'])) {
		$ip_address = $_SERVER['HTTP_FORWARDED'];
	} else {
		$ip_address = $_SERVER['REMOTE_ADDR'];
	}
	return $ip_address;
}

/**
 * دالة توليد كلمة سر بشكل تلقائي
 * مع تحديد شروط كأحرف كبيرة وصغيرو وارقام ورموز
 * l: حروف صغيرى
 * u: حروف كبيرة
 * d: ارقام
 * s: حروف خاصة
 * الدالة بشكل افتراضي تنشئ كلمة سر بطول 8 حروف بكل الشروط generateStrongPassword()
 * اذا ادرت تخصص استعمل المتغيرات
 */
function tweak_array_rand($array)
{
	if (function_exists('random_int')) {
		return random_int(0, count($array) - 1);
	} elseif (function_exists('mt_rand')) {
		return mt_rand(0, count($array) - 1);
	} else {
		return array_rand($array);
	}
}
function genPass($length = 8, $available_sets = 'luds')
{
	$sets = array();
	if (strpos($available_sets, 'l') !== false)
		$sets[] = 'abcdefghjkmnpqrstuvwxyz';
	if (strpos($available_sets, 'u') !== false)
		$sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
	if (strpos($available_sets, 'd') !== false)
		$sets[] = '23456789';
	if (strpos($available_sets, 's') !== false)
		$sets[] = '!@#$%&*?';
	$all = '';
	$password = '';
	foreach ($sets as $set) {
		$password .= $set[tweak_array_rand(str_split($set))];
		$all .= $set;
	}
	$all = str_split($all);
	for ($i = 0; $i < $length - count($sets); $i++) {
		$password .= $all[tweak_array_rand($all)];
	}
	$password = str_shuffle($password);
	return $password;
}

/******
 * تقوم هذه الدالة بالتحقق من ان الجهاز المستخدم هو هاتف
 * لا يمكن الاعتماد عليها تماما لان المستخدم قادر على تغيير user agent 
 */

function isMobile()
{
	return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}

/**
 * تقوم هذه الوظيفة بإرجاع اشعار بتقنية bootstrap 5.3
 * حيث انها تستقبل الرسالة و نوع الاشعار و خيار ازالة الاشعار وهو اختياري
 * مثال: 
 * alerts("Thank you","success")
 * اذا اردت اظهار زر ازالة الاشعار
 * alerts("Thank you","success", 1)
 */
function alerts($text, $type, $close = false)
{
	$alert = '';
	if ($close) {
		$alert = '<div class="alert alert-' . $type . '">' . $text . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
	} else {
		$alert = '<div class="alert alert-' . $type . '">' . $text . '</div>';
	}
	return $alert;
}

/**
 * دالة اضافة اشعار عند حفظ او اجراء تعديل لنجاح المهمة او خطأ ما
 * $type: success, error, warning, info, question
 * $title: اما نص او html
 * $text: اما نص او html ، لكن اذا كان html يجب وضع علامة 1 في خانة html
 * $link: رابط عند ضغط على زر، يمكن استعمال متغير here لتحديث الصفحة
 * $html: اذا كان المحتوى html اكتب 1 او true
 * $close: اذا ترد غلق الاشعار فقط بدون تحديث او توجيه اكتب 1 او true
 */
function sweet($type, $title, $text, $link = null)
{
	global $lang;
	switch ($type) {
		case 'error':
			$colorbtn = "#dc3545";
			break;
		case 'success':
			$colorbtn = "#218838";
			break;
		case 'warning':
			$colorbtn = "#ffc107";
			break;
		case 'info':
			$colorbtn = "#17a2b8";
			break;
		case 'question':
			$colorbtn = "#17a2b8";
			break;
		default:
			$colorbtn = "#218838";
	}
	if (!$link) {
		$result =  "window.close()";
	} else {
		if ($link == "here") {
			$result = "window . location = window.location.href";
		} else {
			$result = "window . location = '" . $link . "'";
		}
	}
	echo "<script>Swal.fire({icon: '$type',title: `$title`,html: `$text`, confirmButtonText: '" . $lang['ok'] . "',confirmButtonColor: '$colorbtn',showCloseButton: true}).then((result)=>{if(result.isConfirmed){" . $result . "}else{" . $result . "}})</script>";
}


function sweetTwig($type, $title, $text, $link = null)
{
	global $lang;
	switch ($type) {
		case 'error':
			$colorbtn = "#dc3545";
			break;
		case 'success':
			$colorbtn = "#218838";
			break;
		case 'warning':
			$colorbtn = "#ffc107";
			break;
		case 'info':
			$colorbtn = "#17a2b8";
			break;
		case 'question':
			$colorbtn = "#17a2b8";
			break;
		default:
			$colorbtn = "#218838";
	}
	if (!$link) {
		$result =  "window.close()";
	} else {
		if ($link == "here") {
			$result = "window . location = window.location.href";
		} else {
			$result = "window . location = '" . $link . "'";
		}
	}
	return "<script>Swal.fire({icon: '$type',title: `$title`,html: `$text`, confirmButtonText: '" . $lang["ok"] . "',confirmButtonColor: '$colorbtn',showCloseButton: true}).then((result)=>{if(result.isConfirmed){" . $result . "}else{" . $result . "}})</script>";
}


/**
 * FILTER AND SANITISE URL
 */
function escUrl($url)
{
	if ('' == $url) {
		return $url;
	}
	// Remove any invalid characters from the URL.
	$url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\\x80-\\xff]|i', '', $url);
	// Strip any carriage returns or line feeds from the URL.
	$strip = array('%0d', '%0a', '%0D', '%0A');
	$url = (string) $url;
	$count = 1;
	while ($count) {
		$url = str_replace($strip, '', $url, $count);
	}
	// Encode any ampersands (`&`) and single quotes (`'`) as HTML entities.
	$url = str_replace(';//', '://', $url);
	$url = htmlentities($url);
	$url = str_replace('&amp;', '&#038;', $url);
	$url = str_replace("'", '&#039;', $url);
	$url = filter_var($url, FILTER_SANITIZE_URL);
	// Check to make sure that the URL starts with a slash (`/`).
	if ($url[0] !== '/') {
		return '';
	} else {
		return $url;
	}
}

/**
 * GET PAGE TITLE FROM PATH
 */
function setTitle($path)
{
	if ($path) {
		$path = rtrim($path, '.php');
		$paths = explode("/", $path);
		$paths = array_slice($paths, -2);
		return implode("_", $paths);
	} else {
		return "page_title";
	}
}

/**
 * ضغط وتصغير الصور المرفوعة تلقائياً (لتسريع تحميل الموقع)
 * يعمل فقط على الصور النقطية المدعومة من GD (jpg/png/gif/webp)، ولا يغيّر
 * SVG أو الصيغ غير المدعومة (bmp/tiff/heic...)، ولا يلمس الصور الصغيرة أصلاً
 */
function compressUploadedImage($filepath, $maxDimension = 1920, $quality = 82)
{
	if (!extension_loaded('gd')) return;
	$ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
	if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) return;

	$info = @getimagesize($filepath);
	if (!$info) return;
	$width = $info[0];
	$height = $info[1];
	if ($width <= 0 || $height <= 0) return;
	if ($width <= $maxDimension && $height <= $maxDimension) return; // لا حاجة للتصغير

	$data = @file_get_contents($filepath);
	if ($data === false) return;
	$image = @imagecreatefromstring($data);
	if (!$image) return;

	$ratio = min($maxDimension / $width, $maxDimension / $height);
	$newWidth = (int) round($width * $ratio);
	$newHeight = (int) round($height * $ratio);
	if ($newWidth < 1) $newWidth = 1;
	if ($newHeight < 1) $newHeight = 1;

	$resized = imagecreatetruecolor($newWidth, $newHeight);

	// الحفاظ على الشفافية لصيغ PNG/GIF/WEBP
	if (in_array($ext, ['png', 'gif', 'webp'])) {
		imagealphablending($resized, false);
		imagesavealpha($resized, true);
		$transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
		imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
	}

	imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

	switch ($ext) {
		case 'jpg':
		case 'jpeg':
			imagejpeg($resized, $filepath, $quality);
			break;
		case 'png':
			imagepng($resized, $filepath, 6);
			break;
		case 'gif':
			imagegif($resized, $filepath);
			break;
		case 'webp':
			if (function_exists('imagewebp')) {
				imagewebp($resized, $filepath, $quality);
			}
			break;
	}

	imagedestroy($image);
	imagedestroy($resized);
}

/***
 * $input = اسم حقل input file
 * $max_size = الحجم الأقصى بالميجا
 * $dir = مسار الرفع
 */
function up($name, $input, $dir, $max_size)
{
	if (is_uploaded_file($_FILES[$input]['tmp_name']) and !empty($_FILES[$input])) {
		global $filename;
		// get file info	
		$file_path = $_FILES[$input]['tmp_name'];
		$file_size = filesize($file_path);
		$file_info = finfo_open(FILEINFO_MIME_TYPE);
		$file_type = finfo_file($file_info, $file_path);
		$file_ext  = strtolower(pathinfo($_FILES[$input]['name'], PATHINFO_EXTENSION));
		$max_size = $max_size * 1024 * 1024; // تحويل الميجابايت إلى بايت
		// check size
		if ($file_size === 0) {
			return "خطأ، الملف فارغ"; // اذا كان الملف فارغ
			die();
		}
		if ($file_size > $max_size) {
			return "خطأ، حجم الملف أكبر من $max_size ميجابايت"; // اذا كان الحجم أكبر من الحد المسموح به
			die();
		}
		// check file type
		$allowedTypes = [
			'image/png' => 'png',
			'image/jpg' => 'jpg',
			'image/jpeg' => 'jpeg',
			'image/svg+xml' => 'svg',
			'image/webp' => 'webp',
			'image/avif' => 'avif',
			'image/gif' => 'gif',
			'image/bmp' => 'bmp',
			'image/x-bmp' => 'bmp',
			'image/x-ms-bmp' => 'bmp',
			'image/x-windows-bmp' => 'bmp',
			'image/vnd.microsoft.icon' => 'ico',
			'image/x-icon' => 'ico',
			'image/tiff' => 'tiff',
			'image/x-tiff' => 'tif',
			'image/heic' => 'heic',
			'image/heif' => 'heif',
			'image/heic-sequence' => 'heic',
			'image/heif-sequence' => 'heif'
		];
		if (!in_array($file_type, array_keys($allowedTypes)) or !in_array($file_ext, $allowedTypes)) {
			return "خطأ، امتداد الملف غير مسموح به";
			die();
		}
		if ($file_ext != "svg") {
			$imagesizeinfo = @getimagesize($file_path);
			$unsupported_image_exts = ['webp', 'avif', 'heic', 'heif', 'tif', 'tiff', 'bmp', 'ico'];
			if ($imagesizeinfo) {
				if (empty($imagesizeinfo['mime']) || !in_array($imagesizeinfo['mime'], array_keys($allowedTypes))) {
					return "خطأ، امتداد الملف غير مسموح به";
					die();
				}
				if ($imagesizeinfo[0] == 0 or $imagesizeinfo[1] == 0) {
					return "خطأ، امتداد الملف غير مسموح به";
					die();
				}
			} else {
				if (!in_array($file_ext, $unsupported_image_exts, true)) {
					return "خطأ، امتداد الملف غير مسموح به";
					die();
				}
			}
			$image_type = @exif_imagetype($file_path);
			$allowed_image_types = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF];
			if (defined('IMAGETYPE_WEBP')) {
				$allowed_image_types[] = IMAGETYPE_WEBP;
			}
			if (defined('IMAGETYPE_AVIF')) {
				$allowed_image_types[] = IMAGETYPE_AVIF;
			}
			if (defined('IMAGETYPE_BMP')) {
				$allowed_image_types[] = IMAGETYPE_BMP;
			}
			if (defined('IMAGETYPE_TIFF_II')) {
				$allowed_image_types[] = IMAGETYPE_TIFF_II;
			}
			if (defined('IMAGETYPE_TIFF_MM')) {
				$allowed_image_types[] = IMAGETYPE_TIFF_MM;
			}
			if (defined('IMAGETYPE_ICO')) {
				$allowed_image_types[] = IMAGETYPE_ICO;
			}
			if ($image_type !== false && !in_array($image_type, $allowed_image_types, true)) {
				return "خطأ، امتداد الملف غير مسموح به";
				die();
			}
			if ($image_type === false && !in_array($file_ext, $unsupported_image_exts, true)) {
				return "خطأ، امتداد الملف غير مسموح به";
				die();
			}
		}
		// file name and path
		// check filename 
		$check_filename = pathinfo($name, PATHINFO_EXTENSION);
		if (!empty($check_filename)) {
			$filename =  $name;
		} else {
			$extension = $allowedTypes[$file_type];
			$filename =  $name . "." . $extension;
		}
		if (!str_ends_with($dir, "/")) {
			$dir = $dir . "/";
		}
		$newFilepath = $dir . $filename;
		// move file
		if (move_uploaded_file($file_path, $newFilepath)) {
			compressUploadedImage($newFilepath); // تصغير وضغط الصورة تلقائياً إن لزم
			return "uploaded_done"; // success
		} else {
			return "خطأ، فشل في رفع الملف، اعد المحاولة"; // فشل في رفع الملف
			die();
		}
	}
}

/**
 * pdf upload
 */
function fileup($name, $input, $dir, $max_size)
{
	if (is_uploaded_file($_FILES[$input]['tmp_name']) && !empty($_FILES[$input])) {
		global $filename;

		// get file info
		$file_path = $_FILES[$input]['tmp_name'];
		$file_size = filesize($file_path);
		$file_ext  = strtolower(pathinfo($_FILES[$input]['name'], PATHINFO_EXTENSION));
		$max_size = $max_size * 1024 * 1024; // تحويل الميجابايت إلى بايت

		// check size
		if ($file_size === 0) {
			return "خطأ، الملف فارغ";
		}
		if ($file_size > $max_size) {
			return "خطأ، حجم الملف أكبر من {$max_size} بايت";
		}

		// allowed extensions only
		$allowedExts = [
			'png',
			'jpg',
			'jpeg',
			'svg',
			'doc',
			'docx',
			'xls',
			'xlsx',
			'ppt',
			'pptx',
			'csv',
			'rar',
			'zip',
			'7z',
			'tar',
			'iso',
			'txt',
			'html',
			'htm',
			'pdf',
			'apk',
			'xapk',
			'mp3',
			'wav',
			'wma',
			'aac',
			'flac',
			'mp4',
			'mpeg',
			'mov',
			'avi',
			'wmv'
		];

		if (!in_array($file_ext, $allowedExts)) {
			return "خطأ، امتداد الملف {$file_ext} غير مسموح به";
		}

		// check filename 
		$check_filename = pathinfo($name, PATHINFO_EXTENSION);
		if (!empty($check_filename)) {
			$filename = $name;
		} else {
			$filename = $name . "." . $file_ext;
		}

		if (!str_ends_with($dir, "/")) {
			$dir = $dir . "/";
		}
		$newFilepath = $dir . $filename;

		// move file
		if (move_uploaded_file($file_path, $newFilepath)) {
			return "uploaded_done";
		} else {
			return "خطأ، فشل في رفع الملف، اعد المحاولة";
		}
	}
}



function multiUP($name, $input, $dir, $max_size)
{
	$responses = [];
	$filenames = [];
	$max_size = $max_size * 1024 * 1024; // تحويل الميجابايت إلى بايت

	foreach ($_FILES[$input]['tmp_name'] as $key => $file_path) {
		if (is_uploaded_file($file_path) && !empty($_FILES[$input]['name'][$key])) {
			// get file info    
			$file_size = filesize($file_path);
			$file_info = finfo_open(FILEINFO_MIME_TYPE);
			$file_type = finfo_file($file_info, $file_path);
			$file_ext  = strtolower(pathinfo($_FILES[$input]['name'][$key], PATHINFO_EXTENSION));

			// check size
			if ($file_size === 0) {
				$responses[] = "empty_file"; // اذا كان الملف فارغ
				continue;
			}
			if ($file_size > $max_size) {
				$responses[] = "big_file"; // اذا كان الحجم أكبر من الحد المسموح به
				continue;
			}

			// check file type
			$allowedTypes = ['image/png' => 'png', 'image/jpg' => 'jpg', 'image/jpeg' => 'jpeg'];
			if (!in_array($file_type, array_keys($allowedTypes)) || !in_array($file_ext, $allowedTypes)) {
				$responses[] = "err_extensions";
				continue;
			}
			$imagesizeinfo = getimagesize($file_path);
			if (!in_array($imagesizeinfo['mime'], array_keys($allowedTypes))) {
				$responses[] = "err_extensions";
				continue;
			}
			if ($imagesizeinfo[0] == 0 || $imagesizeinfo[1] == 0 || empty($imagesizeinfo)) {
				$responses[] = "err_extensions";
				continue;
			}
			$imagesizeinfo = exif_imagetype($file_path);
			if ($imagesizeinfo != 2 && $imagesizeinfo != 3) {
				$responses[] = "err_extensions";
				continue;
			}

			// file name and path
			$extension = $allowedTypes[$file_type];
			$filename = $name . "_" . $key . "." . $extension; // تعديل اسم الملف ليكون فريداً
			$filenames[] = $filename; // حفظ اسم الملف في المصفوفة
			if (!str_ends_with($dir, "/")) {
				$dir = $dir . "/";
			}
			$newFilepath = $dir . $filename;

			// move file
			if (move_uploaded_file($file_path, $newFilepath)) {
				$responses[] = "uploaded_done"; // success
			} else {
				$responses[] = "failed_up"; // فشل في رفع الملف
			}
		}
	}

	return ['responses' => $responses, 'filenames' => $filenames];
}

function multifile($name, $input, $dir, $max_size)
{
	$responses = [];
	$filenames = [];
	$max_size = $max_size * 1024 * 1024; // تحويل الميجابايت إلى بايت

	foreach ($_FILES[$input]['tmp_name'] as $key => $file_path) {
		if (is_uploaded_file($file_path) && !empty($_FILES[$input]['name'][$key])) {
			// get file info    
			$file_size = filesize($file_path);
			$file_info = finfo_open(FILEINFO_MIME_TYPE);
			$file_type = finfo_file($file_info, $file_path);
			$file_ext  = strtolower(pathinfo($_FILES[$input]['name'][$key], PATHINFO_EXTENSION));

			// check size
			if ($file_size === 0) {
				$responses[] = "خطأ، الملف فارغ"; // اذا كان الملف فارغ
				continue;
			}
			if ($file_size > $max_size) {
				$responses[] = "خطأ، حجم الملف أكبر من $max_size ميجابايت"; // اذا كان الحجم أكبر من الحد المسموح به
				continue;
			}

			// check file type
			$allowedTypes =
				[
					'image/png' => 'png',
					'image/jpg' => 'jpg',
					'image/jpeg' => 'jpeg',
					'image/svg+xml' => 'svg',
					'application/msword' => 'doc',
					'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
					'application/vnd.ms-excel' => 'xls',
					'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
					'application/vnd.ms-powerpoint' => 'ppt',
					'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
					'text/csv' => 'csv',
					'application/x-rar-compressed' => 'rar',
					'application/zip' => 'zip',
					'application/x-7z-compressed' => '7z',
					'application/octet-stream' => 'iso',
					'text/plain' => 'txt',
					'application/pdf' => 'pdf',
					'application/vnd.android.package-archive' => 'apk',
					'application/xapk-package' => 'xapk',
					'audio/mpeg' => 'mp3',
					'audio/wav' => 'wav',
					'audio/x-ms-wma' => 'wma',
					'audio/aac' => 'aac',
					'audio/flac' => 'flac',
					'video/mp4' => 'mp4',
					'video/mpeg' => 'mpeg',
					'video/quicktime' => 'mov',
					'video/x-msvideo' => 'avi',
					'video/x-ms-wmv' => 'wmv'
				];
			if (!in_array($file_type, array_keys($allowedTypes)) || !in_array($file_ext, $allowedTypes)) {
				$responses[] = "خطأ، امتداد الملف غير مسموح به";
				continue;
			}

			// file name and path
			$check_filename = pathinfo($name, PATHINFO_EXTENSION);
			if (!empty($check_filename)) {
				$filename =  $name . "_" . $key; // اسم الملف يكون فريداً
			} else {
				$extension = $allowedTypes[$file_type];
				$filename =  $name . "_" . $key . "." . $extension; // اسم الملف يكون فريداً
			}
			$filenames[] = $filename; // حفظ اسم الملف في المصفوفة
			if (!str_ends_with($dir, "/")) {
				$dir = $dir . "/";
			}
			$newFilepath = $dir . $filename;

			// move file
			if (move_uploaded_file($file_path, $newFilepath)) {
				$responses[] = "uploaded_done"; // success
			} else {
				$responses[] = "خطأ، فشل في رفع الملف، اعد المحاولة"; // فشل في رفع الملف
			}
		} else {
			$responses[] = "file_not_uploaded";
		}
	}

	return ['responses' => $responses, 'filenames' => $filenames];
}

/**
 * $date = ضع تاريخ ميلاد العضو
 */
function age($date)
{
	$ToDate = date("Y-m-d H:i:s");
	return date_diff(date_create($date), date_create($ToDate))->y;
	// return date_diff(date_create($date), date_create($ToDate))->m;
	// return date_diff(date_create($date), date_create($ToDate))->d;
}


/**
 * اذا وضعت ,true
 * سيتم طباعة التاريخ مفصل: 49 دقيقة، 19 ثانية مضت
 * 
 * اذا تركتها فارغة سيطبع: 49 دقيقة مضت
 */
function ago($datetime, $full = false)
{
	global $lang;

	// تمرير null (تاريخ غير موجود/فارغ) لـ DateTime::__construct() أصبح مهجوراً
	// (deprecated) في PHP 8.1+ — نتحقق أولاً ونعيد نصاً فارغاً بدل تمرير null مباشرة.
	if (empty($datetime)) {
		return '';
	}

	$now = new DateTime;
	$ago = new DateTime($datetime);
	$diff = $now->diff($ago);
	// ملاحظة: DateInterval لا تملك خاصية "w" أصلاً؛ إسناد قيمة لها مباشرة على الكائن
	// كان يُنشئ خاصية ديناميكية غير معلنة، وهو أمر أصبح مهجوراً (deprecated) في PHP 8.2+.
	// لذلك نحسب الأسابيع والأيام المتبقية في مصفوفة منفصلة بدل تعديل الكائن نفسه.
	$weeks = (int) floor($diff->d / 7);
	$days = $diff->d - ($weeks * 7);
	$values = array(
		'y' => $diff->y,
		'm' => $diff->m,
		'w' => $weeks,
		'd' => $days,
		'h' => $diff->h,
		'i' => $diff->i,
		's' => $diff->s,
	);
	$string = array(
		'y' => $lang['year'],
		'm' => $lang['month'],
		'w' => $lang['week'],
		'd' => $lang['day'],
		'h' => $lang['hour'],
		'i' => $lang['minute'],
		's' => $lang['second'],
	);

	foreach ($string as $k => &$v) {
		if ($values[$k]) {
			$v = $values[$k] . ' ' . $v . ($values[$k] > 1 ? '' : '');
		} else {
			unset($string[$k]);
		}
	}

	if (!$full) $string = array_slice($string, 0, 1);
	$result = $string ? implode($lang['comma'] . ' ', $string)  : $lang['now'];
	return $result . " " . $lang['since'];
}

/**
 * check if array empty or not
 */
function notnull($array)
{
	if (empty($array)) {
		return false;
	}
	foreach ($array as $value) {
		if (is_null($value)) {
			return false;
		}
	}
	return true;
}

/**
 * echo <br>
 */
function br()
{
	echo "<br>";
}


/**
 * date diff in days
 */

function dateDiffInDays($date1, $date2)
{
	// Calculating the difference in timestamps
	$diff = strtotime($date2) - strtotime($date1);
	// 1 day = 24 hours
	// 24 * 60 * 60 = 86400 seconds
	$diffround = round($diff / 86400);
	if ($diffround <= 0) {
		return 0;
	} else {
		return abs($diffround);
	}
}

function arabicDate($en_date)
{
	$time = strtotime($en_date);
	$months = ["Jan" => "يناير", "Feb" => "فبراير", "Mar" => "مارس", "Apr" => "أبريل", "May" => "مايو", "Jun" => "يونيو", "Jul" => "يوليو", "Aug" => "أغسطس", "Sep" => "سبتمبر", "Oct" => "أكتوبر", "Nov" => "نوفمبر", "Dec" => "ديسمبر"];
	$days = ["Sat" => "السبت", "Sun" => "الأحد", "Mon" => "الإثنين", "Tue" => "الثلاثاء", "Wed" => "الأربعاء", "Thu" => "الخميس", "Fri" => "الجمعة"];
	$am_pm = ['AM' => 'صباحاً', 'PM' => 'مساءً'];

	$day = $days[date('D', $time)];
	$month = $months[date('M', $time)];
	$am_pm = $am_pm[date('A', $time)];
	$date = $day . ' ' . date('d', $time) . ' - ' . $month . ' ' . date('m', $time) . ' - ' . date('Y', $time) . '   ' . date('h:i', $time) . ' ' . $am_pm;
	return $date;
}


function generateRandomString($length = 10)
{
	$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';

	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[random_int(0, $charactersLength - 1)];
	}

	return $randomString;
}

function formatPhoneNumber($phone)
{
	// إزالة أي مسافات أو رموز غير الأرقام
	$phone = preg_replace('/[^0-9]/', '', $phone);

	// إذا كان الرقم يبدأ بـ "0" (مثل 0555555555)
	if (strpos($phone, '0') === 0) {
		$phone = '966' . substr($phone, 1); // استبدل "0" بـ "966"
	}
	if (strpos($phone, '5') === 0) {
		$phone = '9665' . substr($phone, 1); // استبدل "5" بـ "9665"
	}
	// إذا كان الرقم يحتوي على رمز "+" (مثل +966555555555)
	elseif (strpos($phone, '966') === 0) {
		$phone = '966' . substr($phone, 3); // تأكد من إزالة "+" إن وجدت
	}

	return $phone;
}

function whatsapp($to, $message, $type = NULL, $fileLink = null)
{
	global $user;
	global $site;
	$curl = curl_init();
	if ($type == "file") {
		if (!empty($fileLink)) {
			curl_setopt_array($curl, array(
				CURLOPT_URL => 'https://whatelex.com/api/create-message',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_POSTFIELDS => array(
					'appkey' => $site['wa_appkey'],
					'authkey' => $site['wa_authkey'],
					'to' => $to,
					'message' => $message,
					'file' => $fileLink,
					'sandbox' => 'false'
				),
			));
		} else {
			return json_encode(["status" => false, "message" => "The url is required"]);
		}
	} else {
		curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://whatelex.com/api/create-message',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => array(
				'appkey' => $site['wa_appkey'],
				'authkey' => $site['wa_authkey'],
				'to' => $to,
				'message' => $message,
				'sandbox' => 'false'
			),
		));
	}



	$response = curl_exec($curl);

	curl_close($curl);
	return $response;
}


function telegram($message)
{
	global $site;
	$token = $site['tg_token'];
	$id = $site['tg_id'];
	$url = "https://api.telegram.org/bot$token/sendMessage";

	$data = array(
		"chat_id" => $id,
		"parse_mode" => "HTML",
		"text" => $message
	);

	$options = array(
		'http' => array(
			'method'  => 'POST',
			'header'  => 'Content-type: application/x-www-form-urlencoded',
			'content' => http_build_query($data)
		)
	);

	$context  = stream_context_create($options); // إنشاء سياق للطلب
	$result = file_get_contents($url, false, $context); // الارسال

	if ($result === false) { // التحقق من النتيجة
		return "failed"; // فشل
	} else {
		return "success"; // نجاح
	}
}


/**
 * لإضافة ملف مرفق
 * $attachments = ["files/document.pdf", "files/image.jpg"]; 
 */

function sendEmail($subject, $email, $messageData, $template, $attachments = NULL)
{
	global $message;
	// send email
	$message = file_get_contents(getpath() . 'includes/libs/phpmailer/' . $template);
	foreach ($messageData as $key => $value) {
		$message = str_replace('%' . $key . '%', safer($value), $message);
	}
	if (!empty($attachments) and $attachments != false) {
		mailer($email, $subject, $message, NULL, $attachments);
	}
}

function generateShortDescription($fullDescription, $maxLength = 300)
{
	$short = strip_tags($fullDescription); // إزالة أي HTML
	if (strlen($short) <= $maxLength) {
		return $short;
	}

	$short = substr($short, 0, $maxLength);
	$lastPeriod = strrpos($short, '.');

	if ($lastPeriod !== false) {
		return trim(substr($short, 0, $lastPeriod + 1)); // تقطع عند آخر نقطة (.)
	}

	return trim($short) . '...'; // fallback لو ما فيه نقطة
}
