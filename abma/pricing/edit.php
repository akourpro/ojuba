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
dbSelect("pricing", "*", "WHERE id = ? LIMIT 1", [$id]);
if ($countrows === 0) {
  sweet("error", "خطأ", "السجل غير موجود", "pricing");
  exit;
}
$row = $rows[0];

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

  $columns = "name = ?, name_en = ?, price = ?, currency = ?, period = ?, period_en = ?, features = ?, features_en = ?, is_featured = ?, ordering = ?, status = ?";
  $values = [$name, $name_en, $price, $currency, $period, $period_en, $features, $features_en, $is_featured, $ordering, $status, $id];
  dbUpdate("pricing", $columns, $values, "WHERE id = ? LIMIT 1");
  sweet("success", "تم", "تم التحديث بنجاح", "pricing");
  exit;
}
?>
<title>تعديل باقة <?php echo h($row['name']) ?></title>
<div class="card mb-4">
  <h5 class="card-header">تعديل</h5>
  <form class="card-body" method="post">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name" value="<?php echo h($row['name']) ?>">
          <label>اسم الباقة (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name_en" value="<?php echo h($row['name_en']) ?>">
          <label>اسم الباقة (انجليزي)</label>
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="price" value="<?php echo h($row['price']) ?>">
          <label>السعر</label>
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="currency" value="<?php echo h($row['currency']) ?>">
          <label>العملة</label>
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="ordering" value="<?php echo h($row['ordering']) ?>">
          <label>ترتيب العرض</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="period" value="<?php echo h($row['period']) ?>">
          <label>الدورية (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="period_en" value="<?php echo h($row['period_en']) ?>">
          <label>الدورية (انجليزي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control" name="features" style="height:140px"><?php echo h($row['features']) ?></textarea>
          <label>المميزات (سطر لكل ميزة - عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control" name="features_en" style="height:140px"><?php echo h($row['features_en']) ?></textarea>
          <label>المميزات (سطر لكل ميزة - انجليزي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-check mt-2">
          <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" <?php if ($row['is_featured'] == 1) echo "checked" ?>>
          <label class="form-check-label" for="is_featured">باقة مميزة (تُبرز بصرياً في القالب)</label>
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

<script src="js/pricing.js"></script>
