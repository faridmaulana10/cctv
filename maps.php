<?php
// API Key YouTube
$apiKey = 'AIzaSyAPnGJS6r5Q2H_235Szh3bxPrQBmvckM8k';

// Fungsi cek status YouTube LIVE
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

// Load data CCTV dari JSON
$jsonFile = 'cctv_data.json';
$cctvList = [];

if (file_exists($jsonFile)) {
    $jsonString = file_get_contents($jsonFile);
    $cctvList = json_decode($jsonString, true);
    
    // Tambahkan status untuk setiap CCTV
    foreach ($cctvList as &$cctv) {
        $cctv['status'] = checkYoutubeStatus($cctv['video_id'], $apiKey);
    }
    unset($cctv);
} else {
    die("Error: File cctv_data.json tidak ditemukan!");
}

// Hitung statistik
$totalCCTV = count($cctvList);
$onlineCount = count(array_filter($cctvList, fn($c) => $c['status'] === 'online'));
$offlineCount = $totalCCTV - $onlineCount;

// CCTV aktif (default yang pertama)
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
    <title>CCTV Monitoring - Kabupaten Rembang</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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

        /* Header Modern */
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

        .nav-btn {
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

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
        }

        /* Container Utama */
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Stats Cards */
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

        .stat-icon.total {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .stat-icon.online {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            color: white;
        }

        .stat-icon.offline {
            background: linear-gradient(135deg, #eb3349, #f45c43);
            color: white;
        }

        .stat-info h3 {
            font-size: 2rem;
            color: #2c3e50;
            font-weight: 700;
        }

        .stat-info p {
            color: #7f8c8d;
            font-size: 1rem;
        }

        /* Main Content Grid */
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        /* Video Section */
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
        }

        .video-header h2 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
        }

        .video-header .location {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            background: #000;
        }

        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        /* Report Section */
        .report-section {
            padding: 2rem;
            background: #f8f9fa;
        }

        .report-section h3 {
            color: #1e293b;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .report-section h3 i {
            color: #ef4444;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #1e293b;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .form-group label i {
            margin-right: 0.5rem;
            color: #667eea;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .btn-submit-report {
            width: 100%;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-family: 'Poppins', sans-serif;
        }

        .btn-submit-report:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
        }

        .btn-submit-report:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        /* Sidebar CCTV List */
        .cctv-sidebar {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-height: 800px;
            overflow-y: auto;
        }

        .cctv-sidebar h3 {
            margin-bottom: 1.5rem;
            color: #2c3e50;
            font-size: 1.2rem;
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

        .cctv-info {
            flex: 1;
        }

        .cctv-info h4 {
            font-size: 0.95rem;
            margin-bottom: 0.3rem;
        }

        .cctv-info p {
            font-size: 0.8rem;
            opacity: 0.8;
            margin-bottom: 0.2rem;
        }

        /* Map Section */
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

        .map-header h2 {
            font-size: 1.3rem;
        }

        #map {
            height: 500px;
            width: 100%;
        }

        /* Footer */
        .footer {
            padding: 2rem 40px;
            background: #1e293b;
        }

        .footer-content {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 3rem;
            max-width: 1400px;
            margin: 0 auto 2rem;
        }

        .footer-section h3 {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-section h3 i {
            color: #667eea;
        }

        .footer-section p {
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.8;
            font-size: 0.9rem;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 0.75rem;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .footer-links a:hover {
            color: #667eea;
            padding-left: 5px;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .social-links a:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 0.9rem;
        }

        .footer-bottom strong {
            color: #667eea;
        }

        /* Scrollbar */
        .cctv-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .cctv-sidebar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .cctv-sidebar::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 10px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .main-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }

        @media (max-width: 768px) {
            .footer {
                padding: 2rem 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo-section">
                <div class="logo-icon">
                    <i class="fas fa-video"></i>
                </div>
                <div class="logo-text">
                    <h1>CCTV Monitoring System</h1>
                    <p>Kabupaten Rembang</p>
                </div>
            </div>
            <a href="home.php" class="nav-btn">
                <i class="fas fa-home"></i> Home
            </a>
        </div>
    </header>

    <!-- Main Container -->
    <div class="container">
        <!-- Stats Cards -->
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
            <div class="stat-card">
                <div class="stat-icon online">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $onlineCount ?></h3>
                    <p>CCTV Online</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon offline">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $offlineCount ?></h3>
                    <p>CCTV Offline</p>
                </div>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="main-grid">
            <!-- Video Section -->
            <div class="video-section">
                <div class="video-header">
                    <h2><?= htmlspecialchars($activeCCTV['nama']) ?></h2>
                    <div class="location">
                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($activeCCTV['alamat']) ?>
                    </div>
                    <div class="status-badge">
                        <i class="fas fa-circle" style="color: <?= $activeCCTV['status'] === 'online' ? '#38ef7d' : '#f45c43' ?>;"></i>
                        <?= strtoupper($activeCCTV['status']) ?>
                    </div>
                </div>
                <div class="video-container">
                    <iframe 
                        src="https://www.youtube.com/embed/<?= htmlspecialchars($activeCCTV['video_id']) ?>?autoplay=1&mute=1" 
                        allow="autoplay; encrypted-media" 
                        allowfullscreen>
                    </iframe>
                </div>

                <!-- Report Section -->
                <div class="report-section">
                    <h3>
                        <i class="fas fa-exclamation-triangle"></i>
                        Laporkan Masalah
                    </h3>
                    
                    <div id="reportAlert"></div>

                    <form id="reportForm">
                        <input type="hidden" name="cctv_id" value="<?= $activeCCTV['id'] ?>">
                        <input type="hidden" name="cctv_name" value="<?= htmlspecialchars($activeCCTV['nama']) ?>">
                        
                        <div class="form-group">
                            <label>
                                <i class="fas fa-user"></i> Nama Pelapor <span style="color: #ef4444;">*</span>
                            </label>
                            <input type="text" 
                                   name="reporter_name" 
                                   class="form-control" 
                                   placeholder="Masukkan nama Anda"
                                   maxlength="100"
                                   required>
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-comment-alt"></i> Isi Laporan <span style="color: #ef4444;">*</span>
                            </label>
                            <textarea name="report_text" 
                                      class="form-control" 
                                      placeholder="Jelaskan masalah yang Anda temukan (min. 10 karakter, max. 1000 karakter)"
                                      maxlength="1000"
                                      required></textarea>
                            <small style="color: #64748b; margin-top: 0.25rem; display: block;">
                                <i class="fas fa-info-circle"></i> 
                                Contoh: "CCTV tidak berfungsi", "Ada vandalisme", dll.
                            </small>
                        </div>

                        <button type="submit" class="btn-submit-report">
                            <i class="fas fa-paper-plane"></i>
                            Kirim Laporan
                        </button>
                    </form>
                </div>
            </div>

            <!-- CCTV Sidebar -->
            <div class="cctv-sidebar">
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

        <!-- Map Section -->
        <div class="map-section">
            <div class="map-header">
                <h2><i class="fas fa-map"></i> Peta Lokasi CCTV</h2>
            </div>
            <div id="map"></div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>
                    <i class="fas fa-video"></i>
                    CCTV Monitoring System
                </h3>
                <p>
                    Sistem monitoring CCTV modern untuk Kabupaten Rembang. 
                    Memantau keamanan dan lalu lintas kota secara real-time dengan teknologi terkini.
                </p>
                <div class="social-links">
                    <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" title="TikTok"><i class="fab fa-tiktok"></i></a>
                    <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" title="Lokasi"><i class="fas fa-map-marker-alt"></i></a>
                </div>
            </div>

            <div class="footer-section">
                <h3>
                    <i class="fas fa-clock"></i>
                    Waktu Pelayanan
                </h3>
                <div class="schedule-item">
                    <p><span class="schedule-day">Senin - Kamis</span>
                    <span class="schedule-time">07:30 - 16:00</span></p>
                </div>
                <div class="schedule-item">
                    <p><span class="schedule-day">Jumat</span>
                    <span class="schedule-time">07:30 - 11:00</span></p>
                </div>
                <div class="schedule-item">
                    <p><span class="schedule-day">Sabtu - Minggu</span>
                    <span class="schedule-closed">LIBUR</span></p>
                </div>
            </div>

            <div class="footer-section">
                <h3>
                    <i class="fas fa-info-circle"></i>
                    Informasi
                </h3>
                <ul class="footer-links">
                    <li><a href="https://dinkominfo.rembangkab.go.id"><i class="fas fa-chevron-right"></i> Tentang Kami</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>
                &copy; <?= date('Y') ?> <strong>CCTV Monitoring System</strong> - Kabupaten Rembang. 
                All Rights Reserved. Made with <i class="fas fa-heart" style="color: #ef4444;"></i> by Tim Pengembang
            </p>
        </div>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Data CCTV dari PHP
        const cctvList = <?= json_encode($cctvList) ?>;

        // Initialize Map
        const map = L.map('map').setView([-6.71, 111.39], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Add markers
        cctvList.forEach(cctv => {
            const iconColor = cctv.status === 'online' ? '#38ef7d' : '#f45c43';
            
            const icon = L.divIcon({
                html: `<div style="
                    background: white;
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    border: 3px solid ${iconColor};
                ">
                    <i class="fas fa-video" style="color: ${iconColor}; font-size: 18px;"></i>
                </div>`,
                className: 'custom-marker',
                iconSize: [40, 40],
                iconAnchor: [20, 40],
                popupAnchor: [0, -40]
            });

            const marker = L.marker([parseFloat(cctv.lat), parseFloat(cctv.lng)], { icon: icon })
                .addTo(map)
                .bindPopup(`
                    <div style="text-align: center; font-family: 'Poppins', sans-serif;">
                        <strong style="color: #1e293b;">${cctv.nama}</strong><br>
                        <small style="color: #64748b;">${cctv.alamat}</small><br>
                        <span style="color: ${iconColor}; font-weight: 600; margin-top: 8px; display: inline-block;">
                            ${cctv.status === 'online' ? '🟢' : '🔴'} ${cctv.status.toUpperCase()}
                        </span><br>
                        <a href="?id=${cctv.id}" style="display: inline-block; margin-top: 10px; padding: 8px 16px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; text-decoration: none; border-radius: 8px; font-size: 0.85rem; font-weight: 600;">
                            <i class="fas fa-play"></i> Lihat CCTV
                        </a>
                    </div>
                `, {
                    maxWidth: 300
                });
        });

        // Highlight active CCTV on map
        const activeCCTV = <?= json_encode($activeCCTV) ?>;
        if (activeCCTV) {
            map.setView([parseFloat(activeCCTV.lat), parseFloat(activeCCTV.lng)], 15);
        }

        // Handle Report Form Submission
        document.getElementById('reportForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const alertDiv = document.getElementById('reportAlert');
            
            // Disable button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
            
            // Get form data
            const formData = new FormData(this);
            
            try {
                const response = await fetch('submit_report.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Show success message
                    alertDiv.innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            ${result.message}
                        </div>
                    `;
                    
                    // Reset form
                    this.reset();
                    
                    // Remove alert after 5 seconds
                    setTimeout(() => {
                        alertDiv.innerHTML = '';
                    }, 5000);
                } else {
                    // Show error message
                    alertDiv.innerHTML = `
                        <div class="alert alert-error">
                            <i class="fas fa-times-circle"></i>
                            ${result.message}
                        </div>
                    `;
                    
                    // Remove alert after 5 seconds
                    setTimeout(() => {
                        alertDiv.innerHTML = '';
                    }, 5000);
                }
            } catch (error) {
                console.error('Error:', error);
                alertDiv.innerHTML = `
                    <div class="alert alert-error">
                        <i class="fas fa-times-circle"></i>
                        Terjadi kesalahan. Silakan coba lagi.
                    </div>
                `;
                
                setTimeout(() => {
                    alertDiv.innerHTML = '';
                }, 5000);
            } finally {
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Kirim Laporan';
            }
        });
    </script>
</body>
</html>