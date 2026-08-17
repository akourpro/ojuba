<?php
requireOwner();

// حفظ إعدادات "النسخ الاحتياطية الدورية التلقائية" — نفس اصطلاح إجراءات GET
// السريعة المحمية بـ requireOwner() المستخدم بأماكن أخرى (مثل تعيين قائمة
// الاشتراك العام بـ abma/mailing/lists.php)، وليس نموذج POST منفصل.
if (isset($_GET['save_backup_settings'])) {
    $enabled = isset($_GET['sb_enabled']) ? '1' : '0';
    $interval = max(1, (int) ($_GET['sb_interval'] ?? 7));
    $keep = max(1, (int) ($_GET['sb_keep'] ?? 5));
    saveSetting('scheduled_backup_enabled', $enabled);
    saveSetting('scheduled_backup_interval_days', $interval);
    saveSetting('scheduled_backup_keep', $keep);
    sweet("success", "تم", "تم حفظ إعدادات النسخ الاحتياطية الدورية", "backup");
    exit;
}
?>
<title>نسخة احتياطية</title>
<h4 class="py-3 mb-3"><span class="text-muted fw-light">النظام /</span> نسخة احتياطية</h4>

<div class="alert alert-info">
    يمكنك من هنا تحميل نسخة احتياطية كاملة من قاعدة البيانات، أو من ملفات القالب المفعّل حالياً، لحفظها لديك أو استخدامها لاحقاً عند الاستعادة.
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body text-center py-5">
                <i class="mdi mdi-database-outline" style="font-size:48px;color:#696cff"></i>
                <h5 class="mt-3">نسخة قاعدة البيانات</h5>
                <p class="text-muted">ملف SQL يحتوي على بنية وبيانات جميع الجداول، يمكن استيراده لاحقاً عبر phpMyAdmin أو أي أداة إدارة MySQL.</p>
                <a href="api/backup.php?type=db" class="btn btn-primary">
                    <i class="mdi mdi-download"></i> تحميل نسخة قاعدة البيانات
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body text-center py-5">
                <i class="mdi mdi-folder-zip-outline" style="font-size:48px;color:#696cff"></i>
                <h5 class="mt-3">نسخة ملفات القالب</h5>
                <p class="text-muted">أرشيف ZIP يحتوي على كل ملفات القالب المفعّل حالياً (<b><?php echo htmlspecialchars($site['theme'] ?? '') ?></b>) بما فيها أي تعديلات أجريتها عبر محرر القالب.</p>
                <a href="api/backup.php?type=theme" class="btn btn-primary">
                    <i class="mdi mdi-download"></i> تحميل نسخة القالب
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$sbEnabled = ($site['scheduled_backup_enabled'] ?? '1') === '1';
$sbInterval = (int) ($site['scheduled_backup_interval_days'] ?? 7);
$sbKeep = (int) ($site['scheduled_backup_keep'] ?? 5);
?>
<div class="card mt-4">
    <h5 class="card-header"><i class="mdi mdi-clock-outline"></i> النسخ الاحتياطية الدورية التلقائية</h5>
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <input type="hidden" name="save_backup_settings" value="1">
            <div class="col-md-3">
                <label class="switch switch-lg" style="margin-left: 10%;">
                    <input type="checkbox" class="switch-input addon-toggle" role="switch" id="sb_enabled" name="sb_enabled" <?php if ($sbEnabled) echo 'checked'; ?>>
                    <span class="switch-toggle-slider">
                        <span class="switch-on"></span>
                        <span class="switch-off"></span>
                    </span>
                    <span class="switch-label">تفعيل النسخ الدورية</span>
                </label>
            </div>
            <div class="col-md-3">
                <div class="form-floating">
                    <input type="number" min="1" class="form-control" id="sb_interval" name="sb_interval" value="<?php echo $sbInterval ?: 7; ?>">
                    <label for="sb_interval">كل كم يوم</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-floating">
                    <input type="number" min="1" class="form-control" id="sb_keep" name="sb_keep" value="<?php echo $sbKeep ?: 5; ?>">
                    <label for="sb_keep">عدد النسخ المحتفَظ بها</label>
                </div>
            </div>
            <div class="col-md-3 d-grid">
                <button type="submit" class="btn btn-primary">حفظ الإعدادات</button>
            </div>
        </form>
        <?php if (!empty($site['scheduled_backup_last_error'])): ?>
            <div class="alert alert-warning mt-3 mb-0 py-2 px-3 small"><i class="mdi mdi-alert-outline"></i> تعذّرت آخر محاولة نسخ دورية: <?php echo safer($site['scheduled_backup_last_error']); ?></div>
        <?php endif; ?>
    </div>
</div>

<?php
$backupsDir = getpath() . 'backups/';
$autoBackups = glob($backupsDir . 'pre-update-backup-*.zip') ?: [];
rsort($autoBackups);
$scheduledBackups = glob($backupsDir . 'scheduled-backup-*.zip') ?: [];
rsort($scheduledBackups);
?>
<?php if (!empty($scheduledBackups)): ?>
    <div class="card mt-4">
        <h5 class="card-header"><i class="mdi mdi-history"></i> النسخ الاحتياطية الدورية المحفوظة</h5>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>الملف</th>
                            <th>الحجم</th>
                            <th>التاريخ</th>
                            <th style="width:110px">تنزيل</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($scheduledBackups as $path): ?>
                            <?php $bname = basename($path); ?>
                            <tr>
                                <td><small dir="ltr"><?php echo htmlspecialchars($bname, ENT_QUOTES, 'UTF-8'); ?></small></td>
                                <td><?php echo round(filesize($path) / 1024 / 1024, 2); ?> م.ب</td>
                                <td><?php echo date('Y-m-d H:i', filemtime($path)); ?></td>
                                <td><a class="btn btn-sm btn-outline-primary" href="api/backup-download?file=<?php echo urlencode($bname); ?>"><i class="mdi mdi-download"></i> تنزيل</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if (!empty($autoBackups)): ?>
    <div class="card mt-4">
        <h5 class="card-header"><i class="mdi mdi-history"></i> نسخ احتياطية تلقائية (قبل كل تحديث للسكربت)</h5>
        <div class="card-body">
            <p class="text-muted small mb-3">تُنشأ هذه النسخ تلقائياً — ملفات السكربت كاملة + تفريغ قاعدة البيانات في أرشيف zip واحد — قبل تطبيق أي تحديث من صفحة "الإصدار والتحديثات"، دون أي إجراء يدوي منك.</p>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>الملف</th>
                            <th>الحجم</th>
                            <th>التاريخ</th>
                            <th style="width:110px">تنزيل</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($autoBackups as $path): ?>
                            <?php $bname = basename($path); ?>
                            <tr>
                                <td><small dir="ltr"><?php echo htmlspecialchars($bname, ENT_QUOTES, 'UTF-8'); ?></small></td>
                                <td><?php echo round(filesize($path) / 1024 / 1024, 2); ?> م.ب</td>
                                <td><?php echo date('Y-m-d H:i', filemtime($path)); ?></td>
                                <td><a class="btn btn-sm btn-outline-primary" href="api/backup-download?file=<?php echo urlencode($bname); ?>"><i class="mdi mdi-download"></i> تنزيل</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>