<?php
// بوتستراب صريح بدل الاعتماد على auto_prepend_file (بعض الاستضافات لا تدعمه
// إطلاقاً — راجع تعليق autoload.php بجذر الموقع لتفاصيل كاملة). آمن حتى لو
// نجح auto_prepend_file أيضاً على استضافات أخرى، لأن require_once يتجاهل أي
// تحميل مكرَّر لنفس الملف تلقائياً.
$__d = __DIR__;
while (!is_file($__d . '/autoload.php') && $__d !== dirname($__d)) {
    $__d = dirname($__d);
}
require_once $__d . '/autoload.php';
?>
<?php
if (!moduleEnabled('matches') || !matchesTableExists()) {
    echo safeRender('404.twig', [
        "error_type"        => "404",
        "error_message"     => $lang['page_not_found'],
        "error_description" => $lang['page_not_found_desc'],
    ]);
    die();
}

$id = isset($_GET['id']) ? (int) numer($_GET['id']) : 0;
$match = $id > 0 ? getMatchById($id) : null;

if (!$match) {
    echo safeRender('404.twig', [
        "error_type"        => "404",
        "error_message"     => $lang['page_not_found'],
        "error_description" => $lang['page_not_found_desc'],
    ]);
    die();
}

$match['url'] = routeUrl('matches', $match['id']);

// المقالات المرتبطة بهذه المباراة (البحث العكسي عبر blogs.related_match_id) —
// نفس العمود المستخدم بصندوق "ملخص المباراة" أعلى صفحة المقال، هنا نعرض
// الاتجاه المعاكس: كل الأخبار التي رُبطت بهذه المباراة تحديداً.
$related_articles = [];
if (moduleEnabled('blogs') && blogsRelatedMatchColumnExists()) {
    dbSelect(
        "blogs",
        "id, slug, name, name_en, image",
        "WHERE status = ? AND related_match_id = ? ORDER BY id DESC LIMIT 6",
        ["active", $match['id']]
    );
    if ($countrows >= 1) {
        foreach ($rows as $row) {
            $row['name'] = unescapeSafe(($_COOKIE['lang'] == "ar") ? $row['name'] : ($row['name_en'] ?? $row['name']));
            $row['image'] = !empty($row['image']) ? $site['site_url'] . "files/blogs/" . $row['image'] : "";
            $row['url'] = routeUrl('blog', $row['slug']);
            $related_articles[] = $row;
        }
    }
}

function utf8izeMatchView($mixed)
{
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = utf8izeMatchView($value);
        }
    } elseif (is_string($mixed)) {
        $mixed = mb_convert_encoding($mixed, 'UTF-8', 'UTF-8');
    }
    return $mixed;
}

// حالة SportsEvent المقابلة لحالة المباراة الداخلية — راجع
// https://schema.org/EventStatusType
$eventStatusMap = [
    'live'     => 'https://schema.org/EventScheduled',
    'upcoming' => 'https://schema.org/EventScheduled',
    'finished' => 'https://schema.org/EventScheduled', // انتهت فعلياً، ليست ملغاة
];

$schema = [
    "@context" => "https://schema.org",
    "@type" => "SportsEvent",
    "name" => $match['team_home'] . ' × ' . $match['team_away'],
    "startDate" => date('Y-m-d\TH:i:sP', strtotime($match['match_date'])),
    "eventStatus" => $eventStatusMap[$match['match_status']] ?? 'https://schema.org/EventScheduled',
    "eventAttendanceMode" => "https://schema.org/OfflineEventAttendanceMode",
    "homeTeam" => [
        "@type" => "SportsTeam",
        "name" => $match['team_home'],
    ],
    "awayTeam" => [
        "@type" => "SportsTeam",
        "name" => $match['team_away'],
    ],
    "url" => $match['url'],
    "organizer" => [
        "@type" => businessSchemaType(),
        "name" => $site['name'],
        "url" => $site['site_url'],
    ],
];

if (!empty($match['competition'])) {
    $schema['superEvent'] = [
        "@type" => "SportsEvent",
        "name" => $match['competition'],
    ];
}

if (!empty($match['venue'])) {
    $schema['location'] = [
        "@type" => "Place",
        "name" => $match['venue'],
    ];
} else {
    // location مطلوب من Google لبطاقات SportsEvent — نستخدم الموقع نفسه كقيمة
    // احتياطية إن لم يُدخَل اسم الملعب
    $schema['location'] = [
        "@type" => "Place",
        "name" => $site['name'],
    ];
}

if ($match['match_status'] === 'finished' && $match['score_home'] !== null && $match['score_away'] !== null) {
    $schema['description'] = ($_COOKIE['lang'] == "ar")
        ? "انتهت المباراة بنتيجة {$match['score_home']} - {$match['score_away']}"
        : "Final score: {$match['score_home']} - {$match['score_away']}";
}

$schema_utf8 = utf8izeMatchView($schema);

echo safeRender('matches/view.twig', [
    'match' => $match,
    'related_articles' => $related_articles,
    'schema_json' => json_encode($schema_utf8, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
]);
