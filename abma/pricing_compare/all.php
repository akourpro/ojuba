<?php function h($v)
{
  return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
} ?>
<title>جدول مقارنة الباقات</title>
<h4 class="py-3 mb-3"><span class="text-muted fw-light">باقات الأسعار /</span> جدول مقارنة الباقات</h4>

<?php
// إعدادات قسم جدول المقارنة (العنوان فقط — الأعمدة نفسها ديناميكية وتُبنى تلقائياً
// من باقات جدول pricing الفعّالة، لذلك لا حاجة لتخزين أسماء أعمدة ثابتة)
$compareSettingsExist = false;
$compareSettings = null;
try {
  dbSelect("pricing_compare_settings", "*", "LIMIT 1");
  $compareSettingsExist = $countrows === 1;
  $compareSettings = $compareSettingsExist ? $rows[0] : null;
} catch (Exception $e) {
  // الجدول غير موجود بعد
}

if (isset($_POST['submit_compare_settings'])) {
  $csrf->verify();
  $eyebrow = safer($_POST['eyebrow'] ?? null);
  $eyebrow_en = safer($_POST['eyebrow_en'] ?? null);
  $title = safer($_POST['title'] ?? null);
  $title_en = safer($_POST['title_en'] ?? null);
  $description = safer($_POST['description'] ?? null);
  $description_en = safer($_POST['description_en'] ?? null);

  if ($compareSettingsExist) {
    dbUpdate(
      "pricing_compare_settings",
      "eyebrow = ?, eyebrow_en = ?, title = ?, title_en = ?, description = ?, description_en = ?",
      [$eyebrow, $eyebrow_en, $title, $title_en, $description, $description_en, $compareSettings['id']],
      "WHERE id = ? LIMIT 1"
    );
  } else {
    dbInsert(
      "pricing_compare_settings",
      "eyebrow, eyebrow_en, title, title_en, description, description_en",
      [$eyebrow, $eyebrow_en, $title, $title_en, $description, $description_en]
    );
  }
  sweet("success", "تم", "تم حفظ إعدادات جدول المقارنة بنجاح", "pricing-compare");
  exit;
}

// الباقات الفعّالة حالياً — هذه هي أعمدة الجدول (ديناميكية بالكامل، بدون تخزين أسماء ثابتة)
dbSelect("pricing", "id, name, ordering", "WHERE status = ? ORDER BY ordering ASC, id DESC", ["active"]);
$activePlans = ($countrows >= 1) ? $rows : [];
?>

<?php if (!$compareSettingsExist): ?>
<div class="alert alert-warning">
  جداول جدول المقارنة غير موجودة بعد. <a href="pricing/migrate">اضغط هنا لتشغيل الترحيل مرة واحدة</a> قبل استخدام هذا القسم.
</div>
<?php else: ?>
<div class="card mb-4">
  <h5 class="card-header">إعدادات قسم جدول المقارنة</h5>
  <form class="card-body" method="post">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="eyebrow" value="<?php echo h($compareSettings['eyebrow']) ?>">
          <label>الوسم العلوي (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="eyebrow_en" value="<?php echo h($compareSettings['eyebrow_en']) ?>">
          <label>الوسم العلوي (انجليزي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="title" value="<?php echo h($compareSettings['title']) ?>">
          <label>عنوان القسم (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="title_en" value="<?php echo h($compareSettings['title_en']) ?>">
          <label>عنوان القسم (انجليزي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control" name="description" style="height:80px"><?php echo h($compareSettings['description']) ?></textarea>
          <label>الوصف (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control" name="description_en" style="height:80px"><?php echo h($compareSettings['description_en']) ?></textarea>
          <label>الوصف (انجليزي)</label>
        </div>
      </div>
    </div>
    <div class="pt-4">
      <?php $csrf->input(); ?>
      <button type="submit" name="submit_compare_settings" class="btn btn-primary"><i class="mdi mdi-content-save"></i> حفظ إعدادات القسم</button>
      <a href="pricing" class="btn btn-outline-secondary"><i class="mdi mdi-arrow-right"></i> رجوع لباقات الأسعار</a>
    </div>
  </form>
</div>
<?php endif; ?>

<?php if (empty($activePlans)): ?>
<div class="alert alert-warning">
  لا توجد باقات أسعار فعّالة حالياً. <a href="pricing/new">أضف باقة</a> أولاً حتى تظهر كأعمدة في جدول المقارنة.
</div>
<?php else: ?>

<div class="row mb-3">
  <div class="col-sm">
    <a href="pricing-compare/new" class="btn btn-secondary waves-effect waves-light"><i class="mdi mdi-plus"></i> اضافة صف مقارنة جديد</a>
  </div>
</div>

<div class="alert alert-info">
  أعمدة الجدول أدناه (<?php echo implode('، ', array_column($activePlans, 'name')) ?>) مأخوذة تلقائياً من باقات الأسعار الفعّالة — لو أضفت أو أوقفت باقة من <a href="pricing">قائمة الباقات</a> ستتغيّر الأعمدة هنا وفي صفحة /pricing تلقائياً. يمكنك كتابة ✓ أو ✕ أو أي نص (مثل "غير محدود") في كل خلية.
</div>

<div class="card mt-2">
  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle orders_table">
      <thead class="table-light">
        <tr>
          <th style="width:50px">#</th>
          <th>الميزة</th>
          <?php foreach ($activePlans as $plan): ?>
          <th><?php echo h($plan['name']) ?></th>
          <?php endforeach; ?>
          <th>الترتيب</th>
          <th>الحالة</th>
          <th style="width:120px">إجراء</th>
        </tr>
      </thead>
      <tbody>
        <?php
        dbSelect("pricing_compare_features", "id, feature, ordering, status", "ORDER BY ordering ASC, id DESC");
        if ($countrows >= 1) {
          $featureRowsList = $rows;
          $featureIdsList = array_column($featureRowsList, 'id');
          $valueLookup = [];
          if (!empty($featureIdsList)) {
            $ph = implode(',', array_fill(0, count($featureIdsList), '?'));
            dbSelect("pricing_compare_values", "feature_id, plan_id, value", "WHERE feature_id IN ($ph)", $featureIdsList);
            if ($countrows >= 1) {
              foreach ($rows as $v) {
                $valueLookup[$v['feature_id']][$v['plan_id']] = $v['value'];
              }
            }
          }
          $i = 1;
          foreach ($featureRowsList as $row) {
            echo '<tr>';
            echo '<td>' . $i++ . '</td>';
            echo '<td>' . h($row['feature']) . '</td>';
            foreach ($activePlans as $plan) {
              echo '<td>' . h($valueLookup[$row['id']][$plan['id']] ?? '') . '</td>';
            }
            echo '<td>' . $row['ordering'] . '</td>';
            echo '<td>' . h($row['status']) . '</td>';
            echo '<td>
                    <div class="dropdown">
                      <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">إجراء</button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                          <a href="pricing-compare/' . $row['id'] . '/edit" class="dropdown-item text-warning edit"><i class="mdi mdi-pen"></i> تعديل</a>
                          <span data-id="' . $row['id'] . '" data-action="delete" data-name="' . h($row['feature']) . '" class="dropdown-item text-danger delete"><i class="mdi mdi-delete"></i> حذف</span>
                        </div>
                  </td>';
            echo '</tr>';
          }
        }
        ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<script src="js/pricing_compare.js"></script>
<script src="js/tables.js"></script>
