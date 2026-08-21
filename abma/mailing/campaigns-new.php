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
dbSelect("email_lists", "id, name", "WHERE status = 1 ORDER BY name ASC");
$lists = $rows;
if (empty($lists)) {
  sweet("error", "خطأ", "أنشئ قائمة بريدية أولاً قبل إنشاء حملة", "mailing/lists");
  exit;
}

if (isset($_POST['submit'])) {
  $csrf->verify();
  $name = safer($_POST['name'] ?? null);
  $subject = safer($_POST['subject'] ?? null);
  $templateMediaPath = safer($_POST['template_media_path'] ?? '');
  $attachmentsMediaJson = $_POST['media_attachments_json'] ?? '[]';
  $attachmentsMedia = json_decode($attachmentsMediaJson, true);
  if (!is_array($attachmentsMedia)) $attachmentsMedia = [];

  if (empty($name) || empty($subject)) {
    sweet("error", "خطأ", "الاسم والموضوع مطلوبان");
  } else {
    // القالب: إما رفع مباشر أو مسار من مكتبة الوسائط
    $templatePath = null;
    if (!empty($_FILES['template_file']['name'])) {
      $base = 'campaign-tpl-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
      $up = fileup($base, 'template_file', getpath() . 'files/mailing/templates', 10);
      if ($up !== 'uploaded_done') {
        sweet("error", "خطأ", is_string($up) ? $up : "فشل رفع ملف القالب");
        exit;
      }
      global $filename;
      $templatePath = 'files/mailing/templates/' . $filename;
    } elseif (!empty($templateMediaPath)) {
      $templatePath = $templateMediaPath;
    }

    if (empty($templatePath)) {
      sweet("error", "خطأ", "يجب رفع قالب HTML أو اختيار قالب من مكتبة الوسائط");
      exit;
    }

    // المرفقات: رفع مباشر (متعدد) + مسارات من مكتبة الوسائط
    // كل عنصر يُخزَّن كـ ['path' => المسار الفعلي على القرص, 'name' => الاسم الأصلي الذي سيظهر في الإيميل]
    // حتى لا يظهر للمستلم الاسم العشوائي المولَّد لمنع تصادم الملفات على القرص
    $attachments = [];
    if (!empty($_FILES['attachment_files']['name'][0])) {
      $count = count($_FILES['attachment_files']['name']);
      for ($i = 0; $i < $count; $i++) {
        if (empty($_FILES['attachment_files']['name'][$i])) continue;
        $originalAttName = safer($_FILES['attachment_files']['name'][$i]);
        $singleFile = [
          'name' => $_FILES['attachment_files']['name'][$i],
          'type' => $_FILES['attachment_files']['type'][$i],
          'tmp_name' => $_FILES['attachment_files']['tmp_name'][$i],
          'error' => $_FILES['attachment_files']['error'][$i],
          'size' => $_FILES['attachment_files']['size'][$i],
        ];
        $_FILES['__single_attachment'] = $singleFile;
        $base = 'campaign-att-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
        $up = fileup($base, '__single_attachment', getpath() . 'files/mailing/attachments', 20);
        if ($up === 'uploaded_done') {
          global $filename;
          $attachments[] = ['path' => 'files/mailing/attachments/' . $filename, 'name' => $originalAttName];
        }
      }
    }
    foreach ($attachmentsMedia as $att) {
      if (is_array($att) and !empty($att['path'])) {
        $attachments[] = ['path' => safer($att['path']), 'name' => safer($att['name'] ?? basename($att['path']))];
      }
    }

    $columns = "name, list_id, subject, template, attachments, status, sent_count, total_count, date";
    $values = [$name, 0, $subject, $templatePath, serialize($attachments), 'draft', 0, 0, date('Y-m-d H:i:s')];
    $newId = dbInsert("email_campaigns", $columns, $values);
    logAction('mailing_campaign_add', 'تم إنشاء حملة بريدية جديدة: ' . $name);
    sweet("success", "تم", "تم إنشاء الحملة، الآن أضف القوائم المستهدفة واختر المستلمين قبل البدء بالإرسال", "mailing/campaigns/" . $newId);
    exit;
  }
}
?>
<title>إنشاء حملة بريدية جديدة</title>
<div class="card mb-4">
  <h5 class="card-header">إنشاء حملة بريدية جديدة</h5>
  <p class="text-muted px-4 pt-3 mb-0 small">بعد حفظ الحملة، ستتمكن من إضافة قائمة أو أكثر واختيار المستلمين المطلوبين من كل قائمة قبل بدء الإرسال.</p>
  <form class="card-body" method="post" enctype="multipart/form-data" id="campaignForm">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name" value="<?php echo $name ?? '' ?>" placeholder="اسم الحملة (داخلي)" required>
          <label>اسم الحملة <sup class="text-muted">(للتنظيم الداخلي فقط)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="subject" value="<?php echo $subject ?? '' ?>" placeholder="عنوان الرسالة" required>
          <label>عنوان الرسالة (Subject)</label>
        </div>
      </div>
      <div class="col-md-12">
        <hr>
        <h6 class="mb-2">قالب البريد الإلكتروني (HTML)</h6>
        <p class="text-muted small">يمكنك استخدام وسوم مثل <code>%email%</code> أو <code>%field_1%</code> داخل القالب وسيتم استبدالها تلقائياً ببيانات كل جهة اتصال حسب حقول القائمة المستهدفة.</p>
        <div class="row g-3 align-items-center">
          <div class="col-md-6">
            <input type="file" class="form-control" name="template_file" id="templateFileInput" accept=".html,.htm">
          </div>
          <div class="col-md-6">
            <button type="button" class="btn btn-outline-secondary media-picker-btn" data-mode="reference" data-ext="html,htm" data-title="اختر قالب HTML من مكتبة الوسائط" id="templateLibraryBtn">
              <i class="mdi mdi-image-multiple-outline"></i> اختر قالب من المكتبة
            </button>
          </div>
        </div>
        <div id="templateSelectedInfo" class="mt-2 text-success small" style="display:none;"></div>
        <input type="hidden" name="template_media_path" id="templateMediaPath" value="">
      </div>

      <div class="col-md-12">
        <hr>
        <h6 class="mb-2">المرفقات <sup class="text-success">(اختياري)</sup></h6>
        <div class="row g-3 align-items-center">
          <div class="col-md-6">
            <input type="file" class="form-control" name="attachment_files[]" multiple>
          </div>
          <div class="col-md-6">
            <button type="button" class="btn btn-outline-secondary media-picker-btn" data-mode="reference" data-title="اختر مرفقاً من مكتبة الوسائط" id="attachmentLibraryBtn">
              <i class="mdi mdi-image-multiple-outline"></i> إضافة مرفق من المكتبة
            </button>
          </div>
        </div>
        <div id="attachmentsChips" class="mt-2 d-flex flex-wrap gap-2"></div>
        <input type="hidden" name="media_attachments_json" id="mediaAttachmentsJson" value="[]">
      </div>
    </div>
    <div class="pt-4">
      <?php $csrf->input(); ?>
      <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-plus"></i> حفظ الحملة كمسوّدة</button>
    </div>
  </form>
</div>

<script src="js/mailing.js"></script>
<script src="js/mailing-campaign.js"></script>
