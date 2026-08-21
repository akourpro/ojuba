<?php
include_once dirname(__DIR__) . '/includes/config.php';
include_once dirname(__DIR__) . '/includes/functions.php';
require_once 'includes/feed_importer.php';

header('Content-Type: application/json; charset=utf-8');

if (!moduleEnabled('feeds') || !feedsTableExists()) {
    echo json_encode(['status' => false, 'message' => 'وحدة سحب المقالات غير مفعّلة أو غير مُجهَّزة']);
    exit;
}

$expectedToken = ensureFeedsCronToken();
$givenToken = safer($_GET['token'] ?? '');
if (empty($givenToken) || !hash_equals($expectedToken, $givenToken)) {
    http_response_code(403);
    echo json_encode(['status' => false, 'message' => 'توكن غير صحيح']);
    exit;
}


$throttleSeconds = 300;
global $site;
$lastRun = !empty($site['feeds_cron_last_run']) ? strtotime($site['feeds_cron_last_run']) : 0;
if ($lastRun && (time() - $lastRun) < $throttleSeconds) {
    echo json_encode(['status' => true, 'message' => 'تم التخطي (آخر تشغيل حديث)', 'skipped' => true]);
    exit;
}

saveSetting('feeds_cron_last_run', date('Y-m-d H:i:s'));

$result = runFeedImport(null, 5, 8);

echo json_encode([
    'status' => true,
    'processed' => $result['processed'],
    'imported' => $result['imported'],
    'details' => $result['details'],
], JSON_UNESCAPED_UNICODE);
