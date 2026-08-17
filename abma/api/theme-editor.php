<?php
header('Content-Type: application/json; charset=utf-8');
requireOwner();

/**
 * محرر أكواد القالب المفعل (Twig / CSS / JS / JSON)
 * كل العمليات هنا مقيّدة داخل مجلد القالب النشط فقط: templates/{theme}/
 * ولا يُسمح بالتعامل إلا مع امتدادات آمنة (لا PHP إطلاقاً) لمنع رفع أكواد تنفيذية.
 * requireOwner() أعلاه تمنع أي حساب editor من الوصول لهذه الواجهة حتى لو استُدعيت مباشرة.
 */

const TE_ALLOWED_EXT = ['twig', 'css', 'js', 'json', 'svg', 'txt', 'md'];
const TE_MAX_SIZE = 2 * 1024 * 1024; // 2MB لكل ملف

function te_valid_relpath($rel)
{
    if (!is_string($rel) || $rel === '') return false;
    if (strpos($rel, "\0") !== false) return false;
    $rel = str_replace('\\', '/', $rel);
    if ($rel[0] === '/') return false;
    $parts = explode('/', $rel);
    foreach ($parts as $part) {
        if ($part === '' || $part === '.' || $part === '..') return false;
        if (!preg_match('/^[A-Za-z0-9_\-. ]+$/u', $part)) return false;
    }
    return true;
}

function te_ext_allowed($path)
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return in_array($ext, TE_ALLOWED_EXT, true);
}

function te_theme_base()
{
    global $site;
    return realpath(getpath() . 'templates/' . $site['theme']);
}

// إرجاع المسار الحقيقي لملف/مجلد موجود بالفعل، بعد التأكد أنه داخل مجلد القالب فقط
function te_resolve_existing($rel)
{
    $base = te_theme_base();
    if (!$base || !te_valid_relpath($rel)) return false;
    $full = realpath($base . '/' . $rel);
    if ($full === false) return false;
    if ($full !== $base && strpos($full, $base . DIRECTORY_SEPARATOR) !== 0) return false;
    return $full;
}

// إرجاع المسار الحقيقي المستهدف لملف/مجلد جديد غير موجود بعد، بشرط أن مجلده الأب داخل القالب
function te_resolve_new($rel)
{
    $base = te_theme_base();
    if (!$base || !te_valid_relpath($rel)) return false;
    if (file_exists($base . '/' . $rel)) return false;
    $parentRel = dirname($rel);
    $parentFull = ($parentRel === '.') ? $base : realpath($base . '/' . $parentRel);
    if ($parentFull === false) return false;
    if ($parentFull !== $base && strpos($parentFull, $base . DIRECTORY_SEPARATOR) !== 0) return false;
    return $base . '/' . $rel;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$action = $data['action'] ?? '';

if (empty($action)) {
    echo json_encode(['status' => false, 'message' => 'طلب غير صالح']);
    exit;
}

if ($action === 'read') {
    $rel = $data['path'] ?? '';
    $full = te_resolve_existing($rel);
    if (!$full || is_dir($full) || !te_ext_allowed($full)) {
        echo json_encode(['status' => false, 'message' => 'الملف غير موجود أو غير قابل للتحرير']);
        exit;
    }
    echo json_encode(['status' => true, 'content' => file_get_contents($full)]);
    exit;
}

if ($action === 'save') {
    $csrf->verify('ajax');
    $rel = $data['path'] ?? '';
    $content = $data['content'] ?? '';
    $full = te_resolve_existing($rel);
    if (!$full || is_dir($full) || !te_ext_allowed($full)) {
        echo json_encode(['status' => false, 'message' => 'الملف غير موجود أو غير قابل للتحرير']);
        exit;
    }
    if (strlen($content) > TE_MAX_SIZE) {
        echo json_encode(['status' => false, 'message' => 'حجم الملف أكبر من الحد المسموح به']);
        exit;
    }
    file_put_contents($full, $content);
    logAction("theme_edit_save", "تم تعديل ملف القالب: " . $rel);
    echo json_encode(['status' => true, 'message' => 'تم الحفظ بنجاح']);
    exit;
}

if ($action === 'create_file') {
    $csrf->verify('ajax');
    $rel = trim($data['path'] ?? '', '/');
    if (!te_ext_allowed($rel)) {
        echo json_encode(['status' => false, 'message' => 'امتداد الملف غير مسموح به']);
        exit;
    }
    $full = te_resolve_new($rel);
    if (!$full) {
        echo json_encode(['status' => false, 'message' => 'اسم غير صالح أو الملف موجود مسبقاً']);
        exit;
    }
    file_put_contents($full, '');
    echo json_encode(['status' => true, 'message' => 'تم إنشاء الملف']);
    exit;
}

if ($action === 'create_folder') {
    $csrf->verify('ajax');
    $rel = trim($data['path'] ?? '', '/');
    $full = te_resolve_new($rel);
    if (!$full) {
        echo json_encode(['status' => false, 'message' => 'اسم غير صالح أو المجلد موجود مسبقاً']);
        exit;
    }
    mkdir($full, 0755, true);
    logAction("theme_edit_create_folder", "تم إنشاء مجلد في القالب: " . $rel);
    echo json_encode(['status' => true, 'message' => 'تم إنشاء المجلد']);
    exit;
}

if ($action === 'delete') {
    $csrf->verify('ajax');
    $rel = $data['path'] ?? '';
    $full = te_resolve_existing($rel);
    if (!$full) {
        echo json_encode(['status' => false, 'message' => 'العنصر غير موجود']);
        exit;
    }
    // منع حذف مجلد القالب نفسه
    if ($full === te_theme_base()) {
        echo json_encode(['status' => false, 'message' => 'لا يمكن حذف مجلد القالب نفسه']);
        exit;
    }
    if (is_dir($full)) {
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($full, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($full);
    } else {
        unlink($full);
    }
    logAction("theme_edit_delete", "تم حذف عنصر من القالب: " . $rel);
    echo json_encode(['status' => true, 'message' => 'تم الحذف']);
    exit;
}

if ($action === 'rename') {
    $csrf->verify('ajax');
    $rel = $data['path'] ?? '';
    $newName = basename(trim($data['new_name'] ?? '', '/'));
    $fullOld = te_resolve_existing($rel);
    if (!$fullOld || $newName === '' || !preg_match('/^[A-Za-z0-9_\-. ]+$/u', $newName)) {
        echo json_encode(['status' => false, 'message' => 'اسم غير صالح']);
        exit;
    }
    if (!is_dir($fullOld) && !te_ext_allowed($newName)) {
        echo json_encode(['status' => false, 'message' => 'امتداد الملف غير مسموح به']);
        exit;
    }
    $newRel = trim(dirname($rel) === '.' ? $newName : dirname($rel) . '/' . $newName, '/');
    $fullNew = te_resolve_new($newRel);
    if (!$fullNew) {
        echo json_encode(['status' => false, 'message' => 'الاسم مستخدم مسبقاً أو غير صالح']);
        exit;
    }
    rename($fullOld, $fullNew);
    echo json_encode(['status' => true, 'message' => 'تم إعادة التسمية']);
    exit;
}

echo json_encode(['status' => false, 'message' => 'إجراء غير معروف']);
