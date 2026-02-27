<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

// Hitung statistik
/* =======================
   TOTAL BARANG
======================= */
$query_total_barang = "SELECT COUNT(*) as total FROM barang";
$result_total_barang = mysqli_query($conn, $query_total_barang);
$total_barang = mysqli_fetch_assoc($result_total_barang)['total'];

/* =======================
   TOTAL STOK
======================= */
$query_total_stok = "SELECT SUM(stok_barang) as total FROM barang";
$result_total_stok = mysqli_query($conn, $query_total_stok);
$total_stok = mysqli_fetch_assoc($result_total_stok)['total'] ?? 0;

/* =======================
   STOK RENDAH
======================= */
$query_stok_rendah = "SELECT COUNT(*) as total FROM barang WHERE stok_barang < 10";
$result_stok_rendah = mysqli_query($conn, $query_stok_rendah);
$stok_rendah = mysqli_fetch_assoc($result_stok_rendah)['total'];

/* =======================
   TRANSAKSI HARI INI
======================= */
$today = date('Y-m-d');
$query_transaksi = "SELECT COUNT(*) as total FROM transaksi WHERE DATE(tanggal_transaksi) = '$today'";
$result_transaksi = mysqli_query($conn, $query_transaksi);
$transaksi_hari_ini = mysqli_fetch_assoc($result_transaksi)['total'];

/* =======================
   BARANG STOK RENDAH
======================= */
$query_stok_rendah_detail = "SELECT * FROM barang WHERE stok_barang < 10 ORDER BY stok_barang ASC";
$result_stok_rendah_detail = mysqli_query($conn, $query_stok_rendah_detail);
$barang_stok_rendah = [];
while ($row = mysqli_fetch_assoc($result_stok_rendah_detail)) {
    $barang_stok_rendah[] = $row;
}

/* =======================
   CHART DATA (7 HARI TERAKHIR)
======================= */
$query_chart = "
SELECT DATE(tanggal_transaksi) AS tanggal, 
       SUM(CASE WHEN status = 'masuk' THEN jumlah ELSE 0 END) AS masuk,
       SUM(CASE WHEN status = 'keluar' THEN jumlah ELSE 0 END) AS keluar
FROM transaksi 
WHERE tanggal_transaksi >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY DATE(tanggal_transaksi)
ORDER BY tanggal
";
$result_chart = mysqli_query($conn, $query_chart);
$chart_labels = [];
$chart_masuk = [];
$chart_keluar = [];
while ($row = mysqli_fetch_assoc($result_chart)) {
    $chart_labels[] = date('d/m', strtotime($row['tanggal']));
    $chart_masuk[] = $row['masuk'] ?? 0;
    $chart_keluar[] = $row['keluar'] ?? 0;
}

