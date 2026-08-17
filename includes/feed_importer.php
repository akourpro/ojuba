<?php

/**
 * محرّك وحدة "سحب المقالات" (Feed Import / RSS) — منطق الجلب/التحليل/الاستيراد
 * بالكامل، منفصل في ملف خاص (وليس داخل functions.php الضخم أصلاً) لأنه كتلة
 * منطق كبيرة ومستقلة. يُضمَّن فقط من نقاط الدخول التي تحتاجه فعلياً:
 * abma/feeds/all.php (زر "سحب الآن")، abma/api/feeds.php (سحب AJAX)،
 * api/feeds-cron.php (الجدولة التلقائية العامة).
 *
 * يتطلب أن يكون includes/functions.php قد حُمِّل مسبقاً (dbSelect/dbInsert/
 * dbUpdate/safer/genCode/compressUploadedImage/moduleEnabled/feedsTableExists
 * كلها مُعرَّفة هناك) وأن يكون HTMLPurifier محمَّلاً (مُحمَّل بالفعل من أعلى
 * functions.php).
 *
 * ==================== قرارات أمان مهمة ====================
 * 1. محتوى المقال (description) يُنقّى عبر purifyImportedHtml() أدناه (HTMLPurifier
 *    بدون ترميز htmlspecialchars لاحق — يبقى HTML خاماً كما يتوقعه القالب عبر
 *    |raw) قبل الحفظ، **خلافاً** لسياسة "المحتوى اليدوي بلوحة التحكم يُخزَّن
 *    خاماً بدون تنقية لأن صاحب الموقع نفسه المصدر الموثوق" — هنا المصدر موقع
 *    خارجي غير موثوق بالضرورة، فالتنقية إلزامية لمنع حقن سكربتات/أكواد ضارة.
 * 2. عنوان المقال (name) يمر عبر safer() تماماً كما في abma/blogs/new.php
 *    (تنقية + ترميز htmlspecialchars) — نفس اصطلاح الحقول النصية القصيرة.
 * 3. الصور تُحمَّل وتُخزَّن محلياً دائماً (وليس رابطاً خارجياً) — قرار المستخدم
 *    الصريح. لا رفع لملفات غير صور (نتحقق من Content-Type فعلياً من الاستجابة،
 *    وليس فقط امتداد الرابط) وبحد أقصى حجم معقول لمنع استنزاف المساحة.
 */

/**
 * التوكن السري المستخدم لحماية نقطة api/feeds-cron.php العامة من الاستدعاء
 * العشوائي (راجع abma/feeds/all.php لعرض رابط الجدولة الكامل به). يُولَّد مرة
 * واحدة تلقائياً (عشوائي 32 حرفاً) ويُحفظ بجدول settings عبر saveSetting() —
 * لا حاجة لإعداد يدوي من صاحب الموقع.
 */
function ensureFeedsCronToken()
{
    global $site;
    if (!empty($site['feeds_cron_token'])) {
        return $site['feeds_cron_token'];
    }
    $token = bin2hex(random_bytes(16));
    saveSetting('feeds_cron_token', $token);
    $site['feeds_cron_token'] = $token;
    return $token;
}

/**
 * تنقية HTML مقال مسحوب من مصدر خارجي — نفس فلسفة safer() لكن **بدون**
 * الترميز النهائي بـhtmlspecialchars، لأن الحقل يُخزَّن HTML خاماً (يُطبع عبر
 * |raw بالقالب) خلافاً لحقول مثل name/tags التي تمر عبر safer() العادية.
 */
function purifyImportedHtml($html)
{
    $config = HTMLPurifier_Config::createDefault();
    $config->set('HTML.Allowed', 'p,br,strong,b,em,i,u,s,h1,h2,h3,h4,h5,h6,ul,ol,li,a[href|title],img[src|alt|title],blockquote,table,thead,tbody,tr,td,th,figure,figcaption,span,div,pre,code');
    $config->set('AutoFormat.RemoveEmpty', true);
    $config->set('HTML.TargetBlank', true);
    $purifier = new HTMLPurifier($config);
    return $purifier->purify((string) $html);
}

