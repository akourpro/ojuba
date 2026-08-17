<?php requireOwner(); mailingRequireModule(); ?>
<?php
$id = numer($_GET['id'] ?? 0);
dbSelect("email_lists", "*", "WHERE id = ? LIMIT 1", [$id]);
if ($countrows === 0) {
  sweet("error", "خطأ", "القائمة غير موجودة", "mailing/lists");
  exit;
}
$row = $rows[0];
$existingFields = @unserialize($row['fields']);
if (!is_array($existingFields)) $existingFields = [];

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
        // نحافظ على المفتاح الأصلي إن كان موجوداً مسبقاً حتى لا نفقد قيم جهات الاتصال الحالية
        // وإلا نولّد مفتاحاً فريداً (لا نعتمد على ترقيم متسلسل تجنباً لتصادم المفاتيح
        // في حال وُجدت فجوات بسبب حذف حقول سابقة)
        $key = safer($f['key'] ?? '') ?: ('field_' . substr(md5(uniqid((string)$i, true)), 0, 8));
        $fields[] = ['key' => $key, 'label' => $label, 'type' => $type];
        $i++;
      }
    }

    $columns = "name = ?, description = ?, fields = ?, status = ?";
    $values = [$name, $description ?: null, serialize($fields), $status, $id];
    dbUpdate("email_lists", $columns, $values, "WHERE id = ? LIMIT 1");
    logAction('mailing_list_edit', 'تم تعديل القائمة البريدية: ' . $name);
    sweet("success", "تم", "تم التحديث بنجاح", "mailing/lists");
    exit;
  } else {
    sweet("error", "خطأ", "اسم القائمة مطلوب");
  }
}
?>
<title>تعديل قائمة <?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></title>
<div class="card mb-4">
  <h5 class="card-header">تعديل قائمة بريدية</h5>
  <form class="card-body" method="post" id="listForm">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?>">
          <label>اسم القائمة</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <select class="form-select" name="status"><?php $__cur = (string)($row['status'] ?? '1'); ?>
            <option value="1" <?php if ($__cur === '1') echo 'selected'; ?>>مفعّلة</option>
            <option value="0" <?php if ($__cur === '0') echo 'selected'; ?>>موقوفة</option>
          </select><label>الحالة</label>
        </div>
      </div>
      <div class="col-md-12">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control" name="description" style="height:90px"><?php echo htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
          <label>وصف القائمة <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>

      <div class="col-md-12">
        <hr>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="mb-0">الحقول المخصّصة لهذه القائمة</h6>
          <button type="button" id="addFieldBtn" class="btn btn-sm btn-outline-secondary"><i class="mdi mdi-plus"></i> إضافة حقل</button>
        </div>
        <p class="text-muted small">تنبيه: حذف حقل هنا لن يحذف القيم المخزّنة له لدى جهات الاتصال الحاليين، لكنه سيخفيه من نماذج الإضافة/التعديل لاحقاً.</p>
        <div id="fieldsContainer"></div>
      </div>
    </div>
    <div class="pt-4">
      <?php $csrf->input(); ?>
      <input type="hidden" name="fields_json" id="fieldsJson" value="<?php echo htmlspecialchars(json_encode($existingFields, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>">
      <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-pen"></i> تحديث</button>
    </div>
  </form>
</div>

<script src="js/mailing.js"></script>
<script>window.__existingFields = <?php echo json_encode($existingFields, JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="js/mailing-fields.js"></script>
