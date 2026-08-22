<?php
// بوتستراب صريح خفيف (بدون تسجيل دخول تلقائي أو هيدر/فوتر إداري — هذا الملف
// صفحة دخول/خروج أو نقطة AJAX تتحقق من الصلاحيات بنفسها) بدل الاعتماد على
// auto_prepend_file. راجع تعليق abma/minimal.php لتفاصيل كاملة.
require_once dirname(__DIR__) . '/minimal.php';
?>
<?php
/**
 * استيراد قالب جديد عبر ملف zip — للمالك (Owner) فقط.
 *
 * تدفّق العمل على مرحلتين لتفادي الكتابة فوق قالب موجود بدون تأكيد:
 * 1) action=upload  → يستقبل الملف، يتحقق منه بالكامل (امتدادات، Zip Slip، أحجام،
 *    ملفات .htaccess ممنوعة، فحص أكواد PHP مضمّنة داخل ملفات نصية)، يستخرجه لمجلد
 *    مؤقت محمي. لو الاسم غير متكرر يُنقل مباشرة لـ templates/ وينتهي الأمر. لو
 *    متكرر، يُبقي الملفات بمجلد مؤقت ويعيد "conflict" بانتظار تأكيد الاستبدال.
 * 2) action=confirm → يُستدعى فقط عند وجود تعارض بالاسم، إما لإتمام الاستبدال أو
 *    لإلغاء العملية وتنظيف المجلد المؤقت.
 *
 * ملاحظة: لا حاجة لتضمين includes/config.php أو includes/functions.php أو
 * includes/csrf.php هنا — abma/.htaccess يُحمّل abma/autoload.php تلقائياً قبل
 * أي ملف PHP داخل abma/ (بما فيها هذا الملف)، وهو ما يوفر $csrf وكل الدوال
 * المشتركة والتحقق من تسجيل الدخول جاهزة مسبقاً.
 */

requireOwner();
header('Content-Type: application/json; charset=utf-8');

