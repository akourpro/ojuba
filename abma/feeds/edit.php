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
feedsRequireModule();

if (!feedsTableExists()) {
  sweet("error", "الوحدة غير جاهزة", "جداول وحدة سحب المقالات غير مُجهَّزة بعد، شغّل الترحيل أولاً من صفحة سحب المقالات.", "feeds");
  exit;
}

$id = isset($_GET['id']) ? (int) numer($_GET['id']) : 0;
dbSelect("feed_sources", "*", "WHERE id = ? LIMIT 1", [$id]);
if ($countrows != 1) {
  sweet("error", "غير موجود", "المصدر غير موجود", "feeds");
  exit;
}
$source = $rows[0];

if (isset($_POST['submit'])) {
  $csrf->verify();
  $name = safer($_POST['name'] ?? null);
  $feed_url = safer($_POST['feed_url'] ?? null);
  $category_id = null;
  if (!empty($_POST['category_id'])) {
    dbSelect("blog_categories", "id", "WHERE id = ? LIMIT 1", [$_POST['category_id']]);
    if ($countrows == 1) {
      $category_id = (int) $_POST['category_id'];
    }
  }
  $replacements = $_POST['replacements'] ?? null;
  $auto_publish = isset($_POST['auto_publish']) ? 1 : 0;
  $status = safer($_POST['status'] ?? 'active');

  if (!empty($name) && !empty($feed_url) && preg_match('#^https?://#i', $feed_url)) {
    dbUpdate(
      "feed_sources",
      "name = ?, feed_url = ?, category_id = ?, replacements = ?, auto_publish = ?, status = ?",
      [$name, $feed_url, $category_id, $replacements, $auto_publish, $status, $id],
      "WHERE id = ?"
    );
    sweet("success", "نجاح", "تم تحديث المصدر بنجاح", "feeds");
    exit;
  } else {
    sweet("error", "خطأ", "الاسم ورابط الـfeed (يبدأ بـ http:// أو https://) حقول إجبارية");
  }
}

// قيم النموذج المعروضة: بعد submit فاشل تبقى القيم المُدخَلة، وإلا القيم المحفوظة بقاعدة البيانات
$formName = $name ?? $source['name'];
$formFeedUrl = $feed_url ?? $source['feed_url'];
$formCategoryId = array_key_exists('category_id', $_POST ?? []) ? $category_id : $source['category_id'];
$formReplacements = $replacements ?? $source['replacements'];
$formStatus = $status ?? $source['status'];
$formAutoPublish = isset($_POST['submit']) ? $auto_publish : $source['auto_publish'];
?>
<title>تعديل مصدر</title>
<div class="card mb-4">
  <h5 class="card-header">تعديل مصدر سحب مقالات</h5>
  <form class="card-body" method="post" id="feedForm">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name" value="<?php echo $formName ?>" placeholder="اسم المصدر" required>
          <label>اسم المصدر</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="feed_url" value="<?php echo $formFeedUrl ?>" placeholder="https://example.com/feed" dir="ltr" required>
          <label>رابط الـRSS/Feed</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <select class="form-select select2" name="category_id">
            <option value="">بدون تصنيف</option>
            <?php
            dbSelect("blog_categories", "*", "ORDER BY id DESC");
            if ($countrows >= 1) {
              foreach ($rows as $category_row) {
                $selected = ((int) $formCategoryId === (int) $category_row['id']) ? 'selected' : '';
                echo '<option value="' . $category_row['id'] . '" ' . $selected . '>' . $category_row['name'] . '</option>';
              }
            }
            ?>
          </select>
          <label>التصنيف الذي تُضاف إليه المقالات المسحوبة</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <select class="form-select" name="status">
            <option value="active" <?php if ($formStatus == "active") echo "selected" ?>>مفعّل</option>
            <option value="disabled" <?php if ($formStatus == "disabled") echo "selected" ?>>متوقف</option>
          </select>
          <label>الحالة</label>
        </div>
      </div>

      <div class="col-md-12">
        <div class="form-check form-switch mt-2">
          <input class="form-check-input" type="checkbox" role="switch" name="auto_publish" id="autoPublishSwitch" <?php if (!empty($formAutoPublish)) echo 'checked' ?>>
          <label class="form-check-label" for="autoPublishSwitch">نشر المقالات المسحوبة تلقائياً (بدون مراجعة)</label>
        </div>
        <small class="form-text text-muted">إن كان متوقفاً (الأكثر أماناً): تُحفظ المقالات المسحوبة "مخفية" وتحتاج مراجعتك ثم تفعيلها يدوياً من صفحة المقالات قبل ظهورها بالموقع.</small>
      </div>

      <div class="col-md-12">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control" name="replacements" placeholder="الأصلي => البديل" style="height:140px;font-family:monospace;direction:ltr;text-align:left;" dir="ltr"><?php echo $formReplacements ?></textarea>
          <label>قواعد الاستبدال التلقائي (اختياري)</label>
        </div>
        <small class="form-text text-muted">سطر واحد لكل قاعدة، بالصيغة: <code>الكلمة الأصلية => الكلمة البديلة</code>. تُطبَّق تلقائياً على عنوان ومحتوى كل مقال يُسحب من هذا المصدر لاحقاً (لا تُعاد تلقائياً على مقالات سُحبت مسبقاً).</small>
      </div>
    </div>
    <div class="pt-4">
      <?php $csrf->input(); ?>
      <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-content-save"></i> حفظ التعديلات</button>
    </div>
  </form>
</div>

<script>
  $('.select2').select2();
</script>
<script src="js/feeds.js"></script>
