<title>مكتبة الوسائط</title>
<h4 class="py-3 mb-3"><span class="text-muted fw-light">المحتوى /</span> مكتبة الوسائط</h4>

<div class="alert alert-info">
    مكتبة موحّدة لكل الملفات المرفوعة (صور، وأيضاً قوالب HTML ومستندات ومرفقات)، يمكنك رفع ملفات جديدة هنا واستخدام زر "اختر من المكتبة" داخل نماذج المقالات/الخدمات/الأعمال ووحدة مراسلة البريد وغيرها لإعادة استخدام أي ملف دون رفعه من جديد.
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-center mb-3">
            <div class="col-md-4">
                <input type="file" id="mediaUploadInput" class="form-control" multiple>
            </div>
            <div class="col-md-4">
                <input type="text" id="mediaSearch" class="form-control" placeholder="بحث باسم الملف...">
            </div>
        </div>
        <div id="mediaGrid" class="row g-3"></div>
        <div id="mediaPagination" class="mt-3"></div>
    </div>
</div>

<script src="js/media-library.js"></script>
