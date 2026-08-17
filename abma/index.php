<title>الرئيسية</title>
<?php
// ============================================================
//  إحصائيات لوحة التحكم — كل الاستعلامات بسيطة (COUNT / GROUP BY)
//  ومحاطة بالحد الأدنى اللازم، بدون أي تعديل على البيانات
// ============================================================

$arMonthsShort = [1 => "ينا", 2 => "فبر", 3 => "مار", 4 => "أبر", 5 => "ماي", 6 => "يون", 7 => "يول", 8 => "أغس", 9 => "سبت", 10 => "أكت", 11 => "نوف", 12 => "ديس"];

// ---- إجماليات سريعة ----
dbSelect("blogs", "id");
$totalBlogs = $countrows;
dbSelect("blogs", "id", "WHERE date >= ?", [date('Y-m-01')]);
$blogsThisMonth = $countrows;

dbSelect("services", "id");
$totalServices = $countrows;

dbSelect("portfolio", "id");
$totalPortfolio = $countrows;

dbSelect("team", "id");
$totalTeam = $countrows;

dbSelect("clients", "id");
$totalClients = $countrows;

dbSelect("testimonials", "id");
$totalTestimonials = $countrows;
dbSelect("testimonials", "AVG(rating) as avg_rating", "WHERE status = ?", ["active"]);
$avgRating = round((float) ($rows[0]['avg_rating'] ?? 0), 1);

dbSelect("pricing", "id", "WHERE status = ?", ["active"]);
$activePricingPlans = $countrows;

dbSelect("contact", "id");
$totalMessages = $countrows;
dbSelect("contact", "id", "WHERE seen = ?", [0]);
$unseenMessages = $countrows;

// ---- نمو المحتوى خلال آخر 6 أشهر (مقالات / أعمال / خدمات) ----
$growthMonths = [];
for ($i = 5; $i >= 0; $i--) {
    $ts = strtotime("-$i months");
    $growthMonths[date('Y-m', $ts)] = $arMonthsShort[(int) date('n', $ts)] . ' ' . date('y', $ts);
}
$fromDate = date('Y-m-01', strtotime('-5 months'));

$blogsGrowthMap = array_fill_keys(array_keys($growthMonths), 0);
dbSelect("blogs", "DATE_FORMAT(date, '%Y-%m') as ym, COUNT(*) as cnt", "WHERE date >= ? GROUP BY ym", [$fromDate]);
if ($countrows >= 1) {
    foreach ($rows as $r) {
        if (isset($blogsGrowthMap[$r['ym']])) {
            $blogsGrowthMap[$r['ym']] = (int) $r['cnt'];
        }
    }
}

$portfolioGrowthMap = array_fill_keys(array_keys($growthMonths), 0);
dbSelect("portfolio", "DATE_FORMAT(date, '%Y-%m') as ym, COUNT(*) as cnt", "WHERE date >= ? GROUP BY ym", [$fromDate]);
if ($countrows >= 1) {
    foreach ($rows as $r) {
        if (isset($portfolioGrowthMap[$r['ym']])) {
            $portfolioGrowthMap[$r['ym']] = (int) $r['cnt'];
        }
    }
}

$servicesGrowthMap = array_fill_keys(array_keys($growthMonths), 0);
dbSelect("services", "DATE_FORMAT(date, '%Y-%m') as ym, COUNT(*) as cnt", "WHERE date >= ? GROUP BY ym", [$fromDate]);
if ($countrows >= 1) {
    foreach ($rows as $r) {
        if (isset($servicesGrowthMap[$r['ym']])) {
            $servicesGrowthMap[$r['ym']] = (int) $r['cnt'];
        }
    }
}

$growthLabels = array_values($growthMonths);
$blogsGrowth = array_values($blogsGrowthMap);
$portfolioGrowth = array_values($portfolioGrowthMap);
$servicesGrowth = array_values($servicesGrowthMap);

