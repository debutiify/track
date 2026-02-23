<?php
$campaign_dir = __DIR__ . '/../campaigns';
$log_dir = __DIR__ . '/../logs';

if (isset($_POST['import_csv']) && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    if (($handle = fopen($file, "r")) !== false) {
        fgetcsv($handle); // skip header
        date_default_timezone_set('Asia/Kolkata'); // Optional

        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            list($cid, $campaign_id, $adset_id, $ad_id, $redirect_url, $fallback_url, $daily_limit) = $data;
            $campaign_data = [
                "campaign_id" => $campaign_id,
                "adset_id" => $adset_id,
                "ad_id" => $ad_id,
                "redirect_url" => $redirect_url,
                "fallback_url" => $fallback_url,
                "daily_limit" => (int)$daily_limit,
                "created_at" => date("Y-m-d H:i")
            ];
            file_put_contents("$campaign_dir/$cid.json", json_encode($campaign_data, JSON_PRETTY_PRINT));
        }
        fclose($handle);
    }
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cid = $_POST['cid'];
    $file_path = "$campaign_dir/$cid.json";

    date_default_timezone_set('Asia/Kolkata'); // ✅ Set desired timezone

    $data = [
        "campaign_id" => $_POST['campaign_id'],
        "adset_id" => $_POST['adset_id'],
        "ad_id" => $_POST['ad_id'],
        "redirect_url" => $_POST['redirect_url'],
        "fallback_url" => $_POST['fallback_url'],
        "daily_limit" => (int)$_POST['daily_limit'],
    ];

    // Add or preserve creation date
    if (!file_exists($file_path)) {
        $data["created_at"] = date("Y-m-d H:i");
    } else {
        $existing = json_decode(file_get_contents($file_path), true);
        $data["created_at"] = $existing["created_at"] ?? date("Y-m-d H:i");
    }

    file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT));
    header("Location: index.php");
    exit;
}

$campaigns = glob("$campaign_dir/*.json");

