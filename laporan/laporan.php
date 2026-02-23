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

// PERBAIKAN: Query tanpa JOIN ke users, gunakan id_user dari transaksi
$query_laporan = "
SELECT 
    t.*,
    b.nama_barang,
    b.kode_barang
FROM transaksi t
JOIN barang b ON t.id_barang = b.id_barang
$query_where
ORDER BY t.tanggal_transaksi DESC
";

$result_laporan = mysqli_query($conn, $query_laporan);
$total_rows = mysqli_num_rows($result_laporan);

// Statistik laporan - PERBAIKAN: Query tanpa subquery yang kompleks
$query_stats = "
SELECT 
    COUNT(*) as total_transaksi,
    SUM(CASE WHEN t.status = 'masuk' THEN t.jumlah ELSE 0 END) as total_masuk,
    SUM(CASE WHEN t.status = 'keluar' THEN t.jumlah ELSE 0 END) as total_keluar
FROM transaksi t
$query_where
";

$result_stats = mysqli_query($conn, $query_stats);
$stats = mysqli_fetch_assoc($result_stats);

// Hitung rata-rata, maksimum, minimum secara terpisah jika perlu
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

// Persiapkan data untuk chart
$query_chart = "
SELECT 
    DATE(tanggal_transaksi) as tanggal,
    SUM(CASE WHEN status = 'masuk' THEN jumlah ELSE 0 END) as masuk,
    SUM(CASE WHEN status = 'keluar' THEN jumlah ELSE 0 END) as keluar
FROM transaksi
WHERE DATE(tanggal_transaksi) BETWEEN '$start_date' AND '$end_date'
GROUP BY DATE(tanggal_transaksi)
ORDER BY tanggal
";

$result_chart = mysqli_query($conn, $query_chart);
$chart_data = [];
$dates = [];
$masuk_data = [];
$keluar_data = [];

while ($row = mysqli_fetch_assoc($result_chart)) {
    $chart_data[] = $row;
    $dates[] = date('d/m', strtotime($row['tanggal']));
    $masuk_data[] = (int)$row['masuk'];
    $keluar_data[] = (int)$row['keluar'];
}

