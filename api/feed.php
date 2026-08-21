<?php
// بوتستراب صريح (كان يعتمد على auto_prepend_file عبر api/.htaccess/api/.user.ini
// فقط — بعض الاستضافات لا تُطبِّق أياً منهما، راجع تعليق autoload.php بجذر
// الموقع لتفاصيل كاملة).
include_once dirname(__DIR__) . '/includes/config.php';
include_once dirname(__DIR__) . '/includes/functions.php';

// إعداد الهيدر لتحديد نوع المحتوى XML
header("Content-Type: application/rss+xml; charset=UTF-8");


// جلب أحدث 10 مقالات
// $sql = "SELECT title, slug, content, pub_date FROM posts ORDER BY pub_date DESC LIMIT 10";
// $result = $conn->query($sql);

dbSelect("blogs", "slug, name, name_en, description, description_en, image, last_update, date", "WHERE status = ? ORDER BY id DESC LIMIT 10", ["active"]);

// إخراج ملف RSS
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0">
    <channel>
        <title><?php echo ($_COOKIE['lang'] == "ar") ? $site['name'] : $site['name_en'] ?></title>
        <link><?php echo $site['site_url'] ?></link>
        <description><?php echo ($_COOKIE['lang'] == "ar") ? $site['description'] : $site['description_en'] ?></description>
        <language><?php echo ($_COOKIE['lang'] == "ar") ? "ar-sa" : "en-us" ?></language>
        <lastBuildDate><?php echo date(DATE_RSS) ?></lastBuildDate>

        <?php foreach ($rows as $row):
            if ($_COOKIE['lang'] == "ar") {
                $row['name'] = $row['name'];
                $row['description'] = $row['description'];
            } else {
                $row['name'] = $row['name_en'];
                $row['description'] = $row['description_en'];
            }
            if (!empty($row['last_update'])) {
                $date_modified = date(DATE_RSS, strtotime($row['last_update']));
            } else {
                $date_modified = date(DATE_RSS, strtotime($row['date']));
            }
        ?>
            <item>
                <title><?php echo htmlspecialchars(unescapeSafe($row['name']), ENT_QUOTES, 'UTF-8') ?></title>
                <link><?php echo safer(routeUrl('blog', $row['slug'])) ?></link>
                <guid><?php echo safer(routeUrl('blog', $row['slug'])) ?></guid>
                <pubDate><?php echo $date_modified ?></pubDate>
                <enclosure url="<?php echo safer($site['site_url'] . 'files/blogs/' . $row['image']) ?>" type="image/jpeg" />
                <description>
                    <![CDATA[<?php echo mb_substr(strip_tags($row['description']), 0, 300) ?>...]]>
                </description>
            </item>
        <?php endforeach; ?>

    </channel>
</rss>