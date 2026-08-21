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
<?php requireOwner();
mailingRequireModule(); ?>
<?php
$q = safer($_GET['q'] ?? '');

// تعيين قائمة "الاشتراك العام" — القائمة التي يُضاف إليها كل من يشترك عبر
// نموذج "النشرة البريدية" بالموقع العام (api/subscribe.php). إجراء غير هدّام
// وقابل للتغيير في أي وقت، بنفس أسلوب روابط الإجراءات السريعة الأخرى بلوحة
// التحكم (GET محمي بـ requireOwner() أعلاه).
if (isset($_GET['set_public_list'])) {
  $newPublicListId = (int) $_GET['set_public_list'];
  dbSelect("email_lists", "id", "WHERE id = ? LIMIT 1", [$newPublicListId]);
  if ($countrows === 1) {
    saveSetting('newsletter_list_id', $newPublicListId);
    sweet("success", "تم", "تم تعيين هذه القائمة كقائمة الاشتراك العام لنموذج النشرة البريدية بالموقع", "mailing/lists");
    exit;
  }
}
dbSelect("settings", "value", "WHERE name = 'newsletter_list_id' LIMIT 1");
$publicListId = $countrows ? (int) $rows[0]['value'] : 0;
?>
<title>القوائم البريدية</title>
<h4 class="py-3 mb-3"><span class="text-muted fw-light">مراسلة البريد /</span> القوائم البريدية</h4>

