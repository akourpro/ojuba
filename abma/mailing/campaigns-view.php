<?php requireOwner(); mailingRequireModule(); ?>
<?php
$id = numer($_GET['id'] ?? 0);
dbSelect("email_campaigns", "*", "WHERE id = ? LIMIT 1", [$id]);
if ($countrows === 0) {
  sweet("error", "خطأ", "الحملة غير موجودة", "mailing/campaigns");
  exit;
}
$campaign = $rows[0];

// توافقاً مع حملات أُنشئت قبل نظام القوائم المتعددة ولم تُرحَّل بعد (نادراً ما
// يحدث لأن صفحة الترحيل mailing/migrate تتكفّل بهذا، لكن كإجراء احترازي إضافي)
if ((int)$campaign['list_id'] > 0) {
  dbSelect("email_campaign_lists", "id", "WHERE campaign_id = ? AND list_id = ? LIMIT 1", [$id, $campaign['list_id']]);
  if ($countrows === 0) {
    dbInsert("email_campaign_lists", "campaign_id, list_id, date", [$id, $campaign['list_id'], date('Y-m-d H:i:s')]);
  }
}

$attachments = @unserialize($campaign['attachments']);
if (!is_array($attachments)) $attachments = [];

// القوائم المضافة لهذه الحملة، مع إحصائيات كل قائمة
dbSelect(
  "email_campaign_lists cl JOIN email_lists el ON el.id = cl.list_id",
  "cl.list_id, el.name as list_name,
   (SELECT COUNT(*) FROM email_list_contacts c WHERE c.list_id = cl.list_id AND c.status = 1) as list_total,
   (SELECT COUNT(*) FROM email_campaign_recipients r WHERE r.campaign_id = cl.campaign_id AND r.list_id = cl.list_id) as added_count,
   (SELECT COUNT(*) FROM email_campaign_recipients r WHERE r.campaign_id = cl.campaign_id AND r.list_id = cl.list_id AND r.status = 'sent') as sent_count,
   (SELECT COUNT(*) FROM email_campaign_recipients r WHERE r.campaign_id = cl.campaign_id AND r.list_id = cl.list_id AND r.status = 'pending') as pending_count",
  "WHERE cl.campaign_id = ? ORDER BY el.name ASC",
  [$id]
);
$targetLists = $rows;

// القوائم المتاحة للإضافة (المفعّلة وغير المضافة بعد لهذه الحملة)
dbSelect(
  "email_lists el",
  "el.id, el.name",
  "WHERE el.status = 1 AND el.id NOT IN (SELECT list_id FROM email_campaign_lists WHERE campaign_id = ?) ORDER BY el.name ASC",
  [$id]
);
$availableLists = $rows;

// إجمالي المستلمين المحدَّدين لهذه الحملة عبر كل القوائم
dbSelect("email_campaign_recipients", "COUNT(*) as cnt", "WHERE campaign_id = ?", [$id]);
$totalRecipients = (int)($rows[0]['cnt'] ?? 0);
dbSelect("email_campaign_recipients", "COUNT(*) as cnt", "WHERE campaign_id = ? AND status = 'sent'", [$id]);
$sentRecipients = (int)($rows[0]['cnt'] ?? 0);

global $site;
$statusLabels = ['draft' => 'مسودة', 'sending' => 'جاري الإرسال', 'sent' => 'تم الإرسال'];
?>
<title>حملة: <?php echo htmlspecialchars($campaign['name'], ENT_QUOTES, 'UTF-8') ?></title>
<h4 class="py-3 mb-3"><span class="text-muted fw-light">مراسلة البريد / <a href="mailing/campaigns">الحملات البريدية</a> /</span> <?php echo htmlspecialchars($campaign['name'], ENT_QUOTES, 'UTF-8') ?></h4>

