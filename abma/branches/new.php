<?php function h($v)
{
  return htmlspecialchars($v ?? null, ENT_QUOTES, 'UTF-8');
} ?>
<title>اضافة فرع جديد</title>
<?php
$status = '';
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

  $columns = "name, name_en, address, address_en, phone, email, map, working_hours, working_hours_en, ordering, status, date";
  $values = [$name, $name_en, $address, $address_en, $phone, $email, $map, $working_hours, $working_hours_en, $ordering, $status, date('Y-m-d H:i:s')];
  dbInsert("branches", $columns, $values);
  sweet("success", "تم", "تمت الإضافة بنجاح", "branches");
  exit;
}
?>
<div class="card mb-4">
  <h5 class="card-header">إضافة جديد</h5>
  <form class="card-body" method="post">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name" value="<?php echo $name ?? '' ?>" placeholder="اسم الفرع" required>
          <label>اسم الفرع (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name_en" value="<?php echo $name_en ?? '' ?>" placeholder="اسم الفرع (انجليزي)">
          <label>اسم الفرع (انجليزي) <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="address" value="<?php echo $address ?? '' ?>" placeholder="العنوان">
          <label>العنوان (عربي) <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="address_en" value="<?php echo $address_en ?? '' ?>" placeholder="Address">
          <label>العنوان (انجليزي) <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="phone" value="<?php echo $phone ?? '' ?>" placeholder="الهاتف">
          <label>الهاتف <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-floating form-floating-outline">
          <input type="email" class="form-control" name="email" value="<?php echo $email ?? '' ?>" placeholder="البريد الالكتروني">
          <label>البريد الالكتروني <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="ordering" value="0">
          <label>ترتيب العرض</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="working_hours" value="<?php echo $working_hours ?? '' ?>" placeholder="مثال: يومياً 9ص - 9م">
          <label>أوقات الدوام (عربي) <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="working_hours_en" value="<?php echo $working_hours_en ?? '' ?>" placeholder="Daily 9AM - 9PM">
          <label>أوقات الدوام (انجليزي) <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-12">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="map" value="<?php echo $map ?? '' ?>" placeholder="رابط تضمين خرائط جوجل (iframe src)">
          <label>رابط الخريطة (Google Maps embed) <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <select class="form-select" name="status">
            <option value="active" <?php if ($status == "active") echo "selected" ?>>ظاهر</option>
            <option value="disabled" <?php if ($status == "disabled") echo "selected" ?>>مخفي</option>
          </select><label>الحالة</label>
        </div>
      </div>

    </div>
    <div class="pt-4">
      <?php $csrf->input(); ?>
      <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-plus"></i> حفظ</button>
    </div>
  </form>
</div>

<script src="js/branches.js"></script>
