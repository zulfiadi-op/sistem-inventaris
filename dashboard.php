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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Inventaris</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Sidebar Styling Sesuai Data Barang */
        .sidebar {
            background: linear-gradient(180deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            color: white;
            position: fixed;
            width: 250px;
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .logo-container {
            padding: 2rem 1rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .nav-link-custom {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 10px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: block;
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

        .user-info {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding: 1rem;
            margin-top: auto;
        }

        /* Main Content Styling */
        .main-content {
            margin-left: 250px;
            min-height: 100vh;
            transition: margin-left 0.3s;
        }

        .navbar-custom {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
            padding: 1rem 1.5rem;
        }

        /* Dashboard Cards */
        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,.05);
            transition: transform 0.3s;
            border-left: 5px solid;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,.05);
        }

        @media (max-width: 768px) {
            .sidebar { width: 0; overflow: hidden; }
            .main-content { margin-left: 0; }
            .sidebar.active { width: 250px; }
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <div class="sidebar d-flex flex-column">
            <div class="logo-container">
                <h4 class="mb-0"><i class="bi bi-box-seam"></i> Inventaris Roti</h4>
                <small class="text-light">Management System</small>
            </div>
            
            <nav class="nav flex-column mt-3">
                <a href="dashboard.php" class="nav-link-custom active">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="barang/index.php" class="nav-link-custom">
                    <i class="bi bi-box"></i> Data Barang
                </a>
                <a href="transaksi/index.php" class="nav-link-custom">
                    <i class="bi bi-arrow-left-right"></i> Transaksi
                </a>
                <a href="laporan/laporan.php" class="nav-link-custom">
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
                <a href="auth/logout.php" class="btn btn-outline-light btn-sm w-100 mt-2">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
        
        <div class="main-content flex-grow-1">
            <nav class="navbar navbar-custom">
                <div class="container-fluid">
                    <button class="btn btn-outline-primary d-md-none" id="sidebarToggle">
                        <i class="bi bi-list"></i>
                    </button>
                    
                    <div class="d-flex align-items-center w-100">
                        <h4 class="mb-0 me-auto d-none d-md-block">
                            <i class="bi bi-speedometer2 text-primary"></i> Dashboard
                        </h4>
                        
                        <div class="d-flex align-items-center">
                            <span class="me-3 d-none d-md-block text-muted">
                                <i class="bi bi-calendar-check"></i> <?php echo date('d F Y'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </nav>
            
            <div class="container-fluid p-4">
                <p class="text-muted">Overview sistem inventaris roti Anda</p>
                
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card" style="border-left-color: var(--primary-color);">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Barang</h6>
                                        <h3 class="mb-0 fw-bold"><?php echo number_format($total_barang); ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-box text-primary" style="font-size: 2.5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card" style="border-left-color: var(--success-color);">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Stok</h6>
                                        <h3 class="mb-0 fw-bold"><?php echo number_format($total_stok); ?> <small style="font-size: 14px">pcs</small></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-stack text-success" style="font-size: 2.5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card" style="border-left-color: var(--warning-color);">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="text-muted mb-2">Stok Rendah</h6>
                                        <h3 class="mb-0 fw-bold text-danger"><?php echo number_format($stok_rendah); ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-exclamation-triangle text-warning" style="font-size: 2.5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card" style="border-left-color: var(--info-color);">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="text-muted mb-2">Transaksi Hari Ini</h6>
                                        <h3 class="mb-0 fw-bold"><?php echo number_format($transaksi_hari_ini); ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-arrow-left-right text-info" style="font-size: 2.5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0 fw-bold"><i class="bi bi-exclamation-triangle text-warning me-2"></i> Perhatian: Stok Hampir Habis</h5>
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
                                                    <td><span class="badge bg-secondary"><?php echo $item['kode_barang']; ?></span></td>
                                                    <td class="fw-bold"><?php echo htmlspecialchars($item['nama_barang']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['varian'] ?? '-'); ?></td>
                                                    <td><span class="badge bg-danger p-2"><?php echo $item['stok_barang']; ?> pcs</span></td>
                                                    <td class="text-center">
                                                        <a href="barang/edit.php?id=<?php echo $item['id_barang']; ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                                                            Update Stok
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                                        <p class="text-muted mt-3">Semua stok aman terkendali!</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar Toggle untuk Mobile
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>