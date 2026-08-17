<?php
if (!moduleEnabled('matches') || !matchesTableExists()) {
    echo safeRender('404.twig', [
        "error_type"        => "404",
        "error_message"     => $lang['page_not_found'],
        "error_description" => $lang['page_not_found_desc'],
    ]);
    die();
}

dbSelect("sport_matches", "COUNT(*) as cnt", "WHERE status = ?", ["active"]);
$totalMatches = (int) ($rows[0]['cnt'] ?? 0);

$pagination = paginate($totalMatches, 12);

// نفس ترتيب getMatchesForDisplay() (مباشر > قادمة > منتهية، ثم الموعد) لكن مع
// ترقيم صفحات كامل — لا تُستخدم getMatchesForDisplay() هنا لأنها محدودة بحد
// أقصى ثابت (limit) وغير مصمَّمة للترقيم؛ هذا استعلام مستقل خاص بصفحة الأرشيف.
dbSelect(
    "sport_matches",
    "id, competition, team_home, team_home_logo, team_away, team_away_logo, match_date, venue, score_home, score_away, match_status, broadcast_channel",
    "WHERE status = ? ORDER BY CASE match_status WHEN 'live' THEN 0 WHEN 'upcoming' THEN 1 ELSE 2 END ASC, match_date DESC LIMIT " . (int) $pagination['per_page'] . " OFFSET " . (int) $pagination['offset'],
    ["active"]
);

$matches = [];
foreach ($rows as $row) {
    $row['team_home_logo'] = !empty($row['team_home_logo']) ? $site['site_url'] . 'files/matches/' . $row['team_home_logo'] : '';
    $row['team_away_logo'] = !empty($row['team_away_logo']) ? $site['site_url'] . 'files/matches/' . $row['team_away_logo'] : '';
    $row['date_human'] = ago($row['match_date']);
    $row['url'] = routeUrl('matches', $row['id']);
    $matches[] = $row;
}

echo safeRender('matches.twig', [
    'matches' => $matches,
    'pagination' => $pagination,
]);
