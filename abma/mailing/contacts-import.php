<?php requireOwner();
mailingRequireModule(); ?>
<?php
$listId = numer($_GET['list_id'] ?? 0);
dbSelect("email_lists", "*", "WHERE id = ? LIMIT 1", [$listId]);
if ($countrows === 0) {
  sweet("error", "خطأ", "القائمة غير موجودة", "mailing/lists");
  exit;
}
$list = $rows[0];
$fields = @unserialize($list['fields']);
if (!is_array($fields)) $fields = [];

const MAILING_IMPORT_MAX_ROWS = 20000;
const MAILING_IMPORT_MAX_SIZE_MB = 5;

/**
 * تحويل نص الملف إلى UTF-8 مهما كانت ترميزه الأصلي (يعالج حالة تصدير الإكسل
 * العربي بترميز Windows-1256 وهي مشكلة شائعة جداً عند تصدير جهات اتصال عربية)،
 * ويزيل BOM إن وُجد في بداية ملفات UTF-8 المُصدَّرة من Excel
 */
function mailing_csv_normalize_encoding($content)
{
  // إزالة UTF-8 BOM
  if (strncmp($content, "\xEF\xBB\xBF", 3) === 0) {
    $content = substr($content, 3);
  }

  // إذا كان UTF-8 صحيحاً فلا داعي للتحويل
  if (mb_check_encoding($content, 'UTF-8')) {
    return $content;
  }

  // جرّب أكثر الترميزات العربية شيوعاً
  foreach (
    [
      'Windows-1256',
      'CP1256',
      'ISO-8859-6',
      'Windows-1252'
    ] as $enc
  ) {

    $converted = @iconv($enc, 'UTF-8//IGNORE', $content);

    if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
      return $converted;
    }
  }

  return $content;
}

/**
 * كشف الفاصل الأكثر ترجيحاً في السطر الأول (فاصلة أو فاصلة منقوطة أو تبويب) —
 * Excel بإعدادات اللغة العربية غالباً يصدّر بفاصلة منقوطة بدل الفاصلة
 */
function mailing_csv_detect_delimiter($firstLine)
{
  $candidates = [',' => substr_count($firstLine, ','), ';' => substr_count($firstLine, ';'), "\t" => substr_count($firstLine, "\t")];
  arsort($candidates);
  $best = array_key_first($candidates);
  return $candidates[$best] > 0 ? $best : ',';
}

/**
 * مطابقة اسم عمود في رأس الملف مع عمود البريد الإلكتروني أو أحد الحقول المخصّصة
 */
function mailing_csv_normalize_header($h)
{
  return trim(mb_strtolower($h, 'UTF-8'));
}

$summary = null;

