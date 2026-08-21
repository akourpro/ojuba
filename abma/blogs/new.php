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
<title>انشاء مقالة جديدة</title>
<?php
$status = '';
if (isset($_POST['submit'])) {
    $csrf->verify();
    $name = safer($_POST['name']);
    $name_en = safer($_POST['name_en']);
    $description = $_POST['description'];
    $description_en = $_POST['description_en'];
    $slug = safer($_POST['slug']);
    $tags = safer($_POST['tags']);
    $tags_en = safer($_POST['tags_en']);
    $status = safer($_POST['status']);
    if (!empty($_POST['category']) and $_POST['category'] != "") {
        dbSelect("blog_categories", "id", "WHERE id = ? LIMIT 1", [$_POST['category']]);
        if ($countrows == 1) {
            $category = safer($_POST['category']);
        } else {
            $category = null;
        }
    } else {
        $category = null;
    }
    if (!empty($slug) and !empty($status) and $status != "") {
        $slug = strtolower($slug);
        dbSelect("blogs", "slug", "WHERE slug = ? LIMIT 1", [$slug]);
        if ($countrows == 0) {
            $columns = "name, name_en, description, description_en, slug, tags, tags_en, status, category, date";
            $values = [$name, $name_en, $description, $description_en, $slug, $tags, $tags_en, $status, $category, date("Y-m-d H:i:s")];
            if (blogsFeaturedColumnExists()) {
                $featured = isset($_POST['featured']) ? 1 : 0;
                $columns .= ", featured";
                $values[] = $featured;
            }
            if (blogsRelatedMatchColumnExists()) {
                $related_match_id = null;
                if (!empty($_POST['related_match_id'])) {
                    dbSelect("sport_matches", "id", "WHERE id = ? LIMIT 1", [$_POST['related_match_id']]);
                    if ($countrows == 1) {
                        $related_match_id = (int) $_POST['related_match_id'];
                    }
                }
                $columns .= ", related_match_id";
                $values[] = $related_match_id;
            }
            $id = dbInsert("blogs", $columns, $values);

            if (!empty($_FILES['image']['name'])) { // التحقق من وجود صورة جديدة
                $image =  "post-" . $id;
                $up = up($image, "image", "../../files/blogs", 20);
                if ($up) {
                    dbUpdate("blogs", "image = ?", [$filename, $id], "WHERE id = ? LIMIT 1");
                }
            }

            if (function_exists('do_action')) {
                do_action('ojuba_blog_saved', $id, true);
            }

            sweet("success", "نجاح", "تم انشاء المقالة بنجاح", "blogs");
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
<link rel="stylesheet" href="../js/select2.css">
<script src="../js/select2.min.js"></script>
<?php if (!blogsFeaturedColumnExists()): ?>
<div class="alert alert-warning">
  خاصية "مقال مميّز/عاجل" غير مُجهَّزة بعد بقاعدة البيانات. <a href="blogs/migrate">اضغط هنا لتشغيل الترحيل مرة واحدة</a> لتفعيلها.
</div>
<?php endif; ?>
<div class="card mb-4">
    <h5 class="card-header">انشاء مقالة جديدة</h5>
    <form class="card-body" method="post" enctype="multipart/form-data">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" name="name" placeholder="اسم المقالة بالعربية" value="<?php echo $name ?? '' ?>">
                    <label>اسم المقالة <sup>(عربي)</sup></label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" name="name_en" placeholder="اسم المقالة بالانجليزية" value="<?php echo $name_en ?? '' ?>">
                    <label>اسم المقالة <sup>(انجليزي)</sup></label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                    <p>محتوى المقالة <sup>(عربي)</sup>:</p>
                    <textarea class="form-control contents" placeholder="محتوى المقالة عربي" name="description"><?php echo $description ?? '' ?></textarea>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                    <p>محتوى المقالة <sup>(انجليزي)</sup>:</p>
                    <textarea class="form-control contents" placeholder="محتوى المقالة انجليزي" name="description_en"><?php echo $description_en ?? '' ?></textarea>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" name="tags" placeholder="أدخل الكلمات الدلالية" value="<?php echo $tags ?? '' ?>">
                    <label>الكلمات الدلالية (عربي) <sup class="text-success">(اختياري)</sup></label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control" name="tags_en" placeholder="أدخل الكلمات الدلالية" value="<?php echo $tags_en ?? '' ?>">
                    <label>الكلمات الدلالية (انجليزي) <sup class="text-success">(اختياري)</sup></label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control slug" name="slug" id="slug" data-id="" placeholder="أدخل اسم رابط المقالة" value="<?php echo $slug ?? '' ?>" onkeyup="this.value = this.value.replace(/[^a-zA-Z0-9-_]/g, '')" required>
                    <label>اسم الرابط slug</label>
                    <div id="message"></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                    <select class="form-select select2" name="category">
                        <option value="">بدون تصنيف</option>
                        <?php
                        dbSelect("blog_categories", "*", "ORDER BY id DESC");
                        if ($countrows >= 1) {
                            foreach ($rows as $category_row) {
                                echo '<option value="' . $category_row['id'] . '">' . $category_row['name'] . ' - ' . $category_row['name_en'] . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <label>تصنيف المقالة</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                    <select class="form-select" name="status" id="status">
                        <option value="active" <?php if ($status == "active") echo "selected" ?>>مفعل</option>
                        <option value="disabled" <?php if ($status == "disabled") echo "selected" ?>>مخفي</option>
                    </select>
                    <label>الحالة</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                    <input type="file" class="form-control" id="imageInputNew" name="image" accept="image/*" required>
                    <label>صورة المقالة</label>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary mt-2 media-picker-btn" data-target="#imageInputNew"><i class="mdi mdi-image-multiple-outline"></i> اختر من المكتبة</button>
            </div>
            <?php if (blogsFeaturedColumnExists()): ?>
            <div class="col-md-6">
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" role="switch" name="featured" id="featuredSwitch">
                    <label class="form-check-label" for="featuredSwitch">مقال مميّز / عاجل</label>
                </div>
                <small class="form-text text-muted">يظهر بأولوية في شريط الأخبار العاجلة والمقال الرئيسي بقوالب الأخبار.</small>
            </div>
            <?php endif; ?>
            <?php if (blogsRelatedMatchColumnExists() && moduleEnabled('matches') && matchesTableExists()): ?>
            <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                    <select class="form-select select2" name="related_match_id">
                        <option value="">بدون ربط بمباراة</option>
                        <?php
                        dbSelect("sport_matches", "id, competition, team_home, team_away, match_date", "WHERE status = ? ORDER BY match_date DESC", ["active"]);
                        if ($countrows >= 1) {
                            foreach ($rows as $match_row) {
                                echo '<option value="' . $match_row['id'] . '">' . $match_row['team_home'] . ' × ' . $match_row['team_away'] . ' — ' . htmlspecialchars($match_row['competition'], ENT_QUOTES, 'UTF-8') . ' (' . date('Y-m-d', strtotime($match_row['match_date'])) . ')</option>';
                            }
                        }
                        ?>
                    </select>
                    <label>المباراة المرتبطة <sup class="text-success">(اختياري)</sup></label>
                </div>
                <small class="form-text text-muted">إن اخترت مباراة، يعرض القالب صندوق ملخص المباراة أعلى صفحة المقال (يدعمه قالب OjubaSport حالياً).</small>
            </div>
            <?php endif; ?>
        </div>
        <div class="pt-4">
            <?php $csrf->input(); ?>
            <button type="submit" name="submit" class="btn btn-primary me-sm-3 me-1 waves-effect waves-light" disabled><i class="mdi mdi-plus"></i> انشاء</button>
            <button type="submit" name="preview" class="btn btn-secondary" formaction="../preview-post" formtarget="_blank"> معاينة </button>
        </div>
    </form>
</div>
<script>
    $('.select2').select2();
</script>
<script src="js/editor.js"></script>
<script src="js/blogs.js"></script>