<?php function h($v)
{
  return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
} ?>
<?php
$id = numer($_GET['id'] ?? 0);
dbSelect("clients", "*", "WHERE id = ? LIMIT 1", [$id]);
if ($countrows === 0) {
  sweet("error", "خطأ", "السجل غير موجود", "clients");
  exit;
}
$row = $rows[0];

if (isset($_POST['submit'])) {
  $csrf->verify();
  $name = safer($_POST['name'] ?? null);
  $url = safer($_POST['url'] ?? null);
  $status = numer($_POST['status'] ?? 1);

  if (!empty($name)) {
    $logo = $row['logo'] ?? null;
    if (!empty($_FILES['logo']['name'])) {
      if (!empty($row['logo']) and file_exists('../../files/clients/' . $row['logo'])) {
        unlink('../../files/clients/' . $row['logo']);
      }
      up(genCode('clients', 'logo', 'id', 12), 'logo', '../../files/clients', 20);
      $logo = $filename;
    }

    $columns = "name = ?, logo = ?, url = ?, status = ?";
    $values = [$name, $logo, $url ?: null, $status, $id];
    dbUpdate("clients", $columns, $values, "WHERE id = ? LIMIT 1");
    sweet("success", "تم", "تم التحديث بنجاح", "clients");
    exit;
  } else {
    sweet("error", "خطأ", "اسم العميل مطلوب");
  }
}
?>
<title>تعديل عميل <?php echo h($row['name']) ?></title>
<div class="card mb-4">
  <h5 class="card-header">تعديل</h5>
  <form class="card-body" method="post" enctype="multipart/form-data">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name" value="<?php echo h($row['name']) ?>">
          <label>اسم العميل</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="url" value="<?php echo h($row['url']) ?>">
          <label>رابط الموقع <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline text-center">
          <div class="position-relative d-inline-block">
            <img src="<?php echo !empty($row['logo']) ? "../files/clients/" . h($row['logo']) : 'default-image.jpg'; ?>" alt="الشعار الحالي" class="img-thumbnail rounded" style="width: 150px; height: 150px; object-fit: contain;" id="preview-image">
            <label for="imageInput" class="btn btn-sm btn-primary position-absolute bottom-0 start-50 translate-middle-x mb-2">
              تغيير الشعار
            </label>
          </div>
          <input type="file" class="form-control d-none" id="imageInput" name="logo" accept="image/*" onchange="previewSelectedImage(event)">
          <div class="mt-2">
            <button type="button" class="btn btn-sm btn-outline-secondary media-picker-btn" data-target="#imageInput" data-preview="#preview-image">
              <i class="mdi mdi-image-multiple-outline"></i> اختر من المكتبة
            </button>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <select class="form-select" name="status"><?php $__cur = (string)($row['status'] ?? '1'); ?>
            <option value="1" <?php if ($__cur === '1') echo 'selected'; ?>>ظاهر</option>
            <option value="0" <?php if ($__cur === '0') echo 'selected'; ?>>مخفي</option>
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

<script src="js/clients.js"></script>
<script>
  function previewSelectedImage(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('preview-image');
    if (file) {
      const reader = new FileReader();
      reader.onload = e => preview.src = e.target.result;
      reader.readAsDataURL(file);
    }
  }
</script>
