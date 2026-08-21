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
<?php function h($v)
{
  return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
} ?>
<?php
$id = numer($_GET['id'] ?? 0);
dbSelect("services", "*", "WHERE id = ? LIMIT 1", [$id]);
if ($countrows === 0) {
  sweet("error", "خطأ", "السجل غير موجود", "services");
  exit;
}
$row = $rows[0];

if (isset($_POST['submit'])) {
  $csrf->verify();
  $name = safer($_POST['name'] ?? null);
  $name_en = safer($_POST['name_en'] ?? null);
  $icon = safer($_POST['icon'] ?? null);
  $description = $_POST['description'] ?? null;
  $description_en = $_POST['description_en'] ?? null;
  $image = $row['image'] ?? null;
  if (!empty($_FILES['image']['name'])) {
    unlink('../../files/services/' . $row['image']);
    up(genCode('services', 'image', 'id', 12), 'image', '../../files/services', 20);
    $image = $filename;
  }
  $slug = safer($_POST['slug'] ?? null);
  $status = safer($_POST['status'] ?? null);

  dbSelect("services", "*", "WHERE slug = ? AND id != ? LIMIT 1", [$slug, $id]);
  if ($countrows == 0) {
    $columns = "name = ?, name_en = ?, icon = ?, description = ?, description_en = ?, image = ?, slug = ?, status = ?";
    $values = [$name, $name_en, $icon, $description, $description_en, $image, $slug, $status, $id];
    dbUpdate("services", $columns, $values, "WHERE id = ? LIMIT 1");
    sweet("success", "تم", "تم التحديث بنجاح", "services");
    exit;
  } else {
    sweet("error", "خطأ", "اسم الرابط مستخدم من قبل، يرجى تغييره");
  }
}
?>
<title>تعديل الخدمة <?php echo htmlspecialchars($row['name'] ?? null, ENT_QUOTES, 'UTF-8') ?></title>
<link rel="stylesheet" href="../css/summernote-lite.min.css">
<script src="../js/summernote-lite.min.js"></script>
<script src="../js/summernote-ar-AR.min.js"></script>
<div class="card mb-4">
  <h5 class="card-header">تعديل</h5>
  <form class="card-body" method="post" enctype="multipart/form-data">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($row['name'] ?? null, ENT_QUOTES, 'UTF-8') ?>">
          <label>اسم الخدمة (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name_en" value="<?php echo htmlspecialchars($row['name_en'] ?? null, ENT_QUOTES, 'UTF-8') ?>">
          <label>اسم الخدمة (انجليزي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="icon" value="<?php echo htmlspecialchars($row['icon'] ?? null, ENT_QUOTES, 'UTF-8') ?>">
          <label>الايقونة</label>
        </div>
      </div>

      <div class="col-md-6">
        <div class="form-floating form-floating-outline text-center">
          <!-- حاوية عرض الصورة -->
          <div class="position-relative d-inline-block">
            <!-- عرض الصورة الحالية أو صورة افتراضية -->
            <img src="<?php echo !empty($row['image']) ? "../files/services/" . htmlspecialchars($row['image'], ENT_QUOTES, 'UTF-8') : 'default-image.jpg'; ?>" alt="الصورة الحالية" class="img-thumbnail rounded" style="width: 180px; height: 180px; object-fit: cover;" id="preview-image">
            <label for="imageInput"
              class="btn btn-sm btn-primary position-absolute bottom-0 start-50 translate-middle-x mb-2">
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

      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control contents" name="description"><?php echo htmlspecialchars($row['description'] ?? null, ENT_QUOTES, 'UTF-8') ?></textarea>
          <label>وصف الخدمة</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control contents" name="description_en"><?php echo htmlspecialchars($row['description_en'] ?? null, ENT_QUOTES, 'UTF-8') ?></textarea>
          <label>وصف الخدمة (انجليزي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control slug" name="slug" id="slug" data-id="<?php echo $id ?>" value="<?php echo htmlspecialchars($row['slug'] ?? null, ENT_QUOTES, 'UTF-8') ?>" required>
          <label>اسم الرابط (slug)</label>
          <div id="message"></div>
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
      <button type="submit" name="preview" class="btn btn-secondary" formaction="../preview-service" formtarget="_blank"> معاينة </button>
    </div>
  </form>
</div>

<script src="js/editor.js"></script>
<script src="js/services.js"></script>
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