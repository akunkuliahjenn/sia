<?php
// pages/transaksi.php
// Halaman untuk pencatatan transaksi pemasukan (penjualan) dan pengeluaran.
// Ditambah fungsionalitas edit transaksi.

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php'; // Sertakan file koneksi database

$type = $_GET['type'] ?? 'pemasukan'; // Default type adalah pemasukan
$page_title = ($type == 'pemasukan') ? 'Pencatatan Penjualan' : 'Pencatatan Pengeluaran';

$products = [];
try {
    $conn = $db;
    // Mengambil daftar produk untuk dropdown jika transaksinya pemasukan
    // Menggunakan 'sale_price' dan 'stock' sesuai struktur DB Anda
    if ($type == 'pemasukan') {
        $stmt = $conn->query("SELECT id, name, sale_price, stock FROM products ORDER BY name ASC");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC); // Pastikan fetch dengan associative array untuk JS
    }

     // Filter parameters
    $search = $_GET['search'] ?? '';
    $date_filter = $_GET['date_filter'] ?? 'semua';

    // Pagination 
    $limit_options = [10, 25, 50, 100];
    $limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $limit_options) ? (int)$_GET['limit'] : 10;
    $page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
    $offset = ($page - 1) * $limit;

    // Build WHERE clause for filters
    $whereClause = "WHERE t.type = :type";
    $params = [':type' => $type];

    // Search filter
    if (!empty($search)) {
        $whereClause .= " AND (t.description LIKE :search OR t.amount LIKE :search)";
        $params[':search'] = '%' . $search . '%';
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
    $countQuery = "SELECT COUNT(*) FROM transactions t " . $whereClause;
    $stmtCount = $conn->prepare($countQuery);
    foreach ($params as $key => $value) {
        $stmtCount->bindValue($key, $value);
    }
    $stmtCount->execute();
    $totalTransactions = $stmtCount->fetchColumn();
    $totalPages = ceil($totalTransactions / $limit);

    // Get transactions with filters and pagination
    $transactionQuery = "SELECT t.*, p.name as product_name FROM transactions t LEFT JOIN products p ON t.product_id = p.id " . $whereClause . " ORDER BY t.created_at DESC, t.date DESC LIMIT :limit OFFSET :offset";
    $stmtTransactions = $conn->prepare($transactionQuery);
    foreach ($params as $key => $value) {
        $stmtTransactions->bindValue($key, $value);
    }
    $stmtTransactions->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmtTransactions->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmtTransactions->execute();
    $transactions = $stmtTransactions->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error di halaman Transaksi: " . $e->getMessage());
    $transactions = []; // Pastikan variabel tetap diinisialisasi
}

