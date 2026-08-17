<title>الإحصائيات</title>
<h4 class="py-3 mb-3"><span class="text-muted fw-light">الإحصائيات /</span> عرض الكل</h4>

<div class="row mb-3">
  <div class="col-sm">
    <a href="stats/new" class="btn btn-secondary waves-effect waves-light"><i class="mdi mdi-plus"></i> اضافة إحصائية جديدة</a>
  </div>
</div>

<div class="card mt-2">
  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle orders_table">
      <thead class="table-light">
        <tr>
          <th style="width:70px">#</th>
          <th>الرقم</th>
          <th>التسمية</th>
          <th>الترتيب</th>
          <th>الحالة</th>
          <th style="width:120px">إجراء</th>
        </tr>
      </thead>
      <tbody>
        <?php
        dbSelect("stats", "id, number, suffix, label, ordering, status", "ORDER BY ordering ASC, id DESC");
        if ($countrows >= 1) {
          $i = 1;
          foreach ($rows as $row) {
            echo '
              <tr>
                  <td>' . $i++ . '</td>
                  <td>' . $row['number'] . $row['suffix'] . '</td>
                  <td>' . $row['label'] . '</td>
                  <td>' . $row['ordering'] . '</td>
                  <td>' . $row['status'] . '</td>
                  <td>
                    <div class="dropdown">
                      <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">إجراء</button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                          <a href="stats/' . $row['id'] . '/edit" class="dropdown-item text-warning edit"><i class="mdi mdi-pen"></i> تعديل</a>
                          <span data-id="' . $row['id'] . '" data-name="' . $row['label'] . '" data-action="delete" class="dropdown-item text-danger delete"><i class="mdi mdi-delete"></i> حذف</span>
                        </div>
                  </td>
              </tr>';
          }
        }
        ?>
      </tbody>
    </table>
  </div>
  <div class="card-footer d-flex justify-content-between align-items-center">
    <div id="pagerInfo" class="small text-muted"></div>
    <nav>
      <ul id="pager" class="pagination pagination-sm mb-0"></ul>
    </nav>
  </div>
</div>

<script src="js/stats.js"></script>
<script src="js/tables.js"></script>
