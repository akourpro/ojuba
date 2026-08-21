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
        document.addEventListener('DOMContentLoaded', function() {
            var wrapperSelectors = '.form-floating, .col-md-12, .col-md-8, .col-md-6, .col-md-4, .col-md-3, .col-12, .mb-3, .form-group';
            document.querySelectorAll('[name$="_en"], [name*="_en["]').forEach(function(el) {
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
    require_once 'includes/feed_importer.php';
    $feedsCronToken = ensureFeedsCronToken();
    ?>
    <script>
        (function() {
            try {
                fetch('<?php echo $site['site_url']; ?>api/feeds-cron?token=<?php echo $feedsCronToken; ?>', {
                    method: 'GET',
                    keepalive: true,
                    mode: 'no-cors'
                });
            } catch (e) {
                /* تجاهل بصمت */
            }
        })();
    </script>
<?php endif; ?>

<?php if (isOwner()): ?>
    <script>
        (function() {
            try {
                fetch('<?php echo $site['site_url']; ?>api/update-check', {
                    method: 'GET',
                    keepalive: true,
                    mode: 'no-cors'
                });
            } catch (e) {
                /* تجاهل بصمت */
            }
        })();
    </script>
<?php endif; ?>

</body>

</html>