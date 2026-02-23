<?php
// layout.php di root folder
session_start();
require_once 'config/database.php';

// Set default timezone
date_default_timezone_set('Asia/Jakarta');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventaris Roti - <?php echo $pageTitle ?? 'Dashboard'; ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/custom.css">
    
    <style>
    :root {
        --primary: #4361ee;
        --primary-dark: #3a56d4;
        --secondary: #4cc9f0;
        --light: #f8f9fa;
        --dark: #212529;
    }
    
    body {
        background-color: #f5f7fb;
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        padding-top: 60px; /* Untuk navbar fixed */
    }
    
    /* Navbar Styling */
    .navbar-main {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        height: 60px;
    }
    
    .navbar-brand {
        font-weight: 700;
        font-size: 1.25rem;
    }
    
    /* Sidebar */
    .sidebar-wrapper {
        position: fixed;
        top: 60px;
        left: 0;
        height: calc(100vh - 60px);
        width: 250px;
        background: white;
        box-shadow: 2px 0 10px rgba(0,0,0,0.05);
        z-index: 100;
        overflow-y: auto;
        transition: all 0.3s;
    }
    
    .sidebar-logo {
        padding: 20px;
        border-bottom: 1px solid #eaeaea;
        background: linear-gradient(to right, var(--primary), var(--primary-dark));
        color: white;
    }
    
    .sidebar-nav {
        padding: 15px 0;
    }
    
    .nav-item-sidebar {
        margin: 5px 15px;
    }
    
    .nav-link-sidebar {
        color: #333;
        padding: 10px 15px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        text-decoration: none;
        transition: all 0.3s;
    }
    
    .nav-link-sidebar:hover {
        background-color: #eef2ff;
        color: var(--primary);
    }
    
    .nav-link-sidebar.active {
        background: linear-gradient(to right, var(--primary), var(--primary-dark));
        color: white;
        font-weight: 500;
    }
    
    .nav-link-sidebar i {
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }
    
    /* Main Content */
    .main-content-wrapper {
        margin-left: 250px;
        padding: 25px;
        transition: all 0.3s;
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .sidebar-wrapper {
            transform: translateX(-100%);
        }
        
        .sidebar-wrapper.active {
            transform: translateX(0);
        }
        
        .main-content-wrapper {
            margin-left: 0;
        }
        
        .navbar-toggler-sidebar {
            display: block !important;
        }
    }
    
    @media (min-width: 993px) {
        .navbar-toggler-sidebar {
            display: none !important;
        }
    }
    
    /* Content Styling */
    .content-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        padding: 25px;
        margin-bottom: 25px;
    }
    
    .content-header {
        margin-bottom: 30px;
    }
    
    .content-header h1 {
        color: var(--primary);
        font-weight: 700;
        margin-bottom: 5px;
    }
    
    .content-header p {
        color: #666;
        font-size: 1.1rem;
    }
    
    /* Stat Cards */
    .stat-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.08);
        transition: transform 0.3s, box-shadow 0.3s;
        border-left: 4px solid var(--primary);
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.1);
    }
    
    /* Product Cards */
    .product-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        border: 1px solid #eaeaea;
        transition: all 0.3s;
        cursor: pointer;
        text-align: center;
    }
    
    .product-card:hover {
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        transform: translateY(-3px);
        border-color: var(--primary);
    }
    
    /* Buttons */
    .btn-primary-custom {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border: none;
        padding: 10px 25px;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s;
    }
    
    .btn-primary-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
    }
    
    /* Table */
    .table-container {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }
    
    /* Alert */
    .alert-custom {
        border-radius: 10px;
        border: none;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-main navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <button class="navbar-toggler navbar-toggler-sidebar me-2" type="button" id="sidebarToggle">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-box-seam me-2"></i> Inventaris Roti
            </a>
            
            <div class="d-flex align-items-center ms-auto">
                <span class="navbar-text text-white me-3 d-none d-md-block">
                    <i class="bi bi-person-circle me-1"></i> 
                    <?php echo $_SESSION['username'] ?? $_SESSION['nama_lengkap'] ?? 'Administrator'; ?>
                </span>
                
                <a href="auth/logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar-wrapper" id="sidebar">
        <div class="sidebar-logo">
            <h5 class="fw-bold mb-1"><i class="bi bi-box-seam"></i> Supply Management</h5>
            <small class="text-light">Sistem Inventaris Roti</small>
        </div>
        
        <div class="sidebar-nav">
            <?php
            // Get current page for active menu
            $currentPage = basename($_SERVER['PHP_SELF']);
            $currentDir = basename(dirname($_SERVER['PHP_SELF']));
            ?>
            
            <div class="nav-item-sidebar">
                <a href="dashboard.php" class="nav-link-sidebar <?php echo ($currentPage == 'dashboard.php' || $currentPage == 'index.php') ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </div>
            
            <div class="nav-item-sidebar">
                <a href="barang/index.php" class="nav-link-sidebar <?php echo ($currentDir == 'barang') ? 'active' : ''; ?>">
                    <i class="bi bi-box"></i> Data Barang
                </a>
            </div>
            
            <div class="nav-item-sidebar">
                <a href="transaksi/index.php" class="nav-link-sidebar <?php echo ($currentDir == 'transaksi' && $currentPage != 'supply.php') ? 'active' : ''; ?>">
                    <i class="bi bi-arrow-left-right"></i> Transaksi
                </a>
            </div>
            
            <div class="nav-item-sidebar">
                <a href="transaksi/supply.php" class="nav-link-sidebar <?php echo ($currentPage == 'supply.php') ? 'active' : ''; ?>">
                    <i class="bi bi-truck"></i> Supply Baru
                </a>
            </div>
            
            <div class="nav-item-sidebar">
                <a href="cetak/supply.php" class="nav-link-sidebar <?php echo ($currentDir == 'cetak') ? 'active' : ''; ?>">
                    <i class="bi bi-printer"></i> Cetak Laporan
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content-wrapper" id="mainContent">
        <!-- Content akan diisi oleh masing-masing halaman -->
        <?php if (!isset($noContent)): ?>
        <?php endif; ?>
</body>
</html>