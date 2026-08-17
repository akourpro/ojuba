<?php function h($v)
{
  return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
} ?>
<?php
$id = numer($_GET['id'] ?? 0);
dbSelect("certificates", "*", "WHERE id = ? LIMIT 1", [$id]);
if ($countrows === 0) {
  sweet("error", "خطأ", "السجل غير موجود", "certificates");
  exit;
}
$row = $rows[0];

if (isset($_POST['submit'])) {
  $csrf->verify();
  $name = safer($_POST['name'] ?? null);
  $name_en = safer($_POST['name_en'] ?? null);
  $issuer = safer($_POST['issuer'] ?? null);
  $issuer_en = safer($_POST['issuer_en'] ?? null);
  $date_issued = safer($_POST['date_issued'] ?? null);
  $ordering = numer($_POST['ordering'] ?? 0);
  $status = safer($_POST['status'] ?? null);

  $image = $row['image'] ?? null;
  if (!empty($_FILES['image']['name'])) {
    if (!empty($row['image']) and file_exists('../../files/certificates/' . $row['image'])) {
      unlink('../../files/certificates/' . $row['image']);
    }
    up(genCode('certificates', 'image', 'id', 12), 'image', '../../files/certificates', 20);
    $image = $filename;
  }

  $columns = "name = ?, name_en = ?, issuer = ?, issuer_en = ?, image = ?, date_issued = ?, ordering = ?, status = ?";
  $values = [$name, $name_en, $issuer, $issuer_en, $image, $date_issued ?: null, $ordering, $status, $id];
  dbUpdate("certificates", $columns, $values, "WHERE id = ? LIMIT 1");
  sweet("success", "تم", "تم التحديث بنجاح", "certificates");
  exit;
}
?>
<title>تعديل شهادة <?php echo h($row['name']) ?></title>
<div class="card mb-4">
  <h5 class="card-header">تعديل</h5>
  <form class="card-body" method="post" enctype="multipart/form-data">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name" value="<?php echo h($row['name']) ?>">
          <label>اسم الشهادة (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name_en" value="<?php echo h($row['name_en']) ?>">
          <label>اسم الشهادة (انجليزي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="issuer" value="<?php echo h($row['issuer']) ?>">
          <label>الجهة المانحة (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="issuer_en" value="<?php echo h($row['issuer_en']) ?>">
          <label>الجهة المانحة (انجليزي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline text-center">
          <div class="position-relative d-inline-block">
            <img src="<?php echo !empty($row['image']) ? "../files/certificates/" . h($row['image']) : 'default-image.jpg'; ?>" alt="الصورة الحالية" class="img-thumbnail rounded" style="width: 150px; height: 150px; object-fit: cover;" id="preview-image">
            <label for="imageInput" class="btn btn-sm btn-primary position-absolute bottom-0 start-50 translate-middle-x mb-2">
              تغيير الصورة
            </label>
          </div>
          <input type="file" class="form-control d-none" id="imageInput" name="image" accept="image/*" onchange="previewSelectedImage(event)">
          <div class="mt-2">
            <button type="button" class="btn btn-sm btn-outline-secondary media-picker-btn" data-target="#imageInput" data-preview="#preview-image">
              <i class="mdi mdi-image-multiple-outline"></i> اختر من المكتبة
            </button>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <input type="date" class="form-control" name="date_issued" value="<?php echo h($row['date_issued']) ?>">
          <label>تاريخ الإصدار</label>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="ordering" value="<?php echo h($row['ordering']) ?>">
          <label>ترتيب العرض</label>
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

<script src="js/certificates.js"></script>
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
