<?php
requireOwner();

$activityLabels = [
    'personal_portfolio' => ['label' => 'موقع شخصي / بورتفوليو', 'desc' => 'مناسب للأفراد والمستقلين الذين يريدون عرض أعمالهم ومهاراتهم وسيرتهم الذاتية.'],
    'blog'                => ['label' => 'مدونة / مجلة محتوى', 'desc' => 'مناسب للمواقع التي يكون المحتوى المكتوب (المقالات) هو المحور الأساسي فيها.'],
    'consulting_company'  => ['label' => 'شركة استشارية / خدمية', 'desc' => 'مناسب لمكاتب الاستشارات والخدمات المهنية التي تحتاج عرض خدماتها وفريقها وآراء عملائها.'],
    'company'             => ['label' => 'شركة / مؤسسة عامة', 'desc' => 'مناسب للشركات والمؤسسات التي تحتاج موقعاً تعريفياً شاملاً بخدماتها وأعمالها.'],
    'tech_company'        => ['label' => 'شركة تقنية / برمجية', 'desc' => 'مناسب لشركات التقنية والبرمجيات ووكالات التسويق الرقمي.'],
    'other'               => ['label' => 'أخرى', 'desc' => 'قوالب عامة لا تنتمي لتصنيف نشاط محدد.'],
];

$templatesDir = '../templates';
$folders = array_filter(glob($templatesDir . '/*'), 'is_dir');

$grouped = [];
foreach ($folders as $folder) {
    $templateName = basename($folder);
    $manifestFile = $folder . '/theme.json';
    $activityType = 'other';
    $displayName = $templateName;
    $modules = [];
    if (is_file($manifestFile)) {
        $json = json_decode(file_get_contents($manifestFile), true);
        if (is_array($json)) {
            if (!empty($json['activity_type']) && isset($activityLabels[$json['activity_type']])) {
                $activityType = $json['activity_type'];
            }
            if (!empty($json['name'])) $displayName = $json['name'];
            if (!empty($json['modules']) && is_array($json['modules'])) $modules = $json['modules'];
        }
    }
    $screenshotPath = "$folder/screenshot.png";
    $grouped[$activityType][] = [
        'folder' => $templateName,
        'name' => $displayName,
        'screenshot' => file_exists($screenshotPath) ? $screenshotPath : null,
        'modules' => $modules,
    ];
}

$moduleLabels = [
    'pages' => 'صفحات', 'blogs' => 'مقالات', 'services' => 'خدمات', 'portfolio' => 'أعمال',
    'categories' => 'تصنيفات', 'contact' => 'تواصل', 'search' => 'بحث', 'clients' => 'شركاء',
    'team' => 'فريق', 'testimonials' => 'آراء عملاء', 'faq' => 'أسئلة شائعة', 'stats' => 'إحصائيات',
    'pricing' => 'باقات', 'branches' => 'فروع', 'certificates' => 'شهادات',
];
?>
<title>معالج اختيار نوع النشاط</title>
<h4 class="py-3 mb-3"><span class="text-muted fw-light">الإعدادات /</span> معالج اختيار نوع النشاط</h4>

<div class="alert alert-info">
    اختر تصنيف النشاط الأقرب لمنشأتك لتصفح القوالب المُعدّة خصيصاً له، ثم اضغط "تفعيل" لتطبيق القالب مباشرة على موقعك.
</div>

<?php foreach ($activityLabels as $type => $info):
    if (empty($grouped[$type])) continue;
?>
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title"><?php echo htmlspecialchars($info['label']) ?></h5>
        <p class="text-muted"><?php echo htmlspecialchars($info['desc']) ?></p>
        <div class="row">
            <?php foreach ($grouped[$type] as $theme): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <?php if ($theme['screenshot']): ?>
                        <img src="<?php echo $theme['screenshot'] ?>" class="card-img-top" alt="<?php echo htmlspecialchars($theme['name']) ?>" style="height:200px;object-fit:cover">
                    <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center bg-light" style="height:200px">
                            <i class="mdi mdi-image-off-outline" style="font-size:36px;color:#ccc"></i>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h6 class="card-title"><?php echo htmlspecialchars($theme['name']) ?></h6>
                        <?php if (!empty($theme['modules'])): ?>
                        <div class="mb-2">
                            <?php foreach ($theme['modules'] as $mod => $enabled): if (!$enabled) continue; ?>
                                <span class="badge bg-label-primary me-1 mb-1"><?php echo htmlspecialchars($moduleLabels[$mod] ?? $mod) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($site['theme'] == $theme['folder']): ?>
                            <span class="btn btn-success btn-sm disabled">مفعل حالياً</span>
                        <?php else: ?>
                            <span data-name="<?php echo htmlspecialchars($theme['folder']) ?>" class="btn btn-primary btn-sm activate-theme">تفعيل هذا القالب</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
    $(".activate-theme").click(function() {
        var name = $(this).data("name");
        Swal.fire({
            title: "هل انت متأكد تفعيل القالب (" + name + ")",
            icon: "info",
            showCancelButton: true,
            confirmButtonColor: "#4a971c",
            cancelButtonColor: "#9A9A9A",
            confirmButtonText: "نعم",
            cancelButtonText: "تراجع",
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "api/themes",
                    headers: { '_csrf': $('meta[name="_csrf"]').attr('content') },
                    data: JSON.stringify({ name: name }),
                    dataType: "json",
                    encode: true,
                }).done(function(data) {
                    if (data.status) {
                        Swal.fire({ icon: "success", title: data.message, toast: true, position: "top-start", showConfirmButton: false, timer: 3000 });
                        setTimeout(location.reload.bind(location), 500);
                    } else {
                        Swal.fire({ icon: "error", title: data.message, toast: true, position: "top-start", showConfirmButton: false, timer: 3000 });
                    }
                });
            }
        });
    });
</script>