/**
 * تطبيق "قواعد الاستبدال" على نص المقال (عنوان أو محتوى) — نص حر بصيغة سطر لكل
 * قاعدة: "الكلمة المطلوب استبدالها => الكلمة البديلة". أسطر بدون "=>" أو فارغة
 * تُتجاهَل بصمت. استبدال نصي مباشر (str_replace) حساس لحالة الأحرف، يطبَّق
 * بالترتيب المُدخَل (قاعدة لاحقة قد تُعدِّل نتيجة قاعدة سابقة).
 */
function applyFeedReplacements($text, $rulesRaw)
{
    $rulesRaw = (string) $rulesRaw;
    if (trim($rulesRaw) === '') {
        return $text;
    }
    $lines = preg_split('/\r\n|\r|\n/', $rulesRaw);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '=>') === false) {
            continue;
        }
        [$find, $replace] = array_map('trim', explode('=>', $line, 2));
        if ($find === '') {
            continue;
        }
        $text = str_replace($find, $replace, $text);
    }
    return $text;
}

/**
 * جلب محتوى رابط عن بعد عبر cURL بمهلة زمنية محدودة و User-Agent واضح — تُستخدم
 * لجلب ملف الـfeed نفسه وصور المقالات المُضمَّنة به. تُعيد مصفوفة
 * ['body' => string|null, 'content_type' => string|null, 'error' => string|null].
 */
function fetchRemoteUrl($url, $timeoutSeconds = 12, $maxBytes = 8 * 1024 * 1024)
{
    if (!preg_match('#^https?://#i', (string) $url)) {
        return ['body' => null, 'content_type' => null, 'error' => 'رابط غير صالح'];
    }
    if (!function_exists('curl_init')) {
        // بديل احتياطي عبر file_get_contents إن لم تكن إضافة cURL مُفعَّلة بالاستضافة
        $context = stream_context_create([
            'http' => ['timeout' => $timeoutSeconds, 'header' => "User-Agent: OjubaFeedImporter/1.0\r\n"],
            'https' => ['timeout' => $timeoutSeconds],
        ]);
        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            return ['body' => null, 'content_type' => null, 'error' => 'فشل الجلب (file_get_contents)'];
        }
        return ['body' => $body, 'content_type' => null, 'error' => null];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => $timeoutSeconds,
        CURLOPT_CONNECTTIMEOUT => min(8, $timeoutSeconds),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'OjubaFeedImporter/1.0 (+https://ojuba.sa)',
        CURLOPT_RANGE => '0-' . $maxBytes,
    ]);
    $body = curl_exec($ch);
    $err = curl_error($ch);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $err) {
        return ['body' => null, 'content_type' => null, 'error' => $err ?: 'فشل الجلب'];
    }
    if ($httpCode >= 400) {
        return ['body' => null, 'content_type' => $contentType, 'error' => 'رمز استجابة HTTP: ' . $httpCode];
    }
    return ['body' => $body, 'content_type' => $contentType, 'error' => null];
}

/**
 * تحليل ملف feed (RSS 2.0 أو Atom) وإرجاع مصفوفة عناصر موحَّدة الشكل:
 * [guid, link, title, content_html, image_url, categories (array)]
 * يدعم أهم الحقول الشائعة بكلا الصيغتين، بما فيها content:encoded (RSS) و
 * media:content/media:thumbnail (كلا الصيغتين) لاستخراج الصورة، مع احتياطي
 * استخراج أول <img> من نص المقال إن لم توجد صورة صريحة بالـfeed.
 */
