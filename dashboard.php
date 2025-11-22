<?php
include 'koneksi.php';
session_start();
if (!isset($_SESSION['admin_logged'])) {
    header("Location: login.php");
    exit;
}

// === API Key YouTube ===
$apiKey = 'AIzaSyAPnGJS6r5Q2H_235Szh3bxPrQBmvckM8k';

// === Load data CCTV dari JSON ===
$dataFile = 'cctv_data.json';
$devices = file_exists($dataFile)
    ? json_decode(file_get_contents($dataFile), true)
    : [];

// === Fungsi cek status YouTube LIVE ===
function cekStatusYoutube($videoId, $apiKey) {
    if (!$videoId) return "offline";

    $apiUrl = "https://www.googleapis.com/youtube/v3/videos?part=liveStreamingDetails&id={$videoId}&key={$apiKey}";
    $raw = @file_get_contents($apiUrl);

    if (!$raw) return "offline";

    $data = json_decode($raw, true);
    $details = $data['items'][0]['liveStreamingDetails'] ?? null;

    if ($details &&
        isset($details['actualStartTime']) &&
        !isset($details['actualEndTime'])
    ) {
        return "online";
    }

    return "offline";
}

// === Hitung realtime status ===
$totalCCTV = count($devices);
$online = 0;
$offline = 0;

foreach ($devices as &$d) {
    $videoId = $d['video_id'] ?? null;

    $status = cekStatusYoutube($videoId, $apiKey);
    $d['status'] = $status;

    ($status === "online") ? $online++ : $offline++;
}
unset($d);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard CCTV</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- font -->
 <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
<style>
    body { font-family: "poppins" !important; margin: 0; padding: 0; background: #f5f5f5; }
    .sidebar { width: 230px; height: 130vh; background: #222; color: white; float: left; padding-top: 20px; font-family: "poppins"; }
    .sidebar a { display: block; padding: 12px; color: white; text-decoration: none; }
    .sidebar a:hover { background: #444; }

    h1 {
        font-family: "poppins";
        margin-top: 0 !important;
    }
    /* .sidebar > h2 { */
        /* margin-top: 0 !important; */
    /* } */
    .logout-btn {
        margin-top: 20px;
        background: #e74c3c;
        color: white;
        display: block;
        padding: 12px;
        text-decoration: none;
        text-align: center;
        border-radius: 6px;
        font-weight: bold;
    }
    .logout-btn:hover { background: #c0392b; }

    .content { margin-left: 230px; padding: 20px; }

    .card-container { 
        display: flex; 
        gap: 20px; 
        /* margin-bottom: 25px; */
        font-family: "poppins";
     }
    .card { 
        background: white; 
        padding: 0 20px; 
        border-radius: 8px; 
        box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
        width: 230px; 
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }
    /* .card .value { font-size: 32px; margin-top: 10px; } */
.value {
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 5px;
    background-color: lightblue;
    width: 50px;
    height: 50px;
    font-size: 1.5em;
}
    .map-wrapper {
        width: 100%;
        height: 500px;
        border: 1px solid #ccc;
        border-radius: 10px;
        overflow: hidden;
        background: white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
</style>
</head>
<body>

<div class="sidebar">
    <h2 style="text-align:center;">📡 CCTV PANEL</h2>

    <a href="dashboard.php"><i class="fa fa-chart-line"></i> Dashboard</a>
    <a href="maps.php"><i class="fa fa-map"></i> Peta CCTV</a>
    <a href="list.php"><i class="fa fa-video"></i> Data CCTV</a>

    <hr style="border-color:#555;">
    <h3 style="padding-left:12px;">STATISTIK</h3>
    <a><i class="fa fa-camera"></i> Jumlah CCTV: <strong><?= $totalCCTV ?></strong></a>
    <a><i class="fa fa-circle" style="color:green;"></i> Online: <strong><?= $online ?></strong></a>
    <a><i class="fa fa-circle" style="color:red;"></i> Offline: <strong><?= $offline ?></strong></a>

    <!-- Tombol Logout -->
    <a href="logout.php" class="logout-btn" onclick="return confirm('Yakin ingin logout?');">
        <i class="fa fa-sign-out-alt"></i> Logout
    </a>
</div>

<div class="content">
    <h1>Dashboard Monitoring CCTV</h1>

    <div class="card-container">
        <div class="card">
            <h3>Total CCTV</h3>
            <div class="value"><?= $totalCCTV ?></div>
        </div>

        <div class="card" style="border-left:5px solid green;">
            <h3>CCTV Online</h3>
            <div class="value"><?= $online ?></div>
        </div>

        <div class="card" style="border-left:5px solid red;">
            <h3>CCTV Offline</h3>
            <div class="value"><?= $offline ?></div>
        </div>
    </div>

    <h2>Peta CCTV</h2>
    <div class="map-wrapper">
        <?php include 'maps_only.php'; ?>
    </div>
</div>

</body>
</html>