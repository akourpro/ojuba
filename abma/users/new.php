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
<title>اضافة حساب جديد</title>
<?php
$role = 'editor';
$status = 'active';
if (isset($_POST['submit'])) {
  $csrf->verify();
  $username = safer($_POST['username'] ?? null);
  $email = safer($_POST['email'] ?? null);
  $password = $_POST['password'] ?? '';
  $confirm = $_POST['confirm_password'] ?? '';
  $role = in_array($_POST['role'] ?? '', ['owner', 'editor'], true) ? $_POST['role'] : 'editor';
  $status = in_array($_POST['status'] ?? '', ['active', 'disabled'], true) ? $_POST['status'] : 'active';

  if (empty($username) or empty($email) or empty($password)) {
    sweet("error", "خطأ", "جميع الحقول إجبارية");
  } elseif ($password !== $confirm) {
    sweet("error", "خطأ", "كلمة المرور غير متطابقة");
  } elseif (!isPasswordStrong($password)) {
    sweet("error", "خطأ", "كلمة المرور ضعيفة: يجب ألا تقل عن 8 أحرف وتحتوي على حرف ورقم معاً");
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sweet("error", "خطأ", "البريد الالكتروني غير صالح");
  } else {
    dbSelect("admins", "id", "WHERE username = ? OR email = ? LIMIT 1", [$username, $email]);
    if ($countrows >= 1) {
      sweet("error", "خطأ", "اسم المستخدم أو البريد الالكتروني مستخدم مسبقاً");
    } else {
      $hashed = password_hash(hash('sha512', $password), PASSWORD_BCRYPT);
      $columns = "username, email, password, role, status, date";
      $values = [$username, $email, $hashed, $role, $status, date('Y-m-d H:i:s')];
      dbInsert("admins", $columns, $values);
      logAction("user_create", "تم إنشاء حساب أدمن جديد: " . $username . " (" . $role . ")");
      sweet("success", "تم", "تمت إضافة الحساب بنجاح", "users");
      exit;
    }
  }
}
?>
<div class="card mb-4">
  <h5 class="card-header">إضافة جديد</h5>
  <form class="card-body" method="post">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="username" value="<?php echo $username ?? '' ?>" placeholder="اسم المستخدم" required>
          <label>اسم المستخدم</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="email" class="form-control" name="email" value="<?php echo $email ?? '' ?>" placeholder="البريد الالكتروني" required>
          <label>البريد الالكتروني</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="password" class="form-control" name="password" placeholder="كلمة المرور" minlength="8" required>
          <label>كلمة المرور</label>
        </div>
        <small class="text-muted">8 أحرف على الأقل، حرف ورقم معاً</small>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="password" class="form-control" name="confirm_password" placeholder="تأكيد كلمة المرور" required>
          <label>تأكيد كلمة المرور</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <select class="form-select" name="role">
            <option value="editor" <?php if ($role == "editor") echo "selected" ?>>editor — محتوى فقط</option>
            <option value="owner" <?php if ($role == "owner") echo "selected" ?>>owner — وصول كامل</option>
          </select><label>الصلاحية</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <select class="form-select" name="status">
            <option value="active" <?php if ($status == "active") echo "selected" ?>>مفعّل</option>
            <option value="disabled" <?php if ($status == "disabled") echo "selected" ?>>معطّل</option>
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