// ---- رسائل التواصل خلال آخر 14 يوماً ----
$msgDaysMap = [];
for ($i = 13; $i >= 0; $i--) {
    $msgDaysMap[date('Y-m-d', strtotime("-$i days"))] = 0;
}
$fromDay = date('Y-m-d', strtotime('-13 days'));
dbSelect("contact", "DATE(date) as d, COUNT(*) as cnt", "WHERE date >= ? GROUP BY d", [$fromDay]);
if ($countrows >= 1) {
    foreach ($rows as $r) {
        if (isset($msgDaysMap[$r['d']])) {
            $msgDaysMap[$r['d']] = (int) $r['cnt'];
        }
    }
}
$msgLabels = [];
foreach (array_keys($msgDaysMap) as $d) {
    $ts = strtotime($d);
    $msgLabels[] = (int) date('j', $ts) . ' ' . $arMonthsShort[(int) date('n', $ts)];
}
$msgCounts = array_values($msgDaysMap);

// ---- توزيع المحتوى (لدونات) ----
$distributionLabels = ['المقالات', 'الخدمات', 'الأعمال', 'فريق العمل', 'آراء العملاء', 'العملاء'];
$distributionData = [$totalBlogs, $totalServices, $totalPortfolio, $totalTeam, $totalTestimonials, $totalClients];

// ---- أحدث الرسائل غير المقروءة (لكل الأدوار) ----
dbSelect("contact", "id, first_name, last_name, email, message, date", "WHERE seen = ? ORDER BY id DESC LIMIT 5", [0]);
$latestMessages = $rows ?? [];

// ---- آخر النشاطات (المالك فقط، بنفس صلاحية صفحة سجل النشاط) ----
$recentActivity = [];
if (isOwner()) {
    dbSelect("logs l LEFT JOIN admins a ON a.id = l.user_id", "l.id, l.date, l.action, l.description, a.username", "ORDER BY l.id DESC LIMIT 8");
    $recentActivity = $rows ?? [];
}
$actionLabels = [
    'login'                    => 'تسجيل دخول',
    'login_failed'             => 'محاولة دخول فاشلة',
    'user_create'              => 'إنشاء حساب أدمن',
    'user_update'              => 'تعديل حساب أدمن',
    'user_delete'              => 'حذف حساب أدمن',
    'theme_switch'             => 'تبديل القالب',
    'theme_edit_save'          => 'تعديل ملف قالب',
    'theme_edit_create_folder' => 'إنشاء مجلد قالب',
    'theme_edit_delete'        => 'حذف عنصر من القالب',
    'backup_download'          => 'تحميل نسخة احتياطية',
    'home_sections_update'     => 'تعديل ترتيب أقسام الرئيسية',
    'home_raw_edit_save'       => 'تعديل ملف الرئيسية مباشرة',
    'media_upload'             => 'رفع ملف لمكتبة الوسائط',
    'media_delete'             => 'حذف ملف من مكتبة الوسائط',
    'theme_import'             => 'استيراد قالب عبر zip',
];

function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="mb-1">نظرة عامة 👋</h4>
        <p class="text-muted mb-0"><?php echo function_exists('arabicDate') ? arabicDate(date('Y-m-d H:i:s')) : date('Y-m-d') ?></p>
    </div>
</div>

