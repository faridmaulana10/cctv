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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard CCTV</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- font -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body { 
        font-family: "Poppins", sans-serif !important; 
        margin: 0; 
        padding: 0; 
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }

    /* Sidebar Modern */
    .sidebar { 
        width: 280px; 
        height: 100vh; 
        background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
        color: white; 
        position: fixed;
        left: 0;
        top: 0;
        padding-top: 20px; 
        font-family: "Poppins", sans-serif;
        box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        overflow-y: auto;
        z-index: 1000;
    }

    .sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 10px;
    }

    .sidebar > h2 {
        text-align: center;
        padding: 0 20px 20px;
        border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        margin: 0 0 20px 0;
        font-size: 1.3rem;
        font-weight: 700;
        color: white;
    }

    .sidebar-logo {
        width: 80px;
        height: 80px;
        margin: 0 auto 15px;
        display: block;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .sidebar a { 
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 20px; 
        color: rgba(255, 255, 255, 0.8); 
        text-decoration: none;
        transition: all 0.3s ease;
        border-left: 3px solid transparent;
        font-weight: 500;
    }

    .sidebar a:hover { 
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border-left-color: #667eea;
        padding-left: 25px;
    }

    .sidebar a i {
        width: 20px;
        text-align: center;
        font-size: 1.1rem;
    }

    .sidebar hr {
        border: none;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        margin: 15px 0;
    }

    .sidebar h3 {
        padding: 10px 20px;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: rgba(255, 255, 255, 0.5);
        font-weight: 600;
    }

    .logout-btn {
        margin: 20px;
        background: linear-gradient(135deg, #ef4444, #dc2626) !important;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 14px 20px;
        text-decoration: none;
        text-align: center;
        border-radius: 12px;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        transition: all 0.3s ease;
        border: none;
        border-left: 3px solid transparent;
    }

    .logout-btn:hover { 
        background: linear-gradient(135deg, #dc2626, #b91c1c) !important;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        padding-left: 20px;
    }

    /* Content Area */
    .content { 
        margin-left: 280px; 
        padding: 30px 40px;
        min-height: 100vh;
    }

    h1 {
        font-family: "Poppins", sans-serif;
        margin: 0 0 30px 0 !important;
        font-size: 2rem;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    h1 i {
        color: #667eea;
        font-size: 1.8rem;
    }

    /* Card Container */
    .card-container { 
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 25px; 
        margin-bottom: 35px;
        font-family: "Poppins", sans-serif;
    }

    .card { 
        background: white; 
        padding: 25px 30px; 
        border-radius: 20px; 
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); 
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s ease;
        border: 1px solid rgba(0, 0, 0, 0.05);
        position: relative;
        overflow: hidden;
        cursor: pointer;
    }

    .card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 5px;
        height: 100%;
        background: linear-gradient(180deg, #667eea, #764ba2);
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
    }

    .card h3 {
        font-size: 1rem;
        font-weight: 600;
        color: #64748b;
        margin: 0;
    }

    .value {
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 15px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        width: 70px;
        height: 70px;
        font-size: 1.8rem;
        font-weight: 700;
        color: white;
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    }

    .card:nth-child(2)::before {
        background: linear-gradient(180deg, #10b981, #059669);
    }

    .card:nth-child(2) .value {
        background: linear-gradient(135deg, #10b981, #059669);
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
    }

    .card:nth-child(3)::before {
        background: linear-gradient(180deg, #ef4444, #dc2626);
    }

    .card:nth-child(3) .value {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
    }

    /* Map Section */
    h2 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    h2 i {
        color: #667eea;
    }

    .map-wrapper {
        width: 100%;
        height: 550px;
        border: none;
        border-radius: 20px;
        overflow: hidden;
        background: white;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }

    /* Stats in Sidebar */
    .sidebar a:not(.logout-btn) {
        font-size: 0.95rem;
    }

    .sidebar a strong {
        margin-left: auto;
        background: rgba(255, 255, 255, 0.1);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.9rem;
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .sidebar {
            width: 250px;
        }
        
        .content {
            margin-left: 250px;
            padding: 20px;
        }

        .card-container {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .sidebar {
            width: 100%;
            height: auto;
            position: relative;
        }

        .content {
            margin-left: 0;
        }

        h1 {
            font-size: 1.5rem;
        }

        .card {
            flex-direction: column;
            text-align: center;
            gap: 15px;
        }

        .value {
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
        }
    }

    /* Loading Animation */
    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }

    .card:hover .value {
        animation: pulse 2s ease-in-out infinite;
    }

    /* List Container */
    .list-container {
        display: none;
        background: white;
        border-radius: 20px;
        padding: 20px;
        max-height: 400px;
        overflow-y: auto;
        margin-bottom: 35px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .list-container.active {
        display: block;
        animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .list-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e2e8f0;
    }

    .list-header h3 {
        margin: 0;
        color: #1e293b;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .close-btn-list {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        font-family: "Poppins", sans-serif;
    }

    .close-btn-list:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
    }

    .list-container ul {
        list-style: none;
        padding: 0;
        margin: 0;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 15px;
    }

    .list-container li {
        padding: 15px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .list-container li:hover {
        border-color: #667eea;
        background: linear-gradient(135deg, #667eea05, #764ba205);
        transform: translateX(5px);
    }

    .list-container li i {
        font-size: 1.5rem;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }

    .list-container li.online i {
        background: linear-gradient(135deg, #10b981, #059669);
    }

    .list-container li.offline i {
        background: linear-gradient(135deg, #ef4444, #dc2626);
    }

    .list-item-content {
        flex: 1;
    }

    .list-item-content strong {
        display: block;
        color: #1e293b;
        font-size: 1rem;
        margin-bottom: 4px;
    }

    .list-item-content small {
        color: #64748b;
        font-size: 0.85rem;
    }

    .list-container li a {
        color: white;
        background: linear-gradient(135deg, #667eea, #764ba2);
        padding: 8px 16px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.3s ease;
        white-space: nowrap;
    }

    .list-container li a:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
    }

    .list-container::-webkit-scrollbar {
        width: 8px;
    }

    .list-container::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
    }

    .list-container::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-radius: 10px;
    }

   /* Footer */
    .footer {
        margin-left: 280px;
        padding: 2rem 40px;
        background: #1e293b;
        border-top: 1px solid #e2e8f0;
        margin-top: 3rem;
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

    .footer-links a i {
        font-size: 0.8rem;
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

    @media (max-width: 1024px) {
        .footer {
            margin-left: 250px;
        }

        .footer-content {
            grid-template-columns: 1fr;
            gap: 2rem;
        }
    }

    @media (max-width: 768px) {
        .footer {
            margin-left: 0;
            padding: 2rem 20px;
        }
    }
</style>
</head>
<body>

<div class="sidebar">
    <!-- Logo -->
    <img src="uploads/logo.png" alt="Logo CCTV" class="sidebar-logo" onerror="this.style.display='none'">
    <h2>📡 CCTV PANEL</h2>

    <a href="dashboard.php"><i class="fa fa-chart-line"></i> Dashboard</a>
    <a href="maps_preview_admin.php"><i class="fa fa-map"></i> Peta CCTV</a>
    <a href="list.php"><i class="fa fa-video"></i> Data CCTV</a>

    <hr>
    <h3>STATISTIK</h3>
    <a><i class="fa fa-camera"></i> Jumlah CCTV <strong><?= $totalCCTV ?></strong></a>
    <a><i class="fa fa-circle" style="color:#10b981;"></i> Online <strong><?= $online ?></strong></a>
    <a><i class="fa fa-circle" style="color:#ef4444;"></i> Offline <strong><?= $offline ?></strong></a>

    <!-- Tombol Logout -->
    <a href="logout.php" class="logout-btn" onclick="return confirm('Yakin ingin logout?');">
        <i class="fa fa-sign-out-alt"></i> Logout
    </a>
</div>

<div class="content">
    <h1><i class="fas fa-tachometer-alt"></i> Dashboard Monitoring CCTV</h1>

    <div class="card-container">
        <div class="card">
            <h3>Total CCTV</h3>
            <div class="value"><?= $totalCCTV ?></div>
        </div>

        <div class="card" id="cardOnline" style="cursor: pointer;">
            <h3>CCTV Online</h3>
            <div class="value"><?= $online ?></div>
        </div>

        <div class="card" id="cardOffline" style="cursor: pointer;">
            <h3>CCTV Offline</h3>
            <div class="value"><?= $offline ?></div>
        </div>
    </div>

    <div class="list-container" id="listContainer">
        <div class="list-header">
            <h3 id="listTitle"><i class="fas fa-list"></i> Daftar CCTV</h3>
            <button class="close-btn-list" onclick="hideList()">
                <i class="fas fa-times"></i> Tutup
            </button>
        </div>
        <ul id="listContent"></ul>
    </div>

    <h2><i class="fas fa-map-marked-alt"></i> Peta CCTV</h2>
    <div class="map-wrapper">
        <?php include 'maps_only.php'; ?>
    </div>
</div>

<!-- Footer -->
<footer class="footer">
    <div class="footer-content">
        <div class="footer-section">
            <!-- Logo Footer -->
            <!-- <img src="uploads/logo.png" alt="Logo CCTV" class="footer-logo" onerror="this.style.display='none'"> -->
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

<script>
// Data CCTV dari PHP
const cctvDevices = <?= json_encode($devices) ?>;

// Event listener untuk card Online
document.getElementById('cardOnline').addEventListener('click', () => showList('online'));

// Event listener untuk card Offline
document.getElementById('cardOffline').addEventListener('click', () => showList('offline'));

function showList(status) {
    const listContainer = document.getElementById('listContainer');
    const listContent = document.getElementById('listContent');
    const listTitle = document.getElementById('listTitle');
    const filtered = cctvDevices.filter(c => c.status === status);

    const titleText = status === 'online' ? 'Daftar CCTV Online 🟢' : 'Daftar CCTV Offline 🔴';
    listTitle.innerHTML = `<i class="fas fa-list"></i> ${titleText}`;

    let html = '';
    filtered.forEach(cctv => {
        html += `<li class="${status}">
            <div class="list-item-content">
                <strong>${cctv.nama}</strong>
                <small>${cctv.alamat}</small>
            </div>
            <a href="maps_preview_admin.php?id=${cctv.id}">Lihat</a>
        </li>`;
    });

    if (filtered.length === 0) {
        html = '<p style="text-align: center; color: #64748b; padding: 20px;">Tidak ada CCTV ' + (status === 'online' ? 'online' : 'offline') + '</p>';
    }

    listContent.innerHTML = html;
    listContainer.classList.add('active');
    
    // Scroll to list
    listContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function hideList() {
    document.getElementById('listContainer').classList.remove('active');
}
</script>

</body>
</html>