// Query untuk mendapatkan top barang
$query_top = "
SELECT 
    b.nama_barang,
    b.kode_barang,
    COUNT(t.id_transaksi) as frekuensi,
    SUM(CASE WHEN t.status = 'masuk' THEN t.jumlah ELSE 0 END) as total_masuk,
    SUM(CASE WHEN t.status = 'keluar' THEN t.jumlah ELSE 0 END) as total_keluar
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
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --danger-color: #f72585;
            --warning-color: #f8961e;
            --light-color: #f8f9fa;
            --dark-color: #212529;
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
            padding-left: 1.5rem;
        }
        
        .stat-card.total {
            border-color: var(--primary-color);
        }
        
        .stat-card.masuk {
            border-color: var(--success-color);
        }
        
        .stat-card.keluar {
            border-color: var(--danger-color);
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
        
        .badge-keluar {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(247, 37, 133, 0.3);
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
                <a href="/auth/logout.php" class="btn btn-outline-light btn-sm w-100 mt-2">
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
                                <option value="masuk" <?php echo $filter_status === 'masuk' ? 'selected' : ''; ?>>Barang Masuk</option>
                                <option value="keluar" <?php echo $filter_status === 'keluar' ? 'selected' : ''; ?>>Barang Keluar</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-funnel me-2"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Statistik Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card total">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Transaksi</h6>
                                        <h3 class="mb-0"><?php echo number_format($stats['total_transaksi'] ?? 0); ?></h3>
                                        <small class="text-muted"><?php echo $total_rows; ?> data ditampilkan</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-list-check" style="font-size: 2.5rem; color: var(--primary-color);"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card masuk">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="text-muted mb-2">Barang Masuk</h6>
                                        <h3 class="mb-0"><?php echo number_format($stats['total_masuk'] ?? 0); ?> pcs</h3>
                                        <small class="text-muted">
                                            <?php 
                                            $avg_masuk = ($stats['total_transaksi'] > 0 && $stats['total_masuk'] > 0) ? 
                                                number_format($stats['total_masuk'] / $stats['total_transaksi'], 1) : 0;
                                            echo $avg_masuk; ?> pcs/transaksi
                                        </small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-arrow-down-circle" style="font-size: 2.5rem; color: var(--success-color);"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card keluar">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="text-muted mb-2">Barang Keluar</h6>
                                        <h3 class="mb-0"><?php echo number_format($stats['total_keluar'] ?? 0); ?> pcs</h3>
                                        <small class="text-muted">
                                            <?php 
                                            $avg_keluar = ($stats['total_transaksi'] > 0 && $stats['total_keluar'] > 0) ? 
                                                number_format($stats['total_keluar'] / $stats['total_transaksi'], 1) : 0;
                                            echo $avg_keluar; ?> pcs/transaksi
                                        </small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-arrow-up-circle" style="font-size: 2.5rem; color: var(--danger-color);"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
                
                <!-- Chart -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title mb-3">
                                    <i class="bi bi-graph-up me-2"></i> Grafik Transaksi (<?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?>)
                                </h6>
                                <div class="chart-container">
                                    <canvas id="transaksiChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
            
                </div>
                
                <!-- Tabel Laporan -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-table me-2"></i> Data Transaksi</h5>
                        <small class="text-muted">Periode: <?php echo date('d F Y', strtotime($start_date)); ?> - <?php echo date('d F Y', strtotime($end_date)); ?></small>
                    </div>
                    <div class="card-body">
                        <?php if ($total_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Tanggal & Waktu</th>
                                            <th>Barang</th>
                                            <th>Kode</th>
                                            <th>Jumlah</th>
                                            <th>Status</th>
                                            <th>Supplier/Penerima</th>
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
                                                <small><?php echo date('d/m/Y', strtotime($row['tanggal_transaksi'])); ?></small><br>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($row['tanggal_transaksi'])); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo $row['kode_barang']; ?></span></td>
                                            <td>
                                                <span class="badge <?php echo $row['status'] === 'masuk' ? 'badge-masuk' : 'badge-keluar'; ?>">
                                                    <?php echo $row['jumlah']; ?> pcs
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $row['status'] === 'masuk' ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo $row['status'] === 'masuk' ? 'MASUK' : 'KELUAR'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $row['supplier'] ? htmlspecialchars($row['supplier']) : '-'; ?></td>
                                            <td><small><?php echo $row['keterangan'] ? htmlspecialchars($row['keterangan']) : '-'; ?></small></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 4rem; color: #ddd;"></i>
                                <p class="text-muted mt-3">Tidak ada data transaksi untuk periode yang dipilih</p>
                                <a href="?" class="btn btn-primary">
                                    <i class="bi bi-arrow-clockwise me-2"></i> Tampilkan Semua Data
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($total_rows > 0): ?>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                Menampilkan <?php echo $total_rows; ?> data transaksi
                            </small>
                            <div>
                                <button class="btn btn-sm btn-outline-primary" onclick="window.print()">
                                    <i class="bi bi-printer me-1"></i> Cetak
                                </button>
                                <button class="btn btn-sm btn-outline-success" onclick="exportToExcel()">
                                    <i class="bi bi-file-earmark-excel me-1"></i> Excel
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
    
    <script>
        // Toggle Sidebar on Mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        // Chart Configuration
        <?php if (count($dates) > 0): ?>
        const ctx = document.getElementById('transaksiChart').getContext('2d');
        const transaksiChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: 'Barang Masuk',
                    data: <?php echo json_encode($masuk_data); ?>,
                    borderColor: '#4cc9f0',
                    backgroundColor: 'rgba(76, 201, 240, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Barang Keluar',
                    data: <?php echo json_encode($keluar_data); ?>,
                    borderColor: '#f72585',
                    backgroundColor: 'rgba(247, 37, 133, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Jumlah (pcs)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Tanggal'
                        }
                    }
                }
            }
        });
        <?php else: ?>
        // Jika tidak ada data chart, sembunyikan chart container
        document.getElementById('transaksiChart').parentElement.innerHTML = 
            '<div class="text-center py-4">' +
            '<i class="bi bi-graph-up" style="font-size: 3rem; color: #ddd;"></i>' +
            '<p class="text-muted mt-2">Tidak ada data chart untuk periode ini</p>' +
            '</div>';
        <?php endif; ?>
        
        // Export to Excel function
        function exportToExcel() {
            // Simple table export (in a real application, use a library like SheetJS)
            let table = document.querySelector('.table');
            let rows = table.querySelectorAll('tr');
            let csv = [];
            
            rows.forEach(row => {
                let rowData = [];
                row.querySelectorAll('th, td').forEach(cell => {
                    // Remove badge HTML and get text only
                    let text = cell.innerText.replace(/\n/g, ' ').trim();
                    rowData.push(text);
                });
                csv.push(rowData.join(','));
            });
            
            let csvContent = csv.join('\n');
            let blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            let link = document.createElement('a');
            let url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', 'laporan_transaksi_<?php echo date('Y-m-d'); ?>.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            alert('Data berhasil diekspor ke CSV!');
        }
        
        // Print styles
        window.addEventListener('beforeprint', () => {
            document.querySelector('.sidebar').style.display = 'none';
            document.querySelector('.main-content').style.marginLeft = '0';
            document.querySelector('.navbar-custom').style.display = 'none';
            document.querySelector('.filter-card').style.display = 'none';
        });
        
        window.addEventListener('afterprint', () => {
            document.querySelector('.sidebar').style.display = '';
            document.querySelector('.main-content').style.marginLeft = '250px';
            document.querySelector('.navbar-custom').style.display = '';
            document.querySelector('.filter-card').style.display = '';
        });
    </script>
</body>
</html>