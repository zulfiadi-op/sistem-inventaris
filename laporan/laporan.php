<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Tanggal filter default
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // Awal bulan ini
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Hari ini
$filter_status = $_GET['status'] ?? 'all'; // Semua status

// Query dasar untuk laporan
$query_where = "WHERE DATE(t.tanggal_transaksi) BETWEEN '$start_date' AND '$end_date'";
if ($filter_status !== 'all') {
    $query_where .= " AND t.status = '$filter_status'";
}

// Query untuk laporan dengan menampilkan supplier untuk transaksi keluar
$query_laporan = "
SELECT 
    t.*,
    b.nama_barang,
    b.kode_barang,
    CASE 
        WHEN t.status = 'masuk' AND t.supplier IS NOT NULL AND t.supplier != '' THEN t.supplier
        WHEN t.status = 'keluar' AND t.supplier IS NOT NULL AND t.supplier != '' THEN t.supplier
        ELSE '-'
    END as nama_supplier
FROM transaksi t
JOIN barang b ON t.id_barang = b.id_barang
$query_where
ORDER BY t.tanggal_transaksi DESC
";

$result_laporan = mysqli_query($conn, $query_laporan);

// Cek error query
if (!$result_laporan) {
    die("Error query laporan: " . mysqli_error($conn));
}

$total_rows = mysqli_num_rows($result_laporan);

// Statistik laporan - BARANG KELUAR sekarang hanya dari transaksi ke supplier
$query_stats = "
SELECT 
    COUNT(*) as total_transaksi,
    SUM(CASE WHEN t.status = 'masuk' THEN t.jumlah ELSE 0 END) as total_masuk,
    SUM(CASE WHEN t.status = 'keluar' AND t.supplier IS NOT NULL AND t.supplier != '' THEN t.jumlah ELSE 0 END) as total_keluar_ke_supplier,
    SUM(CASE WHEN t.status = 'masuk' AND t.supplier IS NOT NULL AND t.supplier != '' THEN 1 ELSE 0 END) as total_transaksi_masuk_dari_supplier,
    SUM(CASE WHEN t.status = 'keluar' AND t.supplier IS NOT NULL AND t.supplier != '' THEN 1 ELSE 0 END) as total_transaksi_keluar_ke_supplier
FROM transaksi t
$query_where
";

$result_stats = mysqli_query($conn, $query_stats);

// Cek error query
if (!$result_stats) {
    die("Error query stats: " . mysqli_error($conn));
}

$stats = mysqli_fetch_assoc($result_stats);

// Hitung rata-rata, maksimum, minimum
$query_minmax = "
SELECT 
    AVG(jumlah) as rata_rata,
    MAX(jumlah) as max_jumlah,
    MIN(jumlah) as min_jumlah
FROM transaksi t
$query_where
";

$result_minmax = mysqli_query($conn, $query_minmax);
$minmax = mysqli_fetch_assoc($result_minmax);

// Gabungkan statistik
$stats = array_merge($stats, $minmax);

// Hitung rata-rata per transaksi untuk barang masuk
$avg_masuk = 0;
if ($stats['total_transaksi_masuk_dari_supplier'] > 0 && $stats['total_masuk'] > 0) {
    $avg_masuk = $stats['total_masuk'] / $stats['total_transaksi_masuk_dari_supplier'];
}

// Hitung rata-rata per transaksi untuk barang keluar ke supplier
$avg_keluar_supplier = 0;
if ($stats['total_transaksi_keluar_ke_supplier'] > 0 && $stats['total_keluar_ke_supplier'] > 0) {
    $avg_keluar_supplier = $stats['total_keluar_ke_supplier'] / $stats['total_transaksi_keluar_ke_supplier'];
}

// Persiapkan data untuk chart - hanya masuk dan keluar ke supplier
$query_chart = "
SELECT 
    DATE(tanggal_transaksi) as tanggal,
    SUM(CASE WHEN status = 'masuk' THEN jumlah ELSE 0 END) as masuk,
    SUM(CASE WHEN status = 'keluar' AND supplier IS NOT NULL AND supplier != '' THEN jumlah ELSE 0 END) as keluar_ke_supplier
