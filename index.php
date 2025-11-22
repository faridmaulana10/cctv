<?php
// API Key YouTube Data API v3
$apiKey = 'AIzaSyAPnGJS6r5Q2H_235Szh3bxPrQBmvckM8k';

// Fungsi untuk cek status video YouTube
function checkYoutubeStatus($videoId, $apiKey) {
    $apiUrl = "https://www.googleapis.com/youtube/v3/videos?part=liveStreamingDetails&id={$videoId}&key={$apiKey}";
    $json = @file_get_contents($apiUrl);
    if (!$json) return 'off';
    $data = json_decode($json, true);
    if (!empty($data['items'])) {
        $details = $data['items'][0]['liveStreamingDetails'] ?? null;
        if ($details && isset($details['actualStartTime']) && !isset($details['actualEndTime'])) {
            return 'on'; // streaming aktif
        }
    }
    return 'off'; // tidak aktif
}

// Ambil data CCTV
$jsonString = file_get_contents('cctv_data.json');
$cctv_list = json_decode($jsonString, true);
if (!is_array($cctv_list)) die("Data CCTV tidak valid.");

// Ambil data deteksi kendaraan
$vehicle_counts = [];
if (file_exists('vehicle_count.json')) {
    $vehicle_counts = json_decode(file_get_contents('vehicle_count.json'), true);
}

// ID CCTV aktif
$active_id = isset($_GET['id']) ? intval($_GET['id']) : 1;
$active_cctv = null;

// Tambahkan status ke setiap CCTV
foreach ($cctv_list as &$cctv) {
    $cctv['status'] = checkYoutubeStatus($cctv['video_id'], $apiKey);
    if ($cctv['id'] === $active_id) $active_cctv = $cctv;
}
unset($cctv);

if (!$active_cctv) $active_cctv = $cctv_list[0];

// Data hitungan kendaraan untuk CCTV aktif
$counts = $vehicle_counts[$active_cctv['id']] ?? ['car' => 0, 'motorbike' => 0, 'bus' => 0, 'truck' => 0];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>CCTV Kabupaten Rembang</title>
    <style>
        body {
            margin: 0;
            font-family: "Segoe UI", sans-serif;
            background-color: rgb(243, 248, 255);
            color: #333;
        }
        header, footer {
            background: rgb(177, 93, 255);
            color: white;
            padding: 15px 0;
            text-align: center;
        }
        .menu-nav {
            margin-top: 10px;
        }
        .menu-nav a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
            font-weight: bold;
            font-size: 14px;
            padding: 5px 10px;
            border-radius: 6px;
        }
        .main-container {
            display: flex;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            gap: 20px;
            align-items: flex-start;
        }
        .main-video {
            flex: 3;
        }
        .video-wrapper {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
        }
        .video-wrapper iframe {
            position: absolute;
            top: 0; left: 0;
            width: 100%;
            height: 100%;
        }
        .sidebar {
            flex: 1;
            display: flex;
            flex-direction: column;
            margin-top: 75px;
            height: 500px;
        }
        .sidebar-item {
            display: flex;
            background: white;
            border-radius: 8px;
            box-shadow: 0 3px 5px rgba(0,0,0,0.1);
            margin-bottom: 10px;
            cursor: pointer;
            overflow: hidden;
            transition: transform 0.2s;
        }
        .sidebar-item:hover {
            transform: scale(1.03);
        }
        .sidebar-item img {
            width: 150px;
            height: 120px;
            object-fit: cover;
        }
        .info {
            padding: 8px;
            font-size: 13px;
        }
        .status {
            display: inline-block;
            font-size: 12px;
        }
        .status.on {
            color: green;
        }
        .status.off {
            color: red;
        }
        footer {
            margin-top: 30px;
        }
    </style>
</head>
<body>

<header>
    <h1>CCTV KABUPATEN REMBANG</h1>
    <nav class="menu-nav">
        <a href="maps.php">🗺️ Maps</a>
    </nav>
</header>

<div class="main-container">
    <div class="main-video">
        <h2><?= htmlspecialchars($active_cctv['nama']) ?></h2>
        <div class="video-wrapper">
            <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($active_cctv['video_id']) ?>?autoplay=1&mute=1"
                    allow="autoplay; encrypted-media" allowfullscreen></iframe>
        </div>

    </div>

    <div class="sidebar">
        <?php foreach ($cctv_list as $cctv): if ($cctv['id'] === $active_cctv['id']) continue; ?>
        <div class="sidebar-item" onclick="window.location.href='?id=<?= $cctv['id'] ?>'">
            <img src="<?= htmlspecialchars($cctv['thumbnail']) ?>" alt="Thumbnail">
            <div class="info">
                <strong><?= htmlspecialchars($cctv['nama']) ?></strong><br>
                <span class="status <?= $cctv['status'] ?>">
                    <?= $cctv['status'] === 'on' ? '🟢 Nyala' : '🔴 Mati' ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<footer>
    <p>&copy; <?= date('Y') ?> Sistem Pemantauan CCTV</p>
</footer>

</body>
</html>