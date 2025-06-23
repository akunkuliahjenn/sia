<?php
// admin/dashboard.php
// Halaman dashboard untuk admin, menampilkan ringkasan sistem.

require_once __DIR__ . '/../includes/auth_check.php'; // Pastikan user sudah login dan role admin
require_once __DIR__ . '/../config/db.php'; // Sertakan file koneksi database

// Pastikan hanya admin yang bisa mengakses halaman ini
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: /cornerbites-sia/pages/dashboard.php");
    exit();
}

$totalUsers = 0;
$totalTransactions = 0;
$totalProducts = 0;
$totalRevenueGlobal = 0;
$totalExpenseGlobal = 0;

try {
    $conn = $db;

    // Hitung total pengguna
    $stmtUsers = $conn->query("SELECT COUNT(*) AS total_users FROM users");
    $totalUsers = $stmtUsers->fetchColumn();

    // Hitung total transaksi (semua jenis)
    $stmtTransactions = $conn->query("SELECT COUNT(*) AS total_transactions FROM transactions");
    $totalTransactions = $stmtTransactions->fetchColumn();

    // Hitung total produk terdaftar
    $stmtProducts = $conn->query("SELECT COUNT(*) AS total_products FROM products");
    $totalProducts = $stmtProducts->fetchColumn();

    // Hitung total pendapatan global
    $stmtRevenue = $conn->query("SELECT SUM(amount) AS total_revenue FROM transactions WHERE type = 'pemasukan'");
    $totalRevenueGlobal = $stmtRevenue->fetchColumn();

    // Hitung total pengeluaran global
    $stmtExpense = $conn->query("SELECT SUM(amount) AS total_expense FROM transactions WHERE type = 'pengeluaran'");
    $totalExpenseGlobal = $stmtExpense->fetchColumn();

} catch (PDOException $e) {
    error_log("Error di Admin Dashboard: " . $e->getMessage());
    // Anda bisa menampilkan pesan error yang ramah pengguna di sini
}
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>
<div class="flex h-screen bg-gray-100 font-sans">
    <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="flex items-center justify-between h-16 bg-white border-b border-gray-200 px-6 shadow-sm">
            <h1 class="text-xl font-semibold text-gray-800">Admin Dashboard</h1>
            <div>
                <span class="text-gray-600">Selamat datang, Admin <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>!</span>
            </div>
        </header>
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-200 p-6">
            <div class="container mx-auto">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Ringkasan Sistem</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <!-- Card Total Pengguna -->
                    <div class="bg-white rounded-lg shadow-md p-6 transform hover:scale-105 transition duration-200 ease-in-out">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Pengguna</h3>
                        <p class="text-4xl font-extrabold text-indigo-600"><?php echo $totalUsers; ?></p>
                        <p class="text-sm text-gray-500 mt-2">Akun Terdaftar</p>
                    </div>

                    <!-- Card Total Transaksi -->
                    <div class="bg-white rounded-lg shadow-md p-6 transform hover:scale-105 transition duration-200 ease-in-out">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Transaksi</h3>
                        <p class="text-4xl font-extrabold text-blue-600"><?php echo $totalTransactions; ?></p>
                        <p class="text-sm text-gray-500 mt-2">Total Pencatatan</p>
                    </div>

                    <!-- Card Total Produk -->
                    <div class="bg-white rounded-lg shadow-md p-6 transform hover:scale-105 transition duration-200 ease-in-out">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Produk</h3>
                        <p class="text-4xl font-extrabold text-purple-600"><?php echo $totalProducts; ?></p>
                        <p class="text-sm text-gray-500 mt-2">Item Barang/Jasa</p>
                    </div>

                    <!-- Card Total Pendapatan Global -->
                    <div class="bg-white rounded-lg shadow-md p-6 transform hover:scale-105 transition duration-200 ease-in-out">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Pendapatan Global</h3>
                        <p class="text-4xl font-extrabold text-green-600">Rp <?php echo number_format($totalRevenueGlobal, 0, ',', '.'); ?></p>
                        <p class="text-sm text-gray-500 mt-2">Dari Semua UMKM</p>
                    </div>

                    <!-- Card Total Pengeluaran Global -->
                    <div class="bg-white rounded-lg shadow-md p-6 transform hover:scale-105 transition duration-200 ease-in-out">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Pengeluaran Global</h3>
                        <p class="text-4xl font-extrabold text-red-600">Rp <?php echo number_format($totalExpenseGlobal, 0, ',', '.'); ?></p>
                        <p class="text-sm text-gray-500 mt-2">Dari Semua UMKM</p>
                    </div>

                </div>

                <!-- Bagian untuk grafik atau informasi tambahan lainnya -->
                <div class="bg-white rounded-lg shadow-md p-6 mt-8">
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">Aktivitas Terbaru Sistem</h3>
                    <p class="text-gray-600">Grafik atau daftar log aktivitas bisa ditampilkan di sini.</p>
                </div>
            </div>
        </main>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/footer.php'; ?>
