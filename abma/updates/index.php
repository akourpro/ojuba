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
require_once 'includes/updater.php';


if (isset($_GET['save_patch_setting'])) {
  $autoPatch = isset($_GET['auto_patch']) ? '1' : '0';
  saveSetting('update_auto_apply_patches', $autoPatch);
  sweet("success", "تم", "تم حفظ إعداد التحديثات التلقائية", "updates");
  exit;
}

$info = updaterAvailableInfo();
$current = $info['current'];
$autoPatchEnabled = ($site['update_auto_apply_patches'] ?? '0') === '1';
?>
<title>الإصدار والتحديثات</title>
<h4 class="py-3 mb-1"><span class="text-muted fw-light">النظام /</span> الإصدار والتحديثات</h4>
<p class="text-muted mb-4">يتحقق السكربت تلقائياً من وجود إصدار أحدث كل بضع ساعات أثناء تصفّحك للوحة التحكم، ويمكنك أيضاً التحقق يدوياً في أي وقت. التحديث يُنشئ نسخة احتياطية كاملة (ملفات + قاعدة بيانات) تلقائياً قبل تطبيق أي تغيير.</p>

<div class="row g-4 mb-2">
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-body text-center py-5">
        <i class="mdi mdi-tag-check-outline" style="font-size:42px;color:#696cff"></i>
        <h6 class="text-muted mt-3 mb-1">الإصدار المثبَّت حالياً</h6>
        <h3 class="mb-0"><?php echo safer($current); ?></h3>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card h-100">
      <div class="card-body py-5">
        <?php if ($info['available']): ?>
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
              <span class="badge bg-label-success mb-2">يتوفر تحديث</span>
              <h5 class="mb-1">إصدار جديد: <?php echo safer($info['latest']); ?><?php if (!empty($info['name'])) echo ' — ' . safer($info['name']); ?></h5>
              <?php if (!empty($info['published_at'])): ?>
                <small class="text-muted">نُشر: <?php echo safer(date('Y-m-d', strtotime($info['published_at']))); ?></small>
              <?php endif; ?>
            </div>
            <div class="d-flex gap-2">
              <?php if (!empty($info['html_url'])): ?>
                <a href="<?php echo safer($info['html_url']); ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary">سجل التغييرات</a>
              <?php endif; ?>
              <button type="button" id="applyUpdateBtn" class="btn btn-success" data-target-version="<?php echo safer($info['latest']); ?>"><i class="mdi mdi-cloud-download-outline"></i> تحديث الآن</button>
            </div>
          </div>
          <?php if (!empty($info['notes'])): ?>
            <hr>
            <div class="small text-muted" style="max-height:180px;overflow:auto;white-space:pre-line;"><?php echo nl2br(safer($info['notes'])); ?></div>
          <?php endif; ?>
        <?php else: ?>
          <div class="d-flex align-items-center gap-3">
            <i class="mdi mdi-check-circle-outline" style="font-size:36px;color:#71dd37"></i>
            <div>
              <h6 class="mb-1">أنت تستخدم أحدث إصدار متوفر</h6>
              <small class="text-muted">آخر فحص: <?php echo !empty($info['checked_at']) ? safer(ago($info['checked_at'])) : 'لم يُفحَص بعد'; ?></small>
            </div>
          </div>
        <?php endif; ?>

        <?php if (!empty($info['last_error'])): ?>
          <div class="alert alert-warning mt-3 mb-0 py-2 px-3 small"><i class="mdi mdi-alert-outline"></i> تعذّر آخر فحص: <?php echo safer($info['last_error']); ?></div>
        <?php endif; ?>

        <div class="mt-3">
          <button type="button" id="checkUpdateBtn" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-refresh"></i> تحقق الآن</button>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($info['last_applied_at'])): ?>
  <div class="alert alert-info d-flex align-items-center gap-2">
    <i class="mdi mdi-information-outline"></i>
    <span>آخر تحديث تم تطبيقه: الإصدار <?php echo safer($info['last_applied_version']); ?> بتاريخ <?php echo safer($info['last_applied_at']); ?> — راجع صفحة "نسخة احتياطية" لتنزيل النسخة المأخوذة قبل هذا التحديث.</span>
  </div>
