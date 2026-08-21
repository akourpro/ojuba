<?php

/**
 * التحقق بخطوتين (Two-Factor Authentication) لحسابات لوحة التحكم — تطبيق TOTP
 * (Time-based One-Time Password) خفيف قائم على PHP فقط، متوافق مع RFC 6238
 * وأي تطبيق مصادقة قياسي (Google Authenticator، Microsoft Authenticator،
 * Authy، 1Password...).
 *
 * رمز QR: لا يُولَّد بهذا الملف (PHP) إطلاقاً — يُرسَم بالكامل داخل متصفح
 * المستخدم عبر مكتبة JS مستقلة (abma/js/qrcode.min.js) من نص otpauth:// الذي
 * تبنيه abma/account.php أصلاً كنص عادي. هذا يتجنّب المخاطر التي كانت تمنع QR
 * سابقاً (خدمة صور خارجية تُسرّب السرّ عبر رابط) دون التضحية بتجربة المستخدم —
 * لا اتصال شبكة، ولا تخزين لصورة QR بقاعدة البيانات أو بالسيرفر إطلاقاً. الإدخال
 * اليدوي (نسخ السرّ Base32) يبقى معروضاً أيضاً كخيار بديل.
 *
 * هذا الملف مستقل وغير مُحمَّل تلقائياً مع كل الصفحات (نفس فلسفة
 * includes/feed_importer.php و includes/updater.php) — يُضمَّن فقط من
 * abma/account.php و abma/auth/login.php و includes/functions.php (عبر
 * require_once عند الحاجة الفعلية).
 */

const TOTP_PERIOD_SECONDS = 30;
const TOTP_DIGITS = 6;
const TOTP_WINDOW = 1; // يقبل الرمز الحالي + رمز سابق/تالي (±30 ثانية) لتفاوت الساعة البسيط

function totpBase32Encode($binary)
{
	$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
	$bits = '';
	for ($i = 0; $i < strlen($binary); $i++) {
		$bits .= str_pad(decbin(ord($binary[$i])), 8, '0', STR_PAD_LEFT);
	}
	$output = '';
	foreach (str_split($bits, 5) as $chunk) {
		if (strlen($chunk) < 5) {
			$chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
		}
		$output .= $alphabet[bindec($chunk)];
	}
	return $output;
}

function totpBase32Decode($base32)
{
	$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
	$base32 = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', (string) $base32));
	$bits = '';
	for ($i = 0; $i < strlen($base32); $i++) {
		$pos = strpos($alphabet, $base32[$i]);
		if ($pos === false) {
			continue;
		}
		$bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
	}
	$binary = '';
	foreach (str_split($bits, 8) as $byte) {
		if (strlen($byte) === 8) {
			$binary .= chr(bindec($byte));
		}
	}
	return $binary;
}

/**
 * توليد سرّ جديد (160 بت، 20 بايت — الطول الموصى به بـRFC 4226) بصيغة Base32
 * جاهزة لإدخالها يدوياً بأي تطبيق مصادقة.
 */
function totpGenerateSecret()
{
	return totpBase32Encode(random_bytes(20));
}

/**
 * توليد الرمز المكوَّن من 6 أرقام لخطوة زمنية محدَّدة ($timeSlice = عدد فترات
 * الـ30 ثانية منذ Unix epoch) — تطبيق مباشر لخوارزمية HOTP (RFC 4226) على عدّاد
 * زمني بدل عدّاد تسلسلي، وهو بالضبط ما يعرّفه TOTP (RFC 6238).
 */
function totpCodeAt($secretBase32, $timeSlice)
{
	$key = totpBase32Decode($secretBase32);
	if ($key === '') {
		return null;
	}
	// عدّاد 8 بايت big-endian — القيمة تُناسب دائماً الـ32 بت الأدنى حتى عام 2106
	$counter = pack('N*', 0, $timeSlice);
	$hash = hash_hmac('sha1', $counter, $key, true);
	$offset = ord(substr($hash, -1)) & 0x0F;
	$part = substr($hash, $offset, 4);
	$value = unpack('N', $part)[1] & 0x7FFFFFFF;
	$code = $value % (10 ** TOTP_DIGITS);
	return str_pad((string) $code, TOTP_DIGITS, '0', STR_PAD_LEFT);
}

