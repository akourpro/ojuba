<?php
if (!moduleEnabled('blogs')) {
    echo safeRender('404.twig', [
        "error_type"        => "404",
        "error_message"     => $lang['page_not_found'],
        "error_description" => $lang['page_not_found_desc'],
    ]);
    die();
}
if (isset($_GET['slug'])) {
    $slug = safer($_GET['slug']);
    $slug = preg_replace('/[^\p{Arabic}a-zA-Z0-9_-]+/u', '', $slug);
    $slug = strtolower($slug);
    dbSelect("blogs", "*", "WHERE slug = ? OR id = ? AND status = ? LIMIT 1", [$slug, $slug, "active"]);
    if ($countrows == 1) {
        $blog = $rows[0];
        dbUpdate("blogs", "views = ?", [$blog['views'] + 1, $blog['id']], "WHERE id = ? LIMIT 1");

        if ($_COOKIE['lang'] == "ar") {
            $blog['name'] = $blog['name'];
            $blog['description'] = $blog['description'];
            $blog['tags'] = explode(',', $blog['tags']);
        } else {
            $blog['name'] = $blog['name_en'];
            $blog['description'] = $blog['description_en'];
            $blog['tags'] = explode(',', $blog['tags_en']);
        }
        $blog['name'] = unescapeSafe($blog['name']);
        $blog['tags'] = unescapeSafe($blog['tags']);
        $blog['reading_time'] = readingTimeMinutes($blog['description']);
        $blog['url'] = routeUrl('blog', $blog['slug']);
        $blog['image'] = $site['site_url'] . "files/blogs/" . $blog['image'];

        $categoryId = (int) ($blog['category'] ?? 0);

        if (moduleEnabled('blog_categories') && $blog['category'] >= 1) {
            dbSelect("blog_categories", "*", "WHERE id = ? LIMIT 1", [$blog['category']]);
            if ($countrows == 1) {
                $blog['category'] = unescapeSafe(($_COOKIE['lang'] == "ar") ? $rows[0]['name'] : $rows[0]['name_en']);
            } else {
                $blog['category'] = "";
            }
        } else {
            $blog['category'] = "";
        }

        // مقالات ذات صلة (نفس التصنيف أولاً ثم الأحدث) — روابط داخلية بين المقالات
        // لتحسين عمق الزحف وفهرسة جوجل لبقية المدونة بدل الاعتماد فقط على صفحة الأرشيف المُقسّمة صفحات
        $related = [];
        $relatedIds = [(int) $blog['id']];
        if ($categoryId >= 1) {
            dbSelect(
                "blogs",
                "id, slug, name, name_en, image",
                "WHERE status = ? AND category = ? AND id != ? ORDER BY id DESC LIMIT 3",
                ["active", $categoryId, $blog['id']]
            );
            if ($countrows >= 1) {
                foreach ($rows as $row) {
                    $related[] = $row;
                    $relatedIds[] = (int) $row['id'];
                }
            }
        }
        if (count($related) < 3) {
            $need = 3 - count($related);
            $placeholders = implode(',', array_fill(0, count($relatedIds), '?'));
            dbSelect(
                "blogs",
                "id, slug, name, name_en, image",
                "WHERE status = ? AND id NOT IN ($placeholders) ORDER BY id DESC LIMIT " . (int) $need,
                array_merge(["active"], $relatedIds)
            );
            if ($countrows >= 1) {
                foreach ($rows as $row) {
                    $related[] = $row;
                }
            }
        }
        foreach ($related as &$rp) {
            $rp['name'] = unescapeSafe(($_COOKIE['lang'] == "ar") ? $rp['name'] : ($rp['name_en'] ?? $rp['name']));
            $rp['image'] = !empty($rp['image']) ? $site['site_url'] . "files/blogs/" . $rp['image'] : "";
            $rp['url'] = routeUrl('blog', $rp['slug']);
        }
        unset($rp);

        $date_published = date('Y-m-d\TH:i:sP', strtotime($blog['date']));
        if (!empty($blog['last_update'])) {
            $date_modified = date('Y-m-d\TH:i:sP', strtotime($blog['last_update']));
        } else {
            $date_modified = $date_published;
        }

        $blog['last_update'] = ago($blog['last_update']);

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
        $schema = [
            "@context" => "https://schema.org",
            "@type" => "BlogPosting",
            "headline" => $blog['name'],
            "description" => str_replace(["\r\n", "\n", "\r", "    ", "'", '"'], '', generateShortDescription($blog['description'])),
            "articleBody" => strip_tags($blog['description']),
            "image" => [
                "@type" => "ImageObject",
                "url" => $blog['image'],
                "width" => 1200, // غيّر القيم بحسب أبعاد الصورة الحقيقية
                "height" => 630
            ],
            "author" => [
                "@type" => "Person",
                "name" => $lang['mohammad_akour'],
                "url" => routeUrl('blog', $blog['slug']),
            ],
            "publisher" => [
                "@type" => businessSchemaType(),
                "name" => $site['name'],
                "logo" => [
                    "@type" => "ImageObject",
                    "url" => $site['site_url'] . "images/logo.png"
                ]
            ],
            "datePublished" => $date_published,
            "dateModified" => $date_modified,
            "url" => routeUrl('blog', $blog['slug']),
            "mainEntityOfPage" => [
                "@type" => "WebPage",
                "@id" => routeUrl('blog', $blog['slug']),
            ]
        ];

        if (!empty($blog['category'])) {
            $schema['articleSection'] = $blog['category'];
        }
        if (!empty($blog['tags']) && is_array($blog['tags']) && !empty($blog['tags'][0])) {
            $schema['keywords'] = implode(', ', array_map('trim', $blog['tags']));
        }

        $schema_utf8 = utf8ize($schema);




        // إعلانات صفحة المقال (الشريط الجانبي + داخل نص المقال) — راجع
        // getAdsByPosition() بـ includes/functions.php لآلية التحقق من الوحدة
        $ads_sidebar = getAdsByPosition('blog_sidebar');
        $ads_article = getAdsByPosition('article_inline');

        // المباراة المرتبطة بالمقال (اختياري، عمود blogs.related_match_id) — لعرض
        // صندوق ملخص المباراة أعلى صفحة المقال بالقوالب الرياضية. getMatchById()
        // تتحقق ذاتياً من تفعيل وحدة matches ووجود جدولها، فآمنة الاستدعاء دائماً.
        $related_match = null;
        if (blogsRelatedMatchColumnExists() && !empty($blog['related_match_id'])) {
            $related_match = getMatchById($blog['related_match_id']);
        }

        // Render the blog view
        echo safeRender('blogs/view.twig', [
            'blog' => $blog,
            'related' => $related,
            'related_match' => $related_match,
            'ads_sidebar' => $ads_sidebar,
            'ads_article' => $ads_article,
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
