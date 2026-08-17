<?php requireOwner(); ?>
<title>دليل بناء القوالب للمصممين</title>
<h4 class="py-3 mb-3"><span class="text-muted fw-light">الإعدادات /</span> دليل بناء القوالب</h4>

<div class="alert alert-info">
    هذا الدليل مرجع كامل لأي مصمم/مطوّر يريد بناء قالب (Theme) جديد لهذا السكربت باستخدام Twig. يشرح بنية الملفات المطلوبة، وكل المتغيرات والدوال المتاحة داخل القوالب، مع أمثلة عملية مستوحاة من قالب <b>istishari</b> الجاهز (يمكن الرجوع لملفاته مباشرة كمرجع حي).
</div>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title mb-3">فهرس سريع</h5>
        <div class="row">
            <div class="col-md-4"><a href="developers#structure">1. هيكل ملفات القالب</a></div>
            <div class="col-md-4"><a href="developers#themejson">2. ملف theme.json</a></div>
            <div class="col-md-4"><a href="developers#globals">3. المتغيرات المتاحة في كل صفحة</a></div>
            <div class="col-md-4"><a href="developers#functions">4. الدوال والفلاتر</a></div>
            <div class="col-md-4"><a href="developers#pages">5. متغيرات كل صفحة على حدة</a></div>
            <div class="col-md-4"><a href="developers#pagination">6. الترقيم (Pagination)</a></div>
            <div class="col-md-4"><a href="developers#seo">7. اصطلاحات SEO</a></div>
            <div class="col-md-4"><a href="developers#homesections">8. ترتيب أقسام الرئيسية</a></div>
            <div class="col-md-4"><a href="developers#contact">9. نموذج التواصل</a></div>
            <div class="col-md-4"><a href="developers#newsletter">9.1 نموذج النشرة البريدية</a></div>
            <div class="col-md-4"><a href="developers#ads">9.2 نظام الإعلانات</a></div>
            <div class="col-md-4"><a href="developers#sports">9.3 وحدات الرياضة (مباريات/ترتيب/فيديوهات)</a></div>
            <div class="col-md-4"><a href="developers#feeds">9.4 سحب المقالات (RSS/Feed)</a></div>
            <div class="col-md-4"><a href="developers#routing">10. مسارات الروابط</a></div>
            <div class="col-md-4"><a href="developers#uploads">11. مسارات الصور المرفوعة</a></div>
            <div class="col-md-4"><a href="developers#tips">12. نصائح عملية</a></div>
            <div class="col-md-4"><a href="developers#hooks">13. نظام Hooks/Actions (للمطوّرين)</a></div>
            <div class="col-md-4"><a href="developers#restapi">14. REST API خفيف (api/v1)</a></div>
        </div>
    </div>
</div>

<div class="card mb-4" id="structure">
    <div class="card-body">
        <h5 class="card-title">1. هيكل ملفات القالب</h5>
        <p>كل قالب هو مجلد مستقل داخل <code>templates/</code> باسم مفرد بالإنجليزية بدون مسافات (مثلاً <code>templates/mytheme/</code>). Twig يبحث عن الملفات داخل هذا المجلد فقط، فلا يمكن للقالب استدعاء ملفات قالب آخر.</p>

        <h6 class="mt-4">ملفات أساسية (يجب توفّرها دائماً)</h6>
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>الملف</th>
                    <th>الوظيفة</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>base.twig</code></td>
                    <td>القالب الأساسي الذي تمتد منه كل الصفحات الأخرى عبر <code>{% extends "base.twig" %}</code>، ويحتوي عادة على <code>{% include 'header.twig' %}</code> وBlock باسم <code>content</code> و<code>{% include 'footer.twig' %}</code>.</td>
                </tr>
                <tr>
                    <td><code>header.twig</code></td>
                    <td>يحتوي <code>&lt;head&gt;</code> الكامل (meta, CSS) وبداية <code>&lt;body&gt;</code> وشريط التنقل العلوي.</td>
                </tr>
                <tr>
                    <td><code>footer.twig</code></td>
                    <td>الفوتر وإغلاق وسوم HTML وروابط ملفات JS.</td>
                </tr>
                <tr>
                    <td><code>home.twig</code></td>
                    <td>الصفحة الرئيسية للنسخة العربية.</td>
                </tr>
                <tr>
                    <td><code>home_en.twig</code></td>
                    <td>الصفحة الرئيسية للنسخة الإنجليزية (يمكن أن تكون نفس محتوى home.twig مع نصوص مختلفة، أو تتشاركان partial واحد كما في istishari).</td>
                </tr>
                <tr>
                    <td><code>404.twig</code></td>
                    <td>صفحة الخطأ 404، تُستخدم أيضاً تلقائياً لأي وحدة غير مفعّلة في theme.json.</td>
                </tr>
                <tr>
                    <td><code>pages.twig</code></td>
                    <td>عرض الصفحات الثابتة (سياسة الخصوصية، من نحن...).</td>
                </tr>
                <tr>
                    <td><code>theme.json</code></td>
                    <td>ملف الإعدادات (راجع القسم التالي) — بدونه تُعتبر كل الوحدات القديمة مفعّلة تلقائياً.</td>
                </tr>
                <tr>
                    <td><code>screenshot.png</code></td>
                    <td>صورة معاينة القالب، تظهر في معرض القوالب وفي معالج اختيار نوع النشاط بلوحة التحكم. بدونها لن يظهر القالب في المعرض.</td>
                </tr>
            </tbody>
        </table>

        <h6 class="mt-4">ملفات اختيارية (حسب الوحدات المفعّلة)</h6>
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>الوحدة</th>
                    <th>الملفات</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>contact</td>
                    <td><code>contact.twig</code></td>
                </tr>
                <tr>
                    <td>search</td>
                    <td><code>search.twig</code></td>
                </tr>
                <tr>
                    <td>blogs</td>
                    <td><code>blogs/all.twig</code>, <code>blogs/view.twig</code></td>
                </tr>
                <tr>
                    <td>services</td>
                    <td><code>services/all.twig</code>, <code>services/view.twig</code></td>
                </tr>
                <tr>
                    <td>portfolio</td>
                    <td><code>portfolio/all.twig</code>, <code>portfolio/view.twig</code></td>
                </tr>
            </tbody>
        </table>
        <p class="text-muted mb-0">أما بقية الوحدات (team, testimonials, faq, stats, pricing, branches, certificates, clients) فليس لها صفحات مستقلة — بياناتها تُعرض فقط ضمن الصفحة الرئيسية.</p>

        <h6 class="mt-4">مجلد الأصول (Assets)</h6>
        <p class="mb-0">يُفضّل وضع كل ملفات CSS/JS/الخطوط الخاصة بالقالب داخل <code>assets/</code> أو <code>css/</code> و<code>js/</code> داخل مجلد القالب نفسه، والوصول لها عبر المتغير <code>templatePath</code> (مثال: <code>{{ templatePath }}/assets/css/style.css</code>).</p>
    </div>
</div>

<div class="card mb-4" id="themejson">
    <div class="card-body">
        <h5 class="card-title">2. ملف theme.json</h5>
        <p>يحدّد اسم القالب، نوع النشاط الأنسب له (يُستخدم في معالج اختيار نوع النشاط)، وأي الوحدات (Modules) يدعمها القالب. لوحة التحكم تُخفي تلقائياً أي رابط/قسم لوحدة غير مفعّلة هنا، ولا تظهر بياناتها في الصفحة الرئيسية.</p>
        <pre class="bg-light p-3 rounded" dir="ltr" style="direction:ltr;text-align:left"><code>{
    "name": "My Theme",
    "activity_type": "consulting_company",
    "modules": {
        "pages": true,
        "blogs": true,
        "services": true,
        "portfolio": true,
        "categories": true,
        "contact": true,
        "search": true,
        "clients": true,
        "team": true,
        "testimonials": true,
        "faq": true,
        "stats": true,
        "pricing": false,
        "branches": false,
        "certificates": false
    }
}</code></pre>
        <p class="text-muted">أنواع النشاط المستخدمة حالياً في معالج اختيار نوع النشاط: <code>personal_portfolio</code>, <code>blog</code>, <code>consulting_company</code>, <code>company</code>, <code>tech_company</code> — يمكنك استخدام قيمة جديدة، ستظهر ضمن تصنيف "أخرى" حتى تُضاف كتصنيف رسمي.</p>
        <p class="mb-0"><b>ملاحظة مهمة:</b> إن لم يوجد theme.json إطلاقاً، تُعتبر الوحدات التسع الأساسية (pages, blogs, services, portfolio, categories, contact, search, clients) مفعّلة تلقائياً، والوحدات الأحدث (team, testimonials, faq, stats, pricing, branches, certificates) معطّلة افتراضياً.</p>

        <h6 class="mt-4">مفتاح <code>data</code> (اختياري) — بيانات القالب والمصمم</h6>
        <p>مفتاح إضافي واختياري بجانب <code>name</code>/<code>activity_type</code>/<code>modules</code>، يُستخدم فقط لعرض معلومات تعريفية عن القالب ومصمّمه في "معرض القوالب" بلوحة التحكم (بطاقة كل قالب). لا علاقة له بعمل القالب على الموقع نفسه.</p>
        <pre class="bg-light p-3 rounded" dir="ltr" style="direction:ltr;text-align:left"><code>{
    "name": "WorkUp",
    "activity_type": "tech_company",
    "data": {
        "name": "البنفسجي",
        "description": "قالب بنفسجي تم تطويره لسكربت WorkUP",
        "design": "محمد عكور",
        "url": "https://workup.sa"
    },
    "modules": { ... }
}</code></pre>
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th>الحقل</th>
                    <th>الوصف</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>data.name</code></td>
                    <td>اسم القالب المعروض في بطاقة معرض القوالب (إن غاب، يُستخدم اسم مجلد القالب بدلاً منه).</td>
                </tr>
                <tr>
                    <td><code>data.description</code></td>
                    <td>وصف مختصر للقالب يظهر تحت اسمه في نفس البطاقة.</td>
                </tr>
                <tr>
                    <td><code>data.design</code></td>
                    <td>اسم المصمم/المطوّر، يظهر في أسفل البطاقة كـ"المطور: ...".</td>
                </tr>
                <tr>
                    <td><code>data.url</code></td>
                    <td>رابط يضعه المصمم (موقعه الشخصي، معرض أعماله...)، يظهر كزر "زيارة موقع المطور" في أسفل البطاقة.</td>
                </tr>
            </tbody>
        </table>
        <p class="mb-0 text-muted">تُقرأ هذه البيانات عبر الدالة <code>themeData($folder)</code> في <code>includes/functions.php</code> (تُعيد <code>false</code> إن كان theme.json غير موجود أو مفتاح <code>data</code> غير موجود/فارغ)، وتُستهلك في <code>abma/templates.php</code>. كل الحقول اختيارية تماماً — القالب يعمل بشكل طبيعي بدونها، فقط لن تظهر بطاقة "معلومات المصمم" في المعرض.</p>
    </div>
