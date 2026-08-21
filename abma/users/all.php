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
<title>المستخدمون</title>
<h4 class="py-3 mb-3"><span class="text-muted fw-light">الإعدادات /</span> إدارة حسابات لوحة التحكم</h4>

<div class="row mb-3">
  <div class="col-sm">
    <a href="users/new" class="btn btn-secondary waves-effect waves-light"><i class="mdi mdi-plus"></i> اضافة حساب جديد</a>
  </div>
</div>

<div class="alert alert-info">
  <b>owner</b>: وصول كامل يشمل تحرير القالب، الإعدادات، القوالب، وإدارة المستخدمين.
  <b>editor</b>: إدارة المحتوى فقط (المقالات، الخدمات، الأعمال، الفريق...) بدون الوصول للصفحات الحساسة.
</div>

<div class="card mt-2">
  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle orders_table">
      <thead class="table-light">
        <tr>
          <th style="width:70px">#</th>
          <th>اسم المستخدم</th>
          <th>البريد الالكتروني</th>
          <th>الصلاحية</th>
          <th>الحالة</th>
          <th style="width:120px">إجراء</th>
        </tr>
      </thead>
      <tbody>
        <?php
        dbSelect("admins", "id, username, email, role, status", "ORDER BY id ASC");
        if ($countrows >= 1) {
          $i = 1;
          foreach ($rows as $row) {
            $roleLabel = $row['role'] === 'owner' ? '<span class="badge bg-primary">owner</span>' : '<span class="badge bg-secondary">editor</span>';
            $isSelf = ((int)($_SESSION['user_id'] ?? 0) === (int)$row['id']);
            echo '
              <tr>
                  <td>' . $i++ . '</td>
                  <td>' . $row['username'] . ($isSelf ? ' <span class="text-muted">(أنت)</span>' : '') . '</td>
                  <td>' . $row['email'] . '</td>
                  <td>' . $roleLabel . '</td>
                  <td>' . $row['status'] . '</td>
                  <td>
                    <div class="dropdown">
                      <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">إجراء</button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                          <a href="users/' . $row['id'] . '/edit" class="dropdown-item text-warning edit"><i class="mdi mdi-pen"></i> تعديل</a>' .
              (!$isSelf ? '<span data-id="' . $row['id'] . '" data-name="' . $row['username'] . '" data-action="delete" class="dropdown-item text-danger delete"><i class="mdi mdi-delete"></i> حذف</span>' : '') . '
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

<script src="js/users.js"></script>
<script src="js/tables.js"></script>