<div class="row mb-3">
  <div class="col-sm d-flex gap-2">
    <a href="mailing/lists/new" class="btn btn-secondary waves-effect waves-light"><i class="mdi mdi-plus"></i> إضافة قائمة جديدة</a>
    <a href="mailing/campaigns" class="btn btn-outline-primary waves-effect waves-light"><i class="mdi mdi-email-fast-outline"></i> الحملات البريدية</a>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <div class="col-md-4">
        <div class="form-floating">
          <input type="text" name="q" class="form-control" placeholder="ابحث بالبريد الإلكتروني في جميع القوائم..." value="<?php echo safer($q) ?>">
          <label>ابحث بالبريد الإلكتروني في جميع القوائم...</label>
        </div>
      </div>
      <div class="col-md-2 d-grid">
        <button class="btn btn-primary" type="submit">بحث</button>
      </div>
      <?php if ($q !== ''): ?>
        <div class="col-md-2 d-grid">
          <a href="mailing/lists" class="btn btn-outline-secondary">إلغاء البحث</a>
        </div>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php if ($q !== ''): ?>
  <div class="card mt-2">
    <div class="card-header bg-light">نتائج البحث عن "<?php echo safer($q) ?>"</div>
    <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle orders_table">
        <thead class="table-light">
          <tr>
            <th style="width:70px">#</th>
            <th>البريد الإلكتروني</th>
            <th>القائمة</th>
            <th>الحالة</th>
            <th>التاريخ</th>
            <th style="width:140px">إجراء</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $pagination = null;
          $whereSql = "WHERE elc.email COLLATE utf8mb4_general_ci LIKE ?";
          $whereBinds = ["%" . $q . "%"];
          dbSelect("email_list_contacts elc JOIN email_lists el ON el.id = elc.list_id", "COUNT(*) as cnt", $whereSql, $whereBinds);
          $total = (int)($rows[0]['cnt'] ?? 0);
          $pagination = paginate($total, 25);
          dbSelect(
            "email_list_contacts elc JOIN email_lists el ON el.id = elc.list_id",
            "elc.id, elc.email, elc.status, elc.date, elc.list_id, el.name as list_name",
            $whereSql . " ORDER BY elc.id DESC LIMIT " . (int)$pagination['per_page'] . " OFFSET " . (int)$pagination['offset'],
            $whereBinds
          );
          if ($countrows >= 1) {
            $i = $pagination['offset'] + 1;
            foreach ($rows as $row) {
              $status_label = ($row['status'] == 1) ? 'مفعّلة' : 'موقوفة';
              echo '
              <tr>
                  <td>' . $i++ . '</td>
                  <td>' . safer($row['email']) . '</td>
                  <td><a href="mailing/lists/' . $row['list_id'] . '/contacts">' . safer($row['list_name']) . '</a></td>
                  <td>' . $status_label . '</td>
                  <td>' . safer($row['date']) . '</td>
                  <td>
                    <div class="dropdown">
                      <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">إجراء</button>
                        <div class="dropdown-menu">
                          <a href="mailing/lists/' . $row['list_id'] . '/contacts/' . $row['id'] . '/edit" class="dropdown-item text-warning"><i class="mdi mdi-pen"></i> تعديل</a>
                          <span data-id="' . $row['id'] . '" data-name="' . safer($row['email']) . '" data-action="delete_contact" class="dropdown-item text-danger delete-contact"><i class="mdi mdi-delete"></i> حذف</span>
                        </div>
                  </td>
              </tr>';
            }
          } else {
            echo '<tr><td colspan="6" class="text-center text-muted py-4">لا توجد نتائج مطابقة لبحثك</td></tr>';
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if ($pagination && $pagination['total_pages'] > 1): ?>
    <nav class="mt-3">
      <ul class="pagination justify-content-center">
        <?php $qParam = '&q=' . urlencode($q); ?>
        <?php if ($pagination['has_prev']): ?><li class="page-item"><a class="page-link" href="mailing/lists?page=<?php echo $pagination['prev_page'] . $qParam ?>">السابق</a></li><?php endif; ?>
        <?php foreach ($pagination['pages'] as $p): ?>
          <li class="page-item <?php if ($p == $pagination['page']) echo 'active'; ?>"><a class="page-link" href="mailing/lists?page=<?php echo $p . $qParam ?>"><?php echo $p ?></a></li>
        <?php endforeach; ?>
        <?php if ($pagination['has_next']): ?><li class="page-item"><a class="page-link" href="mailing/lists?page=<?php echo $pagination['next_page'] . $qParam ?>">التالي</a></li><?php endif; ?>
      </ul>
    </nav>
  <?php endif; ?>

<?php else: ?>

  <div class="card mt-2">
    <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle orders_table">
        <thead class="table-light">
          <tr>
            <th style="width:70px">#</th>
            <th>اسم القائمة</th>
            <th>عدد الحقول المخصّصة</th>
            <th>عدد جهات الاتصال</th>
            <th>الحالة</th>
            <th>اشتراك الموقع العام</th>
            <th style="width:160px">إجراء</th>
          </tr>
        </thead>
        <tbody>
          <?php
          dbSelect(
            "email_lists el",
            "el.id, el.name, el.fields, el.status, el.date, (SELECT COUNT(*) FROM email_list_contacts elc WHERE elc.list_id = el.id) as contacts_count",
            "ORDER BY el.id DESC"
          );
          if ($countrows >= 1) {
            $i = 1;
            foreach ($rows as $row) {
              $fields = @unserialize($row['fields']);
              $fieldsCount = is_array($fields) ? count($fields) : 0;
              $status_label = ($row['status'] == 1) ? 'مفعّلة' : 'موقوفة';
              if ((int)$row['id'] === $publicListId) {
                $publicCell = '<span class="badge bg-label-success"><i class="mdi mdi-check-circle-outline"></i> قائمة الاشتراك العام</span>';
              } else {
                $publicCell = '<a href="mailing/lists?set_public_list=' . $row['id'] . '" class="btn btn-sm btn-outline-secondary">تعيين كقائمة عامة</a>';
              }
              echo '
              <tr>
                  <td>' . $i++ . '</td>
                  <td>' . safer($row['name']) . '</td>
                  <td>' . $fieldsCount . '</td>
                  <td><a href="mailing/lists/' . $row['id'] . '/contacts">' . (int)$row['contacts_count'] . ' جهة اتصال</a></td>
                  <td>' . $status_label . '</td>
                  <td>' . $publicCell . '</td>
                  <td>
                    <div class="dropdown">
                      <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">إجراء</button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                          <a href="mailing/lists/' . $row['id'] . '/contacts" class="dropdown-item text-primary"><i class="mdi mdi-account-multiple-outline"></i> جهات الاتصال</a>
                          <a href="mailing/lists/' . $row['id'] . '/edit" class="dropdown-item text-warning edit"><i class="mdi mdi-pen"></i> تعديل</a>
                          <span data-id="' . $row['id'] . '" data-name="' . safer($row['name']) . '" data-action="delete_list" class="dropdown-item text-danger delete-list"><i class="mdi mdi-delete"></i> حذف</span>
                        </div>
                  </td>
              </tr>';
            }
          } else {
            echo '<tr><td colspan="7" class="text-center text-muted py-4">لا توجد قوائم بريدية بعد</td></tr>';
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>

<?php endif; ?>

<script src="js/mailing.js"></script>
<script src="js/tables.js"></script>