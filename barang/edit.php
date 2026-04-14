<?php
/**
 * File: edit.php (Halaman Edit Barang)
 * Fungsi: Menampilkan form edit dan memproses update data barang berdasarkan ID
 * Akses: Hanya user yang sudah login (requireLogin)
 */

// ========== KONFIGURASI ERROR REPORTING ==========
// Aktifkan error reporting untuk debugging (semua error ditampilkan)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ========== INCLUDE FILE YANG DIBUTUHKAN ==========
require_once '../includes/session.php';      // Fungsi manajemen session
require_once '../includes/functions.php';    // Fungsi helper (cleanInput, calculateHargaJual, dll)
requireLogin();                               // Pastikan user sudah login

// ========== KONEKSI DATABASE ==========
// Menggunakan MySQLi dengan konstanta dari config/database.php
require_once __DIR__ . '/../config/database.php';
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// ========== AMBIL ID DARI URL ==========
// Mendapatkan parameter 'id' dari URL query string, default 0 jika tidak ada
// Contoh URL: edit.php?id=5
$id = $_GET['id'] ?? 0;

// ========== AMBIL DATA BARANG ==========
// Memanggil fungsi getBarangById() dari functions.php untuk mengambil data barang
$barang = getBarangById($id);

// ========== VALIDASI DATA BARANG ==========
// Jika barang tidak ditemukan (ID tidak valid atau sudah dihapus)
if (!$barang) {
    // Redirect ke halaman index (daftar barang)
    header('Location: index.php');
    exit(); // Hentikan eksekusi script
}

// ========== PROSES UPDATE DATA ==========
// Memeriksa apakah form telah disubmit (method POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // ========== SANITASI INPUT ==========
    // cleanInput() membersihkan input dari karakter berbahaya (XSS/SQL Injection)
    $kode_barang = cleanInput($_POST['kode_barang']);     // Kode barang (ROTIxxx)
    $nama_barang = cleanInput($_POST['nama_barang']);     // Nama barang (wajib)
    $varian_barang = cleanInput($_POST['varian_barang']); // Varian (optional)
    $stok_barang = (int)$_POST['stok_barang'];            // Stok barang (integer, min 0)
    $keterangan = cleanInput($_POST['keterangan']);       // Keterangan (optional)
    $harga_satuan = (int)$_POST['harga_satuan'];          // Harga beli (integer)
    $harga_jual = (int)$_POST['harga_jual'];              // Harga jual (integer)
    
    // ========== VALIDASI HARGA JUAL ==========
    // Jika harga_jual kosong (0) tapi harga_satuan diisi, hitung otomatis dengan markup 50%
    if (empty($harga_jual) && !empty($harga_satuan)) {
        // calculateHargaJual() dari functions.php: harga_satuan + (harga_satuan * 50/100)
        $harga_jual = calculateHargaJual($harga_satuan, 50);
    }
    
    // ========== PREPARED STATEMENT (UPDATE) ==========
    // Query UPDATE dengan placeholder (?) untuk keamanan (mencegah SQL Injection)
    $query = "UPDATE barang SET 
              kode_barang = ?,      -- Parameter 1
              nama_barang = ?,      -- Parameter 2
              varian_barang = ?,    -- Parameter 3
              stok_barang = ?,      -- Parameter 4
              keterangan = ?,       -- Parameter 5
              harga_satuan = ?,     -- Parameter 6
              harga_jual = ?        -- Parameter 7
              WHERE id_barang = ?"; // Parameter 8 (WHERE condition)
    
    // Mempersiapkan statement
    $stmt = mysqli_prepare($conn, $query);
    
    // Mengikat parameter ke statement
    // 'sssisiii' = tipe data: string, string, string, integer, string, integer, integer, integer
    // s = string, i = integer
    mysqli_stmt_bind_param($stmt, 'sssisiii', 
        $kode_barang,    // string - kode barang
        $nama_barang,    // string - nama barang
        $varian_barang,  // string - varian barang
        $stok_barang,    // integer - stok barang
        $keterangan,     // string - keterangan
        $harga_satuan,   // integer - harga beli
        $harga_jual,     // integer - harga jual
        $id              // integer - ID barang (WHERE condition)
    );
    
    // Eksekusi statement
    if (mysqli_stmt_execute($stmt)) {
        // Jika berhasil, redirect ke halaman index dengan parameter message
        // Parameter digunakan untuk menampilkan notifikasi sukses
        header('Location: index.php?message=update_success');
        exit(); // Hentikan eksekusi setelah redirect
    } else {
        // Jika gagal, simpan pesan error untuk ditampilkan
        $error = "Gagal mengupdate barang! Error: " . mysqli_error($conn);
    }
    
    // Tutup statement untuk membebaskan resource
    mysqli_stmt_close($stmt);
}

