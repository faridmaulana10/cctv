<?php
include 'koneksi.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Hapus thumbnail dari folder uploads
    $res = $conn->query("SELECT thumbnail FROM cctv_data WHERE id = $id");
    $data = $res->fetch_assoc();
    if ($data && file_exists("uploads/" . $data['thumbnail'])) {
        unlink("uploads/" . $data['thumbnail']);
    }

    // Hapus dari database
    $conn->query("DELETE FROM cctv_data WHERE id = $id");

    // Perbarui file JSON
    include 'update_json.php';
}

header("Location: list.php");
exit;
?>