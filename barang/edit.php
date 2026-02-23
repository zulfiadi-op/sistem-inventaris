<?php
// Aktifkan error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/session.php';
require_once '../includes/functions.php';
requireLogin();

// Gunakan mysqli dari config
require_once __DIR__ . '/../config/database.php';
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Ambil ID dari URL
$id = $_GET['id'] ?? 0;

// Ambil data barang dari database
$barang = getBarangById($id);

// Jika barang tidak ditemukan, redirect ke halaman index
if (!$barang) {
    header('Location: index.php');
    exit();
}

// Proses update data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kode_barang = cleanInput($_POST['kode_barang']);
    $nama_barang = cleanInput($_POST['nama_barang']);
    $varian_barang = cleanInput($_POST['varian_barang']);
    $stok_barang = (int)$_POST['stok_barang'];
    $keterangan = cleanInput($_POST['keterangan']);
    $harga_satuan = (int)$_POST['harga_satuan'];
    $harga_jual = (int)$_POST['harga_jual'];
    
    // Jika harga_jual kosong, hitung otomatis
    if (empty($harga_jual) && !empty($harga_satuan)) {
        $harga_jual = calculateHargaJual($harga_satuan, 50);
    }
    
    // Gunakan mysqli untuk update
    $query = "UPDATE barang SET 
              kode_barang = ?, 
              nama_barang = ?, 
              varian_barang = ?, 
              stok_barang = ?, 
              keterangan = ?, 
              harga_satuan = ?, 
              harga_jual = ?
              WHERE id_barang = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    
    mysqli_stmt_bind_param($stmt, 'sssisiii', 
        $kode_barang, 
        $nama_barang, 
        $varian_barang, 
        $stok_barang, 
        $keterangan, 
        $harga_satuan, 
        $harga_jual, 
        $id
    );
    
    if (mysqli_stmt_execute($stmt)) {
        // Redirect dengan parameter sukses
        header('Location: index.php?message=update_success');
        exit();
    } else {
        $error = "Gagal mengupdate barang! Error: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
}

$page_title = 'Edit Barang';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit Barang</h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="kode_barang" class="form-label">Kode Barang</label>
                    <input type="text" class="form-control" id="kode_barang" name="kode_barang" 
                           value="<?php echo htmlspecialchars($barang['kode_barang'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="nama_barang" class="form-label">Nama Barang</label>
                    <input type="text" class="form-control" id="nama_barang" name="nama_barang" 
                           value="<?php echo htmlspecialchars($barang['nama_barang'] ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="varian_barang" class="form-label">Varian</label>
                    <input type="text" class="form-control" id="varian_barang" name="varian_barang" 
                           value="<?php echo htmlspecialchars($barang['varian_barang'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label for="stok_barang" class="form-label">Stok</label>
                    <input type="number" class="form-control" id="stok_barang" name="stok_barang" 
                           value="<?php echo htmlspecialchars($barang['stok_barang'] ?? 0); ?>" required min="0">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="harga_satuan" class="form-label">Harga Satuan (Harga Beli)</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" id="harga_satuan" name="harga_satuan" 
                               value="<?php echo htmlspecialchars($barang['harga_satuan'] ?? 0); ?>" 
                               required min="0" oninput="calculateHargaJual()">
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="harga_jual" class="form-label">Harga Jual</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" id="harga_jual" name="harga_jual" 
                               value="<?php echo htmlspecialchars($barang['harga_jual'] ?? 0); ?>" 
                               required min="0">
                    </div>
                    <div class="form-text">
                        <small>Margin saat ini: 
                            <span id="margin_text">
                                <?php 
                                $harga_beli = $barang['harga_satuan'] ?? 0;
                                $harga_jual_val = $barang['harga_jual'] ?? 0;
                                $margin = $harga_jual_val - $harga_beli;
                                $persen = ($harga_beli > 0) ? round(($margin/$harga_beli)*100, 1) : 0;
                                echo formatRupiah($margin) . " ($persen%)";
                                ?>
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="applyDefaultMarkup()">
                                <i class="bi bi-calculator"></i> Hitung 50% Markup
                            </button>
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Update Barang
                </button>
                <button type="button" class="btn btn-outline-danger" onclick="window.location.href='index.php'">
                    <i class="bi bi-x-circle"></i> Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Fungsi untuk cek parameter URL dan tampilkan SweetAlert
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('message') === 'update_success') {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Data barang telah diperbarui.',
            timer: 3000,
            showConfirmButton: false
        }).then(() => {
            // Bersihkan parameter URL agar saat direfresh alert tidak muncul lagi
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    }
    
    // Inisialisasi margin display
    calculateHargaJual();
});

function calculateHargaJual() {
    const hargaSatuan = document.getElementById('harga_satuan').value;
    const hargaJualInput = document.getElementById('harga_jual');
    
    if (hargaSatuan) {
        const currentJual = parseFloat(hargaJualInput.value) || 0;
        const currentSatuan = parseFloat(hargaSatuan);
        
        if (currentSatuan > 0) {
            const margin = currentJual - currentSatuan;
            const persen = Math.round((margin / currentSatuan) * 100 * 10) / 10;
            document.getElementById('margin_text').innerText = 
                `Rp ${margin.toLocaleString('id-ID')} (${persen}%)`;
        }
    }
}

function applyDefaultMarkup() {
    const hargaSatuan = document.getElementById('harga_satuan').value;
    if (hargaSatuan) {
        const hargaJual = Math.round(parseInt(hargaSatuan) * 1.5);
        document.getElementById('harga_jual').value = hargaJual;
        calculateHargaJual();
    }
}
</script>

<?php 
$footer_path = '../includes/footer.php';
if (file_exists($footer_path)) {
    include $footer_path;
} else {
    echo '</div></div></body></html>';
}
?>