<!-- ===== KPI Cards ===== -->
<div class="row gy-4 mb-4">

    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div class="avatar">
                        <div class="avatar-initial bg-label-primary rounded">
                            <i class="mdi mdi-post mdi-24px"></i>
                        </div>
                    </div>
                </div>
                <div class="card-info mt-4 pt-1 mt-lg-1 mt-xl-4">
                    <h5 class="mb-2"><?php echo $totalBlogs ?></h5>
                    <p class="mb-2">اجمالي المقالات</p>
                    <?php if ($blogsThisMonth > 0): ?>
                    <span class="badge bg-label-success"><i class="mdi mdi-trending-up"></i> +<?php echo $blogsThisMonth ?> هذا الشهر</span>
                    <?php else: ?>
                    <span class="badge bg-label-secondary">لا جديد هذا الشهر</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div class="avatar">
                        <div class="avatar-initial bg-label-success rounded">
                            <i class="mdi mdi-tag-outline mdi-24px"></i>
                        </div>
                    </div>
                </div>
                <div class="card-info mt-4 pt-1 mt-lg-1 mt-xl-4">
                    <h5 class="mb-2"><?php echo $activePricingPlans ?></h5>
                    <p class="mb-2">باقات أسعار فعّالة</p>
                    <a href="pricing" class="badge bg-label-success text-decoration-none">إدارة الباقات</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div class="avatar">
                        <div class="avatar-initial bg-label-danger rounded">
                            <i class="mdi mdi-email-outline mdi-24px"></i>
                        </div>
                    </div>
                </div>
                <div class="card-info mt-4 pt-1 mt-lg-1 mt-xl-4">
                    <h5 class="mb-2"><?php echo $totalMessages ?></h5>
                    <p class="mb-2">رسائل التواصل</p>
                    <?php if ($unseenMessages > 0): ?>
                    <a href="contacts" class="badge bg-label-danger text-decoration-none"><?php echo $unseenMessages ?> غير مقروءة</a>
                    <?php else: ?>
                    <span class="badge bg-label-secondary">لا رسائل جديدة</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div class="avatar">
                        <div class="avatar-initial bg-label-warning rounded">
                            <i class="mdi mdi-comment-quote mdi-24px"></i>
                        </div>
                    </div>
                </div>
                <div class="card-info mt-4 pt-1 mt-lg-1 mt-xl-4">
                    <h5 class="mb-2"><?php echo $totalTestimonials ?></h5>
                    <p class="mb-2">آراء العملاء</p>
                    <?php if ($avgRating > 0): ?>
                    <span class="badge bg-label-warning"><i class="mdi mdi-star"></i> <?php echo $avgRating ?> / 5</span>
                    <?php else: ?>
                    <span class="badge bg-label-secondary">بدون تقييم بعد</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div class="avatar">
                        <div class="avatar-initial bg-label-info rounded">
                            <i class="mdi mdi-network-pos mdi-24px"></i>
                        </div>
                    </div>
                </div>
                <div class="card-info mt-4 pt-1 mt-lg-1 mt-xl-4">
                    <h5 class="mb-2"><?php echo $totalPortfolio ?></h5>
                    <p class="mb-2">اجمالي الأعمال</p>
                    <a href="portfolio" class="badge bg-label-info text-decoration-none">عرض الكل</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div class="avatar">
                        <div class="avatar-initial bg-label-dark rounded">
                            <i class="mdi mdi-account-wrench mdi-24px"></i>
                        </div>
                    </div>
                </div>
                <div class="card-info mt-4 pt-1 mt-lg-1 mt-xl-4">
                    <h5 class="mb-2"><?php echo $totalServices ?></h5>
                    <p class="mb-2">اجمالي الخدمات</p>
                    <a href="services" class="badge bg-label-dark text-decoration-none">عرض الكل</a>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ===== Charts: نمو المحتوى + توزيع المحتوى ===== -->
<div class="row gy-4 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">نمو المحتوى</h5>
                    <p class="text-muted mb-0 small">مقالات، أعمال، وخدمات — آخر 6 أشهر</p>
                </div>
            </div>
            <div class="card-body">
                <div id="chartContentGrowth"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">توزيع المحتوى</h5>
                <p class="text-muted mb-0 small">جميع أقسام الموقع</p>
            </div>
            <div class="card-body">
                <div id="chartContentDistribution"></div>
            </div>
        </div>
    </div>
</div>

<!-- ===== Charts: رسائل التواصل + أحدث الرسائل ===== -->
<div class="row gy-4 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">رسائل التواصل</h5>
                <p class="text-muted mb-0 small">آخر 14 يوماً</p>
            </div>
            <div class="card-body">
                <div id="chartMessages"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">أحدث الرسائل</h5>
                <a href="contacts" class="small">عرض الكل</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($latestMessages)): ?>
                <p class="text-muted text-center py-5 mb-0">لا توجد رسائل غير مقروءة 🎉</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($latestMessages as $m): ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="me-2">
                                <p class="mb-0 fw-medium"><?php echo h(trim($m['first_name'] . ' ' . $m['last_name'])) ?: '—' ?></p>
                                <p class="mb-0 text-muted small"><?php echo h($m['email']) ?></p>
                                <p class="mb-0 small text-truncate" style="max-width:230px;"><?php echo h($m['message']) ?></p>
                            </div>
                            <span class="badge bg-label-dark rounded-pill flex-shrink-0"><?php echo function_exists('ago') ? ago($m['date']) : '' ?></span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (isOwner()): ?>
