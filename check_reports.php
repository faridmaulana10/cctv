<?php
include 'koneksi.php';

// Get search parameter
$search_name = isset($_GET['search']) ? trim($_GET['search']) : '';

// Query reports based on search
$reports = [];
if (!empty($search_name)) {
    $stmt = $conn->prepare("SELECT * FROM reports WHERE reporter_name LIKE ? ORDER BY created_at DESC");
    $search_param = "%{$search_name}%";
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
    
    $stmt->close();
}

// Get statistics
$stats = [
    'total' => 0,
    'pending' => 0,
    'reviewed' => 0,
    'resolved' => 0
];

if (!empty($search_name)) {
    foreach ($reports as $report) {
        $stats['total']++;
        $stats[$report['status']]++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Status Laporan - CCTV Monitoring</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1.5rem 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border-radius: 20px;
            max-width: 1200px;
            margin: 0 auto 2rem;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .logo-text h1 {
            font-size: 1.5rem;
            color: #2c3e50;
            font-weight: 700;
        }

        .logo-text p {
            font-size: 0.85rem;
            color: #7f8c8d;
        }

        .nav-btn {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .search-section {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .search-section h2 {
            color: #1e293b;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .search-section h2 i {
            color: #667eea;
        }

        .search-form {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 1rem 1.25rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .btn-search {
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-family: 'Poppins', sans-serif;
        }

        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .hint {
            margin-top: 1rem;
            color: #64748b;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .hint i {
            color: #3b82f6;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-left: 4px solid;
        }

        .stat-card.total { border-color: #667eea; }
        .stat-card.pending { border-color: #f59e0b; }
        .stat-card.reviewed { border-color: #3b82f6; }
        .stat-card.resolved { border-color: #10b981; }

        .stat-card h3 {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 0.5rem;
        }

        .stat-card .value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e293b;
        }

        /* Reports */
        .reports-container {
            display: grid;
            gap: 1.5rem;
        }

        .report-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-left: 4px solid;
            transition: all 0.3s ease;
        }

        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .report-card.pending { border-color: #f59e0b; }
        .report-card.reviewed { border-color: #3b82f6; }
        .report-card.resolved { border-color: #10b981; }

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
        }

        .report-status {
            padding: 8px 16px;
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
        }

        .report-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .report-date {
            font-size: 0.85rem;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
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

        /* Timeline */
        .timeline {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .timeline-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .timeline-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            padding: 0 1rem;
        }

        .timeline-steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 1rem;
            right: 1rem;
            height: 2px;
            background: #e2e8f0;
            z-index: 0;
        }

        .timeline-step {
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .timeline-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-size: 1.1rem;
            border: 3px solid white;
        }

        .timeline-step.active .timeline-icon {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .timeline-step.completed .timeline-icon {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .timeline-label {
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 500;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .header-content {
                flex-direction: column;
            }

            .search-form {
                flex-direction: column;
            }

            .search-input {
                min-width: 100%;
            }

            .btn-search {
                width: 100%;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .timeline-steps {
                flex-direction: column;
                gap: 1rem;
            }

            .timeline-steps::before {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo-section">
                <div class="logo-icon">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="logo-text">
                    <h1>Cek Status Laporan</h1>
                    <p>Pantau perkembangan laporan Anda</p>
                </div>
            </div>
            <a href="maps.php" class="nav-btn">
                <i class="fas fa-arrow-left"></i> Kembali ke Maps
            </a>
        </div>
    </div>

    <div class="container">
        <!-- Search Section -->
        <div class="search-section">
            <h2>
                <i class="fas fa-search"></i>
                Cari Laporan Anda
            </h2>
            <form method="GET" class="search-form">
                <input type="text" 
                       name="search" 
                       class="search-input" 
                       placeholder="Masukkan nama pelapor..."
                       value="<?= htmlspecialchars($search_name) ?>"
                       required>
                <button type="submit" class="btn-search">
                    <i class="fas fa-search"></i>
                    Cari Laporan
                </button>
            </form>
            <div class="hint">
                <i class="fas fa-info-circle"></i>
                Masukkan nama yang sama dengan saat Anda melaporkan untuk melihat status laporan
            </div>
        </div>

        <?php if (!empty($search_name)): ?>
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <h3>Total Laporan</h3>
                    <div class="value"><?= $stats['total'] ?></div>
                </div>
                <div class="stat-card pending">
                    <h3>Menunggu</h3>
                    <div class="value"><?= $stats['pending'] ?></div>
                </div>
                <div class="stat-card reviewed">
                    <h3>Ditinjau</h3>
                    <div class="value"><?= $stats['reviewed'] ?></div>
                </div>
                <div class="stat-card resolved">
                    <h3>Selesai</h3>
                    <div class="value"><?= $stats['resolved'] ?></div>
                </div>
            </div>

            <!-- Reports -->
            <div class="reports-container">
                <?php if (count($reports) > 0): ?>
                    <?php foreach ($reports as $report): ?>
                        <div class="report-card <?= $report['status'] ?>">
                            <div class="report-header">
                                <div class="report-info">
                                    <h3>
                                        <i class="fas fa-video"></i>
                                        <?= htmlspecialchars($report['cctv_name']) ?>
                                    </h3>
                                    <div class="report-meta">
                                        Dilaporkan oleh: <strong><?= htmlspecialchars($report['reporter_name']) ?></strong>
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
                                        'pending' => 'Menunggu Tindakan',
                                        'reviewed' => 'Sedang Ditinjau',
                                        'resolved' => 'Telah Diselesaikan'
                                    ];
                                    ?>
                                    <i class="fas <?= $status_icon[$report['status']] ?>"></i>
                                    <?= $status_text[$report['status']] ?>
                                </span>
                            </div>

                            <div class="report-content">
                                <p><?= nl2br(htmlspecialchars($report['report_text'])) ?></p>
                            </div>

                            <!-- Timeline -->
                            <div class="timeline">
                                <div class="timeline-title">
                                    <i class="fas fa-tasks"></i>
                                    Progress Penanganan
                                </div>
                                <div class="timeline-steps">
                                    <div class="timeline-step completed">
                                        <div class="timeline-icon">
                                            <i class="fas fa-paper-plane"></i>
                                        </div>
                                        <div class="timeline-label">Terkirim</div>
                                    </div>
                                    <div class="timeline-step <?= in_array($report['status'], ['reviewed', 'resolved']) ? 'completed' : ($report['status'] === 'pending' ? 'active' : '') ?>">
                                        <div class="timeline-icon">
                                            <i class="fas fa-eye"></i>
                                        </div>
                                        <div class="timeline-label">Ditinjau</div>
                                    </div>
                                    <div class="timeline-step <?= $report['status'] === 'resolved' ? 'completed' : ($report['status'] === 'reviewed' ? 'active' : '') ?>">
                                        <div class="timeline-icon">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                        <div class="timeline-label">Selesai</div>
                                    </div>
                                </div>
                            </div>

                            <div class="report-footer">
                                <div class="report-date">
                                    <i class="fas fa-calendar"></i>
                                    <?= date('d M Y, H:i', strtotime($report['created_at'])) ?>
                                </div>
                                <?php if ($report['status'] === 'resolved'): ?>
                                    <span style="color: #10b981; font-weight: 600; font-size: 0.85rem;">
                                        <i class="fas fa-check-double"></i> Terima kasih atas laporannya!
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>Tidak Ada Laporan Ditemukan</h3>
                        <p>Tidak ada laporan dengan nama "<?= htmlspecialchars($search_name) ?>"</p>
                        <p style="margin-top: 0.5rem; font-size: 0.9rem;">Pastikan nama yang Anda masukkan sama dengan saat melaporkan</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>