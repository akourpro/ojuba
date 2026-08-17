<?php
requireOwner();
$addonsMap = [
    "pages"           => ["الصفحات", "mdi-book-open-page-variant", "صفحات نصية عامة مثل سياسة الخصوصية والشروط والأحكام"],
    "blogs"           => ["المقالات", "mdi-post", "المدونة/المقالات الإخبارية"],
    "blog_categories" => ["تصنيفات المقالات", "mdi-shape-outline", "تصنيف المقالات إلى أقسام"],
    "services"        => ["الخدمات", "mdi-account-wrench", "قائمة خدمات النشاط"],
    "portfolio"       => ["معرض الأعمال", "mdi-network-pos", "معرض المشاريع والأعمال السابقة"],
    "categories"      => ["فئات معرض الأعمال", "mdi-tag-multiple-outline", "تصنيف عناصر معرض الأعمال"],
    "team"            => ["فريق العمل", "mdi-account-group", "عرض أعضاء فريق العمل"],
    "testimonials"    => ["آراء العملاء", "mdi-comment-quote", "شهادات وتقييمات العملاء"],
    "faq"             => ["الأسئلة الشائعة", "mdi-help-circle-outline", "قسم الأسئلة المتكررة"],
    "stats"           => ["الإحصائيات", "mdi-chart-box-outline", "أرقام وإحصائيات النشاط"],
    "pricing"         => ["باقات الأسعار", "mdi-tag-outline", "خطط وباقات الاشتراك"],
    "branches"        => ["الفروع", "mdi-map-marker-outline", "فروع النشاط ومواقعها"],
    "certificates"    => ["الشهادات والجوائز", "mdi-certificate-outline", "الشهادات والجوائز والاعتمادات"],
    "clients"         => ["العملاء", "mdi-domain", "شعارات/قائمة عملاء النشاط"],
    "mailing"         => ["مراسلة البريد", "mdi-email-fast-outline", "قوائم بريدية وحملات تسويقية عبر الإيميل"],
    "ads"             => ["الإعلانات", "mdi-bullhorn-outline", "إعلانات نصية أو صورية أو أكواد إعلانية (مثل Google AdSense) بمواضع محددة بالموقع"],
    "matches"         => ["جدول المباريات", "mdi-soccer", "مباريات قادمة/مباشرة/منتهية مع النتيجة والقناة الناقلة"],
    "standings"       => ["جدول الترتيب", "mdi-podium-gold", "جدول ترتيب فرق الدوري بالنقاط وفارق الأهداف"],
    "videos"          => ["الفيديوهات", "mdi-youtube", "مقاطع فيديو وملخصات مرئية مضمّنة من YouTube"],
    "feeds"           => ["سحب المقالات (RSS)", "mdi-rss", "سحب مقالات تلقائياً من مواقع أخرى عبر RSS/Feed وإضافتها كمقالات جديدة بتصنيف محدَّد، مع استبدال كلمات تلقائياً"],
];

$manifest = themeManifest();
$activeModules = $manifest["modules"] ?? [];
$themeName = $site['theme'] ?? '';
?>
<title>الإضافات</title>
<h4 class="py-3 mb-1"><span class="text-muted fw-light">الإعدادات /</span> الإضافات</h4>
<p class="text-muted mb-4">فعّل الإضافات التي تناسب القالب الخاص بك، <b>لا تفعل الاضافات الغير مدعومة من اجل تسريع الموقع</b></p>

<div class="row g-4" id="addonsGrid">
    <?php foreach ($addonsMap as $key => $info):
        [$label, $icon, $desc] = $info;
        $enabled = !empty($activeModules[$key]);
    ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 addon-card" data-module="<?php echo $key ?>">
                <div class="card-body d-flex align-items-start gap-3">
                    <div class="addon-icon-wrap flex-shrink-0 d-flex align-items-center justify-content-center rounded <?php echo $enabled ? 'bg-label-success' : 'bg-label-secondary' ?>" style="width:48px;height:48px">
                        <i class="mdi <?php echo $icon ?> mdi-24px"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></h6>
                            <label class="switch switch-md" style="margin-left: 10%;">
                                <input type="checkbox" class="switch-input addon-toggle" role="switch" data-module="<?php echo $key ?>" <?php if ($enabled) echo 'checked'; ?>>
                                <span class="switch-toggle-slider">
                                    <span class="switch-on"></span>
                                    <span class="switch-off"></span>
                                </span>
                            </label>
                        </div>
                        <p class="text-muted small mb-0 mt-1"><?php echo htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script src="js/addons.js"></script>