// ========== KONFIGURASI HALAMAN ==========
$page_title = 'Edit Barang'; // Judul halaman untuk header

// ========== INCLUDE HEADER DAN NAVBAR ==========
include '../includes/header.php'; // Meta tags, CSS, opening HTML tags
include '../includes/navbar.php';  // Menu navigasi
?>

<!-- ========== SWEET ALERT CDN ========== -->
<!-- SweetAlert2 untuk notifikasi dan konfirmasi yang menarik -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- ========== KONTEN UTAMA ========== -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit Barang</h1>
    <!-- Tombol kembali ke halaman index (daftar barang) -->
    <a href="index.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<!-- ========== FORM EDIT BARANG ========== -->
<div class="card">
    <div class="card-body">
        
        <!-- ========== MENAMPILKAN ERROR (JIKA ADA) ========== -->
        <?php if (isset($error)): ?>
            <script>
            // Sweet Alert untuk menampilkan pesan error
            Swal.fire({
                icon: 'error',              // Icon error (merah)
                title: 'Gagal!',            // Judul alert
                text: '<?php echo addslashes($error); ?>', // Pesan error (addslashes untuk quote)
                showConfirmButton: true,    // Tampilkan tombol OK
                timer: 5000                 // Auto close setelah 5 detik
            });
            </script>
        <?php endif; ?>
        
        <!-- Form dengan method POST (action kosong = submit ke halaman sendiri) -->
        <form method="POST" action="" id="editForm">
            
            <!-- ========== BARIS 1: Kode Barang & Nama Barang ========== -->
            <div class="row mb-3">
                <!-- Kolom Kode Barang (dapat diedit, tidak readonly seperti di tambah) -->
                <div class="col-md-6">
                    <label for="kode_barang" class="form-label">Kode Barang</label>
                    <input type="text" class="form-control" id="kode_barang" name="kode_barang" 
                           value="<?php echo htmlspecialchars($barang['kode_barang'] ?? ''); ?>" required>
                    <!-- htmlspecialchars mencegah XSS, ?? '' mencegah error jika null -->
                </div>
                
                <!-- Kolom Nama Barang (wajib diisi) -->
                <div class="col-md-6">
                    <label for="nama_barang" class="form-label">Nama Barang</label>
                    <input type="text" class="form-control" id="nama_barang" name="nama_barang" 
                           value="<?php echo htmlspecialchars($barang['nama_barang'] ?? ''); ?>" required>
                </div>
            </div>
            
            <!-- ========== BARIS 2: Varian & Stok ========== -->
            <div class="row mb-3">
                <!-- Kolom Varian Barang (optional) -->
                <div class="col-md-6">
                    <label for="varian_barang" class="form-label">Varian</label>
                    <input type="text" class="form-control" id="varian_barang" name="varian_barang" 
                           value="<?php echo htmlspecialchars($barang['varian_barang'] ?? ''); ?>">
                </div>
                
                <!-- Kolom Stok Barang (wajib, min 0) -->
                <div class="col-md-6">
                    <label for="stok_barang" class="form-label">Stok</label>
                    <input type="number" class="form-control" id="stok_barang" name="stok_barang" 
                           value="<?php echo htmlspecialchars($barang['stok_barang'] ?? 0); ?>" 
                           required min="0">
                </div>
            </div>
            
            <!-- ========== BARIS 3: Keterangan & Harga Satuan ========== -->
            <div class="row mb-3">
                <!-- Kolom Keterangan (textarea, optional) -->
                <div class="col-md-6">
                    <label for="keterangan" class="form-label">Keterangan</label>
                    <textarea class="form-control" id="keterangan" name="keterangan" rows="2">
                        <?php echo htmlspecialchars($barang['keterangan'] ?? ''); ?>
                    </textarea>
                </div>
                
                <!-- Kolom Harga Satuan (Harga Beli) -->
                <div class="col-md-6">
                    <label for="harga_satuan" class="form-label">Harga Satuan (Harga Beli)</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" id="harga_satuan" name="harga_satuan" 
                               value="<?php echo htmlspecialchars($barang['harga_satuan'] ?? 0); ?>" 
                               required min="0" oninput="calculateHargaJual()">
                        <!-- oninput: event saat nilai berubah, memanggil fungsi hitung margin -->
                    </div>
                </div>
            </div>
            
            <!-- ========== BARIS 4: Harga Jual & Informasi Margin ========== -->
            <div class="row mb-3">
                <!-- Kolom Harga Jual (dapat diedit manual atau otomatis) -->
                <div class="col-md-6">
                    <label for="harga_jual" class="form-label">Harga Jual</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" id="harga_jual" name="harga_jual" 
                               value="<?php echo htmlspecialchars($barang['harga_jual'] ?? 0); ?>" 
                               required min="0" oninput="calculateHargaJual()">
                    </div>
                </div>
                
                <!-- Kolom Informasi Margin dan Tombol Hitung Otomatis -->
                <div class="col-md-6">
                    <div class="mt-4">
                        <!-- Alert info untuk menampilkan margin keuntungan -->
                        <div class="alert alert-info py-2 px-3 mb-0">
                            <small>
                                <i class="bi bi-info-circle"></i> 
                                Margin: <strong id="margin_text">
                                    <?php 
                                    // Hitung margin awal dari data yang ada di database
                                    $harga_beli = $barang['harga_satuan'] ?? 0;
                                    $harga_jual_val = $barang['harga_jual'] ?? 0;
                                    $margin = $harga_jual_val - $harga_beli;
                                    $persen = ($harga_beli > 0) ? round(($margin/$harga_beli)*100, 1) : 0;
                                    echo 'Rp ' . number_format($margin, 0, ',', '.') . " ($persen%)";
                                    ?>
                                </strong>
                            </small>
                        </div>
                        
                        <!-- Tombol untuk menghitung otomatis dengan markup 50% -->
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2 w-100" onclick="applyDefaultMarkup()">
                            <i class="bi bi-calculator"></i> Hitung dengan Markup 50%
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- ========== TOMBOL AKSI ========== -->
            <div class="d-flex justify-content-between mt-4">
                <!-- Tombol Submit Update -->
                <button type="submit" class="btn btn-primary" id="btnUpdate">
                    <i class="bi bi-save"></i> Update Barang
                </button>
                
                <!-- Tombol Batal dengan konfirmasi -->
                <button type="button" class="btn btn-outline-danger" onclick="confirmCancel()">
                    <i class="bi bi-x-circle"></i> Batal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========== JAVASCRIPT UNTUK FORM EDIT ========== -->
