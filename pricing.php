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
// إعدادات صفحة الأسعار المستقلة (تفعيل/إيقاف + عنوان ووصف الهيدر) — قابلة للتعديل
// من لوحة التحكم عبر "باقات الأسعار". محاطة بـ try/catch لأن الجدول قد لا يكون
// موجوداً بعد إن لم تُشغَّل صفحة الترحيل abma/pricing/migrate بعد.
$pricingPageEnabled = true;
// pricingPage: العنوان الرئيسي (H1) والوصف الظاهران فوق بطاقات الأسعار في الصفحة نفسها
$pricingPage = [
    "title" => "أسعار خطط WorkUp",
    "title_en" => "WorkUp Pricing Plans",
    "description" => "تسعير شفاف وواضح، شهرياً لكل موظف فعّال في النظام — بدون رسوم مخفية. اختر الباقة المناسبة لحجم فريقكم، أو تواصل معنا لعرض سعر مخصص للمؤسسات الكبيرة.",
    "description_en" => "Transparent and straightforward pricing, billed monthly per active employee in the system — with no hidden fees. Choose the plan that fits your team size, or contact us for a custom quote for large organizations.",
];
// pricingSeo: عنوان ووصف الصفحة (SEO) — منفصل عن عنوان الهيدر أعلاه، يُستخدم فقط في
// وسم <title> وميتا description وOpen Graph (لا يظهر على الصفحة نفسها للزائر)
$pricingSeo = [
    "title" => "أسعار نظام الموارد البشرية والحضور",
    "title_en" => "HR System Pricing Plans",
    "description" => "أسعار نظام WorkUp لإدارة القوى العاملة بالريال السعودي (SAR)، شهرياً لكل موظف. قارن بين الباقات، واطلب عرض سعر مخصص.",
    "description_en" => "WorkUp workforce management system pricing in Saudi Riyal (SAR), billed monthly per employee. Compare the plans and request a custom quote.",
];
try {
    dbSelect("pricing_page_settings", "*", "LIMIT 1");
    if ($countrows === 1) {
        $pricingPageEnabled = (bool) $rows[0]['enabled'];
        if (!empty($rows[0]['title'])) {
            $pricingPage['title'] = $rows[0]['title'];
        }
        if (!empty($rows[0]['title_en'])) {
            $pricingPage['title_en'] = $rows[0]['title_en'];
        }
        if (!empty($rows[0]['description'])) {
            $pricingPage['description'] = $rows[0]['description'];
        }
        if (!empty($rows[0]['description_en'])) {
            $pricingPage['description_en'] = $rows[0]['description_en'];
        }
        if (!empty($rows[0]['seo_title'])) {
            $pricingSeo['title'] = $rows[0]['seo_title'];
        }
        if (!empty($rows[0]['seo_title_en'])) {
            $pricingSeo['title_en'] = $rows[0]['seo_title_en'];
        }
        if (!empty($rows[0]['seo_description'])) {
            $pricingSeo['description'] = $rows[0]['seo_description'];
        }
        if (!empty($rows[0]['seo_description_en'])) {
            $pricingSeo['description_en'] = $rows[0]['seo_description_en'];
        }
    }
} catch (Exception $e) {
    // الجدول غير موجود بعد — استخدم القيم الافتراضية أعلاه
}

if (!moduleEnabled('pricing') || !$pricingPageEnabled) {
    echo safeRender('404.twig', [
        "error_type"        => "404",
        "error_message"     => $lang['page_not_found'],
        "error_description" => $lang['page_not_found_desc'],
    ]);
    die();
}

// Get pricing plans (نفس منطق الرئيسية)
$pricing = [];
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

function utf8ize_pricing($mixed)
{
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = utf8ize_pricing($value);
        }
    } elseif (is_string($mixed)) {
        $mixed = mb_convert_encoding($mixed, 'UTF-8', 'UTF-8');
    }
    return $mixed;
}

