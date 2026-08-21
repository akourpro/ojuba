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
if (!moduleEnabled('portfolio')) {
    echo safeRender('404.twig', [
        "error_type"        => "404",
        "error_message"     => $lang['page_not_found'],
        "error_description" => $lang['page_not_found_desc'],
    ]);
    die();
}
if (isset($_GET['id']) or isset($_GET['slug'])) {
    if (isset($_GET['id'])) {
        $id = safer($_GET['id']);
        $id = preg_replace('/[^\p{Arabic}a-zA-Z0-9_-]+/u', '', $id);
        $id = strtolower($id);
        dbSelect("portfolio", "*", "WHERE id = ? AND status = ? LIMIT 1", [$id, "active"]);
    } elseif (isset($_GET['slug'])) {
        $slug = safer($_GET['slug']);
        $slug = preg_replace('/[^\p{Arabic}a-zA-Z0-9_-]+/u', '', $slug);
        $slug = strtolower($slug);
        dbSelect("portfolio", "*", "WHERE slug = ? AND status = ? LIMIT 1", [$slug, "active"]);
    }
    if ($countrows == 1) {
        $portfolio = $rows[0];
        $id = $portfolio['id'];
        if ($_COOKIE['lang'] == "ar") {
            $portfolio['name'] = $portfolio['name'];
            $portfolio['description'] = $portfolio['description'];
        } else {
            $portfolio['name'] = $portfolio['name_en'];
            $portfolio['description'] = $portfolio['description_en'];
        }
        $portfolio['demo'] = $portfolio['url'];
        $portfolio['url'] = routeUrl('portfolio', $portfolio['id']);
        $portfolio['image'] = !empty($portfolio['image'])
            ? $site['site_url'] . "files/portfolio/" . $portfolio['image']
            : "";

        if ($portfolio['category'] >= 1) {
            dbSelect("categories", "id, name, name_en", "WHERE id = ? LIMIT 1", [$portfolio['category']]);
            if ($countrows == 1) {
                if ($_COOKIE['lang'] == "ar") {
                    $portfolio['category'] = $rows[0]['name'];
                } else {
                    $portfolio['category'] = $rows[0]['name_en'];
                }
            } else {
                $portfolio['category'] = "";
            }
        }

        // وظيفة لتحويل النصوص إلى UTF-8 من أجل السيرفرات التي لا تدعم UTF-8
        function utf8ize($mixed)
        {
            if (is_array($mixed)) {
                foreach ($mixed as $key => $value) {
                    $mixed[$key] = utf8ize($value);
                }
            } elseif (is_string($mixed)) {
                $mixed = mb_convert_encoding($mixed, 'UTF-8', 'UTF-8');
            }
            return $mixed;
        }


        // إعدادات Schema
        $inLang = (isset($_COOKIE['lang']) && $_COOKIE['lang'] === 'ar') ? 'ar' : 'en';
        $demoUrl   = !empty($portfolio['url']) ? $portfolio['url'] : null;
        $cleanDesc = str_replace(["\r\n", "\n", "\r", "    ", "'", '"'], '', generateShortDescription($portfolio['description']));

        $schema = [
            "@context" => "https://schema.org",
            "@type"    => "CreativeWork",
            "@id"      => $portfolio['url'] . "#creativework",
            "url"      => $portfolio['url'],
            "name"     => $portfolio['name'],
            "description" => $cleanDesc,
            "text"        => strip_tags($portfolio['description']),
            "inLanguage"  => $inLang,

            "image" => [
                "@type"  => "ImageObject",
                "url"    => $portfolio['image'],
                "width"  => 1200, // حدّثها لأبعادك الفعلية
                "height" => 630
            ],
            "thumbnailUrl" => $portfolio['image'],

            "author" => [
                "@type" => businessSchemaType(),
                "name"  => $site['name'],
                "url"   => $site['site_url']
            ],
            "publisher" => [
                "@type" => businessSchemaType(),
                "name"  => $site['name'],
                "logo"  => [
                    "@type" => "ImageObject",
                    "url"   => $site['site_url'] . "images/logo.png"
                ]
            ],

            // عنصر ضمن مجموعة "الأعمال / البورتفوليو"
            "isPartOf" => [
                "@type" => "Collection",
                "name"  => $site['name'] . " Portfolio",
                "url"   => routeUrl('portfolio')
            ],

            "mainEntityOfPage" => [
                "@type" => "WebPage",
                "@id"   => $portfolio['url']
            ],
        ];

        // أضف رابط المعاينة كـ sameAs إن وُجد
        if ($demoUrl) {
            $schema["sameAs"] = [$demoUrl];
        }

        $schema_utf8 = utf8ize($schema);



        // الاعمال والمشاريع الاخرى
        dbSelect("portfolio", "id, slug, name, name_en, url, image", "WHERE id != ? AND status = ? ORDER BY id DESC LIMIT 6", [$id, "active"]);
        $other_portfolios = [];
        foreach ($rows as $row) {
            if ($_COOKIE['lang'] == "ar") {
                $row['name'] = $row['name'];
            } else {
                $row['name'] = $row['name_en'];
            }
            $row['url'] = routeUrl('portfolio', $row['id']);
            $other_portfolios[] = $row;
        }

        // Render the portfolio view
        echo safeRender('portfolio/view.twig', [
            'portfolio' => $portfolio,
            'other_portfolios' => $other_portfolios,
            'schema_json' => json_encode($schema_utf8, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        ]);
    } else {
        echo safeRender('404.twig', [
            "error_type" => "404",
            "error_message" => $lang['page_not_found'],
            "error_description" => $lang['page_not_found_desc'],
        ]);
        die();
    }
} else {
    echo safeRender('404.twig', [
        "error_type" => "404",
        "error_message" => $lang['page_not_found'],
        "error_description" => $lang['page_not_found_desc'],
    ]);
    die();
}
