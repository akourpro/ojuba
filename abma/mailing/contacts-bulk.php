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
<?php requireOwner(); mailingRequireModule(); ?>
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

function mailing_input_type_bulk($type)
{
  $map = ['text' => 'text', 'number' => 'number', 'email' => 'email', 'phone' => 'tel', 'date' => 'date'];
  return $map[$type] ?? 'text';
}

$summary = null;

if (isset($_POST['submit'])) {
  $csrf->verify();
  $status = numer($_POST['status'] ?? 1);
  $rawEmails = $_POST['emails_raw'] ?? '';

  // القيم المشتركة الاختيارية تُطبَّق على كل جهات الاتصال المُضافة في هذه الدفعة
  $sharedData = [];
  foreach ($fields as $f) {
    $val = safer($_POST['field_' . $f['key']] ?? '');
    if ($val !== '') $sharedData[$f['key']] = $val;
  }

  // تقسيم النص المُلصق على أي فاصل شائع: سطر جديد، فاصلة، فاصلة منقوطة، مسافة، تبويب
  $parts = preg_split('/[\s,;]+/', $rawEmails, -1, PREG_SPLIT_NO_EMPTY);

  // إزالة التكرار داخل القائمة الملصقة نفسها (case-insensitive)
  $seen = [];
  $candidates = [];
  foreach ($parts as $p) {
    $p = trim($p);
    $lower = strtolower($p);
    if ($p === '' || isset($seen[$lower])) continue;
    $seen[$lower] = true;
    $candidates[] = $p;
  }

  // جلب كل الإيميلات الموجودة حالياً عبر كل القوائم البريدية (وليس هذه القائمة فقط)
  // مع اسم القائمة التي ينتمي لها كل إيميل، لمنع تكرار نفس الإيميل في أكثر من قائمة
  dbSelect(
    "email_list_contacts c",
    "c.email, el.name as list_name",
    "JOIN email_lists el ON el.id = c.list_id"
  );
  $existing = []; // lower(email) => اسم القائمة التي يوجد بها حالياً
  foreach ($rows as $r) $existing[strtolower($r['email'])] = $r['list_name'];

  $added = 0;
  $invalid = [];
  $duplicate = []; // [['email'=>.., 'list'=>..], ...]

  foreach ($candidates as $email) {
    $email = safer($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $invalid[] = $email;
      continue;
    }
    $lower = strtolower($email);
    if (isset($existing[$lower])) {
      $duplicate[] = ['email' => $email, 'list' => $existing[$lower]];
      continue;
    }
    dbInsert(
      "email_list_contacts",
      "list_id, email, data, status, date",
      [$listId, $email, serialize($sharedData), $status, date('Y-m-d H:i:s')]
    );
    $existing[$lower] = $list['name']; // نمنع تكرار نفس الإيميل مرتين لو كُرِّر في اللصق نفسه بأشكال مختلفة
    $added++;
  }

  logAction('mailing_contacts_bulk_add', 'إضافة جهات اتصال بالجملة لقائمة "' . $list['name'] . '": ' . $added . ' مضافة');

  $msg = "تمت إضافة <strong>{$added}</strong> جهة اتصال جديدة إلى قائمة \"" . htmlspecialchars($list['name'], ENT_QUOTES, 'UTF-8') . "\".";

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
    $msg .= "<br><br>تم تجاهل " . count($invalid) . " لصيغة غير صحيحة.";
  }

  sweet($added > 0 ? "success" : "warning", "تمت المعالجة", $msg, "mailing/lists/" . $listId . "/contacts");
  exit;
}
?>
<title>إضافة جهات اتصال بالجملة</title>
<h4 class="py-3 mb-3">
  <span class="text-muted fw-light">مراسلة البريد / <a href="mailing/lists">القوائم البريدية</a> / <a href="mailing/lists/<?php echo $listId ?>/contacts"><?php echo htmlspecialchars($list['name'], ENT_QUOTES, 'UTF-8') ?></a> /</span>
  إضافة بالجملة
</h4>

<div class="card mb-4">
  <h5 class="card-header">إضافة مجموعة إيميلات دفعة واحدة</h5>
  <form class="card-body" method="post">
    <div class="mb-4">
      <label class="form-label fw-medium">الإيميلات</label>
      <textarea class="form-control" name="emails_raw" rows="10" placeholder="الصق الإيميلات هنا، كل إيميل بسطر جديد (أو مفصولة بفاصلة/مسافة)&#10;example1@domain.com&#10;example2@domain.com&#10;example3@domain.com" required></textarea>
      <div class="form-text">يمكنك اللصق من إكسل أو ملف نصي مباشرة — سطر لكل إيميل، أو مفصولة بفاصلة. سيتم تجاهل أي إيميل موجود مسبقاً في أي قائمة بريدية أخرى (كل إيميل ينتمي لقائمة واحدة فقط) أو بصيغة غير صحيحة، مع عرض ملخص مفصّل بالنتيجة.</div>
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

    <?php if (!empty($fields)): ?>
    <div class="mt-4">
      <hr>
      <h6 class="mb-2">قيم مشتركة للحقول المخصّصة <sup class="text-success">(اختياري)</sup></h6>
      <p class="text-muted small">إن عبّأت قيمة هنا، ستُطبَّق على كل جهات الاتصال المضافة في هذه الدفعة (يمكنك تعديل كل واحدة لاحقاً بشكل فردي). اتركها فارغة إن كانت القيم مختلفة لكل شخص.</p>
      <div class="row g-4">
        <?php foreach ($fields as $f): ?>
        <div class="col-md-6">
          <div class="form-floating form-floating-outline">
            <input type="<?php echo mailing_input_type_bulk($f['type']) ?>" class="form-control" name="field_<?php echo htmlspecialchars($f['key'], ENT_QUOTES, 'UTF-8') ?>" placeholder="<?php echo htmlspecialchars($f['label'], ENT_QUOTES, 'UTF-8') ?>">
            <label><?php echo htmlspecialchars($f['label'], ENT_QUOTES, 'UTF-8') ?></label>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="pt-4">
      <?php $csrf->input(); ?>
      <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-account-multiple-plus-outline"></i> إضافة الكل</button>
      <a href="mailing/lists/<?php echo $listId ?>/contacts" class="btn btn-outline-secondary">إلغاء</a>
    </div>
  </form>
</div>
