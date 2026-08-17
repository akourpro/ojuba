<?php requireOwner(); ?>
<?php
/**
 * ترحيل قاعدة بيانات لدعم التحكم بصفحة "/pricing" المستقلة من لوحة التحكم:
 * - pricing_page_settings: صف واحد (تفعيل/إيقاف الصفحة + عنوان ووصف الهيدر عربي/انجليزي)
 * - pricing_faqs: الأسئلة الشائعة الخاصة بصفحة الأسعار (منفصلة عن الأسئلة الشائعة العامة)
 * - pricing_compare_settings: صف واحد (عنوان/وصف قسم جدول المقارنة فقط — أعمدة الجدول نفسها
 *   أصبحت ديناميكية بالكامل وتُبنى تلقائياً من باقات جدول pricing الفعّالة، بدون تخزين ثابت)
 * - pricing_compare_features: صفوف جدول المقارنة (اسم الميزة فقط، القيم في جدول منفصل)
 * - pricing_compare_values: قيمة كل ميزة × باقة (feature_id + plan_id) — يسمح بأي عدد باقات
 *
 * آمن لإعادة التشغيل (CREATE TABLE IF NOT EXISTS + تحقق قبل الإدراج).
 */
global $con;

$queries = [
  "CREATE TABLE IF NOT EXISTS `pricing_page_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `enabled` tinyint(1) NOT NULL DEFAULT 1,
    `title` varchar(255) DEFAULT NULL,
    `title_en` varchar(255) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `description_en` text DEFAULT NULL,
    `seo_title` varchar(255) DEFAULT NULL,
    `seo_title_en` varchar(255) DEFAULT NULL,
    `seo_description` text DEFAULT NULL,
    `seo_description_en` text DEFAULT NULL,
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

  "CREATE TABLE IF NOT EXISTS `pricing_faqs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `question` varchar(500) DEFAULT NULL,
    `question_en` varchar(500) DEFAULT NULL,
    `answer` text DEFAULT NULL,
    `answer_en` text DEFAULT NULL,
    `status` varchar(20) NOT NULL DEFAULT 'active',
    `ordering` int(11) NOT NULL DEFAULT 0,
    `date` datetime DEFAULT NULL,
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

  "CREATE TABLE IF NOT EXISTS `pricing_compare_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `eyebrow` varchar(255) DEFAULT NULL,
    `eyebrow_en` varchar(255) DEFAULT NULL,
    `title` varchar(255) DEFAULT NULL,
    `title_en` varchar(255) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `description_en` text DEFAULT NULL,
    `col1` varchar(100) DEFAULT NULL,
    `col1_en` varchar(100) DEFAULT NULL,
    `col2` varchar(100) DEFAULT NULL,
    `col2_en` varchar(100) DEFAULT NULL,
    `col3` varchar(100) DEFAULT NULL,
    `col3_en` varchar(100) DEFAULT NULL,
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

  "CREATE TABLE IF NOT EXISTS `pricing_compare_features` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `feature` varchar(255) DEFAULT NULL,
    `feature_en` varchar(255) DEFAULT NULL,
    `val1` varchar(150) DEFAULT NULL,
    `val1_en` varchar(150) DEFAULT NULL,
    `val2` varchar(150) DEFAULT NULL,
    `val2_en` varchar(150) DEFAULT NULL,
    `val3` varchar(150) DEFAULT NULL,
    `val3_en` varchar(150) DEFAULT NULL,
    `status` varchar(20) NOT NULL DEFAULT 'active',
    `ordering` int(11) NOT NULL DEFAULT 0,
    `date` datetime DEFAULT NULL,
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

  "CREATE TABLE IF NOT EXISTS `pricing_compare_values` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `feature_id` int(11) NOT NULL,
    `plan_id` int(11) NOT NULL,
    `value` varchar(150) DEFAULT NULL,
    `value_en` varchar(150) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `feature_plan` (`feature_id`, `plan_id`)
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

// ترحيل توافقي: لو كان جدول pricing_page_settings موجوداً مسبقاً من نسخة سابقة (بدون
// حقول السيو الجديدة seo_title/seo_description)، أضفها الآن. يتم تجاهل خطأ "العمود موجود
// مسبقاً" بصمت لأن هذا متوقع في كل تشغيلة لاحقة للترحيل.
$alterQueries = [
  "ALTER TABLE `pricing_page_settings` ADD COLUMN `seo_title` varchar(255) DEFAULT NULL AFTER `description_en`",
  "ALTER TABLE `pricing_page_settings` ADD COLUMN `seo_title_en` varchar(255) DEFAULT NULL AFTER `seo_title`",
  "ALTER TABLE `pricing_page_settings` ADD COLUMN `seo_description` text DEFAULT NULL AFTER `seo_title_en`",
  "ALTER TABLE `pricing_page_settings` ADD COLUMN `seo_description_en` text DEFAULT NULL AFTER `seo_description`",
];
foreach ($alterQueries as $aq) {
  try {
    $con->exec($aq);
    $results[] = ['ok' => true, 'text' => 'تم إضافة حقل سيو جديد لجدول إعدادات صفحة الأسعار'];
  } catch (Exception $e) {
    if (stripos($e->getMessage(), 'duplicate column') === false) {
      $results[] = ['ok' => false, 'text' => 'خطأ عند إضافة حقول السيو: ' . $e->getMessage()];
    }
  }
}

// تعبئة أولية بصف الإعدادات (إن لم يكن موجوداً) بنفس النص الحالي المعروض فعلياً
// على الصفحة، حتى لا يتغيّر أي شيء ظاهرياً بعد الترحيل مباشرة
dbSelect("pricing_page_settings", "id", "LIMIT 1");
if ($countrows == 0) {
  dbInsert(
    "pricing_page_settings",
    "enabled, title, title_en, description, description_en, seo_title, seo_title_en, seo_description, seo_description_en",
    [
      1,
      "أسعار خطط WorkUp",
      "WorkUp Pricing Plans",
      "تسعير شفاف وواضح، شهرياً لكل موظف فعّال في النظام — بدون رسوم مخفية. اختر الباقة المناسبة لحجم فريقكم، أو تواصل معنا لعرض سعر مخصص للمؤسسات الكبيرة.",
      "Transparent and straightforward pricing, billed monthly per active employee in the system — with no hidden fees. Choose the plan that fits your team size, or contact us for a custom quote for large organizations.",
      "أسعار نظام الموارد البشرية والحضور",
      "HR System Pricing Plans",
      "أسعار نظام WorkUp لإدارة القوى العاملة بالريال السعودي (SAR)، شهرياً لكل موظف. قارن بين الباقات، واطلب عرض سعر مخصص.",
      "WorkUp workforce management system pricing in Saudi Riyal (SAR), billed monthly per employee. Compare the plans and request a custom quote.",
    ]
  );
  $results[] = ['ok' => true, 'text' => 'تم إنشاء صف إعدادات صفحة الأسعار الافتراضي'];
} else {
  // ترحيل توافقي: لو كان الصف موجوداً مسبقاً (من نسخة سابقة) وحقول السيو فارغة، عبّئها
  // بنفس نص السيو الحالي المستخدم فعلياً في القالب، حتى لا يتغيّر شيء ظاهرياً بعد الترحيل
  dbSelect("pricing_page_settings", "id, seo_title", "LIMIT 1");
  if ($countrows === 1 && empty($rows[0]['seo_title'])) {
    dbUpdate(
      "pricing_page_settings",
      "seo_title = ?, seo_title_en = ?, seo_description = ?, seo_description_en = ?",
      [
        "أسعار نظام الموارد البشرية والحضور",
        "HR System Pricing Plans",
        "أسعار نظام WorkUp لإدارة القوى العاملة بالريال السعودي (SAR)، شهرياً لكل موظف. قارن بين الباقات، واطلب عرض سعر مخصص.",
        "WorkUp workforce management system pricing in Saudi Riyal (SAR), billed monthly per employee. Compare the plans and request a custom quote.",
        $rows[0]['id'],
      ],
      "WHERE id = ? LIMIT 1"
    );
    $results[] = ['ok' => true, 'text' => 'تم تعبئة حقول السيو الافتراضية لصف الإعدادات الموجود مسبقاً'];
  }
}

// تعبئة أولية بالأسئلة الشائعة الحالية المُثبَّتة بالكود (إن لم توجد أي أسئلة بعد)
dbSelect("pricing_faqs", "id", "LIMIT 1");
if ($countrows == 0) {
  $seedFaqs = [
    ["هل السعر شهري أم سنوي؟", "Is the price monthly or annual?", "التسعير الظاهر أعلاه شهري لكل موظف فعّال في النظام. لخيارات الفوترة السنوية أو أي ترتيب مخصص، تواصلوا معنا مباشرة.", "The prices shown above are billed monthly per active employee. For annual billing or a custom arrangement, please contact us directly."],
    ["هل يمكن تغيير الباقة لاحقاً؟", "Can I change my plan later?", "نعم، يمكن الترقية أو تعديل الباقة حسب نمو عدد موظفيكم واحتياج فريقكم، بالتنسيق مع فريق الدعم.", "Yes, you can upgrade or adjust your plan as your team grows, in coordination with our support team."],
    ["هل يوجد فرق بين التثبيت على سيرفركم أو سيرفرنا؟", "Is there a difference between hosting on your server or ours?", "نعم، WorkUp يدعم التثبيت على سيرفر العميل الخاص لضمان سيطرة كاملة على البيانات، أو الاستضافة لدينا — التفاصيل والتسعير حسب الخيار عند التواصل.", "Yes, WorkUp supports installation on your own server for full data control, or hosting with us — pricing details depend on the option you choose when you contact us."],
    ["هل هناك حد أدنى لعدد الموظفين؟", "Is there a minimum number of employees?", "الباقة الأساسية مناسبة للفرق الصغيرة حتى 10 موظفين، وتوجد باقات أكبر للشركات المتوسطة والكبيرة حسب الحاجة.", "The Basic plan suits small teams of up to 10 employees, with larger plans available for medium and large companies as needed."],
    ["كيف أعرف السعر الدقيق لشركتي؟", "How do I get an exact price for my company?", "التسعير النهائي يعتمد على عدد الموظفين وعدد الفروع والخيارات المطلوبة. راسلونا عبر نموذج التواصل وسنرسل عرض سعر مفصّل.", "The final price depends on employee count, branch count, and the options you need. Contact us for a detailed quote."],
  ];
  $i = 1;
  foreach ($seedFaqs as $f) {
    dbInsert(
      "pricing_faqs",
      "question, question_en, answer, answer_en, status, ordering, date",
      [$f[0], $f[1], $f[2], $f[3], "active", $i, date("Y-m-d H:i:s")]
    );
    $i++;
  }
  $results[] = ['ok' => true, 'text' => 'تم إدراج ' . count($seedFaqs) . ' سؤال شائع افتراضي لصفحة الأسعار'];
}

// تعبئة أولية بإعدادات جدول المقارنة (إن لم تكن موجودة) — بدون أسماء أعمدة ثابتة،
// لأنها أصبحت تُبنى تلقائياً من باقات جدول pricing الفعّالة
dbSelect("pricing_compare_settings", "id", "LIMIT 1");
if ($countrows == 0) {
  dbInsert(
    "pricing_compare_settings",
    "eyebrow, eyebrow_en, title, title_en, description, description_en",
    [
      "مقارنة تفصيلية",
      "Detailed Comparison",
      "أي باقة تناسب فريقكم؟",
      "Which plan is right for your team?",
      "مقارنة سريعة لأبرز الفروقات بين الباقات.",
      "A quick comparison of the key differences between the plans.",
    ]
  );
  $results[] = ['ok' => true, 'text' => 'تم إنشاء صف إعدادات جدول المقارنة الافتراضي'];
}

// الباقات الفعّالة حالياً (بنفس ترتيب ظهورها في صفحة /pricing) — تُستخدم لتوزيع القيم
// الافتراضية على أعمدة القيم الديناميكية بدل الأعمدة الثلاثة الثابتة سابقاً
dbSelect("pricing", "id", "WHERE status = ? ORDER BY ordering ASC, id DESC", ["active"]);
$activePlanIds = ($countrows >= 1) ? array_column($rows, 'id') : [];

// تعبئة أولية بصفوف جدول المقارنة الحالية المُثبَّتة بالكود (إن لم توجد أي صفوف بعد)،
// وربط قيمها بأول 3 باقات فعّالة (أو أقل إن كان عددها أصغر)
dbSelect("pricing_compare_features", "id", "LIMIT 1");
if ($countrows == 0) {
  $seedCompare = [
    ["عدد الفروع", "Number of branches", "فرع واحد", "1 branch", "حتى 5 فروع", "Up to 5 branches", "غير محدود", "Unlimited"],
    ["عدد الموظفين", "Number of employees", "10", "10", "+10", "10+", "غير محدود", "Unlimited"],
    ["صلاحيات دقيقة لكل موظف ووحدة", "Granular permissions for each employee and unit", "✓", "✓", "✓", "✓", "✓", "✓"],
    ["تفويض لوحة مصغّرة لقادة الفرق", "Delegate a mini dashboard to team leaders", "✓", "✓", "✓", "✓", "✓", "✓"],
    ["تخصيص تصميم التقارير وملفات PDF", "Custom report and PDF design", "✓", "✓", "✓", "✓", "✓", "✓"],
    ["سجل تدقيق كامل لكل إجراء", "Complete audit log for every action", "✓", "✓", "✓", "✓", "✓", "✓"],
    ["قاعدة معرفة داخلية قابلة للبحث", "Searchable internal knowledge base", "✓", "✓", "✓", "✓", "✓", "✓"],
    ["تثبيت كامل على سيرفركم الخاص", "Full installation on your own server", "✕", "✕", "✓", "✓", "✓", "✓"],
    ["مستوى الدعم", "Support level", "خلال ايام العمل", "During business days", "خلال ايام العمل", "During business days", "دعم ذو أولوية", "Priority support"],
    ["استملاك النظام", "System ownership", "✕", "✕", "✕", "✕", "✓", "✓"],
  ];
  $i = 1;
  $insertedCount = 0;
  foreach ($seedCompare as $r) {
    $featureId = dbInsert(
      "pricing_compare_features",
      "feature, feature_en, status, ordering, date",
      [$r[0], $r[1], "active", $i, date("Y-m-d H:i:s")]
    );
    // r[2..7] = val1, val1_en, val2, val2_en, val3, val3_en — توزَّع على أول 3 باقات فعّالة بالترتيب
    for ($p = 0; $p < 3; $p++) {
      if (!isset($activePlanIds[$p])) {
        break;
      }
      dbInsert(
        "pricing_compare_values",
        "feature_id, plan_id, value, value_en",
        [$featureId, $activePlanIds[$p], $r[2 + $p * 2], $r[3 + $p * 2]]
      );
    }
    $i++;
    $insertedCount++;
  }
  $results[] = ['ok' => true, 'text' => 'تم إدراج ' . $insertedCount . ' صف افتراضي لجدول المقارنة، مع ربط قيمها بأول ' . min(3, count($activePlanIds)) . ' باقة فعّالة'];
}

// ترحيل توافقي: لو كانت pricing_compare_features تحتوي أعمدة val1/val2/val3 القديمة
// (من نسخة سابقة من هذه الميزة) ولم يتم بعد نقل قيمها لجدول pricing_compare_values
// الجديد، انقلها تلقائياً مرة واحدة بربطها بأول 3 باقات فعّالة بنفس الترتيب القديم.
try {
  dbSelect("pricing_compare_features", "id, val1, val1_en, val2, val2_en, val3, val3_en", "");
  if ($countrows >= 1 && !empty($activePlanIds)) {
    $migratedOldVals = 0;
    foreach ($rows as $fr) {
      $oldVals = [
        [$fr['val1'] ?? null, $fr['val1_en'] ?? null],
        [$fr['val2'] ?? null, $fr['val2_en'] ?? null],
        [$fr['val3'] ?? null, $fr['val3_en'] ?? null],
      ];
      for ($p = 0; $p < 3; $p++) {
        if (!isset($activePlanIds[$p]) || $oldVals[$p][0] === null) {
          continue;
        }
        dbSelect("pricing_compare_values", "id", "WHERE feature_id = ? AND plan_id = ? LIMIT 1", [$fr['id'], $activePlanIds[$p]]);
        if ($countrows == 0) {
          dbInsert(
            "pricing_compare_values",
            "feature_id, plan_id, value, value_en",
            [$fr['id'], $activePlanIds[$p], $oldVals[$p][0], $oldVals[$p][1]]
          );
          $migratedOldVals++;
        }
      }
    }
    if ($migratedOldVals > 0) {
      $results[] = ['ok' => true, 'text' => 'تم ترحيل ' . $migratedOldVals . ' قيمة من النسخة القديمة (val1/val2/val3) إلى جدول القيم الديناميكي الجديد'];
    }
  }
} catch (Exception $e) {
  // أعمدة val1/val2/val3 القديمة غير موجودة أصلاً (تركيب جديد) — لا حاجة لأي ترحيل
}
?>
<title>ترحيل صفحة الأسعار</title>
<h4 class="py-3 mb-3">ترحيل بيانات صفحة الأسعار</h4>

<div class="card">
  <div class="card-body">
    <ul class="mb-3">
      <?php foreach ($results as $r): ?>
        <li class="<?php echo $r['ok'] ? 'text-success' : 'text-danger' ?>"><?php echo htmlspecialchars($r['text'], ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ul>
    <a href="pricing" class="btn btn-primary">الذهاب لباقات الأسعار</a>
  </div>
</div>
