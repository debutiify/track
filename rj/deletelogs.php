<?php
$logsDir = __DIR__ . '/logs/';
$deletedFiles = 0;

// Check if folder exists
if (!is_dir($logsDir)) {
    die("❌ 'logs/' folder not found.");
}

$files = glob($logsDir . '*.csv');

if (!$files) {
    die("ℹ️ No .csv log files found in 'logs/' folder.");
}

foreach ($files as $file) {
    if (is_file($file)) {
        unlink($file);
        $deletedFiles++;
    }
}

echo "✅ Deleted $deletedFiles log file(s) from 'logs/' folder.";
?>