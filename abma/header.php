<?php
// مطلوب هنا فقط لدالة updaterAvailableInfo() (شارة إشعار "الإصدار والتحديثات"
// بالقائمة الجانبية أدناه) — قراءة خفيفة من settings المخزَّنة مسبقاً بدون أي
// اتصال شبكة، راجع includes/updater.php.
require_once 'includes/updater.php';
?>
<!DOCTYPE html>

<html lang="ar" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="rtl" data-theme="theme-default" data-assets-path="assets/">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <META NAME="ROBOTS" CONTENT="NOARCHIVE">
    <META NAME="GOOGLEBOT" CONTENT="NOARCHIVE">
    <meta name="_csrf" content="<?php echo $csrf->header(); ?>">

    <base href="<?php echo $site["site_url"]; ?>abma/" />

    <style>
        @font-face {
            font-family: 'Tajawal';
            src: url('assets/vendor/fonts/Tajawal.ttf');
        }
    </style>

    <!-- Icons -->
    <link rel="stylesheet" href="assets/vendor/fonts/materialdesignicons.css" />
    <link rel="stylesheet" href="assets/vendor/fonts/flag-icons.css" />

    <!-- Menu waves for no-customizer fix -->
    <link rel="stylesheet" href="assets/vendor/libs/node-waves/node-waves.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="assets/vendor/css/rtl/core.css" class="template-customizer-core-css" />
    <!-- <link rel="stylesheet" href="assets/vendor/css/rtl/theme-default.css" class="template-customizer-theme-css" /> -->
    <link rel="stylesheet" href="assets/vendor/css/rtl/theme-semi-dark.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="assets/vendor/libs/typeahead-js/typeahead.css" />
    <!-- <link rel="stylesheet" href="assets/vendor/libs/bootstrap-maxlength/bootstrap-maxlength.css" /> -->
    <link rel="stylesheet" href="assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.css" />
    <style>
        .swal2-container {
            z-index: 100000000;
        }

        .note-editor {
            z-index: 1000000 !important;
        }

        .note-editable {
            background: #fff !important;
        }

        .modal {
            z-index: 10000000 !important;
        }
    </style>

    <!-- Page CSS -->
    <script src="assets/vendor/libs/jquery/jquery.js"></script>

    <!-- Helpers -->
    <script src="assets/vendor/js/helpers.js"></script>
    <!-- <script src="assets/vendor/js/template-customizer.js"></script> -->
    <script src="assets/js/config.js"></script>
    <script src="../js/sweetalert2.all.min.js"></script>
    <script src="../js/js.cookie.min.js"></script>
    <script src="../js/functions.js"></script>
</head>