// Schema.org: Product + Offer لكل باقة لها سعر رقمي فعلي (بالريال السعودي)
// الباقات ذات السعر المخصص (مثل "المؤسسات" التي تعتمد على عرض سعر يدوي) تُستثنى من offers
// عمداً بدل وضع "price" غير حقيقي، لأن جوجل يعتبر ذلك بيانات مضلّلة قابلة لعقوبة يدوية.
$offers = [];
foreach ($pricing as $plan) {
    if (!is_numeric($plan['price'])) {
        continue;
    }
    $offers[] = [
        "@type" => "Offer",
        "name" => $plan['name'],
        "price" => (string) $plan['price'],
        "priceCurrency" => "SAR",
        "priceSpecification" => [
            "@type" => "UnitPriceSpecification",
            "price" => (string) $plan['price'],
            "priceCurrency" => "SAR",
            "unitText" => $plan['period'],
        ],
        "url" => $site['site_url'] . "pricing",
        "availability" => "https://schema.org/InStock",
        "category" => $plan['name'],
    ];
}

// استخدمنا "SoftwareApplication" بدل "Product": فـ Product مخصّص أساساً لسلع تجارية
// (ماركت/شوبينغ) وتتوقع حقولاً مثل سياسة الإرجاع وتفاصيل الشحن — لا معنى لها لبرنامج/اشتراك
// SaaS. هذا يزيل تحذيرات hasMerchantReturnPolicy و shippingDetails لأنها لم تعد مناسبة أصلاً
// بدل محاولة تعبئتها ببيانات وهمية.
$schema = [
    "@context" => "https://schema.org",
    "@type" => "SoftwareApplication",
    "name" => "WorkUp",
    "applicationCategory" => "BusinessApplication",
    "operatingSystem" => "Web",
    "description" => ($_COOKIE['lang'] == "ar")
        ? "منظومة WorkUp لإدارة القوى العاملة، بأسعار بالريال السعودي حسب عدد الموظفين."
        : "WorkUp workforce management platform, priced in Saudi Riyals (SAR) per employee.",
    "image" => $site['site_url'] . "files/images/banner" . (($_COOKIE['lang'] == "ar") ? "" : "-en") . ".png",
    "url" => $site['site_url'] . "pricing",
    "brand" => [
        "@type" => "Brand",
        "name" => "WorkUp"
    ],
    "offers" => $offers
];

$schema_utf8 = utf8ize_pricing($schema);

// FAQ خاص بصفحة الأسعار + Schema FAQPage (لفرصة ظهور Rich Results)
// قابلة للتعديل من لوحة التحكم عبر "باقات الأسعار" > "الأسئلة الشائعة لصفحة الأسعار"
if ($_COOKIE['lang'] == "ar") {
    $pricingFaqs = [
        ["q" => "هل السعر شهري أم سنوي؟", "a" => "التسعير الظاهر أعلاه شهري لكل موظف فعّال في النظام. لخيارات الفوترة السنوية أو أي ترتيب مخصص، تواصلوا معنا مباشرة."],
        ["q" => "هل يمكن تغيير الباقة لاحقاً؟", "a" => "نعم، يمكن الترقية أو تعديل الباقة حسب نمو عدد موظفيكم واحتياج فريقكم، بالتنسيق مع فريق الدعم."],
        ["q" => "هل يوجد فرق بين التثبيت على سيرفركم أو سيرفرنا؟", "a" => "نعم، WorkUp يدعم التثبيت على سيرفر العميل الخاص لضمان سيطرة كاملة على البيانات، أو الاستضافة لدينا — التفاصيل والتسعير حسب الخيار عند التواصل."],
        ["q" => "هل هناك حد أدنى لعدد الموظفين؟", "a" => "الباقة الأساسية مناسبة للفرق الصغيرة حتى 10 موظفين، وتوجد باقات أكبر للشركات المتوسطة والكبيرة حسب الحاجة."],
        ["q" => "كيف أعرف السعر الدقيق لشركتي؟", "a" => "التسعير النهائي يعتمد على عدد الموظفين وعدد الفروع والخيارات المطلوبة. راسلونا عبر نموذج التواصل وسنرسل عرض سعر مفصّل."],
    ];
} else {
    $pricingFaqs = [
        ["q" => "Is the price monthly or annual?", "a" => "The prices shown above are billed monthly per active employee. For annual billing or a custom arrangement, please contact us directly."],
        ["q" => "Can I change my plan later?", "a" => "Yes, you can upgrade or adjust your plan as your team grows, in coordination with our support team."],
        ["q" => "Is there a difference between hosting on your server or ours?", "a" => "Yes, WorkUp supports installation on your own server for full data control, or hosting with us — pricing details depend on the option you choose when you contact us."],
        ["q" => "Is there a minimum number of employees?", "a" => "The Basic plan suits small teams of up to 10 employees, with larger plans available for medium and large companies as needed."],
        ["q" => "How do I get an exact price for my company?", "a" => "The final price depends on employee count, branch count, and the options you need. Contact us for a detailed quote."],
    ];
}
try {
    dbSelect("pricing_faqs", "question, question_en, answer, answer_en", "WHERE status = ? ORDER BY ordering ASC, id ASC", ["active"]);
    if ($countrows >= 1) {
        $dbFaqs = [];
        foreach ($rows as $r) {
            if ($_COOKIE['lang'] == "ar") {
                $dbFaqs[] = ["q" => $r['question'], "a" => $r['answer']];
            } else {
                $dbFaqs[] = ["q" => $r['question_en'] ?: $r['question'], "a" => $r['answer_en'] ?: $r['answer']];
            }
        }
        $pricingFaqs = $dbFaqs;
    }
} catch (Exception $e) {
    // الجدول غير موجود بعد — استخدم الأسئلة الافتراضية أعلاه
}

