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
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == 1;

// DEBUG: Cek nilai status yang ada di database (hanya jika debug mode aktif)
$status_values = [];
$sample_data = [];
$total_supplier = 0;

if ($debug_mode) {
    $query_cek_status = "SELECT DISTINCT status FROM transaksi WHERE supplier IS NOT NULL AND supplier != ''";
    $result_cek = mysqli_query($conn, $query_cek_status);
    if ($result_cek) {
        while ($row = mysqli_fetch_assoc($result_cek)) {
            $status_values[] = $row['status'];
        }
    }
    
    // Cek total transaksi dengan supplier
    $query_cek_supplier = "SELECT COUNT(*) as total FROM transaksi WHERE supplier IS NOT NULL AND supplier != ''";
    $result_supplier = mysqli_query($conn, $query_cek_supplier);
    if ($result_supplier) {
        $total_supplier = mysqli_fetch_assoc($result_supplier)['total'];
    }
    
    // Ambil sample data
    if ($total_supplier > 0) {
        $query_actual = "SELECT status, supplier, tanggal_transaksi, jumlah FROM transaksi WHERE supplier IS NOT NULL AND supplier != '' LIMIT 5";
        $result_actual = mysqli_query($conn, $query_actual);
        if ($result_actual) {
            while ($row = mysqli_fetch_assoc($result_actual)) {
                $sample_data[] = $row;
            }
        }
    }
}

// Query untuk laporan - Menampilkan semua transaksi supplier (tanpa filter status)
$query_laporan = "
SELECT 
    t.*,
    b.nama_barang,
    b.kode_barang,
    t.supplier as nama_supplier
FROM transaksi t
JOIN barang b ON t.id_barang = b.id_barang
WHERE t.supplier IS NOT NULL 
AND t.supplier != ''
AND DATE(t.tanggal_transaksi) BETWEEN '$start_date' AND '$end_date'
ORDER BY t.tanggal_transaksi DESC
";

$result_laporan = mysqli_query($conn, $query_laporan);

// Cek error query
if (!$result_laporan) {
    die("Error query laporan: " . mysqli_error($conn));
}

$total_rows = mysqli_num_rows($result_laporan);

// Statistik laporan - Menghitung semua transaksi supplier
$query_stats = "
SELECT 
    COUNT(*) as total_transaksi,
    SUM(t.jumlah) as total_barang,
    AVG(t.jumlah) as rata_rata_barang,
    MAX(t.jumlah) as maks_barang,
    MIN(t.jumlah) as min_barang,
    SUM(CASE WHEN LOWER(t.status) = 'terkirim' OR LOWER(t.status) = 'in' OR LOWER(t.status) = 'berhasil' THEN 1 ELSE 0 END) as total_terkirim,
    SUM(CASE WHEN LOWER(t.status) = 'keluar' OR LOWER(t.status) = 'out' THEN 1 ELSE 0 END) as total_keluar,
    SUM(CASE WHEN LOWER(t.status) = 'terkirim' OR LOWER(t.status) = 'in' OR LOWER(t.status) = 'berhasil' THEN t.jumlah ELSE 0 END) as total_barang_terkirim,
    SUM(CASE WHEN LOWER(t.status) = 'keluar' OR LOWER(t.status) = 'out' THEN t.jumlah ELSE 0 END) as total_barang_keluar
FROM transaksi t
WHERE t.supplier IS NOT NULL 
AND t.supplier != ''
AND DATE(t.tanggal_transaksi) BETWEEN '$start_date' AND '$end_date'
";

$result_stats = mysqli_query($conn, $query_stats);

// Cek error query
if (!$result_stats) {
    die("Error query stats: " . mysqli_error($conn));
}

$stats = mysqli_fetch_assoc($result_stats);

// Persiapkan data untuk chart
$query_chart = "
SELECT 
    DATE(tanggal_transaksi) as tanggal,
    SUM(jumlah) as total_barang
FROM transaksi
WHERE supplier IS NOT NULL 
AND supplier != ''
AND DATE(tanggal_transaksi) BETWEEN '$start_date' AND '$end_date'
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
$barang_data = [];

