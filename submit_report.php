<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'koneksi.php';
header('Content-Type: application/json');

// Debug log
file_put_contents('report_debug.log', date('Y-m-d H:i:s') . " - Request received\n", FILE_APPEND);
file_put_contents('report_debug.log', "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cctv_id = isset($_POST['cctv_id']) ? intval($_POST['cctv_id']) : 0;
    $cctv_name = isset($_POST['cctv_name']) ? trim($_POST['cctv_name']) : '';
    $reporter_name = isset($_POST['reporter_name']) ? trim($_POST['reporter_name']) : '';
    $report_text = isset($_POST['report_text']) ? trim($_POST['report_text']) : '';
    
    // Validasi input
    if ($cctv_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'CCTV ID tidak valid']);
        exit;
    }
    
    if (empty($reporter_name)) {
        echo json_encode(['success' => false, 'message' => 'Nama pelapor harus diisi']);
        exit;
    }
    
    if (strlen($reporter_name) > 100) {
        echo json_encode(['success' => false, 'message' => 'Nama pelapor maksimal 100 karakter']);
        exit;
    }
    
    if (empty($report_text)) {
        echo json_encode(['success' => false, 'message' => 'Isi laporan harus diisi']);
        exit;
    }
    
    if (strlen($report_text) < 10) {
        echo json_encode(['success' => false, 'message' => 'Isi laporan minimal 10 karakter']);
        exit;
    }
    
    if (strlen($report_text) > 1000) {
        echo json_encode(['success' => false, 'message' => 'Isi laporan maksimal 1000 karakter']);
        exit;
    }
    
    // Cek apakah tabel reports ada
    $table_check = $conn->query("SHOW TABLES LIKE 'reports'");
    if ($table_check->num_rows == 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Tabel laporan belum dibuat. Silakan hubungi administrator.'
        ]);
        exit;
    }
    
    // Insert ke database
    try {
        $stmt = $conn->prepare("INSERT INTO reports (cctv_id, cctv_name, reporter_name, report_text, status) VALUES (?, ?, ?, ?, 'pending')");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("isss", $cctv_id, $cctv_name, $reporter_name, $report_text);
        
        if ($stmt->execute()) {
            file_put_contents('report_debug.log', "Success - Report ID: " . $stmt->insert_id . "\n", FILE_APPEND);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Laporan berhasil dikirim. Terima kasih atas partisipasi Anda!',
                'report_id' => $stmt->insert_id
            ]);
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        file_put_contents('report_debug.log', "Error: " . $e->getMessage() . "\n", FILE_APPEND);
        
        echo json_encode([
            'success' => false, 
            'message' => 'Gagal menyimpan laporan: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
}

$conn->close();
?>