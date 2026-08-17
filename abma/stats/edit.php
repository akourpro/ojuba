<?php function h($v)
{
  return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
} ?>
<?php
$id = numer($_GET['id'] ?? 0);
dbSelect("stats", "*", "WHERE id = ? LIMIT 1", [$id]);
if ($countrows === 0) {
  sweet("error", "خطأ", "السجل غير موجود", "stats");
  exit;
}
$row = $rows[0];

if (isset($_POST['submit'])) {
  $csrf->verify();
  $number = safer($_POST['number'] ?? null);
  $suffix = safer($_POST['suffix'] ?? null);
  $label = safer($_POST['label'] ?? null);
  $label_en = safer($_POST['label_en'] ?? null);
  $icon = safer($_POST['icon'] ?? null);
  $ordering = numer($_POST['ordering'] ?? 0);
  $status = safer($_POST['status'] ?? null);

  $columns = "number = ?, suffix = ?, label = ?, label_en = ?, icon = ?, ordering = ?, status = ?";
  $values = [$number, $suffix, $label, $label_en, $icon, $ordering, $status, $id];
  dbUpdate("stats", $columns, $values, "WHERE id = ? LIMIT 1");
  sweet("success", "تم", "تم التحديث بنجاح", "stats");
  exit;
}
?>
<title>تعديل إحصائية</title>
<div class="card mb-4">
  <h5 class="card-header">تعديل</h5>
  <form class="card-body" method="post">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="number" value="<?php echo h($row['number']) ?>">
          <label>الرقم</label>
        </div>
      </div>
      <div class="col-md-2">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="suffix" value="<?php echo h($row['suffix']) ?>">
          <label>لاحقة</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="icon" value="<?php echo h($row['icon']) ?>">
          <label>الايقونة</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="label" value="<?php echo h($row['label']) ?>">
          <label>التسمية (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="label_en" value="<?php echo h($row['label_en']) ?>">
          <label>التسمية (انجليزي)</label>
        </div>
      </div>
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

<script src="js/stats.js"></script>
