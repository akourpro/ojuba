<?php function h($v)
{
  return htmlspecialchars($v ?? null, ENT_QUOTES, 'UTF-8');
} ?>
<title>اضافة عميل جديد</title>
<?php
$status = '1';
if (isset($_POST['submit'])) {
  $csrf->verify();
  $name = safer($_POST['name'] ?? null);
  $url = safer($_POST['url'] ?? null);
  $status = numer($_POST['status'] ?? 1);

  if (!empty($name)) {
    $logo = null;
    if (!empty($_FILES['logo']['name'])) {
      up(genCode('clients', 'logo', 'id', 12), 'logo', '../../files/clients', 20);
      $logo = $filename;
    }

    $columns = "name, logo, url, status, date";
    $values = [$name, $logo, $url ?: null, $status, date('Y-m-d H:i:s')];
    dbInsert("clients", $columns, $values);
    sweet("success", "تم", "تمت الإضافة بنجاح", "clients");
    exit;
  } else {
    sweet("error", "خطأ", "اسم العميل مطلوب");
  }
}
?>
<div class="card mb-4">
  <h5 class="card-header">إضافة جديد</h5>
  <form class="card-body" method="post" enctype="multipart/form-data">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name" value="<?php echo $name ?? '' ?>" placeholder="اسم العميل" required>
          <label>اسم العميل</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="url" value="<?php echo $url ?? '' ?>" placeholder="رابط العميل">
          <label>رابط الموقع <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="file" class="form-control" id="imageInputNew" name="logo" accept="image/*" required>
          <label>شعار العميل</label>
        </div>
          <button type="button" class="btn btn-sm btn-outline-secondary mt-2 media-picker-btn" data-target="#imageInputNew"><i class="mdi mdi-image-multiple-outline"></i> اختر من المكتبة</button>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <select class="form-select" name="status">
            <option value="1" <?php if ((string)$status === "1") echo "selected" ?>>ظاهر</option>
            <option value="0" <?php if ((string)$status === "0") echo "selected" ?>>مخفي</option>
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

<script src="js/clients.js"></script>
