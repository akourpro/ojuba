<?php
// بوتستراب صريح بدل الاعتماد على auto_prepend_file (بعض الاستضافات لا تدعمه
// إطلاقاً — راجع تعليق abma/autoload.php لتفاصيل كاملة). آمن حتى لو نجح
// auto_prepend_file أيضاً على استضافات أخرى، لأن require_once يتجاهل أي
// تحميل مكرَّر لنفس الملف تلقائياً.
$__d = __DIR__;
while (!is_file($__d . '/autoload.php') && $__d !== dirname($__d)) {
    $__d = dirname($__d);
}
require_once $__d . '/autoload.php';
?>
<?php requireOwner(); ?>
<title>ترتيب أقسام الرئيسية</title>
<h4 class="py-3 mb-3"><span class="text-muted fw-light">الإعدادات /</span> ترتيب أقسام الصفحة الرئيسية</h4>

<?php
$sectionLabels = [
    'stats'        => 'الإحصائيات',
    'services'     => 'الخدمات',
    'pricing'      => 'الباقات والأسعار',
    'portfolio'    => 'أعمالنا',
    'team'         => 'فريق العمل',
    'certificates' => 'الشهادات والاعتمادات',
    'testimonials' => 'آراء العملاء',
    'clients'      => 'شركاء النجاح',
    'branches'     => 'الفروع',
    'faq'          => 'الأسئلة الشائعة',
    'blog'         => 'أحدث المقالات',
];

if (isset($_POST['submit'])) {
    $csrf->verify();
    $keys = $_POST['section_key'] ?? [];
    $orders = $_POST['ordering'] ?? [];
    $enabledKeys = $_POST['enabled'] ?? [];

    foreach ($keys as $i => $key) {
        $key = safer($key);
        if (!isset($sectionLabels[$key])) continue;
        $ordering = numer($orders[$i] ?? ($i + 1));
        $enabled = in_array($key, $enabledKeys, true) ? 1 : 0;
        dbUpdate("home_sections", "enabled = ?, ordering = ?", [$enabled, $ordering, $key], "WHERE section_key = ? LIMIT 1");
    }
    logAction("home_sections_update", "تم تحديث ترتيب/تفعيل أقسام الصفحة الرئيسية");
    sweet("success", "تم", "تم حفظ الترتيب بنجاح", "home");
    exit;
}

dbSelect("home_sections", "section_key, enabled, ordering", "ORDER BY ordering ASC, id ASC");
$sections = $rows;
?>

<div class="alert alert-info">
    يمكنك من هنا التحكم بترتيب أقسام الصفحة الرئيسية وإخفاء/إظهار أي قسم منها، لتناسب طبيعة نشاطك. هذا الترتيب يُطبَّق على القوالب التي تدعم الترتيب الديناميكي للأقسام (مثل قالب "istishari"). لاحظ أن القسم يظهر فقط إذا كان مفعّلاً هنا <b>وأيضاً</b> مفعّلاً ضمن وحدات القالب النشط.
</div>

<form method="post">
    <div class="card mt-2">
        <div class="table-responsive">
            <table class="table table-bordered align-middle orders_table">
                <thead class="table-light">
                    <tr>
                        <th style="width:80px">الترتيب</th>
                        <th>القسم</th>
                        <th style="width:100px" class="text-center">مفعّل</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1;
                    foreach ($sections as $section): ?>
                        <tr>
                            <td>
                                <input type="hidden" name="section_key[]" value="<?php echo safer($section['section_key']) ?>">
                                <input type="number" class="form-control form-control-sm" name="ordering[]" value="<?php echo (int)$section['ordering'] ?>" min="1" style="width:80px">
                            </td>
                            <td><?php echo safer($sectionLabels[$section['section_key']] ?? $section['section_key']) ?></td>
                            <td class="text-center">
                                <label class="switch switch-md d-flex justify-content-center">
                                    <input type="checkbox" class="switch-input" id="pp_enabled" name="enabled[]" value="<?php echo safer($section['section_key']) ?>" <?php if ($section['enabled']) echo 'checked' ?> />
                                    <span class="switch-toggle-slider">
                                        <span class="switch-on"></span>
                                        <span class="switch-off"></span>
                                    </span>
                                </label>
                            </td>
                        </tr>
                    <?php $i++;
                    endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="pt-3">
        <?php $csrf->input(); ?>
        <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-content-save"></i> حفظ الترتيب</button>
    </div>
</form>