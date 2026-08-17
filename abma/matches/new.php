<title>إضافة مباراة جديدة</title>
<?php
requireOwner();
matchesRequireModule();

if (!matchesTableExists()) {
  sweet("error", "الوحدة غير جاهزة", "جدول المباريات غير مُجهَّز بعد، شغّل الترحيل أولاً من صفحة جدول المباريات.", "matches");
  exit;
}

$match_status = 'upcoming';
$status = '';
if (isset($_POST['submit'])) {
  $csrf->verify();
  $competition = safer($_POST['competition'] ?? null);
  $team_home = safer($_POST['team_home'] ?? null);
  $team_away = safer($_POST['team_away'] ?? null);
  $match_date_raw = $_POST['match_date'] ?? '';
  $match_date = !empty($match_date_raw) ? date('Y-m-d H:i:s', strtotime($match_date_raw)) : date('Y-m-d H:i:s');
  $venue = safer($_POST['venue'] ?? null);
  $match_status = safer($_POST['match_status'] ?? 'upcoming');
  $allowedStatuses = ['upcoming', 'live', 'finished'];
  if (!in_array($match_status, $allowedStatuses, true)) {
    $match_status = 'upcoming';
  }
  $score_home = ($match_status !== 'upcoming') ? numer($_POST['score_home'] ?? 0) : null;
  $score_away = ($match_status !== 'upcoming') ? numer($_POST['score_away'] ?? 0) : null;
  $broadcast_channel = safer($_POST['broadcast_channel'] ?? null);
  $ordering = numer($_POST['ordering'] ?? 0);
  $status = safer($_POST['status'] ?? 'active');

  $team_home_logo = null;
  if (!empty($_FILES['team_home_logo']['name'])) {
    up(genCode('sport_matches', 'team_home_logo', 'id', 12), 'team_home_logo', '../../files/matches', 10);
    $team_home_logo = $filename;
  }
  $team_away_logo = null;
  if (!empty($_FILES['team_away_logo']['name'])) {
    up(genCode('sport_matches', 'team_away_logo', 'id', 12), 'team_away_logo', '../../files/matches', 10);
    $team_away_logo = $filename;
  }

  $columns = "competition, team_home, team_home_logo, team_away, team_away_logo, match_date, venue, score_home, score_away, match_status, broadcast_channel, ordering, status, date";
  $values = [$competition, $team_home, $team_home_logo, $team_away, $team_away_logo, $match_date, $venue, $score_home, $score_away, $match_status, $broadcast_channel, $ordering, $status, date('Y-m-d H:i:s')];
  dbInsert("sport_matches", $columns, $values);
  sweet("success", "تم", "تمت الإضافة بنجاح", "matches");
  exit;
}
?>
<div class="card mb-4">
  <h5 class="card-header">إضافة مباراة جديدة</h5>
  <form class="card-body" method="post" enctype="multipart/form-data" id="matchForm">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="competition" value="<?php echo $competition ?? '' ?>" placeholder="اسم البطولة/المسابقة">
          <label>البطولة / المسابقة <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="datetime-local" class="form-control" name="match_date" required>
          <label>تاريخ ووقت المباراة</label>
        </div>
      </div>

      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="team_home" value="<?php echo $team_home ?? '' ?>" placeholder="اسم الفريق المضيف" required>
          <label>الفريق المضيف</label>
        </div>
        <div class="form-floating form-floating-outline mt-2">
          <input type="file" class="form-control" id="teamHomeLogoNew" name="team_home_logo" accept="image/*">
          <label>شعار الفريق المضيف <sup class="text-success">(اختياري)</sup></label>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-2 media-picker-btn" data-target="#teamHomeLogoNew"><i class="mdi mdi-image-multiple-outline"></i> اختر من المكتبة</button>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="team_away" value="<?php echo $team_away ?? '' ?>" placeholder="اسم الفريق الضيف" required>
          <label>الفريق الضيف</label>
        </div>
        <div class="form-floating form-floating-outline mt-2">
          <input type="file" class="form-control" id="teamAwayLogoNew" name="team_away_logo" accept="image/*">
          <label>شعار الفريق الضيف <sup class="text-success">(اختياري)</sup></label>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-2 media-picker-btn" data-target="#teamAwayLogoNew"><i class="mdi mdi-image-multiple-outline"></i> اختر من المكتبة</button>
      </div>

      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="venue" value="<?php echo $venue ?? '' ?>" placeholder="الملعب/المكان">
          <label>الملعب <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="broadcast_channel" value="<?php echo $broadcast_channel ?? '' ?>" placeholder="مثال: قناة SSC 1">
          <label>القناة الناقلة <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>

      <div class="col-md-4">
        <div class="form-floating form-floating-outline">
          <select class="form-select" name="match_status" id="matchStatus">
            <option value="upcoming" <?php if ($match_status == "upcoming") echo "selected" ?>>قادمة</option>
            <option value="live" <?php if ($match_status == "live") echo "selected" ?>>مباشر الآن</option>
            <option value="finished" <?php if ($match_status == "finished") echo "selected" ?>>انتهت</option>
          </select>
          <label>حالة المباراة</label>
        </div>
      </div>
      <div class="col-md-4 match-score-group">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="score_home" value="0">
          <label>نتيجة الفريق المضيف</label>
        </div>
      </div>
      <div class="col-md-4 match-score-group">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="score_away" value="0">
          <label>نتيجة الفريق الضيف</label>
        </div>
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

<script src="js/matches.js"></script>
