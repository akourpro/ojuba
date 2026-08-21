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
$blogs = [];
if (moduleEnabled('blogs')) {
    dbSelect("blogs", "id, slug, name, name_en, image", "WHERE status = ? ORDER BY id DESC LIMIT 3", ["active"]);
    if ($countrows >= 1) {
        foreach ($rows as $row) {
            if ($_COOKIE['lang'] == "ar") {
                $row['name'] = $row['name'];
            } else {
                $row['name'] = $row['name_en'] ?? $row['name'];
            }
            $row['name'] = unescapeSafe($row['name']);
            $row['image'] = $site['site_url'] . "files/blogs/" . $row['image'];
            $row['url'] = routeUrl('blog', $row['slug']);
            $blogs[] = $row;
        }
    }
}

// بيانات إضافية مخصّصة لقوالب الأخبار (المقال الرئيسي، الشبكة، الأكثر قراءة، الأقسام)
// إضافية بالكامل ولا تُغيّر أو تستبدل $blogs الأصلية المستخدمة ببقية القوالب — استعلامات منفصلة
// كي لا يتأثر أي قالب آخر يعتمد على $blogs بحقولها/عددها الحالي.
$newsBlogCategoriesEnabled = moduleEnabled('blog_categories');
$news_latest = [];
$news_trending = [];
$news_categories = [];
if (moduleEnabled('blogs')) {
    // المقالات "المميّزة/العاجلة" (featured) تُقدَّم أولاً إن كانت الخاصية مُجهَّزة
    // بقاعدة البيانات (عبر ترحيل abma/blogs/migrate.php)، وإلا يبقى الترتيب
    // بالأحدث تاريخياً كما كان دائماً — بدون أي كسر على التركيبات القديمة.
    $newsOrderBy = blogsFeaturedColumnExists() ? "featured DESC, id DESC" : "id DESC";
    dbSelect(
        "blogs",
        "id, slug, name, name_en, image, category, description, description_en, date, views" . (blogsFeaturedColumnExists() ? ", featured" : ""),
        "WHERE status = ? ORDER BY " . $newsOrderBy . " LIMIT 13",
        ["active"]
    );
    if ($countrows >= 1) {
        foreach ($rows as $row) {
            if ($_COOKIE['lang'] == "ar") {
                $row['name'] = $row['name'];
                $row['description'] = $row['description'];
            } else {
                $row['name'] = $row['name_en'] ?? $row['name'];
                $row['description'] = $row['description_en'] ?? $row['description'];
            }
            $row['name'] = unescapeSafe($row['name']);
            $row['reading_time'] = readingTimeMinutes($row['description']);
            $row['description'] = unescapeSafe(mb_substr(strip_tags((string) $row['description']), 0, 140));

            if ($newsBlogCategoriesEnabled && $row['category'] >= 1) {
                dbSelect("blog_categories", "id, name, name_en", "WHERE id = ? LIMIT 1", [$row['category']]);
                if ($countrows == 1) {
                    $row['category_id']   = $rows[0]['id'];
                    $row['category_name'] = unescapeSafe(($_COOKIE['lang'] == "ar") ? $rows[0]['name'] : $rows[0]['name_en']);
                } else {
                    $row['category_id']   = 0;
                    $row['category_name'] = "";
                }
            } else {
                $row['category_id']   = 0;
                $row['category_name'] = "";
            }

            $row['date_human'] = ago($row['date']);
            $row['image'] = $site['site_url'] . "files/blogs/" . $row['image'];
            $row['url'] = routeUrl('blog', $row['slug']);
            $news_latest[] = $row;
        }
    }

    dbSelect(
        "blogs",
        "id, slug, name, name_en, image, views",
        "WHERE status = ? ORDER BY views DESC, id DESC LIMIT 5",
        ["active"]
    );
    if ($countrows >= 1) {
        foreach ($rows as $row) {
            if ($_COOKIE['lang'] == "ar") {
                $row['name'] = $row['name'];
            } else {
                $row['name'] = $row['name_en'] ?? $row['name'];
            }
            $row['name'] = unescapeSafe($row['name']);
            $row['image'] = $site['site_url'] . "files/blogs/" . $row['image'];
            $row['url'] = routeUrl('blog', $row['slug']);
            $news_trending[] = $row;
        }
    }

    if ($newsBlogCategoriesEnabled) {
        dbSelect("blog_categories", "id, name, name_en", "ORDER BY id ASC");
        if ($countrows >= 1) {
            foreach ($rows as $row) {
                if ($_COOKIE['lang'] == "ar") {
                    $row['name'] = $row['name'];
                } else {
                    $row['name'] = $row['name_en'] ?? $row['name'];
                }
                $row['name'] = unescapeSafe($row['name']);
                $row['url'] = routeUrl('blog') . '?category=' . $row['id'];
                $news_categories[] = $row;
            }
        }
    }
}

