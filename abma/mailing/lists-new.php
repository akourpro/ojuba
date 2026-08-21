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
<?php requireOwner(); mailingRequireModule(); ?>
<title>إضافة قائمة بريدية جديدة</title>
<?php
$status = '1';
if (isset($_POST['submit'])) {
  $csrf->verify();
  $name = safer($_POST['name'] ?? null);
  $description = safer($_POST['description'] ?? null);
  $status = numer($_POST['status'] ?? 1);
  $fieldsJson = $_POST['fields_json'] ?? '[]';
  $fieldsDecoded = json_decode($fieldsJson, true);

  if (!empty($name)) {
    $fields = [];
    if (is_array($fieldsDecoded)) {
      $i = 1;
      foreach ($fieldsDecoded as $f) {
        $label = safer($f['label'] ?? '');
        $type = safer($f['type'] ?? 'text');
        if (!in_array($type, ['text', 'number', 'email', 'phone', 'date'])) $type = 'text';
        if ($label === '') continue;
        $fields[] = ['key' => 'field_' . $i, 'label' => $label, 'type' => $type];
        $i++;
      }
    }

    $columns = "name, description, fields, status, date";
    $values = [$name, $description ?: null, serialize($fields), $status, date('Y-m-d H:i:s')];
    dbInsert("email_lists", $columns, $values);
    logAction('mailing_list_add', 'تمت إضافة قائمة بريدية جديدة: ' . $name);
    sweet("success", "تم", "تمت إضافة القائمة بنجاح", "mailing/lists");
    exit;
  } else {
    sweet("error", "خطأ", "اسم القائمة مطلوب");
  }
}
?>
<div class="card mb-4">
  <h5 class="card-header">إضافة قائمة بريدية جديدة</h5>
  <form class="card-body" method="post" id="listForm">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name" value="<?php echo $name ?? '' ?>" placeholder="اسم القائمة" required>
          <label>اسم القائمة</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <select class="form-select" name="status">
            <option value="1" <?php if ((string)$status === "1") echo "selected" ?>>مفعّلة</option>
            <option value="0" <?php if ((string)$status === "0") echo "selected" ?>>موقوفة</option>
          </select><label>الحالة</label>
        </div>
      </div>
      <div class="col-md-12">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control" name="description" style="height:90px" placeholder="وصف القائمة"><?php echo $description ?? '' ?></textarea>
          <label>وصف القائمة <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>

      <div class="col-md-12">
        <hr>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="mb-0">الحقول المخصّصة لهذه القائمة</h6>
          <button type="button" id="addFieldBtn" class="btn btn-sm btn-outline-secondary"><i class="mdi mdi-plus"></i> إضافة حقل</button>
        </div>
        <p class="text-muted small">هذه الحقول ستظهر عند إضافة جهة اتصال داخل هذه القائمة (مثال: الشركة، المسمى الوظيفي...)، بالإضافة إلى البريد الإلكتروني الذي يُطلب دائماً.</p>
        <div id="fieldsContainer"></div>
      </div>
    </div>
    <div class="pt-4">
      <?php $csrf->input(); ?>
      <input type="hidden" name="fields_json" id="fieldsJson" value="[]">
      <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-plus"></i> حفظ</button>
    </div>
  </form>
</div>

<script src="js/mailing.js"></script>
<script src="js/mailing-fields.js"></script>