if (isset($_POST['submit'])) {
  $csrf->verify();
  $status = numer($_POST['status'] ?? 1);

  if (empty($_FILES['csv_file']['name']) or !is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
    sweet("error", "خطأ", "الرجاء اختيار ملف CSV للاستيراد");
    exit;
  }

  $ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
  if ($ext !== 'csv') {
    sweet("error", "خطأ", "صيغة الملف يجب أن تكون CSV فقط");
    exit;
  }
  if ($_FILES['csv_file']['size'] <= 0) {
    sweet("error", "خطأ", "الملف فارغ");
    exit;
  }
  if ($_FILES['csv_file']['size'] > MAILING_IMPORT_MAX_SIZE_MB * 1024 * 1024) {
    sweet("error", "خطأ", "حجم الملف أكبر من الحد المسموح (" . MAILING_IMPORT_MAX_SIZE_MB . " ميجابايت)");
    exit;
  }

  $content = file_get_contents($_FILES['csv_file']['tmp_name']);
  $content = mailing_csv_normalize_encoding($content);

  // توحيد نهايات الأسطر (ملفات Excel أحياناً تستخدم \r فقط كنهاية سطر على ماك القديم)
  $content = str_replace(["\r\n", "\r"], "\n", $content);
  $firstLineEnd = strpos($content, "\n");
  $firstLine = $firstLineEnd !== false ? substr($content, 0, $firstLineEnd) : $content;
  $delimiter = mailing_csv_detect_delimiter($firstLine);

  $fh = fopen('php://temp', 'r+b');
  fwrite($fh, $content);
  rewind($fh);

  $headerRow = fgetcsv($fh, 0, $delimiter);
  if ($headerRow === false || $headerRow === null) {
    fclose($fh);
    sweet("error", "خطأ", "تعذّرت قراءة الملف، تأكد أنه ملف CSV صحيح");
    exit;
  }

  // مطابقة أعمدة الرأس: عمود البريد الإلكتروني + أعمدة الحقول المخصّصة (بالاسم أو المفتاح)
  $emailColIndex = null;
  $fieldColMap = []; // index العمود => key الحقل
  foreach ($headerRow as $idx => $h) {
    $norm = mailing_csv_normalize_header($h);
    if ($norm === '') continue;
    if (
      $emailColIndex === null and (
        strpos($norm, 'email') !== false or strpos($norm, 'mail') !== false or
        strpos($norm, 'بريد') !== false or strpos($norm, 'ايميل') !== false or strpos($norm, 'إيميل') !== false
      )
    ) {
      $emailColIndex = $idx;
      continue;
    }
    foreach ($fields as $f) {
      $labelNorm = mailing_csv_normalize_header($f['label']);
      $keyNorm = mailing_csv_normalize_header($f['key']);
      if ($norm === $labelNorm or $norm === $keyNorm) {
        $fieldColMap[$idx] = $f['key'];
        break;
      }
    }
  }

  if ($emailColIndex === null) {
    fclose($fh);
    sweet("error", "خطأ", "لم يتم العثور على عمود البريد الإلكتروني في الملف. تأكد أن الصف الأول يحتوي على عمود باسم \"email\" (أو \"البريد الإلكتروني\")، ويمكنك تنزيل نموذج CSV جاهز أدناه.");
    exit;
  }

  // جلب كل الإيميلات الموجودة حالياً عبر كل القوائم البريدية مع اسم القائمة، لمنع التكرار العام
  dbSelect(
    "email_list_contacts c",
    "c.email, el.name as list_name",
    "JOIN email_lists el ON el.id = c.list_id"
  );
  $existing = [];
  foreach ($rows as $r) $existing[strtolower($r['email'])] = $r['list_name'];

  $added = 0;
  $invalid = [];
  $duplicate = [];
  $emptyRows = 0;
  $rowCount = 0;
  $tooMany = false;

  while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
    if ($row === null or (count($row) === 1 and trim((string)$row[0]) === '')) continue; // سطر فارغ
    $rowCount++;
    if ($rowCount > MAILING_IMPORT_MAX_ROWS) {
      $tooMany = true;
      break;
    }

    $rawEmail = trim($row[$emailColIndex] ?? '');
    if ($rawEmail === '') {
      $emptyRows++;
      continue;
    }
    $email = safer($rawEmail);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $invalid[] = $email;
      continue;
    }
    $lower = strtolower($email);
    if (isset($existing[$lower])) {
      $duplicate[] = ['email' => $email, 'list' => $existing[$lower]];
      continue;
    }

    $contactData = [];
    foreach ($fieldColMap as $colIdx => $fieldKey) {
      $contactData[$fieldKey] = safer(trim($row[$colIdx] ?? ''));
    }

    dbInsert(
      "email_list_contacts",
      "list_id, email, data, status, date",
      [$listId, $email, serialize($contactData), $status, date('Y-m-d H:i:s')]
    );
    $existing[$lower] = $list['name'];
    $added++;
  }
  fclose($fh);

  logAction('mailing_contacts_import', 'استيراد CSV لقائمة "' . $list['name'] . '": ' . $added . ' مضافة');

  $msg = "تمت إضافة <strong>{$added}</strong> جهة اتصال جديدة إلى قائمة \"" . htmlspecialchars($list['name'], ENT_QUOTES, 'UTF-8') . "\" من الملف.";
  if (!empty($fieldColMap)) {
    $msg .= "<br>تم التعرف تلقائياً على " . count($fieldColMap) . " عمود بيانات إضافي من الملف.";
  }
  if (!empty($duplicate)) {
    $msg .= "<br><br>تم تجاهل " . count($duplicate) . " إيميل لوجوده مسبقاً في قائمة أخرى:";
    $msg .= "<ul style='text-align:right;margin:6px 0 0;padding-inline-start:20px;max-height:180px;overflow-y:auto;'>";
    $shown = 0;
    foreach ($duplicate as $d) {
      if ($shown >= 30) {
        $msg .= "<li>و " . (count($duplicate) - 30) . " إيميل آخر...</li>";
        break;
      }
      $msg .= "<li>" . htmlspecialchars($d['email'], ENT_QUOTES, 'UTF-8') . " — موجود في قائمة \"" . htmlspecialchars($d['list'], ENT_QUOTES, 'UTF-8') . "\"</li>";
      $shown++;
    }
    $msg .= "</ul>";
  }
  if (!empty($invalid)) {
    $msg .= "<br><br>تم تجاهل " . count($invalid) . " صف لصيغة بريد غير صحيحة.";
  }
  if ($emptyRows > 0) {
    $msg .= "<br>تم تجاهل {$emptyRows} صف بدون بريد إلكتروني.";
  }
  if ($tooMany) {
    $msg .= "<br><br><strong>تنبيه:</strong> الملف يحتوي على أكثر من " . MAILING_IMPORT_MAX_ROWS . " صف، تمت معالجة أول " . MAILING_IMPORT_MAX_ROWS . " صف فقط. يرجى تقسيم الملف واستيراد الباقي على دفعات.";
  }

  sweet($added > 0 ? "success" : "warning", "تمت معالجة الملف", $msg, "mailing/lists/" . $listId . "/contacts");
  exit;
}
?>
<title>استيراد جهات اتصال من CSV</title>
<h4 class="py-3 mb-3">
  <span class="text-muted fw-light">مراسلة البريد / <a href="mailing/lists">القوائم البريدية</a> / <a href="mailing/lists/<?php echo $listId ?>/contacts"><?php echo htmlspecialchars($list['name'], ENT_QUOTES, 'UTF-8') ?></a> /</span>
  استيراد من CSV
