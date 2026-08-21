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
<title>العملاء</title>
<h4 class="py-3 mb-3"><span class="text-muted fw-light">العملاء /</span> عرض الكل</h4>

<div class="row mb-3">
  <div class="col-sm">
    <a href="clients/new" class="btn btn-secondary waves-effect waves-light"><i class="mdi mdi-plus"></i> اضافة عميل جديد</a>
  </div>
</div>

<div class="card mt-2">
  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle orders_table">
      <thead class="table-light">
        <tr>
          <th style="width:70px">#</th>
          <th>الشعار</th>
          <th>اسم العميل</th>
          <th>الحالة</th>
          <th style="width:120px">إجراء</th>
        </tr>
      </thead>
      <tbody>
        <?php
        dbSelect("clients", "id, name, logo, status", "ORDER BY id DESC");
        if ($countrows >= 1) {
          $i = 1;
          foreach ($rows as $row) {
            $logo_img = !empty($row['logo']) ? '<img src="../files/clients/' . $row['logo'] . '" alt="' . $row['name'] . '" style="height:36px;max-width:100px;object-fit:contain;">' : '-';
            $status_label = ($row['status'] == 1) ? 'ظاهر' : 'مخفي';
            echo '
              <tr>
                  <td>' . $i++ . '</td>
                  <td>' . $logo_img . '</td>
                  <td>' . $row['name'] . '</td>
                  <td>' . $status_label . '</td>
                  <td>
                    <div class="dropdown">
                      <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">إجراء</button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                          <a href="clients/' . $row['id'] . '/edit" class="dropdown-item text-warning edit"><i class="mdi mdi-pen"></i> تعديل</a>
                          <span data-id="' . $row['id'] . '" data-name="' . $row['name'] . '" data-action="delete" class="dropdown-item text-danger delete"><i class="mdi mdi-delete"></i> حذف</span>
                        </div>
                  </td>
              </tr>';
          }
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<script src="js/clients.js"></script>
<script src="js/tables.js"></script>