function jerr($msg)
{
    echo json_encode(['status' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

function rrmdir($dir)
{
    if (!is_dir($dir)) {
        return;
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        if ($item->isDir()) {
            @rmdir($item->getPathname());
        } else {
            @unlink($item->getPathname());
        }
    }
    @rmdir($dir);
}

$action = safer($_POST['action'] ?? '');

$tmpRoot = getpath() . 'abma/tmp/theme_import/';
if (!is_dir($tmpRoot)) {
    @mkdir($tmpRoot, 0755, true);
}
// حماية دائمة لمجلد التخزين المؤقت: منع أي وصول مباشر عبر الرابط لأي ملف بداخله،
// بغض النظر عن نوعه (الملفات هنا للفحص الداخلي فقط، لا يجب أن تصل عبر HTTP إطلاقاً)
$tmpHtaccess = $tmpRoot . '.htaccess';
if (!is_file($tmpHtaccess)) {
    @file_put_contents($tmpHtaccess, "Require all denied\n");
}

// تنظيف مجلدات مؤقتة قديمة (أكثر من ساعة) في كل استدعاء — بديل بسيط عن مهمة مجدولة
foreach ((glob($tmpRoot . '*', GLOB_ONLYDIR) ?: []) as $oldDir) {
    if (@filemtime($oldDir) < time() - 3600) {
        rrmdir($oldDir);
    }
}

$allowedExt = ['twig', 'html', 'htm', 'css', 'scss', 'sass', 'less', 'map', 'js', 'mjs', 'json', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'avif', 'woff', 'woff2', 'ttf', 'otf', 'eot', 'txt', 'md'];
$textScanExt = ['twig', 'html', 'htm', 'css', 'scss', 'sass', 'less', 'js', 'mjs', 'json', 'txt', 'md'];

const MAX_ZIP_SIZE = 20 * 1024 * 1024;          // 20 ميجا لملف الـ zip نفسه
const MAX_TOTAL_UNCOMPRESSED = 100 * 1024 * 1024; // 100 ميجا بعد فك الضغط بالكامل
const MAX_FILE_COUNT = 3000;
const MAX_SINGLE_FILE = 15 * 1024 * 1024;        // 15 ميجا لأي ملف منفرد داخل الأرشيف

if ($action === 'upload') {


    if (empty($_FILES['zipfile']) || !is_uploaded_file($_FILES['zipfile']['tmp_name'] ?? '')) {
        jerr('لم يتم رفع أي ملف');
    }
    if (($_FILES['zipfile']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        jerr('فشل رفع الملف إلى السيرفر');
    }

    $tmpUploaded = $_FILES['zipfile']['tmp_name'];
    $origName = $_FILES['zipfile']['name'];
    $size = filesize($tmpUploaded);

    if ($size <= 0) {
        jerr('الملف المرفوع فارغ');
    }
    if ($size > MAX_ZIP_SIZE) {
        jerr('حجم الملف أكبر من الحد المسموح (20 ميجابايت)');
    }
    if (strtolower(pathinfo($origName, PATHINFO_EXTENSION)) !== 'zip') {
        jerr('يجب أن يكون الملف بصيغة zip فقط');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmpUploaded);
    finfo_close($finfo);
    $okMimes = ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'];
    if (!in_array($mime, $okMimes, true)) {
        jerr('نوع الملف غير صالح، يجب أن يكون أرشيف zip حقيقي');
    }

    if (!class_exists('ZipArchive')) {
        jerr('السيرفر لا يدعم معالجة ملفات zip حالياً (إضافة ZipArchive غير مفعّلة بـ PHP)');
    }

    $zip = new ZipArchive();
    if ($zip->open($tmpUploaded) !== true) {
        jerr('تعذّر فتح ملف zip، قد يكون تالفاً');
    }

    $count = $zip->numFiles;
    if ($count <= 0) {
        jerr('الأرشيف فارغ');
    }
    if ($count > MAX_FILE_COUNT) {
        jerr('عدد الملفات داخل الأرشيف كبير جداً (الحد الأقصى ' . MAX_FILE_COUNT . ')');
    }

    // تحليل الأسماء: كشف مجلد جذري واحد يلف كل شيء + فحص أمان المسارات + الامتدادات + الأحجام
    $rootDirs = [];
    $totalUncompressed = 0;

    for ($i = 0; $i < $count; $i++) {
        $stat = $zip->statIndex($i);
        $name = $stat['name'];

        // تجاهل إدخالات المجلدات نفسها (تنتهي بـ /)
        if (substr($name, -1) === '/') {
            continue;
        }

        // حماية Zip Slip: أي محاولة إفلات خارج مجلد الاستخراج
        if (
            strpos($name, '..') !== false ||
            (isset($name[0]) && $name[0] === '/') ||
            preg_match('#^[a-zA-Z]:#', $name) ||
            strpos($name, "\0") !== false
        ) {
            $zip->close();
            jerr('اسم ملف غير آمن داخل الأرشيف: ' . $name);
        }

        $baseName = basename($name);
        if (in_array(strtolower($baseName), ['.htaccess', '.user.ini', 'web.config', 'php.ini', '.htpasswd'], true)) {
            $zip->close();
            jerr('غير مسموح بوجود ملف إعداد خادم (' . $baseName . ') داخل القالب — القوالب تقبل Twig/HTML/CSS/SCSS/JS ووسائط ثابتة فقط');
        }

        $fext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($fext, $allowedExt, true)) {
            $zip->close();
            jerr('نوع ملف غير مسموح به داخل القالب: ' . $name . ' — الامتدادات المسموحة فقط: Twig, HTML, CSS, SCSS/SASS/LESS, JS, JSON، وملفات وسائط ثابتة');
        }

        if ($stat['size'] > MAX_SINGLE_FILE) {
            $zip->close();
            jerr('حجم أحد الملفات كبير جداً: ' . $name);
        }
        $totalUncompressed += $stat['size'];
        if ($totalUncompressed > MAX_TOTAL_UNCOMPRESSED) {
            $zip->close();
            jerr('الحجم الإجمالي للملفات بعد فك الضغط أكبر من الحد المسموح (100 ميجابايت)');
        }

        $firstSegment = strstr($name, '/', true);
        if ($firstSegment !== false && $firstSegment !== '') {
            $rootDirs[$firstSegment] = true;
        } else {
            $rootDirs['#ROOT#'] = true; // يوجد ملف مباشرة في جذر الأرشيف بدون مجلد لاف
        }
    }

    $wrapped = (count($rootDirs) === 1 && !isset($rootDirs['#ROOT#']));
    $wrapFolder = $wrapped ? array_key_first($rootDirs) : null;

    // استخراج لمجلد مؤقت محمي
    $token = bin2hex(random_bytes(16));
    $stagingDir = $tmpRoot . $token;
    if (!@mkdir($stagingDir, 0755, true)) {
        $zip->close();
        jerr('تعذّر إنشاء مجلد مؤقت على السيرفر');
    }
    if (!$zip->extractTo($stagingDir)) {
        $zip->close();
        rrmdir($stagingDir);
        jerr('فشل استخراج ملفات الأرشيف');
    }
    $zip->close();

    $themeRoot = $wrapped ? rtrim($stagingDir . '/' . $wrapFolder, '/') : rtrim($stagingDir, '/');

    // تحقق نهائي بعد الاستخراج الفعلي: كل ملف يقع فعلاً داخل مجلد التخزين المؤقت
    // (دفاع إضافي مستقل عن فحص الأسماء أعلاه)، بالإضافة لفحص محتوى الملفات النصية
    // بحثاً عن أكواد PHP مُهرَّبة داخل ملفات يُفترض أنها ثابتة بحتة
    $realStaging = realpath($stagingDir);
    if ($realStaging !== false && is_dir($stagingDir)) {
        $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stagingDir, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($iter as $file) {
            if ($file->isDir()) {
                continue;
            }
            $real = realpath($file->getPathname());
            if ($real === false || strpos($real, $realStaging) !== 0) {
                rrmdir($stagingDir);
                jerr('تم رصد محاولة إفلات مسار غير آمنة داخل الأرشيف');
            }
            $fext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
            if (in_array($fext, $textScanExt, true)) {
                $contents = @file_get_contents($file->getPathname());
                if ($contents !== false && (stripos($contents, '<?php') !== false || stripos($contents, '<?=') !== false)) {
                    rrmdir($stagingDir);
                    jerr('تم رصد كود PHP داخل ملف من المفترض أنه ثابت بحت: ' . $file->getFilename() . ' — القوالب لا تقبل أي كود من جانب الخادم');
                }
            }
        }
    }

    if (!is_dir($themeRoot)) {
        rrmdir($stagingDir);
        jerr('تعذّر تحديد مجلد القالب داخل الأرشيف');
    }

    // تحديد اسم/slug القالب: اسم المجلد اللاف إن وُجد، وإلا اسم ملف الـ zip نفسه
    $slugSource = $wrapped ? $wrapFolder : pathinfo($origName, PATHINFO_FILENAME);
    $slug = strtolower(preg_replace('/[^a-z0-9_-]+/', '-', $slugSource));
    $slug = trim($slug, '-_');
    if ($slug === '' || $slug === '.' || $slug === '..') {
        $slug = 'theme-' . substr($token, 0, 8);
    }

    $warnings = [];
    if (!is_file($themeRoot . '/screenshot.png')) {
        // لا يوجد ملف screenshot.png داخل القالب المستورَد، ومعرض القوالب (abma/templates.php)
        // يخفي أي قالب لا يملك هذا الملف تماماً عبر file_exists(). لذلك ننشئ صورة بديلة
        // شفافة 1x1 بكسل بدل ترك القالب مخفياً عن المستخدم — تظهر كخلفية فارغة بالبطاقة
        // فقط لإظهار القالب بالمعرض، ويمكن للمستخدم استبدالها لاحقاً بصورة حقيقية.
        $blankPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        @file_put_contents($themeRoot . '/screenshot.png', $blankPng);
        $warnings[] = 'لا يوجد ملف screenshot.png داخل القالب — تم إنشاء صورة فارغة تلقائياً حتى يظهر القالب في المعرض، ويُفضّل استبدالها لاحقاً بصورة حقيقية للقالب.';
    }
    $themeJsonPath = $themeRoot . '/theme.json';
    if (is_file($themeJsonPath)) {
        $decoded = json_decode(file_get_contents($themeJsonPath), true);
        if (!is_array($decoded)) {
            $warnings[] = 'ملف theme.json داخل القالب غير صالح (JSON غير سليم) — سيتم التعامل مع القالب بإعدادات افتراضية حتى يُصلَح.';
        }
    } else {
        $warnings[] = 'لا يوجد ملف theme.json داخل القالب — سيتم تفعيل الوحدات القديمة الأساسية تلقائياً فقط.';
    }

    $targetDir = getpath() . 'templates/' . $slug;
    $conflict = is_dir($targetDir);

    if (!$conflict) {
        if (!@rename($themeRoot, $targetDir)) {
            rrmdir($stagingDir);
            jerr('تعذّر نقل ملفات القالب إلى مجلد templates');
        }
        rrmdir($stagingDir); // تنظيف أي بقايا (مثل المجلد اللاف الفارغ إن وُجد)
        logAction('theme_import', 'تم استيراد قالب جديد عبر ملف zip: ' . $slug);
        echo json_encode(['status' => true, 'imported' => true, 'slug' => $slug, 'message' => 'تم استيراد القالب بنجاح', 'warnings' => $warnings], JSON_UNESCAPED_UNICODE);
        exit;
    } else {
        echo json_encode([
            'status' => true,
            'conflict' => true,
            'slug' => $slug,
            'staging_token' => $token,
            'message' => 'يوجد قالب بنفس الاسم (' . $slug . ') مسبقاً — هل تريد استبداله بالكامل؟',
            'warnings' => $warnings,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if ($action === 'confirm') {


    $token = safer($_POST['staging_token'] ?? '');
    $slug = strtolower(preg_replace('/[^a-z0-9_-]+/', '-', safer($_POST['slug'] ?? '')));
    $confirm = !empty($_POST['confirm']) && $_POST['confirm'] !== 'false' && $_POST['confirm'] !== '0';

    if ($token === '' || $slug === '' || !preg_match('/^[a-f0-9]{32}$/', $token)) {
        jerr('طلب غير صالح');
    }

    $stagingDir = $tmpRoot . $token;
    if (!is_dir($stagingDir)) {
        jerr('انتهت صلاحية عملية الاستيراد (مضى أكثر من ساعة أو تم إلغاؤها) — يرجى رفع الملف مجدداً');
    }

    if (!$confirm) {
        rrmdir($stagingDir);
        echo json_encode(['status' => true, 'cancelled' => true, 'message' => 'تم إلغاء الاستيراد'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // إيجاد مجلد القالب الفعلي داخل staging (نفس منطق الكشف عن مجلد اللف)
    $entries = array_values(array_diff(scandir($stagingDir), ['.', '..', '.htaccess']));
    $themeRoot = $stagingDir;
    if (count($entries) === 1 && is_dir($stagingDir . '/' . $entries[0])) {
        $themeRoot = $stagingDir . '/' . $entries[0];
    }

    $targetDir = getpath() . 'templates/' . $slug;
    if (is_dir($targetDir)) {
        rrmdir($targetDir);
    }
    if (!@rename($themeRoot, $targetDir)) {
        jerr('تعذّر نقل ملفات القالب إلى مجلد templates');
    }
    rrmdir($stagingDir);
    logAction('theme_import', 'تم استيراد قالب عبر zip مع استبدال قالب موجود بنفس الاسم: ' . $slug);
    echo json_encode(['status' => true, 'imported' => true, 'slug' => $slug, 'message' => 'تم استبدال القالب واستيراده بنجاح'], JSON_UNESCAPED_UNICODE);
    exit;
}

jerr('طلب غير صالح');
