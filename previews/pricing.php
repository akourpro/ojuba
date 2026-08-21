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
header('X-Robots-Tag: noindex, nofollow', true);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true);

if (isset($_POST) and !empty($_POST)) {

    $csrf->verify();

    if (!login_check_admin()) {
        echo 'غير مسموح لك بالدخول الى هذه الصفحة';
        die();
    }

    // العنوان الرئيسي (الهيدر) وعنوان/وصف السيو — من القيم غير المحفوظة في النموذج،
    // بنفس أسماء الحقول المستخدمة في نموذج "باقات الأسعار" بلوحة التحكم
    $pricingPage = [
        "title"        => safer($_POST['pp_title'] ?? '') ?: "أسعار خطط WorkUp",
        "title_en"     => safer($_POST['pp_title_en'] ?? '') ?: "WorkUp Pricing Plans",
        "description"  => $_POST['pp_description'] ?? '',
        "description_en" => $_POST['pp_description_en'] ?? '',
    ];
    $pricingSeo = [
        "title"        => safer($_POST['pp_seo_title'] ?? '') ?: "أسعار نظام الموارد البشرية والحضور",
        "title_en"     => safer($_POST['pp_seo_title_en'] ?? '') ?: "HR System Pricing Plans",
        "description"  => $_POST['pp_seo_description'] ?? '',
        "description_en" => $_POST['pp_seo_description_en'] ?? '',
    ];

    // باقات الأسعار الفعّالة الحالية (كما ستظهر فعلياً على الصفحة الحية)
    $pricing = [];
    dbSelect("pricing", "id, name, name_en, price, currency, period, period_en, features, features_en, is_featured", "WHERE status = ? ORDER BY ordering ASC, id DESC", ["active"]);
    if ($countrows >= 1) {
        foreach ($rows as $row) {
            if (($_COOKIE['lang'] ?? 'en') == "ar") {
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

    // الأسئلة الشائعة الحالية لصفحة الأسعار (من قاعدة البيانات إن وُجدت)
    $pricingFaqs = [];
    try {
        dbSelect("pricing_faqs", "question, question_en, answer, answer_en", "WHERE status = ? ORDER BY ordering ASC, id ASC", ["active"]);
        if ($countrows >= 1) {
            foreach ($rows as $r) {
                if (($_COOKIE['lang'] ?? 'en') == "ar") {
                    $pricingFaqs[] = ["q" => $r['question'], "a" => $r['answer']];
                } else {
                    $pricingFaqs[] = ["q" => $r['question_en'] ?: $r['question'], "a" => $r['answer_en'] ?: $r['answer']];
                }
            }
        }
    } catch (Exception $e) {
        // الجدول غير موجود بعد
    }

    // إعدادات + صفوف جدول المقارنة الحالية (من قاعدة البيانات إن وُجدت)
    $pricingCompare = [
        "eyebrow" => "مقارنة تفصيلية", "eyebrow_en" => "Detailed Comparison",
        "title" => "أي باقة تناسب فريقكم؟", "title_en" => "Which plan is right for your team?",
        "description" => "مقارنة سريعة لأبرز الفروقات بين الباقات.", "description_en" => "A quick comparison of the key differences between the plans.",
    ];
    $pricingCompareRows = [];
    try {
        dbSelect("pricing_compare_settings", "*", "LIMIT 1");
        if ($countrows === 1) {
            foreach ($rows[0] as $k => $v) {
                if ($k !== 'id' && !empty($v)) {
                    $pricingCompare[$k] = $v;
                }
            }
        }
        dbSelect("pricing_compare_features", "id, feature, feature_en", "WHERE status = ? ORDER BY ordering ASC, id ASC", ["active"]);
        if ($countrows >= 1) {
            $featureRows = $rows;
            $featureIds = array_column($featureRows, 'id');
            $valueMap = [];
            if (!empty($featureIds)) {
                $placeholders = implode(',', array_fill(0, count($featureIds), '?'));
                dbSelect("pricing_compare_values", "feature_id, plan_id, value, value_en", "WHERE feature_id IN ($placeholders)", $featureIds);
                if ($countrows >= 1) {
                    foreach ($rows as $v) {
                        $valueMap[$v['feature_id']][$v['plan_id']] = (($_COOKIE['lang'] ?? 'en') == "ar") ? $v['value'] : ($v['value_en'] ?: $v['value']);
                    }
                }
            }
            foreach ($featureRows as $f) {
                $rowValues = [];
                foreach ($pricing as $plan) {
                    $rowValues[$plan['id']] = $valueMap[$f['id']][$plan['id']] ?? '';
                }
                $pricingCompareRows[] = [
                    "feature" => (($_COOKIE['lang'] ?? 'en') == "ar") ? $f['feature'] : ($f['feature_en'] ?: $f['feature']),
                    "values" => $rowValues,
                ];
            }
        }
    } catch (Exception $e) {
        // الجداول غير موجودة بعد
    }

    $pricingTemplate = (($_COOKIE['lang'] ?? 'en') == "ar") ? 'pricing.twig' : 'pricing_en.twig';

    $html = safeRender($pricingTemplate, [
        'pricing'            => $pricing,
        'pricing_faqs'       => $pricingFaqs,
        'pricingPage'        => $pricingPage,
        'pricingSeo'         => $pricingSeo,
        'pricingCompare'     => $pricingCompare,
        'pricingCompareRows' => $pricingCompareRows,
    ]);

    // شريط تنبيه ثابت أعلى الصفحة يوضّح أن هذه معاينة غير محفوظة بعد
    $bannerText = (($_COOKIE['lang'] ?? 'en') == "ar")
        ? '⚡ هذه معاينة فقط — التعديلات لم تُحفظ بعد. أغلق هذه النافذة واضغط "حفظ" في لوحة التحكم لتثبيتها.'
        : '⚡ Preview only — changes are not saved yet. Close this tab and click "Save" in the dashboard to publish them.';
    $banner = '<div style="position:fixed;top:0;left:0;right:0;z-index:99999;background:#111827;color:#fff;text-align:center;padding:10px 14px;font:600 14px/1.5 sans-serif;box-shadow:0 2px 10px rgba(0,0,0,.2);">' . htmlspecialchars($bannerText, ENT_QUOTES, 'UTF-8') . '</div><div style="height:44px;"></div>';
    $html = preg_replace('/<body([^>]*)>/i', '<body$1>' . $banner, $html, 1);

    echo $html;
} else {
    echo safeRender('404.twig', [
        "error_type" => "404",
        "error_message" => $lang['page_not_found'],
        "error_description" => $lang['page_not_found_desc'],
    ]);
    die();
}
