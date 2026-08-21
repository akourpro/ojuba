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
standingsRequireModule();
?>
<title>جدول الترتيب</title>
<h4 class="py-3 mb-1"><span class="text-muted fw-light">الرياضة /</span> جدول الترتيب</h4>

<?php if (!standingsTableExists()): ?>
<div class="alert alert-warning d-flex align-items-center justify-content-between">
  <span><i class="mdi mdi-alert-outline"></i> جدول الترتيب غير مُجهَّز بعد بقاعدة البيانات، شغّل الترحيل أولاً.</span>
  <a href="standings/migrate" class="btn btn-sm btn-warning">تشغيل الترحيل</a>
</div>
<?php else: ?>

<p class="text-muted mb-4">أضف فرق الدوري وإحصائياتها. الترتيب المعروض بالموقع يعتمد أولاً على "ترتيب العرض" اليدوي، ثم النقاط.</p>

<div class="row mb-3">
  <div class="col-sm">
    <a href="standings/new" class="btn btn-secondary waves-effect waves-light"><i class="mdi mdi-plus"></i> إضافة فريق جديد</a>
  </div>
</div>

<div class="card mt-2">
  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle orders_table">
      <thead class="table-light">
        <tr>
          <th style="width:70px">#</th>
          <th>الفريق</th>
          <th>لعب</th>
          <th>فاز</th>
          <th>تعادل</th>
          <th>خسر</th>
          <th>نقاط</th>
          <th>الترتيب</th>
          <th>الحالة</th>
          <th style="width:120px">إجراء</th>
        </tr>
      </thead>
      <tbody>
        <?php
        dbSelect("sport_standings", "id, team_name, played, won, drawn, lost, points, ordering, status", "ORDER BY ordering ASC, points DESC, id ASC");
        if ($countrows >= 1) {
          $i = 1;
          foreach ($rows as $row) {
            echo '
              <tr>
                  <td>' . $i++ . '</td>
                  <td>' . safer($row['team_name']) . '</td>
                  <td>' . $row['played'] . '</td>
                  <td>' . $row['won'] . '</td>
                  <td>' . $row['drawn'] . '</td>
                  <td>' . $row['lost'] . '</td>
                  <td><b>' . $row['points'] . '</b></td>
                  <td>' . $row['ordering'] . '</td>
                  <td>' . $row['status'] . '</td>
                  <td>
                    <div class="dropdown">
                      <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">إجراء</button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                          <a href="standings/' . $row['id'] . '/edit" class="dropdown-item text-warning edit"><i class="mdi mdi-pen"></i> تعديل</a>
                          <span data-id="' . $row['id'] . '" data-name="' . safer($row['team_name']) . '" data-action="delete" class="dropdown-item text-danger delete"><i class="mdi mdi-delete"></i> حذف</span>
                        </div>
                  </td>
              </tr>';
          }
        } else {
          echo '<tr><td colspan="10" class="text-center text-muted py-4">لا توجد فرق بعد</td></tr>';
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>

<script src="js/standings.js"></script>
