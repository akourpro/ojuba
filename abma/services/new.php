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
  return htmlspecialchars($v ?? null, ENT_QUOTES, 'UTF-8');
} ?>
<title>إضافة خدمة جديدة</title>
<?php
$status = '';
if (isset($_POST['submit'])) {
  $csrf->verify();
  $name = safer($_POST['name'] ?? null);
  $name_en = safer($_POST['name_en'] ?? null);
  $icon = safer($_POST['icon'] ?? null);
  $description = $_POST['description'] ?? null;
  $description_en = $_POST['description_en'] ?? null;
  $slug = safer($_POST['slug'] ?? null);
  $slug = strtolower($slug);

  dbSelect("services", "slug", "WHERE slug = ? LIMIT 1", [$slug]);
  if ($countrows == 0) {
    $image = null;
    if (!empty($_FILES['image']['name'])) {
      up(genCode('services', 'image', 'id', 12), 'image', '../../files/services', 20);
      $image = $filename;
    }
    $status = safer($_POST['status'] ?? null);

    $columns = "name, name_en, icon, description, description_en, image, slug, status";
    $values = [$name, $name_en, $icon, $description, $description_en, $image, $slug, $status];
    dbInsert("services", $columns, $values);
    sweet("success", "تم", "تمت الإضافة بنجاح", "services");
    exit;
  } else {
    sweet("error", "خطأ", "اسم الرابط slug موجود مسبقاً");
  }
}
?>
<link rel="stylesheet" href="../css/summernote-lite.min.css">
<script src="../js/summernote-lite.min.js"></script>
<script src="../js/summernote-ar-AR.min.js"></script>
<div class="card mb-4">
  <h5 class="card-header">إضافة جديد</h5>
  <form class="card-body" method="post" enctype="multipart/form-data">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name" value="<?php echo $name ?? '' ?>" placeholder="اسم الخدمة (عربي)" required>
          <label>اسم الخدمة (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name_en" value="<?php echo $name_en ?? '' ?>" placeholder="اسم الخدمة (انجليزي)">
          <label>اسم الخدمة (انجليزي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="icon" value="<?php echo $icon ?? '' ?>" placeholder="الايقونة">
          <label>الايقونة <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="file" class="form-control" id="imageInputNew" name="image" accept="image/*">
          <label>الصورة <sup class="text-success">(اختياري)</sup></label>
        </div>
          <button type="button" class="btn btn-sm btn-outline-secondary mt-2 media-picker-btn" data-target="#imageInputNew"><i class="mdi mdi-image-multiple-outline"></i> اختر من المكتبة</button>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control contents" name="description" placeholder="وصف الخدمة" required><?php echo $description ?? '' ?></textarea>
          <label>وصف الخدمة</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control contents" name="description_en" placeholder="وصف الخدمة (انجليزي)"><?php echo $description_en ?? '' ?></textarea>
          <label>وصف الخدمة (انجليزي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control slug" id="slug" value="<?php echo $slug ?? '' ?>" name="slug" placeholder="اسم الرابط (slug)" required>
          <label>اسم الرابط (slug)</label>
          <div id="message"></div>
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
      <button type="submit" name="submit" class="btn btn-primary" disabled><i class="mdi mdi-plus"></i> حفظ</button>
      <button type="submit" name="preview" class="btn btn-secondary" formaction="../preview-service" formtarget="_blank"> معاينة </button>
    </div>
  </form>
</div>

<script src="js/editor.js"></script>
<script src="js/services.js"></script>