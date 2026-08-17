<title>باقات الأسعار</title>
<h4 class="py-3 mb-3"><span class="text-muted fw-light">باقات الأسعار /</span> عرض الكل</h4>

<?php

dbSelect("pricing_page_settings", "*", "LIMIT 1");
if ($countrows == 1) {
  $pageSettingsExist = $countrows === 1;
  $pageSettings = $pageSettingsExist ? $rows[0] : null;
} else {
  $pageSettingsExist = false;
  $pageSettings = null;
}


if (isset($_POST['submit_page_settings'])) {
  $csrf->verify();
  $pp_enabled = isset($_POST['pp_enabled']) ? 1 : 0;
  $pp_title = safer($_POST['pp_title'] ?? null);
  $pp_title_en = safer($_POST['pp_title_en'] ?? null);
  $pp_description = safer($_POST['pp_description'] ?? null);
  $pp_description_en = safer($_POST['pp_description_en'] ?? null);
  $pp_seo_title = safer($_POST['pp_seo_title'] ?? null);
  $pp_seo_title_en = safer($_POST['pp_seo_title_en'] ?? null);
  $pp_seo_description = safer($_POST['pp_seo_description'] ?? null);
  $pp_seo_description_en = safer($_POST['pp_seo_description_en'] ?? null);

  if ($pageSettingsExist) {
    dbUpdate(
      "pricing_page_settings",
      "enabled = ?, title = ?, title_en = ?, description = ?, description_en = ?, seo_title = ?, seo_title_en = ?, seo_description = ?, seo_description_en = ?",
      [$pp_enabled, $pp_title, $pp_title_en, $pp_description, $pp_description_en, $pp_seo_title, $pp_seo_title_en, $pp_seo_description, $pp_seo_description_en, $pageSettings['id']],
      "WHERE id = ? LIMIT 1"
    );
  } else {
    dbInsert(
      "pricing_page_settings",
      "enabled, title, title_en, description, description_en, seo_title, seo_title_en, seo_description, seo_description_en",
      [$pp_enabled, $pp_title, $pp_title_en, $pp_description, $pp_description_en, $pp_seo_title, $pp_seo_title_en, $pp_seo_description, $pp_seo_description_en]
    );
  }
  sweet("success", "تم", "تم حفظ إعدادات صفحة الأسعار بنجاح", "pricing");
  exit;
}
?>

<?php if (!$pageSettingsExist): ?>
  <div class="alert alert-warning">
    جدول إعدادات صفحة الأسعار غير موجود بعد. <a href="pricing/migrate">اضغط هنا لتشغيل الترحيل مرة واحدة</a> قبل استخدام هذا القسم.
  </div>