// Get services
$services = [];
if (moduleEnabled('services')) {
    dbSelect("services", "id, slug, name, name_en, description, description_en, image, icon", "WHERE status = ? ORDER BY id DESC", ["active"]);
    if ($countrows >= 1) {
        foreach ($rows as $row) {
            if ($_COOKIE['lang'] == "ar") {
                $row['name'] = $row['name'];
                $row['description'] = $row['description'];
            } else {
                $row['name'] = $row['name_en'] ?? $row['name'];
                $row['description'] = $row['description_en'] ?? $row['description'];
            }
            $row['name'] = unescapeSafe($row['name']);
            $row['image'] = !empty($row['image'])
                ? $site['site_url'] . "files/services/" . $row['image']
                : "";
            $row['url'] = routeUrl('service', $row['slug']);
            $services[] = $row;
        }
    }
}

// Get portfolio
$portfolio = [];
if (moduleEnabled('portfolio')) {
    dbSelect("portfolio", "id, slug, name, name_en, description, description_en, image, url, completion_date, category", "WHERE status = ? ORDER BY id DESC LIMIT 3", ["active"]);
    if ($countrows >= 1) {
        foreach ($rows as $row) {
            if ($row['category'] >= 1) {
                dbSelect("categories", "id, name, name_en", "WHERE id = ? LIMIT 1", [$row['category']]);
                if ($countrows == 1) {
                    if ($_COOKIE['lang'] == "ar") {
                        $row['category'] = $rows[0]['name'];
                    } else {
                        $row['category'] = $rows[0]['name_en'];
                    }
                    $row['category'] = unescapeSafe($row['category']);
                } else {
                    $row['category'] = "";
                }
            }
            if ($_COOKIE['lang'] == "ar") {
                $row['name'] = $row['name'];
                // give 80 characters of description
                $row['description'] = mb_substr($row['description'], 0, 80) . '...';
            } else {
                $row['name'] = $row['name_en'] ?? $row['name'];
                $row['description'] = mb_substr($row['description_en'] ?? $row['description'], 0, 80) . '...';
            }
            $row['name'] = unescapeSafe($row['name']);
            if ($row['slug'] != "" and $row['slug'] != null) {
                $row['slug'] = $row['slug'];
            } else {
                $row['slug'] =  $row['id'];
            }
            $row['image'] = !empty($row['image'])
                ? $site['site_url'] . "files/portfolio/" . $row['image']
                : "";
            $portfolio[] = $row;
        }
    }
}

// Get team members
$team = [];
if (moduleEnabled('team')) {
    dbSelect("team", "id, name, name_en, position, position_en, image, bio, bio_en, facebook, twitter, linkedin, whatsapp, website, phone, instagram, snapchat", "WHERE status = ? ORDER BY ordering ASC, id DESC", ["active"]);
    if ($countrows >= 1) {
        foreach ($rows as $row) {
            if ($_COOKIE['lang'] == "ar") {
                $row['name'] = $row['name'];
                $row['position'] = $row['position'];
                $row['bio'] = $row['bio'];
            } else {
                $row['name'] = $row['name_en'] ?? $row['name'];
                $row['position'] = $row['position_en'] ?? $row['position'];
                $row['bio'] = $row['bio_en'] ?? $row['bio'];
            }
            $row['name'] = unescapeSafe($row['name']);
            $row['position'] = unescapeSafe($row['position']);
            $row['image'] = !empty($row['image'])
                ? $site['site_url'] . "files/team/" . $row['image']
                : $site['site_url'] . "files/images/avatar.png";
            $team[] = $row;
        }
    }
}

