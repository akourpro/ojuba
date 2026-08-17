<?php
requireOwner();
videosRequireModule();

if (!videosTableExists()) {
  sweet("error", "الوحدة غير جاهزة", "جدول الفيديوهات غير مُجهَّز بعد، شغّل الترحيل أولاً من صفحة الفيديوهات.", "videos");
  exit;
}

$id = numer($_GET['id'] ?? 0);
dbSelect("sport_videos", "*", "WHERE id = ? LIMIT 1", [$id]);
if ($countrows === 0) {
  sweet("error", "خطأ", "السجل غير موجود", "videos");
  exit;
}
$row = $rows[0];

if (isset($_POST['submit'])) {
  $csrf->verify();
  $title = safer($_POST['title'] ?? null);
  $youtube_url = safer($_POST['youtube_url'] ?? null);
  $ordering = numer($_POST['ordering'] ?? 0);
  $status = safer($_POST['status'] ?? 'active');

  $thumbnail = $row['thumbnail'] ?? null;
  if (!empty($_FILES['thumbnail']['name'])) {
    if (!empty($row['thumbnail']) and file_exists('../../files/videos/' . $row['thumbnail'])) {
      unlink('../../files/videos/' . $row['thumbnail']);
    }
    up(genCode('sport_videos', 'thumbnail', 'id', 12), 'thumbnail', '../../files/videos', 10);
    $thumbnail = $filename;
  }

  $columns = "title = ?, youtube_url = ?, thumbnail = ?, ordering = ?, status = ?";
  $values = [$title, $youtube_url, $thumbnail, $ordering, $status, $id];
  dbUpdate("sport_videos", $columns, $values, "WHERE id = ? LIMIT 1");
  sweet("success", "تم", "تم التحديث بنجاح", "videos");
  exit;
}
?>
<title>تعديل الفيديو <?php echo safer($row['title']) ?></title>
<div class="card mb-4">
  <h5 class="card-header">تعديل</h5>
  <form class="card-body" method="post" enctype="multipart/form-data">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="title" value="<?php echo safer($row['title']) ?>" required>
          <label>عنوان الفيديو</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="youtube_url" value="<?php echo safer($row['youtube_url']) ?>" dir="ltr" required>
          <label>رابط فيديو يوتيوب</label>
        </div>
      </div>
      <div class="col-md-6">
        <?php if (!empty($row['thumbnail'])): ?>
        <div class="mb-2"><img src="../files/videos/<?php echo safer($row['thumbnail']) ?>" class="img-thumbnail rounded" style="width:140px;height:78px;object-fit:cover;"></div>
        <?php endif; ?>
        <div class="form-floating form-floating-outline">
          <input type="file" class="form-control" id="thumbEdit" name="thumbnail" accept="image/*">
          <label>تغيير الصورة المصغّرة</label>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-2 media-picker-btn" data-target="#thumbEdit"><i class="mdi mdi-image-multiple-outline"></i> اختر من المكتبة</button>
      </div>
      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="ordering" value="<?php echo safer($row['ordering']) ?>">
          <label>ترتيب العرض</label>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-floating form-floating-outline">
          <select class="form-select" name="status"><?php $__status = $row['status'] ?? ''; ?>
            <option value="active" <?php if ($__status == "active") echo "selected" ?>>ظاهر</option>
            <option value="disabled" <?php if ($__status == "disabled") echo "selected" ?>>مخفي</option>
          </select><label>الحالة</label>
        </div>
      </div>
    </div>
    <div class="pt-4">
      <?php $csrf->input(); ?>
      <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-pen"></i> تحديث</button>
    </div>
  </form>
</div>

<script src="js/videos.js"></script>
