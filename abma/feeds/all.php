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
<?php
requireOwner();
feedsRequireModule();
require_once 'includes/feed_importer.php';
?>
<title>سحب المقالات (RSS/Feed)</title>
<h4 class="py-3 mb-1"><span class="text-muted fw-light">المقالات /</span> سحب المقالات (RSS/Feed)</h4>

<?php if (!feedsTableExists()): ?>
<div class="alert alert-warning d-flex align-items-center justify-content-between">
  <span><i class="mdi mdi-alert-outline"></i> جداول وحدة سحب المقالات غير مُجهَّزة بعد بقاعدة البيانات، شغّل الترحيل أولاً.</span>
  <a href="feeds/migrate" class="btn btn-sm btn-warning">تشغيل الترحيل</a>
</div>
<?php else: ?>

<p class="text-muted mb-4">أضف مصادر (روابط RSS/Atom) ليقوم السكربت بسحب مقالاتها تلقائياً وإضافتها كمقالات جديدة بتصنيف تحدّده، مع إمكانية استبدال كلمات معيّنة تلقائياً في كل مقال مسحوب.</p>

<div class="row mb-3">
  <div class="col-sm">
    <a href="feeds/new" class="btn btn-secondary waves-effect waves-light"><i class="mdi mdi-plus"></i> إضافة مصدر جديد</a>
  </div>
</div>

<div class="card mb-4">
  <h5 class="card-header"><i class="mdi mdi-clock-outline"></i> التشغيل التلقائي (الجدولة)</h5>
  <div class="card-body">
    <p class="mb-2">السحب يعمل تلقائياً بشكل دوري دون الحاجة لأي إجراء يدوي (يُشغَّل تلقائياً أثناء تصفّحك للوحة التحكم). للحصول على أعلى موثوقية (يعمل حتى بدون تسجيل دخولك للوحة التحكم إطلاقاً)، يمكنك — اختيارياً — إضافة الرابط التالي إلى "مهام مجدولة / Cron Jobs" باستضافتك ليعمل كل 15-30 دقيقة:</p>
    <?php $cronToken = ensureFeedsCronToken(); ?>
    <div class="input-group mb-2">
      <input type="text" class="form-control" readonly value="<?php echo $site['site_url'] . 'api/feeds-cron?token=' . $cronToken; ?>" id="cronUrlInput" dir="ltr">
      <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('cronUrlInput').value)"><i class="mdi mdi-content-copy"></i> نسخ</button>
    </div>
    <small class="text-muted">هذا الرابط عام (بدون تسجيل دخول) لكنه محمي برمز سري ضمن الرابط نفسه — لا تشاركه مع أحد.</small>
  </div>
</div>

<div class="card mt-2">
  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle orders_table">
      <thead class="table-light">
        <tr>
          <th style="width:60px">#</th>
          <th>الاسم</th>
          <th>التصنيف الهدف</th>
          <th>عدد المستوردة</th>
          <th>آخر جلب</th>
          <th>آخر نتيجة</th>
          <th>النشر</th>
          <th>الحالة</th>
          <th style="width:150px">إجراء</th>
        </tr>
      </thead>
      <tbody>
        <?php
        dbSelect(
          "feed_sources",
          "id, name, feed_url, category_id, auto_publish, status, last_fetched_at, last_status, imported_count",
          "ORDER BY id DESC"
        );
        if ($countrows >= 1) {
          $i = 1;
          foreach ($rows as $row) {
            $categoryName = 'بدون تصنيف';
            if (!empty($row['category_id'])) {
              dbSelect("blog_categories", "name", "WHERE id = ? LIMIT 1", [$row['category_id']]);
              if ($countrows == 1) {
                $categoryName = $rows[0]['name'];
              }
            }
            $lastFetched = !empty($row['last_fetched_at']) ? ago($row['last_fetched_at']) : 'لم يُسحَب بعد';
            $lastStatus = !empty($row['last_status']) ? safer($row['last_status']) : '—';
            $publishLabel = !empty($row['auto_publish']) ? '<span class="badge bg-success">نشر تلقائي</span>' : '<span class="badge bg-secondary">يحتاج مراجعة</span>';
            echo '
              <tr>
                  <td>' . $i++ . '</td>
                  <td>' . safer($row['name']) . '<br><small class="text-muted" dir="ltr">' . safer(mb_strimwidth($row['feed_url'], 0, 50, '…')) . '</small></td>
                  <td>' . safer($categoryName) . '</td>
                  <td>' . (int) $row['imported_count'] . '</td>
                  <td>' . $lastFetched . '</td>
                  <td><small>' . $lastStatus . '</small></td>
                  <td>' . $publishLabel . '</td>
                  <td>' . ($row['status'] === 'active' ? '<span class="badge bg-label-success">مفعّل</span>' : '<span class="badge bg-label-secondary">متوقف</span>') . '</td>
                  <td>
                    <div class="dropdown">
                      <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">إجراء</button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                          <span data-id="' . $row['id'] . '" data-action="pull" class="dropdown-item text-primary pull-now"><i class="mdi mdi-cloud-download-outline"></i> سحب الآن</span>
                          <a href="feeds/' . $row['id'] . '/edit" class="dropdown-item text-warning edit"><i class="mdi mdi-pen"></i> تعديل</a>
                          <span data-id="' . $row['id'] . '" data-name="' . safer($row['name']) . '" data-action="delete" class="dropdown-item text-danger delete"><i class="mdi mdi-delete"></i> حذف</span>
                        </div>
                  </td>
              </tr>';
          }
        } else {
          echo '<tr><td colspan="9" class="text-center text-muted py-4">لا توجد مصادر بعد</td></tr>';
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>

<script src="js/feeds.js"></script>
