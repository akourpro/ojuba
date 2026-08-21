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
<title>اضافة باقة جديدة</title>
<?php
$status = '';
$is_featured = '';
if (isset($_POST['submit'])) {
  $csrf->verify();
  $name = safer($_POST['name'] ?? null);
  $name_en = safer($_POST['name_en'] ?? null);
  $price = safer($_POST['price'] ?? null);
  $currency = safer($_POST['currency'] ?? null);
  $period = safer($_POST['period'] ?? null);
  $period_en = safer($_POST['period_en'] ?? null);
  $features = $_POST['features'] ?? null;
  $features_en = $_POST['features_en'] ?? null;
  $is_featured = isset($_POST['is_featured']) ? 1 : 0;
  $ordering = numer($_POST['ordering'] ?? 0);
  $status = safer($_POST['status'] ?? null);

  $columns = "name, name_en, price, currency, period, period_en, features, features_en, is_featured, ordering, status, date";
  $values = [$name, $name_en, $price, $currency, $period, $period_en, $features, $features_en, $is_featured, $ordering, $status, date('Y-m-d H:i:s')];
  dbInsert("pricing", $columns, $values);
  sweet("success", "تم", "تمت الإضافة بنجاح", "pricing");
  exit;
}
?>
<div class="card mb-4">
  <h5 class="card-header">إضافة جديد</h5>
  <form class="card-body" method="post">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name" value="<?php echo $name ?? '' ?>" placeholder="اسم الباقة" required>
          <label>اسم الباقة (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name_en" value="<?php echo $name_en ?? '' ?>" placeholder="اسم الباقة (انجليزي)">
          <label>اسم الباقة (انجليزي) <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="price" value="<?php echo $price ?? '' ?>" placeholder="السعر" required>
          <label>السعر</label>
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="currency" value="<?php echo $currency ?? '' ?>" placeholder="مثال: ر.س">
          <label>العملة <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="ordering" value="0">
          <label>ترتيب العرض</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="period" value="<?php echo $period ?? '' ?>" placeholder="مثال: شهرياً">
          <label>الدورية (عربي) <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="period_en" value="<?php echo $period_en ?? '' ?>" placeholder="Monthly">
          <label>الدورية (انجليزي) <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control" name="features" placeholder="ميزة في كل سطر" style="height:140px"><?php echo $features ?? '' ?></textarea>
          <label>المميزات (سطر لكل ميزة - عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control" name="features_en" placeholder="One feature per line" style="height:140px"><?php echo $features_en ?? '' ?></textarea>
          <label>المميزات (سطر لكل ميزة - انجليزي) <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-check mt-2">
          <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" <?php if ($is_featured) echo "checked" ?>>
          <label class="form-check-label" for="is_featured">باقة مميزة (تُبرز بصرياً في القالب)</label>
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

<script src="js/pricing.js"></script>
