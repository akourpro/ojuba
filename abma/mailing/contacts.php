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
$listId = numer($_GET['list_id'] ?? 0);
dbSelect("email_lists", "*", "WHERE id = ? LIMIT 1", [$listId]);
if ($countrows === 0) {
  sweet("error", "خطأ", "القائمة غير موجودة", "mailing/lists");
  exit;
}
$list = $rows[0];
$fields = @unserialize($list['fields']);
if (!is_array($fields)) $fields = [];

$q = trim($_GET['q'] ?? '');
$qParam = $q !== '' ? '&q=' . urlencode($q) : '';
?>
<title>جهات اتصال: <?php echo htmlspecialchars($list['name'], ENT_QUOTES, 'UTF-8') ?></title>
<h4 class="py-3 mb-3">
  <span class="text-muted fw-light">مراسلة البريد / <a href="mailing/lists">القوائم البريدية</a> /</span>
  <?php echo htmlspecialchars($list['name'], ENT_QUOTES, 'UTF-8') ?>
</h4>

<div class="row mb-3">
  <div class="col-sm d-flex gap-2">
    <a href="mailing/lists/<?php echo $listId ?>/contacts/new" class="btn btn-secondary waves-effect waves-light"><i class="mdi mdi-plus"></i> إضافة جهة اتصال</a>
    <a href="mailing/lists/<?php echo $listId ?>/contacts/bulk" class="btn btn-primary waves-effect waves-light"><i class="mdi mdi-account-multiple-plus-outline"></i> إضافة بالجملة</a>
    <a href="mailing/lists/<?php echo $listId ?>/contacts/import" class="btn btn-outline-primary waves-effect waves-light"><i class="mdi mdi-file-upload-outline"></i> استيراد من CSV</a>
    <a href="api/mailing?action=export_contacts&list_id=<?php echo $listId ?><?php echo $q !== '' ? '&q=' . urlencode($q) : '' ?>" class="btn btn-outline-success waves-effect waves-light"><i class="mdi mdi-file-download-outline"></i> تصدير CSV<?php echo $q !== '' ? ' (نتائج البحث)' : '' ?></a>
    <a href="mailing/lists/<?php echo $listId ?>/edit" class="btn btn-outline-secondary waves-effect waves-light"><i class="mdi mdi-pen"></i> تعديل حقول القائمة</a>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <input type="hidden" name="list_id" value="<?php echo $listId ?>">
      <div class="col-md-4">
        <div class="form-floating">
          <input type="text" name="q" class="form-control" placeholder="ابحث بالبريد الإلكتروني..." value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>">
          <label>ابحث بالبريد الإلكتروني...</label>
        </div>
      </div>
      <div class="col-md-2 d-grid">
        <button class="btn btn-primary" type="submit">بحث</button>
      </div>
      <?php if ($q !== ''): ?>
        <div class="col-md-2 d-grid">
          <a href="mailing/lists/<?php echo $listId ?>/contacts" class="btn btn-outline-secondary">إلغاء البحث</a>
        </div>
      <?php endif; ?>
    </form>
  </div>
</div>

<div class="card mt-2">
  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle orders_table">
      <thead class="table-light">
        <tr>
          <th style="width:50px">#</th>
          <th>البريد الإلكتروني</th>
          <?php foreach ($fields as $f): ?>
            <th><?php echo htmlspecialchars($f['label'], ENT_QUOTES, 'UTF-8') ?></th>
          <?php endforeach; ?>
          <th>الحالة</th>
          <th style="width:120px">إجراء</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $pagination = null;
        $whereSql = "WHERE list_id = ?";
        $whereBinds = [$listId];
        if ($q !== '') {
          $whereSql .= " AND email COLLATE utf8mb4_general_ci LIKE ?";
          $whereBinds[] = "%" . $q . "%";
        }
        dbSelect("email_list_contacts", "COUNT(*) as cnt", $whereSql, $whereBinds);
        $total = (int)($rows[0]['cnt'] ?? 0);
        $pagination = paginate($total, 25);
        dbSelect(
          "email_list_contacts",
          "id, email, data, status, date",
          $whereSql . " ORDER BY id DESC LIMIT " . (int)$pagination['per_page'] . " OFFSET " . (int)$pagination['offset'],
          $whereBinds
        );
        if ($countrows >= 1) {
          $i = $pagination['offset'] + 1;
          foreach ($rows as $row) {
            $contactData = @unserialize($row['data']);
            if (!is_array($contactData)) $contactData = [];
            $status_label = ($row['status'] == 1) ? 'مفعّلة' : 'موقوفة';
            echo '<tr><td>' . $i++ . '</td><td>' . htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') . '</td>';
            foreach ($fields as $f) {
              $val = $contactData[$f['key']] ?? '';
              echo '<td>' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '</td>';
            }
            echo '<td>' . $status_label . '</td>
                  <td>
                    <div class="dropdown">
                      <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">إجراء</button>
                        <div class="dropdown-menu">
                          <a href="mailing/lists/' . $listId . '/contacts/' . $row['id'] . '/edit" class="dropdown-item text-warning"><i class="mdi mdi-pen"></i> تعديل</a>
                          <span data-id="' . $row['id'] . '" data-name="' . htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') . '" data-action="delete_contact" class="dropdown-item text-danger delete-contact"><i class="mdi mdi-delete"></i> حذف</span>
                        </div>
                  </td></tr>';
          }
        } else {
          $colspan = 4 + count($fields);
          $emptyMsg = $q !== '' ? 'لا توجد نتائج مطابقة لبحثك' : 'لا توجد جهات اتصال بعد';
          echo '<tr><td colspan="' . $colspan . '" class="text-center text-muted py-4">' . $emptyMsg . '</td></tr>';
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($pagination && $pagination['total_pages'] > 1): ?>
  <nav class="mt-3">
    <ul class="pagination justify-content-center">
      <?php if ($pagination['has_prev']): ?><li class="page-item"><a class="page-link" href="mailing/lists/<?php echo $listId ?>/contacts?page=<?php echo $pagination['prev_page'] . $qParam ?>">السابق</a></li><?php endif; ?>
      <?php foreach ($pagination['pages'] as $p): ?>
        <li class="page-item <?php if ($p == $pagination['page']) echo 'active'; ?>"><a class="page-link" href="mailing/lists/<?php echo $listId ?>/contacts?page=<?php echo $p . $qParam ?>"><?php echo $p ?></a></li>
      <?php endforeach; ?>
      <?php if ($pagination['has_next']): ?><li class="page-item"><a class="page-link" href="mailing/lists/<?php echo $listId ?>/contacts?page=<?php echo $pagination['next_page'] . $qParam ?>">التالي</a></li><?php endif; ?>
    </ul>
  </nav>
<?php endif; ?>

<script src="js/mailing.js"></script>