// Sort by modified time, latest first
usort($campaigns, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

if (isset($_GET['delete'])) {
    $del_cid = $_GET['delete'];
    $campaign_file = "$campaign_dir/$del_cid.json";
    $log_file = "$log_dir/$del_cid.csv";

    if (file_exists($campaign_file)) {
        unlink($campaign_file); // delete campaign config
    }

    if (file_exists($log_file)) {
        unlink($log_file); // delete log file
    }

    header("Location: index.php");
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Redirect Admin Panel</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 30px;
            background: #f9f9f9;
            color: #333;
        }
        h2, h3 {
            color: #444;
        }
        form input, form button {
            padding: 10px;
            margin: 6px 0;
            width: 300px;
            border: 1px solid #ccc;
            border-radius: 5px;
            display: block;
        }
        form button {
            width: 150px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
        form button:hover {
            background-color: #0056b3;
        }
        .filter-group input, .filter-group select {
            padding: 8px;
            margin: 5px 10px 5px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            margin-top: 20px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f1f1f1;
        }
        ul li {
            margin-bottom: 10px;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .filter-group {
            margin-top: 10px;
        }
    </style>
</head>
<body>


<h2>Create New Campaign</h2>
<form method="POST" action="export_campaigns.php" style="margin-top:20px;">
    <button type="submit" style="padding: 10px 20px;">Export All Campaigns</button>
</form>
<h2>Import Bulk Campaigns (CSV)</h2>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="csv_file" accept=".csv" required>
    <button type="submit" name="import_csv">Import CSV</button>
</form>
<form method="POST">
    <input name="cid" placeholder="Unique Campaign Key (cid)" required>
    <input name="campaign_id" placeholder="Campaign ID (from FB)" required>
    <input name="adset_id" placeholder="Adset ID" required>
    <input name="ad_id" placeholder="Ad ID" required>
    <input name="redirect_url" placeholder="Redirect URL" required>
    <input name="fallback_url" placeholder="Fallback URL" required>
    <input name="daily_limit" type="number" value="3" placeholder="Daily Click Limit/IP">
    <button type="submit">Save Campaign</button>
</form>
<hr>

<style>
    table.campaigns {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    table.campaigns th, table.campaigns td {
        border: 1px solid #ccc;
        padding: 8px;
        text-align: left;
    }
    table.campaigns th {
        background-color: #000000;
    }
    table.campaigns td a {
        color: #007BFF;
        text-decoration: none;
    }
    table.campaigns td a:hover {
        text-decoration: underline;
    }
</style>
<?php
// Search and pagination setup
$search = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : '';
$filtered_campaigns = [];

foreach ($campaigns as $file) {
    $cid = basename($file, ".json");
    $data = json_decode(file_get_contents($file), true);
    $campaign_id = $data['campaign_id'] ?? '';

    if ($search === '' || strpos(strtolower($cid), $search) !== false || strpos(strtolower($campaign_id), $search) !== false) {
        $filtered_campaigns[] = ['cid' => $cid, 'data' => $data];
    }
}

$total = count($filtered_campaigns);
$per_page = 50;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$total_pages = ceil($total / $per_page);
$start = ($page - 1) * $per_page;
$paginated = array_slice($filtered_campaigns, $start, $per_page);
?>
<h2>All Campaigns</h2>
<form method="GET" style="margin-bottom: 10px;">
    <input type="text" name="search" placeholder="Search by Campaign Key or ID" value="<?= htmlspecialchars($search) ?>" style="padding:8px; width: 300px;">
    <button type="submit" style="padding:8px;">Search</button>
</form>
<table class="campaigns">
    <thead>
        <tr>
        <th>Date</th>
            <th>Campaign Key</th>
            <th>Campaign ID</th>
            <th>Adset ID</th>
            <th>Ad ID</th>
            <th>Redirect URL</th>
            <th>Fallback URL</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($paginated as $row):
            $cid = $row['cid'];
            $data = $row['data'];
        ?>
        <tr>
        <td><?= isset($data['created_at']) ? htmlspecialchars($data['created_at']) : '-' ?></td>
            <td><strong><?= htmlspecialchars($cid) ?></strong></td>
            <td><?= htmlspecialchars($data['campaign_id']) ?></td>
            <td><?= htmlspecialchars($data['adset_id']) ?></td>
            <td><?= htmlspecialchars($data['ad_id']) ?></td>
            <td><a href="<?= htmlspecialchars($data['redirect_url']) ?>" target="_blank">Redirect</a></td>
            <td><a href="<?= htmlspecialchars($data['fallback_url']) ?>" target="_blank">Fallback</a></td>
            <td><a href="?edit=<?= urlencode($cid) ?>">Edit</a> |
                <a href="../redirect.php?cid=<?= urlencode($cid) ?>" target="_blank">Test</a> |
                <a href="?view=<?= urlencode($cid) ?>">Logs</a> |
                <a href="../redirect.php?cid=<?= urlencode($cid) ?>&campaign_id=<?= urlencode($data['campaign_id']) ?>&adset_id=<?= urlencode($data['adset_id']) ?>&ad_id=<?= urlencode($data['ad_id']) ?>" target="_blank">Live URL</a> |
                <a href="../redirect.php?cid=<?= urlencode($cid) ?>&campaign_id={{campaign.id}}&adset_id={{adset.id}}&ad_id={{ad.id}}" target="_blank">Ads URL</a> |
                <a href="?delete=<?= urlencode($cid) ?>" onclick="return confirm('Are you sure you want to delete this campaign?');" style="color:red;">Delete</a>

            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php if ($total_pages > 1): ?>
<style>
.pagination {
    margin-top: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}
.pagination a,
.pagination strong {
    padding: 6px 12px;
    border: 1px solid #007bff;
    border-radius: 4px;
    text-decoration: none;
    color: #007bff;
    background-color: #fff;
    transition: background-color 0.2s ease-in-out;
}
.pagination a:hover {
    background-color: #007bff;
    color: white;
}
.pagination strong {
    background-color: #007bff;
    color: white;
    cursor: default;
}
</style>

<div class="pagination">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <?php if ($i == $page): ?>
            <strong><?= $i ?></strong>
        <?php else: ?>
            <a href="?search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php
if (isset($_GET['view'])):
    $view_cid = $_GET['view'];
    $log_file = "$log_dir/$view_cid.csv";
?>
    <h3>Logs for Campaign: <?= htmlspecialchars($view_cid) ?></h3>
    <div class="filter-group">
        <input type="text" id="ipFilter" placeholder="Filter by IP">
        <select id="typeFilter">
            <option value="">All Types</option>
            <option value="Redirected">VALID</option>
            <option value="Blocked">BLOCKED</option>
        </select>
        <input type="text" id="campaignIdFilter" placeholder="Filter by Campaign ID">
        <input type="text" id="adsetIdFilter" placeholder="Filter by Adset ID">
        <input type="text" id="adIdFilter" placeholder="Filter by Ad ID">
    </div>

    <table class="logs">
        <thead>
            <tr>
                <th>Date</th>
                <th>IP</th>
                <th>Type</th>
                <th>Campaign ID</th>
                <th>Adset ID</th>
                <th>Ad ID</th>
            </tr>
        </thead>
        <tbody id="logTable">
<?php
$lines = [];
if (file_exists($log_file)) {
    $lines = array_reverse(file($log_file)); // Newest first
}

$total_rows = count($lines);
$rows_per_page = 50;
$total_pages = ceil($total_rows / $rows_per_page);
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$start_index = ($current_page - 1) * $rows_per_page;
$paginated_lines = array_slice($lines, $start_index, $rows_per_page);

foreach ($paginated_lines as $line) {
    $parts = explode(",", trim($line));
    if (count($parts) >= 6) {
        echo "<tr>";
        foreach ($parts as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
}
?>
</tbody>
    </table>
    <?php if ($total_pages > 1): ?>
<div style="margin-top: 10px;">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <?php if ($i == $current_page): ?>
            <strong><?= $i ?></strong>
        <?php else: ?>
            <a href="?view=<?= urlencode($view_cid) ?>&page=<?= $i ?>"><?= $i ?></a>
        <?php endif; ?>
        &nbsp;
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const filters = {
        ip: document.getElementById("ipFilter"),
        type: document.getElementById("typeFilter"),
        campaign_id: document.getElementById("campaignIdFilter"),
        adset_id: document.getElementById("adsetIdFilter"),
        ad_id: document.getElementById("adIdFilter"),
    };

    const table = document.getElementById("logTable");

    function filterRows() {
        const rows = table.querySelectorAll("tr");
        rows.forEach(row => {
            const cells = row.querySelectorAll("td");
            if (cells.length < 6) return;
            const [date, ip, type, cid, adset, ad] = [...cells].map(c => c.textContent.toLowerCase());

            const show =
                ip.includes(filters.ip.value.toLowerCase()) &&
                (filters.type.value === "" || type === filters.type.value.toLowerCase()) &&
                cid.includes(filters.campaign_id.value.toLowerCase()) &&
                adset.includes(filters.adset_id.value.toLowerCase()) &&
                ad.includes(filters.ad_id.value.toLowerCase());

            row.style.display = show ? "" : "none";
        });
    }

    Object.values(filters).forEach(input => input.addEventListener("input", filterRows));
});
</script>
<?php
if (isset($_GET['edit'])):
    $edit_cid = $_GET['edit'];
    $edit_file = "$campaign_dir/$edit_cid.json";
    if (file_exists($edit_file)):
        $data = json_decode(file_get_contents($edit_file), true);
?>
    <hr>
    <h2>Edit Campaign: <?= htmlspecialchars($edit_cid) ?></h2>
    <form method="POST">
        <input type="hidden" name="cid" value="<?= htmlspecialchars($edit_cid) ?>">
        <input name="campaign_id" placeholder="Campaign ID" required value="<?= htmlspecialchars($data['campaign_id']) ?>">
        <input name="adset_id" placeholder="Adset ID" required value="<?= htmlspecialchars($data['adset_id']) ?>">
        <input name="ad_id" placeholder="Ad ID" required value="<?= htmlspecialchars($data['ad_id']) ?>">
        <input name="redirect_url" placeholder="Redirect URL" required value="<?= htmlspecialchars($data['redirect_url']) ?>">
        <input name="fallback_url" placeholder="Fallback URL" required value="<?= htmlspecialchars($data['fallback_url']) ?>">
        <input name="daily_limit" type="number" value="<?= htmlspecialchars($data['daily_limit']) ?>" placeholder="Daily Click Limit/IP">
        <button type="submit">Update Campaign</button>
    </form>
<?php
    else:
        echo "<p>Campaign not found.</p>";
    endif;
endif;
?>
</body>
</html>