<?php
include 'koneksi.php';
session_start();

if (!isset($_SESSION['admin_logged'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'];
$result = $conn->query("SELECT * FROM cctv_data WHERE id = $id");
$data = $result->fetch_assoc();

if (!$data) {
    echo "Data tidak ditemukan!";
    exit;
}

if (isset($_POST['submit'])) {
    $nama = $_POST['nama'];
    $video_id = $_POST['video_id'];
    $alamat = $_POST['alamat'];
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];

    // Jika thumbnail baru diunggah
    if ($_FILES['thumbnail']['name']) {
        $targetDir = "uploads/";
        $fileName = basename($_FILES["thumbnail"]["name"]);
        $targetFile = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $fileSize = $_FILES["thumbnail"]["size"];

        // Validasi tipe file
        if ($fileType != "jpg" && $fileType != "jpeg") {
            $uploadError = "Hanya file JPG/JPEG yang diperbolehkan.";
        }
        // Validasi ukuran file (3MB = 3 * 1024 * 1024 bytes)
        elseif ($fileSize > 3145728) {
            $uploadError = "Ukuran file maksimal 3MB. Ukuran file Anda: " . round($fileSize / 1048576, 2) . "MB";
        }
        else {
            if (move_uploaded_file($_FILES["thumbnail"]["tmp_name"], $targetFile)) {
                $thumbnail = $fileName;
            } else {
                $uploadError = "Gagal mengunggah gambar baru.";
            }
        }

        // Jika ada error upload, tampilkan dan stop
        if (isset($uploadError)) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    const container = document.querySelector('.container');
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-error';
                    alert.innerHTML = '<i class=\"fas fa-times-circle\"></i> " . $uploadError . "';
                    container.insertBefore(alert, container.firstChild);
                });
            </script>";
            $thumbnail = $data['thumbnail']; // Gunakan thumbnail lama
        }
    } else {
        $thumbnail = $data['thumbnail']; // Gunakan thumbnail lama
    }

    // Update database jika tidak ada error atau jika tidak upload file baru
    if (!isset($uploadError) || !$_FILES['thumbnail']['name']) {
        $stmt = $conn->prepare("UPDATE cctv_data SET nama=?, video_id=?, thumbnail=?, alamat=?, lat=?, lng=? WHERE id=?");
        $stmt->bind_param("ssssssi", $nama, $video_id, $thumbnail, $alamat, $lat, $lng, $id);
        $stmt->execute();
        $stmt->close();

        include 'update_json.php';

        header("Location: list.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data CCTV</title>
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
            background: linear-gradient(135deg, #10b981, #059669);
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
            color: #10b981;
        }

        .current-thumbnail {
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 12px;
            text-align: center;
        }

        .current-thumbnail h4 {
            color: #1e293b;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .current-thumbnail img {
            max-width: 300px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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

        label {
            font-weight: 600;
            display: block;
            margin-bottom: 0.5rem;
            color: #1e293b;
            font-size: 0.95rem;
        }

        label i {
            margin-right: 0.5rem;
            color: #10b981;
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
            border-color: #10b981;
            background: white;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
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
            background: linear-gradient(135deg, #10b981, #059669);
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
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
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

        .button-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 2rem;
        }

        button,
        .btn-cancel {
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
            border: none;
            text-decoration: none;
        }

        button {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-cancel {
            background: linear-gradient(135deg, #64748b, #475569);
            color: white;
            box-shadow: 0 4px 15px rgba(100, 116, 139, 0.3);
        }

        .btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(100, 116, 139, 0.4);
        }

        button:active,
        .btn-cancel:active {
            transform: translateY(0);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .form-row,
            .button-group {
                grid-template-columns: 1fr;
            }

            .top-nav {
                flex-direction: column;
                gap: 1rem;
            }

            .container {
                padding: 1.5rem;
            }

            .current-thumbnail img {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<!-- Top navigation -->
<div class="top-nav">
    <div class="nav-title">
        <i class="fas fa-edit"></i>
        <span>Edit Data CCTV</span>
    </div>
    <a href="list.php" class="nav-back">
        <i class="fas fa-arrow-left"></i> Kembali ke Data CCTV
    </a>
</div>

<div class="container">
    <h2>
        <i class="fas fa-edit"></i>
        Form Edit CCTV
    </h2>

    <!-- Tampilkan thumbnail saat ini -->
    <div class="current-thumbnail">
        <h4>
            <i class="fas fa-image"></i>
            Thumbnail Saat Ini
        </h4>
        <img src="uploads/<?= htmlspecialchars($data['thumbnail']) ?>" alt="Thumbnail">
    </div>

    <form action="" method="post" enctype="multipart/form-data">
        <div class="form-row">
            <div class="form-group">
                <label>
                    <i class="fas fa-tag"></i> Nama CCTV <span class="required">*</span>
                </label>
                <input type="text" name="nama" value="<?= htmlspecialchars($data['nama']) ?>" required>
            </div>

            <div class="form-group">
                <label>
                    <i class="fab fa-youtube"></i> Video ID <span class="required">*</span>
                </label>
                <input type="text" name="video_id" value="<?= htmlspecialchars($data['video_id']) ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>
                <i class="fas fa-image"></i> Thumbnail Baru (JPG)
            </label>
            <input type="file" name="thumbnail" accept="image/jpeg">
            <div class="hint">
                <i class="fas fa-info-circle"></i>
                Kosongkan jika tidak ingin mengubah thumbnail
            </div>
        </div>

        <div class="form-group">
            <label>
                <i class="fas fa-map-marker-alt"></i> Alamat <span class="required">*</span>
            </label>
            <textarea name="alamat" required><?= htmlspecialchars($data['alamat']) ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>
                    <i class="fas fa-location-arrow"></i> Latitude <span class="required">*</span>
                </label>
                <input type="text" name="lat" value="<?= htmlspecialchars($data['lat']) ?>" required>
            </div>

            <div class="form-group">
                <label>
                    <i class="fas fa-location-arrow"></i> Longitude <span class="required">*</span>
                </label>
                <input type="text" name="lng" value="<?= htmlspecialchars($data['lng']) ?>" required>
            </div>
        </div>

        <div class="button-group">
            <button type="submit" name="submit">
                <i class="fas fa-save"></i>
                Update Data
            </button>
            <a href="list.php" class="btn-cancel">
                <i class="fas fa-times"></i>
                Batal
            </a>
        </div>
    </form>
</div>

</body>
</html>