<script>
/**
 * INISIALISASI HALAMAN
 * Event listener saat DOM (HTML) selesai dimuat
 */
document.addEventListener('DOMContentLoaded', function() {
    // Hitung dan tampilkan margin keuntungan berdasarkan data awal
    calculateHargaJual();
    
    // Tambahkan event listener untuk form submit (override default behavior)
    document.getElementById('editForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Mencegah submit langsung
        confirmUpdate();     // Tampilkan konfirmasi terlebih dahulu
    });
});

/**
 * MENGHITUNG DAN MENAMPILKAN MARGIN KEUNTUNGAN
 * Margin = Harga Jual - Harga Beli
 * Persentase = (Margin / Harga Beli) × 100%
 * 
 * Warna margin berdasarkan persentase:
 * - < 20%: Merah (keuntungan kecil)
 * - 20-40%: Kuning (keuntungan sedang)
 * - > 40%: Hijau (keuntungan besar)
 */
function calculateHargaJual() {
    // Ambil nilai dari input (parseFloat untuk angka desimal, default 0)
    const hargaSatuan = parseFloat(document.getElementById('harga_satuan').value) || 0;
    const hargaJualInput = parseFloat(document.getElementById('harga_jual').value) || 0;
    
    // Jika kedua nilai valid (lebih dari 0)
    if (hargaSatuan > 0 && hargaJualInput > 0) {
        // Hitung margin absolut (Rp)
        const margin = hargaJualInput - hargaSatuan;
        
        // Hitung persentase margin (dibulatkan 1 desimal)
        const persen = ((margin / hargaSatuan) * 100).toFixed(1);
        
        // Tampilkan margin dengan format Rupiah
        document.getElementById('margin_text').innerHTML = 
            `Rp ${margin.toLocaleString('id-ID')} (${persen}%)`;
        
        // Beri warna berdasarkan persentase margin
        const marginElement = document.getElementById('margin_text');
        if (persen < 20) {
            marginElement.className = 'text-danger';   // Merah (kurang untung)
        } else if (persen < 40) {
            marginElement.className = 'text-warning';  // Kuning (cukup untung)
        } else {
            marginElement.className = 'text-success';  // Hijau (sangat untung)
        }
    } else {
        // Jika data tidak lengkap, tampilkan 0
        document.getElementById('margin_text').innerHTML = 'Rp 0 (0%)';
        document.getElementById('margin_text').className = '';
    }
}

