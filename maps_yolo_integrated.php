<?php
session_start();

if (!isset($_SESSION['user_logged'])) {
    header("Location: login_user.php");
    exit;
}

$apiKey = 'AIzaSyAPnGJS6r5Q2H_235Szh3bxPrQBmvckM8k';

function checkYoutubeStatus($videoId, $apiKey) {
    if (!$videoId) return 'offline';
    $apiUrl = "https://www.googleapis.com/youtube/v3/videos?part=liveStreamingDetails&id={$videoId}&key={$apiKey}";
    $json = @file_get_contents($apiUrl);
    if (!$json) return 'offline';
    $data = json_decode($json, true);
    if (!empty($data['items'])) {
        $details = $data['items'][0]['liveStreamingDetails'] ?? null;
        if ($details && isset($details['actualStartTime']) && !isset($details['actualEndTime'])) {
            return 'online';
        }
    }
    return 'offline';
}

$jsonFile = 'cctv_data.json';
$cctvList = [];

if (file_exists($jsonFile)) {
    $jsonString = file_get_contents($jsonFile);
    $cctvList = json_decode($jsonString, true);
    foreach ($cctvList as &$cctv) {
        $cctv['status'] = checkYoutubeStatus($cctv['video_id'], $apiKey);
    }
    unset($cctv);
} else {
    die("Error: File cctv_data.json tidak ditemukan!");
}

$totalCCTV = count($cctvList);
$onlineCount = count(array_filter($cctvList, fn($c) => $c['status'] === 'online'));
$offlineCount = $totalCCTV - $onlineCount;

$activeCCTVId = isset($_GET['id']) ? intval($_GET['id']) : ($cctvList[0]['id'] ?? 1);
$activeCCTV = null;

foreach ($cctvList as $cctv) {
    if ($cctv['id'] === $activeCCTVId) {
        $activeCCTV = $cctv;
        break;
    }
}

