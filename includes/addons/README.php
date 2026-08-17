<?php
/**
 * ضع هنا ملفات إضافات الطرف الثالث (extension/addon) — كل ملف PHP مباشر داخل
 * هذا المجلد (وليس بمجلدات فرعية) يُحمَّل تلقائياً عبر ojubaLoadAddons()
 * (includes/hooks.php) بمجرّد تحميل includes/functions.php بأي طلب — قبل أي
 * فرصة لتشغيل do_action()/apply_filters() الفعلية.
 *
 * لا تعدّل أي ملف نواة (root/*.php، includes/functions.php، abma/*) لإضافة
 * ميزتك — استخدم فقط add_action()/add_filter() هنا. هذا يضمن بقاء إضافتك
 * سليمة عبر أي تحديث تلقائي مستقبلي للسكربت (التحديث يستبدل ملفات النواة، ولا
 * يلمس هذا المجلد إطلاقاً لأنه ليس جزءاً من مستودع السكربت الرسمي).
 *
 * مثال:
 *
 *   add_action('ojuba_blog_saved', function ($blogId, $isNew) {
 *       // أرسل إشعاراً خارجياً عند نشر مقال جديد مثلاً
 *   });
 *
 *   add_filter('ojuba_render_vars', function ($vars, $template) {
 *       if ($template === 'home.twig') {
 *           $vars['my_custom_widget'] = '...';
 *       }
 *       return $vars;
 *   });
 *
 * راجع abma/developers.php قسم "13. نظام Hooks/Actions" للتوثيق الكامل.
 */
