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
    <title>Data CCTV</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');
        body {
            font-family: "poppins" !important;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        
        .sidebar {
            font-family: "poppins";
            width: 230px;
            height: 130vh;
            background: #222;
            color: white;
            float: left;
            padding-top: 20px;
        }
        .sidebar a {
            font-family: "poppins";
            display: block;
            padding: 12px;
            color: white;
            text-decoration: none;
        }
        .sidebar a:hover { background: #444; }

        .content {
            margin-left: 230px;
            padding: 25px;
        }
        .title { 
            margin: 0; 
            font-family: "Poppins", sans-serif;
        }

        .top-bar {
            text-align: right;
            margin-bottom: 15px;
        }
        .top-bar a {
            background: #3498db;
            color: white;
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
        }
        .top-bar a:hover { opacity: 0.85; }

        table {
            width: 100%;
            border-collapse: collapse;
            border-color: black;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            /* border: 1px solid grey; */
        }
        th, td {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #34495e;
            color: white;
        }

        td img {
            border-radius: 8px;
        }

        .action-button {
            align-items: center;
            height: 100% !important;
        }

        .btn-edit,
        .btn-delete {
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 14px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            transition: 0.25s ease-in-out;
        }

        /* Tombol Update */
        .btn-edit {
            background: #2ecc71;
            color: white;
        }
        .btn-edit:hover {
            background: #27ae60;
            transform: translateY(-2px);
        }

        /* Tombol Delete */
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        .btn-delete:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        .btn-edit:hover, .btn-delete:hover {
            opacity: 0.85;
        }

        .stat-box {
            padding: 8px 12px;
            margin: 5px 10px;
            border-radius: 5px;
        }

        .logout-btn {
            background: #c0392b !important;
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

    <div class="stat-box"><i class="fa fa-camera"></i> Total CCTV: <b><?= $totalCCTV ?></b></div>
    <div class="stat-box"><i class="fa fa-circle" style="color:green;"></i> Online: <b><?= $online ?></b></div>
    <div class="stat-box"><i class="fa fa-circle" style="color:red;"></i> Offline: <b><?= $offline ?></b></div>

    <hr style="border-color:#555;">

    <a href="logout.php" class="logout-btn"><i class="fa fa-right-from-bracket"></i> Logout</a>
</div>

<div class="content">
    <h1 class="title">Data CCTV</h1>

    <div class="top-bar">
        <a href="tambah.php">+ Tambah Data CCTV</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nama</th>
                <th>Video ID</th>
                <th>Thumbnail</th>
                <th>Alamat</th>
                <th>Koordinat</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody>
            <?php
            mysqli_data_seek($result, 0); // reset pointer

            while ($row = $result->fetch_assoc()):
                $status = cekStatusYoutube($row['video_id'], $apiKey);
            ?>
                <tr>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= htmlspecialchars($row['video_id']) ?></td>
                    <td><img src="uploads/<?= htmlspecialchars($row['thumbnail']) ?>" width="100"></td>
                    <td><?= htmlspecialchars($row['alamat']) ?></td>
                    <td><?= $row['lat'] ?>, <?= $row['lng'] ?></td>
                    <td>
                        <span style="color:<?= $status == 'online' ? 'green' : 'red' ?>; font-weight:bold;">
                            <?= $status ?>
                        </span>
                    </td>

                    <td class="" >
                        <div class="action-button" style="flex-direction: row; display: flex; gap: 0.3em; justify-content: center; height: 100%;">
                            <a class="btn-edit" href="update.php?id=<?= $row['id'] ?>" style="align-self: center;">Update</a>
                            <a class="btn-delete" href="delete.php?id=<?= $row['id'] ?>" onclick="return confirm('Hapus data ini?')">Hapus</a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>

    </table>
</div>

</body>
</html>