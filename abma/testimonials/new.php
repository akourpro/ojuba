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
<title>اضافة رأي عميل جديد</title>
<?php
$status = '';
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

  $image = null;
  if (!empty($_FILES['image']['name'])) {
    up(genCode('testimonials', 'image', 'id', 12), 'image', '../../files/testimonials', 20);
    $image = $filename;
  }

  $columns = "name, name_en, position, position_en, image, content, content_en, rating, ordering, status, date";
  $values = [$name, $name_en, $position, $position_en, $image, $content, $content_en, $rating, $ordering, $status, date('Y-m-d H:i:s')];
  dbInsert("testimonials", $columns, $values);
  sweet("success", "تم", "تمت الإضافة بنجاح", "testimonials");
  exit;
}
?>
<div class="card mb-4">
  <h5 class="card-header">إضافة جديد</h5>
  <form class="card-body" method="post" enctype="multipart/form-data">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name" value="<?php echo $name ?? '' ?>" placeholder="اسم العميل" required>
          <label>اسم العميل (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name_en" value="<?php echo $name_en ?? '' ?>" placeholder="اسم العميل (انجليزي)">
          <label>اسم العميل (انجليزي) <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="position" value="<?php echo $position ?? '' ?>" placeholder="المنصب / الشركة">
          <label>المنصب / الشركة (عربي) <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="position_en" value="<?php echo $position_en ?? '' ?>" placeholder="المنصب / الشركة (انجليزي)">
          <label>المنصب / الشركة (انجليزي) <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="file" class="form-control" id="imageInputNew" name="image" accept="image/*">
          <label>الصورة الشخصية <sup class="text-success">(اختياري)</sup></label>
        </div>
          <button type="button" class="btn btn-sm btn-outline-secondary mt-2 media-picker-btn" data-target="#imageInputNew"><i class="mdi mdi-image-multiple-outline"></i> اختر من المكتبة</button>
      </div>
      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <select class="form-select" name="rating">
            <?php for ($n = 5; $n >= 1; $n--): ?>
              <option value="<?php echo $n ?>" <?php if ($n == 5) echo 'selected' ?>><?php echo str_repeat('★', $n) ?></option>
            <?php endfor; ?>
          </select>
          <label>التقييم</label>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="ordering" value="0">
          <label>ترتيب العرض</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control" name="content" placeholder="نص الرأي" style="height:120px" required><?php echo $content ?? '' ?></textarea>
          <label>نص الرأي (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control" name="content_en" placeholder="نص الرأي (انجليزي)" style="height:120px"><?php echo $content_en ?? '' ?></textarea>
          <label>نص الرأي (انجليزي) <sup class="text-success">(اختياري)</sup></label>
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

<script src="js/testimonials.js"></script>