// Get testimonials
$testimonials = [];
if (moduleEnabled('testimonials')) {
    dbSelect("testimonials", "id, name, name_en, position, position_en, image, content, content_en, rating", "WHERE status = ? ORDER BY ordering ASC, id DESC", ["active"]);
    if ($countrows >= 1) {
        foreach ($rows as $row) {
            if ($_COOKIE['lang'] == "ar") {
                $row['name'] = $row['name'];
                $row['position'] = $row['position'];
                $row['content'] = $row['content'];
            } else {
                $row['name'] = $row['name_en'] ?? $row['name'];
                $row['position'] = $row['position_en'] ?? $row['position'];
                $row['content'] = $row['content_en'] ?? $row['content'];
            }
            $row['name'] = unescapeSafe($row['name']);
            $row['position'] = unescapeSafe($row['position']);
            if (!empty($row['image']) and $row['image'] != "") {
                $row['image'] = $site['site_url'] . "files/testimonials/" . $row['image'];
            } else {
                $row['image'] = $site['site_url'] . "files/images/avatar.png";
            }

            $testimonials[] = $row;
        }
    }
}

// Get FAQ
$faqs = [];
if (moduleEnabled('faq')) {
    dbSelect("faq", "id, question, question_en, answer, answer_en", "WHERE status = ? ORDER BY ordering ASC, id DESC", ["active"]);
    if ($countrows >= 1) {
        foreach ($rows as $row) {
            if ($_COOKIE['lang'] == "ar") {
                $row['question'] = $row['question'];
                $row['answer'] = $row['answer'];
            } else {
                $row['question'] = $row['question_en'] ?? $row['question'];
                $row['answer'] = $row['answer_en'] ?? $row['answer'];
            }
            $row['question'] = unescapeSafe($row['question']);
            $faqs[] = $row;
        }
    }
}

// Get stats
$stats = [];
if (moduleEnabled('stats')) {
    dbSelect("stats", "id, number, suffix, label, label_en, icon", "WHERE status = ? ORDER BY ordering ASC, id DESC", ["active"]);
    if ($countrows >= 1) {
        foreach ($rows as $row) {
            if ($_COOKIE['lang'] == "ar") {
                $row['label'] = $row['label'];
            } else {
                $row['label'] = $row['label_en'] ?? $row['label'];
            }
            $row['label'] = unescapeSafe($row['label']);
            $stats[] = $row;
        }
    }
}

// Get pricing
$pricing = [];
if (moduleEnabled('pricing')) {
    dbSelect("pricing", "id, name, name_en, price, currency, period, period_en, features, features_en, is_featured", "WHERE status = ? ORDER BY ordering ASC, id DESC", ["active"]);
    if ($countrows >= 1) {
        foreach ($rows as $row) {
            if ($_COOKIE['lang'] == "ar") {
                $row['name'] = $row['name'];
                $row['period'] = $row['period'];
                $row['features'] = array_filter(array_map('trim', explode("\n", (string)$row['features'])));
            } else {
                $row['name'] = $row['name_en'] ?? $row['name'];
                $row['period'] = $row['period_en'] ?? $row['period'];
                $row['features'] = array_filter(array_map('trim', explode("\n", (string)($row['features_en'] ?: $row['features']))));
            }
            $row['name'] = unescapeSafe($row['name']);
            $pricing[] = $row;
        }
    }
}

// Get branches
$branches = [];
if (moduleEnabled('branches')) {
    dbSelect("branches", "id, name, name_en, address, address_en, phone, email, map, working_hours, working_hours_en", "WHERE status = ? ORDER BY ordering ASC, id DESC", ["active"]);
    if ($countrows >= 1) {
        foreach ($rows as $row) {
            if ($_COOKIE['lang'] == "ar") {
                $row['name'] = $row['name'];
                $row['address'] = $row['address'];
                $row['working_hours'] = $row['working_hours'];
            } else {
                $row['name'] = $row['name_en'] ?? $row['name'];
                $row['address'] = $row['address_en'] ?? $row['address'];
                $row['working_hours'] = $row['working_hours_en'] ?? $row['working_hours'];
            }
            $row['name'] = unescapeSafe($row['name']);
            $row['address'] = unescapeSafe($row['address']);
            $row['working_hours'] = unescapeSafe($row['working_hours']);
            $branches[] = $row;
        }
    }
}