<?php else: ?>
  <div class="card mb-4">
    <h5 class="card-header">إعدادات صفحة الأسعار المستقلة (<a href="<?php echo $site['site_url'] ?>pricing" target="_blank">/pricing</a>)</h5>
    <form class="card-body" method="post">
      <div class="row g-4">
        <div class="col-12">
          <label class="switch switch-lg">
            <input type="checkbox" class="switch-input" role="switch" id="pp_enabled" name="pp_enabled" <?php if ($pageSettings['enabled']) echo 'checked' ?> />
            <span class="switch-toggle-slider">
              <span class="switch-on"></span>
              <span class="switch-off"></span>
            </span>
            <span class="switch-label">تفعيل صفحة الأسعار المستقلة</span>
          </label>
        </div>

        <div class="col-12">
          <hr class="mt-0">
          <h6 class="mb-1">عنوان ووصف الصفحة (SEO)</h6>
          <p class="text-muted mb-0" style="font-size:13px;">يظهر في نتائج البحث بجوجل، وفي عنوان تبويب المتصفح، وعند مشاركة رابط الصفحة — لا يظهر مباشرة للزائر داخل الصفحة نفسها.</p>
        </div>
        <div class="col-md-6">
          <div class="form-floating form-floating-outline">
            <input type="text" class="form-control" name="pp_seo_title" value="<?php echo safer($pageSettings['seo_title']) ?>">
            <label>عنوان الصفحة - SEO (عربي)</label>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-floating form-floating-outline">
            <input type="text" class="form-control" name="pp_seo_title_en" value="<?php echo safer($pageSettings['seo_title_en']) ?>">
            <label>عنوان الصفحة - SEO (انجليزي)</label>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-floating form-floating-outline">
            <textarea class="form-control" name="pp_seo_description" style="height:100px"><?php echo safer($pageSettings['seo_description']) ?></textarea>
            <label>وصف الصفحة - SEO (عربي)</label>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-floating form-floating-outline">
            <textarea class="form-control" name="pp_seo_description_en" style="height:100px"><?php echo safer($pageSettings['seo_description_en']) ?></textarea>
            <label>وصف الصفحة - SEO (انجليزي)</label>
          </div>
        </div>

        <div class="col-12">
          <hr>
          <h6 class="mb-1">العنوان الرئيسي الظاهر في الصفحة (الهيدر)</h6>
          <p class="text-muted mb-0" style="font-size:13px;">هذا هو العنوان الكبير والوصف اللذان يظهران فعلياً للزائر أعلى بطاقات الأسعار — منفصلان تماماً عن عنوان ووصف السيو أعلاه.</p>
        </div>
        <div class="col-md-6">
          <div class="form-floating form-floating-outline">
            <input type="text" class="form-control" name="pp_title" value="<?php echo safer($pageSettings['title']) ?>">
            <label>العنوان الرئيسي في الصفحة (عربي)</label>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-floating form-floating-outline">
            <input type="text" class="form-control" name="pp_title_en" value="<?php echo safer($pageSettings['title_en']) ?>">
            <label>العنوان الرئيسي في الصفحة (انجليزي)</label>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-floating form-floating-outline">
            <textarea class="form-control" name="pp_description" style="height:100px"><?php echo safer($pageSettings['description']) ?></textarea>
            <label>الوصف تحت العنوان الرئيسي (عربي)</label>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-floating form-floating-outline">
            <textarea class="form-control" name="pp_description_en" style="height:100px"><?php echo safer($pageSettings['description_en']) ?></textarea>
            <label>الوصف تحت العنوان الرئيسي (انجليزي)</label>
          </div>
        </div>
      </div>
      <div class="pt-4">
        <?php $csrf->input(); ?>
        <button type="submit" name="submit_page_settings" class="btn btn-primary"><i class="mdi mdi-content-save"></i> حفظ إعدادات الصفحة</button>
        <button type="submit" name="preview" class="btn btn-secondary" formaction="../preview-pricing?lang=ar" formtarget="_blank"><i class="mdi mdi-eye-outline"></i> معاينة (عربي)</button>
        <button type="submit" name="preview" class="btn btn-secondary" formaction="../preview-pricing?lang=en" formtarget="_blank"><i class="mdi mdi-eye-outline"></i> معاينة (English)</button>
        <a href="pricing-faqs" class="btn btn-outline-secondary"><i class="mdi mdi-help-circle-outline"></i> الأسئلة الشائعة لصفحة الأسعار</a>
        <a href="pricing-compare" class="btn btn-outline-secondary"><i class="mdi mdi-table"></i> جدول مقارنة الباقات</a>
      </div>
    </form>
  </div>
<?php endif; ?>

<div class="row mb-3">
  <div class="col-sm">
    <a href="pricing/new" class="btn btn-secondary waves-effect waves-light"><i class="mdi mdi-plus"></i> اضافة باقة جديدة</a>
  </div>
</div>

<div class="card mt-2">
  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle orders_table">
      <thead class="table-light">
        <tr>
          <th style="width:70px">#</th>
          <th>الاسم</th>
          <th>السعر</th>
          <th>مميزة</th>
          <th>الترتيب</th>
          <th>الحالة</th>
          <th style="width:120px">إجراء</th>
        </tr>
      </thead>
      <tbody>
        <?php
        dbSelect("pricing", "id, name, price, currency, is_featured, ordering, status", "ORDER BY ordering ASC, id DESC");
        if ($countrows >= 1) {
          $i = 1;
          foreach ($rows as $row) {
            echo '
              <tr>
                  <td>' . $i++ . '</td>
                  <td>' . $row['name'] . '</td>
                  <td>' . $row['price'] . ' ' . $row['currency'] . '</td>
                  <td>' . ($row['is_featured'] == 1 ? '<span class="badge bg-warning">مميزة</span>' : '') . '</td>
                  <td>' . $row['ordering'] . '</td>
                  <td>' . $row['status'] . '</td>
                  <td>
                    <div class="dropdown">
                      <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">إجراء</button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                          <a href="pricing/' . $row['id'] . '/edit" class="dropdown-item text-warning edit"><i class="mdi mdi-pen"></i> تعديل</a>
                          <span data-id="' . $row['id'] . '" data-name="' . $row['name'] . '" data-action="delete" class="dropdown-item text-danger delete"><i class="mdi mdi-delete"></i> حذف</span>
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

<script src="js/pricing.js"></script>
<script src="js/tables.js"></script>