</div>

<div class="card mb-4" id="globals">
    <div class="card-body">
        <h5 class="card-title">3. المتغيرات المتاحة في كل صفحة (Globals)</h5>
        <p>هذه المتغيرات متاحة تلقائياً في <b>كل</b> ملفات Twig بالقالب دون الحاجة لتمريرها من أي ملف PHP.</p>
        <table class="table table-bordered table-striped">
            <thead class="table-light">
                <tr>
                    <th>المتغير</th>
                    <th>النوع</th>
                    <th>الوصف</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>siteUrl</code></td>
                    <td>نص</td>
                    <td>الرابط الأساسي للموقع (ينتهي بـ /)، استخدمه دائماً بدل كتابة الروابط يدوياً.</td>
                </tr>
                <tr>
                    <td><code>templatePath</code></td>
                    <td>نص</td>
                    <td>المسار النسبي لمجلد القالب الحالي، مثال: <code>templates/mytheme</code>.</td>
                </tr>
                <tr>
                    <td><code>siteName</code></td>
                    <td>نص</td>
                    <td>اسم الموقع، بلغة الزائر الحالية تلقائياً.</td>
                </tr>
                <tr>
                    <td><code>siteDescription</code></td>
                    <td>نص</td>
                    <td>وصف الموقع العام (لوسم meta description الافتراضي).</td>
                </tr>
                <tr>
                    <td><code>siteMetaTags</code></td>
                    <td>نص</td>
                    <td>الكلمات المفتاحية العامة للموقع.</td>
                </tr>
                <tr>
                    <td><code>location</code></td>
                    <td>نص</td>
                    <td>الموقع الجغرafي (نص حر تحدده الإدارة من الإعدادات).</td>
                </tr>
                <tr>
                    <td><code>logo</code> / <code>logo_color</code></td>
                    <td>نص</td>
                    <td>اسم ملفَي الشعار (أبيض/ملوّن)، تُقرأ من <code>files/images/</code>.</td>
                </tr>
                <tr>
                    <td><code>csrftoken</code></td>
                    <td>نص</td>
                    <td>رمز CSRF الحالي، ضعه في أي نموذج AJAX عام (مثل نموذج التواصل).</td>
                </tr>
                <tr>
                    <td><code>whatsapp_number</code></td>
                    <td>نص</td>
                    <td>رقم واتساب التواصل (بدون +).</td>
                </tr>
                <tr>
                    <td><code>site_mail</code> / <code>site_phone</code></td>
                    <td>نص</td>
                    <td>بريد وهاتف التواصل.</td>
                </tr>
                <tr>
                    <td><code>maps</code></td>
                    <td>نص</td>
                    <td>رابط تضمين خرائط جوجل (iframe src جاهز).</td>
                </tr>
                <tr>
                    <td><code>pdf</code></td>
                    <td>نص</td>
                    <td>رابط ملف (سيرة ذاتية مثلاً، حسب استخدام الموقع).</td>
                </tr>
                <tr>
                    <td><code>facebook</code>, <code>instagram</code>, <code>twitter</code>/<code>x</code>, <code>github</code>, <code>snapchat</code>, <code>discord</code>, <code>linkedin</code>, <code>youtube</code></td>
                    <td>نص</td>
                    <td>معرّفات/روابط حسابات التواصل الاجتماعي كما أدخلتها الإدارة (وليست روابط كاملة بالضرورة، تحقق من القيمة).</td>
                </tr>
                <tr>
                    <td><code>lang</code></td>
                    <td>قاموس (dictionary)</td>
                    <td>كل نصوص الترجمة، الوصول عبر <code>{{ lang.key }}</code>. يحتوي أيضاً <code>lang.lang_code</code> ("ar"/"en") و<code>lang.dir</code> ("rtl"/"ltr") مفيدان للتحكم باتجاه الصفحة والأيقونات.</td>
                </tr>
                <tr>
                    <td><code>currentLang</code></td>
                    <td>نص</td>
                    <td>اللغة الحالية للزائر ("ar"/"en") من الكوكي.</td>
                </tr>
                <tr>
                    <td><code>languageMode</code></td>
                    <td>نص</td>
                    <td>إعداد "لغة الموقع الرئيسية" (صفحة إعدادات الموقع): <code>both</code> (لغتان) / <code>ar</code> (عربي فقط) / <code>en</code> (إنجليزي فقط). أي زر تبديل لغة بالقالب يجب لفّه بـ <code>{% if languageMode == 'both' %}...{% endif %}</code> — عند اقتصار الموقع على لغة واحدة، حقول الترجمة نفسها تختفي بلوحة التحكم فلا يوجد محتوى مترجَم أصلاً لعرض زر التبديل من أجله.</td>
                </tr>
                <tr>
                    <td><code>pages</code></td>
                    <td>مصفوفة</td>
                    <td>كل الصفحات الثابتة النشطة، كل عنصر: <code>{name, slug}</code>. مفيدة لبناء قائمة "صفحات" في الهيدر/الفوتر.</td>
                </tr>
                <tr>
                    <td><code>clients</code></td>
                    <td>مصفوفة</td>
                    <td>شركاء/عملاء الموقع النشطون، كل عنصر: <code>{name, logo (رابط كامل), url}</code>.</td>
                </tr>
                <tr>
                    <td><code>services</code></td>
                    <td>مصفوفة</td>
                    <td>نسخة مختصرة من الخدمات النشطة <code>{name, slug}</code> فقط — تُستخدم عادة لقائمة منسدلة بالهيدر. <b>تنبيه:</b> في <code>home.twig</code> وصفحات الخدمات، يتم استبدالها بنسخة أكبر تحتوي بيانات أوسع (راجع القسم التالي).</td>
                </tr>
                <tr>
                    <td><code>blogCategories</code></td>
                    <td>مصفوفة</td>
                    <td>تصنيفات المقالات النشطة (إن كانت وحدتا <code>blogs</code> و<code>blog_categories</code> مفعّلتين)، كل عنصر: <code>{id, name, url}</code> — <code>url</code> جاهز لصفحة أرشيف المقالات مفلترة بالتصنيف. مفيدة لبناء شريط أقسام بالهيدر (مثال حي: <code>templates/news/header.twig</code>، عنصر <code>.cat-bar</code>).</td>
                </tr>
                <tr>
                    <td><code>tickerEnabled</code></td>
                    <td>Boolean</td>
                    <td>إعداد "تفعيل شريط الأخبار العاجلة" (صفحة إعدادات الموقع، قسم 6). خاص بالقوالب التي تعرض شريطاً متحركاً بآخر الأخبار — لفّ عرض الشريط بـ <code>{% if tickerEnabled %}...{% endif %}</code>.</td>
                </tr>
                <tr>
                    <td><code>tickerSpeed</code></td>
                    <td>رقم (ثوانٍ)</td>
                    <td>سرعة تمرير شريط الأخبار العاجلة، يضبطها صاحب الموقع من نفس قسم الإعدادات. استخدمها كـ <code>style="animation-duration: {{ tickerSpeed }}s;"</code> على العنصر المتحرك — لا تفترض مدة ثابتة بالـ CSS.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-4" id="functions">
    <div class="card-body">
        <h5 class="card-title">4. الدوال والفلاتر المتاحة</h5>
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>الاستخدام</th>
                    <th>الوصف</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>{% if moduleEnabled('team') %}...{% endif %}</code></td>
                    <td>يتحقق هل الوحدة مفعّلة في theme.json الحالي. <b>ضروري</b> استخدامه قبل عرض أي قسم يعتمد على وحدة اختيارية، حتى لو كانت البيانات فارغة أصلاً.</td>
                </tr>
                <tr>
                    <td><code>{{ text|nl2br_raw }}</code></td>
                    <td>تحويل الأسطر الجديدة \n إلى &lt;br&gt; بدون تحويل بقية HTML (بعكس فلتر nl2br القياسي في Twig).</td>
                </tr>
                <tr>
                    <td><code>{{ serializedValue|unserialize }}</code></td>
                    <td>لفك تشفير قيم PHP المخزّنة بصيغة serialize() (نادراً ما تحتاجه في قالب عادي).</td>
                </tr>
                <tr>
                    <td><code>{{ dump(variable) }}</code></td>
                    <td>لأغراض التطوير فقط: يطبع محتوى أي متغيّر لمعرفة بنيته أثناء بناء القالب. احذفه قبل النشر النهائي.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-4" id="pages">
    <div class="card-body">
        <h5 class="card-title">5. متغيرات كل صفحة على حدة</h5>
        <p>بالإضافة للمتغيرات العامة أعلاه، كل صفحة تُمرَّر لها بيانات خاصة بها من ملف PHP المقابل:</p>

        <h6 class="mt-4">home.twig / home_en.twig</h6>
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th>المتغير</th>
                    <th>الحقول داخل كل عنصر</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>blogs</code></td>
                    <td>id, slug, name, image (رابط كامل), url — آخر 3 مقالات فقط</td>
                </tr>
                <tr>
                    <td><code>services</code></td>
                    <td>id, slug, name, description, image (رابط كامل), icon, url — كل الخدمات النشطة</td>
                </tr>
                <tr>
                    <td><code>portfolios</code> / <code>projects</code></td>
                    <td>id, slug, name, description (مختصرة 80 حرف), image, url, completion_date, category (اسم التصنيف) — آخر 3 أعمال</td>
                </tr>
                <tr>
                    <td><code>team</code></td>
                    <td>id, name, position, image, bio, facebook, twitter, linkedin, instagram, snapchat, whatsapp, phone, website</td>
                </tr>
                <tr>
                    <td><code>testimonials</code></td>
                    <td>id, name, position, image, content, rating (رقم 1-5)</td>
                </tr>
                <tr>
                    <td><code>faqs</code></td>
                    <td>id, question, answer</td>
                </tr>
                <tr>
                    <td><code>stats</code></td>
                    <td>id, number, suffix, label, icon</td>
                </tr>
                <tr>
                    <td><code>pricing</code></td>
                    <td>id, name, price, currency, period, features (مصفوفة أسطر), is_featured</td>
                </tr>
                <tr>
                    <td><code>branches</code></td>
                    <td>id, name, address, phone, email, map, working_hours</td>
                </tr>
                <tr>
                    <td><code>certificates</code></td>
                    <td>id, name, issuer, image, date_issued</td>
                </tr>
                <tr>
                    <td><code>home_sections_order</code></td>
                    <td>مصفوفة نصوص (مفاتيح الأقسام) — راجع قسم "ترتيب أقسام الرئيسية" أدناه</td>
                </tr>
                <tr>
                    <td><code>schema_json</code></td>
                    <td>نص JSON جاهز لوضعه داخل <code>&lt;script type="application/ld+json"&gt;</code> لتحسين SEO</td>
                </tr>
                <tr>
                    <td><code>news_latest</code></td>
                    <td>مخصّصة لقوالب الأخبار (مستقلة تماماً عن <code>blogs</code> أعلاه، لا تؤثر عليها): id, slug, name, image (رابط كامل), url, category_id, category_name, description (مختصرة 140 حرف), date_human (نص "منذ..")، views، reading_time (دقائق تقريبية)، featured (0/1، راجع ملاحظة أدناه) — آخر 13 مقالاً، الميزة (<code>featured</code>) أولاً إن كانت الخاصية مُفعَّلة.</td>
                </tr>
                <tr>
                    <td><code>news_trending</code></td>
                    <td>id, slug, name, image (رابط كامل), url, views — أعلى 5 مقالات بعدد المشاهدات (الأكثر قراءة)</td>
                </tr>
                <tr>
                    <td><code>news_categories</code></td>
                    <td>id, name, url — كل تصنيفات المقالات (نفس بيانات <code>blogCategories</code> العام، لكن هذه نسخة محلية بالصفحة الرئيسية تحديداً)</td>
                </tr>
            </tbody>
        </table>
        <p class="text-muted mb-0"><b>ملاحظة عن <code>featured</code>:</b> حقل "مقال مميّز/عاجل" اختياري، يُضاف لجدول <code>blogs</code> عبر ترحيل ذاتي (صفحة <code>blogs/migrate</code> بلوحة التحكم، رابط يظهر تلقائياً بنموذجي إضافة/تعديل المقال إن لم تُشغَّل الخاصية بعد). إن لم يُشغَّل الترحيل أصلاً، الحقل ببساطة لا يظهر ضمن عناصر <code>news_latest</code> — تعامل معه دائماً بـ <code>{% if item.featured %}...{% endif %}</code> وليس كحقل مضمون الوجود.</p>

        <p class="text-muted mb-0"><b>ملاحظة عن حقول فريق العمل:</b> <code>facebook</code>, <code>twitter</code>, <code>linkedin</code>, <code>instagram</code>, <code>snapchat</code>, <code>website</code> روابط كاملة كما أدخلتها الإدارة، استخدمها مباشرة في <code>href</code>. أما <code>whatsapp</code> و<code>phone</code> فهما رقمان خامان فقط (بدون رابط جاهز) — يجب عليك بناء الرابط داخل القالب:</p>
        <pre class="bg-light p-3 rounded" dir="ltr" style="direction:ltr;text-align:left"><code>&lt;a href="https://wa.me/{{ member.whatsapp }}"&gt;...&lt;/a&gt;  {# بدون علامة + #}
&lt;a href="tel:{{ member.phone }}"&gt;...&lt;/a&gt;

{% if member.facebook or member.whatsapp or member.phone or member.website or member.instagram or member.snapchat or member.linkedin or member.twitter %}
  ...أيقونات التواصل...
{% endif %}</code></pre>
        <p class="text-muted mb-0">هذا نفس الاصطلاح المستخدم مع متغيّر <code>whatsapp_number</code> العام. لا تنسَ إضافة أيقونة/رابط لكل حقل من هذه الحقول الثمانية في أي قالب تبنيه إن كانت وحدة <code>team</code> مفعّلة — القالب الحالي (workup) يطبّق هذا بالضبط في <code>home.twig</code>/<code>home_en.twig</code> ويصلح كمرجع جاهز.</p>

        <h6 class="mt-4">blogs/all.twig</h6>
        <p><code>blogs</code>: مصفوفة {slug, name, image (رابط كامل), url} + <code>pagination</code> (اختياري، راجع القسم التالي)</p>

        <h6>blogs/view.twig</h6>
        <p><code>blog</code>: id, name, description (HTML), tags (مصفوفة), slug, views, reading_time (دقائق تقريبية، من عدد كلمات description)، image (رابط كامل), url, last_update (نص "منذ..") + <code>schema_json</code></p>
        <p><code>related_match</code>: <b>null</b> إن لم يُربط المقال بمباراة، أو عنصر مباراة كامل بنفس شكل عناصر <code>getMatchesForDisplay()</code> (راجع قسم 9.3) إن كان عمود <code>blogs.related_match_id</code> مضبوطاً بلوحة التحكم — لفّ أي عرض بـ<code>{% if related_match is defined and related_match is not null %}...{% endif %}</code>. مثال جاهز للنسخ: <code>templates/ojubasport/blogs/view.twig</code> (صندوق "ملخص المباراة" أعلى المقال).</p>

        <h6 class="mt-4">services/all.twig</h6>
        <p><code>services</code>: مصفوفة {id, slug, name, image, icon, url} + <code>pagination</code></p>

        <h6>services/view.twig</h6>
        <p><code>service</code>: id, name, description (HTML), image, slug, url + <code>other_services</code> (حتى 5 خدمات أخرى: id, name, slug) + <code>schema_json</code></p>

        <h6 class="mt-4">portfolio/all.twig</h6>
        <p><code>portfolios</code> و<code>projects</code> (نفس البيانات، اسمان لنفس المتغيّر): id, slug, name, description, image, url, demo (الرابط الخارجي إن وُجد), completion_date, category (اسم التصنيف) + <code>categories</code> (كل التصنيفات: id, name) + <code>pagination</code></p>

        <h6>portfolio/view.twig</h6>
        <p><code>portfolio</code>: نفس حقول أعلاه لعنصر واحد + <code>other_portfolios</code> (حتى 6 أعمال أخرى) + <code>schema_json</code></p>

        <h6 class="mt-4">pages.twig</h6>
        <p><code>page</code>: id, name, description (HTML), slug, date, ago (نص "منذ..") + <code>pages</code> (كل الصفحات الأخرى للقائمة الجانبية) + <code>no_header</code> (true) + <code>schema_json</code></p>

        <h6 class="mt-4">search.twig</h6>
        <p><code>results</code>: مصفوفة نتائج {id, slug, name/title, description, image/photo} (الحقلان name/title وimage/photo مكرّران لتوافق القوالب القديمة، استخدم أيهما تفضل) + <code>word_search</code> (كلمة البحث) + <code>alert</code> (HTML رسالة جاهزة) + <code>header_cate</code> (تصنيفات) + <code>header_articles</code> (أحدث المقالات)</p>

        <h6 class="mt-4">contact.twig / 404.twig</h6>
        <p><code>contact.twig</code> لا يستقبل متغيرات خاصة (يعتمد على المتغيرات العامة فقط). <code>404.twig</code> يستقبل: <code>error_type</code> ("404")، <code>error_message</code>، <code>error_description</code>.</p>
    </div>
</div>

<div class="card mb-4" id="pagination">
    <div class="card-body">
        <h5 class="card-title">6. الترقيم (Pagination)</h5>
        <p>يُمرَّر تلقائياً لصفحات <code>blogs/all.twig</code>, <code>services/all.twig</code>, <code>portfolio/all.twig</code> إن كانت النتائج أكثر من صفحة واحدة (9 عناصر لكل صفحة). لا تحتاج فعل شيء في PHP — فقط استخدمه داخل القالب:</p>
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th>الحقل</th>
                    <th>الوصف</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>page</code></td>
                    <td>رقم الصفحة الحالية</td>
                </tr>
                <tr>
                    <td><code>total_pages</code></td>
                    <td>إجمالي عدد الصفحات</td>
                </tr>
                <tr>
                    <td><code>has_prev</code> / <code>has_next</code></td>
                    <td>قيمة منطقية</td>
                </tr>
                <tr>
                    <td><code>prev_page</code> / <code>next_page</code></td>
                    <td>رقم الصفحة السابقة/التالية</td>
                </tr>
                <tr>
                    <td><code>pages</code></td>
                    <td>مصفوفة أرقام الصفحات القريبة من الحالية (نافذة بحد 5 أرقام)</td>
                </tr>
                <tr>
                    <td><code>show_first</code> / <code>show_last</code></td>
                    <td>هل يلزم عرض رابط للصفحة الأولى/الأخيرة بشكل منفصل (عند وجود فجوة)</td>
                </tr>
            </tbody>
        </table>
        <pre class="bg-light p-3 rounded" dir="ltr" style="direction:ltr;text-align:left"><code>{% if pagination is defined and pagination.total_pages > 1 %}
&lt;nav&gt;
    &lt;ul class="pagination"&gt;
        {% for p in pagination.pages %}
        &lt;li class="{% if p == pagination.page %}active{% endif %}"&gt;
            &lt;a href="?page={{ p }}"&gt;{{ p }}&lt;/a&gt;
        &lt;/li&gt;
        {% endfor %}
    &lt;/ul&gt;
&lt;/nav&gt;
{% endif %}</code></pre>
        <p class="mb-0 text-muted">للحصول على نموذج كامل جاهز يشمل النقاط "..." والصفحة الأولى/الأخيرة، راجع نهاية <code>templates/istishari/blogs/all.twig</code>.</p>
    </div>
</div>

<div class="card mb-4" id="seo">
    <div class="card-body">
        <h5 class="card-title">7. اصطلاحات SEO (اختيارية لكنها موصى بها)</h5>
        <p>القوالب الحالية (istishari وغيره) تتبع اصطلاحاً موحّداً لضبط وسوم SEO من داخل كل صفحة عبر <code>{% set %}</code> في أعلى الملف، ويقرأها <code>header.twig</code> تلقائياً. اتّباع نفس الاصطلاح في قالبك يجعل كل الصفحات تُدير عناوينها ووصفها الخاص دون تكرار كود:</p>
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th>المتغيّر</th>
                    <th>الاستخدام</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>page_title</code></td>
                    <td>يُضاف قبل اسم الموقع في وسم &lt;title&gt; وog:title</td>
                </tr>
                <tr>
                    <td><code>subPageDescription</code></td>
                    <td>وصف meta description/og:description خاص بهذه الصفحة</td>
                </tr>
                <tr>
                    <td><code>pageMetaTags</code></td>
                    <td>كلمات مفتاحية خاصة بالصفحة (تحل محل siteMetaTags العام)</td>
                </tr>
                <tr>
                    <td><code>og_url</code></td>
                    <td>الجزء النسبي من الرابط بعد siteUrl (مثال: "blog/my-post") لبناء canonical وog:url</td>
                </tr>
                <tr>
                    <td><code>page_type</code></td>
                    <td>نوع Open Graph (article, website...)</td>
                </tr>
                <tr>
                    <td><code>basicIMG</code></td>
                    <td>رابط صورة og:image مخصص لهذه الصفحة</td>
                </tr>
            </tbody>
        </table>
        <pre class="bg-light p-3 rounded" dir="ltr" style="direction:ltr;text-align:left"><code>{% extends "base.twig" %}
{% set page_title = blog.name %}
{% set subPageDescription = blog.description|striptags|slice(0, 150) %}
{% set og_url = "blog/" ~ blog.slug %}
{% set page_type = "article" %}</code></pre>
    </div>
</div>

<div class="card mb-4" id="homesections">
    <div class="card-body">
        <h5 class="card-title">8. ترتيب أقسام الرئيسية (اختياري - متقدّم)</h5>
        <p>لوحة التحكم فيها صفحة "ترتيب أقسام الرئيسية" تسمح للعميل بإخفاء/إعادة ترتيب أقسام الصفحة الرئيسية (إحصائيات، خدمات، باقات، أعمال، فريق، شهادات، آراء عملاء، شركاء، فروع، أسئلة شائعة، مقالات) دون تعديل الكود. لتفعيل هذا في قالبك:</p>
        <ol>
            <li>أنشئ مجلد <code>partials/home-sections/</code> داخل قالبك.</li>
            <li>ضع كل قسم في ملف مستقل بنفس اسم مفتاح القسم: <code>stats.twig</code>, <code>services.twig</code>, <code>pricing.twig</code>, <code>portfolio.twig</code>, <code>team.twig</code>, <code>certificates.twig</code>, <code>testimonials.twig</code>, <code>clients.twig</code>, <code>branches.twig</code>, <code>faq.twig</code>, <code>blog.twig</code> — كل ملف يبدأ بالتحقق من <code>moduleEnabled()</code> وطول البيانات كالمعتاد.</li>
            <li>في <code>home.twig</code>، بدل كتابة الأقسام يدوياً، استخدم حلقة:</li>
        </ol>
        <pre class="bg-light p-3 rounded" dir="ltr" style="direction:ltr;text-align:left"><code>{% include 'partials/home-sections/hero.twig' %}

{% set default_order = ['stats','services','pricing','portfolio','team','certificates','testimonials','clients','branches','faq','blog'] %}
{% for key in home_sections_order|default(default_order) %}
    {% include 'partials/home-sections/' ~ key ~ '.twig' ignore missing %}
{% endfor %}

{% include 'partials/home-sections/cta.twig' %}</code></pre>
        <p class="mb-0 text-muted">هذا النظام اختياري تماماً — إن كتبت home.twig بأقسام ثابتة بترتيب يدوي (كما تفعل أغلب القوالب الحالية)، سيعمل القالب بشكل طبيعي، فقط لن يستفيد العميل من ميزة إعادة الترتيب من لوحة التحكم.</p>
    </div>
</div>

<div class="card mb-4" id="contact">
    <div class="card-body">
        <h5 class="card-title">9. نموذج التواصل — عقد ثابت يجب اتّباعه بالضبط</h5>
        <p>نموذج التواصل يعمل عبر <code>js/contact.js</code> المشترك (لا تكتب JS خاصاً للإرسال، فقط أعد استخدام الملف الموجود). لهذا يجب أن تحمل الحقول نفس المعرّفات (id) التالية بالضبط:</p>
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th>id الحقل</th>
                    <th>مطلوب؟</th>
                    <th>الوصف</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>fname</code></td>
                    <td>نعم</td>
                    <td>الاسم الأول</td>
                </tr>
                <tr>
                    <td><code>lname</code></td>
                    <td>نعم</td>
                    <td>يُستخدم كـ "الموضوع" فعلياً في العرض الإداري رغم الاسم</td>
                </tr>
                <tr>
                    <td><code>phone</code></td>
                    <td>نعم</td>
                    <td>رقم الجوال</td>
                </tr>
                <tr>
                    <td><code>email</code></td>
                    <td>نعم</td>
                    <td>البريد الإلكتروني</td>
                </tr>
                <tr>
                    <td><code>message</code></td>
                    <td>نعم</td>
                    <td>نص الرسالة (textarea)</td>
                </tr>
                <tr>
                    <td><code>website</code></td>
                    <td>نعم (مخفي)</td>
                    <td>حقل honeypot — يجب إخفاؤه بصرياً (position:absolute خارج الشاشة، ليس display:none حتى لا يتجاهله بعض البوتات) ويجب أن يبقى فارغاً دائماً</td>
                </tr>
                <tr>
                    <td><code>csrf</code></td>
                    <td>نعم (مخفي)</td>
                    <td>قيمته <code>{{ csrftoken }}</code></td>
                </tr>
                <tr>
                    <td><code>sendMessage</code></td>
                    <td>نعم</td>
                    <td>زر (وليس submit عادي) بمعرّف هذا الـ id، هو من يُشغّل الإرسال عبر AJAX</td>
                </tr>
                <tr>
                    <td><code>msgSubmit</code></td>
                    <td>نعم</td>
                    <td>عنصر فارغ (div) تظهر بداخله رسالة النجاح/الخطأ بعد الإرسال</td>
                </tr>
            </tbody>
        </table>
        <p class="mb-0">انسخ نموذج <code>templates/istishari/contact.twig</code> كنقطة بداية آمنة، وعدّل التنسيق فقط دون تغيير أسماء الحقول.</p>
    </div>
</div>

<div class="card mb-4" id="newsletter">
    <div class="card-body">
        <h5 class="card-title">9.1 نموذج النشرة البريدية — عقد ثابت يجب اتّباعه بالضبط</h5>
        <p>الاشتراك بالنشرة البريدية يعمل عبر <code>js/subscribe.js</code> المشترك (لا تكتب JS خاصاً للإرسال، أعد استخدام الملف الموجود مثل نموذج التواصل تماماً)، ويُرسل فعلياً إلى <code>api/subscribe</code> ليُضاف المشترك مباشرة داخل نظام المراسلة الحقيقي بلوحة التحكم (<b>الإضافات ▸ مراسلة البريد ▸ القوائم</b>) — وليس أي تخزين وهمي. يجب أن تحمل الحقول نفس المعرّفات (id) التالية بالضبط:</p>
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th>id الحقل</th>
                    <th>مطلوب؟</th>
                    <th>الوصف</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>newsletterForm</code></td>
                    <td>نعم (على وسم <code>&lt;form&gt;</code>)</td>
                    <td>يمكن تكرار هذا المعرّف بأكثر من صفحة (كل صفحة تحمل نسخة DOM مستقلة)، لكن لا تضع أكثر من نموذج واحد بنفس الصفحة.</td>
                </tr>
                <tr>
                    <td><code>newsletterEmail</code></td>
                    <td>نعم</td>
                    <td>حقل البريد الإلكتروني (<code>type="email"</code>)</td>
                </tr>
                <tr>
                    <td><code>newsletterWebsite</code></td>
                    <td>مستحسن (مخفي)</td>
                    <td>حقل honeypot — أخفِه بصرياً (position:absolute خارج الشاشة) وليس display:none، بنفس أسلوب حقل <code>website</code> بنموذج التواصل.</td>
                </tr>
                <tr>
                    <td><code>newsletterNote</code></td>
                    <td>نعم</td>
                    <td>عنصر (عادة <code>div</code>) شقيق مباشر لـ <code>form#newsletterForm</code> (نفس الأب)، تظهر بداخله رسالة النجاح/الخطأ بعد الإرسال.</td>
                </tr>
                <tr>
                    <td>زر <code>type="submit"</code></td>
                    <td>نعم</td>
                    <td>زر إرسال عادي داخل النموذج (وليس زراً منفصلاً كما بنموذج التواصل) — <code>js/subscribe.js</code> يعترض حدث <code>submit</code> نفسه.</td>
                </tr>
            </tbody>
        </table>
        <p class="mb-0">مثال جاهز للنسخ: <code>templates/news/home.twig</code> (صندوق "النشرة البريدية" بالعمود الجانبي) و<code>templates/news/blogs/all.twig</code> (أسفل شبكة المقالات). لفّ العرض دائماً بـ <code>{% if moduleEnabled('mailing') %}...{% endif %}</code> لأن صاحب الموقع قد يوقف الوحدة من صفحة الإضافات.</p>
    </div>
</div>

<div class="card mb-4" id="ads">
    <div class="card-body">
        <h5 class="card-title">9.2 نظام الإعلانات — مواضع ثابتة يوفّرها كل PHP الجذر تلقائياً</h5>
        <p>وحدة "الإعلانات" (لوحة التحكم ▸ الإضافات ▸ الإعلانات، تحتاج تفعيلاً + تشغيل ترحيل من <code>ads/migrate</code> أول مرة) تتيح لصاحب الموقع إضافة إعلانات نصية أو صورية أو أكواد إعلانية (Google AdSense وغيره) وربطها بموضع ثابت من الموقع. جلب البيانات وتحقق التفعيل يتمّان بالكامل عبر الدالة المشتركة <code>getAdsByPosition($position)</code> بملف <code>includes/functions.php</code> — لا تكتب استعلام SQL يدوياً بأي قالب.</p>

        <h6 class="mt-4">مواضع الإعلانات الخمسة (position) والمتغيّر المقابل بكل قالب</h6>
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th>الموضع (position)</th>
                    <th>مصدر البيانات</th>
                    <th>متغيّر Twig</th>
                    <th>أين يُمرَّر</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>home_top</code></td>
                    <td><code>home.php</code></td>
                    <td><code>ads_home_top</code></td>
                    <td>مصفوفة تُمرَّر لقالب الرئيسية فقط (<code>home.twig</code>/<code>home_en.twig</code>)</td>
                </tr>
                <tr>
                    <td><code>home_between</code></td>
                    <td><code>home.php</code></td>
                    <td><code>ads_home_between</code></td>
                    <td>مصفوفة تُمرَّر لقالب الرئيسية فقط</td>
                </tr>
                <tr>
                    <td><code>blog_sidebar</code></td>
                    <td><code>blogs/view.php</code></td>
                    <td><code>ads_sidebar</code></td>
                    <td>مصفوفة تُمرَّر لصفحة المقال الفردي فقط (<code>blogs/view.twig</code>)</td>
                </tr>
                <tr>
                    <td><code>article_inline</code></td>
                    <td><code>blogs/view.php</code></td>
                    <td><code>ads_article</code></td>
                    <td>مصفوفة تُمرَّر لصفحة المقال الفردي فقط</td>
                </tr>
                <tr>
                    <td><code>footer</code></td>
                    <td><code>twigload.php</code></td>
                    <td><code>adsFooter</code></td>
                    <td><b>متغيّر عام (Global)</b> متاح بكل صفحة بأي قالب — لأن الفوتر مشترك بين كل الصفحات</td>
                </tr>
            </tbody>
        </table>

        <h6 class="mt-4">شكل كل عنصر إعلان (object) داخل المصفوفة</h6>
        <p>كل مصفوفة أعلاه تحتوي عناصر بنفس الشكل التالي (بغض النظر عن الموضع):</p>
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th>الحقل</th>
                    <th>الوصف</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>ad.type</code></td>
                    <td>واحد من: <code>text_link</code> / <code>text</code> / <code>image_link</code> / <code>image</code> / <code>code</code></td>
                </tr>
                <tr>
                    <td><code>ad.text</code></td>
                    <td>نص الإعلان — يُستخدم مع <code>text_link</code> و<code>text</code></td>
                </tr>
                <tr>
                    <td><code>ad.link</code></td>
                    <td>رابط جاهز كاملاً (href مباشر) — يُستخدم مع <code>text_link</code> و<code>image_link</code></td>
                </tr>
                <tr>
                    <td><code>ad.image</code></td>
                    <td>رابط الصورة الكامل (site_url + المسار) — يُستخدم مع <code>image</code> و<code>image_link</code></td>
                </tr>
                <tr>
                    <td><code>ad.code</code></td>
                    <td>كود HTML/JS خام (مثل سكربت AdSense) — يُستخدم مع النوع <code>code</code> فقط، ويجب طباعته بفلتر <code>|raw</code> دائماً (بدون أي تنقية إضافية، القيمة مخزَّنة خاماً عمداً).</td>
                </tr>
                <tr>
                    <td><code>ad.name</code></td>
                    <td>اسم داخلي للتعريف فقط (لا يظهر للزائر) — يصلح كـ <code>alt</code> للصور.</td>
                </tr>
            </tbody>
        </table>

        <h6 class="mt-4">نمط العرض القياسي بالقالب (workup)</h6>
        <pre class="bg-light p-3 rounded"><code>{% if moduleEnabled('ads') and ads_home_top is defined and ads_home_top|length > 0 %}
&lt;div class="ad-zone ad-zone-top"&gt;
  &lt;div class="container"&gt;
    &lt;div class="ad-row"&gt;
      {% for ad in ads_home_top %}
      &lt;div class="ad-card"&gt;
        {% if ad.type == 'code' %}{{ ad.code|raw }}
        {% elseif ad.type == 'image' %}&lt;img src="{{ ad.image }}" alt="{{ ad.name }}"&gt;
        {% elseif ad.type == 'image_link' %}&lt;a href="{{ ad.link }}"&gt;&lt;img src="{{ ad.image }}" alt="{{ ad.name }}"&gt;&lt;/a&gt;
        {% elseif ad.type == 'text_link' %}&lt;a href="{{ ad.link }}"&gt;{{ ad.text }}&lt;/a&gt;
        {% elseif ad.type == 'text' %}&lt;p&gt;{{ ad.text }}&lt;/p&gt;
        {% endif %}
      &lt;/div&gt;
      {% endfor %}
    &lt;/div&gt;
  &lt;/div&gt;
&lt;/div&gt;
{% endif %}</code></pre>
        <p>مُطبَّق حالياً على القوالب الجاهزة الرئيسية (<code>workup</code>, <code>default</code>, <code>news</code>) — يمكن الرجوع لأي منها كمرجع جاهز للنسخ: <code>home.twig</code>/<code>home_en.twig</code> (home_top وhome_between)، <code>blogs/view.twig</code> (blog_sidebar وarticle_inline — للقوالب التي تملك وحدة المقالات فقط)، و<code>footer.twig</code> (footer). لفّ أي عرض دائماً بـ <code>moduleEnabled('ads')</code> + التحقق من طول المصفوفة، تماماً كنموذج مراسلة البريد أعلاه.</p>
        <p class="mb-0"><b>تنبيه مهم لأي قالب جديد:</b> أسماء متغيرات الألوان بـ CSS قد تختلف بين القوالب — لا تفترض وجود <code>--primary</code>/<code>--bg</code>/<code>--outline</code>/<code>--shadow</code> بالضرورة؛ افحص <code>:root</code> الفعلي بقالبك أولاً قبل نسخ أي كود CSS جاهز.</p>
    </div>
</div>

<div class="card mb-4" id="sports">
    <div class="card-body">
        <h5 class="card-title">9.3 وحدات الرياضة — جدول المباريات / جدول الترتيب / الفيديوهات</h5>
        <p>ثلاث وحدات اختيارية (لوحة التحكم ▸ الإضافات، كل واحدة تحتاج تفعيلاً + تشغيل ترحيل من <code>matches/migrate</code>/<code>standings/migrate</code>/<code>videos/migrate</code> أول مرة) مبنية على نفس نمط وحدة "الإعلانات": دالة جلب موحّدة بـ<code>includes/functions.php</code> تتحقق ذاتياً من التفعيل ووجود الجدول — لا تكتب استعلام SQL يدوياً بأي قالب. القالب المرجعي الجاهز الذي يستهلك الثلاثة معاً هو <b>OjubaSport</b> (<code>templates/ojubasport</code>).</p>

        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th>الوحدة</th>
                    <th>دالة الجلب</th>
                    <th>متغيّر Twig (بالرئيسية فقط)</th>
                    <th>ملاحظات</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>جدول المباريات</td>
                    <td><code>getMatchesForDisplay($limit = 6)</code></td>
                    <td><code>matches</code></td>
                    <td>كل عنصر: <code>competition</code>, <code>team_home</code>/<code>team_away</code> + <code>team_home_logo</code>/<code>team_away_logo</code> (روابط كاملة)، <code>match_date</code>, <code>date_human</code>, <code>venue</code>, <code>score_home</code>/<code>score_away</code> (فارغة إن كانت الحالة <code>upcoming</code>)، <code>match_status</code> (<code>upcoming</code>/<code>live</code>/<code>finished</code>)، <code>broadcast_channel</code> (القناة الناقلة، نص حر).</td>
                </tr>
                <tr>
                    <td>جدول الترتيب</td>
                    <td><code>getStandings($limit = 20)</code></td>
                    <td><code>standings</code></td>
                    <td>كل عنصر: <code>team_name</code>, <code>team_logo</code>, <code>played</code>, <code>won</code>, <code>drawn</code>, <code>lost</code>, <code>goals_for</code>, <code>goals_against</code>, <code>points</code>, <code>goal_diff</code> (محسوبة تلقائياً، غير مخزَّنة). الترتيب: <code>ordering</code> اليدوي أولاً، ثم <code>points</code>.</td>
                </tr>
                <tr>
                    <td>الفيديوهات</td>
                    <td><code>getVideos($limit = 6)</code></td>
                    <td><code>videos</code></td>
                    <td>كل عنصر: <code>title</code>, <code>youtube_url</code>, <code>embed_url</code> (رابط تضمين جاهز)، <code>thumbnail</code> (صورة مرفوعة يدوياً أو صورة يوتيوب التلقائية إن لم تُرفع). لالتقاط معرّف الفيديو من أي رابط يوتيوب استُخدمت الدالة المساعدة <code>youtubeVideoId($url)</code> — لا حاجة لاستدعائها مباشرة بالقالب، الجلب يتكفّل بها.</td>
                </tr>
            </tbody>
        </table>

        <h6 class="mt-4">نمط العرض القياسي بالقالب (OjubaSport)</h6>
        <pre class="bg-light p-3 rounded"><code>{% if moduleEnabled('matches') and matches is defined and matches|length > 0 %}
&lt;section id="matches"&gt;
  {% for m in matches %}
    &lt;div class="match-card match-status-{{ m.match_status }}"&gt;
      {% if m.match_status == 'live' %}مباشر الآن
      {% elseif m.match_status == 'finished' %}{{ m.score_home }} - {{ m.score_away }}
      {% else %}{{ m.date_human }}{% endif %}
      {% if m.broadcast_channel %}&lt;span&gt;{{ m.broadcast_channel }}&lt;/span&gt;{% endif %}
    &lt;/div&gt;
  {% endfor %}
&lt;/section&gt;
{% endif %}</code></pre>
        <p>راجع <code>templates/ojubasport/home.twig</code> و<code>home_en.twig</code> (أقسام <code>id="matches"</code>/<code>id="standings"</code>/<code>id="videos"</code>) كمرجع كامل جاهز للنسخ، و<code>templates/ojubasport/assets/css/style.css</code> (أقسام <code>.match-card</code>/<code>.standings-table</code>/<code>.video-card</code> بنهاية الملف) للتنسيق. <b>هذه الوحدات الثلاث غير مفعّلة بأي قالب آخر حالياً (workup, default, news, goldenphase)</b> — تُضاف لقالب آخر فقط عند طلب صريح، بنفس سياسة أي ميزة جديدة.</p>
        <p><b>إعادة استخدام مهمة:</b> "كتّاب/محللو" الموقع الرياضي لا يحتاجون وحدة جديدة — وحدة "فريق العمل" (team) الموجودة أصلاً أُعيدت تسميتها بالواجهة فقط بقالب OjubaSport ("كتّابنا"). لأي قالب رياضي مستقبلي، أعد استخدام <code>team</code> بنفس الطريقة بدل بناء وحدة مكرّرة.</p>

        <h6 class="mt-4">ربط مقال بمباراة — صندوق "ملخص المباراة" أعلى صفحة المقال</h6>
        <p>عمود اختياري <code>blogs.related_match_id</code> (nullable، يُضاف عبر نفس <code>blogs/migrate</code>) يسمح لصاحب الموقع بربط أي مقال بمباراة محدَّدة من لوحة التحكم. الدالة <b><code>getMatchById($id)</code></b> (<code>includes/functions.php</code>) تجلب المباراة بنفس شكل عناصر <code>getMatchesForDisplay()</code> بالضبط، و<code>blogs/view.php</code> (PHP الجذر المشترك بين كل القوالب) يمرّرها تلقائياً كمتغيّر <code>related_match</code> لـ<code>blogs/view.twig</code> — راجع قسم 5 (<code>blogs/view.twig</code>) للتفاصيل الكاملة. القالب المرجعي الوحيد الذي يعرض هذا الصندوق فعلياً حالياً هو <b>OjubaSport</b>؛ لإضافته لقالب آخر انسخ كتلة <code>match-summary-box</code> من <code>templates/ojubasport/blogs/view.twig</code> — لا حاجة لأي تعديل PHP، البيانات جاهزة بالفعل بكل قالب.</p>
        <p><b>تنبيه تصميم مهم:</b> النسخة الحالية من OjubaSport مبنية على مبدأ "لوحة نتائج مباشرة أولاً" — قسم المباريات/الترتيب يتصدّر الصفحة الرئيسية قبل أي محتوى إخباري، وصفحة المقال تستخدم كلاسات <code>sport-*</code> مخصّصة (عمود قراءة أضيق، حرف استهلالي كبير، عناوين بحدّ جانبي ملوّن) بدل كلاسات <code>.article-*</code> العامة التي تبقى مستخدمة فقط بصفحات المحتوى الثابت (<code>pages.twig</code>). أي تصميم قالب رياضي جديد يُفضَّل اتّباع نفس المبدأ بدل تصميم شبيه بقالب إخباري عام.</p>

        <h6 class="mt-4">صفحات عامة لأرشيف المباريات + تحديث النتائج تلقائياً</h6>
        <p>بجانب ودجت الرئيسية، توجد صفحتان عامتان مستقلتان على مستوى السكربت: <code>{{ siteUrl }}matches</code> (أرشيف كامل مُرقَّم صفحات، PHP: <code>matches/all.php</code>) و<code>{{ siteUrl }}matches/{id}</code> (تفاصيل مباراة + الأخبار المرتبطة بها عبر بحث عكسي على <code>blogs.related_match_id</code>، PHP: <code>matches/view.php</code>) — راجع قسم 10 للروابط. صفحة التفاصيل تحمل أيضاً بيانات <code>SportsEvent</code> (schema.org) كاملة.</p>
        <p class="mb-0">لوحة النتائج بالرئيسية تتحدَّث تلقائياً دون إعادة تحميل عبر نقطة عامة <code>api/live-scores</code> (JSON، GET، بدون تسجيل دخول) يستطلعها <code>templates/ojubasport/assets/js/live-scores.js</code> كل 25 ثانية عند وجود مباراة مباشرة (أو كل 90 ثانية بدون ذلك). أي تعديل مستقبلي على بنية <code>spotlight-match</code>/<code>score-row</code> بـ<code>home.twig</code> يجب أن يُطبَّق أيضاً بدوال توليد HTML المقابلة داخل هذا الملف كي لا يفقدا التزامن.</p>
    </div>
</div>

<div class="card mb-4" id="feeds">
    <div class="card-body">
        <h5 class="card-title">9.4 سحب المقالات (RSS/Feed) — استيراد تلقائي بالكامل من مواقع أخرى</h5>
        <p>وحدة اختيارية (الإضافات ▸ "سحب المقالات") تسمح لصاحب الموقع بإضافة مصادر RSS/Atom خارجية من <code>abma/feeds</code>، ليسحب السكربت مقالاتها تلقائياً بالكامل (بدون أي زر يدوي إلزامي) ويضيفها كمقالات جديدة بجدول <code>blogs</code> بتصنيف محدَّد مسبقاً، بصور محمَّلة ومُخزَّنة محلياً، مع قواعد استبدال نصي اختيارية لكل مصدر. <b>لا حاجة لأي تعديل بملفات القوالب</b> — المقالات المستوردة تظهر تلقائياً بأي قالب مثل أي مقال عادي (نفس جدول <code>blogs</code>، نفس الصفحات المشتركة).</p>
        <p>محرّك الاستيراد بملف منفصل <code>includes/feed_importer.php</code> (وليس <code>functions.php</code>) — يُضمَّن فقط حيث يُستخدَم فعلياً عبر <code>require_once 'includes/feed_importer.php';</code>. الدالة المحورية <b><code>runFeedImport($sourceId, $maxSources, $maxItemsPerSource)</code></b> تجلب/تحلّل/تستورد، مع تحقق تلقائي من التكرار عبر جدول <code>feed_imported_items</code>.</p>
        <p class="mb-0">التشغيل التلقائي بلا أي إعداد يدوي عبر "نبضة" <code>fetch()</code> خفيفة يُطلقها <code>abma/footer.php</code> بكل زيارة للوحة التحكم، تضرب نقطة عامة محمية بتوكن <code>api/feeds-cron.php</code> (محدَّدة المعدل: مرة كل 5 دقائق كحد أقصى) — بالإضافة لخيار Cron حقيقي بالاستضافة (الرابط الجاهز مع التوكن معروض أعلى صفحة "سحب المقالات"). راجع CLAUDE.md (قسم "وحدة سحب المقالات") للتفاصيل الكاملة بما فيها قرارات الأمان (تنقية HTML المستورد عبر <code>purifyImportedHtml()</code>، التحقق الفعلي من نوع الصور المحمَّلة).</p>
    </div>
</div>

<div class="card mb-4" id="routing">
    <div class="card-body">
        <h5 class="card-title">10. مسارات الروابط (Routing) — قابلة للتخصيص من لوحة التحكم</h5>
        <div class="alert alert-warning">
            <b>إلزامي لأي قالب — حالي أو جديد:</b> الكلمة الإنجليزية بمسار كل نوع محتوى رئيسي
            (<code>blog</code>, <code>service</code>, <code>portfolio</code>, <code>matches</code>,
            <code>page</code>, <code>contact</code>, <code>search</code>) لم تعد ثابتة —
            صاحب الموقع يستطيع تغييرها من <b>الإعدادات ▸ قسم "7. روابط المسارات القابلة
            للتخصيص"</b>. أي رابط بأي ملف Twig (تصفّح، فوتر، breadcrumb، بطاقات، ترقيم صفحات،
            <code>{% set og_url %}</code> لتبديل اللغة...) <b>يجب</b> أن يُبنى عبر متغيّر Twig
            العام الجديد <code>routes</code> بدل كتابة الكلمة حرفياً، وإلا سينكسر الرابط فور أن
            يغيّر صاحب الموقع الإعداد. هذا الشرط إلزامي لكل قالب مستقبلي أيضاً — وليس مثل بقية
            المزايا التي "تُحدَّث فقط عند الطلب".
        </div>
        <p>المتغيّر العام <code>routes</code> (مسجَّل بـ<code>twigload.php</code>، متاح تلقائياً بكل
            صفحة بأي قالب دون أي عمل PHP إضافي) يحمل الرابط الحالي (المخصص أو الافتراضي) لكل نوع:</p>
        <table class="table table-bordered table-sm">
            <tbody>
                <tr>
                    <td><code>{{ siteUrl }}</code></td>
                    <td>الرئيسية</td>
                </tr>
                <tr>
                    <td><code>{{ siteUrl }}{{ routes.blog }}</code></td>
                    <td>كل المقالات (افتراضياً <code>blog</code>)</td>
                </tr>
                <tr>
                    <td><code>{{ siteUrl }}{{ routes.blog }}/{slug}</code></td>
                    <td>مقالة واحدة</td>
                </tr>
                <tr>
                    <td><code>{{ siteUrl }}{{ routes.service }}</code></td>
                    <td>كل الخدمات (افتراضياً <code>service</code>)</td>
                </tr>
                <tr>
                    <td><code>{{ siteUrl }}{{ routes.service }}/{slug}</code></td>
                    <td>خدمة واحدة</td>
                </tr>
                <tr>
                    <td><code>{{ siteUrl }}{{ routes.portfolio }}</code></td>
                    <td>كل الأعمال (افتراضياً <code>portfolio</code>)</td>
                </tr>
                <tr>
                    <td><code>{{ siteUrl }}{{ routes.portfolio }}/{id أو slug}</code></td>
                    <td>عمل واحد</td>
                </tr>
                <tr>
                    <td><code>{{ siteUrl }}{{ routes.matches }}</code></td>
                    <td>أرشيف كامل لكل المباريات (وحدة matches — راجع قسم 9.3)، مُرقَّم صفحات (افتراضياً <code>matches</code>)</td>
                </tr>
                <tr>
                    <td><code>{{ siteUrl }}{{ routes.matches }}/{id}</code></td>
                    <td>تفاصيل مباراة واحدة (بالمعرّف الرقمي — لا يوجد slug لهذه الوحدة)</td>
                </tr>
                <tr>
                    <td><code>{{ siteUrl }}{{ routes.page }}/{slug}</code></td>
                    <td>صفحة ثابتة (افتراضياً <code>page</code>)</td>
                </tr>
                <tr>
                    <td><code>{{ siteUrl }}{{ routes.contact }}</code></td>
                    <td>التواصل (افتراضياً <code>contact</code>)</td>
                </tr>
                <tr>
                    <td><code>{{ siteUrl }}{{ routes.search }}?search={كلمة}</code></td>
                    <td>نتائج بحث (افتراضياً <code>search</code>)</td>
                </tr>
                <tr>
                    <td><code>?page=2</code></td>
                    <td>يُضاف لأي رابط قائمة (<code>routes.blog</code>, <code>routes.service</code>, <code>routes.portfolio</code>...) للترقيم — احرص أن يكون الرابط <b>مطلقاً</b> (<code>{{ siteUrl }}{{ routes.blog }}?page=2</code>) وليس نسبياً (<code>href="blog?page=2"</code>)، لأن الروابط النسبية تنكسر بمجرد تغيير الرابط المخصص</td>
                </tr>
                <tr>
                    <td><code>?lang=ar</code> / <code>?lang=en</code></td>
                    <td>لتبديل اللغة من أي رابط — يُبنى عادة عبر <code>{% set og_url = routes.blog %}</code> بأعلى كل قالب صفحة، ثم <code>{{ siteUrl }}{{ og_url }}?lang=ar</code> بـheader.twig</td>
                </tr>
            </tbody>
        </table>
        <h6 class="mt-4">أمثلة تحويل مباشرة (قبل ← بعد)</h6>
        <table class="table table-bordered table-sm">
            <tbody>
                <tr><td><code>href="&#123;&#123; siteUrl &#125;&#125;blog"</code></td><td>← خطأ (ثابت)</td></tr>
                <tr><td><code>href="&#123;&#123; siteUrl &#125;&#125;&#123;&#123; routes.blog &#125;&#125;"</code></td><td>✓ صحيح (ديناميكي)</td></tr>
                <tr><td><code>&#123;% set og_url = "blog/" ~ blog.slug %&#125;</code></td><td>← خطأ</td></tr>
                <tr><td><code>&#123;% set og_url = routes.blog ~ "/" ~ blog.slug %&#125;</code></td><td>✓ صحيح</td></tr>
                <tr><td><code>href="blog?page=&#123;&#123; p &#125;&#125;"</code> (نسبي)</td><td>← خطأ (ينكسر أيضاً حتى بدون تخصيص، لأنه نسبي)</td></tr>
                <tr><td><code>href="&#123;&#123; siteUrl &#125;&#125;&#123;&#123; routes.blog &#125;&#125;?page=&#123;&#123; p &#125;&#125;"</code></td><td>✓ صحيح (مطلق + ديناميكي)</td></tr>
            </tbody>
        </table>
        <p class="mb-0"><b>ملاحظة لأي كود PHP جديد بالجذر</b> (وليس Twig فقط): استخدم الدالة
            <code>routeUrl($type, $identifier = '')</code> (<code>includes/functions.php</code>) بدل
            تجميع <code>$site['site_url'] . "blog/" . $slug</code> يدوياً — مثال:
            <code>routeUrl('blog', $slug)</code>. أنواع المسار المتاحة: <code>blog</code>,
            <code>service</code>, <code>portfolio</code>, <code>matches</code>, <code>page</code>,
            <code>contact</code>, <code>search</code>. الروابط الثابتة غير القابلة للتخصيص
            (<code>pricing</code>, <code>sitemap</code>, <code>robots</code>, <code>feed</code>/<code>rss</code>/<code>blogs.rss</code>,
            <code>preview-*</code>) تبقى كما هي دون تغيير.</p>
    </div>