<div class="card mb-4">
  <div class="card-body">
    <div class="row g-3 mb-3">
      <div class="col-md-4"><strong>الموضوع:</strong> <?php echo htmlspecialchars($campaign['subject'], ENT_QUOTES, 'UTF-8') ?></div>
      <div class="col-md-4"><strong>الحالة:</strong> <span id="campaignStatusLabel"><?php echo $statusLabels[$campaign['status']] ?? $campaign['status'] ?></span></div>
      <div class="col-md-4"><strong>القالب:</strong> <a href="<?php echo $site['site_url'] . htmlspecialchars($campaign['template'], ENT_QUOTES, 'UTF-8') ?>" target="_blank">معاينة القالب <i class="mdi mdi-open-in-new"></i></a></div>
      <div class="col-md-12">
        <strong>المرفقات:</strong>
        <?php if (empty($attachments)): ?>
          <span class="text-muted">لا توجد مرفقات</span>
        <?php else: ?>
          <?php foreach ($attachments as $att): ?>
            <?php
            $attPath = is_array($att) ? ($att['path'] ?? '') : $att;
            $attName = is_array($att) ? ($att['name'] ?? basename($attPath)) : basename($att);
            ?>
            <a href="<?php echo $site['site_url'] . htmlspecialchars($attPath, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="badge bg-label-secondary me-1"><i class="mdi mdi-paperclip"></i> <?php echo htmlspecialchars($attName, ENT_QUOTES, 'UTF-8') ?></a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="card mb-4">
  <h5 class="card-header d-flex justify-content-between align-items-center">
    القوائم المستهدفة والمستلمون
  </h5>
  <div class="card-body">
    <?php if (!empty($availableLists)): ?>
    <form id="addListForm" class="row g-2 align-items-end mb-4">
      <input type="hidden" name="campaign_id" value="<?php echo $id ?>">
      <div class="col-md-6">
        <div class="form-floating">
          <select class="form-select" name="list_id" required>
            <option value="">اختر قائمة لإضافتها للحملة</option>
            <?php foreach ($availableLists as $l): ?>
              <option value="<?php echo $l['id'] ?>"><?php echo htmlspecialchars($l['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
          <label>اختر قائمة لإضافتها للحملة</label>
        </div>
      </div>
      <div class="col-md-3 d-grid">
        <button type="submit" class="btn btn-secondary"><i class="mdi mdi-plus"></i> إضافة القائمة للحملة</button>
      </div>
    </form>
    <?php else: ?>
      <p class="text-muted small mb-4">تمت إضافة جميع القوائم المفعّلة المتاحة لهذه الحملة.</p>
    <?php endif; ?>

    <?php if (empty($targetLists)): ?>
      <div class="text-center text-muted py-4">لم تتم إضافة أي قائمة بعد إلى هذه الحملة. أضف قائمة من الأعلى للبدء.</div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead class="table-light">
          <tr>
            <th>القائمة</th>
            <th>إجمالي جهات الاتصال بالقائمة</th>
            <th>المضاف كمستلمين للحملة</th>
            <th>تم الإرسال له</th>
            <th>متبقٍ غير مُرسَل</th>
            <th style="width:280px">إجراء</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($targetLists as $l): ?>
          <?php $notAddedYet = (int)$l['list_total'] - (int)$l['added_count']; ?>
          <tr>
            <td><?php echo htmlspecialchars($l['list_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?php echo (int)$l['list_total'] ?></td>
            <td><?php echo (int)$l['added_count'] ?></td>
            <td><span class="badge bg-label-success"><?php echo (int)$l['sent_count'] ?></span></td>
            <td><span class="badge bg-label-warning"><?php echo (int)$l['pending_count'] ?></span></td>
            <td>
              <button type="button" class="btn btn-sm btn-primary pick-recipients-btn" data-list-id="<?php echo $l['list_id'] ?>" data-list-name="<?php echo htmlspecialchars($l['list_name'], ENT_QUOTES, 'UTF-8') ?>">
                <i class="mdi mdi-account-check-outline"></i> اختيار مستلمين
                <?php if ($notAddedYet > 0): ?><span class="badge bg-white text-primary ms-1"><?php echo $notAddedYet ?></span><?php endif; ?>
              </button>
              <span data-list-id="<?php echo $l['list_id'] ?>" data-name="<?php echo htmlspecialchars($l['list_name'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-danger remove-campaign-list ms-1" title="إزالة القائمة من الحملة (لا يحذف من استلم رسالة فعلاً)">
                <i class="mdi mdi-close"></i>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="text-muted small mb-0">عند اختيار جزء فقط من مستلمي قائمة، يمكنك لاحقاً العودة و"اختيار مستلمين" لإضافة الباقي — لن يُعاد الإرسال لمن سبقت إضافته أو الإرسال له.</p>
    <?php endif; ?>
  </div>
</div>

<div class="card mb-4">
  <div class="card-body">
    <div class="mb-2 d-flex justify-content-between">
      <strong>تقدّم الإرسال</strong>
      <span id="progressText"><?php echo $sentRecipients ?> / <?php echo $totalRecipients ?></span>
    </div>
    <div class="progress mb-3" style="height:22px">
      <div id="sendProgressBar" class="progress-bar progress-bar-striped" role="progressbar" style="width:<?php echo $totalRecipients > 0 ? round(($sentRecipients / $totalRecipients) * 100) : 0 ?>%"></div>
    </div>

    <button type="button" id="startSendBtn" class="btn btn-primary" data-campaign-id="<?php echo $campaign['id'] ?>" <?php if ($totalRecipients === 0 || $sentRecipients >= $totalRecipients) echo 'disabled'; ?>>
      <i class="mdi mdi-email-fast-outline"></i>
      <?php
        if ($totalRecipients === 0) {
          echo 'أضف مستلمين أولاً قبل الإرسال';
        } elseif ($sentRecipients >= $totalRecipients) {
          echo 'تم إرسال جميع المستلمين المضافين حالياً';
        } elseif ($sentRecipients > 0) {
          echo 'استئناف الإرسال للمستلمين الجدد';
        } else {
          echo 'بدء الإرسال';
        }
      ?>
    </button>
    <div id="sendLog" class="mt-3 small text-muted"></div>
  </div>
</div>

<!-- مودال اختيار المستلمين من قائمة معيّنة -->
<div class="modal fade" id="pickRecipientsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">اختيار مستلمين من: <span id="pickListName"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="pickLoading" class="text-center text-muted py-4">جاري التحميل...</div>
        <div id="pickEmptyState" class="text-center text-muted py-4" style="display:none">
          تمت إضافة جميع جهات الاتصال المتاحة من هذه القائمة كمستلمين لهذه الحملة بالفعل.
        </div>
        <div id="pickContent" style="display:none">
          <div class="form-check mb-2 pb-2 border-bottom d-flex justify-content-between align-items-center">
            <span>
              <input class="form-check-input" type="checkbox" id="pickSelectAll" checked>
              <label class="form-check-label fw-bold" for="pickSelectAll">تحديد الكل (الوضع الافتراضي)</label>
            </span>
            <span class="badge bg-label-primary" id="pickSelectedCount">تم تحديد 0</span>
          </div>
          <p class="text-muted small mb-2">نصيحة: اضغط على مربع، ثم اضغط مع الاستمرار على <kbd>Shift</kbd> وانقر مربعاً آخر لتحديد كل ما بينهما دفعة واحدة.</p>
          <div id="pickContactsList" style="max-height:360px; overflow-y:auto"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
        <button type="button" id="pickSubmitBtn" class="btn btn-primary">إضافة المحدد كمستلمين للحملة (<span id="pickSubmitCount">0</span>)</button>
      </div>
    </div>
  </div>
</div>

<script src="js/mailing.js"></script>
<script src="js/mailing-campaign-recipients.js"></script>
<script src="js/mailing-send.js"></script>
