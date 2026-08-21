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
<?php
$listId = numer($_GET['list_id'] ?? 0);
dbSelect("email_lists", "*", "WHERE id = ? LIMIT 1", [$listId]);
if ($countrows === 0) {
  sweet("error", "خطأ", "القائمة غير موجودة", "mailing/lists");
  exit;
}
$list = $rows[0];
$fields = @unserialize($list['fields']);
if (!is_array($fields)) $fields = [];
$status = '1';

function mailing_input_type($type)
{
  $map = ['text' => 'text', 'number' => 'number', 'email' => 'email', 'phone' => 'tel', 'date' => 'date'];
  return $map[$type] ?? 'text';
}

if (isset($_POST['submit'])) {
  $csrf->verify();
  $email = safer($_POST['email'] ?? null);
  $status = numer($_POST['status'] ?? 1);

  if (!empty($email) and filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $existingListName = mailingFindEmailListName($email);
    if ($existingListName) {
      sweet("warning", "الإيميل موجود مسبقاً", "لم تتم إضافة الإيميل <strong>" . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</strong> لأنه موجود مسبقاً في قائمة <strong>" . htmlspecialchars($existingListName, ENT_QUOTES, 'UTF-8') . "</strong>. كل إيميل يمكن أن ينتمي لقائمة بريدية واحدة فقط.");
    } else {
      $contactData = [];
      foreach ($fields as $f) {
        $contactData[$f['key']] = safer($_POST['field_' . $f['key']] ?? '');
      }

      $columns = "list_id, email, data, status, date";
      $values = [$listId, $email, serialize($contactData), $status, date('Y-m-d H:i:s')];
      dbInsert("email_list_contacts", $columns, $values);
      sweet("success", "تم", "تمت إضافة جهة الاتصال بنجاح", "mailing/lists/" . $listId . "/contacts");
      exit;
    }
  } else {
    sweet("error", "خطأ", "البريد الإلكتروني مطلوب وبصيغة صحيحة");
  }
}
?>
<title>إضافة جهة اتصال</title>
<div class="card mb-4">
  <h5 class="card-header">إضافة جهة اتصال إلى: <?php echo htmlspecialchars($list['name'], ENT_QUOTES, 'UTF-8') ?></h5>
  <form class="card-body" method="post">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="email" class="form-control" name="email" value="<?php echo $email ?? '' ?>" placeholder="البريد الإلكتروني" required>
          <label>البريد الإلكتروني</label>
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
      <?php foreach ($fields as $f): ?>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="<?php echo mailing_input_type($f['type']) ?>" class="form-control" name="field_<?php echo htmlspecialchars($f['key'], ENT_QUOTES, 'UTF-8') ?>" placeholder="<?php echo htmlspecialchars($f['label'], ENT_QUOTES, 'UTF-8') ?>">
          <label><?php echo htmlspecialchars($f['label'], ENT_QUOTES, 'UTF-8') ?></label>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="pt-4">
      <?php $csrf->input(); ?>
      <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-plus"></i> حفظ</button>
    </div>
  </form>
</div>