while ($row = mysqli_fetch_assoc($result_chart)) {
    $chart_data[] = $row;
    $dates[] = date('d/m', strtotime($row['tanggal']));
    $barang_data[] = (int)$row['total_barang'];
}

// Query untuk mendapatkan top barang
$query_top = "
SELECT 
    b.nama_barang,
    b.kode_barang,
    COUNT(t.id_transaksi) as frekuensi,
    SUM(t.jumlah) as total_barang,
    SUM(CASE WHEN LOWER(t.status) = 'terkirim' OR LOWER(t.status) = 'in' OR LOWER(t.status) = 'berhasil' THEN t.jumlah ELSE 0 END) as total_terkirim,
    SUM(CASE WHEN LOWER(t.status) = 'keluar' OR LOWER(t.status) = 'out' THEN t.jumlah ELSE 0 END) as total_keluar
FROM transaksi t
JOIN barang b ON t.id_barang = b.id_barang
WHERE t.supplier IS NOT NULL 
AND t.supplier != ''
AND DATE(t.tanggal_transaksi) BETWEEN '$start_date' AND '$end_date'
GROUP BY t.id_barang
ORDER BY total_barang DESC
LIMIT 5
";

$result_top = mysqli_query($conn, $query_top);
$top_barang = [];
while ($row = mysqli_fetch_assoc($result_top)) {
    $top_barang[] = $row;
}

// Query untuk statistik supplier
$query_supplier_stats = "
SELECT 
    supplier,
    COUNT(*) as total_transaksi,
    SUM(jumlah) as total_barang,
    SUM(CASE WHEN LOWER(status) = 'terkirim' OR LOWER(status) = 'in' OR LOWER(status) = 'berhasil' THEN jumlah ELSE 0 END) as total_terkirim,
    SUM(CASE WHEN LOWER(status) = 'keluar' OR LOWER(status) = 'out' THEN jumlah ELSE 0 END) as total_keluar
FROM transaksi 
WHERE supplier IS NOT NULL 
AND supplier != ''
AND DATE(tanggal_transaksi) BETWEEN '$start_date' AND '$end_date'
GROUP BY supplier
ORDER BY total_barang DESC
LIMIT 5
";

