<?php
requireOwner();
standingsRequireModule();

if (!standingsTableExists()) {
  sweet("error", "الوحدة غير جاهزة", "جدول الترتيب غير مُجهَّز بعد، شغّل الترحيل أولاً من صفحة جدول الترتيب.", "standings");
  exit;
}

$id = numer($_GET['id'] ?? 0);
dbSelect("sport_standings", "*", "WHERE id = ? LIMIT 1", [$id]);
if ($countrows === 0) {
  sweet("error", "خطأ", "السجل غير موجود", "standings");
  exit;
}
$row = $rows[0];

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

  $team_logo = $row['team_logo'] ?? null;
  if (!empty($_FILES['team_logo']['name'])) {
    if (!empty($row['team_logo']) and file_exists('../../files/standings/' . $row['team_logo'])) {
      unlink('../../files/standings/' . $row['team_logo']);
    }
    up(genCode('sport_standings', 'team_logo', 'id', 12), 'team_logo', '../../files/standings', 10);
    $team_logo = $filename;
  }

  $columns = "team_name = ?, team_logo = ?, played = ?, won = ?, drawn = ?, lost = ?, goals_for = ?, goals_against = ?, points = ?, ordering = ?, status = ?";
  $values = [$team_name, $team_logo, $played, $won, $drawn, $lost, $goals_for, $goals_against, $points, $ordering, $status, $id];
  dbUpdate("sport_standings", $columns, $values, "WHERE id = ? LIMIT 1");
  sweet("success", "تم", "تم التحديث بنجاح", "standings");
  exit;
}
?>
<title>تعديل فريق <?php echo safer($row['team_name']) ?></title>
<div class="card mb-4">
  <h5 class="card-header">تعديل</h5>
  <form class="card-body" method="post" enctype="multipart/form-data">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="team_name" value="<?php echo safer($row['team_name']) ?>" required>
          <label>اسم الفريق</label>
        </div>
      </div>
      <div class="col-md-6">
        <?php if (!empty($row['team_logo'])): ?>
        <div class="mb-2"><img src="../files/standings/<?php echo safer($row['team_logo']) ?>" class="img-thumbnail rounded" style="width:56px;height:56px;object-fit:contain;"></div>
        <?php endif; ?>
        <div class="form-floating form-floating-outline">
          <input type="file" class="form-control" id="teamLogoEdit" name="team_logo" accept="image/*">
          <label>تغيير شعار الفريق</label>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-2 media-picker-btn" data-target="#teamLogoEdit"><i class="mdi mdi-image-multiple-outline"></i> اختر من المكتبة</button>
      </div>

      <div class="col-md-2">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="played" value="<?php echo safer($row['played']) ?>">
          <label>لعب</label>
        </div>
      </div>
      <div class="col-md-2">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="won" value="<?php echo safer($row['won']) ?>">
          <label>فاز</label>
        </div>
      </div>
      <div class="col-md-2">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="drawn" value="<?php echo safer($row['drawn']) ?>">
          <label>تعادل</label>
        </div>
      </div>
      <div class="col-md-2">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="lost" value="<?php echo safer($row['lost']) ?>">
          <label>خسر</label>
        </div>
      </div>
      <div class="col-md-2">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="goals_for" value="<?php echo safer($row['goals_for']) ?>">
          <label>أهداف له</label>
        </div>
      </div>
      <div class="col-md-2">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="goals_against" value="<?php echo safer($row['goals_against']) ?>">
          <label>أهداف عليه</label>
        </div>
      </div>

      <div class="col-md-4">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="points" value="<?php echo safer($row['points']) ?>">
          <label>النقاط</label>
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="ordering" value="<?php echo safer($row['ordering']) ?>">
          <label>ترتيب العرض</label>
        </div>
      </div>
      <div class="col-md-4">
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

<script src="js/standings.js"></script>
