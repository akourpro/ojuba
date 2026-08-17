<title>إضافة فيديو جديد</title>
<?php
requireOwner();
videosRequireModule();

if (!videosTableExists()) {
  sweet("error", "الوحدة غير جاهزة", "جدول الفيديوهات غير مُجهَّز بعد، شغّل الترحيل أولاً من صفحة الفيديوهات.", "videos");
  exit;
}

$status = '';
if (isset($_POST['submit'])) {
  $csrf->verify();
  $title = safer($_POST['title'] ?? null);
  $youtube_url = safer($_POST['youtube_url'] ?? null);
  $ordering = numer($_POST['ordering'] ?? 0);
  $status = safer($_POST['status'] ?? 'active');

  $thumbnail = null;
  if (!empty($_FILES['thumbnail']['name'])) {
    up(genCode('sport_videos', 'thumbnail', 'id', 12), 'thumbnail', '../../files/videos', 10);
    $thumbnail = $filename;
  }

  $columns = "title, youtube_url, thumbnail, ordering, status, date";
  $values = [$title, $youtube_url, $thumbnail, $ordering, $status, date('Y-m-d H:i:s')];
  dbInsert("sport_videos", $columns, $values);
  sweet("success", "تم", "تمت الإضافة بنجاح", "videos");
  exit;
}
?>
<div class="card mb-4">
  <h5 class="card-header">إضافة فيديو جديد</h5>
  <form class="card-body" method="post" enctype="multipart/form-data">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="title" value="<?php echo $title ?? '' ?>" placeholder="عنوان الفيديو" required>
          <label>عنوان الفيديو</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="youtube_url" value="<?php echo $youtube_url ?? '' ?>" placeholder="https://www.youtube.com/watch?v=..." dir="ltr" required>
          <label>رابط فيديو يوتيوب</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="file" class="form-control" id="thumbNew" name="thumbnail" accept="image/*">
          <label>صورة مصغّرة مخصّصة <sup class="text-success">(اختياري — وإلا تُجلب تلقائياً من يوتيوب)</sup></label>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-2 media-picker-btn" data-target="#thumbNew"><i class="mdi mdi-image-multiple-outline"></i> اختر من المكتبة</button>
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

<script src="js/videos.js"></script>