// Pesan sukses atau error setelah proses simpan/update
$message = '';
$message_type = ''; // 'success' or 'error'
if (isset($_SESSION['transaction_message'])) {
    $message = $_SESSION['transaction_message']['text'];
    $message_type = $_SESSION['transaction_message']['type'];
    unset($_SESSION['transaction_message']);
}
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>
<div class="flex h-screen bg-gradient-to-br from-gray-50 to-gray-100 font-sans">
    <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Bar/Header -->
        <header class="flex items-center justify-between h-16 bg-white border-b border-gray-200 px-6 shadow-sm backdrop-blur-sm bg-white/95">
            <div class="flex items-center space-x-3">
                <div class="w-2 h-8 bg-gradient-to-b <?php echo ($type == 'pemasukan') ? 'from-green-500 to-green-600' : 'from-red-500 to-red-600'; ?> rounded-full"></div>
                <h1 class="text-xl font-bold text-gray-800"><?php echo $page_title; ?></h1>
            </div>
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                    <span class="text-white text-sm font-semibold"><?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?></span>
                </div>
                <span class="text-gray-600 font-medium">Halo, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Pengguna'); ?>!</span>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gradient-to-br from-gray-50 to-gray-100 p-6">
            <div class="max-w-7xl mx-auto">
                <!-- Header Section -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2"><?php echo $page_title; ?></h1>
                    <p class="text-gray-600">
                        <?php echo ($type == 'pemasukan') ? 'Catat setiap penjualan produk untuk melacak pendapatan bisnis Anda.' : 'Catat setiap pengeluaran operasional atau pembelian bahan baku untuk kebutuhan bisnis Anda.'; ?>
                    </p>
                </div>

                <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-lg border-l-4 <?php echo ($message_type == 'success' ? 'bg-green-50 border-green-400 text-green-700' : 'bg-red-50 border-red-400 text-red-700'); ?>" role="alert">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <?php if ($message_type == 'success'): ?>
                                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                <?php else: ?>
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium"><?php echo htmlspecialchars($message); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Form Transaksi -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 mb-8">
                    <div class="flex items-center mb-6">
                        <div class="p-2 bg-red-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">Input Transaksi <?php echo ($type == 'pemasukan' ? 'Penjualan' : 'Pengeluaran'); ?></h3>
                            <p class="text-sm text-gray-600 mt-1">
                                <?php echo ($type == 'pemasukan') ? 'Pastikan data penjualan dicatat dengan akurat untuk laporan yang tepat.' : 'Catat setiap pengeluaran untuk memantau arus kas bisnis Anda.'; ?>
                            </p>
                        </div>
                    </div>

                    <form action="/cornerbites-sia/process/simpan_transaksi.php" method="POST">
                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                        <input type="hidden" name="transaction_id" id="transaction_id_to_edit" value=""> <!-- Hidden field for edit -->

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="date" class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Transaksi</label>
                                <input type="date" id="date" name="date" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <?php if ($type == 'pemasukan'): ?>
                                <div>
                                    <label for="product_id" class="block text-sm font-semibold text-gray-700 mb-2">Pilih Produk</label>
                                    <select id="product_id" name="product_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" onchange="updateAmountField()" required>
                                        <option value="">-- Pilih Produk --</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?php echo htmlspecialchars($product['id']); ?>" data-price="<?php echo htmlspecialchars($product['sale_price']); ?>" data-stock="<?php echo htmlspecialchars($product['stock']); ?>">
                                                <?php echo htmlspecialchars($product['name']) . ' (Stok: ' . htmlspecialchars($product['stock']) . ' | Rp ' . number_format($product['sale_price'], 0, ',', '.') . ')'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="quantity" class="block text-sm font-semibold text-gray-700 mb-2">Jumlah Unit</label>
                                    <input type="number" id="quantity" name="quantity" min="1" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" placeholder="Masukkan jumlah unit" oninput="updateAmountField()" required disabled>
                                    <p id="quantity-info" class="text-sm text-gray-500 mt-2 flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 000 16zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                        </svg>
                                        Pilih produk terlebih dahulu untuk mengaktifkan kolom ini
                                    </p>
                                </div>
                            <?php endif; ?>
                            <div>
                                <label for="amount" class="block text-sm font-semibold text-gray-700 mb-2">Jumlah (Rupiah)</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <span class="text-gray-500 text-sm font-medium">Rp</span>
                                    </div>
                                    <input type="text" id="amount_display" class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 <?php echo ($type == 'pemasukan') ? 'bg-gray-50' : ''; ?>" 
                                           placeholder="<?php echo ($type == 'pemasukan') ? 'Otomatis terisi berdasarkan produk' : 'Masukkan nominal pengeluaran'; ?>" 
                                           <?php echo ($type == 'pemasukan') ? 'readonly' : ''; ?> 
                                           oninput="formatRupiah(this)">
                                    <input type="hidden" id="amount" name="amount" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">Deskripsi Transaksi</label>
                            <textarea id="description" name="description" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" placeholder="<?php echo ($type == 'pemasukan') ? 'Contoh: Penjualan Kopi Latte kepada pelanggan' : 'Contoh: Pembelian bahan baku gula, Bayar sewa toko'; ?>" required></textarea>
                        </div>

                        <div class="flex items-center gap-4">
                            <button type="submit" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200" id="submit_button">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Simpan Transaksi
                            </button>
                            <button type="button" class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-200 hidden" id="cancel_edit_button" onclick="resetForm()">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Batal Edit
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Filter Transaksi -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 mb-6">
                    <div class="flex items-center mb-4">
                        <div class="p-2 bg-purple-100 rounded-lg mr-3">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Filter & Pencarian Transaksi</h3>
                    </div>

                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">

                        <!-- Pencarian -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pencarian</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" 
                                       placeholder="Cari deskripsi atau jumlah..." 
                                       class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                            </div>
                        </div>

                        <!-- Filter Tanggal -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Filter Tanggal</label>
                            <select name="date_filter" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                                <option value="semua" <?php echo ($_GET['date_filter'] ?? '') == 'semua' ? 'selected' : ''; ?>>Semua Periode</option>
                                <option value="hari_ini" <?php echo ($_GET['date_filter'] ?? '') == 'hari_ini' ? 'selected' : ''; ?>>Hari Ini</option>
                                <option value="bulan_ini" <?php echo ($_GET['date_filter'] ?? '') == 'bulan_ini' ? 'selected' : ''; ?>>Bulan Ini</option>
                                <option value="tahun_ini" <?php echo ($_GET['date_filter'] ?? '') == 'tahun_ini' ? 'selected' : ''; ?>>Tahun Ini</option>
                            </select>
                        </div>

                        <!-- Per Halaman -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Data per Halaman</label>
                            <select name="limit" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                                <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10 Data</option>
                                <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25 Data</option>
                                <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50 Data</option>
                                <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100 Data</option>
                            </select>
                        </div>

                        <!-- Tombol Filter -->
                        <div class="flex gap-3">
                            <button type="submit" class="inline-flex items-center px-4 py-3 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                                Filter
                            </button>
                            <button type="button" onclick="resetFilter()" class="inline-flex items-center px-4 py-3 border border-gray-300 text-sm font-medium rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Reset
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Daftar Transaksi -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center">
                                <div class="p-2 bg-green-100 rounded-lg mr-3">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Daftar Transaksi <?php echo ($type == 'pemasukan' ? 'Penjualan' : 'Pengeluaran'); ?></h3>
                                    <p class="text-sm text-gray-600">Kelola dan pantau semua transaksi <?php echo ($type == 'pemasukan' ? 'penjualan' : 'pengeluaran'); ?> Anda</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div class="text-right">
                                    <span class="text-sm text-gray-500">Total:</span>
                                    <span class="text-lg font-bold <?php echo $type == 'pemasukan' ? 'text-green-600' : 'text-red-600'; ?> ml-1"><?php echo number_format($totalTransactions); ?> transaksi</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal & Waktu</th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                        <?php if ($type == 'pemasukan'): ?>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah Unit</th>
                                        <?php endif; ?>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah (Rp)</th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (!empty($transactions)): ?>
                                        <?php foreach ($transactions as $transaction): ?>
                                            <tr class="hover:bg-gray-50 transition duration-150">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex flex-col">
                                                        <div class="text-sm font-medium text-gray-900"><?php echo date('d/m/Y', strtotime($transaction['date'])); ?></div>
                                                        <div class="text-xs text-gray-500">
                                                            <?php echo $transaction['created_at'] ? date('H:i:s', strtotime($transaction['created_at'])) : 'N/A'; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="text-sm text-gray-900 max-w-xs truncate" title="<?php echo htmlspecialchars($transaction['description']); ?>">
                                                        <?php echo htmlspecialchars($transaction['description']); ?>
                                                    </div>
                                                </td>
                                                <?php if ($type == 'pemasukan'): ?>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($transaction['product_name'] ?? '-'); ?></div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($transaction['quantity'] ?? '-'); ?></div>
                                                    </td>
                                                <?php endif; ?>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-semibold <?php echo $type == 'pemasukan' ? 'text-green-600' : 'text-red-600'; ?>">
                                                        Rp <?php echo number_format($transaction['amount'], 0, ',', '.'); ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <div class="flex items-center space-x-2">
                                                        <button onclick="editTransaction(<?php echo htmlspecialchars(json_encode($transaction)); ?>, <?php echo htmlspecialchars(json_encode($products)); ?>)" 
                                                                class="inline-flex items-center px-3 py-1 border border-indigo-300 text-xs font-medium rounded-md text-indigo-700 bg-indigo-50 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                            </svg>
                                                            Edit
                                                        </button>
                                                        <a href="/cornerbites-sia/process/simpan_transaksi.php?action=delete&id=<?php echo htmlspecialchars($transaction['id']); ?>&type=<?php echo htmlspecialchars($type); ?>" 
                                                           class="inline-flex items-center px-3 py-1 border border-red-300 text-xs font-medium rounded-md text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-200" 
                                                           onclick="return confirm('Apakah Anda yakin ingin menghapus transaksi ini?');">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                            </svg>
                                                            Hapus
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="<?php echo ($type == 'pemasukan' ? '6' : '4'); ?>" class="px-6 py-12 text-center">
                                                <div class="flex flex-col items-center">
                                                    <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                    <p class="text-gray-500 text-lg font-medium">Belum ada transaksi <?php echo ($type == 'pemasukan' ? 'penjualan' : 'pengeluaran'); ?></p>
                                                    <p class="text-gray-400 text-sm mt-1">Mulai catat transaksi pertama Anda menggunakan form di atas</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <div class="bg-white px-6 py-4 border-t border-gray-200">
                            <div class="flex items-center justify-between">
                                <div class="flex-1 flex justify-between sm:hidden">
                                    <?php if ($page > 1): ?>
                                        <a href="?<?php echo http_build_query(array_merge(array_filter(['type' => $type, 'limit' => $limit, 'search' => $search, 'date_filter' => $date_filter]), ['page' => $page - 1])); ?>" 
                                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</a>
                                    <?php endif; ?>
                                    <?php if ($page < $totalPages): ?>
                                        <a href="?<?php echo http_build_query(array_merge(array_filter(['type' => $type, 'limit' => $limit, 'search' => $search, 'date_filter' => $date_filter]), ['page' => $page + 1])); ?>" 
                                           class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</a>
                                    <?php endif; ?>
                                </div>
                                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            Menampilkan <span class="font-medium"><?php echo number_format($offset + 1); ?></span> sampai 
                                            <span class="font-medium"><?php echo number_format(min($offset + $limit, $totalTransactions)); ?></span> dari 
                                            <span class="font-medium"><?php echo number_format($totalTransactions); ?></span> transaksi
                                        </p>
                                    </div>
                                    <div>
                                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                            <?php 
                                            $current_params = array_filter([
                                                'type' => $type,
                                                'limit' => $limit,
                                                'search' => $search,
                                                'date_filter' => $date_filter
                                            ]);
                                            ?>

                                            <?php if ($page > 1): ?>
                                                <a href="?<?php echo http_build_query(array_merge($current_params, ['page' => $page - 1])); ?>" 
                                                   class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                    <span class="sr-only">Previous</span>
                                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </a>
                                            <?php endif; ?>

                                            <?php 
                                            $startPage = max(1, $page - 2);
                                            $endPage = min($totalPages, $page + 2);
                                            for ($i = $startPage; $i <= $endPage; $i++): 
                                            ?>
                                                <a href="?<?php echo http_build_query(array_merge($current_params, ['page' => $i])); ?>" 
                                                   class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo ($i == $page) ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50'; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            <?php endfor; ?>

                                            <?php if ($page < $totalPages): ?>
                                                <a href="?<?php echo http_build_query(array_merge($current_params, ['page' => $page + 1])); ?>" 
                                                   class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                    <span class="sr-only">Next</span>
                                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </a>
                                            <?php endif; ?>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="/cornerbites-sia/assets/js/transaksi.js"></script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>