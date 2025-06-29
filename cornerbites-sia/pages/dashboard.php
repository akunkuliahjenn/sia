
<?php
// pages/dashboard.php
// Halaman dashboard utama yang menampilkan ringkasan keuangan dan grafik untuk pengguna biasa.

// Pastikan auth_check.php dipanggil di awal setiap halaman yang membutuhkan login
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php'; // Sertakan file koneksi database

// Inisialisasi variabel dengan nilai default
$totalPenjualan = 0;
$totalPengeluaran = 0;
$estimasiLabaBersih = 0;
$produkTerjual = 0;
$stokRendahCount = 0;

$monthlySales = [];
$monthlyExpenses = [];
$monthsLabel = [];

$popularProducts = [];
$popularProductNames = [];
$popularProductQuantities = [];

// Filter parameters untuk transaksi terbaru
$search = $_GET['search'] ?? '';
$date_filter = $_GET['date_filter'] ?? 'semua';
$type_filter = $_GET['type_filter'] ?? 'semua';

try {
    // Ambil koneksi database
    $conn = $db; // $db sudah didefinisikan di config/db.php

    // Query untuk Total Penjualan
    $stmtPenjualan = $conn->query("SELECT SUM(amount) AS total_penjualan FROM transactions WHERE type = 'pemasukan'");
    $resultPenjualan = $stmtPenjualan->fetch();
    $totalPenjualan = $resultPenjualan['total_penjualan'] ?? 0;

    // Query untuk Total Pengeluaran
    $stmtPengeluaran = $conn->query("SELECT SUM(amount) AS total_pengeluaran FROM transactions WHERE type = 'pengeluaran'");
    $resultPengeluaran = $stmtPengeluaran->fetch();
    $totalPengeluaran = $resultPengeluaran['total_pengeluaran'] ?? 0;

    // Hitung Estimasi Laba Bersih
    $estimasiLabaBersih = $totalPenjualan - $totalPengeluaran;

    // Query untuk Jumlah Produk Terjual (total quantity dari transaksi pemasukan)
    $stmtProdukTerjual = $conn->query("SELECT SUM(quantity) AS total_quantity_sold FROM transactions WHERE type = 'pemasukan' AND product_id IS NOT NULL");
    $resultProdukTerjual = $stmtProdukTerjual->fetch();
    $produkTerjual = $resultProdukTerjual['total_quantity_sold'] ?? 0;

    // Query untuk jumlah produk dengan stok rendah (menggunakan 'stock' sesuai DB Anda)
    // Batas stok rendah bisa disesuaikan di sini (misal < 10, < 5, dll.)
    $stmtStokRendah = $conn->query("SELECT COUNT(*) AS stok_rendah_count FROM products WHERE stock < 10");
    $resultStokRendah = $stmtStokRendah->fetch();
    $stokRendahCount = $resultStokRendah['stok_rendah_count'] ?? 0;

    // PAGINATION untuk transaksi terbaru
    $limit_options = [5, 10, 20, 50];
    $limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $limit_options) ? (int)$_GET['limit'] : 5;
    $page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
    $offset = ($page - 1) * $limit;

    // Build WHERE clause for filters
    $whereClause = "WHERE 1=1";
    $params = [];
    
    // Search filter
    if (!empty($search)) {
        $whereClause .= " AND (t.description LIKE :search OR t.amount LIKE :search OR p.name LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    // Type filter
    if ($type_filter !== 'semua') {
        $whereClause .= " AND t.type = :type";
        $params[':type'] = $type_filter;
    }
    
    // Date filter
    if ($date_filter == 'hari_ini') {
        $whereClause .= " AND DATE(t.date) = CURDATE()";
    } elseif ($date_filter == 'bulan_ini') {
        $whereClause .= " AND MONTH(t.date) = MONTH(CURDATE()) AND YEAR(t.date) = YEAR(CURDATE())";
    } elseif ($date_filter == 'tahun_ini') {
        $whereClause .= " AND YEAR(t.date) = YEAR(CURDATE())";
    }

    // Count total transactions with filters
    $countQuery = "SELECT COUNT(*) FROM transactions t LEFT JOIN products p ON t.product_id = p.id " . $whereClause;
    $stmtCount = $conn->prepare($countQuery);
    foreach ($params as $key => $value) {
        $stmtCount->bindValue($key, $value);
    }
    $stmtCount->execute();
    $totalTransaksi = $stmtCount->fetchColumn();
    $totalPages = ceil($totalTransaksi / $limit);

    // Query untuk Transaksi Terbaru dengan filter - perbaikan untuk menampilkan waktu yang benar
    $stmtTransactions = $conn->prepare("SELECT t.*, p.name as product_name, 
        CASE 
            WHEN t.created_at IS NOT NULL AND t.created_at != '0000-00-00 00:00:00' THEN t.created_at
            ELSE t.date 
        END as display_datetime
        FROM transactions t LEFT JOIN products p ON t.product_id = p.id " . $whereClause . " ORDER BY t.date DESC, t.id DESC LIMIT :limit OFFSET :offset");
    foreach ($params as $key => $value) {
        $stmtTransactions->bindValue($key, $value);
    }
    $stmtTransactions->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmtTransactions->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmtTransactions->execute();
    $transaksiTerbaru = $stmtTransactions->fetchAll(PDO::FETCH_ASSOC);

    // --- Data untuk Grafik Tren (Penjualan dan Pengeluaran Bulanan) ---
    // Mengambil data 6 bulan terakhir
    $stmtMonthlySales = $conn->query("
        SELECT
            DATE_FORMAT(date, '%Y-%m') as period,
            DATE_FORMAT(date, '%M %Y') as month_label,
            SUM(amount) as total_amount
        FROM transactions
        WHERE type = 'pemasukan' AND date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY period, month_label
        ORDER BY period ASC
    ");
    $rawMonthlySales = $stmtMonthlySales->fetchAll(PDO::FETCH_ASSOC);

    $stmtMonthlyExpenses = $conn->query("
        SELECT
            DATE_FORMAT(date, '%Y-%m') as period,
            DATE_FORMAT(date, '%M %Y') as month_label,
            SUM(amount) as total_amount
        FROM transactions
        WHERE type = 'pengeluaran' AND date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY period, month_label
        ORDER BY period ASC
    ");
    $rawMonthlyExpenses = $stmtMonthlyExpenses->fetchAll(PDO::FETCH_ASSOC);

    // Populate monthsLabel, monthlySales, monthlyExpenses for the last 6 months
    $salesData = [];
    $expenseData = [];

    // Convert array to associative array with period as key
    foreach ($rawMonthlySales as $row) {
        $salesData[$row['period']] = $row['total_amount'];
    }

    foreach ($rawMonthlyExpenses as $row) {
        $expenseData[$row['period']] = $row['total_amount'];
    }

    // Generate data for last 6 months
    for ($i = 5; $i >= 0; $i--) {
        $date = new DateTime("-$i months");
        $period = $date->format('Y-m');
        $monthLabel = $date->format('M Y');
        $monthsLabel[] = $monthLabel;

        $monthlySales[] = isset($salesData[$period]) ? $salesData[$period] : 0;
        $monthlyExpenses[] = isset($expenseData[$period]) ? $expenseData[$period] : 0;
    }

    // --- Data untuk Grafik Produk Terlaris (Top 5 Products) ---
    $stmtPopularProducts = $conn->query("
        SELECT p.name AS product_name, SUM(t.quantity) AS total_sold_quantity
        FROM transactions t
        JOIN products p ON t.product_id = p.id
        WHERE t.type = 'pemasukan' AND t.product_id IS NOT NULL
        GROUP BY p.name
        ORDER BY total_sold_quantity DESC
        LIMIT 5
    ");
    $popularProducts = $stmtPopularProducts->fetchAll();

    foreach ($popularProducts as $product) {
        $popularProductNames[] = $product['product_name'];
        $popularProductQuantities[] = $product['total_sold_quantity'];
    }

} catch (PDOException $e) {
    // Tangani error database
    error_log("Error di Dashboard Pengguna: " . $e->getMessage());
    $transaksiTerbaru = [];
    $monthsLabel = [];
    $monthlySales = [];
    $monthlyExpenses = [];
    $popularProductNames = [];
    $popularProductQuantities = [];
}

// Convert PHP arrays to JSON for JavaScript
$monthsLabelJson = json_encode($monthsLabel);
$monthlySalesJson = json_encode($monthlySales);
$monthlyExpensesJson = json_encode($monthlyExpenses);
$popularProductNamesJson = json_encode($popularProductNames);
$popularProductQuantitiesJson = json_encode($popularProductQuantities);

?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>
<div class="flex h-screen bg-gradient-to-br from-gray-50 to-gray-100 font-sans">
    <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Bar/Header -->
        <header class="flex items-center justify-between h-16 bg-white border-b border-gray-200 px-6 shadow-sm backdrop-blur-sm bg-white/95">
            <div class="flex items-center space-x-3">
                <div class="w-2 h-8 bg-gradient-to-b from-blue-500 to-blue-600 rounded-full"></div>
                <h1 class="text-xl font-bold text-gray-800">Dashboard Utama</h1>
            </div>
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                    <span class="text-white text-sm font-semibold"><?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?></span>
                </div>
                <span class="text-gray-600 font-medium">Halo, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Pengguna'); ?>!</span>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gradient-to-br from-gray-50 to-gray-100 p-6">
            <div class="mb-6">
                <h2 class="text-3xl font-bold text-gray-800 mb-2">Ringkasan Keuangan Anda</h2>
                <p class="text-gray-600">Pantau kinerja bisnis Anda dalam satu dashboard</p>
            </div>

            <!-- Stats Cards dengan design yang lebih modern -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6 mb-8">
                <!-- Card Total Penjualan -->
                <div class="bg-white rounded-xl shadow-lg p-6 transform hover:scale-105 transition duration-300 ease-in-out border border-gray-100 hover:shadow-xl">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-gradient-to-r from-green-400 to-green-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-green-600 bg-green-100 px-2 py-1 rounded-full">+12%</span>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-2">Total Penjualan</h3>
                    <p class="text-2xl font-bold text-gray-800 mb-1">Rp <?php echo number_format($totalPenjualan, 0, ',', '.'); ?></p>
                    <p class="text-xs text-gray-500">Selama Ini</p>
                </div>

                <!-- Card Total Pengeluaran -->
                <div class="bg-white rounded-xl shadow-lg p-6 transform hover:scale-105 transition duration-300 ease-in-out border border-gray-100 hover:shadow-xl">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-gradient-to-r from-red-400 to-red-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-red-600 bg-red-100 px-2 py-1 rounded-full">-5%</span>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-2">Total Pengeluaran</h3>
                    <p class="text-2xl font-bold text-gray-800 mb-1">Rp <?php echo number_format($totalPengeluaran, 0, ',', '.'); ?></p>
                    <p class="text-xs text-gray-500">Selama Ini</p>
                </div>

                <!-- Card Estimasi Laba Bersih -->
                <div class="bg-white rounded-xl shadow-lg p-6 transform hover:scale-105 transition duration-300 ease-in-out border border-gray-100 hover:shadow-xl">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-400 to-blue-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-blue-600 bg-blue-100 px-2 py-1 rounded-full">+8%</span>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-2">Laba Bersih</h3>
                    <p class="text-2xl font-bold text-gray-800 mb-1">Rp <?php echo number_format($estimasiLabaBersih, 0, ',', '.'); ?></p>
                    <p class="text-xs text-gray-500">Estimasi</p>
                </div>

                <!-- Card Jumlah Produk Terjual -->
                <div class="bg-white rounded-xl shadow-lg p-6 transform hover:scale-105 transition duration-300 ease-in-out border border-gray-100 hover:shadow-xl">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-gradient-to-r from-purple-400 to-purple-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-purple-600 bg-purple-100 px-2 py-1 rounded-full">+15%</span>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-2">Produk Terjual</h3>
                    <p class="text-2xl font-bold text-gray-800 mb-1"><?php echo number_format($produkTerjual, 0, ',', '.'); ?></p>
                    <p class="text-xs text-gray-500">Total Unit</p>
                </div>

                <!-- Card Stok Produk Rendah -->
                <div class="bg-white rounded-xl shadow-lg p-6 transform hover:scale-105 transition duration-300 ease-in-out border border-gray-100 hover:shadow-xl">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-gradient-to-r from-orange-400 to-orange-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-orange-600 bg-orange-100 px-2 py-1 rounded-full">Alert</span>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-2">Stok Rendah</h3>
                    <p class="text-2xl font-bold text-gray-800 mb-1"><?php echo $stokRendahCount; ?></p>
                    <p class="text-xs text-gray-500">Item Perlu Restock</p>
                </div>
            </div>

            <!-- Bagian untuk Grafik (Dua Kolom) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Grafik Tren Penjualan & Pengeluaran Bulanan (Line Chart) -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-800">Tren Penjualan & Pengeluaran</h3>
                        <div class="flex space-x-2">
                            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                            <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                        </div>
                    </div>
                    <div class="relative h-80">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>

                <!-- Grafik Produk Terlaris (Bar Chart) -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-800">Top 5 Produk Terlaris</h3>
                        <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                    </div>
                    <div class="relative h-80">
                        <canvas id="popularProductsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Bagian untuk menampilkan tabel transaksi terbaru dengan filter -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-100">
                <!-- Header dan Filter -->
                <div class="p-6 border-b border-gray-200">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800 mb-1">Transaksi Terbaru</h3>
                            <p class="text-sm text-gray-600">Filter dan cari transaksi Anda</p>
                        </div>
                        <div class="text-right">
                            <span class="text-sm text-gray-600">Total: </span>
                            <span class="text-lg font-bold text-blue-600"><?php echo number_format($totalTransaksi); ?> transaksi</span>
                        </div>
                    </div>

                    <!-- Filter Form -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Search Input -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pencarian</label>
                                <input type="text" id="search_input" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Cari deskripsi atau jumlah..." 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                            </div>

                            <!-- Type Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Transaksi</label>
                                <select id="type_filter" name="type_filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                                    <option value="semua" <?php echo $type_filter == 'semua' ? 'selected' : ''; ?>>Semua</option>
                                    <option value="pemasukan" <?php echo $type_filter == 'pemasukan' ? 'selected' : ''; ?>>Pemasukan</option>
                                    <option value="pengeluaran" <?php echo $type_filter == 'pengeluaran' ? 'selected' : ''; ?>>Pengeluaran</option>
                                </select>
                            </div>

                            <!-- Date Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Filter Tanggal</label>
                                <select id="date_filter" name="date_filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                                    <option value="semua" <?php echo $date_filter == 'semua' ? 'selected' : ''; ?>>Semua</option>
                                    <option value="hari_ini" <?php echo $date_filter == 'hari_ini' ? 'selected' : ''; ?>>Hari Ini</option>
                                    <option value="bulan_ini" <?php echo $date_filter == 'bulan_ini' ? 'selected' : ''; ?>>Bulan Ini</option>
                                    <option value="tahun_ini" <?php echo $date_filter == 'tahun_ini' ? 'selected' : ''; ?>>Tahun Ini</option>
                                </select>
                            </div>

                            <!-- Per Halaman -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Per Halaman</label>
                                <select id="limit_select" name="limit" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                                    <?php foreach ($limit_options as $opt): ?>
                                        <option value="<?php echo $opt; ?>" <?php echo ($limit == $opt) ? 'selected' : ''; ?>>
                                            <?php echo $opt; ?> data
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Action Buttons -->
                            <div class="md:col-span-4 flex gap-3">
                                <button type="submit" class="inline-flex items-center px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200 font-medium">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                    </svg>
                                    Filter
                                </button>
                                        <a href="/cornerbites-sia/pages/dashboard.php" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-200">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        Reset
                                    </a>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabel Transaksi -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal & Waktu</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah (Rp)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($transaksiTerbaru)): ?>
                                <?php foreach ($transaksiTerbaru as $transaksi): ?>
                                    <tr class="hover:bg-gray-50 transition duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div class="flex flex-col">
                                                <span class="font-semibold"><?php echo date('d/m/Y', strtotime($transaksi['display_datetime'])); ?></span>
                                                <span class="text-xs text-gray-500"><?php echo date('H:i:s', strtotime($transaksi['display_datetime'])); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo ($transaksi['type'] == 'pemasukan' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'); ?>">
                                                <?php echo htmlspecialchars(ucfirst($transaksi['type'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="max-w-xs truncate" title="<?php echo htmlspecialchars($transaksi['description']); ?>">
                                                <?php echo htmlspecialchars($transaksi['description']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($transaksi['product_name'] ?? '-'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php 
                                                if ($transaksi['type'] == 'pemasukan' && !empty($transaksi['quantity'])) {
                                                    echo htmlspecialchars($transaksi['quantity']) . ' unit';
                                                } else {
                                                    echo '-';
                                                }
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="font-semibold <?php echo ($transaksi['type'] == 'pemasukan' ? 'text-green-600' : 'text-red-600'); ?>">
                                                Rp <?php echo number_format($transaksi['amount'], 0, ',', '.'); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center space-x-2">
                                                <button class="inline-flex items-center px-3 py-1 border border-indigo-300 text-xs font-medium rounded-md text-indigo-700 bg-indigo-50 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                    Edit
                                                </button>
                                                <button class="inline-flex items-center px-3 py-1 border border-red-300 text-xs font-medium rounded-md text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-200">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <p>Belum ada transaksi tercatat.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-3">
                        <div class="text-sm text-gray-700">
                            Menampilkan <?php echo (($page - 1) * $limit) + 1; ?> sampai <?php echo min($page * $limit, $totalTransaksi); ?> dari <?php echo $totalTransaksi; ?> transaksi
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                   class="px-3 py-2 text-sm bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-200">
                                    ‹ Sebelumnya
                                </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                   class="px-3 py-2 text-sm rounded-lg transition duration-200 <?php echo ($i == $page) ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                   class="px-3 py-2 text-sm bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-200">
                                    Berikutnya ›
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>

<!-- External JavaScript File -->
<script src="/cornerbites-sia/assets/js/dashboard.js"></script>

<script>
    // Data PHP yang di-encode ke JSON
    const monthsLabel = <?php echo $monthsLabelJson; ?>;
    const monthlySales = <?php echo $monthlySalesJson; ?>;
    const monthlyExpenses = <?php echo $monthlyExpensesJson; ?>;
    const popularProductNames = <?php echo $popularProductNamesJson; ?>;
    const popularProductQuantities = <?php echo $popularProductQuantitiesJson; ?>;

    // Initialize charts when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        initializeCharts(monthsLabel, monthlySales, monthlyExpenses, popularProductNames, popularProductQuantities);
    });
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