/**
 * MENERAPKAN MARKUP DEFAULT 50%
 * Menghitung harga jual = Harga Satuan × 1.5
 * Digunakan saat tombol "Hitung dengan Markup 50%" diklik
 */
function applyDefaultMarkup() {
    const hargaSatuan = document.getElementById('harga_satuan').value;
    
    // Validasi: pastikan harga satuan diisi dan lebih dari 0
    if (hargaSatuan && parseFloat(hargaSatuan) > 0) {
        // Hitung harga jual: hargaSatuan × 1.5 (markup 50%)
        const hargaJual = Math.round(parseFloat(hargaSatuan) * 1.5);
        
        // Set nilai ke input harga_jual
        document.getElementById('harga_jual').value = hargaJual;
        
        // Hitung ulang margin
        calculateHargaJual();
        
        // Tampilkan notifikasi toast (pesan sementara)
        Swal.fire({
            icon: 'info',
            title: 'Harga Jual Dihitung',
            text: `Harga jual dengan markup 50%: Rp ${hargaJual.toLocaleString('id-ID')}`,
            timer: 2000,                // Tampil selama 2 detik
            showConfirmButton: false,   // Tanpa tombol OK
            toast: true,                // Mode toast (popup kecil)
            position: 'top-end'         // Posisi di kanan atas
        });
    } else {
        // Jika harga satuan belum diisi
        Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: 'Masukkan harga satuan terlebih dahulu!',
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    }
}

/**
 * KONFIRMASI UPDATE DENGAN SWEET ALERT
 * Menampilkan dialog konfirmasi sebelum mengirim data ke server
 */
function confirmUpdate() {
    // Validasi form bawaan browser (required, min, max, dll)
    const form = document.getElementById('editForm');
    if (!form.checkValidity()) {
        form.reportValidity(); // Tampilkan pesan validasi dari browser
        return;
    }
    
    // Tampilkan dialog konfirmasi
    Swal.fire({
        title: 'Konfirmasi Update',
        text: "Apakah Anda yakin ingin menyimpan perubahan data barang?",
        icon: 'question',           // Icon tanda tanya
        showCancelButton: true,     // Tampilkan tombol batal
        confirmButtonColor: '#3085d6', // Warna tombol konfirmasi (biru)
        cancelButtonColor: '#d33',     // Warna tombol batal (merah)
        confirmButtonText: 'Ya, Update!',
        cancelButtonText: 'Batal',
        reverseButtons: true        // Urutan tombol: Batal di kiri, Update di kanan
    }).then((result) => {
        if (result.isConfirmed) {
            // Tampilkan loading indicator
            Swal.fire({
                title: 'Memproses...',
                text: 'Harap tunggu sebentar',
                allowOutsideClick: false, // Tidak bisa klik di luar
                allowEscapeKey: false,    // Tidak bisa tekan ESC
                showConfirmButton: false, // Sembunyikan tombol
                didOpen: () => {
                    Swal.showLoading();   // Tampilkan animasi loading
                    form.submit();        // Submit form ke server
                }
            });
        }
    });
}

/**
 * KONFIRMASI PEMBATALAN
 * Menampilkan dialog peringatan jika user ingin membatalkan edit
 */
function confirmCancel() {
    Swal.fire({
        title: 'Batalkan Edit?',
        text: "Perubahan yang belum disimpan akan hilang!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Kembali',
        cancelButtonText: 'Tetap di Sini'
    }).then((result) => {
        if (result.isConfirmed) {
            // Redirect ke halaman index (daftar barang)
            window.location.href = 'index.php';
        }
    });
}

