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
<?php requireOwner(); ?>
<?php function h($v)
{
  return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
} ?>
<?php
$id = numer($_GET['id'] ?? 0);
dbSelect("admins", "*", "WHERE id = ? LIMIT 1", [$id]);
if ($countrows === 0) {
  sweet("error", "خطأ", "الحساب غير موجود", "users");
  exit;
}
$row = $rows[0];

if (isset($_POST['submit'])) {
  $csrf->verify();
  $username = safer($_POST['username'] ?? null);
  $email = safer($_POST['email'] ?? null);
  $password = $_POST['password'] ?? '';
  $confirm = $_POST['confirm_password'] ?? '';
  $role = in_array($_POST['role'] ?? '', ['owner', 'editor'], true) ? $_POST['role'] : 'editor';
  $status = in_array($_POST['status'] ?? '', ['active', 'disabled'], true) ? $_POST['status'] : 'active';

  // حماية: لا يمكن ترك النظام بدون owner واحد نشط على الأقل
  $willLoseOwnerAccess = ($row['role'] === 'owner') && ($role !== 'owner' || $status !== 'active');
  if ($willLoseOwnerAccess) {
    dbSelect("admins", "id", "WHERE role = 'owner' AND status = 'active' AND id != ? LIMIT 1", [$id]);
    if ($countrows === 0) {
      sweet("error", "خطأ", "لا يمكن ترك النظام بدون حساب owner واحد نشط على الأقل");
      exit;
    }
  }

  if (empty($username) or empty($email)) {
    sweet("error", "خطأ", "اسم المستخدم والبريد الالكتروني إجباريان");
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sweet("error", "خطأ", "البريد الالكتروني غير صالح");
  } elseif (!empty($password) and $password !== $confirm) {
    sweet("error", "خطأ", "كلمة المرور غير متطابقة");
  } elseif (!empty($password) and !isPasswordStrong($password)) {
    sweet("error", "خطأ", "كلمة المرور ضعيفة: يجب ألا تقل عن 8 أحرف وتحتوي على حرف ورقم معاً");
  } else {
    dbSelect("admins", "id", "WHERE (username = ? OR email = ?) AND id != ? LIMIT 1", [$username, $email, $id]);
    if ($countrows >= 1) {
      sweet("error", "خطأ", "اسم المستخدم أو البريد الالكتروني مستخدم مسبقاً");
      exit;
    }

    if (!empty($password)) {
      $hashed = password_hash(hash('sha512', $password), PASSWORD_BCRYPT);
      $columns = "username = ?, email = ?, password = ?, role = ?, status = ?";
      $values = [$username, $email, $hashed, $role, $status, $id];
    } else {
      $columns = "username = ?, email = ?, role = ?, status = ?";
      $values = [$username, $email, $role, $status, $id];
    }
    dbUpdate("admins", $columns, $values, "WHERE id = ? LIMIT 1");
    logAction("user_update", "تم تعديل حساب أدمن #" . $id . " (" . $username . ") — الصلاحية: " . $role . " — الحالة: " . $status);
    sweet("success", "تم", "تم التحديث بنجاح", "users");
    exit;
  }
}
?>
<title>تعديل حساب <?php echo h($row['username']) ?></title>
<div class="card mb-4">
  <h5 class="card-header">تعديل</h5>
  <form class="card-body" method="post">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="username" value="<?php echo h($row['username']) ?>">
          <label>اسم المستخدم</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="email" class="form-control" name="email" value="<?php echo h($row['email']) ?>">
          <label>البريد الالكتروني</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="password" class="form-control" name="password" placeholder="اتركه فارغاً لعدم التغيير" minlength="8">
          <label>كلمة مرور جديدة <sup class="text-success">(اختياري)</sup></label>
        </div>
        <small class="text-muted">إن أدخلتها: 8 أحرف على الأقل، حرف ورقم معاً</small>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="password" class="form-control" name="confirm_password" placeholder="تأكيد كلمة المرور الجديدة">
          <label>تأكيد كلمة المرور <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <select class="form-select" name="role"><?php $__cur = $row['role'] ?? 'editor'; ?>
            <option value="editor" <?php if ($__cur == "editor") echo "selected" ?>>editor — محتوى فقط</option>
            <option value="owner" <?php if ($__cur == "owner") echo "selected" ?>>owner — وصول كامل</option>
          </select><label>الصلاحية</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <select class="form-select" name="status"><?php $__curS = $row['status'] ?? 'active'; ?>
            <option value="active" <?php if ($__curS == "active") echo "selected" ?>>مفعّل</option>
            <option value="disabled" <?php if ($__curS == "disabled") echo "selected" ?>>معطّل</option>
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
