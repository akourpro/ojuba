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
dbSelect("testimonials", "*", "WHERE id = ? LIMIT 1", [$id]);
if ($countrows === 0) {
  sweet("error", "خطأ", "السجل غير موجود", "testimonials");
  exit;
}
$row = $rows[0];

if (isset($_POST['submit'])) {
  $csrf->verify();
  $name = safer($_POST['name'] ?? null);
  $name_en = safer($_POST['name_en'] ?? null);
  $position = safer($_POST['position'] ?? null);
  $position_en = safer($_POST['position_en'] ?? null);
  $content = $_POST['content'] ?? null;
  $content_en = $_POST['content_en'] ?? null;
  $rating = numer($_POST['rating'] ?? 5);
  $ordering = numer($_POST['ordering'] ?? 0);
  $status = safer($_POST['status'] ?? null);

  $image = $row['image'] ?? null;
  if (!empty($_FILES['image']['name'])) {
    if (!empty($row['image']) and file_exists('../../files/testimonials/' . $row['image'])) {
      unlink('../../files/testimonials/' . $row['image']);
    }
    up(genCode('testimonials', 'image', 'id', 12), 'image', '../../files/testimonials', 20);
    $image = $filename;
  }

  $columns = "name = ?, name_en = ?, position = ?, position_en = ?, image = ?, content = ?, content_en = ?, rating = ?, ordering = ?, status = ?";
  $values = [$name, $name_en, $position, $position_en, $image, $content, $content_en, $rating, $ordering, $status, $id];
  dbUpdate("testimonials", $columns, $values, "WHERE id = ? LIMIT 1");
  sweet("success", "تم", "تم التحديث بنجاح", "testimonials");
  exit;
}
?>
<title>تعديل رأي <?php echo h($row['name']) ?></title>
<div class="card mb-4">
  <h5 class="card-header">تعديل</h5>
  <form class="card-body" method="post" enctype="multipart/form-data">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name" value="<?php echo h($row['name']) ?>">
          <label>اسم العميل (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name_en" value="<?php echo h($row['name_en']) ?>">
          <label>اسم العميل (انجليزي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="position" value="<?php echo h($row['position']) ?>">
          <label>المنصب / الشركة (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="position_en" value="<?php echo h($row['position_en']) ?>">
          <label>المنصب / الشركة (انجليزي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline text-center">
          <div class="position-relative d-inline-block">
            <img src="<?php echo !empty($row['image']) ? "../files/testimonials/" . h($row['image']) : 'avatar.png'; ?>" alt="الصورة الحالية" class="img-thumbnail rounded" style="width: 150px; height: 150px; object-fit: cover;" id="preview-image">
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
          <select class="form-select" name="rating"><?php $__cur = (int)($row['rating'] ?? 5); ?>
            <?php for ($n = 5; $n >= 1; $n--): ?>
              <option value="<?php echo $n ?>" <?php if ($n == $__cur) echo 'selected' ?>><?php echo str_repeat('★', $n) ?></option>
            <?php endfor; ?>
          </select>
          <label>التقييم</label>
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
          <textarea class="form-control" name="content" style="height:120px"><?php echo h($row['content']) ?></textarea>
          <label>نص الرأي (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control" name="content_en" style="height:120px"><?php echo h($row['content_en']) ?></textarea>
          <label>نص الرأي (انجليزي)</label>
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

<script src="js/testimonials.js"></script>
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