</div>

<div class="card mb-4" id="uploads">
    <div class="card-body">
        <h5 class="card-title">11. مسارات الصور المرفوعة</h5>
        <p>كل صور المحتوى تُرفع في مجلدات ثابتة خارج مجلد القالب، بنفس الاسم دائماً، وتصل جاهزة كرابط كامل ضمن المتغيرات أعلاه — لا تحتاج بناء المسار يدوياً إلا في حالات نادرة:</p>
        <table class="table table-bordered table-sm">
            <tbody>
                <tr>
                    <td><code>files/blogs/</code></td>
                    <td>صور المقالات</td>
                </tr>
                <tr>
                    <td><code>files/services/</code></td>
                    <td>صور الخدمات</td>
                </tr>
                <tr>
                    <td><code>files/portfolio/</code></td>
                    <td>صور الأعمال</td>
                </tr>
                <tr>
                    <td><code>files/team/</code></td>
                    <td>صور الفريق</td>
                </tr>
                <tr>
                    <td><code>files/testimonials/</code></td>
                    <td>صور آراء العملاء</td>
                </tr>
                <tr>
                    <td><code>files/certificates/</code></td>
                    <td>صور الشهادات</td>
                </tr>
                <tr>
                    <td><code>files/clients/</code></td>
                    <td>شعارات الشركاء</td>
                </tr>
                <tr>
                    <td><code>files/images/</code></td>
                    <td>شعار الموقع، البنر الافتراضي، الأيقونة (favicon)</td>
                </tr>
                <tr>
                    <td><code>files/media/</code></td>
                    <td>مكتبة الوسائط العامة بلوحة التحكم</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-4" id="tips">
    <div class="card-body">
        <h5 class="card-title">12. نصائح عملية</h5>
        <ul>
            <li>ابدأ دائماً بنسخ قالب <code>istishari</code> كاملاً وأعد تسميته — يطبّق كل الاصطلاحات المذكورة في هذا الدليل بشكل صحيح ومختبر.</li>
            <li>لا تنسَ لف كل قسم اختياري بـ <code>{% if moduleEnabled('x') and x|length > 0 %}</code> حتى لو كنت متأكداً أن العميل سيستخدمه — القالب قد يُعاد استخدامه لاحقاً لعميل آخر بوحدات مختلفة.</li>
            <li>اختبر القالب بتفعيل/تعطيل كل وحدة من theme.json للتأكد أن الصفحة لا تنهار عند غياب أي بيانات.</li>
            <li>استخدم <code>{{ dump(variable) }}</code> مؤقتاً أثناء التطوير لمعرفة الحقول المتاحة فعلياً بدل التخمين.</li>
            <li>القوالب لا تحتاج أي تعديل PHP على الإطلاق — كل المنطق (قواعد البيانات، الترقيم، السيو) جاهز ومشترك بين كل القوالب من الملفات الجذرية (home.php, blogs/, services/, portfolio/...). عملك يقتصر على ملفات .twig وCSS/JS داخل مجلد قالبك فقط.</li>
            <li>بعد الانتهاء، أضف القالب من "القوالب" في القائمة الجانبية لتفعيله، ثم من "محرر القالب" يمكنك تعديل أي ملف Twig مباشرة من المتصفح لاحقاً دون الحاجة لـ FTP.</li>
        </ul>
    </div>
