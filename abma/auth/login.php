<?php
if (login_check_admin()) {
    echo "<meta http-equiv='Refresh' content='0 url=../dashboard'>";
    exit;
}

require_once 'includes/totp.php';

// جلسة "التحقق بخطوتين" المعلَّقة (بعد نجاح كلمة المرور، بانتظار رمز TOTP أو
// رمز استرجاع) — صالحة 5 دقائق فقط من وقت تأكيد كلمة المرور، تُلغى تلقائياً
// بعد ذلك ويُطلَب من المستخدم الدخول من جديد بالكامل.
$pending2faId = null;
if (!empty($_SESSION['pending_2fa_admin_id']) && !empty($_SESSION['pending_2fa_expires']) && time() < $_SESSION['pending_2fa_expires']) {
    $pending2faId = (int) $_SESSION['pending_2fa_admin_id'];
} else {
    unset($_SESSION['pending_2fa_admin_id'], $_SESSION['pending_2fa_expires']);
}

// الخطوة الثانية: التحقق من رمز TOTP أو رمز استرجاع احتياطي
if (isset($_POST['verify_2fa']) && $pending2faId) {
    $csrf->verify();
    $code = trim((string) ($_POST['totp_code'] ?? ''));
    $ip = safer(getIP());

    global $rows, $countrows;
    dbSelect("admins", "totp_secret", "WHERE id = ? LIMIT 1", [$pending2faId]);
    $secret = ($countrows === 1) ? $rows[0]['totp_secret'] : '';

    $verified = ($secret !== '' && totpVerifyCode($secret, $code)) || totpConsumeBackupCode($pending2faId, $code);

    $sys = safer(getOS()) . " - " . safer(getBrowser());
    if ($verified) {
        totpCompleteLogin($pending2faId);
        $user_id = numer($_SESSION['user_id']);
        dbInsert("logs", "user_id, date, ip, sys, action, description", [$user_id, date("Y-m-d H:i:s"), $ip, $sys, "login", "تسجيل دخول ناجح (بعد التحقق بخطوتين)"]);
        echo "<meta http-equiv='Refresh' content='0; url=../dashboard'>";
        sweet("success", "نجاح", "تم الدخول بنجاح");
        exit;
    } else {
        dbInsert("logs", "user_id, date, ip, sys, action, description", [null, date("Y-m-d H:i:s"), $ip, $sys, "login_failed", "رمز تحقق بخطوتين غير صحيح لحساب #" . $pending2faId]);
        sweet("error", "خطأ", "رمز التحقق غير صحيح أو منتهي الصلاحية");
    }
}
?>
<!DOCTYPE html>

<html lang="ar" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="rtl" data-theme="theme-default" data-assets-path="../assets/">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <META NAME="ROBOTS" CONTENT="NOARCHIVE">
    <META NAME="GOOGLEBOT" CONTENT="NOARCHIVE">
    <meta name="_csrf" content="<?php echo $csrf->header(); ?>">
    <title>الدخول</title>

    <style>
        @font-face {
            font-family: 'Tajawal';
            src: url('../assets/vendor/fonts/Tajawal.ttf');
        }
    </style>

    <!-- Icons -->
    <link rel="stylesheet" href="../assets/vendor/fonts/materialdesignicons.css" />
    <link rel="stylesheet" href="../assets/vendor/fonts/flag-icons.css" />

    <!-- Menu waves for no-customizer fix -->
    <link rel="stylesheet" href="../assets/vendor/libs/node-waves/node-waves.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="../assets/vendor/css/rtl/core.css" class="template-customizer-core-css" />
    <!-- <link rel="stylesheet" href="../assets/vendor/css/rtl/theme-default.css" class="template-customizer-theme-css" /> -->
    <link rel="stylesheet" href="../assets/vendor/css/rtl/theme-semi-dark.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/typeahead-js/typeahead.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/bootstrap-maxlength/bootstrap-maxlength.css" />

    <!-- Page CSS -->
    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <link rel="stylesheet" href="../assets/vendor/css/pages/page-auth.css" />

    <!-- Helpers -->
    <script src="../assets/vendor/js/helpers.js"></script>
    <!-- <script src="../assets/vendor/js/template-customizer.js"></script> -->
    <script src="../assets/js/config.js"></script>
    <script src="../../js/sweetalert2.all.min.js"></script>
    <script src="../../js/functions.js"></script>
</head>

