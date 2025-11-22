<?php
include 'koneksi.php';

$result = $koneksi->query("SELECT * FROM cctv_data");
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// Tulis ke file JSON
file_put_contents("cctv_data.json", json_encode($data, JSON_PRETTY_PRINT));
echo "✅ File cctv_data.json berhasil diperbarui.";