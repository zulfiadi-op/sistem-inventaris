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
        // 1. Tambah transaksi - PERBAIKI: HAPUS id_transaksi dari query
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        
        .card-roti {
            cursor: pointer;
            border-top: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .card-roti:hover {
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.15);
        }
        
        .stok-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
        
        .roti-icon {
            font-size: 3rem;
            color: var(--primary-color);
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
        }
        
        .stat-card {
            border-left: 4px solid;
            padding-left: 1.5rem;
        }
        
        .stat-card.total-stok {
            border-color: var(--primary-color);
        }
        
        .stat-card.supply-hari-ini {
            border-color: var(--success-color);
        }
        
        .supply-item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        
        .supply-item:last-child {
            border-bottom: none;
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
        
        .btn-primary-custom {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 25px;
            padding: 8px 20px;
            color: white;
        }
        
        .btn-primary-custom:hover {
            background: linear-gradient(45deg, var(--secondary-color), var(--primary-color));
            color: white;
        }
        
        .modal-header-custom {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px 15px 0 0;
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
        
        .user-info {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding: 1rem;
            margin-top: auto;
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
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar d-flex flex-column">
            <div class="logo-container">
                <h4 class="mb-0"><i class="bi bi-box-seam"></i> Inventaris Roti</h4>
                <small class="text-light">Supply Management</small>
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
                <a href="/laporan/laporan.php" class="nav-link-custom active">
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
                            <i class="bi bi-arrow-left-right text-primary"></i> Transaksi Supply
                        </h4>
                        
                        <div class="search-container me-auto">
                            <i class="bi bi-search search-icon"></i>
                            <input type="text" class="form-control search-input" id="searchBarang" placeholder="Cari barang...">
                        </div>
                        
                        <div class="d-flex align-items-center">
                            <span class="me-3 d-none d-md-block">
                                <i class="bi bi-calendar-check"></i> <?php echo date('d F Y'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </nav>
            
            <!-- Content -->
            <div class="container-fluid p-4">
                <!-- Statistik Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card total-stok">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Stok</h6>
                                        <h3 class="mb-0"><?php echo number_format($total_stok); ?> pcs</h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-box-seam" style="font-size: 2.5rem; color: var(--primary-color);"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card supply-hari-ini">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="text-muted mb-2">Supply Hari Ini</h6>
                                        <h3 class="mb-0"><?php echo number_format($total_supply_hari_ini); ?> pcs</h3>
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
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-box me-2"></i> Daftar Barang</h5>
                        <small class="text-muted">Klik barang untuk melakukan transaksi supply</small>
                    </div>
                    <div class="card-body">
                        <div class="row" id="barangContainer">
                            <?php 
                            // Reset pointer result untuk digunakan kembali
                            mysqli_data_seek($result, 0);
                            while ($barang = mysqli_fetch_assoc($result)): 
                            ?>
                            <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                                <div class="card card-roti h-100"
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

                                        <h6 class="fw-bold"><?= htmlspecialchars($barang['nama_barang']) ?></h6>
                                        <small class="text-muted"><?= $barang['kode_barang'] ?></small>

                                        <p class="fw-bold text-primary mt-2">
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
                    </div>
                </div>
                
                <!-- Supply Hari Ini -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-calendar-day me-2"></i> Supply Hari Ini</h5>
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
                                            <td><?php echo date('H:i', strtotime($supply['tanggal_transaksi'])); ?></td>
                                            <td><?php echo htmlspecialchars($supply['nama_barang']); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo $supply['kode_barang']; ?></span></td>
                                            <td><span class="badge bg-primary"><?php echo $supply['jumlah']; ?> pcs</span></td>
                                            <td><?php echo $supply['supplier'] ?? '-'; ?></td>
                                            <td><span class="badge bg-success">Berhasil</span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: #ddd;"></i>
                                <p class="text-muted mt-2">Belum ada supply hari ini</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Supply Barang -->
    <div class="modal fade" id="modalSupply" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i> Supply Barang</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <!-- FORM DENGAN ACTION KE FILE YANG SAMA -->
                <form id="formSupply" method="POST" action="">
                    <input type="hidden" name="action" value="add_supply">
                    <input type="hidden" name="id_barang" id="modalIdBarang">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Barang</label>
                            <input type="text" class="form-control" id="modalNamaBarang" readonly>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kode Barang</label>
                                <input type="text" class="form-control" id="modalKodeBarang" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stok Saat Ini</label>
                                <input type="text" class="form-control" id="modalStokBarang" readonly>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Jumlah Supply <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="jumlah" id="modalJumlah" min="1" required>
                            <div class="form-text">Masukkan jumlah barang yang akan ditambahkan</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Supplier</label>
                            <select class="form-select" name="supplier" id="modalSupplier" required>
                                <option value="">Pilih Supplier</option>
                                <option value="Supplier A">Supplier A</option>
                                <option value="Supplier B">Supplier B</option>
                                <option value="Supplier C">Supplier C</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" id="modalKeterangan" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
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
        // Toggle Sidebar on Mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        // Search Barang
        document.getElementById('searchBarang').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const barangCards = document.querySelectorAll('.card-roti');
            
            barangCards.forEach(card => {
                const namaBarang = card.getAttribute('data-nama');
                if (namaBarang.includes(searchTerm)) {
                    card.closest('.col-xl-3').style.display = 'block';
                } else {
                    card.closest('.col-xl-3').style.display = 'none';
                }
            });
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
            
            // Validasi dengan Sweet Alert
            if (!jumlah || jumlah < 1) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Jumlah Tidak Valid',
                    text: 'Harap masukkan jumlah yang valid (minimal 1)',
                    confirmButtonColor: '#4361ee'
                });
                return false;
            }
            
            if (!supplier) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Supplier Belum Dipilih',
                    text: 'Harap pilih supplier terlebih dahulu',
                    confirmButtonColor: '#4361ee'
                });
                return false;
            }
            
            // Konfirmasi sebelum submit
            Swal.fire({
                title: 'Konfirmasi Supply',
                html: `
                    <div style="text-align: left">
                        <p><strong>Barang:</strong> ${namaBarang}</p>
                        <p><strong>Jumlah:</strong> ${jumlah} pcs</p>
                        <p><strong>Supplier:</strong> ${supplier}</p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4cc9f0',
                cancelButtonColor: '#f72585',
                confirmButtonText: 'Ya, Supply!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Tampilkan loading
                    Swal.fire({
                        title: 'Memproses...',
                        html: 'Mohon tunggu sebentar',
                        allowOutsideClick: false,
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
                timerProgressBar: true
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
                confirmButtonColor: '#f72585'
            });
        });
        <?php endif; ?>
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>