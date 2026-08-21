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
videosRequireModule();
?>
<title>الفيديوهات</title>
<h4 class="py-3 mb-1"><span class="text-muted fw-light">الرياضة /</span> الفيديوهات</h4>

<?php if (!videosTableExists()): ?>
<div class="alert alert-warning d-flex align-items-center justify-content-between">
  <span><i class="mdi mdi-alert-outline"></i> جدول الفيديوهات غير مُجهَّز بعد بقاعدة البيانات، شغّل الترحيل أولاً.</span>
  <a href="videos/migrate" class="btn btn-sm btn-warning">تشغيل الترحيل</a>
</div>
<?php else: ?>

<p class="text-muted mb-4">أضف مقاطع فيديو من يوتيوب (روابط watch أو youtu.be أو shorts) — الصورة المصغّرة تُجلب تلقائياً إن لم ترفع صورة يدوياً.</p>

<div class="row mb-3">
  <div class="col-sm">
    <a href="videos/new" class="btn btn-secondary waves-effect waves-light"><i class="mdi mdi-plus"></i> إضافة فيديو جديد</a>
  </div>
</div>

<div class="card mt-2">
  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle orders_table">
      <thead class="table-light">
        <tr>
          <th style="width:70px">#</th>
          <th>العنوان</th>
          <th>الرابط</th>
          <th>الترتيب</th>
          <th>الحالة</th>
          <th style="width:120px">إجراء</th>
        </tr>
      </thead>
      <tbody>
        <?php
        dbSelect("sport_videos", "id, title, youtube_url, ordering, status", "ORDER BY ordering ASC, id DESC");
        if ($countrows >= 1) {
          $i = 1;
          foreach ($rows as $row) {
            echo '
              <tr>
                  <td>' . $i++ . '</td>
                  <td>' . safer($row['title']) . '</td>
                  <td><a href="' . safer($row['youtube_url']) . '" target="_blank" rel="noopener">' . safer(mb_substr($row['youtube_url'], 0, 40)) . '…</a></td>
                  <td>' . $row['ordering'] . '</td>
                  <td>' . $row['status'] . '</td>
                  <td>
                    <div class="dropdown">
                      <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">إجراء</button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                          <a href="videos/' . $row['id'] . '/edit" class="dropdown-item text-warning edit"><i class="mdi mdi-pen"></i> تعديل</a>
                          <span data-id="' . $row['id'] . '" data-name="' . safer($row['title']) . '" data-action="delete" class="dropdown-item text-danger delete"><i class="mdi mdi-delete"></i> حذف</span>
                        </div>
                  </td>
              </tr>';
          }
        } else {
          echo '<tr><td colspan="6" class="text-center text-muted py-4">لا توجد فيديوهات بعد</td></tr>';
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>

<script src="js/videos.js"></script>
