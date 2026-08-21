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
<title>الأسئلة الشائعة لصفحة الأسعار</title>
<h4 class="py-3 mb-3"><span class="text-muted fw-light">باقات الأسعار /</span> الأسئلة الشائعة لصفحة الأسعار</h4>

<div class="row mb-3">
  <div class="col-sm d-flex gap-2">
    <a href="pricing-faqs/new" class="btn btn-secondary waves-effect waves-light"><i class="mdi mdi-plus"></i> اضافة سؤال جديد</a>
    <a href="pricing" class="btn btn-outline-secondary waves-effect waves-light"><i class="mdi mdi-arrow-right"></i> رجوع لباقات الأسعار</a>
  </div>
</div>

<div class="alert alert-info">
  هذه الأسئلة تظهر فقط في صفحة الأسعار المستقلة (<a href="<?php echo $site['site_url'] ?>pricing" target="_blank">/pricing</a>)، منفصلة عن الأسئلة الشائعة العامة بالصفحة الرئيسية.
</div>

<div class="card mt-2">
  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle orders_table">
      <thead class="table-light">
        <tr>
          <th style="width:70px">#</th>
          <th>السؤال</th>
          <th>الترتيب</th>
          <th>الحالة</th>
          <th style="width:120px">إجراء</th>
        </tr>
      </thead>
      <tbody>
        <?php
        dbSelect("pricing_faqs", "id, question, ordering, status", "ORDER BY ordering ASC, id DESC");
        if ($countrows >= 1) {
          $i = 1;
          foreach ($rows as $row) {
            echo '
              <tr>
                  <td>' . $i++ . '</td>
                  <td>' . $row['question'] . '</td>
                  <td>' . $row['ordering'] . '</td>
                  <td>' . $row['status'] . '</td>
                  <td>
                    <div class="dropdown">
                      <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">إجراء</button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                          <a href="pricing-faqs/' . $row['id'] . '/edit" class="dropdown-item text-warning edit"><i class="mdi mdi-pen"></i> تعديل</a>
                          <span data-id="' . $row['id'] . '" data-name="' . $row['question'] . '" data-action="delete" class="dropdown-item text-danger delete"><i class="mdi mdi-delete"></i> حذف</span>
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

<script src="js/pricing_faqs.js"></script>
<script src="js/tables.js"></script>