<!-- ===== آخر النشاطات (المالك فقط) ===== -->
<div class="row gy-4 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">آخر النشاطات</h5>
                <a href="logs" class="small">سجل النشاط الكامل</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentActivity)): ?>
                <p class="text-muted text-center py-5 mb-0">لا يوجد أي نشاط مسجّل حتى الآن</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($recentActivity as $a): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="badge bg-label-primary me-1"><?php echo h($actionLabels[$a['action']] ?? $a['action'] ?? '—') ?></span>
                            <span class="text-muted small"><?php echo h($a['username'] ?? '—') ?></span>
                            <?php if (!empty($a['description'])): ?>
                            <span class="small"> — <?php echo h($a['description']) ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="badge bg-label-dark rounded-pill"><?php echo function_exists('ago') ? ago($a['date']) : '' ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.1/dist/apexcharts.min.js"></script>
<script>
(function () {
    if (typeof ApexCharts === 'undefined') return;

    var growthLabels = <?php echo json_encode($growthLabels, JSON_UNESCAPED_UNICODE) ?>;
    var blogsGrowth = <?php echo json_encode($blogsGrowth) ?>;
    var portfolioGrowth = <?php echo json_encode($portfolioGrowth) ?>;
    var servicesGrowth = <?php echo json_encode($servicesGrowth) ?>;

    var msgLabels = <?php echo json_encode($msgLabels, JSON_UNESCAPED_UNICODE) ?>;
    var msgCounts = <?php echo json_encode($msgCounts) ?>;

    var distributionLabels = <?php echo json_encode($distributionLabels, JSON_UNESCAPED_UNICODE) ?>;
    var distributionData = <?php echo json_encode($distributionData) ?>;

    // ألوان متسقة مع القالب
    var palette = ['#696cff', '#71dd37', '#ff3e1d', '#ffab00', '#03c3ec', '#233446'];

    // 1) نمو المحتوى — Area Chart متعدد السلاسل
    new ApexCharts(document.querySelector('#chartContentGrowth'), {
        chart: { type: 'area', height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [
            { name: 'المقالات', data: blogsGrowth },
            { name: 'الأعمال', data: portfolioGrowth },
            { name: 'الخدمات', data: servicesGrowth }
        ],
        colors: [palette[0], palette[3], palette[4]],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
        xaxis: { categories: growthLabels },
        yaxis: { labels: { formatter: function (v) { return Math.round(v); } } },
        legend: { position: 'top', horizontalAlign: 'center' },
        grid: { strokeDashArray: 4 }
    }).render();

    // 2) توزيع المحتوى — Donut
    new ApexCharts(document.querySelector('#chartContentDistribution'), {
        chart: { type: 'donut', height: 300, fontFamily: 'inherit' },
        series: distributionData,
        labels: distributionLabels,
        colors: palette,
        legend: { position: 'bottom' },
        dataLabels: { enabled: true, formatter: function (val) { return Math.round(val) + '%'; } },
        plotOptions: { pie: { donut: { labels: { show: true, total: { show: true, label: 'الإجمالي' } } } } }
    }).render();

    // 3) رسائل التواصل — Bar Chart
    new ApexCharts(document.querySelector('#chartMessages'), {
        chart: { type: 'bar', height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [{ name: 'رسائل', data: msgCounts }],
        colors: [palette[2]],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '45%' } },
        dataLabels: { enabled: false },
        xaxis: { categories: msgLabels },
        yaxis: { labels: { formatter: function (v) { return Math.round(v); } } },
        grid: { strokeDashArray: 4 }
    }).render();
})();
</script>
