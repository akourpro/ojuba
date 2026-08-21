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
adsRequireModule();

$positionLabels = [
  "home_top"      => "أعلى الصفحة الرئيسية",
  "home_between"  => "بين أقسام الرئيسية",
  "blog_sidebar"  => "الشريط الجانبي (صفحات المقالات)",
  "article_inline" => "داخل نص المقال",
  "footer"        => "التذييل",
];
$typeLabels = [
  "text_link"  => "نص برابط",
  "text"       => "نص فقط",
  "image_link" => "صورة برابط",
  "image"      => "صورة فقط",
  "code"       => "كود إعلاني (Adsense وغيره)",
];
?>
<title>الإعلانات</title>
<h4 class="py-3 mb-1"><span class="text-muted fw-light">التسويق /</span> الإعلانات</h4>

<?php if (!adsTableExists()): ?>
<div class="alert alert-warning d-flex align-items-center justify-content-between">
  <span><i class="mdi mdi-alert-outline"></i> جدول الإعلانات غير مُجهَّز بعد بقاعدة البيانات، شغّل الترحيل أولاً.</span>
  <a href="ads/migrate" class="btn btn-sm btn-warning">تشغيل الترحيل</a>
</div>
<?php else: ?>

<p class="text-muted mb-4">أضف إعلانات نصية أو صورية أو أكواد إعلانية (مثل Google AdSense) واختر موضعها في الموقع.</p>

<div class="row mb-3">
  <div class="col-sm">
    <a href="ads/new" class="btn btn-secondary waves-effect waves-light"><i class="mdi mdi-plus"></i> إضافة إعلان جديد</a>
  </div>
</div>

<div class="card mt-2">
  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle orders_table">
      <thead class="table-light">
        <tr>
          <th style="width:70px">#</th>
          <th>الاسم</th>
          <th>النوع</th>
          <th>الموضع</th>
          <th>الترتيب</th>
          <th>الحالة</th>
          <th style="width:120px">إجراء</th>
        </tr>
      </thead>
      <tbody>
        <?php
        dbSelect("ads", "id, name, type, position, ordering, status", "ORDER BY position ASC, ordering ASC, id DESC");
        if ($countrows >= 1) {
          $i = 1;
          foreach ($rows as $row) {
            $typeLabel = $typeLabels[$row['type']] ?? $row['type'];
            $posLabel = $positionLabels[$row['position']] ?? $row['position'];
            echo '
              <tr>
                  <td>' . $i++ . '</td>
                  <td>' . safer($row['name']) . '</td>
                  <td>' . safer($typeLabel) . '</td>
                  <td>' . safer($posLabel) . '</td>
                  <td>' . $row['ordering'] . '</td>
                  <td>' . $row['status'] . '</td>
                  <td>
                    <div class="dropdown">
                      <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">إجراء</button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                          <a href="ads/' . $row['id'] . '/edit" class="dropdown-item text-warning edit"><i class="mdi mdi-pen"></i> تعديل</a>
                          <span data-id="' . $row['id'] . '" data-name="' . safer($row['name']) . '" data-action="delete" class="dropdown-item text-danger delete"><i class="mdi mdi-delete"></i> حذف</span>
                        </div>
                  </td>
              </tr>';
          }
        } else {
          echo '<tr><td colspan="7" class="text-center text-muted py-4">لا توجد إعلانات بعد</td></tr>';
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>

<script src="js/ads.js"></script>
