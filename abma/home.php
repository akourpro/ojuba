<title>تعديل الصفحة الرئيسية</title>
<?php
requireOwner();
if (!isset($_GET['lang'])):
?>
    <style>
        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15) !important;
        }
    </style>


    <!-- واجهة اختيار اللغة -->
    <div class="container mt-5">
        <h2 class="mb-4 text-center">اختر الصفحة التي تريد تعديلها</h2>
        <div class="row justify-content-center">
            <div class="col-md-5 mb-3">
                <div class="card text-center shadow-sm card-hover" style="cursor: pointer;" onclick="location.href='home.php?lang=ar'">
                    <div class="card-body">
                        <h5 class="card-title">🇸🇦 تعديل الصفحة العربية</h5>
                        <p class="card-text">تحرير محتوى الصفحة الرئيسية باللغة العربية.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-5 mb-3">
                <div class="card text-center shadow-sm card-hover" style="cursor: pointer;" onclick="location.href='home.php?lang=en'">
                    <div class="card-body">
                        <h5 class="card-title">🇬🇧 تعديل الصفحة الإنجليزية</h5>
                        <p class="card-text">تحرير محتوى الصفحة الرئيسية باللغة الإنجليزية.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
    exit;
endif;

// تحميل القالب المحدد
$lang = $_GET['lang'] === 'en' ? 'en' : 'ar';
$filename = $lang === 'en' ? 'home_en.twig' : 'home.twig';
$filePath = '../templates/' . $site['theme'] . '/' . $filename;
$content = file_get_contents($filePath);

// عند الحفظ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $csrf->verify();
    file_put_contents($filePath, $_POST['content']);
    logAction("home_raw_edit_save", "تم تعديل ملف الرئيسية مباشرة: " . $filename);
    exit;
}
?>

<!-- واجهة التعديل -->
<div class="container mt-5">
    <h2 class="mb-4 text-center">
        تعديل الصفحة: <?php echo $lang === 'en' ? 'الإنجليزية 🇬🇧' : 'العربية 🇸🇦'; ?>
    </h2>

    <div class="mb-3">
        <label for="editor" class="form-label">محتوى القالب (Twig):</label>
        <textarea id="editor" class="form-control" oninput="updatePreview()" rows="15"><?php echo htmlspecialchars($content); ?></textarea>
    </div>

    <div class="mb-4 text-end">
        <button class="btn btn-primary" onclick="saveContent()">💾 حفظ التعديلات</button>
    </div>
    <div class="mb-4 text-start">
        <button class="btn btn-secondary" onclick="confirmExit()">🔙 العودة</button>
    </div>

    <h4 class="mb-3">📄 معاينة مباشرة:</h4>
    <iframe id="preview" class="w-100 border rounded" style="height: 500px;"></iframe>
</div>

<!-- جافاسكربت -->
<script>
    function updatePreview() {
        const content = document.getElementById('editor').value;
        const iframe = document.getElementById('preview');

        const formData = new FormData();
        formData.append('content', content);

        fetch('../preview-home', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(html => {
                const doc = iframe.contentDocument || iframe.contentWindow.document;
                doc.open();
                doc.write(html);
                doc.close();
            });
    }

    function saveContent() {
        const content = document.getElementById('editor').value;

        const formData = new FormData();
        formData.append('save', '1');
        formData.append('content', content);
        formData.append('_csrf', document.querySelector('meta[name="_csrf"]').content);

        fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(() => {
                sweet("success", "نجاح", "تم حفظ التعديلات بنجاح");
            });
    }

    function confirmExit() {
        sweetConfirm("هل أنت متأكد من رغبتك في العودة؟ تأكد من حفظ التعديلات أولاً.", "العودة", "إلغاء", function() {
            window.location.href = 'home.php';
        });
    }

    function sweetConfirm(message, confirmText, cancelText, onConfirm) {
        Swal.fire({
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
        }).then((result) => {
            if (result.isConfirmed) {
                onConfirm();
            }
        });
    }


    // تحديث المعاينة عند تحميل الصفحة
    updatePreview();
</script>