/**
 * التحقق من رمز أدخله المستخدم مقابل السرّ المخزَّن — يقبل نافذة ±TOTP_WINDOW
 * خطوة زمنية (بالإضافة للخطوة الحالية) لتفادي رفض رمز صحيح بسبب تأخر بسيط
 * بساعة جهاز المستخدم أو زمن إرسال النموذج.
 */
function totpVerifyCode($secretBase32, $code)
{
	$code = preg_replace('/\D/', '', (string) $code);
	if ($code === '' || strlen($code) !== TOTP_DIGITS || $secretBase32 === '') {
		return false;
	}
	$currentSlice = (int) floor(time() / TOTP_PERIOD_SECONDS);
	for ($i = -TOTP_WINDOW; $i <= TOTP_WINDOW; $i++) {
		$expected = totpCodeAt($secretBase32, $currentSlice + $i);
		if ($expected !== null && hash_equals($expected, $code)) {
			return true;
		}
	}
	return false;
}

/**
 * توليد مجموعة رموز استرجاع احتياطية (لحالة فقدان جهاز المصادقة) — كل رمز 8
 * حروف Hex كبيرة، يُعرَض للمستخدم مرة واحدة فقط عند التفعيل، ويُخزَّن بقاعدة
 * البيانات كمصفوفة JSON من (hash عبر password_hash لكل رمز) — وليس نصاً خاماً،
 * نفس منطق تخزين كلمة المرور نفسها.
 */
function totpGenerateBackupCodes($count = 8)
{
	$codes = [];
	for ($i = 0; $i < $count; $i++) {
		$codes[] = strtoupper(bin2hex(random_bytes(4)));
	}
	return $codes;
}

function totpHashBackupCodes(array $plainCodes)
{
	$hashed = [];
	foreach ($plainCodes as $c) {
		$hashed[] = password_hash($c, PASSWORD_BCRYPT);
	}
	return json_encode($hashed);
}

/**
 * التحقق من رمز استرجاع + استهلاكه (رمز استخدام واحد فقط) — تُحدَّث قاعدة
 * البيانات مباشرة بإزالة الرمز المستخدَم من القائمة عند نجاح المطابقة.
 */
function totpConsumeBackupCode($adminId, $code)
{
	global $con;
	$code = trim((string) $code);
	if ($code === '') {
		return false;
	}
	$stmt = $con->prepare("SELECT totp_backup_codes FROM admins WHERE id = ? LIMIT 1");
	$stmt->execute([$adminId]);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	if (!$row || empty($row['totp_backup_codes'])) {
		return false;
	}
	$hashedCodes = json_decode($row['totp_backup_codes'], true);
	if (!is_array($hashedCodes)) {
		return false;
	}
	foreach ($hashedCodes as $idx => $hash) {
		if (password_verify($code, $hash)) {
			unset($hashedCodes[$idx]);
			$update = $con->prepare("UPDATE admins SET totp_backup_codes = ? WHERE id = ?");
			$update->execute([json_encode(array_values($hashedCodes)), $adminId]);
			return true;
		}
	}
	return false;
}

/**
 * إكمال جلسة الدخول فعلياً بعد نجاح التحقق بخطوتين (رمز TOTP أو رمز استرجاع) —
 * نفس منطق تثبيت الجلسة الموجود بـ login_admin() بالضبط (includes/functions.php)
 * لضمان توافق كامل مع login_check_admin()، مع تنظيف مفاتيح الجلسة "المعلَّقة".
 */
function totpCompleteLogin($adminId)
{
	global $con;
	$stmt = $con->prepare("SELECT id, password FROM admins WHERE id = ? LIMIT 1");
	$stmt->execute([$adminId]);
	$admin = $stmt->fetch(PDO::FETCH_ASSOC);
	if (!$admin) {
		return false;
	}
	$user_browser = 1; // نفس القيمة الثابتة المستخدمة بـ login_admin()/login_check_admin()
	$_SESSION['user_id'] = $admin['id'];
	$_SESSION['login_string'] = hash('sha512', $admin['password'] . $user_browser);
	unset($_SESSION['pending_2fa_admin_id'], $_SESSION['pending_2fa_expires']);
	if (function_exists('do_action')) {
		do_action('ojuba_admin_login', $admin['id'], null);
	}
	return true;
}
