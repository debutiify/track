<?php
$campaign_dir = __DIR__ . '/../campaigns';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="all_campaigns.csv"');

$output = fopen("php://output", "w");
fputcsv($output, ['cid', 'campaign_id', 'adset_id', 'ad_id', 'redirect_url', 'fallback_url', 'daily_limit']);

foreach (glob("$campaign_dir/*.json") as $file) {
    $cid = basename($file, ".json");
    $data = json_decode(file_get_contents($file), true);
    fputcsv($output, [
        $cid,
        $data['campaign_id'] ?? '',
        $data['adset_id'] ?? '',
        $data['ad_id'] ?? '',
        $data['redirect_url'] ?? '',
        $data['fallback_url'] ?? '',
        $data['daily_limit'] ?? 3
    ]);
}

fclose($output);
exit;