<body>
    <!-- Content -->

    <div class="position-relative">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner py-4">
                <!-- Login -->
                <div class="card p-2">
                    <!-- Logo -->
                    <div class="app-brand justify-content-center mt-5">
                        <a href="../../" class="app-brand-link gap-2">
                            <span class="app-brand-logo demo">
                                <img src="../../files/images/<?php echo $site['logo']; ?>" alt="" width="150">
                            </span>
                        </a>
                    </div>
                    <!-- /Logo -->

                    <div class="card-body mt-2">
                        <h4 class="mb-2">مرحبًا بك من جديد 👋</h4>
                        <p class="mb-4">قم بتعبئة البيانات للدخول</p>
                        <?php

                        if (isset($_POST['submit']) && !$pending2faId) {
                            $csrf->verify();
                            $email = safer($_POST['email']);
                            $password = $_POST['password'];
                            $ip = safer(getIP());

                            // حماية من محاولات التخمين المتكررة (brute-force):
                            // منع أكثر من 5 محاولات فاشلة من نفس الـ IP خلال 10 دقائق
                            global $rows;
                            global $countrows;
                            dbSelect("logs", "id", "WHERE action = ? AND ip = ? AND date >= (NOW() - INTERVAL 10 MINUTE)", ["login_failed", $ip]);
                            $tooManyAttempts = ($countrows >= 5);

                            if ($tooManyAttempts) {
                                sweet("error", "خطأ", "محاولات دخول كثيرة فاشلة، الرجاء المحاولة لاحقاً بعد بضع دقائق");
                            } elseif (!empty($email) and !empty($password)) {
                                // check login
                                $login = login_admin($email, $password);
                                if ($login == "success") {
                                    // Login success
                                    $sys = safer(getOS()) . " - " . safer(getBrowser());
                                    $date = date("Y-m-d H:i:s");
                                    $user_id = numer($_SESSION['user_id']);
                                    $columns = "user_id, date, ip, sys, action, description";
                                    $values = [$user_id, $date, $ip, $sys, "login", "تسجيل دخول ناجح: " . $email];
                                    dbInsert("logs", $columns, $values);
                                    echo "<meta http-equiv='Refresh' content='0; url=../dashboard'>";
                                    sweet("success", "نجاح", "تم الدخول بنجاح");
                                    exit;
                                } elseif ($login == "2fa_required") {
                                    // كلمة المرور صحيحة — الحساب يتطلب رمز تحقق بخطوتين، أعِد رسم
                                    // الصفحة لتعرض نموذج إدخال الرمز (راجع $pending2faId أعلاه).
                                    $pending2faId = (int) $_SESSION['pending_2fa_admin_id'];
                                } else {
                                    $sys = safer(getOS()) . " - " . safer(getBrowser());
                                    $columns = "user_id, date, ip, sys, action, description";
                                    $values = [null, date("Y-m-d H:i:s"), $ip, $sys, "login_failed", "محاولة دخول فاشلة: " . $email];
                                    dbInsert("logs", $columns, $values);
                                    sweet("error", "خطأ", "البيانات المدخلة غير صحيحة");
                                }
                            } else {
                                sweet("error", "خطأ", "اسم المستخدم وكلمة المرور مطلوبة");
                            }
                        }
                        ?>

                        <?php if ($pending2faId): ?>
                        <form id="formTotp" class="mb-3" method="post">
                            <p class="text-muted small">أدخل رمز التحقق المكوَّن من 6 أرقام من تطبيق المصادقة، أو أحد رموز الاسترجاع الاحتياطية.</p>
                            <div class="form-floating form-floating-outline mb-3">
                                <input type="text" inputmode="numeric" autocomplete="one-time-code" class="form-control" id="totp_code" name="totp_code" placeholder="000000" maxlength="10" autofocus required />
                                <label for="totp_code">رمز التحقق</label>
                            </div>
                            <div class="mb-3">
                                <?php $csrf->input(); ?>
                                <button class="btn btn-primary d-grid w-100" type="submit" name="verify_2fa">تأكيد الدخول</button>
                            </div>
                        </form>
                        <?php else: ?>
                        <form id="formAuthentication" class="mb-3" method="post">
                            <div class="form-floating form-floating-outline mb-3">
                                <input type="text" class="form-control" id="email" name="email" placeholder="أدخل اسم المستخدم او البريد الالكتروني" autofocus required />
                                <label for="email">اسم المستخدم او الايميل</label>
                            </div>
                            <div class="mb-3">
                                <div class="form-password-toggle">
                                    <div class="input-group input-group-merge">
                                        <div class="form-floating form-floating-outline">
                                            <input type="password" id="password" class="form-control" name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" aria-describedby="password" required />
                                            <label for="password">كلمة المرور</label>
                                        </div>
                                        <span class="input-group-text cursor-pointer"><i class="mdi mdi-eye-off-outline"></i></span>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <?php $csrf->input(); ?>
                                <button class="btn btn-primary d-grid w-100" type="submit" name="submit">الدخـول</button>
                            </div>
                        </form>
                        <?php endif; ?>


                        <div class="divider my-4">
                            <div class="divider-text"><span class="text-danger"><i class="tf-icons mdi mdi-heart"></i></span></div>
                        </div>

                        <div class="d-flex justify-content-center gap-2">
                            <p>برمجة وتطوير: <a href="#" target="_blank">محمد عكور</a></p>
                        </div>
                    </div>
                </div>
                <!-- /Login -->
            </div>
        </div>
    </div>



    <div class="content-backdrop fade"></div>
    </div>
    <!-- Content wrapper -->
    </div>
    <!-- / Layout page -->
    </div>

    <!-- Overlay -->
    <div class="layout-overlay layout-menu-toggle"></div>

    <div class="drag-target"></div>
    </div>

    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/node-waves/node-waves.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../assets/vendor/libs/hammer/hammer.js"></script>
    <script src="../assets/vendor/libs/i18n/i18n.js"></script>
    <script src="../assets/vendor/libs/typeahead-js/typeahead.js"></script>
    <script src="../assets/vendor/js/menu.js"></script>


    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="../assets/vendor/libs/bootstrap-maxlength/bootstrap-maxlength.js"></script>
    <script src="../assets/js/pages-auth.js"></script>

    <!-- Main JS -->
    <script src="../assets/js/main.js"></script>

</body>

</html>