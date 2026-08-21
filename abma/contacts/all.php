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
<title>رسائل التواصل</title>
<h4 class="py-3 mb-3"><span class="text-muted fw-light">التواصل /</span> الرسائل</h4>

<div class="card mb-3">
  <div class="card-body">
    <form id="toolbar" class="row g-2 align-items-end">
      <div class="col-md-4">
        <div class="form-floating">
          <input name="q" class="form-control" placeholder="بحث...">
          <label>بحث...</label>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-floating">
          <select name="sort" class="form-select">
            <option value="id">id</option>
            <option value="first_name">الاسم</option>
            <option value="email">البريد الإلكتروني</option>
            <option value="phone">الجوال</option>
            <option value="seen">الحالة</option>
            <option value="date">التاريخ</option>
          </select>
          <label>ترتيب حسب</label>
        </div>
      </div>
      <div class="col-md-2">
        <div class="form-floating">
          <select name="dir" class="form-select">
            <option value="desc">تنازلي</option>
            <option value="asc">تصاعدي</option>
          </select>
          <label>الاتجاه</label>
        </div>
      </div>
      <div class="col-md-2">
        <div class="form-floating">
          <select name="per" class="form-select">
            <option>10</option>
            <option selected>20</option>
            <option>50</option>
            <option>100</option>
          </select>
          <label>لكل صفحة</label>
        </div>
      </div>
      <div class="col-md-1 d-grid">
        <button class="btn btn-primary" type="submit">تطبيق</button>
      </div>
    </form>
  </div>
</div>

<div class="card mt-2">
  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:70px">#</th>
          <th>الاسم</th>
          <th>البريد الإلكتروني</th>
          <th>الجوال</th>
          <th>الحالة</th>
          <th>الرسالة</th>
          <th>التاريخ</th>
          <th style="width:120px">إجراء</th>
        </tr>
      </thead>
      <tbody id="rowsBody"></tbody>
    </table>
  </div>
  <div class="card-footer d-flex justify-content-between align-items-center">
    <div id="pagerInfo" class="small text-muted"></div>
    <nav>
      <ul id="pager" class="pagination pagination-sm mb-0"></ul>
    </nav>
  </div>
</div>

<div class="modal fade" id="replyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <!-- الفورم يلف الهيدر/الجسم/الفوتر، لذا لازم يكون هو نفسه flex column بدل
           block عادي، وإلا لن يلتزم بارتفاع modal-content فينكسر تمرير modal-body
           ويختفي زر الإرسال أسفل الشاشة -->
      <form id="replyForm" class="d-flex flex-column" style="min-height:0;">
        <div class="modal-header">
          <h5 class="modal-title">الرد على الرسالة</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" style="overflow-y:auto; flex:1 1 auto; min-height:0;">
          <input type="hidden" id="replyId" name="id">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="form-floating">
                <input type="text" id="replyName" class="form-control" placeholder="الاسم" readonly>
                <label>الاسم</label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-floating">
                <input type="text" id="replyEmail" class="form-control" placeholder="البريد الإلكتروني" readonly>
                <label>البريد الإلكتروني</label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-floating">
                <input type="text" id="replyPhone" class="form-control" placeholder="الجوال" readonly>
                <label>الجوال</label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-floating">
                <input type="text" id="replyDate" class="form-control" placeholder="التاريخ" readonly>
                <label>التاريخ</label>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">نص الرسالة</label>
              <textarea id="replyOriginal" class="form-control" rows="4" readonly></textarea>
            </div>
            <div class="col-12">
              <div class="form-floating">
                <input type="text" id="replySubject" class="form-control" placeholder="عنوان الرسالة" required>
                <label>عنوان الرد</label>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">الرد</label>
              <textarea id="replyBody" class="form-control" rows="6" required></textarea>
              <div class="form-text">سيتم الإرسال عبر إعدادات SMTP الحالية.</div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
          <button type="submit" id="replySend" class="btn btn-primary">إرسال الرد</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="js/contacts.js"></script>