<?php endif; ?>

<div class="card mt-2">
  <h5 class="card-header"><i class="mdi mdi-shield-sync-outline"></i>التحديثات الأمنية/التصحيحية</h5>
  <div class="card-body">
    <!-- <p class="text-muted small mb-3">GitHub لا يميّز رسمياً "تحديث أمني" عن أي إصدار آخر — الاعتماد العملي هنا: أي إصدار جديد يرفع <b>رقم التصحيح (Patch) فقط</b> بمخطط Semantic Versioning (مثال: <code dir="ltr">1.2.3 → 1.2.4</code>، عادة إصلاحات أمنية/أخطاء منخفضة المخاطر) يمكن تطبيقه تلقائياً دون انتظارك. أي رفع لرقم Minor أو Major (مثال: <code dir="ltr">1.2.x → 1.3.0</code>) يبقى يتطلّب ضغطك على "تحديث الآن" يدوياً دائماً — بغض النظر عن هذا الإعداد.</p> -->
    <p class="text-muted small mb-3">هل تريد تفعيل التحديثات الأمنية التلقائية؟ هذا الخيار مهم من أجل الصيانة الذاتية في حال وجود ثغرات أمنية يتم اكتشافها</p>
    <form method="get" class="d-flex align-items-center gap-3">
      <input type="hidden" name="save_patch_setting" value="1">
      <label class="switch switch-md" style="margin-left: 10%;">
        <input type="checkbox" class="switch-input addon-toggle" role="switch" id="auto_patch" name="auto_patch" <?php if ($autoPatchEnabled) echo 'checked'; ?>>
        <span class="switch-toggle-slider">
          <span class="switch-on"></span>
          <span class="switch-off"></span>
        </span>
        <span class="switch-label">تطبيق تحديثات التصحيح (Patch) تلقائياً</span>
      </label>

      <button type="submit" class="btn btn-sm btn-primary">حفظ</button>
    </form>
  </div>
</div>

<div class="card mt-2">
  <h5 class="card-header"><i class="mdi mdi-help-circle-outline"></i> ماذا يشمل التحديث بالضبط؟</h5>
  <div class="card-body">
    <ul class="mb-0">
      <li>يُنشأ نسخة احتياطية كاملة (كل ملفات السكربت + تفريغ قاعدة البيانات) تلقائياً قبل أي تعديل — تُحفظ بصفحة "نسخة احتياطية".</li>
      <li>يُفعَّل "وضع الصيانة" تلقائياً للموقع العام (وليس لوحة التحكم) طوال مدة نسخ الملفات فقط، ويُلغى تلقائياً فور الانتهاء — يمنع مواجهة الزوار لأخطاء ملفات نصف مُحدَّثة.</li>
      <li>تُستبدَل ملفات كود السكربت (الجذر، <code>includes/</code>، <code>abma/</code>، القوالب الرسمية...) بأحدث نسخة من المستودع الرسمي على GitHub.</li>
      <li><b>لا يُلمَس إطلاقاً</b>: ملف بيانات الاتصال بقاعدة البيانات، مجلد <code>files/</code> (كل ما رفعتَه من صور وملفات)، وأي قالب استوردتَه يدوياً بصيغة zip.</li>
      <li>روابط المسارات المخصَّصة التي غيّرتَها من الإعدادات تُعاد تطبيقها تلقائياً فوق أي تحديث لملف <code>.htaccess</code>.</li>
      <li>تُعاد تهيئة قاعدة البيانات تلقائياً لأي جدول/عمود جديد أضافته النسخة الجديدة — لا حاجة لزيارة صفحات "ترحيل" يدوياً.</li>
      <li><b>تنبيه:</b> أي تعديل يدوي مباشر أجريتَه على ملفات قالب رسمي (وليس عبر لوحة التحكم) سيُفقَد لأن القالب جزء من ملفات السكربت المُحدَّثة — استخدم "استيراد/تصدير القوالب" لحفظ نسخة قبل أي تعديل يدوي بملفات قالب رسمي.</li>
    </ul>
  </div>
</div>

<script src="js/updates.js"></script>