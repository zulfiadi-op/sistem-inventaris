<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

/* =======================
   PROSES TAMBAH SUPPLY (DIINTEGRASIKAN)
======================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_supply') {
    $id_barang = mysqli_real_escape_string($conn, $_POST['id_barang']);
    $jumlah = mysqli_real_escape_string($conn, $_POST['jumlah']);
    $supplier = mysqli_real_escape_string($conn, $_POST['supplier']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $id_user = $_SESSION['user_id'];
    
    // Mulai transaksi
    mysqli_begin_transaction($conn);
    
    try {
        // 1. Tambah transaksi
        $query_insert = "
        INSERT INTO transaksi (id_barang, jumlah, supplier, keterangan, tanggal_transaksi, id_user, status)
        VALUES ('$id_barang', '$jumlah', '$supplier', '$keterangan', NOW(), '$id_user', 'masuk')
        ";
        
        if (!mysqli_query($conn, $query_insert)) {
            throw new Exception("Gagal menambah transaksi: " . mysqli_error($conn));
        }
        
        // 2. Update stok
        $query_update = "
        UPDATE barang 
        SET stok_barang = stok_barang + $jumlah 
        WHERE id_barang = '$id_barang'
        ";
        
        if (!mysqli_query($conn, $query_update)) {
            throw new Exception("Gagal update stok: " . mysqli_error($conn));
        }
        
        // Commit transaksi
        mysqli_commit($conn);
        
        $_SESSION['success_message'] = "Supply berhasil ditambahkan! Jumlah: $jumlah pcs";
        
    } catch (Exception $e) {
        // Rollback jika ada error
        mysqli_rollback($conn);
        $_SESSION['error_message'] = $e->getMessage();
    }
    
    // Redirect untuk menghindari resubmit
    header('Location: index.php');
    exit();
}

/* =======================
   TAMPILKAN PESAN SUKSES/ERROR
======================= */
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

/* =======================
   DATA BARANG
======================= */
$query = "SELECT * FROM barang ORDER BY nama_barang";
$result = mysqli_query($conn, $query);

/* =======================
   SUPPLY HARI INI
======================= */
$supply_hari_ini = [];
$total_supply_hari_ini = 0;

$query_supply = "
SELECT 
    t.*,
    b.nama_barang,
    b.kode_barang
FROM transaksi t
JOIN barang b ON t.id_barang = b.id_barang
WHERE DATE(t.tanggal_transaksi) = CURDATE()
AND t.status = 'masuk'
ORDER BY t.tanggal_transaksi DESC
";

$result_supply = mysqli_query($conn, $query_supply);
while ($row = mysqli_fetch_assoc($result_supply)) {
    $supply_hari_ini[] = $row;
    $total_supply_hari_ini += $row['jumlah'];
}

/* =======================
   TOTAL STOK
======================= */
$query_total_stok = "SELECT SUM(stok_barang) AS total_stok FROM barang";
$result_total_stok = mysqli_query($conn, $query_total_stok);
$total_stok = mysqli_fetch_assoc($result_total_stok)['total_stok'] ?? 0;