<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu -->

            <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
                <div class="app-brand demo">
                    <a href="dashboard" class="app-brand-link">
                        <span class="app-brand-logo demo">
                            <span style="color: var(--bs-primary)">
                                <svg width="268" height="150" viewBox="0 0 38 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M30.0944 2.22569C29.0511 0.444187 26.7508 -0.172113 24.9566 0.849138C23.1623 1.87039 22.5536 4.14247 23.5969 5.92397L30.5368 17.7743C31.5801 19.5558 33.8804 20.1721 35.6746 19.1509C37.4689 18.1296 38.0776 15.8575 37.0343 14.076L30.0944 2.22569Z" fill="currentColor" />
                                    <path d="M30.171 2.22569C29.1277 0.444187 26.8274 -0.172113 25.0332 0.849138C23.2389 1.87039 22.6302 4.14247 23.6735 5.92397L30.6134 17.7743C31.6567 19.5558 33.957 20.1721 35.7512 19.1509C37.5455 18.1296 38.1542 15.8575 37.1109 14.076L30.171 2.22569Z" fill="url(#paint0_linear_2989_100980)" fill-opacity="0.4" />
                                    <path d="M22.9676 2.22569C24.0109 0.444187 26.3112 -0.172113 28.1054 0.849138C29.8996 1.87039 30.5084 4.14247 29.4651 5.92397L22.5251 17.7743C21.4818 19.5558 19.1816 20.1721 17.3873 19.1509C15.5931 18.1296 14.9843 15.8575 16.0276 14.076L22.9676 2.22569Z" fill="currentColor" />
                                    <path d="M14.9558 2.22569C13.9125 0.444187 11.6122 -0.172113 9.818 0.849138C8.02377 1.87039 7.41502 4.14247 8.45833 5.92397L15.3983 17.7743C16.4416 19.5558 18.7418 20.1721 20.5361 19.1509C22.3303 18.1296 22.9391 15.8575 21.8958 14.076L14.9558 2.22569Z" fill="currentColor" />
                                    <path d="M14.9558 2.22569C13.9125 0.444187 11.6122 -0.172113 9.818 0.849138C8.02377 1.87039 7.41502 4.14247 8.45833 5.92397L15.3983 17.7743C16.4416 19.5558 18.7418 20.1721 20.5361 19.1509C22.3303 18.1296 22.9391 15.8575 21.8958 14.076L14.9558 2.22569Z" fill="url(#paint1_linear_2989_100980)" fill-opacity="0.4" />
                                    <path d="M7.82901 2.22569C8.87231 0.444187 11.1726 -0.172113 12.9668 0.849138C14.7611 1.87039 15.3698 4.14247 14.3265 5.92397L7.38656 17.7743C6.34325 19.5558 4.04298 20.1721 2.24875 19.1509C0.454514 18.1296 -0.154233 15.8575 0.88907 14.076L7.82901 2.22569Z" fill="currentColor" />
                                    <defs>
                                        <linearGradient id="paint0_linear_2989_100980" x1="5.36642" y1="0.849138" x2="10.532" y2="24.104" gradientUnits="userSpaceOnUse">
                                            <stop offset="0" stop-opacity="1" />
                                            <stop offset="1" stop-opacity="0" />
                                        </linearGradient>
                                        <linearGradient id="paint1_linear_2989_100980" x1="5.19475" y1="0.849139" x2="10.3357" y2="24.1155" gradientUnits="userSpaceOnUse">
                                            <stop offset="0" stop-opacity="1" />
                                            <stop offset="1" stop-opacity="0" />
                                        </linearGradient>
                                    </defs>
                                </svg>
                            </span>
                        </span>
                        <span class="app-brand-text demo menu-text fw-bold ms-2">لوحة التحكم</span>
                    </a>

                    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                        <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11.4854 4.88844C11.0081 4.41121 10.2344 4.41121 9.75715 4.88844L4.51028 10.1353C4.03297 10.6126 4.03297 11.3865 4.51028 11.8638L9.75715 17.1107C10.2344 17.5879 11.0081 17.5879 11.4854 17.1107C11.9626 16.6334 11.9626 15.8597 11.4854 15.3824L7.96672 11.8638C7.48942 11.3865 7.48942 10.6126 7.96672 10.1353L11.4854 6.61667C11.9626 6.13943 11.9626 5.36568 11.4854 4.88844Z" fill="currentColor" fill-opacity="0.6" />
                            <path d="M15.8683 4.88844L10.6214 10.1353C10.1441 10.6126 10.1441 11.3865 10.6214 11.8638L15.8683 17.1107C16.3455 17.5879 17.1192 17.5879 17.5965 17.1107C18.0737 16.6334 18.0737 15.8597 17.5965 15.3824L14.0778 11.8638C13.6005 11.3865 13.6005 10.6126 14.0778 10.1353L17.5965 6.61667C18.0737 6.13943 18.0737 5.36568 17.5965 4.88844C17.1192 4.41121 16.3455 4.41121 15.8683 4.88844Z" fill="currentColor" fill-opacity="0.38" />
                        </svg>
                    </a>
                </div>

                <div class="menu-inner-shadow"></div>

                <ul class="menu-inner py-1">
                    <!-- Dashboards -->
                    <li class="menu-header fw-medium mt-4">
                        <span class="menu-header-text">البداية</span>
                    </li>
                    <li class="menu-item">
                        <a href="../" class="menu-link" target="_blank">
                            <i class="menu-icon tf-icons mdi mdi-web"></i>رئيسية الموقع</a>
                    </li>
                    <li class="menu-item <?php if (setTitle(escUrl($_SERVER['PHP_SELF'])) == "abma_index") echo "active"; ?>">
                        <a href="dashboard" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-home"></i>الرئيسية</a>
                    </li>

                    <li class="menu-header fw-medium mt-4">
                        <span class="menu-header-text">المحتويات</span>
                    </li>
                    <?php if (moduleEnabled('pages')): ?>
                    <?php
                    $pages_array = ["pages_all", "pages_edit", "pages_new"];
                    ?>
                    <li class="menu-item <?php if (in_array(setTitle(escUrl($_SERVER['PHP_SELF'])), $pages_array)) echo "active"; ?>">
                        <a href="pages" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-book-open-page-variant"></i>الصفحات
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="menu-item <?php if (setTitle(escUrl($_SERVER['PHP_SELF'])) == "media_index") echo "active"; ?>">
                        <a href="media" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-image-multiple-outline"></i> مكتبة الوسائط
                        </a>
                    </li>
                    <?php if (moduleEnabled('blogs')): ?>
                    <?php
                    $blogs_array = ["blogs_all", "blogs_edit", "blogs_new"];
                    ?>
                    <li class="menu-item <?php if (in_array(setTitle(escUrl($_SERVER['PHP_SELF'])), $blogs_array)) echo "active"; ?>">
                        <a href="blogs" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-post"></i> المقالات
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (moduleEnabled('blog_categories')): ?>
                    <li class="menu-item <?php if (setTitle(escUrl($_SERVER['PHP_SELF'])) == "abma_blog_categories") echo "active"; ?>">
                        <a href="blog-categories" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-shape-outline"></i> تصنيفات المقالات
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (moduleEnabled('feeds')): ?>
                    <?php
                    $feeds_array = ["feeds_all", "feeds_new", "feeds_edit"];
                    ?>
                    <li class="menu-item <?php if (in_array(setTitle(escUrl($_SERVER['PHP_SELF'])), $feeds_array)) echo "active"; ?>">
                        <a href="feeds" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-rss"></i> سحب المقالات (RSS)
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (moduleEnabled('services')): ?>
                    <?php
                    $services_array = ["services_all", "services_edit", "services_new"];
                    ?>
                    <li class="menu-item <?php if (in_array(setTitle(escUrl($_SERVER['PHP_SELF'])), $services_array)) echo "active"; ?>">
                        <a href="services" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-account-wrench"></i> الخدمات
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (moduleEnabled('portfolio')): ?>
                    <?php
                    $portfolio_array = ["portfolio_all", "portfolio_edit", "portfolio_new"];
                    ?>
                    <li class="menu-item <?php if (in_array(setTitle(escUrl($_SERVER['PHP_SELF'])), $portfolio_array)) echo "active"; ?>">
                        <a href="portfolio" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-network-pos"></i> معرض الأعمال
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (moduleEnabled('categories')): ?>
                    <li class="menu-item <?php if (setTitle(escUrl($_SERVER['PHP_SELF'])) == "abma_categories") echo "active"; ?>">
                        <a href="categories" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-network-pos"></i> فئات معرض الأعمال
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (moduleEnabled('team')): ?>
                    <?php
                    $team_array = ["team_all", "team_edit", "team_new"];
                    ?>
                    <li class="menu-item <?php if (in_array(setTitle(escUrl($_SERVER['PHP_SELF'])), $team_array)) echo "active"; ?>">
                        <a href="team" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-account-group"></i> فريق العمل
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (moduleEnabled('testimonials')): ?>
                    <?php
                    $testimonials_array = ["testimonials_all", "testimonials_edit", "testimonials_new"];
                    ?>
                    <li class="menu-item <?php if (in_array(setTitle(escUrl($_SERVER['PHP_SELF'])), $testimonials_array)) echo "active"; ?>">
                        <a href="testimonials" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-comment-quote"></i> آراء العملاء
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (moduleEnabled('faq')): ?>
                    <?php
                    $faq_array = ["faq_all", "faq_edit", "faq_new"];
                    ?>
                    <li class="menu-item <?php if (in_array(setTitle(escUrl($_SERVER['PHP_SELF'])), $faq_array)) echo "active"; ?>">
                        <a href="faq" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-help-circle-outline"></i> الأسئلة الشائعة
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (moduleEnabled('stats')): ?>
                    <?php
                    $stats_array = ["stats_all", "stats_edit", "stats_new"];
                    ?>
                    <li class="menu-item <?php if (in_array(setTitle(escUrl($_SERVER['PHP_SELF'])), $stats_array)) echo "active"; ?>">
                        <a href="stats" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-chart-box-outline"></i> الإحصائيات
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (moduleEnabled('pricing')): ?>
                    <?php
                    $pricing_array = ["pricing_all", "pricing_edit", "pricing_new"];
                    ?>
                    <li class="menu-item <?php if (in_array(setTitle(escUrl($_SERVER['PHP_SELF'])), $pricing_array)) echo "active"; ?>">
                        <a href="pricing" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-tag-outline"></i> باقات الأسعار
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (moduleEnabled('branches')): ?>
                    <?php
                    $branches_array = ["branches_all", "branches_edit", "branches_new"];
                    ?>
                    <li class="menu-item <?php if (in_array(setTitle(escUrl($_SERVER['PHP_SELF'])), $branches_array)) echo "active"; ?>">
                        <a href="branches" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-map-marker-outline"></i> الفروع
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (moduleEnabled('certificates')): ?>
                    <?php
                    $certificates_array = ["certificates_all", "certificates_edit", "certificates_new"];
                    ?>
                    <li class="menu-item <?php if (in_array(setTitle(escUrl($_SERVER['PHP_SELF'])), $certificates_array)) echo "active"; ?>">
                        <a href="certificates" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-certificate-outline"></i> الشهادات والجوائز
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (moduleEnabled('clients')): ?>
                    <?php
                    $clients_array = ["clients_all", "clients_edit", "clients_new"];
                    ?>
                    <li class="menu-item <?php if (in_array(setTitle(escUrl($_SERVER['PHP_SELF'])), $clients_array)) echo "active"; ?>">
                        <a href="clients" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-domain"></i> العملاء
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php
                    $contacts_array = ["contacts_all"];
                    ?>
                    <li class="menu-item <?php if (in_array(setTitle(escUrl($_SERVER['PHP_SELF'])), $contacts_array)) echo "active"; ?>">
                        <a href="contacts" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-email"></i> رسائل التواصل
                            <?php dbSelect("contact", "id", "WHERE seen = ?", [0]);
                            if ($countrows >= 1) {
                                echo '<span class="badge bg-danger rounded-pill ms-auto">' . $countrows . '</span>';
                            } ?>
                        </a>
                    </li>

                    <?php if (isOwner()): ?>
                    <?php if (moduleEnabled('mailing')): ?>
                    <li class="menu-header fw-medium mt-4">
                        <span class="menu-header-text">التسويق</span>
                    </li>
                    <?php
                    $mailing_array = ["mailing_lists", "mailing_lists-new", "mailing_lists-edit", "mailing_contacts", "mailing_contacts-new", "mailing_contacts-edit", "mailing_contacts-bulk", "mailing_contacts-import", "mailing_campaigns", "mailing_campaigns-new", "mailing_campaigns-view"];
                    ?>
                    <li class="menu-item <?php if (in_array(setTitle(escUrl($_SERVER['PHP_SELF'])), $mailing_array)) echo "active"; ?>">
                        <a href="mailing" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-email-fast-outline"></i> مراسلة البريد
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (moduleEnabled('ads')): ?>
                    <?php
                    $ads_array = ["ads_all", "ads_new", "ads_edit"];
                    ?>
                    <li class="menu-item <?php if (in_array(setTitle(escUrl($_SERVER['PHP_SELF'])), $ads_array)) echo "active"; ?>">
                        <a href="ads" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-bullhorn-outline"></i> الإعلانات
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (moduleEnabled('matches') or moduleEnabled('standings') or moduleEnabled('videos')): ?>
                    <li class="menu-header fw-medium mt-4">
                        <span class="menu-header-text">الرياضة</span>
                    </li>
                    <?php endif; ?>

                    <?php if (moduleEnabled('matches')): ?>
                    <?php
                    $matches_array = ["matches_all", "matches_new", "matches_edit"];
                    ?>
                    <li class="menu-item <?php if (in_array(setTitle(escUrl($_SERVER['PHP_SELF'])), $matches_array)) echo "active"; ?>">
                        <a href="matches" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-soccer"></i> جدول المباريات
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (moduleEnabled('standings')): ?>
                    <?php
                    $standings_array = ["standings_all", "standings_new", "standings_edit"];
                    ?>
                    <li class="menu-item <?php if (in_array(setTitle(escUrl($_SERVER['PHP_SELF'])), $standings_array)) echo "active"; ?>">
                        <a href="standings" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-podium-gold"></i> جدول الترتيب
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (moduleEnabled('videos')): ?>
                    <?php
                    $videos_array = ["videos_all", "videos_new", "videos_edit"];
                    ?>
                    <li class="menu-item <?php if (in_array(setTitle(escUrl($_SERVER['PHP_SELF'])), $videos_array)) echo "active"; ?>">
                        <a href="videos" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-youtube"></i> الفيديوهات
                        </a>
                    </li>
                    <?php endif; ?>

                    <li class="menu-header fw-medium mt-4">
                        <span class="menu-header-text">الإعدادات</span>
                    </li>

                    <li class="menu-item <?php if (setTitle(escUrl($_SERVER['PHP_SELF'])) == "settings_settings") echo "active"; ?>">
                        <a href="settings" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-cog"></i>اعدادات الموقع</a>
                    </li>
                    <li class="menu-item <?php if (setTitle(escUrl($_SERVER['PHP_SELF'])) == "abma_templates") echo "active"; ?>">
                        <a href="templates" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-panorama-variant-outline"></i>القوالب</a>
                    </li>
                    <li class="menu-item <?php if (setTitle(escUrl($_SERVER['PHP_SELF'])) == "addons_index") echo "active"; ?>">
                        <a href="addons" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-puzzle-outline"></i>الإضافات</a>
                    </li>
                    <li class="menu-item <?php if (setTitle(escUrl($_SERVER['PHP_SELF'])) == "theme-editor_index") echo "active"; ?>">
                        <a href="theme-editor" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-code-tags"></i>تحرير القالب</a>
                    </li>
                    <li class="menu-item <?php if (setTitle(escUrl($_SERVER['PHP_SELF'])) == "home_index") echo "active"; ?>">
                        <a href="home" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-pencil"></i>ترتيب أقسام الرئيسية</a>
                    </li>
                    <li class="menu-item <?php if (setTitle(escUrl($_SERVER['PHP_SELF'])) == "abma_developers") echo "active"; ?>">
                        <a href="developers" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-code-braces"></i>دليل المصممين</a>
                    </li>
                    <li class="menu-item <?php if (setTitle(escUrl($_SERVER['PHP_SELF'])) == "abma_wizard") echo "active"; ?>">
                        <a href="wizard" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-magic-staff"></i>معالج نوع النشاط</a>
                    </li>

                    <li class="menu-header fw-medium mt-4">
                        <span class="menu-header-text">النظام</span>
                    </li>
                    <?php
                    $users_array = ["users_all", "users_edit", "users_new"];
                    ?>
                    <li class="menu-item <?php if (in_array(setTitle(escUrl($_SERVER['PHP_SELF'])), $users_array)) echo "active"; ?>">
                        <a href="users" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-account-key-outline"></i> المستخدمون</a>
                    </li>
                    <li class="menu-item <?php if (setTitle(escUrl($_SERVER['PHP_SELF'])) == "logs_all") echo "active"; ?>">
                        <a href="logs" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-history"></i> سجل النشاط</a>
                    </li>
                    <li class="menu-item <?php if (setTitle(escUrl($_SERVER['PHP_SELF'])) == "backup_index") echo "active"; ?>">
                        <a href="backup" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-cloud-download-outline"></i> نسخة احتياطية</a>
                    </li>
                    <li class="menu-item <?php if (setTitle(escUrl($_SERVER['PHP_SELF'])) == "updates_index") echo "active"; ?>">
                        <a href="updates" class="menu-link">
                            <i class="menu-icon tf-icons mdi mdi-update"></i> الإصدار والتحديثات
                            <?php
                            // شارة إشعار خفيفة (بدون أي اتصال شبكة هنا — قراءة من settings
                            // المخزَّنة مسبقاً فقط عبر آخر فحص تلقائي/يدوي، راجع includes/updater.php)
                            if (function_exists('updaterAvailableInfo')) {
                                $updInfo = updaterAvailableInfo();
                                if (!empty($updInfo['available'])) {
                                    echo '<span class="badge bg-success rounded-pill ms-1">جديد</span>';
                                }
                            }
                            ?>
                        </a>
                    </li>
                    <?php endif; ?>

                </ul>
            </aside>
            <!-- / Menu -->

            <!-- Layout container -->
            <div class="layout-page">
                <!-- Navbar -->

                <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                            <i class="mdi mdi-menu mdi-24px"></i>
                        </a>
                    </div>

                    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">

                        <ul class="navbar-nav flex-row align-items-center ms-auto">

                            <!-- Style Switcher -->
                            <li class="nav-item me-1">
                                <a id="theme-toggle" class="nav-link btn btn-text-white waves-light waves-effect waves-light" href="javascript:void(0);">
                                    <i id="theme-icon" class="mdi mdi-theme-light-dark"></i>
                                </a>
                            </li>
                            <!-- / Style Switcher-->

                            <!-- User -->
                            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <div class="avatar avatar-online">
                                        <img src="assets/img/man.png" alt class="w-px-40 h-auto rounded-circle" />
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">

                                    <li>
                                        <a class="dropdown-item" href="account">
                                            <i class="mdi mdi-account-outline me-2"></i>
                                            <span class="align-middle">حسابي</span>
                                        </a>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider"></div>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="auth/logout" target="">
                                            <i class="mdi mdi-logout me-2"></i>
                                            <span class="align-middle">تسجيل الخروج</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <!--/ User -->
                        </ul>
                    </div>

                </nav>

                <!-- / Navbar -->
                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <script>
                            var $html = $("html");
                            var $themeIcon = $("#theme-icon");

                            // استرجاع الإعدادات المخزنة عند تحميل الصفحة
                            var savedTheme = localStorage.getItem("theme") || "light";
                            applyTheme(savedTheme);

                            $("#theme-toggle").on("click", function() {
                                var newTheme = $html.hasClass("light-style") ? "dark" : "light";
                                localStorage.setItem("theme", newTheme); // حفظ الإعداد في localStorage
                                applyTheme(newTheme);
                            });

                            function applyTheme(theme) {
                                if (theme === "light") {
                                    $html.attr("class", "light-style layout-navbar-fixed layout-menu-fixed layout-compact")
                                        .attr("data-theme", "theme-default")
                                        .attr("data-assets-path", "assets/");

                                    $(".template-customizer-core-css").attr("href", "assets/vendor/css/rtl/core.css");
                                    $(".template-customizer-theme-css").attr("href", "assets/vendor/css/rtl/theme-semi-dark.css");

                                    $themeIcon.attr("class", "mdi mdi-brightness-5"); // تغيير الأيقونة إلى الشمس
                                } else {
                                    $html.attr("class", "layout-navbar-fixed layout-compact dark-style layout-menu-fixed")
                                        .attr("data-theme", "theme-default")
                                        .attr("data-assets-path", "assets/")
                                        .attr("data-template", "vertical-menu-template")
                                        .attr("data-style", "dark");

                                    $(".template-customizer-core-css").attr("href", "assets/vendor/css/rtl/core-dark.css");
                                    $(".template-customizer-theme-css").attr("href", "assets/vendor/css/rtl/theme-semi-dark-dark.css");

                                    $themeIcon.attr("class", "mdi mdi-brightness-4"); // تغيير الأيقونة إلى القمر
                                }
                            }
                        </script>