/**
 ========== PERINGATAN KELUAR HALAMAN (BEFOREUNLOAD) ==========
 * Memunculkan peringatan jika user mencoba meninggalkan halaman
 * tanpa menyimpan perubahan yang sudah dibuat
 */
let formChanged = false; // Flag apakah ada perubahan pada form

// Tambahkan event listener untuk setiap input dan textarea
document.querySelectorAll('#editForm input, #editForm textarea').forEach(element => {
    // Event 'change' terjadi saat nilai berubah dan focus pindah
    element.addEventListener('change', () => {
        formChanged = true;
    });
    
    // Event 'input' terjadi saat user mengetik/mengubah nilai
    element.addEventListener('input', () => {
        formChanged = true;
    });
});

// Event 'beforeunload' terjadi saat user akan meninggalkan halaman
window.addEventListener('beforeunload', function(e) {
    if (formChanged) {
        // Standar: browser akan menampilkan peringatan default
        e.preventDefault();
        e.returnValue = ''; // Required for Chrome
    }
});
</script>

<!-- ========== STYLE TAMBAHAN ========== -->
<style>
/* Style tambahan untuk SweetAlert */
.swal2-popup {
    font-size: 0.9rem;
}

.swal2-toast {
    font-size: 0.85rem;
}

/* Style untuk margin text (keuntungan) */
#margin_text {
    font-weight: bold;
    transition: color 0.3s ease; /* Animasi perubahan warna */
}

/* Warna untuk margin berdasarkan tingkat keuntungan */
.text-danger {
    color: #dc3545 !important;  /* Merah: margin < 20% */
}

.text-warning {
    color: #ffc107 !important;  /* Kuning: margin 20-40% */
}

.text-success {
    color: #28a745 !important;  /* Hijau: margin > 40% */
}

/* Hover effect untuk tombol */
.btn-outline-primary:hover {
    transform: translateY(-2px); /* Efek naik sedikit */
    transition: transform 0.2s;
}

/* Animasi loading untuk tombol saat disabled */
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.btn-primary:disabled {
    animation: pulse 1.5s infinite;
}
</style>

<?php 
/**
 * ========== INCLUDE FOOTER ==========
 * Memuat file footer.php jika ada, jika tidak maka menutup HTML secara manual
 */
$footer_path = '../includes/footer.php';
if (file_exists($footer_path)) {
    include $footer_path;
} else {
    // Fallback: tutup tag HTML yang sudah dibuka di header.php
    echo '</div></div></body></html>';
}

/**
 * ========== CATATAN PENTING UNTUK PENGEMBANGAN ==========
 * 
 * 1. KEAMANAN:
 *    - Menggunakan prepared statement (mysqli_prepare) untuk mencegah SQL Injection
 *    - Menggunakan htmlspecialchars() untuk mencegah XSS saat menampilkan data
 *    - Menggunakan cleanInput() dari functions.php untuk sanitasi input
 * 
 * 2. VALIDASI:
 *    - Validasi sisi client (JavaScript) untuk pengalaman user yang lebih baik
 *    - Validasi sisi server (PHP) untuk keamanan
 *    - Validasi stok barang (min 0) dan harga (min 0)
 * 
 * 3. USER EXPERIENCE:
 *    - SweetAlert2 untuk notifikasi yang menarik
 *    - Konfirmasi sebelum update dan cancel
 *    - Peringatan jika meninggalkan halaman dengan perubahan belum tersimpan
 *    - Perhitungan margin otomatis dengan kode warna (merah/kuning/hijau)
 * 
 * 4. FITUR TAMBAHAN:
 *    - Tombol hitung otomatis dengan markup 50%
 *    - Tampilan margin keuntungan (Rp dan persentase)
 *    - Toast notification untuk aksi cepat
 * 
 * 5. YANG PERLU DIPERHATIKAN:
 *    - Pastikan fungsi getBarangById() sudah didefinisikan di functions.php
 *    - Pastikan fungsi calculateHargaJual() sudah didefinisikan di functions.php
 *    - Pastikan file header.php, navbar.php, footer.php ada di folder includes/
 *    - Koneksi database ($conn) belum ditutup, pertimbangkan untuk menutupnya di footer
 */
?>