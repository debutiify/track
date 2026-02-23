<?php
$logDir = __DIR__ . '/logs/';
$outputFile = __DIR__ . '/blocklogs.csv';

if (!is_dir($logDir)) {
    die("❌ logs folder not found at $logDir\n");
}

$files = glob($logDir . '*.csv');
if (empty($files)) {
    die("❌ No campaign log files found in $logDir\n");
}

$blocked = [];

foreach ($files as $file) {
    $cid = basename($file, '.csv'); // filename as CID
    $handle = fopen($file, "r");
    if ($handle === false) {
        echo "❌ Failed to open: $file\n";
        continue;
    }

    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) < 6) continue;

        $status = strtolower(trim($row[2]));
        if ($status === 'blocked') {
            $campaign_id = trim($row[3]);
            $adset_id = trim($row[4]);
            $ad_id = trim($row[5]);

            $blocked[$cid] = [
                'cid' => $cid,
                'campaign_id' => $campaign_id,
                'adset_id' => $adset_id,
                'ad_id' => $ad_id
            ];
        }
    }

    fclose($handle);
}

// Write to CSV
$fp = fopen($outputFile, 'w');
fputcsv($fp, ['CID', 'Campaign ID', 'Adset ID', 'Ad ID']);

foreach ($blocked as $data) {
    fputcsv($fp, [$data['cid'], $data['campaign_id'], $data['adset_id'], $data['ad_id']]);
}

fclose($fp);

echo "✅ Blocked campaign data saved to: $outputFile\n";