FROM transaksi
WHERE DATE(tanggal_transaksi) BETWEEN '$start_date' AND '$end_date'
GROUP BY DATE(tanggal_transaksi)
ORDER BY tanggal
";

$result_chart = mysqli_query($conn, $query_chart);

// Cek error query
if (!$result_chart) {
    die("Error query chart: " . mysqli_error($conn));
}

$chart_data = [];
$dates = [];
$masuk_data = [];
$keluar_supplier_data = [];

while ($row = mysqli_fetch_assoc($result_chart)) {
    $chart_data[] = $row;
    $dates[] = date('d/m', strtotime($row['tanggal']));
    $masuk_data[] = (int)$row['masuk'];
    $keluar_supplier_data[] = (int)$row['keluar_ke_supplier'];
}

// Query untuk mendapatkan top barang
$query_top = "
SELECT 
    b.nama_barang,
    b.kode_barang,
    COUNT(t.id_transaksi) as frekuensi,
    SUM(CASE WHEN t.status = 'masuk' THEN t.jumlah ELSE 0 END) as total_masuk,
    SUM(CASE WHEN t.status = 'keluar' AND t.supplier IS NOT NULL AND t.supplier != '' THEN t.jumlah ELSE 0 END) as total_keluar_ke_supplier
FROM transaksi t
JOIN barang b ON t.id_barang = b.id_barang
$query_where
GROUP BY t.id_barang
ORDER BY frekuensi DESC
LIMIT 5
";

$result_top = mysqli_query($conn, $query_top);
$top_barang = [];
while ($row = mysqli_fetch_assoc($result_top)) {
    $top_barang[] = $row;
}

// Query untuk statistik supplier (top supplier) untuk barang keluar
$query_supplier_stats_keluar = "
SELECT 
    supplier,
    COUNT(*) as total_transaksi,
    SUM(jumlah) as total_barang
FROM transaksi 
WHERE status = 'keluar' 
AND supplier IS NOT NULL 
AND supplier != ''
AND DATE(tanggal_transaksi) BETWEEN '$start_date' AND '$end_date'
GROUP BY supplier
ORDER BY total_barang DESC
LIMIT 5
";

$result_supplier_stats_keluar = mysqli_query($conn, $query_supplier_stats_keluar);
$supplier_stats_keluar = [];
while ($row = mysqli_fetch_assoc($result_supplier_stats_keluar)) {
    $supplier_stats_keluar[] = $row;
}

// Query untuk statistik supplier (top supplier) untuk barang masuk
$query_supplier_stats_masuk = "
SELECT 
    supplier,
    COUNT(*) as total_transaksi,
    SUM(jumlah) as total_barang
FROM transaksi 
WHERE status = 'masuk' 
AND supplier IS NOT NULL 
AND supplier != ''
AND DATE(tanggal_transaksi) BETWEEN '$start_date' AND '$end_date'
GROUP BY supplier
ORDER BY total_barang DESC
LIMIT 5
";

