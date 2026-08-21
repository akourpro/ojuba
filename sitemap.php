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
// إعداد الروابط مع تفاصيل إضافية
if (@$_COOKIE['lang'] == "ar") {
    $site['name'] = $site['name'];
} else {
    $site['name'] = $site['name_en'];
}
$urls = [
    [
        'loc' => $site['site_url'],
        'lastmod' => date("Y-m-d"),
        'changefreq' => 'daily',
        'priority' => '1.0',
        'title' => $lang['home_page'] . ' | ' . $site['name'],
        'image' => $site['site_url'] . "files/images/" . $site['logo'],
        'image_title' => $site['name'] . ' - ' . $lang['home_page']
    ],
];

// بدء بناء ملف sitemap.xml
header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

// الصفحات الثابتة (تُستثنى إن كانت الوحدة غير مفعّلة في القالب النشط)
if (moduleEnabled('pages')) {
    dbSelect("pages", "slug, name, name_en, date", "WHERE status = ?", ["active"]);
    if ($countrows >= 1) {
        foreach ($rows as $row) {
            $slug = str_replace(" ", "-", $row['slug']);
            $slug = preg_replace('/[^\p{Arabic}a-zA-Z0-9_-]+/u', '', $slug);
            $loc = routeUrl('page', $slug);
            $lastmod = date("Y-m-d", strtotime($row['date']));
            $title = unescapeSafe((@$_COOKIE['lang'] == "ar") ? $row['name'] : ($row['name_en'] ?? $row['name']));

            $urls[] = [
                'loc' => $loc,
                'lastmod' => $lastmod,
                'changefreq' => 'weekly',
                'priority' => '0.8',
                'title' => $title,
                'image' => $site['site_url'] . "files/images/banner.png",
                'image_title' => $title,
            ];
        }
    }
}

// المقالات
if (moduleEnabled('blogs')) {
    dbSelect("blogs", "slug, name, name_en, image, date", "WHERE status = ?", ["active"]);
    if ($countrows >= 1) {
        foreach ($rows as $row) {
            $slug = str_replace(" ", "-", $row['slug']);
            $slug = preg_replace('/[^\p{Arabic}a-zA-Z0-9_-]+/u', '', $slug);
            $loc = routeUrl('blog', $slug);
            $lastmod = date("Y-m-d", strtotime($row['date']));
            $title = unescapeSafe((@$_COOKIE['lang'] == "ar") ? $row['name'] : ($row['name_en'] ?? $row['name']));
            $image = !empty($row['image'])
                ? $site['site_url'] . "files/blogs/" . $row['image']
                : $site['site_url'] . "files/images/banner.png";

            $urls[] = [
                'loc' => $loc,
                'lastmod' => $lastmod,
                'changefreq' => 'weekly',
                'priority' => '0.8',
                'title' => $title,
                'image' => $image,
                'image_title' => $title,
            ];
        }
    }
}

// الخدمات
if (moduleEnabled('services')) {
    dbSelect("services", "slug, name, name_en, image, date", "WHERE status = ?", ["active"]);
    if ($countrows >= 1) {
        foreach ($rows as $row) {
            $slug = str_replace(" ", "-", $row['slug']);
            $slug = preg_replace('/[^\p{Arabic}a-zA-Z0-9_-]+/u', '', $slug);
            $loc = routeUrl('service', $slug);
            $lastmod = date("Y-m-d", strtotime($row['date']));
            $title = unescapeSafe((@$_COOKIE['lang'] == "ar") ? $row['name'] : ($row['name_en'] ?? $row['name']));
            $image = !empty($row['image'])
                ? $site['site_url'] . "files/services/" . $row['image']
                : $site['site_url'] . "files/images/banner.png";

            $urls[] = [
                'loc' => $loc,
                'lastmod' => $lastmod,
                'changefreq' => 'weekly',
                'priority' => '0.8',
                'title' => $title,
                'image' => $image,
                'image_title' => $title,
            ];
        }
    }
}

// معرض الأعمال
if (moduleEnabled('portfolio')) {
    dbSelect("portfolio", "id, slug, name, name_en, image, date", "WHERE status = ?", ["active"]);
    if ($countrows >= 1) {
        foreach ($rows as $row) {
            $loc = (!empty($row['slug']))
                ? routeUrl('portfolio', $row['slug'])
                : routeUrl('portfolio', $row['id']);
            $lastmod = date("Y-m-d", strtotime($row['date']));
            $title = unescapeSafe((@$_COOKIE['lang'] == "ar") ? $row['name'] : ($row['name_en'] ?? $row['name']));
            $image = !empty($row['image'])
                ? $site['site_url'] . "files/portfolio/" . $row['image']
                : $site['site_url'] . "files/images/banner.png";

            $urls[] = [
                'loc' => $loc,
                'lastmod' => $lastmod,
                'changefreq' => 'weekly',
                'priority' => '0.8',
                'title' => $title,
                'image' => $image,
                'image_title' => $title,
            ];
        }
    }
}

// صفحة الأسعار (إن كانت مفعّلة)
if (moduleEnabled('pricing')) {
    $urls[] = [
        'loc' => $site['site_url'] . "pricing",
        'lastmod' => date("Y-m-d"),
        'changefreq' => 'weekly',
        'priority' => '0.7',
        'title' => $lang['pricing'] ?? 'Pricing',
        'image' => '',
        'image_title' => '',
    ];
}

// صفحة التواصل (إن كانت مفعّلة)
if (moduleEnabled('contact')) {
    $urls[] = [
        'loc' => routeUrl('contact'),
        'lastmod' => date("Y-m-d"),
        'changefreq' => 'monthly',
        'priority' => '0.5',
        'title' => $lang['contact_us'] ?? 'Contact',
        'image' => '',
        'image_title' => '',
    ];
}

foreach ($urls as $url) {
    echo '<url>';
    echo '<loc>' . htmlspecialchars($url['loc']) . '</loc>';
    echo '<lastmod>' . $url['lastmod'] . '</lastmod>';
    echo '<changefreq>' . $url['changefreq'] . '</changefreq>';
    echo '<priority>' . $url['priority'] . '</priority>';

    if (!empty($url['image'])) {
        echo '<image:image>';
        echo '<image:loc>' . htmlspecialchars($url['image']) . '</image:loc>';
        echo '<image:title>' . htmlspecialchars($url['image_title']) . '</image:title>';
        echo '</image:image>';
    }

    echo '</url>';
}

echo '</urlset>';
