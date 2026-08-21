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
<?php
requireOwner();
$templatesDir = '../templates';
$folders = array_filter(glob($templatesDir . '/*'), 'is_dir');

?>
<title>القوالب</title>
<div class="container mt-5">
    <h1 class="text-center mb-4">معرض القوالب</h1>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title mb-2"><i class="mdi mdi-upload"></i> استيراد قالب جديد (ملف zip)</h5>
            <form id="themeImportForm" class="row g-2 align-items-end">
                <div class="col-md-8 col-lg-6">
                    <input type="file" id="themeZipInput" name="zipfile" class="form-control" accept=".zip,application/zip">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary"><i class="mdi mdi-upload"></i> استيراد</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <?php foreach ($folders as $folder): ?>
            <?php
            $templateName = basename($folder);
            $screenshotPath = "$folder/screenshot.png";
            // بعض القوالب (خصوصاً المستوردة عبر zip) قد لا تحتوي على screenshot.png، وبدونها
            // كانت تختفي بالكامل من المعرض. ننشئ صورة فارغة بديلة تلقائياً حتى يظهر القالب
            // للمستخدم، ويمكنه لاحقاً استبدالها بصورة حقيقية عبر رفع ملف بنفس الاسم.
            if (!file_exists($screenshotPath) && is_writable($folder)) {
                $blankPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
                @file_put_contents($screenshotPath, $blankPng);
            }
            $templateData = themeData($folder);
            if (file_exists($screenshotPath)): ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <img src="<?php echo $screenshotPath; ?>" class="card-img-top" alt="<?php echo $templateName; ?>" style="height: 250px">
                        <div class="card-body d-flex justify-content-between align-items-center" style="display: grid !important;">
                            <div>
                                <?php if (isset($templateData['name'])): ?>
                                    <h5 class="card-title mb-0"><?php echo $templateData['name']; ?></h5>
                                <?php else: ?>
                                    <h5 class="card-title mb-0"><?php echo $templateName; ?></h5>
                                <?php endif; ?>
                                <?php if (isset($templateData['description'])): ?>
                                    <p class="card-text mb-0"><?php echo $templateData['description']; ?>.</p>
                                <?php else: ?>
                                    <p class="card-text mb-0">هذا هو قالب <?php echo $templateName; ?>.</p>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex gap-2 flex-shrink-0" style="display: grid !important;">
                                <a href="<?php echo rtrim($site['site_url'], '/') . '/?start_preview=' . urlencode($templateName); ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary" title="معاينة كامل الموقع بهذا القالب دون تفعيله">
                                    <i class="mdi mdi-eye-outline"></i> معاينة
                                </a>
                                <?php if ($site['theme'] == $templateName) {
                                    echo '<span class="btn btn-success">مفعل</span>';
                                } else {
                                    echo '<span data-name="' . $templateName . '" class="btn btn-primary active">تفعيل</span>';
                                }
                                ?>
                            </div>
                        </div>
                        <?php if (isset($templateData['design']) or isset($templateData['url'])): ?>
                            <div class="card-footer border-top">
                                <div class="d-flex justify-content-between align-items-center">
                                    <?php if (isset($templateData['design']) and !empty(trim($templateData['design']))) {
                                        echo '<div>
                                                <small>المطور: </small>
                                                <strong>' . safer($templateData['design']) . '</strong>
                                            </div>';
                                    } ?>

                                    <?php if (isset($templateData['url'])) {
                                        echo '<div><a href="' . safer($templateData['url']) . '?utm_source=' . safer($site['site_url']) . '" target="_blank" class="text-decoration-none">زياة موقع المطور</a></div>';
                                    } ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<script src="js/templates_import.js"></script>
<script>
    $(".active").click(function() {
        name = $(this).data("name");

        Swal.fire({
            title: "هل انت متأكد تفعيل القالب (" + name + ")",
            icon: "info",
            showCancelButton: true,
            confirmButtonColor: "#4a971c",
            cancelButtonColor: "#9A9A9A",
            confirmButtonText: "نعم",
            cancelButtonText: "تراجع",
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "api/themes",
                    headers: {
                        '_csrf': $('meta[name="_csrf"]').attr('content')
                    },
                    data: JSON.stringify({
                        name: name
                    }),
                    dataType: "json",
                    encode: true,
                    beforeSend: function() {
                        let timerInterval;
                        Swal.fire({
                            title: "الرجاء الانتظار ...",
                            // timer: 5000,
                            timerProgressBar: true,
                            didOpen: () => {
                                Swal.showLoading();
                                timerInterval = setInterval(() => {}, 100);
                            },
                            willClose: () => {
                                clearInterval(timerInterval);
                            },
                        });
                    },
                }).done(function(data) {
                    if (data.status) {
                        Swal.fire({
                            icon: "success",
                            title: data.message,
                            toast: true,
                            position: "top-start",
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.addEventListener("mouseenter", Swal.stopTimer);
                                toast.addEventListener("mouseleave", Swal.resumeTimer);
                            },
                        });
                        setTimeout(location.reload.bind(location), 500);
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: data.message,
                            toast: true,
                            position: "top-start",
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.addEventListener("mouseenter", Swal.stopTimer);
                                toast.addEventListener("mouseleave", Swal.resumeTimer);
                            },
                        });
                    }
                });
            }
        });
    });
</script>