</div>

<div class="card mb-4" id="hooks">
    <div class="card-body">
        <h5 class="card-title">13. نظام Hooks/Actions (للمطوّرين — وليس لمصممي القوالب فقط)</h5>
        <p>هذا القسم موجَّه لمن يريد بناء <b>إضافة/تخصيص برمجي</b> للسكربت (وليس قالب Twig) — مثل ربط حدث خارجي، تعديل بيانات صفحة قبل عرضها، أو أي منطق إضافي — <b>دون تعديل أي ملف من ملفات النواة</b>. تعديل ملفات النواة مباشرة يعني فقدان تعديلك عند أي تحديث تلقائي مستقبلي للسكربت (راجع نظام التحديثات في CLAUDE.md) — بينما أي كود بمجلد <code>includes/addons/</code> محمي دائماً لأنه ليس جزءاً من مستودع السكربت الرسمي.</p>
        <div class="alert alert-info">
            ضع ملف PHP واحد أو أكثر داخل <code>includes/addons/</code> (يُنشأ تلقائياً، محمي بـ<code>.htaccess</code> من الوصول المباشر عبر الرابط) — يُحمَّل كل ملف بهذا المجلد تلقائياً مع كل طلب، قبل أي فرصة لتشغيل أي حدث لاحق.
        </div>
        <h6 class="mt-4">الدوال المتاحة</h6>
        <table class="table table-bordered table-sm">
            <tbody>
                <tr><td><code>add_action($hook, $callback, $priority = 10)</code></td><td>سجّل دالة تُنفَّذ عند وقوع حدث معيَّن</td></tr>
                <tr><td><code>do_action($hook, ...$args)</code></td><td>شغّل كل الدوال المسجَّلة لحدث (تُستدعى من كود النواة، وليس من إضافتك)</td></tr>
                <tr><td><code>add_filter($hook, $callback, $priority = 10)</code></td><td>سجّل دالة تُعدِّل قيمة موجودة (تستقبل القيمة، تُعيد قيمة معدَّلة)</td></tr>
                <tr><td><code>apply_filters($hook, $value, ...$args)</code></td><td>مرّر قيمة عبر كل الفلاتر المسجَّلة (يُستدعى من كود النواة)</td></tr>
            </tbody>
        </table>
        <h6 class="mt-4">نقاط الامتداد المتوفرة حالياً</h6>
        <table class="table table-bordered table-sm">
            <tbody>
                <tr><td><code>ojuba_admin_login</code></td><td>Action</td><td>بعد نجاح أي دخول للوحة التحكم — <code>do_action('ojuba_admin_login', $adminId, $email)</code></td></tr>
                <tr><td><code>ojuba_blog_saved</code></td><td>Action</td><td>بعد إنشاء/تعديل مقال — <code>do_action('ojuba_blog_saved', $blogId, $isNew)</code></td></tr>
                <tr><td><code>ojuba_render_vars</code></td><td>Filter</td><td>قبل عرض أي صفحة عامة عبر <code>safeRender()</code> مباشرة (يشمل الصفحة الرئيسية) — <code>apply_filters('ojuba_render_vars', $vars, $templateName)</code>، أقوى نقطة امتداد متاحة لأنها تتيح إضافة/تعديل أي متغيّر Twig لأي صفحة دون لمس أي ملف Twig أو PHP</td></tr>
            </tbody>
        </table>
        <h6 class="mt-4">مثال كامل — <code>includes/addons/my-addon.php</code></h6>
        <pre class="bg-light p-3 rounded"><code>&lt;?php
