
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
<div class="flex h-screen bg-gray-100 font-sans">
    <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="flex items-center justify-between h-16 bg-white border-b border-gray-200 px-6 shadow-sm">
            <h1 class="text-xl font-semibold text-gray-800">Laporan Keuangan</h1>
        </header>
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-200 p-6">
            <div class="container mx-auto">
                <!-- Navigasi Tipe Laporan -->
                <div class="flex space-x-4 mb-6">
                    <a href="?type=jurnal_umum&<?php echo http_build_query(array_filter(['search' => $search, 'date_filter' => $date_filter, 'custom_start' => $custom_start, 'custom_end' => $custom_end, 'limit' => $limit])); ?>" 
                       class="py-2 px-4 rounded-lg font-semibold <?php echo ($report_type == 'jurnal_umum' ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-gray-700 hover:bg-gray-100'); ?> transition duration-200">
                        Jurnal Umum
                    </a>
                    <a href="?type=laba_rugi&<?php echo http_build_query(array_filter(['date_filter' => $date_filter, 'custom_start' => $custom_start, 'custom_end' => $custom_end])); ?>" 
                       class="py-2 px-4 rounded-lg font-semibold <?php echo ($report_type == 'laba_rugi' ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-gray-700 hover:bg-gray-100'); ?> transition duration-200">
                        Laba Rugi
                    </a>
                </div>

                <!-- Filter Controls -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <form method="GET" class="flex flex-wrap gap-4 items-end">
                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($report_type); ?>">
                        
                        <?php if ($report_type == 'jurnal_umum'): ?>
                        <!-- Search untuk jurnal umum -->
                        <div class="flex-1 min-w-48">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pencarian</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Cari deskripsi atau jumlah..." 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   id="search-input">
                        </div>
                        <?php endif; ?>
                        
                        <!-- Filter Tanggal -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Filter Tanggal</label>
                            <select name="date_filter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="toggleCustomDate()">
                                <option value="semua" <?php echo $date_filter == 'semua' ? 'selected' : ''; ?>>Semua</option>
                                <option value="hari_ini" <?php echo $date_filter == 'hari_ini' ? 'selected' : ''; ?>>Hari Ini</option>
                                <option value="bulan_ini" <?php echo $date_filter == 'bulan_ini' ? 'selected' : ''; ?>>Bulan Ini</option>
                                <option value="tahun_ini" <?php echo $date_filter == 'tahun_ini' ? 'selected' : ''; ?>>Tahun Ini</option>
                                <option value="custom" <?php echo $date_filter == 'custom' ? 'selected' : ''; ?>>Custom</option>
                            </select>
                        </div>
                        
                        <!-- Custom Date Range -->
                        <div id="custom-date-range" class="flex gap-2" style="<?php echo $date_filter != 'custom' ? 'display: none;' : ''; ?>">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Dari</label>
                                <input type="date" name="custom_start" value="<?php echo htmlspecialchars($custom_start); ?>" 
                                       class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sampai</label>
                                <input type="date" name="custom_end" value="<?php echo htmlspecialchars($custom_end); ?>" 
                                       class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        
                        <?php if ($report_type == 'jurnal_umum'): ?>
                        <!-- Limit per halaman -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Per Halaman</label>
                            <select name="limit" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                                <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                                <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex gap-2">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Filter
                            </button>
                            <?php if ($report_type == 'jurnal_umum'): ?>
                            <button type="button" onclick="exportData('pdf')" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                Export PDF
                            </button>
                            <button type="button" onclick="exportData('excel')" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Export Excel
                            </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <?php if ($report_type == 'jurnal_umum'): ?>
                <!-- Jurnal Umum -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-700">
                            Jurnal Umum (<?php echo number_format($totalRows); ?> transaksi)
                        </h3>
                    </div>
                    
                    <?php if (!empty($data)): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Kredit</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($data as $row): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('d/m/Y', strtotime($row['date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($row['description']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $row['type'] == 'pemasukan' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo ucfirst($row['type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        <?php echo $row['type'] == 'pemasukan' ? 'Rp ' . number_format($row['amount'], 0, ',', '.') : '-'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        <?php echo $row['type'] == 'pengeluaran' ? 'Rp ' . number_format($row['amount'], 0, ',', '.') : '-'; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Menampilkan <?php echo number_format($offset + 1); ?> sampai <?php echo number_format(min($offset + $limit, $totalRows)); ?> dari <?php echo number_format($totalRows); ?> transaksi
                            </div>
                            <div class="flex space-x-2">
                                <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                   class="px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300">‹ Prev</a>
                                <?php endif; ?>
                                
                                <?php 
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                for ($i = $startPage; $i <= $endPage; $i++): 
                                ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                   class="px-3 py-2 text-sm rounded <?php echo $i == $page ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                                    <?php echo $i; ?>
                                </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                   class="px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Next ›</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="px-6 py-8 text-center text-gray-500">
                        Tidak ada data transaksi yang ditemukan.
                    </div>
                    <?php endif; ?>
                </div>

                <?php elseif ($report_type == 'laba_rugi'): ?>
                <!-- Laporan Laba Rugi -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">
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
                    <table class="min-w-full divide-y divide-gray-200">
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td class="px-6 py-3 whitespace-nowrap text-lg font-semibold text-gray-900">Pendapatan Penjualan</td>
                                <td class="px-6 py-3 whitespace-nowrap text-lg font-semibold text-gray-900 text-right">Rp <?php echo number_format($data['pendapatan_penjualan'], 0, ',', '.'); ?></td>
                            </tr>
                            <tr>
                                <td class="px-6 py-3 whitespace-nowrap text-lg text-gray-700">Harga Pokok Penjualan (HPP)</td>
                                <td class="px-6 py-3 whitespace-nowrap text-lg text-gray-700 text-right">(Rp <?php echo number_format($data['hpp'], 0, ',', '.'); ?>)</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-3 whitespace-nowrap text-xl font-bold text-gray-900">Laba Kotor</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xl font-bold text-gray-900 text-right">Rp <?php echo number_format($data['laba_kotor'], 0, ',', '.'); ?></td>
                            </tr>
                            <tr>
                                <td class="px-6 py-3 whitespace-nowrap text-lg text-gray-700">Beban Operasional</td>
                                <td class="px-6 py-3 whitespace-nowrap text-lg text-gray-700 text-right">(Rp <?php echo number_format($data['beban_operasional'], 0, ',', '.'); ?>)</td>
                            </tr>
                            <tr class="bg-blue-50">
                                <td class="px-6 py-3 whitespace-nowrap text-xl font-bold text-blue-800">Laba Bersih</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xl font-bold text-blue-800 text-right">Rp <?php echo number_format($data['laba_bersih'], 0, ',', '.'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<script>
function toggleCustomDate() {
    const dateFilter = document.querySelector('select[name="date_filter"]').value;
    const customRange = document.getElementById('custom-date-range');
    if (dateFilter === 'custom') {
        customRange.style.display = 'flex';
    } else {
        customRange.style.display = 'none';
    }
}

// Real-time search untuk jurnal umum
<?php if ($report_type == 'jurnal_umum'): ?>
let searchTimeout;
document.getElementById('search-input').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        document.querySelector('form').submit();
    }, 500);
});
<?php endif; ?>

function exportData(format) {
    const params = new URLSearchParams(window.location.search);
    params.set('export', format);
    params.delete('page'); // Reset pagination for export
    window.open('export_laporan.php?' + params.toString(), '_blank');
}
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
