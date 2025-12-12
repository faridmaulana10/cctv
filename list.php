<?php
include 'koneksi.php';
session_start();

// Cek login
if (!isset($_SESSION['admin_logged'])) {
    header("Location: login.php");
    exit;
}

// === API Key YouTube ===
$apiKey = 'AIzaSyAPnGJS6r5Q2H_235Szh3bxPrQBmvckM8k';

// Ambil data CCTV dari database
$result = $conn->query("SELECT * FROM cctv_data");

// Hitung statistik realtime
$totalCCTV = 0;
$online = 0;
$offline = 0;

// Fungsi cek status Youtube
function cekStatusYoutube($videoId, $apiKey)
{
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

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data CCTV</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Poppins", sans-serif !important;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        /* Sidebar Modern - Same as dashboard.php */
        .sidebar {
            font-family: "Poppins", sans-serif;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            padding-top: 20px;
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
        text-align: left;
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
            font-family: "Poppins", sans-serif;
            display: block;
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

        .sidebar a.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border-left-color: #667eea;
        }

        .sidebar a i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
            margin-right: 12px;
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

        .sidebar a strong {
            margin-left: auto;
            background: rgba(255, 255, 255, 0.1);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            float: right;
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

        .title {
            margin: 0 0 30px 0;
            font-family: "Poppins", sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .title i {
            color: #667eea;
            font-size: 1.8rem;
        }

        /* Top Bar */
        .top-bar {
            text-align: right;
            margin-bottom: 25px;
        }

        .top-bar a {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .top-bar a:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        /* Table Container */
        .table-container {
            background: white;
            width: 100%;
            border-radius: 20px;
            /* overflow-x: auto; */
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        table {
            width: 100%;
            /* transform: scale(90%); */
            border-collapse: collapse;
            background: white;
            /* font-size: 0.8em; */
            overflow: hix
        }
        
        th, td {
            padding: 16px 12px;
            text-align: center;
            vertical-align: middle;
        }

        td{
            font-size: 0.9em;
        }

        th {
            font-size: 0.8em;
        }

        th {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            /* font-size: 0.85rem; */
            letter-spacing: 0.5px;
        }

        tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        tbody tr:hover {
            background: linear-gradient(135deg, #667eea05, #764ba205);
            transform: scale(1.01);
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        td {
            color: #475569;
            /* font-size: 0.95rem; */
        }

        td img {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        td img:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }

        /* Action Buttons */
        .action-button {
            display: flex;
            gap: 8px;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            /* min-width: 100px; */
        }

        .btn-edit,
        .btn-delete {
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 0.8rem;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            white-space: nowrap;
            flex: 0 0 auto;
        }

        .btn-edit {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
        }

        .btn-delete {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .status-badge.online {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }

        .status-badge.offline {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
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

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 250px;
            }

            .content {
                margin-left: 250px;
                padding: 20px;
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

            .title {
                font-size: 1.5rem;
            }

            table {
                font-size: 0.85rem;
            }

            th, td {
                padding: 12px;
            }

            .action-button {
                flex-direction: column;
                gap: 8px;
            }

            .btn-edit, .btn-delete {
                width: 100%;
                justify-content: center;
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
    <a href="maps_preview_admin.php"><i class="fa fa-map"></i> User Preview</a>
    <a href="list.php" class="active"><i class="fa fa-video"></i> Data CCTV</a>

    <hr>

    <h3>STATISTIK</h3>

    <?php
    // Hitung statistik realtime dari DB
    $stat = $conn->query("SELECT video_id FROM cctv_data");

    while ($row = $stat->fetch_assoc()) {
        $totalCCTV++;
        $status = cekStatusYoutube($row['video_id'], $apiKey);
        if ($status == "online") $online++;
        else $offline++;
    }
    ?>

    <a><i class="fa fa-camera"></i> Jumlah CCTV <strong><?= $totalCCTV ?></strong></a>
    <a><i class="fa fa-circle" style="color:#10b981;"></i> Online <strong><?= $online ?></strong></a>
    <a><i class="fa fa-circle" style="color:#ef4444;"></i> Offline <strong><?= $offline ?></strong></a>

    <!-- Tombol Logout -->
    <a href="logout.php" class="logout-btn" onclick="return confirm('Yakin ingin logout?');">
        <i class="fa fa-sign-out-alt"></i> Logout
    </a>
</div>

<div class="content">
    <h1 class="title"><i class="fas fa-list"></i> Data CCTV</h1>

    <div class="top-bar">
        <a href="tambah.php">
            <i class="fas fa-plus-circle"></i> Tambah Data CCTV
        </a>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th><i class="fas fa-hashtag"></i> No</th>
                    <th><i class="fas fa-tag"></i> Nama</th>
                    <th><i class="fas fa-youtube"></i> Video ID</th>
                    <th><i class="fas fa-image"></i> Thumbnail</th>
                    <th><i class="fas fa-map-marker-alt"></i> Alamat</th>
                    <th><i class="fas fa-map-pin"></i> Koordinat</th>
                    <th><i class="fas fa-signal"></i> Status</th>
                    <th><i class="fas fa-cog"></i> Aksi</th>
                </tr>
            </thead>

            <tbody>
                <?php
                mysqli_data_seek($result, 0); // reset pointer
                $no = 1;

                while ($row = $result->fetch_assoc()):
                    $status = cekStatusYoutube($row['video_id'], $apiKey);
                    $statusClass = $status == 'online' ? 'online' : 'offline';
                    $statusIcon = $status == 'online' ? 'fa-circle-check' : 'fa-circle-xmark';
                ?>
                    <tr>
                        <td><strong><?= $no++ ?></strong></td>
                        <td><strong><?= htmlspecialchars($row['nama']) ?></strong></td>
                        <td><code style="background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-size: 0.85rem;"><?= htmlspecialchars($row['video_id']) ?></code></td>
                        <td><img src="uploads/<?= htmlspecialchars($row['thumbnail']) ?>" width="120" height="80" style="object-fit: cover;"></td>
                        <td><?= htmlspecialchars($row['alamat']) ?></td>
                        <td>
                            <small style="color: #64748b;">
                                <i class="fas fa-location-dot"></i> <?= $row['lat'] ?>, <?= $row['lng'] ?>
                            </small>
                        </td>
                        <td>
                            <span class="status-badge <?= $statusClass ?>">
                                <i class="fas <?= $statusIcon ?>"></i> <?= ucfirst($status) ?>
                            </span>
                        </td>

                        <td>
                            <div class="action-button">
                                <a class="btn-edit" href="update.php?id=<?= $row['id'] ?>">
                                    <i class="fas fa-edit"></i>
                                    <span>Update</span>
                                </a>
                                <a class="btn-delete" href="delete.php?id=<?= $row['id'] ?>" onclick="return confirm('Hapus data ini?')">
                                    <i class="fas fa-trash"></i>
                                    <span>Hapus</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>

        </table>
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
                <a href="https://www.facebook.com/ghost" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="https://www.tiktok.com/@rembangkab" title="TikTok"><i class="fab fa-tiktok"></i></a>
                <a href="https://www.instagram.com/rembangkab" title="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="https://maps.app.goo.gl/EC8tH7vLzesceoes9" title="Lokasi"><i class="fas fa-map-marker-alt"></i></a>
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

</body>
</html>