if (!$activeCCTV) {
    $activeCCTV = $cctvList[0];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCTV AI Detection - Kabupaten Rembang</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1.5rem 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 10000;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .logo-text h1 {
            font-size: 1.5rem;
            color: #2c3e50;
            font-weight: 700;
        }

        .logo-text p {
            font-size: 0.85rem;
            color: #7f8c8d;
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            background: #f8f9fa;
            border-radius: 25px;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .btn-logout {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
        }

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }

        .stat-icon.total { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .stat-icon.online { background: linear-gradient(135deg, #11998e, #38ef7d); color: white; }
        .stat-icon.offline { background: linear-gradient(135deg, #eb3349, #f45c43); color: white; }

        .stat-info h3 {
            font-size: 2rem;
            color: #2c3e50;
            font-weight: 700;
        }

        .stat-info p {
            color: #7f8c8d;
            font-size: 1rem;
        }

        .list-container {
            display: none;
            background: white;
            border-radius: 20px;
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .list-container.active {
            display: block;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .list-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        .close-btn {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
        }

        .main-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .video-section {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .video-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .video-header-left h2 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
        }

        .video-header-left .location {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .detection-toggle {
            display: flex;
            gap: 0.5rem;
        }

        .btn-toggle {
            padding: 0.75rem 1.5rem;
            border: 2px solid white;
            background: rgba(255,255,255,0.1);
            color: white;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-toggle:hover {
            background: rgba(255,255,255,0.2);
        }

        .btn-toggle.active {
            background: white;
            color: #667eea;
        }

        .btn-toggle:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            background: #000;
        }

        .video-container iframe,
        .video-container img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .detection-status {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 0.5rem 1rem;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            border-radius: 8px;
            font-size: 0.85rem;
            z-index: 10;
            display: none;
            align-items: center;
            gap: 0.5rem;
        }

        .detection-status.active {
            display: flex;
        }

        .pulse {
            width: 10px;
            height: 10px;
            background: #38ef7d;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .cctv-sidebar {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-height: 800px;
            overflow-y: auto;
        }

        .stats-panel {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 1.5rem;
        }

        .stats-panel h4 {
            color: #667eea;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: white;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            border-left: 4px solid;
        }

        .stat-item.car { border-color: #28a745; }
        .stat-item.motorbike { border-color: #007bff; }
        .stat-item.bus { border-color: #ffc107; }
        .stat-item.truck { border-color: #dc3545; }

        .stat-label {
            font-weight: 600;
            color: #495057;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #212529;
        }

        .cctv-list-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #f8f9fa;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .cctv-list-item:hover {
            background: linear-gradient(135deg, #667eea15, #764ba215);
            transform: translateX(5px);
        }

        .cctv-list-item.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .cctv-thumbnail {
            width: 100px;
            height: 75px;
            border-radius: 8px;
            object-fit: cover;
        }

        .map-section {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .map-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        #map { height: 500px; width: 100%; }

        @media (max-width: 1024px) {
            .main-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo-section">
                <div class="logo-icon">
                    <i class="fas fa-video"></i>
                </div>
                <div class="logo-text">
                    <h1>🤖 CCTV AI Detection System</h1>
                    <p>Kabupaten Rembang</p>
                </div>
            </div>
            <div class="user-section">
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                    </div>
                    <span class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></span>
                </div>
                <a href="logout_user.php" class="btn-logout" onclick="return confirm('Yakin ingin logout?');">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-camera"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $totalCCTV ?></h3>
                    <p>Total CCTV</p>
                </div>
            </div>
            <div class="stat-card" id="cardOnline">
                <div class="stat-icon online">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $onlineCount ?></h3>
                    <p>CCTV Online</p>
                </div>
            </div>
            <div class="stat-card" id="cardOffline">
                <div class="stat-icon offline">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $offlineCount ?></h3>
                    <p>CCTV Offline</p>
                </div>
            </div>
        </div>

        <div class="list-container" id="listContainer">
            <div class="list-header">
                <h3 id="listTitle"><i class="fas fa-list"></i> Daftar CCTV</h3>
                <button class="close-btn" onclick="hideList()">
                    <i class="fas fa-times"></i> Tutup
                </button>
            </div>
            <ul id="listContent"></ul>
        </div>

        <div class="main-grid">
            <div class="video-section">
                <div class="video-header">
                    <div class="video-header-left">
                        <h2><?= htmlspecialchars($activeCCTV['nama']) ?></h2>
                        <div class="location">
                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($activeCCTV['alamat']) ?>
                        </div>
                    </div>
                    <div class="detection-toggle">
                        <button class="btn-toggle active" id="btnOriginal" onclick="showOriginal()">
                            <i class="fas fa-youtube"></i> Original
                        </button>
                        <button class="btn-toggle" id="btnDetection" onclick="showDetection()">
                            <i class="fas fa-robot"></i> AI Detection
                        </button>
                    </div>
                </div>
                <div class="video-container">
                    <iframe 
                        id="youtubeFrame"
                        src="https://www.youtube.com/embed/<?= htmlspecialchars($activeCCTV['video_id']) ?>?autoplay=1&mute=1" 
                        allow="autoplay; encrypted-media" 
                        allowfullscreen>
                    </iframe>
                    <img id="yoloStream" style="display: none;" src="" alt="YOLO Detection">
                    <div class="detection-status" id="detectionStatus">
                        <div class="pulse"></div>
                        <span>🤖 AI Detection Active</span>
                    </div>
                </div>
            </div>

            <div class="cctv-sidebar">
                <div class="stats-panel">
                    <h4><i class="fas fa-chart-bar"></i> Statistik Deteksi</h4>
                    <div class="stat-item car">
                        <span class="stat-label">🚗 Mobil</span>
                        <span class="stat-value" id="count-car">0</span>
                    </div>
                    <div class="stat-item motorbike">
                        <span class="stat-label">🏍️ Motor</span>
                        <span class="stat-value" id="count-motorbike">0</span>
                    </div>
                    <div class="stat-item bus">
                        <span class="stat-label">🚌 Bus</span>
                        <span class="stat-value" id="count-bus">0</span>
                    </div>
                    <div class="stat-item truck">
                        <span class="stat-label">🚚 Truk</span>
                        <span class="stat-value" id="count-truck">0</span>
                    </div>
                </div>

                <h3><i class="fas fa-list"></i> Daftar CCTV</h3>
                
                <?php foreach ($cctvList as $cctv): ?>
                <a href="?id=<?= $cctv['id'] ?>" class="cctv-list-item <?= $cctv['id'] === $activeCCTV['id'] ? 'active' : '' ?>">
                    <img src="<?= htmlspecialchars($cctv['thumbnail']) ?>" alt="<?= htmlspecialchars($cctv['nama']) ?>" class="cctv-thumbnail">
                    <div class="cctv-info">
                        <h4><?= htmlspecialchars($cctv['nama']) ?></h4>
                        <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($cctv['alamat']) ?></p>
                        <p style="color: <?= $cctv['status'] === 'online' ? '#38ef7d' : '#f45c43' ?>;">
                            <i class="fas fa-circle"></i> <?= ucfirst($cctv['status']) ?>
                        </p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="map-section">
            <div class="map-header">
                <h2><i class="fas fa-map"></i> Peta Lokasi CCTV</h2>
            </div>
            <div id="map"></div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const YOLO_API = 'http://localhost:5000';
        const cctvList = <?= json_encode($cctvList) ?>;
        const activeCCTV = <?= json_encode($activeCCTV) ?>;
        
        let isDetectionActive = false;
        let statsInterval = null;

        // Initialize Map
        const map = L.map('map').setView([-6.71, 111.39], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        // Add markers
        cctvList.forEach(cctv => {
            const color = cctv.status === 'online' ? '#38ef7d' : '#f45c43';
            const icon = L.divIcon({
                html: `<div style="background: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border: 3px solid ${color};">
                    <i class="fas fa-video" style="color: ${color}; font-size: 18px;"></i>
                </div>`,
                iconSize: [40, 40],
                iconAnchor: [20, 40]
            });

            L.marker([parseFloat(cctv.lat), parseFloat(cctv.lng)], { icon: icon })
                .addTo(map)
                .bindPopup(`
                    <div style="text-align: center;">
                        <strong>${cctv.nama}</strong><br>
                        <small>${cctv.alamat}</small><br>
                        <span style="color: ${color};">${cctv.status === 'online' ? '🟢' : '🔴'} ${cctv.status.toUpperCase()}</span><br>
                        <a href="?id=${cctv.id}" style="display: inline-block; margin-top: 10px; padding: 8px 16px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; text-decoration: none; border-radius: 8px;">
                            Lihat
                        </a>
                    </div>
                `);
        });

        if (activeCCTV) {
            map.setView([parseFloat(activeCCTV.lat), parseFloat(activeCCTV.lng)], 15);
        }

        // Detection Functions
        function showOriginal() {
            if (isDetectionActive) {
                stopDetection();
            }
            document.getElementById('youtubeFrame').style.display = 'block';
            document.getElementById('yoloStream').style.display = 'none';
            document.getElementById('btnOriginal').classList.add('active');
            document.getElementById('btnDetection').classList.remove('active');
        }

        async function showDetection() {
            const btnDetection = document.getElementById('btnDetection');
            btnDetection.disabled = true;
            btnDetection.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connecting...';
            
            // Test koneksi dulu
            try {
                console.log('🔍 Testing connection to YOLO API...');
                const healthCheck = await fetch(`${YOLO_API}/health`, {
                    method: 'GET',
                    mode: 'cors',
                    cache: 'no-cache'
                });
                
                if (!healthCheck.ok) {
                    throw new Error('Health check failed with status: ' + healthCheck.status);
                }
                
                const healthData = await healthCheck.json();
                console.log('✅ YOLO API is healthy:', healthData);
                
            } catch (healthError) {
                console.error('❌ Health check failed:', healthError);
                btnDetection.disabled = false;
                btnDetection.innerHTML = '<i class="fas fa-robot"></i> AI Detection';
                
                alert('❌ Tidak dapat terhubung ke YOLO API\n\n' +
                      'Troubleshooting:\n' +
                      '1. Pastikan Python server berjalan:\n' +
                      '   → Buka terminal baru\n' +
                      '   → Jalankan: python yolo_api_simple.py\n\n' +
                      '2. Cek apakah server aktif di browser:\n' +
                      '   → http://localhost:5000/health\n\n' +
                      '3. Gunakan test tool:\n' +
                      '   → Buka: test_yolo_api.html\n\n' +
                      'Error: ' + healthError.message);
                return;
            }
            
            // Jika health check OK, lanjut start detection
            try {
                btnDetection.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Starting...';
                
                const response = await fetch(
                    `${YOLO_API}/start/${activeCCTV.video_id}/${encodeURIComponent(activeCCTV.nama)}`,
                    {
                        method: 'GET',
                        mode: 'cors'
                    }
                );
                
                if (!response.ok) {
                    throw new Error('Start detection failed: ' + response.status);
                }
                
                const data = await response.json();
                console.log('Start detection response:', data);
                
                if (data.status === 'started' || data.status === 'already_running') {
                    document.getElementById('youtubeFrame').style.display = 'none';
                    document.getElementById('yoloStream').style.display = 'block';
                    
                    const streamUrl = `${YOLO_API}/stream/${activeCCTV.video_id}/${encodeURIComponent(activeCCTV.nama)}`;
                    console.log('Loading stream from:', streamUrl);
                    
                    document.getElementById('yoloStream').src = streamUrl;
                    
                    document.getElementById('btnOriginal').classList.remove('active');
                    document.getElementById('btnDetection').classList.add('active');
                    document.getElementById('detectionStatus').classList.add('active');
                    
                    isDetectionActive = true;
                    startStatsUpdate();
                    
                    console.log('✅ Detection started successfully');
                } else {
                    throw new Error(data.message || 'Unknown error');
                }
            } catch (error) {
                console.error('❌ Error starting detection:', error);
                alert('⚠️ Gagal memulai deteksi:\n' + error.message + 
                      '\n\nCek console (F12) untuk detail error');
            }
            
            btnDetection.disabled = false;
            btnDetection.innerHTML = '<i class="fas fa-robot"></i> AI Detection';
        }

        async function stopDetection() {
            if (!isDetectionActive) return;
            
            try {
                await fetch(`${YOLO_API}/stop/${activeCCTV.video_id}`);
                document.getElementById('yoloStream').src = '';
                document.getElementById('detectionStatus').classList.remove('active');
                isDetectionActive = false;
                stopStatsUpdate();
                resetStats();
            } catch (error) {
                console.error('Error:', error);
            }
        }

        function startStatsUpdate() {
            statsInterval = setInterval(async () => {
                if (!isDetectionActive) return;
                
                try {
                    const response = await fetch(`${YOLO_API}/stats/${activeCCTV.video_id}`);
                    const data = await response.json();
                    
                    if (data.counts) {
                        document.getElementById('count-car').textContent = data.counts.car || 0;
                        document.getElementById('count-motorbike').textContent = data.counts.motorbike || 0;
                        document.getElementById('count-bus').textContent = data.counts.bus || 0;
                        document.getElementById('count-truck').textContent = data.counts.truck || 0;
                    }
                } catch (error) {
                    console.error('Stats error:', error);
                }
            }, 2000);
        }

        function stopStatsUpdate() {
            if (statsInterval) {
                clearInterval(statsInterval);
                statsInterval = null;
            }
        }

        function resetStats() {
            document.getElementById('count-car').textContent = '0';
            document.getElementById('count-motorbike').textContent = '0';
            document.getElementById('count-bus').textContent = '0';
            document.getElementById('count-truck').textContent = '0';
        }

        // List functions
        document.getElementById('cardOnline').addEventListener('click', () => showList('online'));
        document.getElementById('cardOffline').addEventListener('click', () => showList('offline'));

        function showList(status) {
            const listContainer = document.getElementById('listContainer');
            const listContent = document.getElementById('listContent');
            const listTitle = document.getElementById('listTitle');
            const filtered = cctvList.filter(c => c.status === status);

            listTitle.innerHTML = `<i class="fas fa-list"></i> Daftar CCTV ${status === 'online' ? 'Online 🟢' : 'Offline 🔴'}`;

            let html = '';
            filtered.forEach(cctv => {
                html += `<li class="${status}">
                    <div style="flex: 1;">
                        <strong>${cctv.nama}</strong><br>
                        <small>${cctv.alamat}</small>
                    </div>
                    <a href="?id=${cctv.id}" style="padding: 8px 16px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; text-decoration: none; border-radius: 8px;">Lihat</a>
                </li>`;
            });

            if (filtered.length === 0) {
                html = `<p style="text-align: center; color: #64748b; padding: 20px;">Tidak ada CCTV ${status === 'online' ? 'online' : 'offline'}</p>`;
            }

            listContent.innerHTML = html;
            listContainer.classList.add('active');
            listContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function hideList() {
            document.getElementById('listContainer').classList.remove('active');
        }

        // Auto-stop detection on page unload
        window.addEventListener('beforeunload', () => {
            if (isDetectionActive) {
                stopDetection();
            }
        });

        // Check API health on load
        fetch(`${YOLO_API}/health`)
            .then(res => res.json())
            .then(data => {
                console.log('✅ YOLO API Connected:', data);
            })
            .catch(err => {
                console.warn('⚠️ YOLO API not available. Make sure to run: python yolo_api_simple.py');
            });

        // Auto-start YOLO detection for online CCTV
        if (activeCCTV.status === 'online') {
            console.log('🚀 Auto-starting YOLO detection for online CCTV:', activeCCTV.nama);
            setTimeout(() => {
                showDetection();
            }, 2000); // Delay 2 seconds to allow page and API check to complete
        }
    </script>
</body>
</html>