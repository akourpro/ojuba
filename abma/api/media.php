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
<?php
header('Content-Type: application/json; charset=utf-8');

/**
 * مكتبة الوسائط الموحّدة — متاحة لأي أدمن مسجّل دخول (owner أو editor)
 * لأن رفع الصور جزء أساسي من إدارة المحتوى، وليست وظيفة حساسة تستوجب owner فقط.
 */
if (empty(currentAdmin())) {
    http_response_code(403);
    echo json_encode(['status' => false, 'message' => 'غير مصرح لك بالوصول']);
    exit;
}

const MEDIA_DIR_REL = 'files/media/';
const MEDIA_MAX_MB = 15;

function media_dir_abs()
{
    return getpath() . MEDIA_DIR_REL;
}

// يدعم الطلبات بصيغة form-data (رفع الملفات) وأيضاً بصيغة JSON خام (الحذف)
// ملاحظة: عند إرسال JSON خام بدون contentType صريح، يحاول PHP تفسيره كـ
// application/x-www-form-urlencoded فيضع النص كاملاً كمفتاح غريب داخل $_POST،
// لذا لا يمكن الاعتماد على empty($_POST) لتحديد وجود جسم JSON فعلي.
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

$jsonBody = [];
if (empty($_FILES) && empty($action)) {
    $decoded = json_decode(file_get_contents('php://input'), true);
    if (is_array($decoded)) {
        $jsonBody = $decoded;
        $action = $jsonBody['action'] ?? '';
    }
}

if ($action === 'list') {
    global $rows, $countrows;
    $search = safer($_GET['search'] ?? '');
    $conditions = [];
    $vars = [];
    if ($search !== '') {
        $conditions[] = 'original_name LIKE ?';
        $vars[] = '%' . $search . '%';
    }
    // فلترة اختيارية حسب الامتداد، مثال: ext=html,htm لعرض قوالب البريد فقط
    $extParam = safer($_GET['ext'] ?? '');
    if ($extParam !== '') {
        $exts = array_filter(array_map('trim', explode(',', strtolower($extParam))));
        if (!empty($exts)) {
            $extConds = [];
            foreach ($exts as $e) {
                $extConds[] = 'filename LIKE ?';
                $vars[] = '%.' . $e;
            }
            $conditions[] = '(' . implode(' OR ', $extConds) . ')';
        }
    }
    $where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    dbSelect('media', 'COUNT(*) as cnt', $where, $vars);
    $total = (int) ($rows[0]['cnt'] ?? 0);
    $pagination = paginate($total, 24);

    dbSelect(
        'media',
        'id, filename, original_name, mime_type, size, date',
        $where . ' ORDER BY id DESC LIMIT ' . (int) $pagination['per_page'] . ' OFFSET ' . (int) $pagination['offset'],
        $vars
    );
    global $site;
    $items = [];
    foreach ($rows as $row) {
        $row['url'] = $site['site_url'] . MEDIA_DIR_REL . $row['filename'];
        $items[] = $row;
    }
    echo json_encode(['status' => true, 'items' => $items, 'pagination' => $pagination]);
    exit;
}

if ($action === 'upload') {
    $csrf->verify('ajax');

    if (empty($_FILES['file']['name'])) {
        echo json_encode(['status' => false, 'message' => 'الرجاء اختيار ملف']);
        exit;
    }

    $base = 'media-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
    $imageExts = ['png', 'jpg', 'jpeg', 'svg', 'webp', 'avif', 'gif', 'bmp', 'ico', 'tiff', 'tif', 'heic', 'heif'];
    $uploadExt = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    if (in_array($uploadExt, $imageExts)) {
        $up = up($base, 'file', media_dir_abs(), MEDIA_MAX_MB);
    } else {
        // ملفات غير الصور (قوالب HTML، مستندات، أرشيفات...) عبر fileup()
        $up = fileup($base, 'file', media_dir_abs(), MEDIA_MAX_MB);
    }

    if ($up !== 'uploaded_done') {
        echo json_encode(['status' => false, 'message' => is_string($up) ? $up : 'فشل رفع الملف']);
        exit;
    }

    global $filename;
    $admin = currentAdmin();
    $originalName = safer($_FILES['file']['name']);
    $mime = @finfo_file(finfo_open(FILEINFO_MIME_TYPE), media_dir_abs() . $filename);
    $size = @filesize(media_dir_abs() . $filename);

    $id = dbInsert(
        'media',
        'filename, original_name, mime_type, size, uploaded_by, date',
        [$filename, $originalName, $mime, $size, $admin['id'] ?? null, date('Y-m-d H:i:s')]
    );

    logAction('media_upload', 'تم رفع ملف لمكتبة الوسائط: ' . $filename);

    global $site;
    echo json_encode([
        'status' => true,
        'message' => 'تم الرفع بنجاح',
        'item' => [
            'id' => $id,
            'filename' => $filename,
            'original_name' => $originalName,
            'mime_type' => $mime,
            'size' => $size,
            'url' => $site['site_url'] . MEDIA_DIR_REL . $filename,
        ],
    ]);
    exit;
}

if ($action === 'delete') {
    $csrf->verify('ajax');
    $id = numer($_POST['id'] ?? ($jsonBody['id'] ?? 0));

    dbSelect('media', 'id, filename', 'WHERE id = ? LIMIT 1', [$id]);
    global $rows, $countrows;
    if ($countrows !== 1) {
        echo json_encode(['status' => false, 'message' => 'الملف غير موجود']);
        exit;
    }
    $item = $rows[0];
    $path = media_dir_abs() . $item['filename'];
    if (is_file($path)) {
        @unlink($path);
    }
    dbDelete('media', 'WHERE id = ? LIMIT 1', [$id]);
    logAction('media_delete', 'تم حذف ملف من مكتبة الوسائط: ' . $item['filename']);

    echo json_encode(['status' => true, 'message' => 'تم الحذف']);
    exit;
}

echo json_encode(['status' => false, 'message' => 'إجراء غير معروف']);
