<title>إضافة مصدر جديد</title>
<?php
requireOwner();
feedsRequireModule();

if (!feedsTableExists()) {
  sweet("error", "الوحدة غير جاهزة", "جداول وحدة سحب المقالات غير مُجهَّزة بعد، شغّل الترحيل أولاً من صفحة سحب المقالات.", "feeds");
  exit;
}

$status = '';
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
  // قواعد الاستبدال تُحفظ خاماً (نص حر بصيغة "من => إلى" لكل سطر) بدون المرور
  // على safer() لأن ترميز HTML قد يشوّه علامات "=>" أو الأحرف الخاصة بالقواعد
  // نفسها — هذا الحقل owner-only أصلاً (صفحات feeds محمية بـrequireOwner()).
  $replacements = $_POST['replacements'] ?? null;
  $auto_publish = isset($_POST['auto_publish']) ? 1 : 0;
  $status = safer($_POST['status'] ?? 'active');

  if (!empty($name) && !empty($feed_url) && preg_match('#^https?://#i', $feed_url)) {
    $columns = "name, feed_url, category_id, replacements, auto_publish, status, date";
    $values = [$name, $feed_url, $category_id, $replacements, $auto_publish, $status, date("Y-m-d H:i:s")];
    dbInsert("feed_sources", $columns, $values);
    sweet("success", "نجاح", "تمت إضافة المصدر بنجاح", "feeds");
    exit;
  } else {
    sweet("error", "خطأ", "الاسم ورابط الـfeed (يبدأ بـ http:// أو https://) حقول إجبارية");
  }
}
?>
<div class="card mb-4">
  <h5 class="card-header">إضافة مصدر سحب مقالات جديد</h5>
  <form class="card-body" method="post" id="feedForm">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name" value="<?php echo $name ?? '' ?>" placeholder="اسم المصدر (للتعريف بلوحة التحكم)" required>
          <label>اسم المصدر</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="feed_url" value="<?php echo $feed_url ?? '' ?>" placeholder="https://example.com/feed" dir="ltr" required>
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
                echo '<option value="' . $category_row['id'] . '">' . $category_row['name'] . '</option>';
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
            <option value="active" <?php if (($status ?? '') == "active") echo "selected" ?>>مفعّل</option>
            <option value="disabled" <?php if (($status ?? '') == "disabled") echo "selected" ?>>متوقف</option>
          </select>
          <label>الحالة</label>
        </div>
      </div>

      <div class="col-md-12">
        <div class="form-check form-switch mt-2">
          <input class="form-check-input" type="checkbox" role="switch" name="auto_publish" id="autoPublishSwitch">
          <label class="form-check-label" for="autoPublishSwitch">نشر المقالات المسحوبة تلقائياً (بدون مراجعة)</label>
        </div>
        <small class="form-text text-muted">إن كان متوقفاً (الافتراضي، الأكثر أماناً): تُحفظ المقالات المسحوبة "مخفية" وتحتاج مراجعتك ثم تفعيلها يدوياً من صفحة المقالات قبل ظهورها بالموقع.</small>
      </div>

      <div class="col-md-12">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control" name="replacements" placeholder="الأصلي => البديل" style="height:140px;font-family:monospace;direction:ltr;text-align:left;" dir="ltr"><?php echo $replacements ?? '' ?></textarea>
          <label>قواعد الاستبدال التلقائي (اختياري)</label>
        </div>
        <small class="form-text text-muted">سطر واحد لكل قاعدة، بالصيغة: <code>الكلمة الأصلية => الكلمة البديلة</code> — مثال: <code>موقع المصدر => موقعنا</code>. تُطبَّق تلقائياً على عنوان ومحتوى كل مقال يُسحب من هذا المصدر.</small>
      </div>
    </div>
    <div class="pt-4">
      <?php $csrf->input(); ?>
      <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-plus"></i> حفظ</button>
    </div>
  </form>
</div>

<script>
  $('.select2').select2();
</script>
<script src="js/feeds.js"></script>
