<?php
header('Content-Type: application/json; charset=utf-8');

/**
 * تفعيل/إيقاف وحدات القالب النشط (theme.json) من صفحة "الإضافات" — Owner فقط
 */
if (!isOwner()) {
    http_response_code(403);
    echo json_encode(['status' => false, 'message' => 'غير مصرح لك بالوصول']);
    exit;
}

// قائمة بيضاء بمفاتيح الوحدات المسموح تبديلها (تطابق $defaults في themeManifest())
// حتى لا يمكن حقن أي مفتاح عشوائي داخل theme.json عبر هذا الـ API
$allowedModules = [
    "pages", "blogs", "blog_categories", "services", "portfolio", "categories",
    "contact", "search", "clients", "team", "testimonials", "faq", "stats",
    "pricing", "branches", "certificates", "mailing", "ads",
    "matches", "standings", "videos", "feeds",
];

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$action = safer($data['action'] ?? ($_POST['action'] ?? ''));

if ($action === 'toggle') {
    $csrf->verify('ajax');

    $module = safer($data['module'] ?? '');
    $enabled = !empty($data['enabled']);

    if (!in_array($module, $allowedModules, true)) {
        echo json_encode(['status' => false, 'message' => 'اسم الوحدة غير معروف']);
        exit;
    }

    global $site;
    $themeName = $site['theme'] ?? '';
    if (empty($themeName)) {
        echo json_encode(['status' => false, 'message' => 'تعذّر تحديد القالب النشط']);
        exit;
    }

    $themeDir = getpath() . "templates/" . $themeName . "/";
    $themeFile = $themeDir . "theme.json";

    if (!is_dir($themeDir)) {
        echo json_encode(['status' => false, 'message' => 'مجلد القالب غير موجود']);
        exit;
    }

    // نبني على الـ manifest المدمج (يحتوي كل الوحدات بقيمها الحالية سواء من الملف أو الافتراضيات)
    // حتى لا نفقد أي مفتاح عند إعادة الكتابة، ونتيح إنشاء theme.json من الصفر إن لم يكن موجوداً
    $manifest = themeManifest();
    $modules = $manifest["modules"];
    $modules[$module] = $enabled;

    $json = [
        "name"          => $manifest["name"] ?: $themeName,
        "activity_type" => $manifest["activity_type"] ?? "",
        "modules"       => $modules,
    ];

    $written = @file_put_contents(
        $themeFile,
        json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );

    if ($written === false) {
        echo json_encode(['status' => false, 'message' => 'تعذّر الكتابة في ملف theme.json، تحقق من صلاحيات الملفات على الاستضافة']);
        exit;
    }

    logAction('addon_toggle', ($enabled ? 'تم تفعيل' : 'تم إيقاف') . ' وحدة: ' . $module);
    echo json_encode([
        'status'  => true,
        'message' => $enabled ? 'تم تفعيل الإضافة بنجاح' : 'تم إيقاف الإضافة بنجاح',
        'module'  => $module,
        'enabled' => $enabled,
    ]);
    exit;
}

echo json_encode(['status' => false, 'message' => 'إجراء غير معروف']);
