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
<title>إضافة إعلان جديد</title>
<?php
requireOwner();
adsRequireModule();

if (!adsTableExists()) {
  sweet("error", "الوحدة غير جاهزة", "جدول الإعلانات غير مُجهَّز بعد، شغّل الترحيل أولاً من صفحة الإعلانات.", "ads");
  exit;
}

$status = '';
if (isset($_POST['submit'])) {
  $csrf->verify();
  $name = safer($_POST['name'] ?? null);
  $type = safer($_POST['type'] ?? 'text');
  $allowedTypes = ['text_link', 'text', 'image_link', 'image', 'code'];
  if (!in_array($type, $allowedTypes, true)) {
    $type = 'text';
  }
  $text = safer($_POST['text'] ?? null);
  $link = safer($_POST['link'] ?? null);
  // كود الإعلان (adsense وغيره) يُحفظ خاماً بدون تمريره على safer()/HTMLPurifier،
  // لأن الأخيرة تحذف وسوم <script> وسمات الإعلانات — نفس اصطلاح حقل bio بوحدة
  // فريق العمل الذي يمر بمحرر نصوص غني ويُخزَّن خاماً ثم يُعرض بالقالب عبر |raw.
  // موثوق فقط لأن هذه الصفحة owner-only (requireOwner أعلاه).
  $code = ($type === 'code') ? ($_POST['code'] ?? null) : null;
  $position = safer($_POST['position'] ?? 'home_top');
  $allowedPositions = ['home_top', 'home_between', 'blog_sidebar', 'article_inline', 'footer'];
  if (!in_array($position, $allowedPositions, true)) {
    $position = 'home_top';
  }
  $ordering = numer($_POST['ordering'] ?? 0);
  $status = safer($_POST['status'] ?? 'active');

  $image = null;
  if (in_array($type, ['image', 'image_link'], true) and !empty($_FILES['image']['name'])) {
    up(genCode('ads', 'image', 'id', 12), 'image', '../../files/ads', 20);
    $image = $filename;
  }

  $columns = "name, type, text, link, image, code, position, ordering, status, date";
  $values = [$name, $type, $text, $link, $image, $code, $position, $ordering, $status, date('Y-m-d H:i:s')];
  dbInsert("ads", $columns, $values);
  sweet("success", "تم", "تمت الإضافة بنجاح", "ads");
  exit;
}
?>
<div class="card mb-4">
  <h5 class="card-header">إضافة إعلان جديد</h5>
  <form class="card-body" method="post" enctype="multipart/form-data" id="adForm">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="name" value="<?php echo $name ?? '' ?>" placeholder="اسم الإعلان (للتعريف بلوحة التحكم فقط)" required>
          <label>اسم الإعلان <sup class="text-danger">(للتعريف الداخلي فقط، لا يظهر بالموقع)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <select class="form-select" name="position">
            <option value="home_top" <?php if (($position ?? '') == "home_top") echo "selected" ?>>أعلى الصفحة الرئيسية</option>
            <option value="home_between" <?php if (($position ?? '') == "home_between") echo "selected" ?>>بين أقسام الرئيسية</option>
            <option value="blog_sidebar" <?php if (($position ?? '') == "blog_sidebar") echo "selected" ?>>الشريط الجانبي (صفحات المقالات)</option>
            <option value="article_inline" <?php if (($position ?? '') == "article_inline") echo "selected" ?>>داخل نص المقال</option>
            <option value="footer" <?php if (($position ?? '') == "footer") echo "selected" ?>>التذييل</option>
          </select>
          <label>موضع الإعلان</label>
        </div>
      </div>

      <div class="col-md-12">
        <div class="form-floating form-floating-outline">
          <select class="form-select" name="type" id="adType">
            <option value="text_link" <?php if (($type ?? '') == "text_link") echo "selected" ?>>نص مع رابط</option>
            <option value="text" <?php if (($type ?? '') == "text") echo "selected" ?>>نص فقط</option>
            <option value="image_link" <?php if (($type ?? '') == "image_link") echo "selected" ?>>صورة مع رابط</option>
            <option value="image" <?php if (($type ?? '') == "image") echo "selected" ?>>صورة فقط</option>
            <option value="code" <?php if (($type ?? '') == "code") echo "selected" ?>>كود إعلاني (Google AdSense أو غيره)</option>
          </select>
          <label>نوع الإعلان</label>
        </div>
      </div>

      <div class="col-md-12 ad-field-group" data-group="text">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control" name="text" placeholder="نص الإعلان" style="height:100px"><?php echo $text ?? '' ?></textarea>
          <label>نص الإعلان</label>
        </div>
      </div>

      <div class="col-md-12 ad-field-group" data-group="link">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="link" value="<?php echo $link ?? '' ?>" placeholder="https://example.com">
          <label>رابط الإعلان (عند الضغط عليه)</label>
        </div>
      </div>

      <div class="col-md-12 ad-field-group" data-group="image">
        <div class="form-floating form-floating-outline">
          <input type="file" class="form-control" id="imageInputNew" name="image" accept="image/*">
          <label>صورة الإعلان</label>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-2 media-picker-btn" data-target="#imageInputNew"><i class="mdi mdi-image-multiple-outline"></i> اختر من المكتبة</button>
      </div>

      <div class="col-md-12 ad-field-group" data-group="code">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control" name="code" placeholder="ألصق كود Google AdSense أو أي كود إعلاني آخر هنا" style="height:160px;font-family:monospace;direction:ltr;text-align:left;" dir="ltr"><?php echo $code ?? '' ?></textarea>
          <label>كود الإعلان (HTML/JS خام)</label>
        </div>
        <small class="text-muted">يُعرض هذا الكود خاماً كما هو بالموقع دون أي تعديل — تأكد أنه من مصدر موثوق (مثل Google AdSense).</small>
      </div>

      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="ordering" value="0">
          <label>ترتيب العرض</label>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <select class="form-select" name="status">
            <option value="active" <?php if ($status == "active") echo "selected" ?>>ظاهر</option>
            <option value="disabled" <?php if ($status == "disabled") echo "selected" ?>>مخفي</option>
          </select><label>الحالة</label>
        </div>
      </div>
    </div>
    <div class="pt-4">
      <?php $csrf->input(); ?>
      <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-plus"></i> حفظ</button>
    </div>
  </form>
</div>

<script src="js/ads.js"></script>
