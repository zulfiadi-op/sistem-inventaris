<?php
session_start();
require_once '../includes/session.php';
require_once '../includes/functions.php';
requireLogin();

// Gunakan mysqli
require_once __DIR__ . '/../config/database.php';
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$queryKode = "SELECT kode_barang FROM barang ORDER BY kode_barang DESC LIMIT 1";
$resultKode = mysqli_query($conn, $queryKode);

if (mysqli_num_rows($resultKode) > 0) {
    $row = mysqli_fetch_assoc($resultKode);
    $lastKode = $row['kode_barang'];
    $angka = (int) substr($lastKode, 4);
    $angka++;
    $kode_barang_otomatis = 'ROTI' . str_pad($angka, 3, '0', STR_PAD_LEFT);
} else {
    $kode_barang_otomatis = 'ROTI001';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kode_barang = cleanInput($_POST['kode_barang']);
    $nama_barang = cleanInput($_POST['nama_barang']);
    $varian_barang = cleanInput($_POST['varian_barang']);
    $stok_barang = (int)$_POST['stok_barang'];
    $keterangan = cleanInput($_POST['keterangan']);
    $harga_satuan = (int)$_POST['harga_satuan'];
    
    // Hitung harga jual otomatis dengan markup 50%
    $harga_jual = $harga_satuan * 1.5;
    
    $query = "INSERT INTO barang (kode_barang, nama_barang, varian_barang, 
              stok_barang, keterangan, harga_satuan, harga_jual) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'sssisii', 
        $kode_barang, $nama_barang, $varian_barang, 
        $stok_barang, $keterangan, $harga_satuan, $harga_jual);
    
    if (mysqli_stmt_execute($stmt)) {
        // Redirect dengan parameter success
        header('Location: index.php?status=success&message=Barang berhasil ditambahkan');
        exit();
    } else {
        $error = "Gagal menambahkan barang! Error: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
}

$page_title = 'Tambah Barang';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<!-- Tambahkan Sweet Alert CSS dan JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Tambah Barang Baru</h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (isset($error)): ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: '<?php echo $error; ?>',
                    showConfirmButton: true,
                    timer: 3000
                });
            </script>
        <?php endif; ?>
        
        <form method="POST" id="formTambahBarang">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="kode_barang" class="form-label">Kode Barang</label>
                    <input type="text" class="form-control" id="kode_barang" name="kode_barang" value="<?= $kode_barang_otomatis; ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label for="nama_barang" class="form-label">Nama Barang</label>
                    <input type="text" class="form-control" id="nama_barang" name="nama_barang" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="varian_barang" class="form-label">Varian</label>
                    <input type="text" class="form-control" id="varian_barang" name="varian_barang">
                </div>
                <div class="col-md-6">
                    <label for="stok_barang" class="form-label">Stok Awal</label>
                    <input type="number" class="form-control" id="stok_barang" name="stok_barang" required min="0">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="harga_satuan" class="form-label">Harga Satuan (Harga Beli)</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" id="harga_satuan" name="harga_satuan" 
                               required min="0" onchange="calculateHargaJual()">
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="harga_jual_preview" class="form-label">Harga Jual (Otomatis)</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" class="form-control" id="harga_jual_preview" readonly 
                               style="background-color: #e9ecef;">
                        <input type="hidden" id="harga_jual" name="harga_jual">
                    </div>
                    <small class="text-muted">Harga jual dihitung otomatis: Harga satuan + 50%</small>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="keterangan" class="form-label">Keterangan</label>
                <textarea class="form-control" id="keterangan" name="keterangan" rows="2"></textarea>
            </div>
            
            <button type="button" class="btn btn-primary" onclick="confirmSubmit()">
                <i class="bi bi-save"></i> Simpan Barang
            </button>
        </form>
    </div>
</div>

<script>
function calculateHargaJual() {
    const hargaSatuan = document.getElementById('harga_satuan').value;
    if (hargaSatuan) {
        const markup = 50; // 50%
        const hargaJual = Math.round(parseInt(hargaSatuan) * (1 + (markup/100)));
        document.getElementById('harga_jual_preview').value = hargaJual.toLocaleString('id-ID');
        document.getElementById('harga_jual').value = hargaJual;
    }
}

function confirmSubmit() {
    // Validasi form
    const namaBarang = document.getElementById('nama_barang').value;
    const stokBarang = document.getElementById('stok_barang').value;
    const hargaSatuan = document.getElementById('harga_satuan').value;
    
    if (!namaBarang || !stokBarang || !hargaSatuan) {
        Swal.fire({
            icon: 'warning',
            title: 'Form Belum Lengkap',
            text: 'Harap isi semua field yang wajib diisi!',
            showConfirmButton: true,
            timer: 3000
        });
        return;
    }
    
    // Tampilkan konfirmasi dengan Sweet Alert
    Swal.fire({
        title: 'Konfirmasi Simpan',
        text: "Apakah Anda yakin ingin menyimpan data barang ini?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Simpan!',
        cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return new Promise((resolve) => {
                // Submit form
                document.getElementById('formTambahBarang').submit();
                resolve();
            });
        }
    });
}

// Hitung otomatis saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
    calculateHargaJual();
});

// Tampilkan notifikasi jika ada parameter di URL
<?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: '<?php echo isset($_GET['message']) ? $_GET['message'] : 'Data barang berhasil ditambahkan'; ?>',
        showConfirmButton: false,
        timer: 2000
    }).then(() => {
        // Redirect ke halaman index setelah notifikasi
        window.location.href = 'index.php';
    });
});
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>