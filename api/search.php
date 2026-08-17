<?php
/**
 * ملاحظة: هذا الملف كان بقايا من قالب متجر إلكتروني قديم (يعتمد على جدول
 * "products" غير الموجود في هذا المشروع)، وغير مستخدم فعلياً من أي واجهة
 * حالياً. تم تفريغه لمنع أي خطأ محتمل عند استدعائه مباشرة.
 * البحث الفعلي للموقع موجود في: /search.php (يعتمد على جدول blogs).
 */

http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['status' => false, 'message' => 'غير متاح']);