add_action('ojuba_blog_saved', function ($blogId, $isNew) {
    // أرسل إشعاراً خارجياً عند نشر مقال جديد، مثلاً
});

add_filter('ojuba_render_vars', function ($vars, $template) {
    if ($template === 'home.twig') {
        $vars['my_custom_widget'] = 'مرحباً من إضافتي';
    }
    return $vars;
}, 10);</code></pre>
        <p class="mb-0">راجع <code>includes/hooks.php</code> للتوثيق الكامل بالكود، و<code>includes/addons/README.php</code> كمرجع سريع داخل المجلد نفسه.</p>
    </div>
</div>

<div class="card mb-4" id="restapi">
    <div class="card-body">
        <h5 class="card-title">14. REST API خفيف للقراءة فقط (<code>api/v1/*</code>)</h5>
        <p>واجهة JSON عامة (بدون تسجيل دخول، قراءة فقط — لا يوجد أي endpoint كتابة) لأي واجهة أمامية منفصلة (headless frontend، تطبيق جوال، تكامل خارجي) تريد قراءة محتوى الموقع دون المرور عبر Twig.</p>
        <table class="table table-bordered table-sm">
            <tbody>
                <tr><td><code>GET api/v1/site</code></td><td>معلومات الموقع + قائمة الوحدات المفعّلة</td></tr>
                <tr><td><code>GET api/v1/blogs</code></td><td>قائمة مقالات (<code>?page=</code>, <code>?per_page=</code>, <code>?category=</code>)</td></tr>
                <tr><td><code>GET api/v1/blogs?slug=xxx</code></td><td>مقال واحد</td></tr>
                <tr><td><code>GET api/v1/pages</code> / <code>?slug=xxx</code></td><td>الصفحات الثابتة</td></tr>
                <tr><td><code>GET api/v1/services</code> / <code>?slug=xxx</code></td><td>الخدمات</td></tr>
                <tr><td><code>GET api/v1/portfolio</code> / <code>?id=N</code></td><td>الأعمال (بمعرّف رقمي وليس slug)</td></tr>
            </tbody>
        </table>
        <p>كل نقطة تدعم <code>?lang=ar</code> أو <code>?lang=en</code> صريح لاختيار لغة المحتوى (مستقل عن كوكي الزائر)، وتُعيد 404 JSON تلقائياً إن كانت الوحدة المعنية معطَّلة (<code>moduleEnabled()</code>). الروابط المُعادة داخل كل عنصر تُبنى عبر <code>routeUrl()</code> فتحترم أي تخصيص لمسارات الموقع (قسم 10) تلقائياً.</p>
        <p class="mb-0">لا حاجة لأي تسجيل دخول أو مفتاح API — هذه بيانات عامة أصلاً معروضة بالموقع. لا يوجد حالياً أي endpoint للكتابة (نشر مقال، إلخ) عمداً — الواجهة قراءة فقط.</p>
    </div>
</div>