function parseFeedItems($xmlBody, $maxItems = 20)
{
    if (empty($xmlBody)) {
        return [];
    }
    $prevSetting = libxml_use_internal_errors(true);
    $xml = simplexml_load_string($xmlBody);
    libxml_clear_errors();
    libxml_use_internal_errors($prevSetting);
    if ($xml === false) {
        return [];
    }

    $namespaces = [
        'content' => 'http://purl.org/rss/1.0/modules/content/',
        'media'   => 'http://search.yahoo.com/mrss/',
        'atom'    => 'http://www.w3.org/2005/Atom',
        'dc'      => 'http://purl.org/dc/elements/1.1/',
    ];

    $items = [];
    $rootName = $xml->getName();

    $nodes = [];
    if ($rootName === 'rss' && isset($xml->channel->item)) {
        $nodes = $xml->channel->item;
    } elseif ($rootName === 'feed' && isset($xml->entry)) {
        $nodes = $xml->entry;
    } elseif (isset($xml->item)) { // بعض صيغ RSS 1.0/RDF البسيطة
        $nodes = $xml->item;
    }

    $i = 0;
    foreach ($nodes as $node) {
        if ($i >= $maxItems) {
            break;
        }
        $i++;

        $title = trim((string) $node->title);

        // الرابط: RSS <link> نص مباشر، Atom <link href="..." rel="alternate"/>
        $link = trim((string) $node->link);
        if ($link === '' && isset($node->link['href'])) {
            $link = trim((string) $node->link['href']);
        }
        if ($link === '') {
            foreach ($node->link as $l) {
                $rel = (string) $l['rel'];
                if ($rel === '' || $rel === 'alternate') {
                    $link = trim((string) $l['href']);
                    break;
                }
            }
        }

        // المعرّف الفريد: <guid> (RSS) أو <id> (Atom)، وإلا الرابط نفسه
        $guid = trim((string) $node->guid);
        if ($guid === '') {
            $guid = trim((string) $node->id);
        }
        if ($guid === '') {
            $guid = $link;
        }

        // المحتوى الكامل: content:encoded (RSS) أولوية، ثم Atom <content>، ثم
        // <description>/<summary> كاحتياطي أخير
        $contentHtml = '';
        $contentNs = $node->children($namespaces['content']);
        if (isset($contentNs->encoded) && trim((string) $contentNs->encoded) !== '') {
            $contentHtml = (string) $contentNs->encoded;
        } elseif (isset($node->content) && trim((string) $node->content) !== '') {
            $contentHtml = (string) $node->content;
        } elseif (isset($node->description) && trim((string) $node->description) !== '') {
            $contentHtml = (string) $node->description;
        } elseif (isset($node->summary) && trim((string) $node->summary) !== '') {
            $contentHtml = (string) $node->summary;
        }

        // الصورة: enclosure (RSS)، ثم media:content/media:thumbnail، وإلا أول
        // <img> داخل نص المقال نفسه
        $image = '';
        if (isset($node->enclosure) && isset($node->enclosure['url'])) {
            $type = (string) $node->enclosure['type'];
            if ($type === '' || stripos($type, 'image') !== false) {
                $image = trim((string) $node->enclosure['url']);
            }
        }
        if ($image === '') {
            $mediaNs = $node->children($namespaces['media']);
            if (isset($mediaNs->content) && isset($mediaNs->content['url'])) {
                $image = trim((string) $mediaNs->content['url']);
            } elseif (isset($mediaNs->thumbnail) && isset($mediaNs->thumbnail['url'])) {
                $image = trim((string) $mediaNs->thumbnail['url']);
            }
        }
        if ($image === '' && $contentHtml !== '') {
            if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $contentHtml, $m)) {
                $image = trim($m[1]);
            }
        }

        // التصنيفات (categories) — تُستخدم كوسوم (tags) اختيارية
        $categories = [];
        if (isset($node->category)) {
            foreach ($node->category as $cat) {
                $catText = trim((string) $cat);
                if ($catText !== '') {
                    $categories[] = $catText;
                }
            }
        }

        if ($title === '' && $link === '') {
            continue; // عنصر فارغ تماماً، تجاهله
        }

        $items[] = [
            'guid'          => $guid !== '' ? $guid : md5($title . $link),
            'link'          => $link,
            'title'         => $title,
            'content_html'  => $contentHtml,
            'image_url'     => $image,
            'categories'    => $categories,
        ];
    }

    return $items;
}

/**
 * تحميل صورة عن بعد وحفظها محلياً بمجلد files/blogs (نفس مجلد صور المقالات
 * العادية) — **قرار صريح**: صور المقالات المسحوبة تُحمَّل وتُخزَّن محلياً دائماً
 * (وليس رابطاً خارجياً) حتى لا يعتمد ظهور صور الموقع على بقاء الموقع المصدر.
 * تتحقق من Content-Type الفعلي للاستجابة (وليس فقط امتداد الرابط) قبل القبول.
 * تُعيد اسم الملف المحفوظ (بدون مسار) عند النجاح، أو null عند الفشل.
 */
