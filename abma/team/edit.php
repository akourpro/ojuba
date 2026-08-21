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
<?php
$id = numer($_GET['id'] ?? 0);
dbSelect("team", "*", "WHERE id = ? LIMIT 1", [$id]);
if ($countrows === 0) {
  sweet("error", "خطأ", "السجل غير موجود", "team");
  exit;
}
$row = $rows[0];

if (isset($_POST['submit'])) {
  $csrf->verify();
  $name = safer($_POST['name'] ?? null);
  $name_en = safer($_POST['name_en'] ?? null);
  $position = safer($_POST['position'] ?? null);
  $position_en = safer($_POST['position_en'] ?? null);
  $bio = $_POST['bio'] ?? null;
  $bio_en = $_POST['bio_en'] ?? null;
  $facebook = safer($_POST['facebook'] ?? null);
  $twitter = safer($_POST['twitter'] ?? null);
  $linkedin = safer($_POST['linkedin'] ?? null);
  $whatsapp = safer($_POST['whatsapp'] ?? null);
  $website = safer($_POST['website'] ?? null);
  $phone = safer($_POST['phone'] ?? null);
  $instagram = safer($_POST['instagram'] ?? null);
  $snapchat = safer($_POST['snapchat'] ?? null);
  $ordering = numer($_POST['ordering'] ?? 0);
  $status = safer($_POST['status'] ?? null);

  $image = $row['image'] ?? null;
  if (!empty($_FILES['image']['name'])) {
    if (!empty($row['image']) and file_exists('../../files/team/' . $row['image'])) {
      unlink('../../files/team/' . $row['image']);
    }
    up(genCode('team', 'image', 'id', 12), 'image', '../../files/team', 20);
    $image = $filename;
  }

  $columns = "name = ?, name_en = ?, position = ?, position_en = ?, image = ?, bio = ?, bio_en = ?, facebook = ?, twitter = ?, linkedin = ?, website = ?, phone = ?, whatsapp = ?, instagram = ?, snapchat = ?, ordering = ?, status = ?";
  $values = [$name, $name_en, $position, $position_en, $image, $bio, $bio_en, $facebook, $twitter, $linkedin, $website, $phone, $whatsapp, $instagram, $snapchat, $ordering, $status, $id];
  dbUpdate("team", $columns, $values, "WHERE id = ? LIMIT 1");
  sweet("success", "تم", "تم التحديث بنجاح", "team");
  exit;
}
?>
<title>تعديل عضو الفريق <?php echo safer($row['name']) ?></title>
<link rel="stylesheet" href="../css/summernote-lite.min.css">
<script src="../js/summernote-lite.min.js"></script>
<script src="../js/summernote-ar-AR.min.js"></script>
<div class="card mb-4">
  <h5 class="card-header">تعديل</h5>
  <form class="card-body" method="post" enctype="multipart/form-data">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name" value="<?php echo safer($row['name']) ?>">
          <label>الاسم (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name_en" value="<?php echo safer($row['name_en']) ?>">
          <label>الاسم (انجليزي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="position" value="<?php echo safer($row['position']) ?>">
          <label>المنصب / التخصص (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="position_en" value="<?php echo safer($row['position_en']) ?>">
          <label>المنصب / التخصص (انجليزي)</label>
        </div>
      </div>

      <div class="col-md-6">
        <div class="form-floating form-floating-outline text-center">
          <div class="position-relative d-inline-block">
            <img src="<?php echo !empty($row['image']) ? "../files/team/" . safer($row['image']) : 'avatar.png'; ?>" alt="الصورة الحالية" class="img-thumbnail rounded" style="width: 180px; height: 180px; object-fit: cover;" id="preview-image">
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
          <input type="number" class="form-control" name="ordering" value="<?php echo safer($row['ordering']) ?>">
          <label>ترتيب العرض</label>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <select class="form-select" name="status"><?php $__cur = ($row['status'] ?? null); ?>
            <option value="active" <?php if ((string)$__cur === 'active') echo 'selected'; ?>>ظاهر</option>
            <option value="disabled" <?php if ((string)$__cur === 'disabled') echo 'selected'; ?>>مخفي</option>
          </select><label>الحالة</label>
        </div>
      </div>

      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control contents" name="bio"><?php echo safer($row['bio']) ?></textarea>
          <label>نبذة مختصرة (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control contents" name="bio_en"><?php echo safer($row['bio_en']) ?></textarea>
          <label>نبذة مختصرة (انجليزي)</label>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="facebook" value="<?php echo safer($row['facebook']) ?>">
          <label>فيسبوك</label>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="instagram" value="<?php echo safer($row['instagram']) ?>">
          <label>انستقرام</label>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="snapchat" value="<?php echo safer($row['snapchat']) ?>">
          <label>سناب شات</label>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="twitter" value="<?php echo safer($row['twitter']) ?>">
          <label>تويتر / X</label>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="linkedin" value="<?php echo safer($row['linkedin']) ?>">
          <label>لينكدإن</label>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="whatsapp" value="<?php echo safer($row['whatsapp']) ?>">
          <label>واتساب</label>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="phone" value="<?php echo safer($row['phone']) ?>">
          <label>رقم اتصال</label>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="website" value="<?php echo safer($row['website']) ?>">
          <label>الموقع الالكتروني</label>
        </div>
      </div>

    </div>
    <div class="pt-4">
      <?php $csrf->input(); ?>
      <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-pen"></i> تحديث</button>
    </div>
  </form>
</div>

<script src="js/editor.js"></script>
<script src="js/team.js"></script>
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