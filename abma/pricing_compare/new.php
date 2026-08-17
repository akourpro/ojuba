<?php function h($v)
{
  return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
} ?>
<title>اضافة صف مقارنة جديد</title>
<?php
// الباقات الفعّالة حالياً — عمود قيمة لكل باقة، ديناميكي بالكامل
dbSelect("pricing", "id, name", "WHERE status = ? ORDER BY ordering ASC, id DESC", ["active"]);
$activePlans = ($countrows >= 1) ? $rows : [];

$status = '';
if (isset($_POST['submit'])) {
  $csrf->verify();
  $feature = safer($_POST['feature'] ?? null);
  $feature_en = safer($_POST['feature_en'] ?? null);
  $ordering = numer($_POST['ordering'] ?? 0);
  $status = safer($_POST['status'] ?? null);

  $featureId = dbInsert(
    "pricing_compare_features",
    "feature, feature_en, ordering, status, date",
    [$feature, $feature_en, $ordering, $status, date('Y-m-d H:i:s')]
  );

  foreach ($activePlans as $plan) {
    $val = safer($_POST['val'][$plan['id']] ?? null);
    $val_en = safer($_POST['val_en'][$plan['id']] ?? null);
    if ($val !== null && $val !== '' || $val_en !== null && $val_en !== '') {
      dbInsert(
        "pricing_compare_values",
        "feature_id, plan_id, value, value_en",
        [$featureId, $plan['id'], $val, $val_en]
      );
    }
  }

  sweet("success", "تم", "تمت الإضافة بنجاح", "pricing-compare");
  exit;
}
?>
<div class="card mb-4">
  <h5 class="card-header">إضافة صف جديد لجدول المقارنة</h5>
  <form class="card-body" method="post">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="feature" value="<?php echo h($feature ?? '') ?>" placeholder="الميزة" required>
          <label>اسم الميزة (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="feature_en" value="<?php echo h($feature_en ?? '') ?>" placeholder="Feature">
          <label>اسم الميزة (انجليزي) <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>

      <?php if (empty($activePlans)): ?>
      <div class="col-12">
        <div class="alert alert-warning mb-0">لا توجد باقات أسعار فعّالة حالياً — أضف باقة أولاً حتى تظهر أعمدة القيم هنا.</div>
      </div>
      <?php else: ?>
      <div class="col-12"><hr></div>
      <?php foreach ($activePlans as $plan): ?>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="val[<?php echo (int)$plan['id'] ?>]" placeholder="✓ / ✕ / نص">
          <label><?php echo h($plan['name']) ?> (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="val_en[<?php echo (int)$plan['id'] ?>]" placeholder="✓ / ✕ / text">
          <label><?php echo h($plan['name']) ?> (انجليزي)</label>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>

      <div class="col-12"><hr></div>
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

<script src="js/pricing_compare.js"></script>