function downloadFeedImage($imageUrl, $baseName)
{
    if (empty($imageUrl)) {
        return null;
    }
    $result = fetchRemoteUrl($imageUrl, 10, 8 * 1024 * 1024);
    if (empty($result['body'])) {
        return null;
    }

    $allowedTypes = [
        'image/png'  => 'png',
        'image/jpg'  => 'jpg',
        'image/jpeg' => 'jpeg',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    $mime = $result['content_type'];
    // بعض الاستضافات لا تُعيد content-type دقيقاً بترويسة HTTP — تحقّق فعلي من
    // بايتات الملف نفسه عبر finfo كمصدر الحقيقة الأخير (نفس أسلوب up() بدالة functions.php)
    if (empty($mime) || !isset($allowedTypes[$mime])) {
        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $detected = @finfo_buffer($finfo, $result['body']);
            finfo_close($finfo);
            if ($detected && isset($allowedTypes[$detected])) {
                $mime = $detected;
            }
        }
    }
    if (empty($mime) || !isset($allowedTypes[$mime])) {
        return null; // ليس نوع صورة مدعوماً — تجاهل الصورة، لا يوقف استيراد المقال نفسه
    }

    $ext = $allowedTypes[$mime];
    $filename = $baseName . '.' . $ext;
    $dir = getpath() . 'files/blogs/';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $fullPath = $dir . $filename;
    if (@file_put_contents($fullPath, $result['body']) === false) {
        return null;
    }
    if (function_exists('compressUploadedImage')) {
        compressUploadedImage($fullPath);
    }
    return $filename;
}

/**
 * توليد slug فريد من عنوان مقال — نفس نمط تنظيف الأحرف المستخدم بأماكن أخرى
 * بالسكربت (`/[^\p{Arabic}a-zA-Z0-9_-]+/u`)، مع ضمان عدم التكرار بجدول blogs
 * عبر إضافة لاحقة رقمية عند الحاجة.
 */
