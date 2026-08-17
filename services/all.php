<?php
if (!moduleEnabled('services')) {
    echo safeRender('404.twig', [
        "error_type"        => "404",
        "error_message"     => $lang['page_not_found'],
        "error_description" => $lang['page_not_found_desc'],
    ]);
    die();
}

dbSelect("services", "COUNT(*) as cnt", "WHERE status = ?", ["active"]);
$totalServices = (int) ($rows[0]['cnt'] ?? 0);

$pagination = paginate($totalServices, 9);

dbSelect("services", "id, slug, name, name_en, image, icon", "WHERE status = ? ORDER BY id DESC LIMIT " . (int) $pagination['per_page'] . " OFFSET " . (int) $pagination['offset'], ["active"]);
$services = $rows;
foreach ($services as &$service) {
    if ($_COOKIE['lang'] == "ar") {
        $service['name'] = $service['name'];
    } else {
        $service['name'] = $service['name_en'];
    }
    $service['image'] = !empty($service['image'])
        ? $site['site_url'] . "files/services/" . $service['image']
        : "";
    $service['url'] = routeUrl('service', $service['slug']);
}

echo safeRender('services/all.twig', [
    'services' => $services,
    'pagination' => $pagination,
]);
