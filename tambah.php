<?php include 'koneksi.php'; ?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Data CCTV</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 0;
        }

        .top-nav {
            padding: 15px 30px;
            background-color: #fff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .top-nav a {
            text-decoration: none;
            color: #333;
            background-color: #e2e6ea;
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .top-nav a:hover {
            background-color: #d6d8db;
        }

        .container {
            width: 500px;
            margin: 40px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 6px;
            color: #333;
        }

        input[type="text"], textarea, input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
        }

        button {
            width: 100%;
            background-color: #28a745;
            color: white;
            border: none;
            padding: 12px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 8px;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #218838;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 15px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>

<!-- Top navigation -->
<div class="top-nav">
    <a href="list.php">← Kembali ke Data CCTV</a>
</div>

<div class="container">
    <h2>Tambah Data CCTV</h2>
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

        if ($fileType != "jpg" && $fileType != "jpeg") {
            echo '<div class="alert alert-error">Hanya file JPG yang diperbolehkan.</div>';
        } elseif (move_uploaded_file($_FILES["thumbnail"]["tmp_name"], $targetFile)) {
            $stmt = $conn->prepare("INSERT INTO cctv_data (nama, video_id, thumbnail, alamat, lat, lng) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $nama, $video_id, $fileName, $alamat, $lat, $lng);
            if ($stmt->execute()) {
                include "update_json.php";
                echo '<div class="alert alert-success">Data berhasil ditambahkan!</div>';
            } else {
                echo '<div class="alert alert-error">Gagal menyimpan ke database.</div>';
            }
            $stmt->close();
        } else {
            echo '<div class="alert alert-error">Gagal mengunggah gambar.</div>';
        }
    }
    ?>

    <form action="" method="post" enctype="multipart/form-data">
        <label>Nama CCTV:</label>
        <input type="text" name="nama" required>

        <label>Video ID:</label>
        <input type="text" name="video_id" required>

        <label>Thumbnail (JPG):</label>
        <input type="file" name="thumbnail" accept="image/jpeg" required>

        <label>Alamat:</label>
        <textarea name="alamat" required></textarea>

        <label>Latitude:</label>
        <input type="text" name="lat" required>

        <label>Longitude:</label>
        <input type="text" name="lng" required>

        <button type="submit" name="submit">Tambah</button>
    </form>
</div>
</body>
</html>