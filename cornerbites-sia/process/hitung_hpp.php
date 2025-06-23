<?php
// process/hitung_hpp.php
// File ini adalah placeholder. Perhitungan HPP akan lebih baik diintegrasikan
// langsung ke dalam logika transaksi penjualan (saat stok keluar) atau laporan.
// Untuk proyek sederhana ini, HPP akan dihitung secara langsung saat laporan.
// File ini mungkin tidak akan digunakan sebagai file proses terpisah yang diakses langsung.

// Namun, jika Anda ingin fungsi HPP terpisah:

/**
 * Fungsi untuk menghitung HPP (Harga Pokok Penjualan) untuk suatu transaksi atau periode.
 * Ini adalah contoh sangat sederhana. HPP kompleks memerlukan metode FIFO/LIFO/Average.
 * Untuk UMKM sederhana, bisa berdasarkan harga beli terakhir produk yang terjual.
 *
 * @param PDO $conn Objek koneksi database.
 * @param int $product_id ID produk yang terjual.
 * @param int $quantity Jumlah unit yang terjual.
 * @return float HPP untuk transaksi tersebut.
 */
function calculateHPP($conn, $product_id, $quantity) {
    try {
        $stmt = $conn->prepare("SELECT purchase_price FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if ($product) {
            return $product['purchase_price'] * $quantity;
        }
    } catch (PDOException $e) {
        error_log("Error hitung HPP: " . $e->getMessage());
    }
    return 0; // Kembalikan 0 jika gagal atau produk tidak ditemukan
}

// Contoh bagaimana ini bisa dipanggil (misalnya dari simpan_transaksi.php saat pemasukan/penjualan)
/*
// Di simpan_transaksi.php setelah mendapatkan product_id dan quantity
require_once __DIR__ . '/hitung_hpp.php';
$hpp_transaksi_ini = calculateHPP($conn, $product_id, $quantity);
// Anda bisa menyimpan HPP ini di tabel transaction_details atau hanya menggunakannya untuk laporan.
*/

// Atau, untuk laporan laba rugi, HPP dihitung agregat di halaman laporan itu sendiri.
// Lihat contoh di pages/laporan.php untuk bagaimana HPP dihitung langsung di sana.

// File ini tidak menghasilkan output HTML atau redirect karena ini adalah fungsi pembantu.
?>
