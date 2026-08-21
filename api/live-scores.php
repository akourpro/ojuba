<?php
/**
 * نقطة عامة (بدون تسجيل دخول، GET فقط) تُعيد بيانات "جدول المباريات" الحالية
 * بصيغة JSON — تُستخدم لتحديث لوحة النتائج بالصفحة الرئيسية تلقائياً كل بضع
 * ثوانٍ دون إعادة تحميل الصفحة كاملة (راجع templates/ojubasport/assets/js/
 * live-scores.js). لا تكتب استعلام SQL جديداً هنا — البيانات تأتي بالكامل من
 * getMatchesForDisplay() (includes/functions.php)، فتبقى النتيجة مطابقة تماماً
 * لما يُعرض بالصفحة الرئيسية (نفس الترتيب: مباشر > قادمة > منتهية).
 *
 * القراءة فقط (GET) — بيانات المباريات عامة أصلاً بالموقع، فلا حاجة لأي حماية
 * إضافية (CSRF/rate-limit) أبعد من كونها READ-ONLY ولا تُعدّل أي شيء بقاعدة
 * البيانات.
 */
include_once dirname(__DIR__) . '/includes/config.php';
include_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!moduleEnabled('matches') || !matchesTableExists()) {
    echo json_encode(['status' => false, 'matches' => []]);
    die();
}

$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 6;
if ($limit < 1) {
    $limit = 6;
}
if ($limit > 20) {
    $limit = 20;
}

$matches = getMatchesForDisplay($limit);

// نُبقي المخرجات UTF-8 سليمة على أي سيرفر (نفس نمط utf8ize() المستخدم بملفات
// PHP الجذر الأخرى عند بناء JSON للمخرجات العامة)
function utf8izeLiveScores($mixed)
{
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = utf8izeLiveScores($value);
        }
    } elseif (is_string($mixed)) {
        $mixed = mb_convert_encoding($mixed, 'UTF-8', 'UTF-8');
    }
    return $mixed;
}

// "matches_route" هو الرابط الحالي (المخصص أو الافتراضي) لنوع مسار "matches" —
// يجب أن يبنيه العميل (live-scores.js) روابط /{matches_route}/{id} به بدل تجميد
// كلمة "matches" ثابتة، وإلا لن يعمل نظام تخصيص الروابط بعد أول تحديث تلقائي.
echo json_encode([
    'status' => true,
    'matches' => utf8izeLiveScores($matches),
    'matches_route' => routeSlug('matches'),
    'generated_at' => time(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