// إعدادات + صفوف جدول المقارنة — قابلة للتعديل من لوحة التحكم عبر "باقات الأسعار" > "جدول مقارنة الباقات"
// الأعمدة نفسها ديناميكية بالكامل: تُبنى مباشرة من مصفوفة $pricing أعلاه (نفس باقات الأسعار
// الفعّالة، بنفس ترتيبها وأسمائها المترجمة) بدل أسماء أعمدة ثابتة، بحيث لو أُضيفت أو حُذفت
// باقة، أو تغيّر اسمها، ينعكس تلقائياً على عناوين جدول المقارنة بدون أي تعديل يدوي.
$pricingCompare = [
    "eyebrow" => "مقارنة تفصيلية",
    "eyebrow_en" => "Detailed Comparison",
    "title" => "أي باقة تناسب فريقكم؟",
    "title_en" => "Which plan is right for your team?",
    "description" => "مقارنة سريعة لأبرز الفروقات بين الباقات.",
    "description_en" => "A quick comparison of the key differences between the plans.",
];
try {
    dbSelect("pricing_compare_settings", "*", "LIMIT 1");
    if ($countrows === 1) {
        foreach ($rows[0] as $k => $v) {
            if ($k !== 'id' && !empty($v)) {
                $pricingCompare[$k] = $v;
            }
        }
    }
} catch (Exception $e) {
    // الجدول غير موجود بعد — استخدم القيم الافتراضية أعلاه
}

// صفوف افتراضية (تُستخدم فقط إن لم تكن جداول قاعدة البيانات جاهزة بعد أو فارغة)،
// معبّأة بقيم افتراضية بحسب ترتيب الباقات الحالية بغض النظر عن عددها الفعلي.
$defaultCompareFeatures = [
    ["ar" => "عدد الفروع", "en" => "Number of branches", "vals" => ["فرع واحد", "حتى 5 فروع", "غير محدود"], "vals_en" => ["1 branch", "Up to 5 branches", "Unlimited"]],
    ["ar" => "عدد الموظفين", "en" => "Number of employees", "vals" => ["10", "+10", "غير محدود"], "vals_en" => ["10", "10+", "Unlimited"]],
    ["ar" => "صلاحيات دقيقة لكل موظف ووحدة", "en" => "Granular permissions for each employee and unit", "vals" => ["✓", "✓", "✓"], "vals_en" => ["✓", "✓", "✓"]],
    ["ar" => "تفويض لوحة مصغّرة لقادة الفرق", "en" => "Delegate a mini dashboard to team leaders", "vals" => ["✓", "✓", "✓"], "vals_en" => ["✓", "✓", "✓"]],
    ["ar" => "تخصيص تصميم التقارير وملفات PDF", "en" => "Custom report and PDF design", "vals" => ["✓", "✓", "✓"], "vals_en" => ["✓", "✓", "✓"]],
    ["ar" => "سجل تدقيق كامل لكل إجراء", "en" => "Complete audit log for every action", "vals" => ["✓", "✓", "✓"], "vals_en" => ["✓", "✓", "✓"]],
    ["ar" => "قاعدة معرفة داخلية قابلة للبحث", "en" => "Searchable internal knowledge base", "vals" => ["✓", "✓", "✓"], "vals_en" => ["✓", "✓", "✓"]],
    ["ar" => "تثبيت كامل على سيرفركم الخاص", "en" => "Full installation on your own server", "vals" => ["✕", "✓", "✓"], "vals_en" => ["✕", "✓", "✓"]],
    ["ar" => "مستوى الدعم", "en" => "Support level", "vals" => ["خلال ايام العمل", "خلال ايام العمل", "دعم ذو أولوية"], "vals_en" => ["During business days", "During business days", "Priority support"]],
    ["ar" => "استملاك النظام", "en" => "System ownership", "vals" => ["✕", "✕", "✓"], "vals_en" => ["✕", "✕", "✓"]],
];
$pricingCompareRows = [];
foreach ($defaultCompareFeatures as $f) {
    $rowValues = [];
    foreach ($pricing as $i => $plan) {
        if ($_COOKIE['lang'] == "ar") {
            $rowValues[$plan['id']] = $f['vals'][$i] ?? '';
        } else {
            $rowValues[$plan['id']] = $f['vals_en'][$i] ?? '';
        }
    }
    $pricingCompareRows[] = [
        "feature" => ($_COOKIE['lang'] == "ar") ? $f['ar'] : $f['en'],
        "values" => $rowValues,
    ];
}

