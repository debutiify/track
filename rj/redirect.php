<?php
$ip = $_SERVER['REMOTE_ADDR'];
$cid = $_GET['cid'] ?? '';
$campaign_id = $_GET['campaign_id'] ?? '';
$adset_id = $_GET['adset_id'] ?? '';
$ad_id = $_GET['ad_id'] ?? '';

$config_file = __DIR__ . "/campaigns/{$cid}.json";
$log_file = __DIR__ . "/logs/{$cid}.csv";
$banned_file = __DIR__ . "/banned.txt";

if (!file_exists($config_file)) {
    http_response_code(404);
    exit("Invalid campaign.");
}

$config = json_decode(file_get_contents($config_file), true);
$redirect_url = $config['redirect_url'];
$fallback_url = $config['fallback_url'];
$limit = $config['daily_limit'];

$banned_ips = file_exists($banned_file) ? file($banned_file, FILE_IGNORE_NEW_LINES) : [];
if (in_array($ip, $banned_ips)) {
    file_put_contents($log_file, date('Y-m-d') . ",$ip,Blocked,BANNED,BANNED,BANNED\n", FILE_APPEND);
    header("Location: $fallback_url");
    exit;
}

$today = date('Y-m-d');
$clicks_today = 0;
if (file_exists($log_file)) {
    foreach (file($log_file) as $line) {
        [$date, $log_ip] = explode(',', $line);
        if ($date == $today && trim($log_ip) == $ip) {
            $clicks_today++;
        }
    }
}

if (
    empty($campaign_id) || empty($adset_id) || empty($ad_id) ||
    strpos($campaign_id, '{{') !== false ||
    strpos($adset_id, '{{') !== false ||
    strpos($ad_id, '{{') !== false ||
    $campaign_id !== $config['campaign_id'] ||
    $adset_id !== $config['adset_id'] ||
    $ad_id !== $config['ad_id']
) {
    // Log blocked click due to invalid IDs
    file_put_contents($log_file, "$today,$ip,Blocked,$campaign_id,$adset_id,$ad_id\n", FILE_APPEND);
    header("Location: $fallback_url");
    exit;
}

if ($clicks_today >= $limit) {
    file_put_contents($banned_file, "$ip\n", FILE_APPEND);
    file_put_contents($log_file, "$today,$ip,Blocked,$campaign_id,$adset_id,$ad_id\n", FILE_APPEND);
    header("Location: $fallback_url");
    exit;
}

// Valid and allowed
file_put_contents($log_file, "$today,$ip,Redirected,$campaign_id,$adset_id,$ad_id\n", FILE_APPEND);
header("Location: $redirect_url");
exit;
?>