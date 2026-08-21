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
<title>إضافة فريق جديد لجدول الترتيب</title>
<?php
requireOwner();
standingsRequireModule();

if (!standingsTableExists()) {
  sweet("error", "الوحدة غير جاهزة", "جدول الترتيب غير مُجهَّز بعد، شغّل الترحيل أولاً من صفحة جدول الترتيب.", "standings");
  exit;
}

$status = '';
if (isset($_POST['submit'])) {
  $csrf->verify();
  $team_name = safer($_POST['team_name'] ?? null);
  $played = numer($_POST['played'] ?? 0);
  $won = numer($_POST['won'] ?? 0);
  $drawn = numer($_POST['drawn'] ?? 0);
  $lost = numer($_POST['lost'] ?? 0);
  $goals_for = numer($_POST['goals_for'] ?? 0);
  $goals_against = numer($_POST['goals_against'] ?? 0);
  $points = numer($_POST['points'] ?? 0);
  $ordering = numer($_POST['ordering'] ?? 0);
  $status = safer($_POST['status'] ?? 'active');

  $team_logo = null;
  if (!empty($_FILES['team_logo']['name'])) {
    up(genCode('sport_standings', 'team_logo', 'id', 12), 'team_logo', '../../files/standings', 10);
    $team_logo = $filename;
  }

  $columns = "team_name, team_logo, played, won, drawn, lost, goals_for, goals_against, points, ordering, status, date";
  $values = [$team_name, $team_logo, $played, $won, $drawn, $lost, $goals_for, $goals_against, $points, $ordering, $status, date('Y-m-d H:i:s')];
  dbInsert("sport_standings", $columns, $values);
  sweet("success", "تم", "تمت الإضافة بنجاح", "standings");
  exit;
}
?>
<div class="card mb-4">
  <h5 class="card-header">إضافة فريق جديد</h5>
  <form class="card-body" method="post" enctype="multipart/form-data">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="team_name" value="<?php echo $team_name ?? '' ?>" placeholder="اسم الفريق" required>
          <label>اسم الفريق</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="file" class="form-control" id="teamLogoNew" name="team_logo" accept="image/*">
          <label>شعار الفريق <sup class="text-success">(اختياري)</sup></label>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-2 media-picker-btn" data-target="#teamLogoNew"><i class="mdi mdi-image-multiple-outline"></i> اختر من المكتبة</button>
      </div>

      <div class="col-md-2">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="played" value="0">
          <label>لعب</label>
        </div>
      </div>
      <div class="col-md-2">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="won" value="0">
          <label>فاز</label>
        </div>
      </div>
      <div class="col-md-2">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="drawn" value="0">
          <label>تعادل</label>
        </div>
      </div>
      <div class="col-md-2">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="lost" value="0">
          <label>خسر</label>
        </div>
      </div>
      <div class="col-md-2">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="goals_for" value="0">
          <label>أهداف له</label>
        </div>
      </div>
      <div class="col-md-2">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="goals_against" value="0">
          <label>أهداف عليه</label>
        </div>
      </div>

      <div class="col-md-4">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="points" value="0">
          <label>النقاط</label>
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="ordering" value="0">
          <label>ترتيب العرض</label>
        </div>
      </div>
      <div class="col-md-4">
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

<script src="js/standings.js"></script>
