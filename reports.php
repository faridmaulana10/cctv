<?php
include 'koneksi.php';
session_start();

// Cek login
if (!isset($_SESSION['admin_logged'])) {
    header("Location: login.php");
    exit;
}

// Cek apakah tabel reports ada
$table_check = $conn->query("SHOW TABLES LIKE 'reports'");
if ($table_check->num_rows == 0) {
    die('<div style="font-family: Arial; padding: 50px; text-align: center;">
        <h2 style="color: #ef4444;">❌ Tabel "reports" belum dibuat!</h2>
        <p>Silakan jalankan SQL berikut di phpMyAdmin:</p>
        <pre style="background: #f3f4f6; padding: 20px; border-radius: 10px; text-align: left; max-width: 800px; margin: 20px auto; overflow-x: auto;">
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cctv_id INT NOT NULL,
    cctv_name VARCHAR(255) NOT NULL,
    reporter_name VARCHAR(100) NOT NULL,
    report_text TEXT NOT NULL,
    status ENUM(\'pending\', \'reviewed\', \'resolved\') DEFAULT \'pending\',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cctv_id) REFERENCES cctv_data(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_status ON reports(status);
CREATE INDEX idx_created_at ON reports(created_at DESC);
CREATE INDEX idx_cctv_id ON reports(cctv_id);
        </pre>
        <a href="dashboard.php" style="display: inline-block; margin-top: 20px; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 8px;">← Kembali ke Dashboard</a>
    </div>');
}

// Handle status update
if (isset($_POST['update_status'])) {
    $report_id = intval($_POST['report_id']);
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE reports SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $report_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: reports.php");
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $report_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM reports WHERE id = ?");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: reports.php");
    exit;
}

// Get filter status
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Get reports from database
$query = "SELECT * FROM reports";
if ($filter_status !== 'all') {
    $allowed_status = ['pending', 'reviewed', 'resolved'];
    if (in_array($filter_status, $allowed_status)) {
        $query .= " WHERE status = '" . $conn->real_escape_string($filter_status) . "'";
    }
}
$query .= " ORDER BY created_at DESC";

$result = $conn->query($query);

if (!$result) {
    die("Error query: " . $conn->error);
}

// Count by status
$count_pending = 0;
$count_reviewed = 0;
$count_resolved = 0;

$count_result = $conn->query("SELECT COUNT(*) as cnt FROM reports WHERE status = 'pending'");
if ($count_result) {
    $count_pending = $count_result->fetch_assoc()['cnt'];
}

$count_result = $conn->query("SELECT COUNT(*) as cnt FROM reports WHERE status = 'reviewed'");
if ($count_result) {
    $count_reviewed = $count_result->fetch_assoc()['cnt'];
}

$count_result = $conn->query("SELECT COUNT(*) as cnt FROM reports WHERE status = 'resolved'");
if ($count_result) {
    $count_resolved = $count_result->fetch_assoc()['cnt'];
}

$count_total = $count_pending + $count_reviewed + $count_resolved;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Masyarakat - CCTV Monitoring</title>
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
        
        /* Sidebar - sama dengan list.php */
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
            text-align: center;
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
            display: flex;
            align-items: center;
            gap: 12px;
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
            color: #ef4444;
            font-size: 1.8rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            cursor: pointer;
            transition: all 0.3s ease;
            border-left: 4px solid;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .stat-card.all {
            border-color: #667eea;
        }

        .stat-card.pending {
            border-color: #f59e0b;
        }

        .stat-card.reviewed {
            border-color: #3b82f6;
        }

        .stat-card.resolved {
            border-color: #10b981;
        }

        .stat-card h3 {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
        }

        /* Filter Buttons */
        .filter-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 10px 20px;
            border: 2px solid #e2e8f0;
            background: white;
            color: #64748b;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'Poppins', sans-serif;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .filter-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: #667eea;
        }

        /* Reports Container */
        .reports-container {
            display: grid;
            gap: 1.5rem;
        }

        .report-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border-left: 4px solid;
            transition: all 0.3s ease;
        }

        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .report-card.pending {
            border-color: #f59e0b;
        }

        .report-card.reviewed {
            border-color: #3b82f6;
        }

        .report-card.resolved {
            border-color: #10b981;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .report-info h3 {
            color: #1e293b;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .report-info h3 i {
            color: #667eea;
        }

        .report-meta {
            font-size: 0.85rem;
            color: #64748b;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .report-status {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .report-status.pending {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
        }

        .report-status.reviewed {
            background: rgba(59, 130, 246, 0.1);
            color: #2563eb;
        }

        .report-status.resolved {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }

        .report-content {
            margin: 1rem 0;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .report-content p {
            color: #475569;
            line-height: 1.6;
            margin: 0;
        }

        .report-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .btn-review {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }

        .btn-resolve {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-delete {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .empty-state i {
            font-size: 4rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            color: #64748b;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #94a3b8;
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

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .report-header {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <img src="uploads/logo.png" alt="Logo CCTV" class="sidebar-logo" onerror="this.style.display='none'">
    <h2>📡 CCTV PANEL</h2>

    <a href="dashboard.php"><i class="fa fa-chart-line"></i> Dashboard</a>
    <a href="maps_preview_admin.php"><i class="fa fa-map"></i> Peta CCTV</a>
    <a href="list.php"><i class="fa fa-video"></i> Data CCTV</a>
    <a href="reports.php" class="active"><i class="fa fa-exclamation-triangle"></i> Laporan</a>

    <hr>
    <h3>STATISTIK LAPORAN</h3>
    <a><i class="fa fa-file-alt"></i> Total <strong><?= $count_total ?></strong></a>
    <a><i class="fa fa-clock" style="color:#f59e0b;"></i> Pending <strong><?= $count_pending ?></strong></a>
    <a><i class="fa fa-eye" style="color:#3b82f6;"></i> Ditinjau <strong><?= $count_reviewed ?></strong></a>
    <a><i class="fa fa-check-circle" style="color:#10b981;"></i> Selesai <strong><?= $count_resolved ?></strong></a>

    <a href="logout.php" class="logout-btn" onclick="return confirm('Yakin ingin logout?');">
        <i class="fa fa-sign-out-alt"></i> Logout
    </a>
</div>

<div class="content">
    <h1 class="title">
        <i class="fas fa-exclamation-triangle"></i> 
        Laporan Masyarakat
    </h1>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <a href="?status=all" class="stat-card all" style="text-decoration: none;">
            <h3>Total Laporan</h3>
            <div class="value"><?= $count_total ?></div>
        </a>
        <a href="?status=pending" class="stat-card pending" style="text-decoration: none;">
            <h3>Menunggu</h3>
            <div class="value"><?= $count_pending ?></div>
        </a>
        <a href="?status=reviewed" class="stat-card reviewed" style="text-decoration: none;">
            <h3>Sedang Ditinjau</h3>
            <div class="value"><?= $count_reviewed ?></div>
        </a>
        <a href="?status=resolved" class="stat-card resolved" style="text-decoration: none;">
            <h3>Selesai</h3>
            <div class="value"><?= $count_resolved ?></div>
        </a>
    </div>

    <!-- Filter Buttons -->
    <div class="filter-buttons">
        <a href="?status=all" class="filter-btn <?= $filter_status === 'all' ? 'active' : '' ?>">
            <i class="fas fa-list"></i> Semua
        </a>
        <a href="?status=pending" class="filter-btn <?= $filter_status === 'pending' ? 'active' : '' ?>">
            <i class="fas fa-clock"></i> Pending
        </a>
        <a href="?status=reviewed" class="filter-btn <?= $filter_status === 'reviewed' ? 'active' : '' ?>">
            <i class="fas fa-eye"></i> Ditinjau
        </a>
        <a href="?status=resolved" class="filter-btn <?= $filter_status === 'resolved' ? 'active' : '' ?>">
            <i class="fas fa-check-circle"></i> Selesai
        </a>
    </div>

    <!-- Reports Container -->
    <div class="reports-container">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($report = $result->fetch_assoc()): ?>
                <div class="report-card <?= $report['status'] ?>">
                    <div class="report-header">
                        <div class="report-info">
                            <h3>
                                <i class="fas fa-video"></i>
                                <?= htmlspecialchars($report['cctv_name']) ?>
                            </h3>
                            <div class="report-meta">
                                <span><i class="fas fa-user"></i> <strong>Pelapor:</strong> <?= htmlspecialchars($report['reporter_name']) ?></span>
                                <span><i class="fas fa-calendar"></i> <strong>Tanggal:</strong> <?= date('d M Y, H:i', strtotime($report['created_at'])) ?></span>
                            </div>
                        </div>
                        <span class="report-status <?= $report['status'] ?>">
                            <?php
                            $status_icon = [
                                'pending' => 'fa-clock',
                                'reviewed' => 'fa-eye',
                                'resolved' => 'fa-check-circle'
                            ];
                            $status_text = [
                                'pending' => 'Menunggu',
                                'reviewed' => 'Ditinjau',
                                'resolved' => 'Selesai'
                            ];
                            ?>
                            <i class="fas <?= $status_icon[$report['status']] ?>"></i>
                            <?= $status_text[$report['status']] ?>
                        </span>
                    </div>

                    <div class="report-content">
                        <p><?= nl2br(htmlspecialchars($report['report_text'])) ?></p>
                    </div>

                    <div class="report-actions">
                        <?php if ($report['status'] === 'pending'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                <input type="hidden" name="status" value="reviewed">
                                <button type="submit" name="update_status" class="btn-action btn-review">
                                    <i class="fas fa-eye"></i> Tinjau
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if ($report['status'] === 'reviewed'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                <input type="hidden" name="status" value="resolved">
                                <button type="submit" name="update_status" class="btn-action btn-resolve">
                                    <i class="fas fa-check-circle"></i> Selesaikan
                                </button>
                            </form>
                        <?php endif; ?>

                        <a href="?delete=<?= $report['id'] ?>" 
                           class="btn-action btn-delete"
                           onclick="return confirm('Yakin ingin menghapus laporan ini?');">
                            <i class="fas fa-trash"></i> Hapus
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>Tidak Ada Laporan</h3>
                <p>
                    <?php if ($filter_status === 'all'): ?>
                        Belum ada laporan dari masyarakat.
                    <?php else: ?>
                        Tidak ada laporan dengan status "<?= $filter_status ?>".
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>