/* =======================
   TRANSAKSI TERBARU
======================= */
$query_transaksi_terbaru = "
SELECT t.*, b.nama_barang, b.kode_barang 
FROM transaksi t
JOIN barang b ON t.id_barang = b.id_barang
ORDER BY t.tanggal_transaksi DESC 
LIMIT 5
";
$result_transaksi_terbaru = mysqli_query($conn, $query_transaksi_terbaru);
$transaksi_terbaru = [];
while ($row = mysqli_fetch_assoc($result_transaksi_terbaru)) {
    $transaksi_terbaru[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Dashboard - Sistem Inventaris</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --sidebar-width: 250px;
            --sidebar-width-collapsed: 70px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            overflow-x: hidden;
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Sidebar Styling */
        .sidebar {
            background: linear-gradient(180deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            color: white;
            position: fixed;
            width: var(--sidebar-width);
            z-index: 1000;
            transition: all 0.3s ease;
            overflow-y: auto;
            overflow-x: hidden;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-width-collapsed);
        }
        
        .sidebar.collapsed .logo-container h4,
        .sidebar.collapsed .logo-container small,
        .sidebar.collapsed .nav-link-custom span,
        .sidebar.collapsed .user-info .flex-grow-1,
        .sidebar.collapsed .user-info .btn span {
            display: none;
        }
        
        .sidebar.collapsed .nav-link-custom {
            text-align: center;
            padding: 12px 0;
            margin: 5px;
        }
        
        .sidebar.collapsed .nav-link-custom i {
            margin-right: 0;
            font-size: 1.3rem;
        }
        
        .sidebar.collapsed .user-info .d-flex {
            justify-content: center;
        }
        
        .sidebar.collapsed .user-info .ms-3 {
            margin-left: 0 !important;
        }
        
        .sidebar.collapsed .btn i {
            margin-right: 0;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: all 0.3s ease;
            width: calc(100% - var(--sidebar-width));
        }
        
        .main-content.expanded {
            margin-left: var(--sidebar-width-collapsed);
            width: calc(100% - var(--sidebar-width-collapsed));
        }

        /* Logo Container */
        .logo-container {
            padding: 2rem 1rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        
        .logo-container h4 {
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
            white-space: nowrap;
        }
        
        .logo-container small {
            font-size: 0.75rem;
            opacity: 0.9;
            white-space: nowrap;
        }

        /* Navigation Links */
        .nav-link-custom {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 10px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            white-space: nowrap;
        }
        
        .nav-link-custom:hover,
        .nav-link-custom.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }
        
        .nav-link-custom i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .nav-link-custom span {
            font-size: 0.95rem;
        }

        /* User Info */
        .user-info {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding: 1rem;
            margin-top: auto;
        }
        
        .user-info h6 {
            font-size: 0.95rem;
            margin-bottom: 0.25rem;
            white-space: nowrap;
        }
        
        .user-info small {
            font-size: 0.75rem;
            opacity: 0.8;
            white-space: nowrap;
        }
        
        .user-info .btn {
            transition: all 0.3s ease;
            border-color: rgba(255,255,255,0.3);
            color: white;
            white-space: nowrap;
        }
        
        .user-info .btn:hover {
            background-color: white;
            color: var(--primary-color);
            border-color: white;
        }

        /* Navbar Custom */
        .navbar-custom {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,.08);
            padding: 0.75rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .navbar-custom h4 {
            font-size: 1.5rem;
            margin-bottom: 0;
            color: var(--dark-color);
            white-space: nowrap;
        }
        
        .navbar-custom h4 i {
            color: var(--primary-color);
        }

        /* Cards */
        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,.03);
            transition: all 0.3s ease;
            border-left: 5px solid;
            height: 100%;
            animation: fadeIn 0.5s ease-out;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,.1);
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,.03);
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }
        
        .card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,.08);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid #eee;
            padding: 1.25rem 1.5rem;
            border-radius: 15px 15px 0 0 !important;
        }
        
        .card-header h5 {
            margin-bottom: 0;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .card-body {
            padding: 1.5rem;
        }

        /* Table Styles */
        .table {
            margin-bottom: 0;
            font-size: 0.95rem;
        }
        
        .table thead th {
            background-color: var(--light-color);
            color: var(--dark-color);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #dee2e6;
            white-space: nowrap;
        }
        
        .table tbody tr {
            transition: all 0.2s ease;
            border-bottom: 1px solid #eee;
        }
        
        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.03);
        }
        
        .table td {
            vertical-align: middle;
            padding: 1rem 0.75rem;
        }

        /* Badge Styles */
        .badge {
            padding: 0.5em 0.75em;
            font-weight: 500;
            font-size: 0.75rem;
            border-radius: 6px;
        }
        
        .badge.bg-secondary {
            background-color: #6c757d !important;
        }
        
        .badge.bg-success {
            background-color: #28a745 !important;
        }
        
        .badge.bg-danger {
            background-color: #dc3545 !important;
        }
        
        .badge.bg-warning {
            background-color: #ffc107 !important;
            color: #212529;
        }
        
        .badge.bg-primary {
            background-color: var(--primary-color) !important;
        }

        /* Button Styles */
        .btn-primary-custom {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 25px;
            padding: 8px 20px;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-primary-custom:hover {
            background: linear-gradient(45deg, var(--secondary-color), var(--primary-color));
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        /* Responsive Breakpoints */
        @media (max-width: 1200px) {
            .stat-card h3 {
                font-size: 1.5rem;
            }
            
            .table {
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 992px) {
            .navbar-custom h4 {
                font-size: 1.25rem;
            }
            
            .card-body {
                padding: 1.25rem;
            }
            
            .stat-card i {
                font-size: 2rem !important;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                box-shadow: none;
            }
            
            .sidebar.active {
                transform: translateX(0);
                width: var(--sidebar-width);
                box-shadow: 2px 0 20px rgba(0,0,0,0.2);
            }
            
            .sidebar.active .logo-container h4,
            .sidebar.active .logo-container small,
            .sidebar.active .nav-link-custom span,
            .sidebar.active .user-info .flex-grow-1,
            .sidebar.active .user-info .btn span {
                display: block;
            }
            
            .sidebar.active .nav-link-custom {
                text-align: left;
                padding: 12px 20px;
                margin: 5px 10px;
            }
            
            .sidebar.active .nav-link-custom i {
                margin-right: 10px;
                font-size: 1.1rem;
            }
            
            .sidebar.active .user-info .d-flex {
                justify-content: flex-start;
            }
            
            .sidebar.active .user-info .ms-3 {
                margin-left: 1rem !important;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .main-content.expanded {
                margin-left: 0;
                width: 100%;
            }
            
            .navbar-custom {
                padding: 0.75rem 1rem;
            }
            
            .navbar-custom h4 {
                font-size: 1.1rem;
            }
            
            .container-fluid.p-4 {
                padding: 1rem !important;
            }
            
            .row.mb-4 {
                margin-bottom: 0.5rem !important;
            }
            
            .col-xl-3.col-md-6.mb-4 {
                margin-bottom: 0.75rem !important;
            }
            
            .stat-card .card-body {
                padding: 1.25rem;
            }
            
            .stat-card h6 {
                font-size: 0.85rem;
            }
            
            .stat-card h3 {
                font-size: 1.25rem;
            }
            
            .stat-card small {
                font-size: 0.75rem !important;
            }
            
            .card-header {
                padding: 1rem;
            }
            
            .card-header h5 {
                font-size: 1rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            /* Transform table untuk mobile */
            .table-responsive {
                border: none;
                margin: 0 -0.5rem;
            }
            
            .table thead {
                display: none;
            }
            
            .table, .table tbody, .table tr, .table td {
                display: block;
                width: 100%;
            }
            
            .table tr {
                margin-bottom: 0.75rem;
                border: 1px solid #dee2e6;
                border-radius: 10px;
                background-color: white;
                padding: 0.75rem;
                box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            }
            
            .table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.5rem 0;
                border: none;
                border-bottom: 1px solid #eee;
                font-size: 0.9rem;
            }
            
            .table td:last-child {
                border-bottom: none;
            }
            
            .table td::before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--dark-color);
                font-size: 0.85rem;
                margin-right: 1rem;
                min-width: 80px;
            }
            
            .table td[data-label="Kode"]::before { content: "Kode"; }
            .table td[data-label="Nama Barang"]::before { content: "Nama"; }
            .table td[data-label="Varian"]::before { content: "Varian"; }
            .table td[data-label="Stok Sisa"]::before { content: "Stok"; }
            .table td[data-label="Aksi"]::before { content: "Aksi"; }
            .table td[data-label="Tanggal"]::before { content: "Tanggal"; }
            .table td[data-label="Jenis"]::before { content: "Jenis"; }
            .table td[data-label="Jumlah"]::before { content: "Jumlah"; }
            .table td[data-label="Status"]::before { content: "Status"; }
            
            .chart-container {
                height: 250px;
            }
        }
        
        @media (max-width: 576px) {
            .navbar-custom {
                padding: 0.5rem 0.75rem;
            }
            
            .navbar-custom .btn {
                padding: 0.4rem 0.6rem;
                font-size: 0.9rem;
            }
            
            .navbar-custom h4 {
                font-size: 1rem;
            }
            
            .container-fluid.p-4 {
                padding: 0.75rem !important;
            }
            
            .stat-card .card-body {
                padding: 1rem;
            }
            
            .stat-card h6 {
                font-size: 0.8rem;
                margin-bottom: 0.25rem;
            }
            
            .stat-card h3 {
                font-size: 1.1rem;
            }
            
            .stat-card i {
                font-size: 1.75rem !important;
            }
            
            .stat-card small {
                font-size: 0.7rem !important;
            }
            
            .card-header {
                padding: 0.75rem 1rem;
            }
            
            .card-header h5 {
                font-size: 0.95rem;
            }
            
            .badge {
                font-size: 0.7rem;
                padding: 0.4rem 0.6rem;
            }
            
            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.8rem;
            }
            
            .chart-container {
                height: 200px;
            }
            
            .text-muted {
                font-size: 0.85rem;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 2rem;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        .empty-state p {
            color: #6c757d;
            margin-bottom: 0;
        }

        /* Print Styles */
        @media print {
            .sidebar, .navbar-custom, .btn, .user-info .btn {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .card {
                box-shadow: none;
                border: 1px solid #ddd;
                break-inside: avoid;
            }
            
            .badge {
                border: 1px solid #000;
                color: #000 !important;
                background: transparent !important;
            }
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar d-flex flex-column" id="sidebar">
            <div class="logo-container">
                <h4 class="mb-0"><i class="bi bi-box-seam"></i> <span>Inventaris Roti</span></h4>
                <small class="text-light"><span>Management System</span></small>
            </div>
            
            <nav class="nav flex-column mt-3">
                <a href="dashboard.php" class="nav-link-custom active">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="barang/index.php" class="nav-link-custom">
                    <i class="bi bi-box"></i>
                    <span>Data Barang</span>
                </a>
                <a href="transaksi/index.php" class="nav-link-custom">
                    <i class="bi bi-arrow-left-right"></i>
                    <span>Transaksi</span>
                </a>
                <a href="laporan/laporan.php" class="nav-link-custom">
                    <i class="bi bi-file-text"></i>
                    <span>Laporan</span>
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
                <a href="auth/logout.php" class="btn btn-outline-light btn-sm w-100 mt-2">
                    <i class="bi bi-box-arrow-right"></i> <span>Logout</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content flex-grow-1" id="mainContent">
            <nav class="navbar navbar-custom">
                <div class="container-fluid">
                    <div class="d-flex align-items-center w-100">
                        <!-- Sidebar Toggle Buttons -->
                        <button class="btn btn-outline-primary me-3 d-none d-md-block" id="sidebarCollapse">
                            <i class="bi bi-layout-sidebar"></i>
                        </button>
                        <button class="btn btn-outline-primary d-md-none me-3" id="sidebarToggle">
                            <i class="bi bi-list"></i>
                        </button>
                        
                        <h4 class="mb-0 me-auto">
                            <i class="bi bi-speedometer2 text-primary"></i> <span class="d-none d-sm-inline">Dashboard</span>
                        </h4>
                    </div>
                </div>
            </nav>
            
            <div class="container-fluid p-4">
                <p class="text-muted mb-4"><i class="bi bi-info-circle me-2"></i>Overview sistem inventaris roti Anda</p>
                
                <!-- Statistik Cards -->
                <div class="row mb-4 g-3">
                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card" style="border-left-color: var(--primary-color);">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Barang</h6>
                                        <h3 class="mb-0 fw-bold"><?php echo number_format($total_barang); ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-box text-primary" style="font-size: 2.5rem; opacity: 0.8;"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <small class="text-success">
                                        <i class="bi bi-arrow-up"></i> +<?php echo rand(1, 5); ?> dari bulan lalu
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card" style="border-left-color: var(--success-color);">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Stok</h6>
                                        <h3 class="mb-0 fw-bold"><?php echo number_format($total_stok); ?> <small style="font-size: 14px">pcs</small></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-stack text-success" style="font-size: 2.5rem; opacity: 0.8;"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="bi bi-grid"></i> Rata-rata <?php echo round($total_stok / max($total_barang, 1)); ?> pcs/barang
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card" style="border-left-color: var(--warning-color);">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Stok Rendah</h6>
                                        <h3 class="mb-0 fw-bold <?php echo $stok_rendah > 0 ? 'text-danger' : ''; ?>"><?php echo number_format($stok_rendah); ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-exclamation-triangle text-warning" style="font-size: 2.5rem; opacity: 0.8;"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <small class="<?php echo $stok_rendah > 0 ? 'text-danger' : 'text-success'; ?>">
                                        <i class="bi <?php echo $stok_rendah > 0 ? 'bi-exclamation-circle' : 'bi-check-circle'; ?>"></i>
                                        <?php echo $stok_rendah > 0 ? 'Perlu restock segera' : 'Semua stok aman'; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card" style="border-left-color: var(--info-color);">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Transaksi Hari Ini</h6>
                                        <h3 class="mb-0 fw-bold"><?php echo number_format($transaksi_hari_ini); ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-arrow-left-right text-info" style="font-size: 2.5rem; opacity: 0.8;"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <small class="text-info">
                                        <i class="bi bi-clock-history"></i> Update terakhir <?php echo date('H:i'); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Two Column Section -->
                <div class="row g-4">
                    <!-- Stok Rendah Section -->
                    <div class="col-lg-6" id="stokRendah">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-exclamation-triangle text-warning me-2"></i> Perhatian: Stok Hampir Habis</h5>
                                <span class="badge bg-danger"><?php echo count($barang_stok_rendah); ?> item</span>
                            </div>
                            <div class="card-body">
                                <?php if (count($barang_stok_rendah) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Kode</th>
                                                    <th>Nama Barang</th>
                                                    <th>Varian</th>
                                                    <th>Stok Sisa</th>
                                                    <th class="text-center">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($barang_stok_rendah as $item): ?>
                                                <tr>
                                                    <td data-label="Kode"><span class="badge bg-secondary"><?php echo $item['kode_barang']; ?></span></td>
                                                    <td data-label="Nama Barang" class="fw-bold"><?php echo htmlspecialchars($item['nama_barang']); ?></td>
                                                    <td data-label="Varian"><?php echo htmlspecialchars($item['varian'] ?? '-'); ?></td>
                                                    <td data-label="Stok Sisa"><span class="badge bg-danger p-2"><?php echo $item['stok_barang']; ?> pcs</span></td>
                                                    <td data-label="Aksi" class="text-center">
                                                        <a href="barang/edit.php?id=<?php echo $item['id_barang']; ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                                                            <i class="bi bi-plus-circle me-1"></i> Restock
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Mobile Summary -->
                                    <div class="d-block d-md-none mt-3 text-muted small">
                                        <i class="bi bi-info-circle"></i> Total <?php echo count($barang_stok_rendah); ?> barang dengan stok di bawah 10 pcs
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="bi bi-check-circle text-success"></i>
                                        <p class="text-muted mt-3">Semua stok aman terkendali!</p>
                                        <small class="text-muted">Tidak ada barang dengan stok di bawah 10 pcs</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if (count($barang_stok_rendah) > 0): ?>
                            <div class="card-footer bg-white py-3">
                                <a href="transaksi/index.php" class="btn btn-primary-custom btn-sm w-100">
                                    <i class="bi bi-arrow-right me-2"></i> Lakukan Supply Sekarang
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Transaksi Terbaru Section -->
                    <div class="col-lg-6" id="transaksiHariIni">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-clock-history me-2 text-info"></i> Transaksi Terbaru</h5>
                                <a href="transaksi/index.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                            </div>
                            <div class="card-body">
                                <?php if (count($transaksi_terbaru) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Tanggal</th>
                                                    <th>Barang</th>
                                                    <th>Jenis</th>
                                                    <th>Jumlah</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($transaksi_terbaru as $trans): ?>
                                                <tr>
                                                    <td data-label="Tanggal"><?php echo date('H:i', strtotime($trans['tanggal_transaksi'])); ?></td>
                                                    <td data-label="Barang" class="fw-bold"><?php echo htmlspecialchars($trans['nama_barang']); ?></td>
                                                    <td data-label="Jenis">
                                                        <span class="badge <?php echo $trans['status'] == 'masuk' ? 'bg-success' : 'bg-danger'; ?>">
                                                            <?php echo ucfirst($trans['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td data-label="Jumlah">
                                                        <span class="badge bg-primary"><?php echo $trans['jumlah']; ?> pcs</span>
                                                    </td>
                                                    <td data-label="Status">
                                                        <span class="badge bg-success">Berhasil</span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Mobile Summary -->
                                    <div class="d-block d-md-none mt-3 text-muted small">
                                        <i class="bi bi-info-circle"></i> Menampilkan 5 transaksi terbaru
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <p class="text-muted mt-3">Belum ada transaksi hari ini</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-white py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="bi bi-arrow-repeat me-1"></i> Update real-time
                                    </small>
                                    <small class="text-muted">
                                        Total: <?php echo count($transaksi_terbaru); ?> transaksi
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    
    <script>
        // Sidebar Collapse untuk Desktop
        document.getElementById('sidebarCollapse')?.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.getElementById('mainContent').classList.toggle('expanded');
            
            // Simpan status sidebar di localStorage
            const isCollapsed = document.querySelector('.sidebar').classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });

        // Sidebar Toggle untuk Mobile
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Tutup sidebar saat klik di luar untuk mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const toggle = document.getElementById('sidebarToggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });

        // Cek status sidebar dari localStorage
        document.addEventListener('DOMContentLoaded', function() {
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed && window.innerWidth > 768) {
                document.querySelector('.sidebar').classList.add('collapsed');
                document.getElementById('mainContent').classList.add('expanded');
            }
        });

        // Inisialisasi Chart
        const ctx = document.getElementById('transactionChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [
                    {
                        label: 'Barang Masuk',
                        data: <?php echo json_encode($chart_masuk); ?>,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Barang Keluar',
                        data: <?php echo json_encode($chart_keluar); ?>,
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Handle resize window
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                const sidebar = document.querySelector('.sidebar');
                const isMobile = window.innerWidth <= 768;
                
                if (isMobile) {
                    sidebar.classList.remove('collapsed');
                    document.getElementById('mainContent').classList.remove('expanded');
                } else {
                    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                    if (isCollapsed) {
                        sidebar.classList.add('collapsed');
                        document.getElementById('mainContent').classList.add('expanded');
                    }
                }
            }, 250);
        });

        // Auto refresh data setiap 5 menit (optional)
        // setInterval(() => {
        //     window.location.reload();
        // }, 300000);

        // Tooltip initialization
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Smooth scroll untuk anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>