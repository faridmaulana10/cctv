<?php
include 'koneksi.php';

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

        if ($fileType != "jpg" && $fileType != "jpeg") {
            echo "<div class='alert alert-danger'>Hanya file JPG yang diperbolehkan.</div>";
            exit;
        }

        if (move_uploaded_file($_FILES["thumbnail"]["tmp_name"], $targetFile)) {
            $thumbnail = $fileName;
        } else {
            echo "<div class='alert alert-danger'>Gagal mengunggah gambar baru.</div>";
            exit;
        }
    } else {
        $thumbnail = $data['thumbnail']; // Gunakan thumbnail lama
    }

    $stmt = $conn->prepare("UPDATE cctv_data SET nama=?, video_id=?, thumbnail=?, alamat=?, lat=?, lng=? WHERE id=?");
    $stmt->bind_param("ssssssi", $nama, $video_id, $thumbnail, $alamat, $lat, $lng, $id);
    $stmt->execute();
    $stmt->close();

    include 'update_json.php';

    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Data CCTV</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f8;
            padding: 30px;
        }

        h2 {
            color: #333;
        }

        form {
            background: #fff;
            padding: 25px;
            max-width: 600px;
            margin: auto;
            border-radius: 12px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
        }

        label {
            font-weight: bold;
            display: block;
            margin-top: 15px;
        }

        input[type="text"],
        textarea,
        input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }

        textarea {
            resize: vertical;
        }

        button, a {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
        }

        button {
            background: #3498db;
            color: white;
        }

        a {
            background: #3498db;
            color: white;
            margin-left: 10px;
        }

        .alert {
            max-width: 600px;
            margin: 10px auto;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }

        .alert-danger {
            background: #e74c3c;
            color: white;
        }
    </style>
</head>
<body>
    <h2>Edit Data CCTV</h2>
    <form action="" method="post" enctype="multipart/form-data">
        <label>Nama CCTV:</label>
        <input type="text" name="nama" value="<?= $data['nama'] ?>" required>

        <label>Video ID YouTube:</label>
        <input type="text" name="video_id" value="<?= $data['video_id'] ?>" required>

        <label>Thumbnail (JPG):</label>
        <input type="file" name="thumbnail" accept="image/jpeg">
        <small>Biarkan kosong jika tidak ingin mengubah thumbnail</small>

        <label>Alamat:</label>
        <textarea name="alamat" required><?= $data['alamat'] ?></textarea>

        <label>Latitude:</label>
        <input type="text" name="lat" value="<?= $data['lat'] ?>" required>

        <label>Longitude:</label>
        <input type="text" name="lng" value="<?= $data['lng'] ?>" required>

        <button type="submit" name="submit">Update</button>
        <a href="list.php">Batal</a>
    </form>
</body>
</html>