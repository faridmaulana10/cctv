<?php
include 'koneksi.php';

// Ambil semua data dari tabel
$result = $conn->query("SELECT * FROM cctv_data");
$cctv_array = [];

while ($row = $result->fetch_assoc()) {
    $cctv_array[] = [
        "id" => (int)$row["id"],
        "nama" => $row["nama"],
        "video_id" => $row["video_id"],
        "thumbnail" => "uploads/" . $row["thumbnail"],
        "alamat" => $row["alamat"],
        "lat" => $row["lat"],
        "lng" => $row["lng"]
    ];
}

// Simpan ke dalam file JSON
file_put_contents("cctv_data.json", json_encode($cctv_array, JSON_PRETTY_PRINT));
?>