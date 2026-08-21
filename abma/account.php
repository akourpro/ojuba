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
<title>حسابي</title>
<?php
require_once 'includes/totp.php';
$myId = numer($_SESSION['user_id'] ?? 0);

// ===== التحقق بخطوتين (2FA/TOTP) — إجراءات هذه الصفحة =====
$totpNewBackupCodes = null;
if (adminsTotpColumnsExist()) {

    // بدء إعداد جديد: توليد سرّ مؤقت (لم يُحفظ بعد بقاعدة البيانات إلا بعد
    // تأكيد رمز صحيح أولاً، لضمان أن المستخدم أدخله بنجاح بتطبيق المصادقة
    // قبل تفعيل الميزة فعلياً على حسابه).
    // مهم: نموذج التأكيد أسفل الصفحة (POST) لا يحمل action صريحاً، فيُرسَل
    // للرابط الحالي نفسه (account?totp_setup=1) — لذلك نتحقق من REQUEST_METHOD
    // (لا تُولَّد أي سرّ جديد أثناء معالجة POST) ومن عدم وجود سرّ معلَّق أصلاً
    // بالجلسة، وإلا كان يُستبدَل السرّ الذي مسحه المستخدم فعلياً بتطبيقه بسرّ
    // جديد مباشرة قبل التحقق من الرمز، فيفشل التأكيد دائماً مهما أدخل المستخدم.
    if (isset($_GET['totp_setup']) && $_SERVER['REQUEST_METHOD'] !== 'POST' && empty($_SESSION['totp_setup_secret'])) {
        $_SESSION['totp_setup_secret'] = totpGenerateSecret();
    }

    // إلغاء الإعداد الجاري
    if (isset($_GET['totp_cancel_setup'])) {
        unset($_SESSION['totp_setup_secret']);
    }

    // تأكيد رمز الإعداد وتفعيل الميزة فعلياً
    if (isset($_POST['totp_confirm_enable'])) {
        $csrf->verify();
        $setupSecret = $_SESSION['totp_setup_secret'] ?? '';
        $code = trim((string) ($_POST['totp_code'] ?? ''));
        if ($setupSecret !== '' && totpVerifyCode($setupSecret, $code)) {
            $backupCodes = totpGenerateBackupCodes();
            dbUpdate(
                "admins",
                "totp_secret = ?, totp_enabled = 1, totp_backup_codes = ?",
                [$setupSecret, totpHashBackupCodes($backupCodes), $myId],
                "WHERE id = ?"
            );
            unset($_SESSION['totp_setup_secret']);
            $totpNewBackupCodes = $backupCodes;
            logAction('totp_enable', 'تم تفعيل التحقق بخطوتين على الحساب');
        } else {
            sweet("error", "خطأ", "رمز التحقق غير صحيح، حاول مرة أخرى", "account");
            die();
        }
    }

    // تعطيل الميزة
    if (isset($_POST['totp_disable'])) {
        $csrf->verify();
        dbUpdate("admins", "totp_enabled = 0, totp_secret = NULL, totp_backup_codes = NULL", [$myId], "WHERE id = ?");
        logAction('totp_disable', 'تم تعطيل التحقق بخطوتين على الحساب');
        sweet("success", "تم", "تم تعطيل التحقق بخطوتين", "account");
        die();
    }

    // إعادة توليد رموز الاسترجاع (تُبطل كل الرموز القديمة فوراً)
    if (isset($_POST['totp_regen_backup'])) {
        $csrf->verify();
        $backupCodes = totpGenerateBackupCodes();
        dbUpdate("admins", "totp_backup_codes = ?", [totpHashBackupCodes($backupCodes), $myId], "WHERE id = ?");
        $totpNewBackupCodes = $backupCodes;
        logAction('totp_regen_backup', 'تم توليد رموز استرجاع جديدة للتحقق بخطوتين');
    }
}

