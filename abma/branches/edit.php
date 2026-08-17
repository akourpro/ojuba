<?php function h($v)
{
  return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
} ?>
<?php
$id = numer($_GET['id'] ?? 0);
dbSelect("branches", "*", "WHERE id = ? LIMIT 1", [$id]);
if ($countrows === 0) {
  sweet("error", "خطأ", "السجل غير موجود", "branches");
  exit;
}
$row = $rows[0];

if (isset($_POST['submit'])) {
  $csrf->verify();
  $name = safer($_POST['name'] ?? null);
  $name_en = safer($_POST['name_en'] ?? null);
  $address = safer($_POST['address'] ?? null);
  $address_en = safer($_POST['address_en'] ?? null);
  $phone = safer($_POST['phone'] ?? null);
  $email = safer($_POST['email'] ?? null);
  $map = safer($_POST['map'] ?? null);
  $working_hours = safer($_POST['working_hours'] ?? null);
  $working_hours_en = safer($_POST['working_hours_en'] ?? null);
  $ordering = numer($_POST['ordering'] ?? 0);
  $status = safer($_POST['status'] ?? null);

  $columns = "name = ?, name_en = ?, address = ?, address_en = ?, phone = ?, email = ?, map = ?, working_hours = ?, working_hours_en = ?, ordering = ?, status = ?";
  $values = [$name, $name_en, $address, $address_en, $phone, $email, $map, $working_hours, $working_hours_en, $ordering, $status, $id];
  dbUpdate("branches", $columns, $values, "WHERE id = ? LIMIT 1");
  sweet("success", "تم", "تم التحديث بنجاح", "branches");
  exit;
}
?>
<title>تعديل فرع <?php echo h($row['name']) ?></title>
<div class="card mb-4">
  <h5 class="card-header">تعديل</h5>
  <form class="card-body" method="post">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name" value="<?php echo h($row['name']) ?>">
          <label>اسم الفرع (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name_en" value="<?php echo h($row['name_en']) ?>">
          <label>اسم الفرع (انجليزي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="address" value="<?php echo h($row['address']) ?>">
          <label>العنوان (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="address_en" value="<?php echo h($row['address_en']) ?>">
          <label>العنوان (انجليزي)</label>
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="phone" value="<?php echo h($row['phone']) ?>">
          <label>الهاتف</label>
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-floating form-floating-outline">
          <input type="email" class="form-control" name="email" value="<?php echo h($row['email']) ?>">
          <label>البريد الالكتروني</label>
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="ordering" value="<?php echo h($row['ordering']) ?>">
          <label>ترتيب العرض</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="working_hours" value="<?php echo h($row['working_hours']) ?>">
          <label>أوقات الدوام (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="working_hours_en" value="<?php echo h($row['working_hours_en']) ?>">
          <label>أوقات الدوام (انجليزي)</label>
        </div>
      </div>
      <div class="col-md-12">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="map" value="<?php echo h($row['map']) ?>">
          <label>رابط الخريطة (Google Maps embed)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <select class="form-select" name="status"><?php $__cur = ($row['status'] ?? null); ?>
            <option value="active" <?php if ((string)$__cur === 'active') echo 'selected'; ?>>ظاهر</option>
            <option value="disabled" <?php if ((string)$__cur === 'disabled') echo 'selected'; ?>>مخفي</option>
          </select><label>الحالة</label>
        </div>
      </div>

    </div>
    <div class="pt-4">
      <?php $csrf->input(); ?>
      <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-pen"></i> تحديث</button>
    </div>
  </form>
</div>

<script src="js/branches.js"></script>
