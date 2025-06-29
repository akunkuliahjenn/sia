
<?php
// pages/laporan_keuangan.php
// Halaman untuk menampilkan laporan jurnal umum dan laba rugi dengan filter dan search

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$report_type = $_GET['type'] ?? 'jurnal_umum';
$search = $_GET['search'] ?? '';
$date_filter = $_GET['date_filter'] ?? 'semua';
$custom_start = $_GET['custom_start'] ?? '';
$custom_end = $_GET['custom_end'] ?? '';
$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], [10, 25, 50]) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$offset = ($page - 1) * $limit;

$page_title = ($report_type == 'jurnal_umum') ? 'Jurnal Umum' : 'Laporan Laba Rugi';

$data = [];
$totalRows = 0;
$totalPages = 1;

try {
    $conn = $db;
    
    if ($report_type == 'jurnal_umum') {
        // Query untuk jurnal umum dengan filter
        $whereClause = "WHERE 1=1";
        $params = [];
        
        // Filter pencarian
        if (!empty($search)) {
            $whereClause .= " AND (description LIKE :search OR amount LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        // Filter tanggal
        if ($date_filter == 'hari_ini') {
            $whereClause .= " AND DATE(date) = CURDATE()";
        } elseif ($date_filter == 'bulan_ini') {
            $whereClause .= " AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
        } elseif ($date_filter == 'tahun_ini') {
            $whereClause .= " AND YEAR(date) = YEAR(CURDATE())";
        } elseif ($date_filter == 'custom' && !empty($custom_start) && !empty($custom_end)) {
            $whereClause .= " AND DATE(date) BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $custom_start;
            $params[':end_date'] = $custom_end;
        }
        
        // Hitung total rows
        $countQuery = "SELECT COUNT(*) FROM transactions " . $whereClause;
        $countStmt = $conn->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $totalRows = $countStmt->fetchColumn();
        $totalPages = ceil($totalRows / $limit);
        
        // Query data dengan pagination
        $query = "SELECT id, date, description, type, amount, product_id FROM transactions " . $whereClause . " ORDER BY date DESC, id DESC LIMIT :limit OFFSET :offset";
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } elseif ($report_type == 'laba_rugi') {
        // Logika untuk laporan laba rugi dengan filter tanggal
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if ($date_filter == 'hari_ini') {
            $whereClause .= " AND DATE(date) = CURDATE()";
        } elseif ($date_filter == 'bulan_ini') {
            $whereClause .= " AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
        } elseif ($date_filter == 'tahun_ini') {
            $whereClause .= " AND YEAR(date) = YEAR(CURDATE())";
        } elseif ($date_filter == 'custom' && !empty($custom_start) && !empty($custom_end)) {
            $whereClause .= " AND DATE(date) BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $custom_start;
            $params[':end_date'] = $custom_end;
        }
        
        // Pendapatan
        $stmtPenjualan = $conn->prepare("SELECT SUM(amount) AS total_penjualan FROM transactions " . $whereClause . " AND type = 'pemasukan'");
        foreach ($params as $key => $value) {
            $stmtPenjualan->bindValue($key, $value);
        }
        $stmtPenjualan->execute();
        $totalPenjualan = $stmtPenjualan->fetchColumn() ?: 0;
        
        // HPP
        $stmtHPP = $conn->prepare("
            SELECT SUM(t.quantity * p.cost_price) AS total_hpp
            FROM transactions t
            JOIN products p ON t.product_id = p.id
            " . $whereClause . " AND t.type = 'pemasukan' AND t.product_id IS NOT NULL
        ");
        foreach ($params as $key => $value) {
            $stmtHPP->bindValue($key, $value);
        }
        $stmtHPP->execute();
        $totalHPP = $stmtHPP->fetchColumn() ?: 0;
        
        // Beban Operasional
        $stmtBeban = $conn->prepare("SELECT SUM(amount) AS total_beban FROM transactions " . $whereClause . " AND type = 'pengeluaran'");
        foreach ($params as $key => $value) {
            $stmtBeban->bindValue($key, $value);
        }
        $stmtBeban->execute();
        $totalBebanOperasional = $stmtBeban->fetchColumn() ?: 0;
        
        $labaKotor = $totalPenjualan - $totalHPP;
        $labaBersih = $labaKotor - $totalBebanOperasional;
        
        $data = [
            'pendapatan_penjualan' => $totalPenjualan,
            'hpp' => $totalHPP,
            'laba_kotor' => $labaKotor,
            'beban_operasional' => $totalBebanOperasional,
            'laba_bersih' => $labaBersih
        ];
    }
    
} catch (PDOException $e) {
    error_log("Error di halaman Laporan Keuangan: " . $e->getMessage());
}

?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>
<div class="flex h-screen bg-gradient-to-br from-gray-50 to-gray-100 font-sans">
    <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Bar/Header -->
        <header class="flex items-center justify-between h-16 bg-white border-b border-gray-200 px-6 shadow-sm backdrop-blur-sm bg-white/95">
            <div class="flex items-center space-x-3">
                <div class="w-2 h-8 bg-gradient-to-b from-purple-500 to-purple-600 rounded-full"></div>
                <h1 class="text-xl font-bold text-gray-800">Laporan Keuangan</h1>
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
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Laporan Keuangan</h1>
                    <p class="text-gray-600">Analisis kinerja keuangan bisnis Anda melalui jurnal umum dan laporan laba rugi yang komprehensif.</p>
                </div>

                <!-- Navigasi Tipe Laporan -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 mb-8">
                    <div class="flex items-center mb-6">
                        <div class="p-2 bg-blue-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">Pilih Jenis Laporan</h3>
                            <p class="text-sm text-gray-600 mt-1">Pilih jenis laporan keuangan yang ingin Anda lihat</p>
                        </div>
                    </div>
                    
                    <div class="flex flex-wrap gap-4">
                        <a href="?type=jurnal_umum&<?php echo http_build_query(array_filter(['search' => $search, 'date_filter' => $date_filter, 'custom_start' => $custom_start, 'custom_end' => $custom_end, 'limit' => $limit])); ?>" 
                           class="inline-flex items-center px-6 py-3 rounded-lg font-semibold transition duration-200 <?php echo ($report_type == 'jurnal_umum' ? 'bg-blue-600 text-white shadow-lg' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'); ?>">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Jurnal Umum
                        </a>
                        <a href="?type=laba_rugi&<?php echo http_build_query(array_filter(['date_filter' => $date_filter, 'custom_start' => $custom_start, 'custom_end' => $custom_end])); ?>" 
                           class="inline-flex items-center px-6 py-3 rounded-lg font-semibold transition duration-200 <?php echo ($report_type == 'laba_rugi' ? 'bg-blue-600 text-white shadow-lg' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'); ?>">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            Laba Rugi
                        </a>
                    </div>
                </div>

                <!-- Filter Controls -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 mb-8">
                    <div class="flex items-center mb-6">
                        <div class="p-2 bg-purple-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">Filter & Pencarian</h3>
                            <p class="text-sm text-gray-600 mt-1">Sesuaikan periode dan data yang ingin ditampilkan</p>
                        </div>
                    </div>

                    <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($report_type); ?>">
                        
                        <?php if ($report_type == 'jurnal_umum'): ?>
                        <!-- Search untuk jurnal umum -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Pencarian</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Cari deskripsi atau jumlah..." 
                                       class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                       id="search-input">
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Filter Tanggal -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Filter Tanggal</label>
                            <select name="date_filter" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" onchange="toggleCustomDate()">
                                <option value="semua" <?php echo $date_filter == 'semua' ? 'selected' : ''; ?>>Semua Periode</option>
                                <option value="hari_ini" <?php echo $date_filter == 'hari_ini' ? 'selected' : ''; ?>>Hari Ini</option>
                                <option value="bulan_ini" <?php echo $date_filter == 'bulan_ini' ? 'selected' : ''; ?>>Bulan Ini</option>
                                <option value="tahun_ini" <?php echo $date_filter == 'tahun_ini' ? 'selected' : ''; ?>>Tahun Ini</option>
                                <option value="custom" <?php echo $date_filter == 'custom' ? 'selected' : ''; ?>>Custom</option>
                            </select>
                        </div>
                        
                        <!-- Custom Date Range -->
                        <div id="custom-date-range" class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4" style="<?php echo $date_filter != 'custom' ? 'display: none;' : ''; ?>">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Mulai</label>
                                <input type="date" name="custom_start" value="<?php echo htmlspecialchars($custom_start); ?>" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Selesai</label>
                                <input type="date" name="custom_end" value="<?php echo htmlspecialchars($custom_end); ?>" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                            </div>
                        </div>
                        
                        <?php if ($report_type == 'jurnal_umum'): ?>
                        <!-- Limit per halaman -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Data per Halaman</label>
                            <select name="limit" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                                <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10 Data</option>
                                <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25 Data</option>
                                <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50 Data</option>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="<?php echo ($report_type == 'jurnal_umum') ? 'lg:col-span-4' : 'lg:col-span-2'; ?> flex flex-wrap gap-3">
                            <button type="submit" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200 font-semibold shadow-lg">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                                Filter Data
                            </button>
                            <a href="?" class="inline-flex items-center px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition duration-200 font-semibold">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Reset Filter
                            </a>
                            <?php if ($report_type == 'jurnal_umum'): ?>
                            <button type="button" onclick="exportData('excel')" class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-200 font-semibold">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Export Excel
                            </button>
                            <button type="button" onclick="exportData('csv')" class="inline-flex items-center px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition duration-200 font-semibold">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Export CSV
                            </button>
                            <button type="button" onclick="exportData('pdf')" class="inline-flex items-center px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-200 font-semibold">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                Export PDF
                            </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <?php if ($report_type == 'jurnal_umum'): ?>
                <!-- Jurnal Umum -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center">
                                <div class="p-2 bg-green-100 rounded-lg mr-3">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Jurnal Umum</h3>
                                    <p class="text-sm text-gray-600">Catatan lengkap semua transaksi keuangan</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-sm text-gray-500">Total:</span>
                                <span class="text-lg font-bold text-blue-600 ml-1"><?php echo number_format($totalRows); ?> transaksi</span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($data)): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                                    <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                                    <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Kredit</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($data as $row): ?>
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo date('d/m/Y', strtotime($row['date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="max-w-xs truncate" title="<?php echo htmlspecialchars($row['description']); ?>">
                                            <?php echo htmlspecialchars($row['description']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $row['type'] == 'pemasukan' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo ucfirst($row['type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                        <span class="<?php echo $row['type'] == 'pemasukan' ? 'text-green-600 font-semibold' : 'text-gray-400'; ?>">
                                            <?php echo $row['type'] == 'pemasukan' ? 'Rp ' . number_format($row['amount'], 0, ',', '.') : '-'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                        <span class="<?php echo $row['type'] == 'pengeluaran' ? 'text-red-600 font-semibold' : 'text-gray-400'; ?>">
                                            <?php echo $row['type'] == 'pengeluaran' ? 'Rp ' . number_format($row['amount'], 0, ',', '.') : '-'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <div class="flex flex-col md:flex-row justify-between items-center gap-3">
                            <div class="text-sm text-gray-700">
                                Menampilkan <?php echo number_format($offset + 1); ?> sampai <?php echo number_format(min($offset + $limit, $totalRows)); ?> dari <?php echo number_format($totalRows); ?> transaksi
                            </div>
                            <div class="flex items-center space-x-2">
                                <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                   class="px-4 py-2 text-sm bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-200">‹ Sebelumnya</a>
                                <?php endif; ?>
                                
                                <?php 
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                for ($i = $startPage; $i <= $endPage; $i++): 
                                ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                   class="px-3 py-2 text-sm rounded-lg transition duration-200 <?php echo $i == $page ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                   class="px-4 py-2 text-sm bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-200">Berikutnya ›</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="px-6 py-12 text-center">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-gray-500 text-lg font-medium">Tidak ada data transaksi yang ditemukan</p>
                        <p class="text-gray-400 text-sm mt-1">Coba sesuaikan filter atau tambahkan transaksi baru</p>
                    </div>
                    <?php endif; ?>
                </div>

                <?php elseif ($report_type == 'laba_rugi'): ?>
                <!-- Laporan Laba Rugi -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                    <div class="flex items-center mb-6">
                        <div class="p-2 bg-green-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">
                                Laporan Laba Rugi 
                                <?php 
                                if ($date_filter == 'hari_ini') echo '- Hari Ini';
                                elseif ($date_filter == 'bulan_ini') echo '- Bulan ' . date('F Y');
                                elseif ($date_filter == 'tahun_ini') echo '- Tahun ' . date('Y');
                                elseif ($date_filter == 'custom' && !empty($custom_start) && !empty($custom_end)) 
                                    echo '- ' . date('d/m/Y', strtotime($custom_start)) . ' s/d ' . date('d/m/Y', strtotime($custom_end));
                                else echo '- Kumulatif';
                                ?>
                            </h3>
                            <p class="text-sm text-gray-600 mt-1">Analisis profitabilitas dan kinerja keuangan bisnis</p>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <tbody class="divide-y divide-gray-200">
                                <tr class="bg-green-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-lg font-bold text-green-800">Pendapatan Penjualan</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-lg font-bold text-green-800 text-right">Rp <?php echo number_format($data['pendapatan_penjualan'], 0, ',', '.'); ?></td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-lg text-gray-700 pl-12">Harga Pokok Penjualan (HPP)</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-lg text-red-600 text-right">(Rp <?php echo number_format($data['hpp'], 0, ',', '.'); ?>)</td>
                                </tr>
                                <tr class="bg-blue-50 border-t-2 border-blue-200">
                                    <td class="px-6 py-4 whitespace-nowrap text-xl font-bold text-blue-800">Laba Kotor</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xl font-bold text-blue-800 text-right">Rp <?php echo number_format($data['laba_kotor'], 0, ',', '.'); ?></td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-lg text-gray-700 pl-12">Beban Operasional</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-lg text-red-600 text-right">(Rp <?php echo number_format($data['beban_operasional'], 0, ',', '.'); ?>)</td>
                                </tr>
                                <tr class="bg-gradient-to-r from-purple-50 to-blue-50 border-t-4 border-purple-300">
                                    <td class="px-6 py-4 whitespace-nowrap text-2xl font-bold text-purple-800">Laba Bersih</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-2xl font-bold <?php echo $data['laba_bersih'] >= 0 ? 'text-green-600' : 'text-red-600'; ?> text-right">
                                        <?php echo $data['laba_bersih'] >= 0 ? 'Rp ' : '(Rp '; ?><?php echo number_format(abs($data['laba_bersih']), 0, ',', '.'); ?><?php echo $data['laba_bersih'] < 0 ? ')' : ''; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Summary Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8">
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="p-2 bg-green-100 rounded-lg mr-3">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-green-800">Margin Laba Kotor</h4>
                                    <p class="text-lg font-bold text-green-900">
                                        <?php echo $data['pendapatan_penjualan'] > 0 ? number_format(($data['laba_kotor'] / $data['pendapatan_penjualan']) * 100, 1) : '0'; ?>%
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="p-2 bg-blue-100 rounded-lg mr-3">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-blue-800">Margin Laba Bersih</h4>
                                    <p class="text-lg font-bold text-blue-900">
                                        <?php echo $data['pendapatan_penjualan'] > 0 ? number_format(($data['laba_bersih'] / $data['pendapatan_penjualan']) * 100, 1) : '0'; ?>%
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="p-2 bg-purple-100 rounded-lg mr-3">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-purple-800">Rasio Beban</h4>
                                    <p class="text-lg font-bold text-purple-900">
                                        <?php echo $data['pendapatan_penjualan'] > 0 ? number_format((($data['hpp'] + $data['beban_operasional']) / $data['pendapatan_penjualan']) * 100, 1) : '0'; ?>%
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<script src="/cornerbites-sia/assets/js/laporan_keuangan.js"></script>
<script>
// Report type specific initialization
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($report_type == 'jurnal_umum'): ?>
    initializeJurnalUmum();
    <?php endif; ?>
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
