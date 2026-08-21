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
requireOwner();

/**
 * تصدير نسخة احتياطية:
 * type=db    → تفريغ قاعدة البيانات بالكامل بصيغة SQL (بدون الاعتماد على mysqldump)
 * type=theme → ضغط مجلد القالب المفعل حالياً بصيغة ZIP
 */

$type = $_GET['type'] ?? '';

if ($type === 'db') {
    global $con;

    $dbName = defined('DATABASE') ? DATABASE : 'database';
    $filename = 'backup-db-' . date('Y-m-d_His') . '.sql';

    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store, no-cache');

    echo "-- نسخة احتياطية لقاعدة البيانات: $dbName\n";
    echo "-- تاريخ الإنشاء: " . date('Y-m-d H:i:s') . "\n";
    echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

    $tablesStmt = $con->query("SHOW TABLES");
    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        // بنية الجدول
        echo "-- --------------------------------------------------------\n";
        echo "-- بنية الجدول `$table`\n";
        echo "-- --------------------------------------------------------\n\n";
        echo "DROP TABLE IF EXISTS `$table`;\n";

        $createStmt = $con->query("SHOW CREATE TABLE `$table`");
        $createRow = $createStmt->fetch(PDO::FETCH_ASSOC);
        echo $createRow['Create Table'] . ";\n\n";

        // بيانات الجدول
        $countStmt = $con->query("SELECT COUNT(*) FROM `$table`");
        $rowCount = (int) $countStmt->fetchColumn();

        if ($rowCount > 0) {
            echo "-- بيانات الجدول `$table`\n";
            $chunkSize = 500;
            $offset = 0;
            while ($offset < $rowCount) {
                $dataStmt = $con->query("SELECT * FROM `$table` LIMIT $chunkSize OFFSET $offset");
                $columnsWritten = false;
                $valuesList = [];
                while ($row = $dataStmt->fetch(PDO::FETCH_ASSOC)) {
                    if (!$columnsWritten) {
                        $cols = implode('`, `', array_keys($row));
                        $columnsWritten = true;
                    }
                    $vals = array_map(function ($v) use ($con) {
                        if ($v === null) return 'NULL';
                        return $con->quote($v);
                    }, array_values($row));
                    $valuesList[] = '(' . implode(', ', $vals) . ')';
                }
                if (!empty($valuesList)) {
                    echo "INSERT INTO `$table` (`$cols`) VALUES\n" . implode(",\n", $valuesList) . ";\n";
                }
                $offset += $chunkSize;
            }
            echo "\n";
        }
        // إطلاق المخزن المؤقت تدريجياً لملفات القواعد الكبيرة
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    echo "SET FOREIGN_KEY_CHECKS=1;\n";

    logAction("backup_download", "تم تحميل نسخة احتياطية لقاعدة البيانات");
    exit;
}

if ($type === 'theme') {
    if (!class_exists('ZipArchive')) {
        http_response_code(500);
        die('امتداد ZipArchive غير مفعّل على السيرفر، لا يمكن إنشاء نسخة احتياطية من القالب.');
    }

    global $site;
    $themeName = $site['theme'] ?? '';
    $themeDir = getpath() . 'templates/' . $themeName;

    if ($themeName === '' || !is_dir($themeDir)) {
        http_response_code(404);
        die('القالب المفعل غير موجود.');
    }

    $tmpZip = tempnam(sys_get_temp_dir(), 'theme_backup_') . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        http_response_code(500);
        die('تعذّر إنشاء ملف ZIP.');
    }

    $baseLen = strlen(rtrim($themeDir, '/')) + 1;
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($themeDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($items as $item) {
        $localPath = $themeName . '/' . substr($item->getPathname(), $baseLen);
        if ($item->isDir()) {
            $zip->addEmptyDir($localPath);
        } else {
            $zip->addFile($item->getPathname(), $localPath);
        }
    }
    $zip->close();

    $filename = 'backup-theme-' . $themeName . '-' . date('Y-m-d_His') . '.zip';

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tmpZip));
    header('Cache-Control: no-store, no-cache');

    readfile($tmpZip);
    unlink($tmpZip);

    logAction("backup_download", "تم تحميل نسخة احتياطية من القالب: " . $themeName);
    exit;
}

http_response_code(400);
die('نوع النسخة الاحتياطية غير معروف.');
