<title>المقالات</title>
<h4 class="py-3 mb-4"><span class="text-muted fw-light">المقالات /</span> عرض الكل</h4>
<style>
    .swal2-container {
        z-index: 1000000;
    }
</style>
<div class="row">
    <div class="col-sm">
        <a href="blogs/new" class="btn btn-secondary waves-effect waves-light"><i class="mdi mdi-plus"></i> انشاء مقالة جديدة</a>
    </div>
</div>
<div class="card mt-2">
    <div class="table-responsive">
        <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table table-bordered table-striped">
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
                $featuredEnabled = blogsFeaturedColumnExists();
                dbSelect("blogs", "id, name, name_en, slug, status" . ($featuredEnabled ? ", featured" : ""), "ORDER BY id DESC");
                foreach ($rows as $row) {
                    $featuredBadge = ($featuredEnabled && !empty($row['featured'])) ? ' <span class="badge bg-label-danger">مميّز/عاجل</span>' : '';

                    echo '
                    <tr>
                        <td>' . $row['id'] . '</td>
                        <td>' . $row['name'] . '<br>' . $row['name_en'] . $featuredBadge . '</td>
                        <td>' . $row['slug'] . '</td>
                        <td>' . $row['status'] . '</td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">اجراء</button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                    <a href="../blog/' . $row['slug'] . '" target="_blank" class="dropdown-item text-primary"><i class="mdi mdi-eye"></i> عرض</a>
                                    <a href="blogs/' . $row['id'] . '/edit" class="dropdown-item text-warning edit"><i class="mdi mdi-pen"></i> تعديل</a>
                                    <span data-id="' . $row['id'] . '" data-name="' . $row['name'] . '" data-action="delete" class="dropdown-item text-danger delete"><i class="mdi mdi-delete"></i> حذف</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                    ';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
<script src="js/blogs.js"></script>