$result_supplier_stats_masuk = mysqli_query($conn, $query_supplier_stats_masuk);
$supplier_stats_masuk = [];
while ($row = mysqli_fetch_assoc($result_supplier_stats_masuk)) {
    $supplier_stats_masuk[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Sistem Inventaris</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Sweet Alert CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --danger-color: #f72585;
            --warning-color: #f8961e;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --supplier-color: #7209b7;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            color: white;
            position: fixed;
            width: 250px;
            z-index: 1000;
        }
        
        .main-content {
            margin-left: 250px;
            background-color: #f5f7fb;
            min-height: 100vh;
        }
        
        .navbar-custom {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
            padding: 1rem 1.5rem;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,.05);
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card {
            border-left: 4px solid;
            padding: 1.5rem;
        }
        
        .stat-card.total {
            border-color: var(--primary-color);
        }
        
        .stat-card.masuk {
            border-color: var(--success-color);
        }
        
        .stat-card.keluar {
            border-color: var(--supplier-color);
        }
        
        .filter-card {
            background-color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .nav-link-custom {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 10px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .nav-link-custom:hover,
        .nav-link-custom.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        
        .nav-link-custom i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .logo-container {
            padding: 2rem 1rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .user-info {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding: 1rem;
            margin-top: auto;
        }
        
        .badge-masuk {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(76, 201, 240, 0.3);
        }
        
        .badge-keluar-supplier {
            background-color: rgba(114, 9, 183, 0.1);
            color: var(--supplier-color);
            border: 1px solid rgba(114, 9, 183, 0.3);
        }
        
        .btn-print {
            background: linear-gradient(45deg, var(--warning-color), #f9c74f);
            border: none;
            color: white;
        }
        
        .btn-print:hover {
            background: linear-gradient(45deg, #e68a00, var(--warning-color));
            color: white;
        }
        
        .btn-primary-custom {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
        }
        
        .btn-primary-custom:hover {
            background: linear-gradient(45deg, var(--secondary-color), var(--primary-color));
            color: white;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                overflow: hidden;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar.active {
                width: 250px;
            }
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .supplier-badge {
            background-color: var(--supplier-color);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
        }
        
        .search-container {
            position: relative;
            max-width: 300px;
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .search-input {
            padding-left: 40px;
            border-radius: 25px;
            border: 1px solid #ddd;
        }
        
        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .summary-card i {
            opacity: 0.3;
            font-size: 3rem;
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            color: #495057;
            font-weight: 600;
        }
        
        .table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar d-flex flex-column">
            <div class="logo-container">
                <h4 class="mb-0"><i class="bi bi-box-seam"></i> Inventaris Roti</h4>
                <small class="text-light">Report Management</small>
            </div>
            
            <nav class="nav flex-column mt-3">
                <a href="../dashboard.php" class="nav-link-custom">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="../barang/" class="nav-link-custom">
                    <i class="bi bi-box"></i> Data Barang
                </a>
                <a href="../transaksi/" class="nav-link-custom">
                    <i class="bi bi-arrow-left-right"></i> Transaksi
                </a>
                <a href="laporan.php" class="nav-link-custom active">
                    <i class="bi bi-file-text"></i> Laporan
                </a>
            </nav>
            
            <div class="user-info mt-auto">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="bi bi-person-circle" style="font-size: 2rem;"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-0"><?php echo $_SESSION['username'] ?? 'User'; ?></h6>
                        <small>Administrator</small>
                    </div>
                </div>
                <a href="../auth/logout.php" class="btn btn-outline-light btn-sm w-100 mt-2">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content flex-grow-1">
            <!-- Top Navbar -->
            <nav class="navbar navbar-custom">
                <div class="container-fluid">
                    <button class="btn btn-outline-primary d-md-none" id="sidebarToggle">
                        <i class="bi bi-list"></i>
                    </button>
                    
                    <div class="d-flex align-items-center w-100">
                        <h4 class="mb-0 me-3 d-none d-md-block">
                            <i class="bi bi-file-text text-primary"></i> Laporan Transaksi
                        </h4>
                        
                        <div class="search-container me-auto">
                            <i class="bi bi-search search-icon"></i>
                            <input type="text" class="form-control search-input" id="searchTable" placeholder="Cari data...">
                        </div>
                        
                        <div class="ms-auto d-flex align-items-center">
                            <span class="me-3 d-none d-md-block">
                                <i class="bi bi-calendar-check"></i> <?php echo date('d F Y'); ?>
                            </span>
                            <button class="btn btn-print" onclick="window.print()">
                                <i class="bi bi-printer me-2"></i> Cetak Laporan
                            </button>
                        </div>
                    </div>
                </div>
            </nav>
            
            <!-- Content -->
            <div class="container-fluid p-4">
                <!-- Filter Laporan -->
                <div class="filter-card">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Akhir</label>
                            <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status Transaksi</label>
                            <select class="form-select" name="status">
                                <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                                <option value="masuk" <?php echo $filter_status === 'masuk' ? 'selected' : ''; ?>>Barang Masuk (Dari Supplier)</option>
                                <option value="keluar" <?php echo $filter_status === 'keluar' ? 'selected' : ''; ?>>Barang Keluar (Ke Supplier)</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary-custom w-100">
                                <i class="bi bi-funnel me-2"></i> Terapkan Filter
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Statistik Cards -->
                <div class="row mb-4">
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card stat-card total">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Transaksi</h6>
                                        <h2 class="mb-0 fw-bold"><?php echo number_format($stats['total_transaksi'] ?? 0); ?></h2>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar-range me-1"></i>
                                            <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?>
                                        </small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-list-check" style="font-size: 3rem; color: var(--primary-color); opacity: 0.3;"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-primary" style="width: 100%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card stat-card masuk">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Barang Masuk (Dari Supplier)</h6>
                                        <h2 class="mb-0 fw-bold"><?php echo number_format($stats['total_masuk'] ?? 0); ?> <small class="fs-6">buah</small></h2>
                                        <small class="text-muted">
                                            <i class="bi bi-boxes me-1"></i>
                                            Rata-rata: <?php echo number_format($avg_masuk, 1); ?> buah/transaksi
                                        </small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-arrow-down-circle" style="font-size: 3rem; color: var(--success-color); opacity: 0.3;"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-success" style="width: <?php echo ($stats['total_masuk'] > 0 && $stats['total_transaksi'] > 0) ? ($stats['total_masuk'] / ($stats['total_masuk'] + $stats['total_keluar_ke_supplier'])) * 100 : 0; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card stat-card keluar">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Barang Keluar (Ke Supplier)</h6>
                                        <h2 class="mb-0 fw-bold"><?php echo number_format($stats['total_keluar_ke_supplier'] ?? 0); ?> <small class="fs-6">buah</small></h2>
                                        <small class="text-muted">
                                            <i class="bi bi-truck me-1"></i>
                                            Rata-rata: <?php echo number_format($avg_keluar_supplier, 1); ?> buah/transaksi
                                        </small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-truck" style="font-size: 3rem; color: var(--supplier-color); opacity: 0.3;"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar" style="background-color: var(--supplier-color); width: <?php echo ($stats['total_keluar_ke_supplier'] > 0 && $stats['total_transaksi'] > 0) ? ($stats['total_keluar_ke_supplier'] / ($stats['total_masuk'] + $stats['total_keluar_ke_supplier'])) * 100 : 0; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Chart dan Top Supplier -->
                <div class="row mb-4">
                    <div class="col-lg-8 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-transparent">
                                <h6 class="mb-0 fw-bold">
                                    <i class="bi bi-graph-up me-2 text-primary"></i> 
                                    Grafik Transaksi
                                </h6>
                                <small class="text-muted">Periode <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></small>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="transaksiChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-transparent">
                                <h6 class="mb-0 fw-bold">
                                    <i class="bi bi-truck me-2" style="color: var(--supplier-color);"></i>
                                    Top 5 Supplier (Barang Keluar)
                                </h6>
                                <small class="text-muted">Supplier dengan pengembalian terbanyak</small>
                            </div>
                            <div class="card-body">
                                <?php if (count($supplier_stats_keluar) > 0): ?>
                                    <?php 
                                    $max_barang = !empty($supplier_stats_keluar) ? max(array_column($supplier_stats_keluar, 'total_barang')) : 1;
                                    ?>
                                    <?php foreach ($supplier_stats_keluar as $index => $supplier): ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div>
                                                <span class="badge bg-secondary me-2">#<?php echo $index + 1; ?></span>
                                                <strong><?php echo htmlspecialchars($supplier['supplier']); ?></strong>
                                            </div>
                                            <span class="badge" style="background-color: var(--supplier-color);">
                                                <?php echo number_format($supplier['total_barang']); ?> buah
                                            </span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar" style="background-color: var(--supplier-color); width: <?php echo ($supplier['total_barang'] / $max_barang) * 100; ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?php echo $supplier['total_transaksi']; ?> transaksi</small>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-truck" style="font-size: 3rem; color: #ddd;"></i>
                                        <p class="text-muted mt-2 mb-0">Tidak ada data supplier</p>
                                        <small>Tidak ada transaksi keluar ke supplier pada periode ini</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Top Barang dan Top Supplier Masuk -->
                <div class="row mb-4">
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-transparent">
                                <h6 class="mb-0 fw-bold">
                                    <i class="bi bi-box me-2 text-success"></i>
                                    Top 5 Barang (Paling Sering Bertransaksi)
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (count($top_barang) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Barang</th>
                                                    <th class="text-center">Frekuensi</th>
                                                    <th class="text-end">Total Masuk</th>
                                                    <th class="text-end">Total Keluar</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($top_barang as $index => $barang): ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($barang['nama_barang']); ?></strong><br>
                                                        <small class="text-muted"><?php echo $barang['kode_barang']; ?></small>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-primary"><?php echo $barang['frekuensi']; ?>x</span>
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="badge badge-masuk"><?php echo number_format($barang['total_masuk']); ?></span>
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="badge badge-keluar-supplier"><?php echo number_format($barang['total_keluar_ke_supplier']); ?></span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-box" style="font-size: 3rem; color: #ddd;"></i>
                                        <p class="text-muted mt-2 mb-0">Tidak ada data barang</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-transparent">
                                <h6 class="mb-0 fw-bold">
                                    <i class="bi bi-truck me-2 text-success"></i>
                                    Top 5 Supplier (Barang Masuk)
                                </h6>
                                <small class="text-muted">Supplier dengan pasokan terbanyak</small>
                            </div>
                            <div class="card-body">
                                <?php if (count($supplier_stats_masuk) > 0): ?>
                                    <?php 
                                    $max_barang_masuk = !empty($supplier_stats_masuk) ? max(array_column($supplier_stats_masuk, 'total_barang')) : 1;
                                    ?>
                                    <?php foreach ($supplier_stats_masuk as $index => $supplier): ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div>
                                                <span class="badge bg-secondary me-2">#<?php echo $index + 1; ?></span>
                                                <strong><?php echo htmlspecialchars($supplier['supplier']); ?></strong>
                                            </div>
                                            <span class="badge bg-success">
                                                <?php echo number_format($supplier['total_barang']); ?> buah
                                            </span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-success" style="width: <?php echo ($supplier['total_barang'] / $max_barang_masuk) * 100; ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?php echo $supplier['total_transaksi']; ?> transaksi</small>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-truck" style="font-size: 3rem; color: #ddd;"></i>
                                        <p class="text-muted mt-2 mb-0">Tidak ada data supplier</p>
                                        <small>Tidak ada transaksi masuk dari supplier pada periode ini</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabel Laporan -->
                <div class="card">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            <i class="bi bi-table me-2 text-primary"></i> 
                            Detail Transaksi
                        </h5>
                        <div>
                            <span class="badge bg-primary me-2">Total: <?php echo $total_rows; ?> data</span>
                            <?php if ($filter_status !== 'all'): ?>
                                <span class="badge bg-info">Filter: <?php echo $filter_status === 'masuk' ? 'Barang Masuk' : 'Barang Keluar'; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($total_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="dataTable">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Tanggal & Waktu</th>
                                            <th>Barang</th>
                                            <th>Kode</th>
                                            <th>Jumlah</th>
                                            <th>Status</th>
                                            <th>Supplier</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        mysqli_data_seek($result_laporan, 0);
                                        $no = 1;
                                        while ($row = mysqli_fetch_assoc($result_laporan)): 
                                        ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td>
                                                <div><?php echo date('d/m/Y', strtotime($row['tanggal_transaksi'])); ?></div>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($row['tanggal_transaksi'])); ?> WIB</small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($row['nama_barang']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($row['kode_barang']); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($row['status'] === 'masuk'): ?>
                                                    <span class="badge badge-masuk fs-6 p-2">
                                                        <i class="bi bi-plus-circle me-1"></i><?php echo number_format($row['jumlah']); ?> buah
                                                    </span>
                                                <?php else: ?>
                                                    <?php if (!empty($row['supplier'])): ?>
                                                        <span class="badge badge-keluar-supplier fs-6 p-2">
                                                            <i class="bi bi-dash-circle me-1"></i><?php echo number_format($row['jumlah']); ?> buah
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark">
                                                            <?php echo number_format($row['jumlah']); ?> buah
                                                        </span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($row['status'] === 'masuk'): ?>
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-arrow-down-circle me-1"></i>BARANG MASUK
                                                    </span>
                                                <?php else: ?>
                                                    <?php if (!empty($row['supplier'])): ?>
                                                        <span class="badge" style="background-color: var(--supplier-color);">
                                                            <i class="bi bi-truck me-1"></i>KE SUPPLIER
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark">KELUAR</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($row['supplier'])): ?>
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-building me-2" style="color: var(--supplier-color);"></i>
                                                        <?php echo htmlspecialchars($row['supplier']); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($row['keterangan'])): ?>
                                                    <span data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($row['keterangan']); ?>">
                                                        <i class="bi bi-chat-dots text-muted"></i>
                                                        <small><?php echo substr(htmlspecialchars($row['keterangan']), 0, 20) . (strlen($row['keterangan']) > 20 ? '...' : ''); ?></small>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 4rem; color: #ddd;"></i>
                                <p class="text-muted mt-3 fs-5">Tidak ada data transaksi</p>
                                <p class="text-muted">Periode <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
                                <a href="?" class="btn btn-primary-custom">
                                    <i class="bi bi-arrow-clockwise me-2"></i> Reset Filter
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($total_rows > 0): ?>
                    <div class="card-footer bg-transparent">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Menampilkan <?php echo $total_rows; ?> data transaksi
                                </small>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-outline-primary" onclick="exportToExcel()">
                                    <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                    <i class="bi bi-printer me-1"></i> Cetak
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Sweet Alert JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Toggle Sidebar on Mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        // Search functionality for table
        document.getElementById('searchTable').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('#dataTable tbody tr');
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Chart Configuration
        <?php if (count($dates) > 0): ?>
        const ctx = document.getElementById('transaksiChart').getContext('2d');
        const transaksiChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [
                    {
                        label: 'Barang Masuk',
                        data: <?php echo json_encode($masuk_data); ?>,
                        borderColor: '#4cc9f0',
                        backgroundColor: 'rgba(76, 201, 240, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#4cc9f0',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    },
                    {
                        label: 'Barang Keluar (Ke Supplier)',
                        data: <?php echo json_encode($keluar_supplier_data); ?>,
                        borderColor: '#7209b7',
                        backgroundColor: 'rgba(114, 9, 183, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#7209b7',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#ddd',
                        borderWidth: 1
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        },
                        title: {
                            display: true,
                            text: 'Jumlah (buah)',
                            color: '#666'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Tanggal',
                            color: '#666'
                        }
                    }
                }
            }
        });
        <?php else: ?>
        document.getElementById('transaksiChart').parentElement.innerHTML = 
            '<div class="text-center py-5">' +
            '<i class="bi bi-graph-up" style="font-size: 3rem; color: #ddd;"></i>' +
            '<p class="text-muted mt-2">Tidak ada data chart untuk periode ini</p>' +
            '</div>';
        <?php endif; ?>
        
        // Export to Excel function
        function exportToExcel() {
            const table = document.querySelector('.table');
            const rows = table.querySelectorAll('tr');
            let csv = [];
            
            // Get headers
            const headers = [];
            table.querySelectorAll('thead th').forEach(th => {
                headers.push(th.innerText.trim());
            });
            csv.push(headers.join(','));
            
            // Get data
            rows.forEach((row, index) => {
                if (index > 0) { // Skip header row
                    const rowData = [];
                    row.querySelectorAll('td').forEach(cell => {
                        // Clean the cell text
                        let text = cell.innerText.replace(/\n/g, ' ').replace(/,/g, ';').trim();
                        rowData.push(text);
                    });
                    csv.push(rowData.join(','));
                }
            });
            
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', 'laporan_transaksi_<?php echo date('Y-m-d'); ?>.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data berhasil diekspor ke Excel',
                showConfirmButton: false,
                timer: 2000
            });
        }
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Print styles
        window.addEventListener('beforeprint', () => {
            document.querySelector('.sidebar').style.display = 'none';
            document.querySelector('.main-content').style.marginLeft = '0';
            document.querySelector('.navbar-custom').style.display = 'none';
            document.querySelector('.filter-card').style.display = 'none';
            document.querySelector('.btn-print').style.display = 'none';
            
            // Show all rows when printing
            const tableRows = document.querySelectorAll('#dataTable tbody tr');
            tableRows.forEach(row => {
                row.style.display = '';
            });
        });
        
        window.addEventListener('afterprint', () => {
            document.querySelector('.sidebar').style.display = '';
            document.querySelector('.main-content').style.marginLeft = '250px';
            document.querySelector('.navbar-custom').style.display = '';
            document.querySelector('.filter-card').style.display = '';
            document.querySelector('.btn-print').style.display = '';
        });
    </script>
</body>
</html>