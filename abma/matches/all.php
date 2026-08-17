<?php
requireOwner();
matchesRequireModule();

$statusLabels = [
  "upcoming" => "قادمة",
  "live"     => "مباشر الآن",
  "finished" => "انتهت",
];
?>
<title>جدول المباريات</title>
<h4 class="py-3 mb-1"><span class="text-muted fw-light">الرياضة /</span> جدول المباريات</h4>

<?php if (!matchesTableExists()): ?>
<div class="alert alert-warning d-flex align-items-center justify-content-between">
  <span><i class="mdi mdi-alert-outline"></i> جدول المباريات غير مُجهَّز بعد بقاعدة البيانات، شغّل الترحيل أولاً.</span>
  <a href="matches/migrate" class="btn btn-sm btn-warning">تشغيل الترحيل</a>
</div>
<?php else: ?>

<p class="text-muted mb-4">أضف مباريات قادمة أو مباشرة أو منتهية، مع النتيجة والقناة الناقلة إن وُجدت.</p>

<div class="row mb-3">
  <div class="col-sm">
    <a href="matches/new" class="btn btn-secondary waves-effect waves-light"><i class="mdi mdi-plus"></i> إضافة مباراة جديدة</a>
  </div>
</div>

<div class="card mt-2">
  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle orders_table">
      <thead class="table-light">
        <tr>
          <th style="width:70px">#</th>
          <th>المباراة</th>
          <th>المسابقة</th>
          <th>الموعد</th>
          <th>الحالة</th>
          <th>الظهور</th>
          <th style="width:120px">إجراء</th>
        </tr>
      </thead>
      <tbody>
        <?php
        dbSelect("sport_matches", "id, competition, team_home, team_away, match_date, score_home, score_away, match_status, ordering, status", "ORDER BY match_date DESC, ordering ASC, id DESC");
        if ($countrows >= 1) {
          $i = 1;
          foreach ($rows as $row) {
            $statusLabel = $statusLabels[$row['match_status']] ?? $row['match_status'];
            $scoreText = ($row['match_status'] === 'upcoming') ? '' : (' (' . (int)$row['score_home'] . ' - ' . (int)$row['score_away'] . ')');
            echo '
              <tr>
                  <td>' . $i++ . '</td>
                  <td>' . safer($row['team_home']) . ' × ' . safer($row['team_away']) . safer($scoreText) . '</td>
                  <td>' . safer($row['competition']) . '</td>
                  <td>' . safer($row['match_date']) . '</td>
                  <td>' . safer($statusLabel) . '</td>
                  <td>' . $row['status'] . '</td>
                  <td>
                    <div class="dropdown">
                      <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">إجراء</button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                          <a href="matches/' . $row['id'] . '/edit" class="dropdown-item text-warning edit"><i class="mdi mdi-pen"></i> تعديل</a>
                          <span data-id="' . $row['id'] . '" data-name="' . safer($row['team_home']) . ' × ' . safer($row['team_away']) . '" data-action="delete" class="dropdown-item text-danger delete"><i class="mdi mdi-delete"></i> حذف</span>
                        </div>
                  </td>
              </tr>';
          }
        } else {
          echo '<tr><td colspan="7" class="text-center text-muted py-4">لا توجد مباريات بعد</td></tr>';
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>

<script src="js/matches.js"></script>