dbSelect("admins", "username, email, password" . (adminsTotpColumnsExist() ? ", totp_enabled" : ""), "WHERE id = ?", [$myId]);
if (isset($_POST['submit'])) {
    $csrf->verify();

    $username = safer($_POST['username']);
    $email = safer($_POST['email']);
    if (!empty($username) or !empty($email)) {

        if (!empty($_POST['password'])) {
            if ($_POST['password'] == $_POST['confirm_password']) {
                $password = $_POST['password'];
                $password = password_hash(hash('sha512', $password), PASSWORD_BCRYPT);
                $columns = "password = ?";
                $values = [$password, numer($_SESSION['user_id'] ?? 0)];
                dbUpdate("admins", $columns, $values, "WHERE id = ?");
            } else {
                sweet("error", "خطأ", "كلمة المرور غير متطابقة", "here");
                die();
            }
        }

        $columns = "username = ?, email = ?";
        $values = [$username, $email, numer($_SESSION['user_id'] ?? 0)];
        dbUpdate("admins", $columns, $values, "WHERE id = ?");

        sweet("success", "نجاح", "تم تحديث بياناتك بنجاح", "here");
        die();
    } else {
        sweet("error", "خطأ", "اسم المستخدم والبريد الالكتروني حقول اجبارية.");
    }
}

?>
<h4 class="py-3 mb-4"><span class="text-muted fw-light">المدير /</span> تحرير الحساب</h4>

<div class="card mb-4">
    <h5 class="card-header">تعديل بيانات العضوية الخاصة بك</h5>
    <form class="card-body" method="post">

        <div class="row g-4">
            <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                    <input type="text" id="multicol-fullname" class="form-control" name="username" value="<?php echo $rows[0]['username']; ?>" placeholder="اسم المستخدم" required>
                    <label for="multicol-fullname">اسم المستخدم</label>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                    <input type="email" id="multicol-email" class="form-control" name="email" value="<?php echo $rows[0]['email']; ?>" placeholder="البريد الالكتروني" required>
                    <label for="multicol-email">البريد الالكتروني</label>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-password-toggle">
                    <div class="input-group input-group-merge">
                        <div class="form-floating form-floating-outline">
                            <input type="password" id="multicol-password" class="form-control" name="password" placeholder="كلمة المرور الجديدة">
                            <label for="multicol-password">كلمة المرور الجديدة</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-password-toggle">
                    <div class="input-group input-group-merge">
                        <div class="form-floating form-floating-outline">
                            <input type="password" id="multicol-password" class="form-control" name="confirm_password" placeholder="تأكيد كلمة المرور الجديدة">
                            <label for="multicol-password">تأكيد كلمة المرور الجديدة</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-danger"><b>ملاحظة: </b>اترك كلمة المرور فارغة لعدم تغييرها</div>


        </div>

        <div class="pt-4">
            <?php $csrf->input(); ?>
            <button type="reset" class="btn btn-outline-secondary waves-effect"><i class="mdi mdi-delete-empty-outline"></i> تفريغ</button>
            <button type="submit" name="submit" class="btn btn-primary me-sm-3 me-1 waves-effect waves-light"><i class="mdi mdi-plus"></i> تعديل</button>


        </div>
    </form>
</div>

<?php if (!adminsTotpColumnsExist()): ?>
    <?php if (isOwner()): ?>
        <div class="alert alert-warning">
            <b>التحقق بخطوتين (2FA) غير مُجهَّز بعد بقاعدة البيانات.</b>
            <a href="users/migrate" class="btn btn-sm btn-warning ms-2">تشغيل الترحيل الآن</a>
        </div>
    <?php endif; ?>
