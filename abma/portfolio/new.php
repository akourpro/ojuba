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
<title>إضافة عمل جديد</title>
<?php
$status = '';
if (isset($_POST['submit'])) {
  $csrf->verify();
  $name = safer($_POST['name'] ?? '');
  $name_en = safer($_POST['name_en'] ?? '');
  $description = $_POST['description'] ?? '';
  $description_en = $_POST['description_en'] ?? '';
  $slug = safer(strtolower($_POST['slug']) ?? '');
  $image = null;
  if (!empty($slug) and $slug != "") {
    dbSelect("portfolio", "slug", "WHERE slug = ? LIMIT 1", [$slug]);
  } else {
    $countrows = 0;
  }
  if ($countrows == 0) {
    if (!empty($_FILES['image']['name'])) {
      up(genCode('portfolio', 'image', 'id', 12), 'image', '../../files/portfolio', 20);
      $image = $filename;
    }
    $url = safer($_POST['url'] ?? '');
    if (!empty($_POST['completion_date'])) {
      $completion_date = safer($_POST['completion_date'] ?? '');
    } else {
      $completion_date = null;
    }
    $status = safer($_POST['status'] ?? '');
    $date = date('Y-m-d H:i:s');

    $columns = "name, name_en, description, description_en, slug, image, url, completion_date, status, date";
    $values = [$name, $name_en, $description, $description_en, $slug, $image, $url, $completion_date, $status, $date];
    dbInsert("portfolio", $columns, $values);
    sweet("success", "تم", "تمت الإضافة بنجاح", "portfolio");
    die();
  } else {
    sweet("error", "خطأ", "الرابط (slug) موجود مسبقاً");
  }
}
?>

<link rel="stylesheet" href="../css/summernote-lite.min.css">
<script src="../js/summernote-lite.min.js"></script>
<script src="../js/summernote-ar-AR.min.js"></script>
<link rel="stylesheet" href="../js/select2.css">
<script src="../js/select2.min.js"></script>


<div class="card mb-4">
  <h5 class="card-header">إضافة جديد</h5>
  <form class="card-body" method="post" enctype="multipart/form-data">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name" value="<?php echo $name ?? ''; ?>" placeholder="اسم العمل" required>
          <label>اسم العمل</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name_en" value="<?php echo $name_en ?? ''; ?>" placeholder="اسم العمل (انجليزي)">
          <label>اسم العمل (انجليزي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control contents" name="description" placeholder="وصف العمل"><?php echo $description ?? ''; ?></textarea>
          <label>وصف العمل</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control contents" name="description_en" placeholder="وصف العمل (انجليزي)"><?php echo $description_en ?? ''; ?></textarea>
          <label>وصف العمل (انجليزي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="file" class="form-control" id="imageInputNew" name="image" accept="image/*">
          <label>صورة العمل</label>
        </div>
          <button type="button" class="btn btn-sm btn-outline-secondary mt-2 media-picker-btn" data-target="#imageInputNew"><i class="mdi mdi-image-multiple-outline"></i> اختر من المكتبة</button>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="url" value="<?php echo $url ?? ''; ?>" placeholder="رابط العمل">
          <label>رابط المعاينة</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="slug" value="<?php echo $slug ?? ''; ?>" id="slug" data-id="" onkeyup="this.value = this.value.replace(/[^a-zA-Z0-9-_]/g, '')" placeholder="رابط العمل (slug)">
          <label>رابط العمل <sup class="text-primary">(slug)</sup></label>
          <div id="message"></div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="date" class="form-control" name="completion_date" value="<?php echo $completion_date ?? ''; ?>" placeholder="تاريخ الانجاز">
          <label>تاريخ الانجاز</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <select class="form-select select2" name="category">
            <option value="">بدون تصنيف</option>
            <?php
            dbSelect("categories", "*", "ORDER BY id DESC");
            if ($countrows >= 1) {
              foreach ($rows as $category) {
                echo '<option value="' . $category['id'] . '">' . $category['name'] . ' - ' . $category['name_en'] . '</option>';
              }
            }
            ?>
          </select>
          <label>التصنيف</label>
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
      <button type="submit" name="preview" class="btn btn-secondary" formaction="../preview-portfolio" formtarget="_blank"> معاينة </button>
    </div>
  </form>
</div>
<script>
  $('.select2').select2();
</script>
<script src="js/editor.js"></script>
<script src="js/portfolio.js"></script>