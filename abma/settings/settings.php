<?php requireOwner(); ?>
<title>الإعدادات</title>
<h4 class="py-3 mb-4"><span class="text-muted fw-light">الاعدادات /</span> إعدادات الموقع</h4>
<?php
if (isset($_POST['submit'])) {
    $csrf->verify();

    if (!empty(safer($_POST['name'])) or !empty(safer($_POST['description'])) or !empty(safer($_POST['site_metaTags']))) {

        if (!empty($_FILES['logo']['name'])) { // التحقق من وجود صورة جديدة
            unlink("../../files/images/" . $site['logo']);
            $logoName = "logo";
            $up = up($logoName, "logo", "../../files/images", 20);
            if ($up == "uploaded_done") {
                $columns = "value = ?";
                $values = [$filename, "logo"];
                dbUpdate("settings", $columns, $values, "WHERE name = ?");
            }
        }
        if (!empty($_FILES['banner']['name'])) { // التحقق من وجود صورة جديدة
            unlink("../../files/images/banner.png");
            $up = up("banner.png", "banner", "../../files/images", 20);
        }
        if (!empty($_FILES['logo_color']['name'])) { // التحقق من وجود لوقو ملون جديد
            unlink("../../files/images/" . $site['logo_color']);
            $up = up("logo-color.png", "logo_color", "../../files/images", 20);
        }
        // Update query
        $settingsData = [
            'name' => safer($_POST['name']),
            'name_en' => safer($_POST['name_en']),
            'site_mail' => safer($_POST['site_mail']),
            'description' => safer($_POST['description']),
            'description_en' => safer($_POST['description_en']),
            'site_phone' => safer($_POST['site_phone']),
            'site_metaTags' => safer($_POST['site_metaTags']),
            'site_metaTags_en' => safer($_POST['site_metaTags_en']),
            'indexnow' => safer($_POST['indexnow']),
            'whatsapp_number' => safer($_POST['whatsapp_number']),
            'facebook' => safer($_POST['facebook']),
            'twitter' => safer($_POST['twitter']),
            'instagram' => safer($_POST['instagram']),
            'github' => safer($_POST['github']),
            'snapchat' => safer($_POST['snapchat']),
            'discord' => safer($_POST['discord']),
            'linkedin' => safer($_POST['linkedin']),
            'youtube' => safer($_POST['youtube']),
            'pdf' => safer($_POST['pdf']),
            'telegram' => safer($_POST['telegram']),
            'smtp_host' => safer($_POST['smtp_host']),
            'smtp_user' => safer($_POST['smtp_user']),
            'smtp_pass' => safer($_POST['smtp_pass']),
            'maps' => safer($_POST['maps']),
            'location' => safer($_POST['location']),
            'location_en' => safer($_POST['location_en'])
        ];

        // حلقة لتحديث البيانات
        foreach ($settingsData as $name => $value) {
            $columns = "value = ?";
            $vars = [$value];
            $where = "WHERE name = ?";
            dbUpdate("settings", $columns, array_merge($vars, [$name]), $where);
        }

        // إعدادات "لغة الموقع" و"نوع الموقع": نستخدم saveSetting() (إدراج أو تحديث)
        // بدل الحلقة أعلاه، لأنها قد لا تملك صفاً بجدول settings بعد في التركيبات القديمة
        $allowedLangModes = ['ar', 'en', 'both'];
        $languageMode = in_array($_POST['language_mode'] ?? '', $allowedLangModes, true) ? $_POST['language_mode'] : 'both';
        saveSetting('language_mode', $languageMode);

        $businessTypeOptions = businessTypeOptions();
        $businessType = isset($businessTypeOptions[$_POST['business_type'] ?? '']) ? $_POST['business_type'] : 'organization';
        saveSetting('business_type', $businessType);

        // إعدادات شريط الأخبار العاجلة — نفس أسلوب saveSetting() لاحتمال عدم وجود صف
        // بجدول settings بعد (تركيبات لم تُحدَّث)
        $tickerEnabled = isset($_POST['ticker_enabled']) ? 1 : 0;
        saveSetting('ticker_enabled', $tickerEnabled);
        $tickerSpeed = (int) ($_POST['ticker_speed'] ?? 26);
        if ($tickerSpeed < 6) $tickerSpeed = 6;
        if ($tickerSpeed > 120) $tickerSpeed = 120;
        saveSetting('ticker_speed', $tickerSpeed);

        // روابط المسارات القابلة للتخصيص — تحقق من صحة كل رابط مُدخَل (بما فيها
        // التعارض مع الأنواع الأخرى) قبل الحفظ، ثم إعادة توليد الكتلة المُدارة
        // بملف .htaccess. أي خطأ تحقّق يُلغي حفظ روابط المسارات فقط (بقية
        // الإعدادات أعلاه تُحفظ بنجاح دائماً) ويُعرض رسالة توضيحية.
        $routeTypes = customizableRoutes();
        $submittedRoutes = [];
        foreach ($routeTypes as $type => $default) {
            $submittedRoutes[$type] = trim((string) ($_POST['route_' . $type] ?? ''));
        }
        $routeError = null;
        foreach ($submittedRoutes as $type => $value) {
            $check = validateRouteSlug($type, $value, $submittedRoutes);
            if ($check !== true) {
                $routeError = $check;
                break;
            }
        }
        $routeSaveWarning = null;
        if ($routeError === null) {
            foreach ($submittedRoutes as $type => $value) {
                saveSetting('route_' . $type, $value);
            }
            $htaccessResult = regenerateRouteHtaccess();
            if ($htaccessResult !== true) {
                $routeSaveWarning = $htaccessResult;
            }
        } else {
            $routeSaveWarning = $routeError;
        }

        if ($routeSaveWarning !== null) {
            sweet("warning", "تم الحفظ مع تنبيه", "تم تحديث اعدادات الموقع، لكن حدثت مشكلة بروابط المسارات: " . $routeSaveWarning, "settings");
        } else {
            sweet("success", "نجاح", "تم تحديث اعدادات الموقع بنجاح", "settings");
        }
    } else {
        sweet("error", "خطأ", "اسم الموقع و وصف الموقع و الكلمات المفتاحية هي حقول اجبارية");
    }
}
?>
<div class="card mb-4">
    <h5 class="card-header">ضبط اعدادات الموقع الأساسية</h5>
    <form class="card-body" method="post" enctype="multipart/form-data">
        <h6>1. الاعدادات الأساسية</h6>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" name="name" value="<?php echo $site['name']; ?>" placeholder="اسم الموقع" required>
                    <label>اسم الموقع</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" name="name_en" value="<?php echo $site['name_en']; ?>" placeholder="اسم الموقع بالانجليزي" required>
                    <label>اسم الموقع بالانجليزي</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating form-floating-outline">
                    <textarea class="form-control" name="description" placeholder="وصف الموقع" required><?php echo $site['description']; ?></textarea>
                    <label>وصف الموقع</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating form-floating-outline">
                    <textarea class="form-control" name="description_en" placeholder="وصف الموقع بالانجليزي" required><?php echo $site['description_en']; ?></textarea>
                    <label>وصف الموقع بالانجليزي</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating form-floating-outline">
                    <textarea class="form-control" name="site_metaTags" placeholder="الكلمات الدلالية" required><?php echo $site['site_metaTags']; ?></textarea>
                    <label>الكلمات الدلالية</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating form-floating-outline">
                    <textarea class="form-control" name="site_metaTags_en" placeholder="الكلمات الدلالية بالانجليزي" required><?php echo $site['site_metaTags_en']; ?></textarea>
                    <label>الكلمات الدلالية بالانجليزي</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating form-floating-outline">
                    <input type="email" class="form-control" name="site_mail" value="<?php echo $site['site_mail']; ?>" placeholder="ايميل الموقع">
                    <label>ايميل الموقع</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" placeholder="جوال الموقع" name="site_phone" value="<?php echo $site['site_phone']; ?>">
                    <label>جوال الموقع</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" placeholder="رمز IndexNow" name="indexnow" value="<?php echo $site['indexnow']; ?>">
                    <label>رمز IndexNow</label>
                </div>
                <small class="form-text text-muted">يمكنك الحصول على رمز IndexNow من خلال <a href="https://www.indexnow.org/" target="_blank">IndexNow</a></small>
            </div>
            <div class="col-md-4">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" placeholder="مثال: الرياض، حي التعاون" name="location" value="<?php echo $site['location']; ?>">
                    <label>وصف اللوكيشن بالعربي</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" placeholder="Example: Riyadh, Taawon" name="location_en" value="<?php echo $site['location_en']; ?>">
                    <label>وصف اللوكيشن بالانجليزي</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" placeholder="رابط خريطة Google" name="maps" value="<?php echo $site['maps']; ?>">
                    <label>رابط الخريطة</label>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                    <select class="form-select" name="language_mode" id="languageModeSelect">
                        <?php
                        $languageModeLabels = [
                            'both' => 'عربي وإنجليزي (لغتان)',
                            'ar'   => 'عربي فقط',
                            'en'   => 'إنجليزي فقط',
                        ];
                        $currentLanguageMode = $site['language_mode'] ?? 'both';
                        foreach ($languageModeLabels as $val => $label): ?>
                            <option value="<?php echo $val; ?>" <?php if ($currentLanguageMode == $val) echo 'selected'; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>لغة الموقع الرئيسية</label>
                </div>
                <small class="form-text text-muted">عند اختيار لغة واحدة فقط، تختفي تلقائياً حقول ترجمة المحتوى (بالإضافة لزر تبديل اللغة بالموقع العام) لأن اللغة الثانية غير مفعّلة أصلاً.</small>
            </div>
            <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                    <select class="form-select" name="business_type">
                        <?php
                        $businessTypeOptions = businessTypeOptions();
                        $currentBusinessType = $site['business_type'] ?? 'organization';
                        foreach ($businessTypeOptions as $val => $opt): ?>
                            <option value="<?php echo $val; ?>" <?php if ($currentBusinessType == $val) echo 'selected'; ?>><?php echo $opt['label']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>نوع الموقع</label>
                </div>
                <small class="form-text text-muted">يُستخدم لتحديد نوع بيانات Schema.org (SEO) المناسبة لموقعك تلقائياً (مؤسسة، نشاط تجاري محلي، مطعم، متجر...).</small>
            </div>
        </div>
        <hr class="my-4 mx-n4">
        <h6>2. مواقع التواصل</h6>
        <div class="row g-4">
            <div class="col-md-3">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" name="whatsapp_number" value="<?php echo $site['whatsapp_number']; ?>" placeholder="واتساب">
                    <label>واتساب</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" name="facebook" value="<?php echo $site['facebook']; ?>" placeholder="فيس بوك">
                    <label>فيس بوك</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" name="twitter" value="<?php echo $site['twitter']; ?>" placeholder="تويتر">
                    <label>تويتر</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" name="instagram" value="<?php echo $site['instagram']; ?>" placeholder="انستقرام">
                    <label>انستقرام</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" name="github" value="<?php echo $site['github']; ?>" placeholder="Github">
                    <label>Github</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" name="snapchat" value="<?php echo $site['snapchat']; ?>" placeholder="سناب شات">
                    <label>سناب شات</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" name="discord" value="<?php echo $site['discord']; ?>" placeholder="دسكورد">
                    <label>دسكورد</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" name="linkedin" value="<?php echo $site['linkedin']; ?>" placeholder="لينكد إن">
                    <label>لينكد إن</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" name="youtube" value="<?php echo $site['youtube']; ?>" placeholder="يوتيوب">
                    <label>يوتيوب</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" name="telegram" value="<?php echo $site['telegram']; ?>" placeholder="تيليجرام">
                    <label>تيليجرام</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" name="pdf" value="<?php echo $site['pdf']; ?>" placeholder="رابط PDF">
                    <label>رابط PDF</label>
                    <small class="text-danger">يمكن وضع رابط تحميل البروفايل او السيرة الذاتية</small>
                </div>
            </div>
        </div>

        <hr class="my-4 mx-n4">
        <h6>3. اعدادات الايميل</h6>
        <div class="row">
            <div class="col">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" name="smtp_host" value="<?php echo $site['smtp_host']; ?>" placeholder="المستضيف">
                    <label>المستضيف</label>
                </div>
            </div>
            <div class="col">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" name="smtp_user" value="<?php echo $site['smtp_user']; ?>" placeholder="اليوزر">
                    <label>اليوزر</label>
                </div>
            </div>
            <div class="col">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" name="smtp_pass" value="<?php echo $site['smtp_pass']; ?>" placeholder="كلمة المرور">
                    <label>كلمة المرور</label>
                </div>
            </div>

        </div>

        <hr class="my-4 mx-n4">
        <h6>4. صورة المعاينة الافتراضية (مهم من أجل SEO)</h6>
        <div class="row">
            <div class="col">
                <div class="form-floating form-floating-outline">
                    <?php
                    // if not exists banner.png Change to banner.jpg
                    $bannerPath = "../../files/images/banner.png";
                    if (!file_exists($bannerPath)) {
                        $bannerPath = "../files/images/banner.jpg";
                    } else {
                        $bannerPath = "../files/images/banner.png";
                    }
                    ?>
                    <img src="<?php echo $bannerPath; ?>" width="200" />
                </div>
            </div>
            <div class="col">
                <div class="form-floating form-floating-outline">
                    <input type="file" class="form-control" id="imageInputBanner" name="banner">
                    <label>الصورة الافتراضية (اتركه فارغ لعدم التغيير)</label>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary mt-2 media-picker-btn" data-target="#imageInputBanner"><i class="mdi mdi-image-multiple-outline"></i> اختر من المكتبة</button>
            </div>
        </div>

        <hr class="my-4 mx-n4">
        <h6>5. شعار (لوقو) الموقع</h6>
        <div class="row">
            <div class="col">
                <div class="form-floating form-floating-outline">
                    <img src="../files/images/<?php echo $site['logo'] ?>" width="200" />
                </div>
            </div>

            <div class="col">
                <div class="form-floating form-floating-outline">
                    <input type="file" class="form-control" id="imageInputLogo" name="logo">
                    <label>شعار الموقع (اتركه فارغ لعدم التغيير)</label>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary mt-2 media-picker-btn" data-target="#imageInputLogo"><i class="mdi mdi-image-multiple-outline"></i> اختر من المكتبة</button>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <div class="form-floating form-floating-outline">
                    <img src="../files/images/<?php echo $site['logo_color'] ?>" width="200" />
                </div>
            </div>

            <div class="col">
                <div class="form-floating form-floating-outline">
                    <input type="file" class="form-control" id="imageInputLogoColor" name="logo_color">
                    <label>شعار الموقع ملون (اتركه فارغ لعدم التغيير)</label>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary mt-2 media-picker-btn" data-target="#imageInputLogoColor"><i class="mdi mdi-image-multiple-outline"></i> اختر من المكتبة</button>
            </div>
        </div>

        <?php
        // شريط الأخبار العاجلة مفيد فقط لمواقع الأخبار/المقالات/الرياضة — يُخفى
        // تماماً (وليس فقط بصرياً) عن أي نوع موقع آخر حتى لا يظهر إعداد لا علاقة
        // له بنشاط الموقع. القيم الثلاث مطابقة لمفاتيح businessTypeOptions()
        // (includes/functions.php): news_site / sports_site / blog_site.
        $tickerRelevantBusinessTypes = ['news_site', 'sports_site', 'blog_site'];
        $showTickerSection = in_array($site['business_type'] ?? 'organization', $tickerRelevantBusinessTypes, true);
        ?>
        <div id="tickerSettingsSection" style="<?php echo $showTickerSection ? '' : 'display:none;'; ?>">
            <hr class="my-4 mx-n4">
            <h6>6. شريط الأخبار العاجلة (Ticker)</h6>
            <p class="text-muted small mb-3">يظهر فقط لأنواع المواقع المناسبة (إخباري / مقالات-مدونة / رياضي) — خاص بالقوالب التي تعرض شريطاً متحركاً بأحدث الأخبار (مثل قالب "الأخبار")، ولا تأثير له على القوالب الأخرى.</p>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" role="switch" name="ticker_enabled" id="tickerEnabledSwitch" <?php if (!empty($site['ticker_enabled'])) echo 'checked'; ?>>
                        <label class="form-check-label" for="tickerEnabledSwitch">تفعيل شريط الأخبار العاجلة</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating form-floating-outline">
                        <input type="number" min="6" max="120" class="form-control" name="ticker_speed" value="<?php echo (int) ($site['ticker_speed'] ?? 26); ?>" placeholder="سرعة الشريط">
                        <label>سرعة تمرير الشريط (بالثواني)</label>
                    </div>
                    <small class="form-text text-muted">رقم أكبر = تمرير أبطأ. رقم أصغر = تمرير أسرع.</small>
                </div>
            </div>
        </div>

        <hr class="my-4 mx-n4">
        <h6>7. روابط المسارات القابلة للتخصيص</h6>
        <p class="text-muted small mb-3">
            تحكّم بالكلمة الإنجليزية المستخدمة برابط كل نوع محتوى بالموقع العام (مثال: تغيير
            <code>blog</code> إلى <code>articles</code> يجعل رابط المقالات <code><?php echo $site['site_url']; ?>articles</code>
            بدل <code><?php echo $site['site_url']; ?>blog</code>). اترك أي حقل فارغاً للإبقاء على الرابط الافتراضي.
            يُسمح فقط بأحرف إنجليزية وأرقام والشرطة (-)، ولا يمكن تكرار نفس الرابط لأكثر من نوع.
            تُحدَّث الروابط فوراً بعد الحفظ دون الحاجة لأي تعديل يدوي بملف الاستضافة.
        </p>
        <?php
        $routeTypeLabels = [
            'blog'      => 'المقالات (المدونة)',
            'service'   => 'الخدمات',
            'portfolio' => 'معرض الأعمال',
            'matches'   => 'المباريات (قوالب رياضية)',
            'page'      => 'الصفحات الثابتة',
            'contact'   => 'التواصل',
            'search'    => 'البحث',
        ];
        $routeDefaults = customizableRoutes();
        ?>
        <div class="row g-4">
            <?php foreach ($routeTypeLabels as $rType => $rLabel): ?>
                <div class="col-md-4">
                    <div class="form-floating form-floating-outline">
                        <input type="text" class="form-control" name="route_<?php echo $rType; ?>"
                            value="<?php echo $site['route_' . $rType] ?? ''; ?>"
                            placeholder="<?php echo $routeDefaults[$rType]; ?>">
                        <label><?php echo $rLabel; ?></label>
                    </div>
                    <small class="form-text text-muted">الافتراضي: <code><?php echo $routeDefaults[$rType]; ?></code></small>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="pt-4">
            <?php $csrf->input(); ?>
            <button type="reset" class="btn btn-outline-secondary waves-effect">الغاء</button>
            <button type="submit" name="submit" class="btn btn-primary me-sm-3 me-1 waves-effect waves-light">اعتمد</button>
        </div>
    </form>
</div>

<script>
    // إظهار/إخفاء قسم "شريط الأخبار العاجلة" فوراً عند تغيير نوع الموقع، دون
    // انتظار حفظ النموذج — يبقى الإخفاء النهائي (server-side) قائماً في PHP
    // أعلاه كمصدر الحقيقة الفعلي بعد أي حفظ/تحديث للصفحة.
    (function () {
        var relevantTypes = ['news_site', 'sports_site', 'blog_site'];
        var businessTypeSelect = document.querySelector('select[name="business_type"]');
        var tickerSection = document.getElementById('tickerSettingsSection');
        if (!businessTypeSelect || !tickerSection) return;
        businessTypeSelect.addEventListener('change', function () {
            tickerSection.style.display = relevantTypes.indexOf(this.value) !== -1 ? '' : 'none';
        });
    })();
</script>