<?php else: ?>
<div class="card mb-4">
    <h5 class="card-header"><i class="mdi mdi-shield-key-outline"></i> التحقق بخطوتين (2FA)</h5>
    <div class="card-body">

        <?php if (!empty($totpNewBackupCodes)): ?>
        <div class="alert alert-success">
            <b>احفظ رموز الاسترجاع التالية الآن</b> — لن تُعرَض مرة أخرى. استخدم أياً منها للدخول إن فقدتَ تطبيق المصادقة (كل رمز يُستخدَم مرة واحدة فقط).
            <div class="mt-2 d-flex flex-wrap gap-2" dir="ltr">
                <?php foreach ($totpNewBackupCodes as $bc): ?>
                    <code class="p-2 bg-light border rounded"><?php echo safer($bc); ?></code>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($rows[0]['totp_enabled'])): ?>
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge bg-label-success"><i class="mdi mdi-check-circle-outline"></i> مفعّل</span>
                <span class="text-muted small">حسابك محمي برمز تحقق إضافي عند الدخول.</span>
            </div>
            <form method="post" class="d-flex gap-2 flex-wrap">
                <?php $csrf->input(); ?>
                <button type="submit" name="totp_regen_backup" class="btn btn-outline-secondary btn-sm">توليد رموز استرجاع جديدة</button>
                <button type="submit" name="totp_disable" class="btn btn-outline-danger btn-sm" onclick="return confirm('تعطيل التحقق بخطوتين سيقلّل من حماية حسابك، هل أنت متأكد؟');">تعطيل التحقق بخطوتين</button>
            </form>

        <?php elseif (!empty($_SESSION['totp_setup_secret'])): ?>
            <?php
                $setupSecret = $_SESSION['totp_setup_secret'];
                $accountLabel = $rows[0]['email'] ?: $rows[0]['username'];
                $groupedSecret = trim(chunk_split($setupSecret, 4, ' '));
                $otpauthUri = 'otpauth://totp/' . rawurlencode('Ojuba:' . $accountLabel) . '?secret=' . $setupSecret . '&issuer=Ojuba&algorithm=SHA1&digits=6&period=30';
            ?>
            <p class="mb-2">افتح تطبيق مصادقة (Google Authenticator، Microsoft Authenticator، Authy...) وامسح رمز QR التالي، أو أضف الحساب يدوياً بالسرّ أسفله إن كنت تفضّل ذلك:</p>

            <div class="text-center mb-3">
                <div id="totpQrCode" class="d-inline-block p-2 bg-white border rounded"></div>
                <script src="js/qrcode.min.js"></script>
                <script>
                    // يُرسَم رمز QR بالكامل داخل المتصفح (لا يُرفَع لأي خادم، ولا يُخزَّن
                    // بقاعدة البيانات أو كملف على السيرفر إطلاقاً) — عرض داخلي مؤقت فقط
                    // من بيانات السرّ الموجودة أصلاً بهذه الصفحة كنص عادي أسفله.
                    new QRCode(document.getElementById('totpQrCode'), {
                        text: <?php echo json_encode($otpauthUri, JSON_UNESCAPED_SLASHES); ?>,
                        width: 180,
                        height: 180,
                        correctLevel: QRCode.CorrectLevel.M
                    });
                </script>
            </div>

            <p class="text-muted small text-center mb-2">أو أدخل السرّ يدوياً إن تعذّر مسح الرمز:</p>
            <div class="p-3 bg-light border rounded text-center mb-3">
                <code style="font-size:20px;letter-spacing:2px;" dir="ltr"><?php echo safer($groupedSecret); ?></code>
            </div>
            <p class="text-muted small mb-3">بعض التطبيقات تقبل أيضاً لصق الرابط التالي مباشرة: <code dir="ltr" style="word-break:break-all;"><?php echo safer($otpauthUri); ?></code></p>
            <form method="post" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <div class="form-floating form-floating-outline">
                        <input type="text" inputmode="numeric" class="form-control" id="totp_code" name="totp_code" placeholder="000000" maxlength="6" required>
                        <label for="totp_code">أدخل الرمز المعروض بالتطبيق للتأكيد</label>
                    </div>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <?php $csrf->input(); ?>
                    <button type="submit" name="totp_confirm_enable" class="btn btn-primary">تأكيد وتفعيل</button>
                    <a href="account?totp_cancel_setup=1" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>

        <?php else: ?>
            <p class="text-muted mb-3">أضف طبقة حماية إضافية لحسابك — بعد إدخال كلمة المرور، سيُطلَب منك أيضاً رمز مؤقت من تطبيق مصادقة على هاتفك.</p>
            <a href="account?totp_setup=1" class="btn btn-primary btn-sm">تفعيل التحقق بخطوتين</a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>