// Get certificates
$certificates = [];
if (moduleEnabled('certificates')) {
    dbSelect("certificates", "id, name, name_en, issuer, issuer_en, image, date_issued", "WHERE status = ? ORDER BY ordering ASC, id DESC", ["active"]);
    if ($countrows >= 1) {
        foreach ($rows as $row) {
            if ($_COOKIE['lang'] == "ar") {
                $row['name'] = $row['name'];
                $row['issuer'] = $row['issuer'];
            } else {
                $row['name'] = $row['name_en'] ?? $row['name'];
                $row['issuer'] = $row['issuer_en'] ?? $row['issuer'];
            }
            $row['name'] = unescapeSafe($row['name']);
            $row['issuer'] = unescapeSafe($row['issuer']);
            $row['image'] = $site['site_url'] . "files/certificates/" . $row['image'];
            $certificates[] = $row;
        }
    }
}

// إعلانات الصفحة الرئيسية (وحدة "الإعلانات" — لوحة التحكم ▸ الإضافات).
// getAdsByPosition() تتحقق ذاتياً من تفعيل الوحدة ووجود جدول ads، فآمنة
// الاستدعاء حتى لو لم تُفعَّل الإضافة أو لم يُشغَّل الترحيل بعد.
$ads_home_top = getAdsByPosition('home_top');
$ads_home_between = getAdsByPosition('home_between');

// بيانات وحدات "أُعجوبة" (matches / standings / videos) للصفحة الرئيسية.
// كل دالة تتحقق ذاتياً من تفعيل الوحدة ووجود الجدول، فآمنة الاستدعاء حتى لو
// لم تُفعَّل الإضافة أو لم يُشغَّل الترحيل بعد — راجع includes/functions.php.
$matches = getMatchesForDisplay();
$standings = getStandings();
$videos = getVideos();

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

if ($_COOKIE['lang'] == "ar") {
    $site['name'] = $site['name'];
    $site['description'] = $site['description'];
} else {
    $site['name'] = $site['name_en'];
    $site['description'] = $site['description_en'];
}

$schemaAvailableLanguages = ($site['language_mode'] === 'ar') ? ["ar"] : (($site['language_mode'] === 'en') ? ["en"] : ["ar", "en"]);

$schema = [
    "@context" => "https://schema.org",
    "@type" => businessSchemaType(),
    "name" => $site['name'],
    "url" => $site['site_url'],
    "logo" => $site['site_url'] . "images/logo-color.png",
    "description" => $site['description'],
    "address" => [
        "@type" => "PostalAddress",
        "addressRegion" => $lang['all_over_the_world']
    ],
    "contactPoint" => [
        "@type" => "ContactPoint",
        "telephone" => "+" . $site['whatsapp_number'],
        "contactType" => $lang['technical_support'],
        "areaServed" => "SA,AE,JO,EG,MA,DZ,LB,PS,IS,SY,TN,QA,KW,OM,IQ,BH,YM,LY,SD,MY,TH,TR",
        "availableLanguage" => $schemaAvailableLanguages
    ],
    "sameAs" => [
        "https://facebook.com/" . $site['facebook'],
        "https://instagram.com/" . $site['instagram'],
        "https://x.com/" . $site['twitter']
    ]
];

$schema_utf8 = utf8ize($schema);


// default home page with language
if ($_COOKIE['lang'] == "ar") {
    $home = 'home.twig';
} else {
    $home = 'home_en.twig';
}

echo safeRender($home, [
    'home_sections_order' => getHomeSectionsOrder(),
    'blogs' => $blogs,
    'news_latest' => $news_latest,
    'news_trending' => $news_trending,
    'news_categories' => $news_categories,
    'services' => $services,
    'portfolios' => $portfolio,
    'projects' => $portfolio,
    'team' => $team,
    'testimonials' => $testimonials,
    'faqs' => $faqs,
    'stats' => $stats,
    'pricing' => $pricing,
    'branches' => $branches,
    'certificates' => $certificates,
    'ads_home_top' => $ads_home_top,
    'ads_home_between' => $ads_home_between,
    'matches' => $matches,
    'standings' => $standings,
    'videos' => $videos,
    'schema_json' => json_encode($schema_utf8, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)

    // 'home_page' => html_entity_decode($site['home_page'])

]);
