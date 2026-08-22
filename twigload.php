<?php
// include twig
require_once 'includes/libs/twig/vendor/autoload.php';
$loader = new \Twig\Loader\FilesystemLoader(getpath() . 'templates/' . $site['theme']);

// تفعيل كاش Twig فقط إذا كان المجلد قابلاً للكتابة فعلياً، لتفادي انهيار
// الموقع بالكامل في حال اختلاف صلاحيات السيرفر (مثل بعض إعدادات XAMPP/الاستضافة)
$twigCacheDir = getpath() . 'compilation_cache';
if (!is_dir($twigCacheDir)) {
    @mkdir($twigCacheDir, 0777, true);
}
$twigCacheEnabled = is_dir($twigCacheDir) && is_writable($twigCacheDir);

$twig = new \Twig\Environment($loader, [
    'debug' => true,
    'cache' => $twigCacheEnabled ? $twigCacheDir : false,
    'auto_reload' => true,
]);

/**
 * تفريغ مجلد كاش Twig بالكامل (يُستخدم عند اكتشاف ملف كاش تالف).
 */
function clearTwigCache()
{
    global $twigCacheDir, $twigCacheEnabled;
    if (empty($twigCacheEnabled) || empty($twigCacheDir) || !is_dir($twigCacheDir)) {
        return;
    }
    try {
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($twigCacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
    } catch (\Throwable $e) {
        error_log('clearTwigCache failed: ' . $e->getMessage());
    }
}

/**
 * تُضيف شريط "وضع معاينة القالب" العائم أسفل الصفحة تلقائياً عند تفعيل المعاينة
 * (previewModeInit في includes/functions.php)، لأي صفحة تُعرض عبر safeRender()
 * بغض النظر عن القالب النشط فعلياً — بدون أي تعديل يدوي لملفات Twig.
 */
function maybeInjectPreviewBanner($html)
{
    global $site;
    if (empty($GLOBALS['previewModeActive']) || !is_string($html)) {
        return $html;
    }

    $previewSlug = $site['theme'];
    $previewData = themeData($previewSlug);
    $previewLabel = (is_array($previewData) && !empty($previewData['name'])) ? $previewData['name'] : $previewSlug;

    $originalSlug = $GLOBALS['previewOriginalTheme'] ?? '';
    $originalData = $originalSlug !== '' ? themeData($originalSlug) : false;
    $originalLabel = (is_array($originalData) && !empty($originalData['name'])) ? $originalData['name'] : $originalSlug;

    $exitUrl = htmlspecialchars(rtrim($site['site_url'], '/') . '/?stop_preview=1');

    $bar = '<div id="wu-preview-mode-bar" style="position:fixed;bottom:0;left:0;right:0;z-index:2147483647;background:#1a1a1a;color:#fff;padding:10px 16px;font-family:Tahoma,Arial,sans-serif;font-size:14px;line-height:1.6;display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:14px;box-shadow:0 -2px 10px rgba(0,0,0,.25);direction:rtl;text-align:center">'
        . '<span>🎨 أنت في <strong>وضع معاينة القالب</strong>: يظهر محتوى موقعك الفعلي حالياً بقالب «' . htmlspecialchars($previewLabel) . '» بدل القالب النشط «' . htmlspecialchars($originalLabel) . '»</span>'
        . '<a href="' . $exitUrl . '" style="background:#e53935;color:#fff;padding:6px 16px;border-radius:6px;text-decoration:none;font-weight:bold;white-space:nowrap">إنهاء المعاينة</a>'
        . '</div>'
        . '<style>body{padding-bottom:56px !important}</style>';

    if (stripos($html, '</body>') !== false) {
        return preg_replace('/<\/body>/i', $bar . '</body>', $html, 1);
    }
    return $html . $bar;
}

/**
 * غلاف آمن لعرض قوالب Twig: في حال كان ملف الكاش المُجمّع تالفاً (مثلاً بسبب
 * انقطاع الكتابة على السيرفر أو امتلاء القرص أثناء التوليد)، يظهر ذلك عادة
 * كخطأ PHP ParseError غير ملتقط يوقف الموقع بالكامل ويكشف مسارات السيرفر
 * للزائر. هذه الدالة تلتقط أي خطأ أثناء العرض، تُفرّغ الكاش، وتعيد العرض مرة
 * واحدة بدون كاش؛ فإن فشلت مجدداً تُظهر صفحة خطأ بسيطة بدل كشف تفاصيل السيرفر.
 */
function safeRender($template, array $vars = [])
{
    global $twig, $twigCacheEnabled, $twigCacheDir;

    // نقطة امتداد عامة (نظام Hooks/Actions، راجع includes/hooks.php): تسمح لأي
    // إضافة طرف ثالث بمجلد includes/addons/ بتعديل/إضافة أي متغيّر Twig قبل
    // عرض أي صفحة عامة مباشرة — دون تعديل أي ملف Twig أو PHP جذري.
    if (function_exists('apply_filters')) {
        $vars = apply_filters('ojuba_render_vars', $vars, $template);
    }

    try {
        return maybeInjectPreviewBanner($twig->render($template, $vars));
    } catch (\Throwable $e) {
        error_log('[Twig] فشل عرض "' . $template . '": ' . $e->getMessage());

        clearTwigCache();

        $originalCache = $twig->getCache();
        try {
            $twig->setCache(false);
            $html = $twig->render($template, $vars);
            $twig->setCache($originalCache);
            return maybeInjectPreviewBanner($html);
        } catch (\Throwable $e2) {
            error_log('[Twig] فشل العرض مجدداً بعد تفريغ الكاش لـ "' . $template . '": ' . $e2->getMessage());
            if (!headers_sent()) {
                http_response_code(500);
            }
            return '<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><title>خطأ غير متوقع</title></head>'
                . '<body style="font-family:sans-serif;text-align:center;padding:80px 20px">'
                . '<h1>حدث خطأ غير متوقع</h1><p>نعمل على إصلاحه، الرجاء المحاولة لاحقاً.</p>'
                . '</body></html>';
        }
    }
}

$filter = new \Twig\TwigFilter('unserialize', 'unserialize');
$twig->addFilter($filter);

// ملاحظة مهمة: يجب تسجيل دوال/فلاتر Twig هنا باستخدام اسم دالة PHP حقيقي (نص)
// وليس closure مباشر. بعض إصدارات PHP الحديثة (لوحظ مع PHP 8.4) تجعل مُصرّف
// Twig يحاول تضمين الـ closure نفسه داخل كود PHP المُولَّد (compiled cache)
// بصيغة غير صالحة (مثل {closure:...}) مما يسبب PHP ParseError عند تنفيذ
// القالب المُجمَّع، ويظهر كخطأ "unexpected token" يوقف الموقع بالكامل.
// الحل: استخدام دوال PHP حقيقية مُسمّاة بدل closures عند التسجيل في Twig.
$twig->addFunction(new \Twig\TwigFunction('nl2br_raw', 'nl2br_raw'));

// السماح للقوالب بمعرفة أي وحدة (module) مفعّلة في القالب النشط
// مثال استخدام داخل twig: {% if moduleEnabled('blogs') %} ... {% endif %}
$twig->addFunction(new \Twig\TwigFunction('moduleEnabled', 'moduleEnabled'));


$twig->addExtension(new \Twig\Extension\DebugExtension());
$templatePath = 'templates/' . $site['theme'];

$twig->addGlobal('templatePath', $templatePath);

$twig->addGlobal('csrftoken', $csrf->header());
$twig->addGlobal('siteUrl', $site["site_url"]);
$twig->addGlobal('whatsapp_number', $site['whatsapp_number']);

// روابط المسارات القابلة للتخصيص (نظام "روابط المسارات" — راجع routeUrl()/
// routeSlug() بـ includes/functions.php) — متغيّر Twig عام "routes" متاح بكل
// صفحة بأي قالب. **إلزامي** لكل قالب حالي ومستقبلي: أي رابط لمقال/خدمة/عمل/
// مباراة/صفحة/تواصل/بحث يُبنى بأي ملف Twig يجب أن يستخدم {{ routes.blog }} ونحوها
// بدل كتابة "blog"/"service"/... حرفياً، وإلا لن يعمل تخصيص الروابط لذلك القالب.
// مثال: <a href="{{ siteUrl }}{{ routes.blog }}/{{ post.slug }}">
$twig->addGlobal('routes', [
    'blog'      => routeSlug('blog'),
    'service'   => routeSlug('service'),
    'portfolio' => routeSlug('portfolio'),
    'matches'   => routeSlug('matches'),
    'page'      => routeSlug('page'),
    'contact'   => routeSlug('contact'),
    'search'    => routeSlug('search'),
]);

$twig->addGlobal('currentLang', safer(@$_COOKIE['lang']));
$twig->addGlobal('languageMode', $site['language_mode'] ?? 'both');

$twig->addGlobal('tickerEnabled', !empty($site['ticker_enabled']));
$twig->addGlobal('tickerSpeed', max(6, (int) ($site['ticker_speed'] ?? 26)));
if (safer(@$_COOKIE['lang']) == "en") {
    $twig->addGlobal('siteName', html_entity_decode($site['name_en'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $twig->addGlobal('siteDescription', $site['description_en']);
    $twig->addGlobal('siteMetaTags', $site['site_metaTags_en']);
    $twig->addGlobal('location', $site['location_en']);
} else {
    $twig->addGlobal('siteName', $site['name']);
    $twig->addGlobal('siteDescription', $site['description']);
    $twig->addGlobal('siteMetaTags', $site['site_metaTags']);
    $twig->addGlobal('location', $site['location']);
}


$twig->addGlobal('logo', $site['logo']);
$twig->addGlobal('logo_color', $site['logo_color']);


$twig->addGlobal('facebook', $site['facebook']);
$twig->addGlobal('instagram', $site['instagram']);
$twig->addGlobal('twitter', $site['twitter']);
$twig->addGlobal('x', $site['twitter']);
$twig->addGlobal('github', $site['github']);
$twig->addGlobal('snapchat', $site['snapchat']);
$twig->addGlobal('discord', $site['discord']);
$twig->addGlobal('linkedin', $site['linkedin']);
$twig->addGlobal('youtube', $site['youtube']);


$twig->addGlobal('site_mail', $site['site_mail']);
$twig->addGlobal('site_phone', $site['site_phone']);
$twig->addGlobal('maps', $site['maps']);
$twig->addGlobal('pdf', $site['pdf']);

$twig->addGlobal('lang', $lang);

// Load pages
dbSelect("pages", "name, name_en, slug", "WHERE status = ? ORDER BY id DESC", ['active']);
if ($countrows >= 1) {
    $pages = [];
    foreach ($rows as $row) {
        if (safer(@$_COOKIE['lang']) == "ar") {
            $pages[] = [
                'name' => $row['name'],
                'slug' => $row['slug'],
            ];
        } else {
            $pages[] = [
                'name' => $row['name_en'],
                'slug' => $row['slug'],
            ];
        }
    }
    $twig->addGlobal('pages', $pages);
} else {
    $twig->addGlobal('pages', []);
}


// Load clients
dbSelect("clients", "name, logo, url", "WHERE status = ? ORDER BY id DESC", [1]);
if ($countrows >= 1) {
    $clients = $rows;
    foreach ($clients as &$client) {
        $client['logo'] = $site['site_url'] . 'files/clients/' . $client['logo'];
    }
    $twig->addGlobal('clients', $clients);
} else {
    $twig->addGlobal('clients', []);
}


// Load services
dbSelect("services", "name, name_en, slug", "WHERE status = ? ORDER BY id DESC", ['active']);
if ($countrows >= 1) {
    $services = [];
    foreach ($rows as $row) {
        if (safer(@$_COOKIE['lang']) == "ar") {
            $services[] = [
                'name' => $row['name'],
                'slug' => $row['slug'],
            ];
        } else {
            $services[] = [
                'name' => $row['name_en'],
                'slug' => $row['slug'],
            ];
        }
    }

    $twig->addGlobal('services', $services);
} else {
    $twig->addGlobal('services', []);
}


// Load blog categories (متاحة بكل الصفحات لبناء قائمة أقسام إخبارية بالهيدر)
if (moduleEnabled('blog_categories') && moduleEnabled('blogs')) {
    dbSelect("blog_categories", "id, name, name_en", "ORDER BY id ASC");
    if ($countrows >= 1) {
        $blogCategoriesNav = [];
        foreach ($rows as $row) {
            $blogCategoriesNav[] = [
                'id'   => $row['id'],
                'name' => (safer(@$_COOKIE['lang']) == "ar") ? $row['name'] : ($row['name_en'] ?? $row['name']),
                'url'  => routeUrl('blog') . '?category=' . $row['id'],
            ];
        }
        $twig->addGlobal('blogCategories', $blogCategoriesNav);
    } else {
        $twig->addGlobal('blogCategories', []);
    }
} else {
    $twig->addGlobal('blogCategories', []);
}

// إعلانات التذييل (موضع "footer") — متاحة بكل صفحات الموقع لأن footer.twig
// مشترك بينها جميعاً، بنفس اصطلاح blogCategories أعلاه. getAdsByPosition()
// تتحقق ذاتياً من تفعيل وحدة "الإعلانات" ووجود جدول ads.
$twig->addGlobal('adsFooter', getAdsByPosition('footer'));
