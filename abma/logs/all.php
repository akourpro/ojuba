<?php requireOwner(); ?>
<title>سجل النشاط</title>
<h4 class="py-3 mb-3"><span class="text-muted fw-light">النظام /</span> سجل النشاط</h4>

<?php
// فلترة اختيارية حسب نوع الحدث
$filterAction = safer($_GET['action_filter'] ?? '');

$where = "";
$vars = [];
if ($filterAction !== '') {
    $where = "WHERE l.action = ?";
    $vars[] = $filterAction;
}

dbSelect("logs l", "COUNT(*) as cnt", $where, $vars);
$totalLogs = (int) ($rows[0]['cnt'] ?? 0);
$pagination = paginate($totalLogs, 20);

dbSelect(
    "logs l LEFT JOIN admins a ON a.id = l.user_id",
    "l.id, l.date, l.ip, l.sys, l.action, l.description, a.username",
    $where . " ORDER BY l.id DESC LIMIT " . (int) $pagination['per_page'] . " OFFSET " . (int) $pagination['offset'],
    $vars
);
$logs = $rows;

// جلب أنواع الأحداث المتاحة للفلترة
dbSelect("logs", "DISTINCT action", "WHERE action IS NOT NULL AND action != '' ORDER BY action ASC");
$actionTypes = array_column($rows, 'action');

$actionLabels = [
    'login'                    => 'تسجيل دخول',
    'login_failed'             => 'محاولة دخول فاشلة',
    'user_create'              => 'إنشاء حساب أدمن',
    'user_update'               => 'تعديل حساب أدمن',
    'user_delete'               => 'حذف حساب أدمن',
    'theme_switch'              => 'تبديل القالب',
    'theme_edit_save'           => 'تعديل ملف قالب',
    'theme_edit_create_folder'  => 'إنشاء مجلد قالب',
    'theme_edit_delete'         => 'حذف عنصر من القالب',
    'backup_download'           => 'تحميل نسخة احتياطية',
    'home_sections_update'      => 'تعديل ترتيب أقسام الرئيسية',
    'home_raw_edit_save'        => 'تعديل ملف الرئيسية مباشرة',
    'media_upload'              => 'رفع ملف لمكتبة الوسائط',
    'media_delete'              => 'حذف ملف من مكتبة الوسائط',
    'theme_import'              => 'استيراد قالب عبر zip',
];
?>

<div class="row mb-3">
    <div class="col-sm">
        <form method="get" class="d-flex gap-2">
            <select name="action_filter" class="form-select" style="max-width:260px" onchange="this.form.submit()">
                <option value="">كل الأحداث</option>
                <?php foreach ($actionTypes as $type): ?>
                    <option value="<?php echo htmlspecialchars($type) ?>" <?php if ($filterAction === $type) echo 'selected' ?>>
                        <?php echo htmlspecialchars($actionLabels[$type] ?? $type) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($filterAction !== ''): ?>
                <a href="logs" class="btn btn-outline-secondary">إلغاء الفلترة</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card mt-2">
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle orders_table">
            <thead class="table-light">
                <tr>
                    <th style="width:70px">#</th>
                    <th>المستخدم</th>
                    <th>الحدث</th>
                    <th>الوصف</th>
                    <th>IP</th>
                    <th>الجهاز</th>
                    <th>التاريخ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">لا يوجد أي سجل نشاط حتى الآن</td>
                    </tr>
                    <?php else: foreach ($logs as $row): ?>
                        <tr>
                            <td><?php echo $row['id'] ?></td>
                            <td><?php echo $row['username'] ? htmlspecialchars($row['username']) : '<span class="text-muted">—</span>' ?></td>
                            <td>
                                <?php if (!empty($row['action'])): ?>
                                    <span class="badge bg-label-primary"><?php echo htmlspecialchars($actionLabels[$row['action']] ?? $row['action']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['description'] ?? '') ?></td>
                            <td><?php echo htmlspecialchars($row['ip'] ?? '') ?></td>
                            <td class="text-muted small"><?php echo htmlspecialchars($row['sys'] ?? '') ?></td>
                            <td>
                                <?php echo $row['date'] ?><br>
                                <span class="badge bg-label-dark rounded-pill">
                                    <?php echo function_exists('ago') ? ago($row['date'], true) : '' ?>
                                </span>
                            </td>
                        </tr>
                <?php endforeach;
                endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($pagination['total_pages'] > 1): ?>
    <nav aria-label="pagination" class="mt-3">
        <ul class="pagination justify-content-center">
            <li class="page-item <?php if (!$pagination['has_prev']) echo 'disabled' ?>">
                <a class="page-link" href="logs?page=<?php echo $pagination['prev_page'] ?>&action_filter=<?php echo urlencode($filterAction) ?>">السابق</a>
            </li>
            <?php foreach ($pagination['pages'] as $p): ?>
                <li class="page-item <?php if ($p == $pagination['page']) echo 'active' ?>">
                    <?php if ($p == $pagination['page']): ?>
                        <a class="page-link"><?php echo $p ?></a>
                    <?php else: ?>
                        <a class="page-link" href="logs?page=<?php echo $p ?>&action_filter=<?php echo urlencode($filterAction) ?>"><?php echo $p ?></a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
            <li class="page-item <?php if (!$pagination['has_next']) echo 'disabled' ?>">
                <a class="page-link" href="logs?page=<?php echo $pagination['next_page'] ?>&action_filter=<?php echo urlencode($filterAction) ?>">التالي</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>