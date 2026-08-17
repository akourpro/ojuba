<?php
if (!moduleEnabled('services')) {
    echo safeRender('404.twig', [
        "error_type"        => "404",
        "error_message"     => $lang['page_not_found'],
        "error_description" => $lang['page_not_found_desc'],
    ]);
    die();
}
if (isset($_GET['slug'])) {
    $slug = safer($_GET['slug']);
    // السماح بالأحرف العربية واللاتينية والأرقام والشرطتين فقط
    $slug = preg_replace('/[^\p{Arabic}a-zA-Z0-9_-]+/u', '', $slug);
    $slug = strtolower($slug);

    // التأكد من أن الحالة "active" تنطبق على جميع الاحتمالات باستخدام الأقواس
    dbSelect("services", "*", "WHERE (slug = ? OR id = ?) AND status = ? LIMIT 1", [$slug, $slug, "active"]);

    if ($countrows == 1) {
        $service = $rows[0];

        // اختيار اللغة المناسبة للاسم والوصف
        if ($_COOKIE['lang'] == "ar") {
            $service['name'] = $service['name'];
            $service['description'] = $service['description'];
        } else {
            $service['name'] = $service['name_en'];
            $service['description'] = $service['description_en'];
        }

        $service['url'] = routeUrl('service', $service['slug']);
        $service['image'] = !empty($service['image'])
            ? $site['site_url'] . "files/services/" . $service['image']
            : "";

        // وظيفة لتحويل النصوص إلى UTF‑8
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

        $shortDescription = generateShortDescription($service['description']);
        $shortDescription = mb_substr($shortDescription, 0, 160);


        $schema = [
            "@context" => "https://schema.org",
            "@type"    => "Service",
            "name"     => $service['name'],
            "serviceType" => $service['name'],   // يمكن إضافة نوع الخدمة هنا
            "description" => str_replace(["\r\n", "\n", "\r", "    ", "'", '"'], '', $shortDescription),
            "articleBody" => strip_tags($service['description']),
            "image" => [
                "@type"  => "ImageObject",
                "url"    => $service['image'],
                "width"  => 1200,
                "height" => 630
            ],
            "author" => [
                "@type" => businessSchemaType(),     // نوع الكيان المناسب حسب "نوع الموقع" بإعدادات الموقع
                "name" => $site['name'],
                "url"  => $service['url'],
            ],
            "publisher" => [
                "@type" => businessSchemaType(),
                "name" => $site['name'],
                "logo" => [
                    "@type" => "ImageObject",
                    "url"   => $site['site_url'] . "images/logo.png"
                ]
            ],
            "url" => $service['url'],
            "mainEntityOfPage" => [
                "@type" => "WebPage",
                "@id"   => routeUrl('service', $service['id']),
            ]
        ];

        // ضمان ترميز UTF‑8 في المخطط
        $schema_utf8 = utf8ize($schema);

        dbSelect("services", "id, slug, name, name_en", "WHERE status = ? AND id != ? ORDER BY id DESC LIMIT 5", ["active", $service['id']]);
        $other_services = [];
        foreach ($rows as $row) {
            if ($_COOKIE['lang'] == "ar") {
                $row['name'] = $row['name'];
            } else {
                $row['name'] = $row['name_en'];
            }
            $row['slug'] = routeUrl('service', $row['slug']);
            $other_services[] = $row;
        }

        // تقديم الصفحة مع تمرير المخطط كـ JSON
        echo safeRender('services/view.twig', [
            'service'     => $service,
            'other_services' => $other_services,
            'schema_json' => json_encode($schema_utf8, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        ]);
    } else {
        echo safeRender('404.twig', [
            "error_type"        => "404",
            "error_message"     => $lang['page_not_found'],
            "error_description" => $lang['page_not_found_desc'],
        ]);
        die();
    }
} else {
    echo safeRender('404.twig', [
        "error_type"        => "404",
        "error_message"     => $lang['page_not_found'],
        "error_description" => $lang['page_not_found_desc'],
    ]);
    die();
}
