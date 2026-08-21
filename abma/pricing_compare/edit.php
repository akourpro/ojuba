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
dbSelect("pricing_compare_features", "*", "WHERE id = ? LIMIT 1", [$id]);
if ($countrows === 0) {
  sweet("error", "خطأ", "السجل غير موجود", "pricing-compare");
  exit;
}
$row = $rows[0];

// الباقات الفعّالة حالياً — عمود قيمة لكل باقة، ديناميكي بالكامل
dbSelect("pricing", "id, name", "WHERE status = ? ORDER BY ordering ASC, id DESC", ["active"]);
$activePlans = ($countrows >= 1) ? $rows : [];

// القيم الحالية المخزّنة لهذا الصف، مفهرسة حسب رقم الباقة
dbSelect("pricing_compare_values", "plan_id, value, value_en", "WHERE feature_id = ?", [$id]);
$currentValues = [];
if ($countrows >= 1) {
  foreach ($rows as $v) {
    $currentValues[$v['plan_id']] = ['value' => $v['value'], 'value_en' => $v['value_en']];
  }
}

if (isset($_POST['submit'])) {
  $csrf->verify();
  $feature = safer($_POST['feature'] ?? null);
  $feature_en = safer($_POST['feature_en'] ?? null);
  $ordering = numer($_POST['ordering'] ?? 0);
  $status = safer($_POST['status'] ?? null);

  dbUpdate(
    "pricing_compare_features",
    "feature = ?, feature_en = ?, ordering = ?, status = ?",
    [$feature, $feature_en, $ordering, $status, $id],
    "WHERE id = ? LIMIT 1"
  );

  foreach ($activePlans as $plan) {
    $val = safer($_POST['val'][$plan['id']] ?? null);
    $val_en = safer($_POST['val_en'][$plan['id']] ?? null);
    dbSelect("pricing_compare_values", "id", "WHERE feature_id = ? AND plan_id = ? LIMIT 1", [$id, $plan['id']]);
    if ($countrows === 1) {
      dbUpdate(
        "pricing_compare_values",
        "value = ?, value_en = ?",
        [$val, $val_en, $rows[0]['id']],
        "WHERE id = ? LIMIT 1"
      );
    } else {
      dbInsert(
        "pricing_compare_values",
        "feature_id, plan_id, value, value_en",
        [$id, $plan['id'], $val, $val_en]
      );
    }
  }

  sweet("success", "تم", "تم التحديث بنجاح", "pricing-compare");
  exit;
}
?>
<title>تعديل صف مقارنة</title>
<div class="card mb-4">
  <h5 class="card-header">تعديل صف جدول المقارنة</h5>
  <form class="card-body" method="post">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="feature" value="<?php echo h($row['feature']) ?>">
          <label>اسم الميزة (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="feature_en" value="<?php echo h($row['feature_en']) ?>">
          <label>اسم الميزة (انجليزي)</label>
        </div>
      </div>

      <?php if (empty($activePlans)): ?>
      <div class="col-12">
        <div class="alert alert-warning mb-0">لا توجد باقات أسعار فعّالة حالياً.</div>
      </div>
      <?php else: ?>
      <div class="col-12"><hr></div>
      <?php foreach ($activePlans as $plan): ?>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="val[<?php echo (int)$plan['id'] ?>]" value="<?php echo h($currentValues[$plan['id']]['value'] ?? '') ?>" placeholder="✓ / ✕ / نص">
          <label><?php echo h($plan['name']) ?> (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="val_en[<?php echo (int)$plan['id'] ?>]" value="<?php echo h($currentValues[$plan['id']]['value_en'] ?? '') ?>" placeholder="✓ / ✕ / text">
          <label><?php echo h($plan['name']) ?> (انجليزي)</label>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>

      <div class="col-12"><hr></div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="ordering" value="<?php echo h($row['ordering']) ?>">
          <label>ترتيب العرض</label>
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

<script src="js/pricing_compare.js"></script>