// الصفوف والقيم الفعلية من قاعدة البيانات — تحل محل الافتراضية أعلاه إن وُجدت
try {
    dbSelect("pricing_compare_features", "id, feature, feature_en", "WHERE status = ? ORDER BY ordering ASC, id ASC", ["active"]);
    if ($countrows >= 1) {
        $featureRows = $rows;
        $featureIds = array_column($featureRows, 'id');
        $valueMap = []; // [feature_id][plan_id] = القيمة الظاهرة بلغة الزائر الحالية
        if (!empty($featureIds)) {
            $placeholders = implode(',', array_fill(0, count($featureIds), '?'));
            dbSelect("pricing_compare_values", "feature_id, plan_id, value, value_en", "WHERE feature_id IN ($placeholders)", $featureIds);
            if ($countrows >= 1) {
                foreach ($rows as $v) {
                    $valueMap[$v['feature_id']][$v['plan_id']] = ($_COOKIE['lang'] == "ar") ? $v['value'] : ($v['value_en'] ?: $v['value']);
                }
            }
        }
        $dbCompareRows = [];
        foreach ($featureRows as $f) {
            $rowValues = [];
            foreach ($pricing as $plan) {
                $rowValues[$plan['id']] = $valueMap[$f['id']][$plan['id']] ?? '';
            }
            $dbCompareRows[] = [
                "feature" => ($_COOKIE['lang'] == "ar") ? $f['feature'] : ($f['feature_en'] ?: $f['feature']),
                "values" => $rowValues,
            ];
        }
        $pricingCompareRows = $dbCompareRows;
    }
} catch (Exception $e) {
    // الجداول غير موجودة بعد — استخدم الصفوف الافتراضية أعلاه
}

$faqSchema = [
    "@context" => "https://schema.org",
    "@type" => "FAQPage",
    "mainEntity" => array_map(function ($item) {
        return [
            "@type" => "Question",
            "name" => $item['q'],
            "acceptedAnswer" => [
                "@type" => "Answer",
                "text" => $item['a'],
            ]
        ];
    }, $pricingFaqs)
];
$faqSchema_utf8 = utf8ize_pricing($faqSchema);

$combined_schema = json_encode($schema_utf8, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
    . "\n</script>\n<script type=\"application/ld+json\">\n"
    . json_encode($faqSchema_utf8, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

// default pricing page with language
if ($_COOKIE['lang'] == "ar") {
    $pricingTemplate = 'pricing.twig';
} else {
    $pricingTemplate = 'pricing_en.twig';
}

echo safeRender($pricingTemplate, [
    'pricing' => $pricing,
    'pricing_faqs' => $pricingFaqs,
    'pricingPage' => $pricingPage,
    'pricingSeo' => $pricingSeo,
    'pricingCompare' => $pricingCompare,
    'pricingCompareRows' => $pricingCompareRows,
    'schema_json' => $combined_schema,
]);
