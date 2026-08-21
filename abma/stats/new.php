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
<title>اضافة إحصائية جديدة</title>
<?php
$status = '';
if (isset($_POST['submit'])) {
  $csrf->verify();
  $number = safer($_POST['number'] ?? null);
  $suffix = safer($_POST['suffix'] ?? null);
  $label = safer($_POST['label'] ?? null);
  $label_en = safer($_POST['label_en'] ?? null);
  $icon = safer($_POST['icon'] ?? null);
  $ordering = numer($_POST['ordering'] ?? 0);
  $status = safer($_POST['status'] ?? null);

  $columns = "number, suffix, label, label_en, icon, ordering, status, date";
  $values = [$number, $suffix, $label, $label_en, $icon, $ordering, $status, date('Y-m-d H:i:s')];
  dbInsert("stats", $columns, $values);
  sweet("success", "تم", "تمت الإضافة بنجاح", "stats");
  exit;
}
?>
<div class="card mb-4">
  <h5 class="card-header">إضافة جديد</h5>
  <form class="card-body" method="post">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="number" value="<?php echo $number ?? '' ?>" placeholder="مثال: 120" required>
          <label>الرقم</label>
        </div>
      </div>
      <div class="col-md-2">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="suffix" value="<?php echo $suffix ?? '' ?>" placeholder="+ أو % أو ...">
          <label>لاحقة <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="icon" value="<?php echo $icon ?? '' ?>" placeholder="مثال: fa fa-users">
          <label>الايقونة <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="label" value="<?php echo $label ?? '' ?>" placeholder="التسمية" required>
          <label>التسمية (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="label_en" value="<?php echo $label_en ?? '' ?>" placeholder="التسمية (انجليزي)">
          <label>التسمية (انجليزي) <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
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

<script src="js/stats.js"></script>