function slugifyFeedTitle($title)
{
    global $rows, $countrows;
    $slug = trim((string) $title);
    $slug = str_replace(' ', '-', $slug);
    $slug = preg_replace('/[^\p{Arabic}a-zA-Z0-9_-]+/u', '', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    $slug = mb_substr($slug, 0, 180);
    if ($slug === '') {
        $slug = 'post-' . substr(md5(uniqid('', true)), 0, 10);
    }
    $slug = strtolower($slug);

    $baseSlug = $slug;
    $i = 2;
    while (true) {
        dbSelect('blogs', 'id', 'WHERE slug = ? LIMIT 1', [$slug]);
        if ($countrows == 0) {
            break;
        }
        $slug = $baseSlug . '-' . $i;
        $i++;
    }
    return $slug;
}

/**
 * الوظيفة الرئيسية: تشغيل استيراد فعلي لمصدر واحد (سحب يدوي "سحب الآن") أو كل
 * المصادر النشطة المستحقة (تشغيل تلقائي عبر api/feeds-cron.php). آمنة الاستدعاء
 * المتكرر — تتحقق ذاتياً من تفعيل الوحدة ووجود جداولها ووحدة "blogs" نفسها.
 *
 * $sourceId = null: تعالج كل المصادر النشطة (الأقدم جلباً أولاً)، بحد أقصى
 *   $maxSources مصادر بهذه التشغيلة الواحدة (تحسّباً لحد وقت تنفيذ PHP بالاستضافات
 *   المشتركة عند التشغيل التلقائي المتكرر).
 * $sourceId = رقم مصدر محدد: تعالج هذا المصدر فقط بغض النظر عن آخر موعد جلب له
 *   (يُستخدم لزر "سحب الآن" اليدوي بلوحة التحكم).
 *
 * تُعيد مصفوفة ملخّص: ['processed' => عدد المصادر المُعالَجة, 'imported' => عدد
 * المقالات المستوردة فعلياً بهذه التشغيلة, 'details' => [نص لكل مصدر]].
 */
function runFeedImport($sourceId = null, $maxSources = 5, $maxItemsPerSource = 8)
{
    $summary = ['processed' => 0, 'imported' => 0, 'details' => []];

    if (!moduleEnabled('feeds') || !feedsTableExists()) {
        $summary['details'][] = 'وحدة سحب المقالات غير مفعّلة أو غير مُجهَّزة بعد.';
        return $summary;
    }
    if (!moduleEnabled('blogs')) {
        $summary['details'][] = 'وحدة المقالات (blogs) غير مفعّلة — لا يمكن استيراد مقالات إليها.';
        return $summary;
    }

    global $rows, $countrows;

    if ($sourceId !== null) {
        dbSelect('feed_sources', '*', 'WHERE id = ? AND status = ? LIMIT 1', [(int) $sourceId, 'active']);
        $sources = $rows;
    } else {
        dbSelect('feed_sources', '*', 'WHERE status = ? ORDER BY last_fetched_at IS NOT NULL, last_fetched_at ASC LIMIT ' . (int) $maxSources, ['active']);
        $sources = $rows;
    }

    if (empty($sources)) {
        $summary['details'][] = 'لا توجد مصادر نشطة لمعالجتها.';
        return $summary;
    }

    foreach ($sources as $source) {
        $summary['processed']++;
        $sourceImportedCount = 0;

        $fetch = fetchRemoteUrl($source['feed_url'], 12);
        if (empty($fetch['body'])) {
            $status = 'فشل جلب الرابط: ' . ($fetch['error'] ?: 'خطأ غير معروف');
            dbUpdate('feed_sources', 'last_fetched_at = ?, last_status = ?', [date('Y-m-d H:i:s'), $status, $source['id']], 'WHERE id = ?');
            $summary['details'][] = $source['name'] . ': ' . $status;
            continue;
        }

        $items = parseFeedItems($fetch['body'], $maxItemsPerSource);
        if (empty($items)) {
            $status = 'تم الجلب لكن لم يُعثر على أي مقالات صالحة بالـfeed (تحقق من صيغة الرابط)';
            dbUpdate('feed_sources', 'last_fetched_at = ?, last_status = ?', [date('Y-m-d H:i:s'), $status, $source['id']], 'WHERE id = ?');
            $summary['details'][] = $source['name'] . ': ' . $status;
            continue;
        }

        // التحقق من أن التصنيف الهدف ما زال موجوداً (قد يُحذَف لاحقاً بعد ربط المصدر به)
        $categoryId = null;
        if (!empty($source['category_id'])) {
            dbSelect('blog_categories', 'id', 'WHERE id = ? LIMIT 1', [$source['category_id']]);
            if ($countrows == 1) {
                $categoryId = (int) $source['category_id'];
            }
        }

        foreach ($items as $item) {
            dbSelect('feed_imported_items', 'id', 'WHERE source_id = ? AND guid = ? LIMIT 1', [$source['id'], $item['guid']]);
            if ($countrows >= 1) {
                continue; // مُستورَد مسبقاً — تخطَّه (منع التكرار بين التشغيلات المتتالية)
            }

            $title = applyFeedReplacements($item['title'], $source['replacements']);
            $contentRaw = applyFeedReplacements($item['content_html'], $source['replacements']);

            $name = safer($title !== '' ? $title : 'مقال بدون عنوان');
            $description = purifyImportedHtml($contentRaw);
            $tags = !empty($item['categories']) ? safer(implode(', ', array_slice($item['categories'], 0, 8))) : null;
            $slug = slugifyFeedTitle($title !== '' ? $title : ('article-' . time()));
            $status = !empty($source['auto_publish']) ? 'active' : 'disabled';

            $columns = 'name, description, slug, tags, status, category, date';
            $values = [$name, $description, $slug, $tags, $status, $categoryId, date('Y-m-d H:i:s')];
            $blogId = dbInsert('blogs', $columns, $values);

            if (!empty($item['image_url']) && $blogId) {
                $imageFilename = downloadFeedImage($item['image_url'], 'feed-' . $blogId);
                if ($imageFilename) {
                    dbUpdate('blogs', 'image = ?', [$imageFilename, $blogId], 'WHERE id = ? LIMIT 1');
                }
            }

            dbInsert('feed_imported_items', 'source_id, guid, blog_id, date', [$source['id'], $item['guid'], $blogId, date('Y-m-d H:i:s')]);

            $sourceImportedCount++;
            $summary['imported']++;
        }

        $newImportedTotal = (int) $source['imported_count'] + $sourceImportedCount;
        $status = $sourceImportedCount > 0
            ? ('تم استيراد ' . $sourceImportedCount . ' مقال جديد بنجاح')
            : 'تم الجلب — لا توجد مقالات جديدة (كلها مستوردة مسبقاً)';
        dbUpdate(
            'feed_sources',
            'last_fetched_at = ?, last_status = ?, imported_count = ?',
            [date('Y-m-d H:i:s'), $status, $newImportedTotal, $source['id']],
            'WHERE id = ?'
        );
        $summary['details'][] = $source['name'] . ': ' . $status;
    }

    return $summary;
}
