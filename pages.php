<?php
// 

if (isset($_GET['slug'])) {
    $slug = safer($_GET['slug']);
    $slug = preg_replace('/[^\p{Arabic}a-zA-Z0-9_-]+/u', '', $slug);
    $slug = strtolower($slug);
} else {
    echo safeRender('404.twig', [
        "error_type" => "404",
        "error_message" => $lang['page_not_found'],
        "error_description" => $lang['page_not_found_desc'],
    ]);
    die();
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



dbSelect("pages", "*", "WHERE slug = ? AND status = ? LIMIT 1", [$slug, 'active']);
if ($countrows == 1) {
    $page = $rows[0];
    if (@$_COOKIE['lang'] == "ar") {
        $page['name'] = $page['name'];
        $page['description'] = $page['description'];
    } else {
        $page['name'] = $page['name_en'];
        $page['description'] = $page['description_en'];
    }
    $page['name'] = unescapeSafe($page['name']);

    $page['ago'] = ago($page['date'], true);

    $schema = [
        "@context" => "https://schema.org",
        "@type" => "WebPage",
        "name" => $page['name'],
        "url" => routeUrl('page', $page['slug']),
        "description" => str_replace(["\r\n", "\n", "\r", "    ", "'", '"'], '', strip_tags($page['description'])),
        "inLanguage" => $lang['lang_code'],
        "isPartOf" => [
            "@type" => "WebSite",
            "name" => $site['name'],
            "url" => $site['site_url']
        ]
    ];

    $schema_utf8 = utf8ize($schema);

    // Get all pages
    dbSelect("pages", "slug, name, name_en", "WHERE status = ? ORDER BY id DESC", ['active']);
    $pages = [];
    if ($countrows > 0) {
        foreach ($rows as $row) {
            if (@$_COOKIE['lang'] == "ar") {
                $row['name'] = $row['name'];
            } else {
                $row['name'] = $row['name_en'];
            }
            $row['name'] = unescapeSafe($row['name']);
            $pages[] = $row;
        }
    }

    echo safeRender('pages.twig', [
        'no_header' => true,
        'page' => $page,
        'schema_json' => json_encode($schema_utf8, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        'pages' => $pages
    ]);
} else {
    echo safeRender('404.twig', [
        "error_type" => "404",
        "error_message" => $lang['page_not_found'],
        "error_description" => $lang['page_not_found_desc'],
    ]);
    die();
}
