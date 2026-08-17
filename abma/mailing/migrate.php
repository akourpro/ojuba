<?php requireOwner(); ?>
<?php
/**
 * ترحيل قاعدة بيانات وحدة مراسلة البريد لدعم:
 * - عدة قوائم مستهدفة داخل الحملة الواحدة (email_campaign_lists)
 * - تحديد مستلمين بعينهم من كل قائمة مع تتبّع من استلم فعلاً حتى لا يُعاد
 *   الإرسال له عند إضافة/إرسال دفعة جديدة لاحقاً (email_campaign_recipients)
 * - عمود ip بجدول email_list_contacts لدعم الاشتراك العام من الموقع (نموذج
 *   "النشرة البريدية" → api/subscribe.php)
 *
 * آمن لإعادة التشغيل (CREATE TABLE IF NOT EXISTS / تجاهل أخطاء "موجود مسبقاً")
 * — يمكن رفعه وتشغيله على السيرفر الحقيقي بنفس الطريقة بعد رفع الملفات.
 */
global $con;

$queries = [
  "CREATE TABLE IF NOT EXISTS `email_campaign_lists` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `campaign_id` int(11) NOT NULL,
    `list_id` int(11) NOT NULL,
    `date` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_campaign_list` (`campaign_id`,`list_id`),
    KEY `campaign_id` (`campaign_id`),
    KEY `list_id` (`list_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

  "CREATE TABLE IF NOT EXISTS `email_campaign_recipients` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `campaign_id` int(11) NOT NULL,
    `list_id` int(11) NOT NULL,
    `contact_id` int(11) NOT NULL,
    `email` varchar(255) NOT NULL,
    `status` varchar(20) NOT NULL DEFAULT 'pending',
    `error` text DEFAULT NULL,
    `date` datetime DEFAULT NULL,
    `sent_date` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_campaign_email` (`campaign_id`,`email`),
    KEY `campaign_id_status` (`campaign_id`,`status`),
    KEY `list_id` (`list_id`),
    KEY `contact_id` (`contact_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",
];

$results = [];
foreach ($queries as $q) {
  try {
    $con->exec($q);
    $results[] = ['ok' => true, 'text' => 'تم تنفيذ الاستعلام بنجاح'];
  } catch (Exception $e) {
    $results[] = ['ok' => false, 'text' => 'خطأ: ' . $e->getMessage()];
  }
}

// ترحيل توافقي: عمود ip بجدول email_list_contacts، يُستخدم لتحديد معدّل
// الاشتراك العام من نموذج "النشرة البريدية" بالموقع (api/subscribe.php) لمنع
// إساءة الاستخدام، ولمعرفة مصدر كل مشترك. يُتجاهل خطأ "العمود/المفتاح موجود
// مسبقاً" بصمت لأنه متوقع في كل تشغيلة لاحقة لهذا الترحيل.
$alterQueries = [
  "ALTER TABLE `email_list_contacts` ADD COLUMN `ip` varchar(45) DEFAULT NULL AFTER `status`",
  "ALTER TABLE `email_list_contacts` ADD KEY `ip` (`ip`)",
];
foreach ($alterQueries as $aq) {
  try {
    $con->exec($aq);
    $results[] = ['ok' => true, 'text' => 'تم تجهيز عمود تتبّع مصدر المشترك (ip) بنجاح'];
  } catch (Exception $e) {
    if (stripos($e->getMessage(), 'duplicate column') === false && stripos($e->getMessage(), 'Duplicate key name') === false) {
      $results[] = ['ok' => false, 'text' => 'خطأ عند تجهيز عمود ip: ' . $e->getMessage()];
    }
  }
}

// ترحيل تلقائي للحملات القديمة (نمط "قائمة واحدة" السابق) إلى النظام الجديد،
// حتى لا تنكسر أي حملة أُنشئت قبل هذا التحديث
dbSelect("email_campaigns", "id, list_id", "WHERE list_id > 0");
$legacyCampaigns = $rows;
$migratedCount = 0;
foreach ($legacyCampaigns as $camp) {
  dbSelect("email_campaign_lists", "id", "WHERE campaign_id = ? AND list_id = ? LIMIT 1", [$camp['id'], $camp['list_id']]);
  if ($countrows > 0) continue; // مُرحّلة مسبقاً

  dbInsert("email_campaign_lists", "campaign_id, list_id, date", [$camp['id'], $camp['list_id'], date('Y-m-d H:i:s')]);

  // كل جهات اتصال القائمة تصبح مستلمين لهذه الحملة، مع الحفاظ على حالة
  // "تم الإرسال" لمن أُرسل له فعلاً سابقاً (من سجل email_campaign_logs) حتى لا
  // يُعاد إرسال الرسالة له من جديد
  dbSelect("email_list_contacts", "id, email", "WHERE list_id = ? AND status = 1", [$camp['list_id']]);
  foreach ($rows as $contact) {
    dbSelect("email_campaign_logs", "id", "WHERE campaign_id = ? AND contact_id = ? AND status = 'sent' LIMIT 1", [$camp['id'], $contact['id']]);
    $alreadySent = $countrows > 0;
    dbInsert(
      "email_campaign_recipients",
      "campaign_id, list_id, contact_id, email, status, date, sent_date",
      [$camp['id'], $camp['list_id'], $contact['id'], $contact['email'], $alreadySent ? 'sent' : 'pending', date('Y-m-d H:i:s'), $alreadySent ? date('Y-m-d H:i:s') : null]
    );
  }
  $migratedCount++;
}
?>
<title>ترحيل قاعدة بيانات المراسلة</title>
<h4 class="py-3 mb-3">ترحيل قاعدة بيانات المراسلة</h4>

<div class="card">
  <div class="card-body">
    <ul class="mb-3">
      <?php foreach ($results as $r): ?>
        <li class="<?php echo $r['ok'] ? 'text-success' : 'text-danger' ?>"><?php echo htmlspecialchars($r['text'], ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
      <li class="text-muted">تم ترحيل <?php echo $migratedCount ?> حملة قديمة (إن وُجدت) إلى نظام القوائم المتعددة.</li>
    </ul>
    <a href="mailing/campaigns" class="btn btn-primary">الذهاب للحملات البريدية</a>
  </div>
</div>