/* =======================
   CHART 7 HARI
======================= */
$query_chart = "
SELECT DATE(tanggal_transaksi) AS tanggal, SUM(jumlah) AS total
FROM transaksi
WHERE status = 'masuk'
AND tanggal_transaksi >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY DATE(tanggal_transaksi)
ORDER BY tanggal
";
$result_chart = mysqli_query($conn, $query_chart);
$chart_data = [];
while ($row = mysqli_fetch_assoc($result_chart)) {
    $chart_data[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Transaksi Supply - Sistem Inventaris</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
        }
        
        /* Sidebar Styles */
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
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            white-space: nowrap;
            text-decoration: none;
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
        
        /* Cards */
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
        
        .card-header small {
            font-size: 0.85rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        /* Stat Cards */
        .stat-card {
            border-left: 4px solid;
            padding-left: 1.5rem;
            height: 100%;
        }
        
        .stat-card .card-body {
            padding: 1.25rem;
        }
        
        .stat-card.total-stok {
            border-color: var(--primary-color);
        }
        
        .stat-card.supply-hari-ini {
            border-color: var(--success-color);
        }
        
        .stat-card h6 {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card h3 {
            font-size: 1.75rem;
            margin-bottom: 0;
            font-weight: 600;
        }
        
        /* Barang Cards */
        .card-roti {
            cursor: pointer;
            border-top: 4px solid var(--primary-color);
            transition: all 0.3s ease;
            position: relative;
            height: 100%;
            overflow: hidden;
        }
        
        .card-roti:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(67, 97, 238, 0.15);
        }
        
        .stok-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
            z-index: 10;
        }
        
        .roti-icon {
            font-size: 3rem;
            color: var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .card-roti:hover .roti-icon {
            transform: scale(1.1);
        }
        
        .btn-supply {
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(76, 201, 240, 0.3);
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-supply:hover {
            transform: translateX(-50%) scale(1.1);
            background-color: var(--primary-color);
        }
        
        .btn-supply i {
            font-size: 1.2rem;
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
        
        /* Button Custom */
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
        
        /* Modal Styles */
        .modal-content {
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }
        
        .modal-header-custom {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 1.25rem;
        }
        
        .modal-header-custom .btn-close {
            filter: brightness(0) invert(1);
        }
        
        .modal-footer {
            border-top: 1px solid #eee;
            padding: 1.25rem;
        }
        
        .supply-item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
            transition: all 0.2s ease;
        }
        
        .supply-item:hover {
            background-color: rgba(67, 97, 238, 0.02);
            padding-left: 10px;
        }
        
        .supply-item:last-child {
            border-bottom: none;
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
            
            .search-container {
                max-width: 250px;
            }
            
            .card-body {
                padding: 1.25rem;
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
            
            .container-fluid.p-4 {
                padding: 1rem !important;
            }
            
            .row.mb-4 {
                margin-bottom: 0.5rem !important;
            }
            
            .col-xl-3.col-md-6.mb-4 {
                margin-bottom: 0.75rem !important;
            }
            
            .stat-card {
                padding-left: 1rem;
            }
            
            .stat-card h3 {
                font-size: 1.25rem;
            }
            
            .stat-card i {
                font-size: 2rem !important;
            }
            
            .card-header {
                padding: 1rem;
            }
            
            .card-header h5 {
                font-size: 1.1rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            /* Transform table for mobile */
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
            
            .table td[data-label="Waktu"]::before { content: "Waktu"; }
            .table td[data-label="Barang"]::before { content: "Barang"; }
            .table td[data-label="Kode"]::before { content: "Kode"; }
            .table td[data-label="Jumlah"]::before { content: "Jumlah"; }
            .table td[data-label="Supplier"]::before { content: "Supplier"; }
            .table td[data-label="Status"]::before { content: "Status"; }
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
            
            .search-container {
                margin-top: 0.5rem;
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
            
            .container-fluid.p-4 {
                padding: 0.75rem !important;
            }
            
            .stat-card .card-body {
                padding: 1rem;
            }
            
            .stat-card h6 {
                font-size: 0.8rem;
            }
            
            .stat-card h3 {
                font-size: 1.1rem;
            }
            
            .stat-card i {
                font-size: 1.75rem !important;
            }
            
            .col-xl-3.col-lg-4.col-md-6 {
                padding: 0 0.5rem;
            }
            
            .card-roti .card-body {
                padding: 1rem 0.75rem;
            }
            
            .roti-icon {
                font-size: 2.5rem;
            }
            
            .card-roti h6 {
                font-size: 0.95rem;
            }
            
            .card-roti small {
                font-size: 0.75rem;
            }
            
            .card-roti p {
                font-size: 0.9rem;
            }
            
            .btn-supply {
                width: 35px;
                height: 35px;
                bottom: -12px;
            }
            
            .btn-supply i {
                font-size: 1rem;
            }
            
            .modal-dialog {
                margin: 0.5rem;
            }
            
            .modal-header-custom {
                padding: 1rem;
            }
            
            .modal-header-custom h5 {
                font-size: 1.1rem;
            }
            
            .modal-body {
                padding: 1rem;
            }
            
            .modal-footer {
                padding: 1rem;
            }
            
            .modal-footer .btn {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }
        
        /* Print Styles */
        @media print {
            .sidebar, .navbar-custom, .btn-supply, .user-info .btn, .search-container {
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
            
            .table td::before {
                display: none;
            }
            
            .badge {
                border: 1px solid #000;
                color: #000 !important;
                background: transparent !important;
            }
        }
        
        /* Loading Animation */
        .swal2-loading {
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
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
        
        .card {
            animation: fadeIn 0.5s ease-out;
        }
        
        /* Hover Effects */
        .btn-close-white:hover {
            opacity: 1;
            transform: rotate(90deg);
            transition: all 0.3s ease;
        }
        
        .badge {
            transition: all 0.2s ease;
        }
        
        .badge:hover {
            transform: scale(1.05);
        }
        
        /* Form Focus States */
        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
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
        
        /* Utility Classes */
        .cursor-pointer {
            cursor: pointer;
        }
        
        .hover-scale {
            transition: transform 0.2s ease;
        }
        
        .hover-scale:hover {
            transform: scale(1.05);
        }
        
        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar d-flex flex-column" id="sidebar">
            <div class="logo-container">
                <h4 class="mb-0"><i class="bi bi-box-seam"></i> <span>Inventaris Roti</span></h4>
                <small class="text-light"><span>Supply Management</span></small>
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
                <a href="../transaksi/" class="nav-link-custom active">
                    <i class="bi bi-arrow-left-right"></i>
                    <span>Transaksi</span>
                </a>
                <a href="../laporan/laporan.php" class="nav-link-custom">
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
                            <i class="bi bi-arrow-left-right text-primary"></i> <span class="d-none d-sm-inline">Transaksi</span> Supply
                        </h4>
                        
                        <div class="search-container me-auto ms-3 ms-md-3">
                            <i class="bi bi-search search-icon"></i>
                            <input type="text" class="form-control search-input" id="searchBarang" placeholder="Cari barang...">
                        </div>
                        
                        <div class="d-flex align-items-center">
                            <span class="ms-3 d-none d-lg-block text-muted">
                                <i class="bi bi-calendar-check"></i> <?php echo date('d F Y'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </nav>
            
            <!-- Content -->
            <div class="container-fluid p-4">
                <!-- Statistik Cards -->
                <div class="row mb-4 g-3">
                    <div class="col-xl-6 col-md-6">
                        <div class="card stat-card total-stok h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Stok</h6>
                                        <h3 class="mb-0"><?php echo number_format($total_stok); ?> <small class="text-muted fs-6">pcs</small></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-box-seam" style="font-size: 2.5rem; color: var(--primary-color);"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-6 col-md-6">
                        <div class="card stat-card supply-hari-ini h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Supply Hari Ini</h6>
                                        <h3 class="mb-0"><?php echo number_format($total_supply_hari_ini); ?> <small class="text-muted fs-6">pcs</small></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-arrow-down-circle" style="font-size: 2.5rem; color: var(--success-color);"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Daftar Barang -->
                <div class="card mb-4">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0"><i class="bi bi-box me-2"></i> Daftar Barang</h5>
                            <small class="text-muted">Klik barang atau tombol (+) untuk melakukan supply</small>
                        </div>
                        <span class="badge bg-primary mt-2 mt-sm-0" id="totalBarang"></span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3" id="barangContainer">
                            <?php 
                            // Reset pointer result untuk digunakan kembali
                            mysqli_data_seek($result, 0);
                            $total_barang = mysqli_num_rows($result);
                            while ($barang = mysqli_fetch_assoc($result)): 
                            ?>
                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                                <div class="card card-roti h-100 position-relative"
                                    data-nama="<?= strtolower($barang['nama_barang']) ?>"
                                    onclick="openSupplyModal(
                                        <?= $barang['id_barang'] ?>,
                                        '<?= htmlspecialchars($barang['nama_barang']) ?>',
                                        '<?= $barang['kode_barang'] ?>',
                                        <?= $barang['stok_barang'] ?>
                                    )">
                                    <div class="card-body text-center position-relative">
                                        <span class="badge stok-badge 
                                            <?= $barang['stok_barang'] < 10 ? 'bg-danger' : ($barang['stok_barang'] < 20 ? 'bg-warning' : 'bg-success') ?>">
                                            <?= $barang['stok_barang'] ?> pcs
                                        </span>

                                        <i class="bi bi-bread-slice roti-icon mb-3"></i>

                                        <h6 class="fw-bold text-truncate"><?= htmlspecialchars($barang['nama_barang']) ?></h6>
                                        <small class="text-muted"><?= $barang['kode_barang'] ?></small>

                                        <p class="fw-bold text-primary mt-2 mb-0">
                                            Rp <?= number_format($barang['harga_jual'], 0, ',', '.') ?>
                                        </p>

                                        <button class="btn btn-success btn-supply"
                                            onclick="event.stopPropagation(); openSupplyModal(
                                                <?= $barang['id_barang'] ?>,
                                                '<?= htmlspecialchars($barang['nama_barang']) ?>',
                                                '<?= $barang['kode_barang'] ?>',
                                                <?= $barang['stok_barang'] ?>
                                            )">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        
                        <?php if ($total_barang == 0): ?>
                        <div class="empty-state">
                            <i class="bi bi-box"></i>
                            <p class="text-muted">Belum ada barang tersedia</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Supply Hari Ini -->
                <div class="card">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0"><i class="bi bi-calendar-day me-2"></i> Supply Hari Ini</h5>
                            <small class="text-muted"><?php echo date('d F Y'); ?></small>
                        </div>
                        <span class="badge bg-primary mt-2 mt-sm-0"><?= count($supply_hari_ini) ?> transaksi</span>
                    </div>
                    <div class="card-body">
                        <?php if (count($supply_hari_ini) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Waktu</th>
                                            <th>Barang</th>
                                            <th>Kode</th>
                                            <th>Jumlah</th>
                                            <th>Supplier</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($supply_hari_ini as $supply): ?>
                                        <tr>
                                            <td data-label="Waktu"><?php echo date('H:i', strtotime($supply['tanggal_transaksi'])); ?></td>
                                            <td data-label="Barang" class="fw-bold"><?php echo htmlspecialchars($supply['nama_barang']); ?></td>
                                            <td data-label="Kode"><span class="badge bg-secondary"><?php echo $supply['kode_barang']; ?></span></td>
                                            <td data-label="Jumlah"><span class="badge bg-primary"><?php echo $supply['jumlah']; ?> pcs</span></td>
                                            <td data-label="Supplier"><?php echo $supply['supplier'] ?? '-'; ?></td>
                                            <td data-label="Status"><span class="badge bg-success">Berhasil</span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Total info untuk mobile -->
                            <div class="d-block d-md-none mt-3 text-muted small">
                                <i class="bi bi-info-circle"></i> Total: <?= $total_supply_hari_ini ?> pcs dari <?= count($supply_hari_ini) ?> transaksi
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <p class="text-muted mt-2">Belum ada supply hari ini</p>
                                <small class="text-muted">Klik tombol (+) pada barang untuk menambah supply</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Supply Barang -->
    <div class="modal fade" id="modalSupply" tabindex="-1" aria-labelledby="modalSupplyLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title" id="modalSupplyLabel">
                        <i class="bi bi-plus-circle me-2"></i> Supply Barang
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- FORM DENGAN ACTION KE FILE YANG SAMA -->
                <form id="formSupply" method="POST" action="">
                    <input type="hidden" name="action" value="add_supply">
                    <input type="hidden" name="id_barang" id="modalIdBarang">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Barang</label>
                            <input type="text" class="form-control bg-light" id="modalNamaBarang" readonly>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Kode Barang</label>
                                <input type="text" class="form-control bg-light" id="modalKodeBarang" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Stok Saat Ini</label>
                                <input type="text" class="form-control bg-light" id="modalStokBarang" readonly>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Jumlah Supply <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" name="jumlah" id="modalJumlah" min="1" required placeholder="Masukkan jumlah">
                            <div class="form-text">Minimal 1 pcs</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Supplier <span class="text-danger">*</span></label>
                            <select class="form-select" name="supplier" id="modalSupplier" required>
                                <option value="">Pilih Supplier</option>
                                <option value="Supplier A">Supplier A</option>
                                <option value="Supplier B">Supplier B</option>
                                <option value="Supplier C">Supplier C</option>
                                <option value="Supplier D">Supplier D</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Keterangan</label>
                            <textarea class="form-control" name="keterangan" id="modalKeterangan" rows="2" placeholder="Opsional"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2 d-none d-sm-inline"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-primary-custom" id="btnSubmitSupply">
                            <i class="bi bi-save me-2"></i> Simpan Supply
                        </button>
                    </div>
                </form>
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
            
            // Update total barang counter
            const totalBarang = <?= $total_barang ?>;
            document.getElementById('totalBarang').textContent = totalBarang + ' barang';
        });
        
        // Search Barang dengan debounce
        let searchTimeout;
        document.getElementById('searchBarang').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const searchTerm = e.target.value.toLowerCase().trim();
            
            searchTimeout = setTimeout(() => {
                const barangCards = document.querySelectorAll('.card-roti');
                let visibleCount = 0;
                
                barangCards.forEach(card => {
                    const namaBarang = card.getAttribute('data-nama');
                    const parentCol = card.closest('.col-xl-3, .col-lg-4, .col-md-6, .col-sm-6');
                    
                    if (namaBarang.includes(searchTerm) || searchTerm === '') {
                        parentCol.style.display = 'block';
                        visibleCount++;
                    } else {
                        parentCol.style.display = 'none';
                    }
                });
                
                // Update total visible
                document.getElementById('totalBarang').textContent = visibleCount + ' barang ditemukan';
                
                // Tampilkan pesan jika tidak ada hasil
                if (visibleCount === 0 && searchTerm !== '') {
                    const container = document.getElementById('barangContainer');
                    let noResult = document.getElementById('noSearchResult');
                    
                    if (!noResult) {
                        noResult = document.createElement('div');
                        noResult.id = 'noSearchResult';
                        noResult.className = 'col-12 text-center py-5';
                        noResult.innerHTML = `
                            <i class="bi bi-search" style="font-size: 3rem; color: #ddd;"></i>
                            <p class="text-muted mt-3">Tidak ada barang dengan nama "${searchTerm}"</p>
                        `;
                        container.appendChild(noResult);
                    }
                } else {
                    const noResult = document.getElementById('noSearchResult');
                    if (noResult) noResult.remove();
                }
            }, 300);
        });
        
        // Modal Functions
        function openSupplyModal(id, nama, kode, stok) {
            document.getElementById('modalIdBarang').value = id;
            document.getElementById('modalNamaBarang').value = nama;
            document.getElementById('modalKodeBarang').value = kode;
            document.getElementById('modalStokBarang').value = stok + ' pcs';
            document.getElementById('modalJumlah').value = '';
            document.getElementById('modalSupplier').value = '';
            document.getElementById('modalKeterangan').value = '';
            
            const modal = new bootstrap.Modal(document.getElementById('modalSupply'));
            modal.show();
        }
        
        // Form Submission dengan validasi client-side menggunakan Sweet Alert
        document.getElementById('formSupply').addEventListener('submit', function(e) {
            e.preventDefault(); // Mencegah submit form langsung
            
            const jumlah = document.getElementById('modalJumlah').value;
            const supplier = document.getElementById('modalSupplier').value;
            const namaBarang = document.getElementById('modalNamaBarang').value;
            const stokSaatIni = document.getElementById('modalStokBarang').value;
            
            // Validasi dengan Sweet Alert
            if (!jumlah || jumlah < 1) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Jumlah Tidak Valid',
                    text: 'Harap masukkan jumlah yang valid (minimal 1)',
                    confirmButtonColor: '#4361ee',
                    confirmButtonText: 'Mengerti'
                });
                return false;
            }
            
            if (!supplier) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Supplier Belum Dipilih',
                    text: 'Harap pilih supplier terlebih dahulu',
                    confirmButtonColor: '#4361ee',
                    confirmButtonText: 'Mengerti'
                });
                return false;
            }
            
            // Konfirmasi sebelum submit
            Swal.fire({
                title: 'Konfirmasi Supply',
                html: `
                    <div style="text-align: left; max-height: 300px; overflow-y: auto;">
                        <table style="width: 100%;">
                            <tr>
                                <td style="padding: 5px 0;"><strong>Barang:</strong></td>
                                <td style="padding: 5px 0;">${namaBarang}</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 0;"><strong>Jumlah:</strong></td>
                                <td style="padding: 5px 0;"><span class="badge bg-primary">${jumlah} pcs</span></td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 0;"><strong>Supplier:</strong></td>
                                <td style="padding: 5px 0;">${supplier}</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 0;"><strong>Stok Setelah Supply:</strong></td>
                                <td style="padding: 5px 0;">${parseInt(stokSaatIni) + parseInt(jumlah)} pcs</td>
                            </tr>
                        </table>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4cc9f0',
                cancelButtonColor: '#f72585',
                confirmButtonText: '<i class="bi bi-check-circle me-2"></i> Ya, Supply!',
                cancelButtonText: '<i class="bi bi-x-circle me-2"></i> Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Tampilkan loading
                    Swal.fire({
                        title: 'Memproses...',
                        html: 'Mohon tunggu sebentar',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Submit form setelah konfirmasi
                    e.target.submit();
                }
            });
        });
        
        // Tampilkan pesan sukses/error dengan Sweet Alert
        <?php if (!empty($success_message)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?php echo addslashes($success_message); ?>',
                showConfirmButton: true,
                confirmButtonColor: '#4cc9f0',
                timer: 3000,
                timerProgressBar: true,
                toast: true,
                position: 'top-end',
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
        });
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: '<?php echo addslashes($error_message); ?>',
                showConfirmButton: true,
                confirmButtonColor: '#f72585',
                confirmButtonText: 'Mengerti'
            });
        });
        <?php endif; ?>
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
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
        
        // Auto close alerts
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(alert => {
                if (alert) {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 5000);
        
        // Keyboard shortcut untuk refresh (Ctrl+R)
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                Swal.fire({
                    title: 'Memuat ulang...',
                    text: 'Harap tunggu sebentar',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                });
            }
        });
        
        // Touch events untuk mobile
        if ('ontouchstart' in window) {
            document.querySelectorAll('.card-roti').forEach(card => {
                card.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.98)';
                });
                
                card.addEventListener('touchend', function() {
                    this.style.transform = '';
                });
            });
        }
    </script>
</body>
</html>