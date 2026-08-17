<title>معرض الأعمال</title>
<h4 class="py-3 mb-4"><span class="text-muted fw-light">معرض الأعمال /</span> عرض الكل</h4>
<style>
  .swal2-container {
    z-index: 1000000;
  }
</style>
<div class="row">
  <div class="col-sm">
    <a href="portfolio/new" class="btn btn-secondary waves-effect waves-light"><i class="mdi mdi-plus"></i> إضافة عمل جديد</a>
  </div>
</div>
<div class="card mt-2">
  <div class="table-responsive">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table table-bordered orders_table">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>عنوان المقالة</th>
          <th>اسم الرابط slug</th>
          <th>الحالة</th>
          <th>اجراء</th>
        </tr>
      </thead>
      <tbody>
        <?php
        dbSelect("portfolio", "id, name, name_en, slug, status", "ORDER BY id DESC");
        if ($countrows >= 1) {
          foreach ($rows as $row) {
            echo '
              <tr>
                  <td>' . $row['id'] . '</td>
                  <td>' . $row['name'] . '<br>' . $row['name_en'] . '</td>
                  <td>' . $row['slug'] . '</td>
                  <td>' . $row['status'] . '</td>
                  <td>
                    <div class="dropdown">
                      <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">اجراء</button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                          <a href="../portfolio/' . $row['slug'] . '" target="_blank" class="dropdown-item text-primary"><i class="mdi mdi-eye"></i> عرض</a>
                          <a href="portfolio/' . $row['id'] . '/edit" class="dropdown-item text-warning edit"><i class="mdi mdi-pen"></i> تعديل</a>
                          <span data-id="' . $row['id'] . '" data-name="' . $row['name'] . '" data-action="delete" class="dropdown-item text-danger delete"><i class="mdi mdi-delete"></i> حذف</span>
                        </div>
                    </div>
                  </td>
              </tr>';
          }
        }
        ?>
      </tbody>
    </table>
  </div>
</div>
<script src="js/portfolio.js"></script>
<script src="js/tables.js"></script>