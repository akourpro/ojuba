<title>انشاء صفحة جديدة</title>
<?php
if (isset($_POST['submit'])) {
    $csrf->verify();
    $name = safer($_POST['name']);
    $name_en = safer($_POST['name_en']);
    $description = $_POST['description'];
    $description_en = $_POST['description_en'];
    $slug = safer($_POST['slug']);
    $status = safer($_POST['status']);
    if (!empty($slug) and !empty($status) and $status != "") {
        $slug = strtolower($slug);
        dbSelect("pages", "slug", "WHERE slug = ? LIMIT 1", [$slug]);
        if ($countrows == 0) {
            $columns = "name, name_en, description, description_en, slug, status, date";
            $values = [$name, $name_en, $description, $description_en, $slug, $status, date("Y-m-d H:i:s")];
            dbInsert("pages", $columns, $values);
            sweet("success", "نجاح", "تم انشاء الصفحة بنجاح", "pages");
            die();
        } else {
            sweet("error", "خطأ", "اسم الرابط slug موجود مسبقاً");
        }
    }
}

?>
<link rel="stylesheet" href="../css/summernote-lite.min.css">
<script src="../js/summernote-lite.min.js"></script>
<script src="../js/summernote-ar-AR.min.js"></script>
<div class="card mb-4">
    <h5 class="card-header">انشاء صفحة جديدة</h5>
    <form class="card-body" method="post" enctype="multipart/form-data">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" name="name" placeholder="اسم الصفحة بالعربية">
                    <label>اسم الصفحة <sup>(عربي)</sup></label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" name="name_en" placeholder="اسم الصفحة بالانجليزية">
                    <label>اسم الصفحة <sup>(انجليزي)</sup></label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                    <p>محتوى الصفحة <sup>(عربي)</sup>:</p>
                    <textarea class="form-control contents" placeholder="محتوى الصفحة عربي" name="description"></textarea>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                    <p>محتوى الصفحة <sup>(انجليزي)</sup>:</p>
                    <textarea class="form-control contents" placeholder="محتوى الصفحة انجليزي" name="description_en"></textarea>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" name="slug" id="slug" data-id="" placeholder="أدخل اسم رابط الصفحة" onkeyup="this.value = this.value.replace(/[^a-zA-Z0-9-_]/g, '')" required>
                    <label>اسم الرابط slug</label>
                    <div id="message"></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                    <select class="form-select" name="status" id="status">
                        <option value="active">مفعل</option>
                        <option value="disabled">مخفي</option>
                    </select>
                    <label>الحالة</label>
                </div>
            </div>
        </div>
        <div class="pt-4">
            <?php $csrf->input(); ?>
            <button type="submit" name="submit" class="btn btn-primary me-sm-3 me-1 waves-effect waves-light" disabled><i class="mdi mdi-plus"></i> انشاء</button>
            <button type="submit" name="preview" class="btn btn-secondary" formaction="../preview-page" formtarget="_blank"> معاينة </button>
        </div>
    </form>
</div>

<script src="js/editor.js"></script>
<script src="js/pages.js"></script>