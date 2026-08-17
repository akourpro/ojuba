<title>فئات معرض الأعمال</title>
<h4 class="py-3 mb-4">فئات معرض الأعمال</h4>
<style>
    .swal2-container {
        z-index: 1000000;
    }
</style>

<div class="row">
    <div class="col-sm">
        <button class="btn btn-primary btn-toggle-sidebar waves-effect waves-light" data-bs-toggle="offcanvas" data-bs-target="#addEventSidebar" aria-controls="addEventSidebar" id="addNewBtn">
            <i class="ri-add-line ri-16px me-1_5"></i>
            <span class="align-middle">إضافة فئة جديدة</span>
        </button>
    </div>
</div>

<div class="card mt-2">
    <div class="table-responsive">
        <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table table-bordered table-striped">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>اسم الفئة عربي</th>
                    <th>اسم الفئة انجليزي</th>
                    <th>اجراء</th>
                </tr>
            </thead>
            <tbody>
                <?php
                dbSelect("categories", "*", "ORDER BY id DESC");
                if ($countrows >= 1) {
                    foreach ($rows as $row) {

                        echo '
                    <tr>
                        <td>' . $row['id'] . '</td>
                        <td>' . $row['name'] . '</td>
                        <td>' . $row['name_en'] . '</td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">اجراء</button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                    <span data-id="' . $row['id'] . '" class="dropdown-item text-warning edit-btn"><i class="mdi mdi-pen"></i> تعديل</span>
                                    <span data-id="' . $row['id'] . '" data-category_name="' . $row['name'] . '" class="dropdown-item text-danger delete"><i class="mdi mdi-delete"></i> حذف</span>
                                    
                                </div>
                            </div>
                        </td>
                    </tr>
                    ';
                    }
                } else {
                    echo '
                    <tr>
                        <td colspan="7" class="text-center">لا توجد بيانات</td>
                    </tr>
                    ';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Sidebar for Add/Edit -->
<div class="col app-calendar-content">
    <div class="app-overlay"></div>
    <div class="offcanvas offcanvas-end event-sidebar" tabindex="-1" id="addEventSidebar">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title" id="addEventSidebarLabel">إضافة فئة جديدة</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form class="event-form pt-0" method="POST" id="mainForm" enctype="multipart/form-data">
                <input type="hidden" name="id" id="category_id" value="">

                <div class="form-floating form-floating-outline mb-5">
                    <input type="text" class="form-control" name="name" id="name" placeholder="اسم الفئة بالعربي" />
                    <label>اسم الفئة بالعربي <sup class="text-danger">(اجباري)</sup></label>
                </div>
                <div class="form-floating form-floating-outline mb-5">
                    <input type="text" class="form-control" name="name_en" id="name_en" placeholder="اسم الفئة بالانجليزي" />
                    <label>اسم الفئة بالانجليزي <sup class="text-danger">(اجباري)</sup></label>
                </div>

                <?php $csrf->input(); ?>
                <div class="mb-5 d-flex justify-content-sm-between justify-content-start my-6 gap-2">
                    <div class="d-flex">
                        <button type="submit" name="submit" id="saveEventBtn" data-action="add" class="btn btn-primary btn-add-event me-4">
                            حفظ
                        </button>
                        <span type="reset" class="btn btn-outline-secondary btn-cancel me-sm-0 me-1" data-bs-dismiss="offcanvas">
                            الغاء
                        </span>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/categories.js"></script>