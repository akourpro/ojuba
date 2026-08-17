</div>

<!-- Footer -->
<footer class="content-footer footer bg-footer-theme">
    <div class="container-xxl">
        <div class="footer-container d-flex align-items-center justify-content-between py-3 flex-md-row flex-column">
            <div class="mb-2 mb-md-0">
                ©
                <script>
                    document.write(new Date().getFullYear());
                </script>
                صنع بكل <span class="text-danger"><i class="tf-icons mdi mdi-heart"></i></span> بواسطة
                <a href="" class="footer-link fw-medium">محمد عكور</a>
            </div>
        </div>
    </div>
</footer>
<!-- / Footer -->

<div class="content-backdrop fade"></div>
</div>
<!-- Content wrapper -->
</div>
<!-- / Layout page -->
</div>

<!-- Overlay -->
<div class="layout-overlay layout-menu-toggle"></div>

<!-- Drag Target Area To SlideIn Menu On Small Screens -->
<div class="drag-target"></div>
</div>
<!-- / Layout wrapper -->

<!-- Core JS -->
<!-- build:js assets/vendor/js/core.js -->

<script src="assets/vendor/libs/popper/popper.js"></script>
<script src="assets/vendor/js/bootstrap.js"></script>
<script src="assets/vendor/libs/node-waves/node-waves.js"></script>
<script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
<script src="assets/vendor/libs/hammer/hammer.js"></script>
<script src="assets/vendor/libs/i18n/i18n.js"></script>
<script src="assets/vendor/libs/typeahead-js/typeahead.js"></script>
<script src="assets/vendor/js/menu.js"></script>


<!-- endbuild -->

<!-- Vendors JS -->
<!-- <script src="assets/vendor/libs/bootstrap-maxlength/bootstrap-maxlength.js"></script> -->
<script src="assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>

<!-- Main JS -->
<script src="assets/js/main.js"></script>
<script src="js/media-picker.js"></script>

<?php if (($site['language_mode'] ?? 'both') !== 'both'): ?>
<script>
    // إخفاء حقول المحتوى باللغة الثانية تلقائياً بكل صفحات لوحة التحكم عندما تكون
    // "لغة الموقع الرئيسية" (إعدادات الموقع > اللغة ونوع الموقع) محصورة بلغة واحدة
    // فقط. يعمل هذا مع أي حقل بأي وحدة يتبع اصطلاح تسمية "field_en" / "field_en[]"
    // تلقائياً دون حاجة لتعديل كل نموذج (new.php/edit.php) على حدة — البيانات القديمة
    // المحفوظة باللغة الثانية تبقى محفوظة بقاعدة البيانات كما هي، فقط تُخفى من الواجهة.
    // انظر توثيق الاصطلاح في CLAUDE.md.
    document.addEventListener('DOMContentLoaded', function () {
        var wrapperSelectors = '.form-floating, .col-md-12, .col-md-8, .col-md-6, .col-md-4, .col-md-3, .col-12, .mb-3, .form-group';
        document.querySelectorAll('[name$="_en"], [name*="_en["]').forEach(function (el) {
            var wrapper = el.closest(wrapperSelectors) || el.parentElement;
            if (wrapper) {
                wrapper.style.display = 'none';
            }
        });
    });
</script>
<?php endif; ?>

<?php if (moduleEnabled('feeds') && feedsTableExists()): ?>
<?php
// "نبضة" تلقائية (fire-and-forget) تُشغِّل جدولة سحب المقالات المستحقة في
// الخلفية كل مرة يتصفّح فيها صاحب الموقع لوحة التحكم — تضمن عمل الميزة
// "تلقائياً بالكامل" دون أي إعداد يدوي حتى بدون Cron حقيقي بالاستضافة (الذي
// يبقى الخيار الأوثق، راجع بطاقة "التشغيل التلقائي" بصفحة سحب المقالات).
// الحماية من التكرار المفرط تتم بالكامل من جهة الخادم (api/feeds-cron.php
// يتجاهل الطلب بصمت إن كان آخر تشغيل أحدث من 5 دقائق)، فلا خطورة من إطلاقها
// على كل تحميل صفحة بلوحة التحكم — الطلب غير مرئي للمستخدم ولا يُبطئ الصفحة
// (fetch بلا انتظار للرد، مع keepalive حتى لو غادر المستخدم الصفحة فوراً).
require_once 'includes/feed_importer.php';
$feedsCronToken = ensureFeedsCronToken();
?>
<script>
    (function () {
        try {
            fetch('<?php echo $site['site_url']; ?>api/feeds-cron?token=<?php echo $feedsCronToken; ?>', { method: 'GET', keepalive: true, mode: 'no-cors' });
        } catch (e) { /* تجاهل بصمت — لا تأثير على تجربة لوحة التحكم */ }
    })();
</script>
<?php endif; ?>

<?php if (isOwner()): ?>
<?php
// "نبضة" تلقائية (fire-and-forget) للتحقق من وجود إصدار جديد من السكربت —
// نفس آلية "النبضة" المستخدمة لوحدة سحب المقالات أعلاه بالضبط. الحماية من
// التكرار المفرط تتم بالكامل من جهة الخادم (updaterCheckForUpdate() تتجاهل
// الطلب بصمت إن كان آخر فحص أحدث من UPDATER_CHECK_INTERVAL_SECONDS بـ
// includes/updater.php)، فلا خطورة من إطلاقها على كل تحميل صفحة بلوحة
// التحكم للمالك (owner) فقط.
?>
<script>
    (function () {
        try {
            fetch('<?php echo $site['site_url']; ?>api/update-check', { method: 'GET', keepalive: true, mode: 'no-cors' });
        } catch (e) { /* تجاهل بصمت */ }
    })();
</script>
<?php endif; ?>

</body>

</html>