$result_supplier_stats = mysqli_query($conn, $query_supplier_stats);
$supplier_stats = [];
while ($row = mysqli_fetch_assoc($result_supplier_stats)) {
    $supplier_stats[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Laporan Transaksi Supplier - Sistem Inventaris Roti</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Sweet Alert CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- jsPDF dan jsPDF-AutoTable untuk PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
    <!-- SheetJS untuk export Excel yang lebih rapi -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --danger-color: #f72585;
            --warning-color: #f8961e;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --supplier-color: #f72585;
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
            background-color: #f5f7fb;
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

        /* Search Container */
        .search-container {
            position: relative;
            max-width: 300px;
            width: 100%;
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 10;
        }
        
        .search-input {
            padding-left: 40px;
            border-radius: 25px;
            border: 1px solid #ddd;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
            border-color: var(--primary-color);
        }

        /* Filter Card */
        .filter-card {
            background-color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,.03);
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,.03);
            transition: all 0.3s ease;
            margin-bottom: 1rem;
            animation: fadeIn 0.5s ease-out;
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
        
        .card-header h5,
        .card-header h6 {
            margin-bottom: 0;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .card-footer {
            background-color: white;
            border-top: 1px solid #eee;
            padding: 1rem 1.5rem;
            border-radius: 0 0 15px 15px !important;
        }

        /* Stat Cards */
        .stat-card {
            border-left: 4px solid;
            height: 100%;
        }
        
        .stat-card .card-body {
            padding: 1.25rem;
        }
        
        .stat-card.total {
            border-color: var(--primary-color);
        }
        
        .stat-card.terkirim {
            border-color: #17a2b8;
        }
        
        .stat-card.keluar {
            border-color: var(--supplier-color);
        }
        
        .stat-card h6 {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card h2 {
            font-size: 2rem;
            margin-bottom: 0.25rem;
            font-weight: 700;
        }
        
        .stat-card small {
            font-size: 0.85rem;
        }

        /* Badge Styles */
        .badge {
            padding: 0.5em 0.75em;
            font-weight: 500;
            font-size: 0.75rem;
            border-radius: 6px;
        }
        
        .badge-terkirim {
            background-color: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
            border: 1px solid rgba(23, 162, 184, 0.3);
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.85rem;
        }
        
        .badge-keluar {
            background-color: rgba(247, 37, 133, 0.1);
            color: #f72585;
            border: 1px solid rgba(247, 37, 133, 0.3);
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.85rem;
        }

        /* Button Styles */
        .btn-print {
            background: linear-gradient(45deg, var(--warning-color), #f9c74f);
            border: none;
            color: white;
            padding: 0.5rem 1.2rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        
        .btn-print:hover {
            background: linear-gradient(45deg, #e68a00, var(--warning-color));
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(248, 150, 30, 0.3);
        }
        
        .btn-primary-custom {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary-custom:hover {
            background: linear-gradient(45deg, var(--secondary-color), var(--primary-color));
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-pdf {
            background: linear-gradient(45deg, #dc3545, #c82333);
            border: none;
            color: white;
            padding: 0.5rem 1.2rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        
        .btn-pdf:hover {
            background: linear-gradient(45deg, #c82333, #dc3545);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
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

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        /* Debug Panel */
        .debug-panel {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            color: #721c24;
        }
        
        .debug-panel pre {
            background-color: #fff;
            padding: 0.5rem;
            border-radius: 5px;
            border: 1px solid #f5c6cb;
        }

        /* Responsive Breakpoints */
        @media (max-width: 1200px) {
            .stat-card h2 {
                font-size: 1.75rem;
            }
            
            .table {
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 992px) {
            .navbar-custom h4 {
                font-size: 1.25rem;
            }
            
            .search-container {
                max-width: 250px;
            }
            
            .card-body {
                padding: 1.25rem;
            }
            
            .stat-card i {
                font-size: 2.5rem !important;
            }
            
            .stat-card h2 {
                font-size: 1.5rem;
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
                margin-right: 1rem;
            }
            
            .navbar-custom .container-fluid {
                flex-wrap: wrap;
            }
            
            .search-container {
                max-width: 100%;
                margin: 0.5rem 0 0 0 !important;
            }
            
            .filter-card {
                padding: 1rem;
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
                padding: 1rem;
            }
            
            .stat-card h6 {
                font-size: 0.8rem;
            }
            
            .stat-card h2 {
                font-size: 1.25rem;
            }
            
            .stat-card small {
                font-size: 0.7rem;
            }
            
            .stat-card i {
                font-size: 2rem !important;
            }
            
            .card-header {
                padding: 1rem;
            }
            
            .card-header h5 {
                font-size: 1rem;
            }
            
            .card-header h6 {
                font-size: 0.95rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .card-footer {
                padding: 0.75rem 1rem;
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
                min-width: 100px;
            }
            
            .table td[data-label="No"]::before { content: "No"; }
            .table td[data-label="Tanggal"]::before { content: "Tanggal"; }
            .table td[data-label="Barang"]::before { content: "Barang"; }
            .table td[data-label="Kode"]::before { content: "Kode"; }
            .table td[data-label="Jumlah"]::before { content: "Jumlah"; }
            .table td[data-label="Status"]::before { content: "Status"; }
            .table td[data-label="Supplier"]::before { content: "Supplier"; }
            .table td[data-label="Keterangan"]::before { content: "Keterangan"; }
            
            .chart-container {
                height: 250px;
            }
            
            .btn-print span, .btn-pdf span {
                display: none;
            }
            
            .btn-print i, .btn-pdf i {
                margin-right: 0 !important;
            }
            
            .badge-terkirim, .badge-keluar {
                font-size: 0.75rem;
                padding: 0.3rem 0.6rem;
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
            
            .search-input {
                padding-left: 35px;
                font-size: 0.9rem;
                height: 38px;
            }
            
            .search-icon {
                left: 12px;
                font-size: 0.9rem;
            }
            
            .filter-card .row {
                --bs-gutter-y: 0.5rem;
            }
            
            .filter-card .btn {
                margin-top: 0.5rem;
            }
            
            .container-fluid.p-4 {
                padding: 0.75rem !important;
            }
            
            .stat-card .card-body {
                padding: 0.75rem;
            }
            
            .stat-card h2 {
                font-size: 1.1rem;
            }
            
            .stat-card h6 {
                font-size: 0.7rem;
            }
            
            .stat-card i {
                font-size: 1.5rem !important;
            }
            
            .card-header {
                padding: 0.75rem;
            }
            
            .card-header h5 {
                font-size: 0.9rem;
            }
            
            .card-header h6 {
                font-size: 0.85rem;
            }
            
            .card-body {
                padding: 0.75rem;
            }
            
            .badge {
                font-size: 0.65rem;
                padding: 0.3rem 0.5rem;
            }
            
            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
            
            .chart-container {
                height: 200px;
            }
            
            .table td::before {
                min-width: 80px;
                font-size: 0.8rem;
            }
            
            .table td {
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
        
        .empty-state small {
            color: #adb5bd;
        }

        /* Print Styles */
        @media print {
            .sidebar, .navbar-custom, .filter-card, .btn-print, .btn-pdf, .btn-primary-custom, 
            .btn-outline-primary, .btn-outline-secondary, .user-info .btn,
            .search-container, .card-footer .btn, .debug-panel, .btn-debug {
                display: none !important;
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
            
            .badge-terkirim, .badge-keluar {
                border: 1px solid #000;
                background: transparent !important;
            }
            
            .table td::before {
                display: none;
            }
            
            .table tr {
                border-bottom: 1px solid #ddd;
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
                <small class="text-light"><span>Report Management</span></small>
            </div>
            
            <nav class="nav flex-column mt-3">
                <a href="../dashboard.php" class="nav-link-custom">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="../barang/" class="nav-link-custom">
                    <i class="bi bi-box"></i>
                    <span>Data Barang</span>
                </a>
                <a href="../transaksi/" class="nav-link-custom">
                    <i class="bi bi-arrow-left-right"></i>
                    <span>Transaksi</span>
                </a>
                <a href="laporan.php" class="nav-link-custom active">
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
                <a href="../auth/logout.php" class="btn btn-outline-light btn-sm w-100 mt-2">
                    <i class="bi bi-box-arrow-right"></i> <span>Logout</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content flex-grow-1" id="mainContent">
            <!-- Top Navbar -->
            <nav class="navbar navbar-custom">
                <div class="container-fluid">
                    <div class="d-flex align-items-center w-100 flex-wrap flex-md-nowrap">
                        <!-- Sidebar Toggle Buttons -->
                        <button class="btn btn-outline-primary me-3 d-none d-md-block" id="sidebarCollapse">
                            <i class="bi bi-layout-sidebar"></i>
                        </button>
                        <button class="btn btn-outline-primary d-md-none me-3" id="sidebarToggle">
                            <i class="bi bi-list"></i>
                        </button>
                        
                        <h4 class="mb-0 me-auto">
                            <i class="bi bi-file-text text-primary"></i> <span class="d-none d-sm-inline">Laporan Transaksi Supplier</span>
                        </h4>
                        
                        <div class="search-container me-auto ms-3 ms-md-3">
                            <i class="bi bi-search search-icon"></i>
                            <input type="text" class="form-control search-input" id="searchTable" placeholder="Cari data...">
                        </div>
                        
                        <div class="ms-auto d-flex align-items-center gap-2">
                            <span class="me-3 d-none d-lg-block text-muted">
                                <i class="bi bi-calendar-check"></i> <?php echo date('d F Y'); ?>
                            </span>
                            <button class="btn btn-pdf" onclick="exportToPDF()">
                                <i class="bi bi-file-pdf"></i> <span class="d-none d-sm-inline ms-2">Cetak PDF</span>
                            </button>
                            <button class="btn btn-print" onclick="window.print()">
                                <i class="bi bi-printer"></i> <span class="d-none d-sm-inline ms-2">Cetak Printer</span>
                            </button>
                        </div>
                    </div>
                </div>
            </nav>
            
            <div class="container-fluid p-4">
                <!-- Filter Laporan -->
                <div class="filter-card">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Tanggal Mulai</label>
                            <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Tanggal Akhir</label>
                            <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary-custom w-100">
                                <i class="bi bi-funnel me-2"></i> <span class="d-none d-sm-inline">Terapkan</span>
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Statistik Cards -->
                <div class="row mb-4 g-3">
                    <div class="col-xl-4 col-md-4">
                        <div class="card stat-card total">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Transaksi Supplier</h6>
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
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-4 col-md-4">
                        <div class="card stat-card terkirim">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="text-muted mb-2">Barang Dikirim Ke Supplier</h6>
                                        <h2 class="mb-0 fw-bold"><?php echo number_format($stats['total_barang_terkirim'] ?? 0); ?> <small class="fs-6">buah</small></h2>
                                        <small class="text-muted">
                                            <i class="bi bi-truck me-1"></i>
                                            <?php echo number_format($stats['total_terkirim'] ?? 0); ?> transaksi
                                        </small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-truck" style="font-size: 3rem; color: #17a2b8; opacity: 0.3;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Chart dan Top Supplier -->
                <div class="row mb-4 g-3">
                    <div class="col-lg-8">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0 fw-bold">
                                        <i class="bi bi-graph-up me-2 text-primary"></i> 
                                        Grafik Transaksi Supplier
                                    </h6>
                                    <small class="text-muted">Periode <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></small>
                                </div>
                                <div class="d-flex gap-2">
                                    <span class="badge-terkirim">Barang Dikirim</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="transaksiChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="mb-0 fw-bold">
                                    <i class="bi bi-truck me-2" style="color: #f72585;"></i>
                                    Top 5 Supplier
                                </h6>
                                <small class="text-muted">Supplier dengan transaksi terbanyak</small>
                            </div>
                            <div class="card-body">
                                <?php if (count($supplier_stats) > 0): ?>
                                    <?php 
                                    $max_barang = !empty($supplier_stats) ? max(array_column($supplier_stats, 'total_barang')) : 1;
                                    ?>
                                    <?php foreach ($supplier_stats as $index => $supplier): ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="text-truncate" style="max-width: 60%;">
                                                <span class="badge bg-secondary me-2">#<?php echo $index + 1; ?></span>
                                                <strong class="text-truncate"><?php echo htmlspecialchars($supplier['supplier']); ?></strong>
                                            </div>
                                            <span class="badge" style="background-color: #f72585; color: white;">
                                                <?php echo number_format($supplier['total_barang']); ?> buah
                                            </span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar" style="background-color: #f72585; width: <?php echo ($supplier['total_barang'] / $max_barang) * 100; ?>%"></div>
                                        </div>
                                        <div class="d-flex justify-content-between mt-1">
                                            <small class="text-muted"><?php echo $supplier['total_transaksi']; ?> transaksi</small>
                                            <small class="text-muted">
                                                T:<?php echo $supplier['total_terkirim'] ?? 0; ?> | 
                                                K:<?php echo $supplier['total_keluar'] ?? 0; ?>
                                            </small>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="bi bi-truck"></i>
                                        <p class="text-muted mt-2 mb-0">Tidak ada data supplier</p>
                                        <small>Tidak ada transaksi supplier pada periode ini</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Top Barang -->
                <div class="row mb-4 g-3">
                    <div class="col-lg-12">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="mb-0 fw-bold">
                                    <i class="bi bi-box me-2" style="color: #f72585;"></i>
                                    Top 5 Barang (Transaksi Supplier)
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
                                                    <th>Kode Barang</th>
                                                    <th class="text-center">Frekuensi</th>
                                                    <th class="text-end">Total Barang</th>
                                                    <th class="text-end">Dikirim</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($top_barang as $index => $barang): ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($barang['nama_barang']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($barang['kode_barang']); ?></span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-primary"><?php echo $barang['frekuensi']; ?>x</span>
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="badge" style="background-color: #6c757d; color: white;"><?php echo number_format($barang['total_barang']); ?></span>
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="badge-terkirim"><?php echo number_format($barang['total_terkirim'] ?? 0); ?></span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="bi bi-box"></i>
                                        <p class="text-muted mt-2 mb-0">Tidak ada data barang</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabel Laporan -->
                <div class="card">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 fw-bold">
                                <i class="bi bi-table me-2 text-primary"></i> 
                                Detail Transaksi Supplier
                            </h5>
                            <small class="text-muted">Periode <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></small>
                        </div>
                        <div class="d-flex gap-2 mt-2 mt-sm-0">
                            <span class="badge bg-primary">Total: <?php echo $total_rows; ?></span>
                            <span class="badge bg-info">M: <?php echo $stats['total_terkirim'] ?? 0; ?></span>
                            <span class="badge" style="background-color: #f72585;">K: <?php echo $stats['total_keluar'] ?? 0; ?></span>
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
                                            // Tentukan warna badge berdasarkan status
                                            $status_lower = strtolower($row['status']);
                                            $badge_class = 'bg-secondary';
                                            $badge_text = ucfirst($row['status']);
                                            
                                            if ($status_lower == 'terkirim' || $status_lower == 'in') {
                                                $badge_class = 'bg-info';
                                                $badge_text = 'TERKIRIM';
                                            } elseif ($status_lower == 'tertunda' || $status_lower == 'out') {
                                                $badge_class = 'bg-danger';
                                                $badge_text = 'TERTUNDA';
                                            } elseif ($status_lower == 'berhasil') {
                                                $badge_class = 'bg-success';
                                                $badge_text = 'BERHASIL';
                                            }
                                        ?>
                                        <tr>
                                            <td data-label="No"><?php echo $no++; ?></td>
                                            <td data-label="Tanggal">
                                                <div><?php echo date('d/m/Y', strtotime($row['tanggal_transaksi'])); ?></div>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($row['tanggal_transaksi'])); ?> WIB</small>
                                            </td>
                                            <td data-label="Barang">
                                                <strong><?php echo htmlspecialchars($row['nama_barang']); ?></strong>
                                            </td>
                                            <td data-label="Kode">
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($row['kode_barang']); ?></span>
                                            </td>
                                            <td data-label="Jumlah">
                                                <span class="badge bg-info fs-6 p-2">
                                                    <?php echo number_format($row['jumlah']); ?> buah
                                                </span>
                                            </td>
                                            <td data-label="Status">
                                                <span class="badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                                            </td>
                                            <td data-label="Supplier">
                                                <?php if (!empty($row['supplier'])): ?>
                                                    <?php echo htmlspecialchars($row['supplier']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Keterangan">
                                                <?php if (!empty($row['keterangan'])): ?>
                                                    <span data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($row['keterangan']); ?>">
                                                        <i class="bi bi-chat-dots text-muted me-1"></i>
                                                        <small class="d-none d-lg-inline"><?php echo substr(htmlspecialchars($row['keterangan']), 0, 20) . (strlen($row['keterangan']) > 20 ? '...' : ''); ?></small>
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
                            
                            <!-- Mobile Summary -->
                            <div class="d-block d-md-none mt-3 text-muted small">
                                <i class="bi bi-info-circle"></i> Menampilkan <?php echo $total_rows; ?> data transaksi supplier
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-inbox" style="font-size: 4rem;"></i>
                                <p class="text-muted mt-3 fs-5">Tidak ada data transaksi supplier</p>
                                <p class="text-muted">Periode <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
                                <?php if ($debug_mode): ?>
                                    <p class="text-danger">Debug: Pastikan data dengan supplier ada di database dan sesuai range tanggal</p>
                                <?php endif; ?>
                                <a href="?" class="btn btn-primary-custom mt-3">
                                    <i class="bi bi-arrow-clockwise me-2"></i> Reset Filter
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($total_rows > 0): ?>
                    <div class="card-footer bg-transparent">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Menampilkan <?php echo $total_rows; ?> data transaksi supplier
                                </small>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary" onclick="exportToExcel()">
                                    <i class="bi bi-file-earmark-excel me-1"></i> <span class="d-none d-sm-inline">Export Excel</span>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="exportToPDF()">
                                    <i class="bi bi-file-pdf me-1"></i> <span class="d-none d-sm-inline">Export PDF</span>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                    <i class="bi bi-printer me-1"></i> <span class="d-none d-sm-inline">Cetak</span>
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
        
        // Search functionality for table dengan debounce
        let searchTimeout;
        document.getElementById('searchTable').addEventListener('keyup', function() {
            clearTimeout(searchTimeout);
            const searchValue = this.value.toLowerCase();
            
            searchTimeout = setTimeout(() => {
                const tableRows = document.querySelectorAll('#dataTable tbody tr');
                let visibleCount = 0;
                
                tableRows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchValue)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Tampilkan pesan jika tidak ada hasil
                const table = document.getElementById('dataTable');
                let noResult = document.getElementById('noSearchResult');
                
                if (visibleCount === 0 && searchValue !== '') {
                    if (!noResult) {
                        noResult = document.createElement('div');
                        noResult.id = 'noSearchResult';
                        noResult.className = 'text-center py-4';
                        noResult.innerHTML = `
                            <i class="bi bi-search" style="font-size: 2rem; color: #ddd;"></i>
                            <p class="text-muted mt-2">Tidak ada data dengan kata kunci "${searchValue}"</p>
                        `;
                        table.parentElement.appendChild(noResult);
                    }
                } else {
                    if (noResult) noResult.remove();
                }
            }, 300);
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
                        label: 'Total Barang Dikirim',
                        data: <?php echo json_encode($barang_data); ?>,
                        borderColor: '#17a2b8',
                        backgroundColor: 'rgba(23, 162, 184, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#17a2b8',
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
                        display: false
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
            '<div class="empty-state">' +
            '<i class="bi bi-graph-up"></i>' +
            '<p class="text-muted mt-2">Tidak ada data chart untuk periode ini</p>' +
            '</div>';
        <?php endif; ?>
        
        // Export to Excel function yang diperbaiki dengan format lebih rapi
        function exportToExcel() {
            // Ambil data dari tabel
            const table = document.getElementById('dataTable');
            if (!table) return;
            
            // Buat workbook baru
            const wb = XLSX.utils.book_new();
            
            // Ambil header
            const headers = [];
            table.querySelectorAll('thead th').forEach(th => {
                headers.push(th.innerText.trim());
            });
            
            // Ambil data baris
            const rows = [];
            table.querySelectorAll('tbody tr').forEach(row => {
                const rowData = [];
                row.querySelectorAll('td').forEach(cell => {
                    // Bersihkan data dari HTML tags dan ambil teks murni
                    let text = cell.innerText.replace(/\n/g, ' ').trim();
                    rowData.push(text);
                });
                if (rowData.length > 0) {
                    rows.push(rowData);
                }
            });
            
            // Buat worksheet dari data
            const wsData = [headers, ...rows];
            const ws = XLSX.utils.aoa_to_sheet(wsData);
            
            // Atur lebar kolom agar lebih rapi
            const colWidths = [
                { wch: 5 },  // No
                { wch: 20 }, // Tanggal & Waktu
                { wch: 25 }, // Barang
                { wch: 15 }, // Kode
                { wch: 12 }, // Jumlah
                { wch: 12 }, // Status
                { wch: 25 }, // Supplier
                { wch: 30 }  // Keterangan
            ];
            ws['!cols'] = colWidths;
            
            // Tambahkan worksheet ke workbook
            XLSX.utils.book_append_sheet(wb, ws, 'Laporan Supplier');
            
            // Generate filename dengan periode
            const startDate = '<?php echo $start_date; ?>'.replace(/-/g, '');
            const endDate = '<?php echo $end_date; ?>'.replace(/-/g, '');
            const filename = `laporan_supplier_${startDate}_${endDate}.xlsx`;
            
            // Export file
            XLSX.writeFile(wb, filename);
            
            // Tampilkan notifikasi sukses
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Laporan Excel berhasil diekspor',
                showConfirmButton: false,
                timer: 2000,
                toast: true,
                position: 'top-end'
            });
        }

        // Export to PDF function using jsPDF
        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            
            // Buat dokumen PDF baru dengan orientasi landscape
            const doc = new jsPDF({
                orientation: 'landscape',
                unit: 'mm',
                format: 'a4'
            });

            // Warna tema
            const primaryColor = [67, 97, 238]; // #4361ee
            const secondaryColor = [247, 37, 133]; // #f72585
            const infoColor = [23, 162, 184]; // #17a2b8

            // Header PDF
            doc.setFillColor(primaryColor[0], primaryColor[1], primaryColor[2]);
            doc.rect(0, 0, 297, 20, 'F');
            
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(16);
            doc.setFont('helvetica', 'bold');
            doc.text('LAPORAN TRANSAKSI SUPPLIER', 148, 12, { align: 'center' });
            
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.text('Sistem Inventaris Roti', 148, 18, { align: 'center' });
            
            // Periode
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(11);
            doc.setFont('helvetica', 'bold');
            doc.text('Periode:', 14, 30);
            doc.setFont('helvetica', 'normal');
            doc.text(`${formatDate('<?php echo $start_date; ?>')} - ${formatDate('<?php echo $end_date; ?>')}`, 40, 30);
            
            // Tanggal cetak
            doc.setFontSize(10);
            doc.setTextColor(100, 100, 100);
            doc.text(`Dicetak pada: ${new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })} ${new Date().toLocaleTimeString('id-ID')}`, 14, 36);
            
            // Ambil data dari tabel
            const table = document.getElementById('dataTable');
            const headers = [];
            const data = [];
            
            if (table) {
                // Headers
                table.querySelectorAll('thead th').forEach(th => {
                    headers.push(th.innerText.trim());
                });
                
                // Data rows
                table.querySelectorAll('tbody tr').forEach(row => {
                    const rowData = [];
                    row.querySelectorAll('td').forEach(cell => {
                        // Bersihkan data dari HTML tags
                        let text = cell.innerText.replace(/\n/g, ' ').trim();
                        rowData.push(text);
                    });
                    if (rowData.length > 0) {
                        data.push(rowData);
                    }
                });
            }
            
            // Buat tabel di PDF
            doc.autoTable({
                head: [headers],
                body: data,
                startY: 45,
                theme: 'grid',
                styles: {
                    fontSize: 8,
                    cellPadding: 3,
                    lineColor: [200, 200, 200],
                    lineWidth: 0.1,
                },
                headStyles: {
                    fillColor: primaryColor,
                    textColor: [255, 255, 255],
                    fontStyle: 'bold',
                    halign: 'center'
                },
                columnStyles: {
                    0: { cellWidth: 15, halign: 'center' }, // No
                    1: { cellWidth: 35 }, // Tanggal
                    2: { cellWidth: 45 }, // Barang
                    3: { cellWidth: 25 }, // Kode
                    4: { cellWidth: 25, halign: 'right' }, // Jumlah
                    5: { cellWidth: 25, halign: 'center' }, // Status
                    6: { cellWidth: 45 }, // Supplier
                    7: { cellWidth: 45 } // Keterangan
                },
                alternateRowStyles: {
                    fillColor: [245, 245, 245]
                },
                margin: { left: 14, right: 14 },
                didDrawPage: function(data) {
                    // Footer setiap halaman
                    doc.setFontSize(8);
                    doc.setTextColor(150, 150, 150);
                    doc.text('Halaman ' + doc.internal.getNumberOfPages(), 280, 200, { align: 'right' });
                }
            });
            
            // Simpan PDF
            doc.save(`laporan_supplier_${formatDateForFilename('<?php echo $start_date; ?>')}_${formatDateForFilename('<?php echo $end_date; ?>')}.pdf`);
            
            // Tampilkan notifikasi sukses
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Laporan PDF berhasil dibuat',
                showConfirmButton: false,
                timer: 2000,
                toast: true,
                position: 'top-end'
            });
        }

        // Helper function untuk format angka
        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        }

        // Helper function untuk format tanggal
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
        }

        // Helper function untuk format tanggal di filename
        function formatDateForFilename(dateString) {
            const date = new Date(dateString);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}${month}${day}`;
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
            document.querySelector('.btn-pdf').style.display = 'none';
            document.querySelector('.card-footer .btn').style.display = 'none';
            
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
            document.querySelector('.btn-pdf').style.display = '';
            document.querySelector('.card-footer .btn').style.display = '';
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
    </script>
</body>
</html>