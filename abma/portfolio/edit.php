<?php function h($v)
{
  return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
} ?>
<?php
$id = numer($_GET['id'] ?? 0);
dbSelect("portfolio", "*", "WHERE id = ? LIMIT 1", [$id]);
if ($countrows === 0) {
  sweet("error", "خطأ", "السجل غير موجود", "portfolio");
  exit;
}
$row = $rows[0];

if (isset($_POST['submit'])) {
  $csrf->verify();
  $name = safer($_POST['name'] ?? '');
  $name_en = safer($_POST['name_en'] ?? '');
  $description = $_POST['description'] ?? '';
  $description_en = $_POST['description_en'] ?? '';
  $slug = safer(strtolower($_POST['slug']) ?? '');
  $image = $row['image'] ?? null;
  if (!empty($slug) and $slug != "") {
    dbSelect("portfolio", "slug", "WHERE slug = ? AND id != ? LIMIT 1", [$slug, $id]);
  } else {
    $countrows = 0;
  }
  if ($countrows == 0) {
    if (!empty($_FILES['image']['name'])) {
      unlink('../../files/portfolio/' . $row['image']);
      up(genCode('portfolio', 'image', 'id', 12), 'image', '../../files/portfolio', 20);
      $image = $filename;
    }
    $url = safer($_POST['url'] ?? '');
    if (!empty($_POST['completion_date'])) {
      $completion_date = safer($_POST['completion_date'] ?? '');
    } else {
      $completion_date = null;
    }
    if (!empty($_POST['category']) and $_POST['category'] != "") {
      dbSelect("categories", "id", "WHERE id = ? LIMIT 1", [$_POST['category']]);
      if ($countrows == 1) {
        $category = safer($_POST['category']);
      } else {
        $category = null;
      }
    } else {
      $category = null;
    }
    $status = safer($_POST['status'] ?? '');

    $columns = "name = ?, name_en = ?, description = ?, description_en = ?, slug = ?, image = ?, url = ?, completion_date = ?, status = ?, category = ?";
    $values = [$name, $name_en, $description, $description_en, $slug, $image, $url, $completion_date, $status, $category, $id];
    dbUpdate("portfolio", $columns, $values, "WHERE id = ? LIMIT 1");
    sweet("success", "تم", "تم التحديث بنجاح", "portfolio");
    exit;
  } else {
    sweet("error", "خطأ", "الرابط (slug) موجود مسبقاً");
  }
}
?>
<title>تعديل العمل</title>

<link rel="stylesheet" href="../css/summernote-lite.min.css">
<script src="../js/summernote-lite.min.js"></script>
<script src="../js/summernote-ar-AR.min.js"></script>
<link rel="stylesheet" href="../js/select2.css">
<script src="../js/select2.min.js"></script>


<div class="card mb-4">
  <h5 class="card-header">تعديل</h5>
  <form class="card-body" method="post" enctype="multipart/form-data">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($row['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <label>اسم العمل</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name_en" value="<?php echo htmlspecialchars($row['name_en'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <label>اسم العمل (انجليزي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control contents" name="description"><?php echo htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
          <label>وصف العمل</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control contents" name="description_en"><?php echo htmlspecialchars($row['description_en'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
          <label>وصف العمل (انجليزي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline text-center">
          <!-- حاوية عرض الصورة -->
          <div class="position-relative d-inline-block">
            <!-- عرض الصورة الحالية أو صورة افتراضية -->
            <img src="<?php echo !empty($row['image']) ? "../files/portfolio/" . htmlspecialchars($row['image'], ENT_QUOTES, 'UTF-8') : 'default-image.jpg'; ?>" alt="الصورة الحالية" class="img-thumbnail rounded" style="width: 180px; height: 180px; object-fit: cover;" id="preview-image">
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
          <input type="text" class="form-control" name="url" value="<?php echo htmlspecialchars($row['url'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <label>رابط المعاينة</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="slug" value="<?php echo $row['slug'] ?? ''; ?>" id="slug" data-id="<?php echo $id; ?>" onkeyup="this.value = this.value.replace(/[^a-zA-Z0-9-_]/g, '')" placeholder="رابط العمل (slug)">
          <label>رابط العمل <sup class="text-primary">(slug)</sup></label>
          <div id="message"></div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="date" class="form-control" name="completion_date" value="<?php echo htmlspecialchars($row['completion_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <label>تاريخ الانجاز</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <select class="form-select select2" name="category"><?php $__cur = ($row['category'] ?? ''); ?>
            <option value="">بدون تصنيف</option>
            <?php
            dbSelect("categories", "*", "ORDER BY id DESC");
            foreach ($rows as $category) {
              $selected = ((string)$__cur === (string)$category['id']) ? 'selected' : '';
              echo '<option value="' . htmlspecialchars($category['id'], ENT_QUOTES, 'UTF-8') . '" ' . $selected . '>' . htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') . ' - ' . htmlspecialchars($category['name_en'], ENT_QUOTES, 'UTF-8') . '</option>';
            }
            ?>
          </select><label>التصنيف</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <select class="form-select" name="status"><?php $__cur = ($row['status'] ?? ''); ?>
            <option value="active" <?php if ((string)$__cur === 'active') echo 'selected'; ?>>ظاهر</option>
            <option value="disabled" <?php if ((string)$__cur === 'disabled') echo 'selected'; ?>>مخفي</option>
          </select><label>الحالة</label>
        </div>
      </div>

    </div>
    <div class="pt-4">
      <?php $csrf->input(); ?>
      <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-pen"></i> تحديث</button>
      <button type="submit" name="preview" class="btn btn-secondary" formaction="../preview-portfolio" formtarget="_blank"> معاينة </button>
    </div>
  </form>
</div>

<script>
  $('.select2').select2();
</script>
<script src="js/editor.js"></script>
<script src="js/portfolio.js"></script>
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