</h4>

<div class="card mb-4">
  <h5 class="card-header">استيراد جهات اتصال من ملف CSV</h5>
  <form class="card-body" method="post" enctype="multipart/form-data">
    <div class="mb-4">
      <label class="form-label fw-medium">ملف CSV</label>
      <input type="file" class="form-control" name="csv_file" accept=".csv" required>
      <div class="form-text">
        يجب أن يحتوي الملف على صف عناوين (Header) أول عمود فيه بريد إلكتروني باسم مثل <code>email</code>.
        <?php if (!empty($fields)): ?>
          أعمدة إضافية تُطابق تلقائياً حسب اسم الحقل في هذه القائمة:
          <?php foreach ($fields as $f): ?><code class="me-1"><?php echo htmlspecialchars($f['label'], ENT_QUOTES, 'UTF-8') ?></code><?php endforeach; ?>.
          <?php endif; ?>
          يدعم الاستيراد ملفات Excel العربية (Windows-1256) تلقائياً، وكل من الفاصلة والفاصلة المنقوطة كفاصل بين الأعمدة.
      </div>
    </div>

    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <select class="form-select" name="status">
            <option value="1" selected>مفعّلة</option>
            <option value="0">موقوفة</option>
          </select><label>الحالة (تُطبَّق على الكل)</label>
        </div>
      </div>
    </div>

    <div class="pt-4 d-flex gap-2">
      <?php $csrf->input(); ?>
      <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-upload"></i> استيراد</button>
      <a href="api/mailing?action=import_template&amp;list_id=<?php echo $listId ?>" class="btn btn-outline-secondary"><i class="mdi mdi-download"></i> تنزيل نموذج CSV</a>
      <a href="mailing/lists/<?php echo $listId ?>/contacts" class="btn btn-outline-secondary">إلغاء</a>
    </div>
  </form>
</div>