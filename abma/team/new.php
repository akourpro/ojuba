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
<title>اضافة عضو جديد لفريق العمل</title>
<?php
$status = '';
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

  $image = null;
  if (!empty($_FILES['image']['name'])) {
    up(genCode('team', 'image', 'id', 12), 'image', '../../files/team', 20);
    $image = $filename;
  }

  $columns = "name, name_en, position, position_en, image, bio, bio_en, facebook, twitter, linkedin, whatsapp, website, phone, instagram, snapchat, ordering, status, date";
  $values = [$name, $name_en, $position, $position_en, $image, $bio, $bio_en, $facebook, $twitter, $linkedin, $whatsapp, $website, $phone, $instagram, $snapchat, $ordering, $status, date('Y-m-d H:i:s')];
  dbInsert("team", $columns, $values);
  sweet("success", "تم", "تمت الإضافة بنجاح", "team");
  exit;
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
          <input type="text" class="form-control" name="name" value="<?php echo $name ?? '' ?>" placeholder="الاسم (عربي)" required>
          <label>الاسم (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name_en" value="<?php echo $name_en ?? '' ?>" placeholder="الاسم (انجليزي)">
          <label>الاسم (انجليزي) <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="position" value="<?php echo $position ?? '' ?>" placeholder="المنصب / التخصص" required>
          <label>المنصب / التخصص (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="position_en" value="<?php echo $position_en ?? '' ?>" placeholder="المنصب / التخصص (انجليزي)">
          <label>المنصب / التخصص (انجليزي) <sup class="text-success">(اختياري)</sup></label>
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
          <input type="number" class="form-control" name="ordering" value="0">
          <label>ترتيب العرض</label>
        </div>
      </div>

      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <select class="form-select" name="status">
            <option value="active" <?php if ($status == "active") echo "selected" ?>>ظاهر</option>
            <option value="disabled" <?php if ($status == "disabled") echo "selected" ?>>مخفي</option>
          </select><label>الحالة</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control contents" name="bio" placeholder="نبذة مختصرة"><?php echo $bio ?? '' ?></textarea>
          <label>نبذة مختصرة (عربي) <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control contents" name="bio_en" placeholder="نبذة مختصرة (انجليزي)"><?php echo $bio_en ?? '' ?></textarea>
          <label>نبذة مختصرة (انجليزي) <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="facebook" value="<?php echo $facebook ?? '' ?>" placeholder="رابط فيسبوك">
          <label>فيسبوك <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="instagram" value="<?php echo $instagram ?? '' ?>" placeholder="رابط انستقرام">
          <label>انستقرام <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="snapchat" value="<?php echo $snapchat ?? '' ?>" placeholder="رابط اضافة سناب">
          <label>سناب شات <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="twitter" value="<?php echo $twitter ?? '' ?>" placeholder="رابط تويتر/X">
          <label>تويتر / X <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="linkedin" value="<?php echo $linkedin ?? '' ?>" placeholder="رابط لينكدإن">
          <label>لينكدإن <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="whatsapp" value="<?php echo $whatsapp ?? '' ?>" placeholder="رقم واتساب (مع الرمز الدولي)">
          <label>واتساب <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="phone" value="<?php echo $phone ?? '' ?>" placeholder="رقم الجوال للاتصال">
          <label>رقم اتصال <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="website" value="<?php echo $website ?? '' ?>" placeholder="الموقع الالكتروني للعضو">
          <label>الموقع الالكتروني <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>

    </div>
    <div class="pt-4">
      <?php $csrf->input(); ?>
      <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-plus"></i> حفظ</button>
    </div>
  </form>
</div>

<script src="js/editor.js"></script>
<script src="js/team.js"></script>