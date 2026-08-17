<?php requireOwner(); mailingRequireModule(); ?>
<title>الحملات البريدية</title>
<h4 class="py-3 mb-3"><span class="text-muted fw-light">مراسلة البريد / <a href="mailing/lists">القوائم البريدية</a> /</span> الحملات البريدية</h4>

<div class="row mb-3">
  <div class="col-sm">
    <a href="mailing/campaigns/new" class="btn btn-secondary waves-effect waves-light"><i class="mdi mdi-plus"></i> إنشاء حملة جديدة</a>
  </div>
</div>

<div class="card mt-2">
  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle orders_table">
      <thead class="table-light">
        <tr>
          <th style="width:50px">#</th>
          <th>اسم الحملة</th>
          <th>القوائم المستهدفة</th>
          <th>الموضوع</th>
          <th>الحالة</th>
          <th>التقدّم</th>
          <th style="width:150px">إجراء</th>
        </tr>
      </thead>
      <tbody>
        <?php
        dbSelect(
          "email_campaigns c",
          "c.id, c.name, c.subject, c.status, c.date,
           (SELECT COUNT(*) FROM email_campaign_lists cl WHERE cl.campaign_id = c.id) as lists_count,
           (SELECT COUNT(*) FROM email_campaign_recipients r WHERE r.campaign_id = c.id) as total_recipients,
           (SELECT COUNT(*) FROM email_campaign_recipients r WHERE r.campaign_id = c.id AND r.status = 'sent') as sent_recipients",
          "ORDER BY c.id DESC"
        );
        $statusLabels = ['draft' => 'مسودة', 'sending' => 'جاري الإرسال', 'sent' => 'تم الإرسال'];
        $statusClass = ['draft' => 'secondary', 'sending' => 'warning', 'sent' => 'success'];
        if ($countrows >= 1) {
          $i = 1;
          foreach ($rows as $row) {
            $label = $statusLabels[$row['status']] ?? $row['status'];
            $cls = $statusClass[$row['status']] ?? 'secondary';
            $total = (int)$row['total_recipients'];
            $sent = (int)$row['sent_recipients'];
            $progress = $total > 0 ? round(($sent / $total) * 100) : 0;
            $listsLabel = (int)$row['lists_count'] > 0 ? ((int)$row['lists_count'] . ' قائمة') : '<span class="text-muted">لم تُضَف قوائم بعد</span>';
            echo '
              <tr>
                  <td>' . $i++ . '</td>
                  <td>' . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . '</td>
                  <td>' . $listsLabel . '</td>
                  <td>' . htmlspecialchars($row['subject'], ENT_QUOTES, 'UTF-8') . '</td>
                  <td><span class="badge bg-label-' . $cls . '">' . $label . '</span></td>
                  <td>' . $sent . ' / ' . $total . ' (' . $progress . '%)</td>
                  <td>
                    <div class="dropdown">
                      <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">إجراء</button>
                        <div class="dropdown-menu">
                          <a href="mailing/campaigns/' . $row['id'] . '" class="dropdown-item text-primary"><i class="mdi mdi-eye-outline"></i> عرض / إرسال</a>
                          <span data-id="' . $row['id'] . '" data-name="' . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . '" data-action="delete_campaign" class="dropdown-item text-danger delete-campaign"><i class="mdi mdi-delete"></i> حذف</span>
                        </div>
                  </td>
              </tr>';
          }
        } else {
          echo '<tr><td colspan="7" class="text-center text-muted py-4">لا توجد حملات بعد</td></tr>';
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<script src="js/mailing.js"></script>
<script src="js/tables.js"></script>
