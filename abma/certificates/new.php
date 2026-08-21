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
<title>اضافة شهادة جديدة</title>
<?php
$status = '';
if (isset($_POST['submit'])) {
  $csrf->verify();
  $name = safer($_POST['name'] ?? null);
  $name_en = safer($_POST['name_en'] ?? null);
  $issuer = safer($_POST['issuer'] ?? null);
  $issuer_en = safer($_POST['issuer_en'] ?? null);
  $date_issued = safer($_POST['date_issued'] ?? null);
  $ordering = numer($_POST['ordering'] ?? 0);
  $status = safer($_POST['status'] ?? null);

  $image = null;
  if (!empty($_FILES['image']['name'])) {
    up(genCode('certificates', 'image', 'id', 12), 'image', '../../files/certificates', 20);
    $image = $filename;
  }

  $columns = "name, name_en, issuer, issuer_en, image, date_issued, ordering, status, date";
  $values = [$name, $name_en, $issuer, $issuer_en, $image, $date_issued ?: null, $ordering, $status, date('Y-m-d H:i:s')];
  dbInsert("certificates", $columns, $values);
  sweet("success", "تم", "تمت الإضافة بنجاح", "certificates");
  exit;
}
?>
<div class="card mb-4">
  <h5 class="card-header">إضافة جديد</h5>
  <form class="card-body" method="post" enctype="multipart/form-data">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name" value="<?php echo $name ?? '' ?>" placeholder="اسم الشهادة" required>
          <label>اسم الشهادة (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name_en" value="<?php echo $name_en ?? '' ?>" placeholder="اسم الشهادة (انجليزي)">
          <label>اسم الشهادة (انجليزي) <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="issuer" value="<?php echo $issuer ?? '' ?>" placeholder="الجهة المانحة">
          <label>الجهة المانحة (عربي) <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="issuer_en" value="<?php echo $issuer_en ?? '' ?>" placeholder="Issuer">
          <label>الجهة المانحة (انجليزي) <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="file" class="form-control" id="imageInputNew" name="image" accept="image/*">
          <label>صورة الشهادة <sup class="text-success">(اختياري)</sup></label>
        </div>
          <button type="button" class="btn btn-sm btn-outline-secondary mt-2 media-picker-btn" data-target="#imageInputNew"><i class="mdi mdi-image-multiple-outline"></i> اختر من المكتبة</button>
      </div>
      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <input type="date" class="form-control" name="date_issued" value="<?php echo $date_issued ?? '' ?>">
          <label>تاريخ الإصدار <sup class="text-success">(اختياري)</sup></label>
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

<script src="js/certificates.js"></script>
