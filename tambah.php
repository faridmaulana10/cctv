<?php 
include 'koneksi.php';
session_start();

if (!isset($_SESSION['admin_logged'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Data CCTV</title>
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
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .top-nav {
            max-width: 900px;
            margin: 0 auto 30px;
            padding: 1.5rem 2rem;
            background: white;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border-radius: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-title {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #1e293b;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .nav-title i {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-back {
            text-decoration: none;
            color: white;
            background: linear-gradient(135deg, #64748b, #475569);
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .nav-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(100, 116, 139, 0.3);
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2.5rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        h2 {
            text-align: center;
            margin-bottom: 2rem;
            color: #1e293b;
            font-size: 2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        h2 i {
            color: #667eea;
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            font-weight: 600;
            display: block;
            margin-bottom: 0.5rem;
            color: #1e293b;
            font-size: 0.95rem;
        }

        label i {
            margin-right: 0.5rem;
            color: #667eea;
        }

        .required {
            color: #ef4444;
        }

        input[type="text"],
        textarea,
        input[type="file"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        input[type="file"] {
            cursor: pointer;
            padding: 12px;
        }

        input[type="file"]::file-selector-button {
            padding: 8px 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-right: 12px;
            transition: all 0.3s ease;
        }

        input[type="file"]::file-selector-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .hint {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .hint i {
            color: #3b82f6;
        }

        button {
            width: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 14px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            border-radius: 12px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: 'Poppins', sans-serif;
            margin-top: 1rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        button:active {
            transform: translateY(0);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .top-nav {
                flex-direction: column;
                gap: 1rem;
            }

            .container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>

<!-- Top navigation -->
<div class="top-nav">
    <div class="nav-title">
        <i class="fas fa-plus-circle"></i>
        <span>Tambah Data CCTV</span>
    </div>
    <a href="list.php" class="nav-back">
        <i class="fas fa-arrow-left"></i> Kembali ke Data CCTV
    </a>
</div>

<div class="container">
    <?php
    if (isset($_POST['submit'])) {
        $nama = $_POST['nama'];
        $video_id = $_POST['video_id'];
        $alamat = $_POST['alamat'];
        $lat = $_POST['lat'];
        $lng = $_POST['lng'];

        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir);
        $fileName = basename($_FILES["thumbnail"]["name"]);
        $targetFile = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $fileSize = $_FILES["thumbnail"]["size"];

        // Validasi tipe file
        if ($fileType != "jpg" && $fileType != "jpeg") {
            echo '<div class="alert alert-error"><i class="fas fa-times-circle"></i> Hanya file JPG/JPEG yang diperbolehkan.</div>';
        } 
        // Validasi ukuran file (3MB = 3 * 1024 * 1024 bytes)
        elseif ($fileSize > 3145728) {
            echo '<div class="alert alert-error"><i class="fas fa-times-circle"></i> Ukuran file maksimal 3MB. Ukuran file Anda: ' . round($fileSize / 1048576, 2) . 'MB</div>';
        }
        elseif (move_uploaded_file($_FILES["thumbnail"]["tmp_name"], $targetFile)) {
            $stmt = $conn->prepare("INSERT INTO cctv_data (nama, video_id, thumbnail, alamat, lat, lng) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $nama, $video_id, $fileName, $alamat, $lat, $lng);
            if ($stmt->execute()) {
                include "update_json.php";
                echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Data berhasil ditambahkan!</div>';
            } else {
                echo '<div class="alert alert-error"><i class="fas fa-times-circle"></i> Gagal menyimpan ke database.</div>';
            }
            $stmt->close();
        } else {
            echo '<div class="alert alert-error"><i class="fas fa-times-circle"></i> Gagal mengunggah gambar.</div>';
        }
    }
    ?>

    <h2>
        <i class="fas fa-video"></i>
        Form Tambah CCTV
    </h2>

    <form action="" method="post" enctype="multipart/form-data">
        <div class="form-row">
            <div class="form-group">
                <label>
                    <i class="fas fa-tag"></i> Nama CCTV <span class="required">*</span>
                </label>
                <input type="text" name="nama" placeholder="Contoh: CCTV 1 - Taman Lasem" required>
            </div>

            <div class="form-group">
                <label>
                    <i class="fab fa-youtube"></i> Video ID <span class="required">*</span>
                </label>
                <input type="text" name="video_id" placeholder="Contoh: levNOPvGOmk" required>
                <div class="hint">
                    <i class="fas fa-info-circle"></i>
                    ID dari URL YouTube
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>
                <i class="fas fa-image"></i> Thumbnail (JPG) <span class="required">*</span>
            </label>
            <input type="file" name="thumbnail" accept="image/jpeg" required>
            <div class="hint">
                <i class="fas fa-info-circle"></i>
                Ukuran maksimal 3MB, format JPG/JPEG
            </div>
        </div>

        <div class="form-group">
            <label>
                <i class="fas fa-map-marker-alt"></i> Alamat <span class="required">*</span>
            </label>
            <textarea name="alamat" placeholder="Masukkan alamat lengkap lokasi CCTV" required></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>
                    <i class="fas fa-location-arrow"></i> Latitude <span class="required">*</span>
                </label>
                <input type="text" name="lat" placeholder="Contoh: -6.697773" required>
            </div>

            <div class="form-group">
                <label>
                    <i class="fas fa-location-arrow"></i> Longitude <span class="required">*</span>
                </label>
                <input type="text" name="lng" placeholder="Contoh: 111.447836" required>
            </div>
        </div>

        <button type="submit" name="submit">
            <i class="fas fa-save"></i>
            Simpan Data CCTV
        </button>
    </form>
</div>

</body>
</html>