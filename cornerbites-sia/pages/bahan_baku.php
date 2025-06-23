<?php
// pages/bahan_baku.php
// Halaman manajemen data bahan baku (CRUD) dengan pagination dan pencarian

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

// Pesan sukses atau error setelah proses
$message = '';
$message_type = ''; // 'success' or 'error'
if (isset($_SESSION['bahan_baku_message'])) {
    $message = $_SESSION['bahan_baku_message']['text'];
    $message_type = $_SESSION['bahan_baku_message']['type'];
    unset($_SESSION['bahan_baku_message']);
}

// Pilihan unit dan jenis (type)
$unitOptions = ['kg', 'gram', 'liter', 'ml', 'pcs', 'buah', 'roll', 'meter', 'box', 'botol', 'lembar']; // 'pack' dihapus
$typeOptions = ['bahan', 'kemasan'];

// Inisialisasi variabel pencarian
$searchQueryRaw = $_GET['search_raw'] ?? '';
$searchQueryPackaging = $_GET['search_kemasan'] ?? '';

// --- Logika Pagination dan Pengambilan Data untuk BAHAN BAKU ---
$rawMaterials = [];
$totalRawMaterialsRows = 0;
$totalRawMaterialsPages = 1;
$rawMaterialsLimitOptions = [5, 10, 25, 50]; // Pilihan jumlah item per halaman
$rawMaterialsLimit = isset($_GET['bahan_limit']) && in_array((int)$_GET['bahan_limit'], $rawMaterialsLimitOptions) ? (int)$_GET['bahan_limit'] : 5;
$rawMaterialsPage = isset($_GET['bahan_page']) ? max((int)$_GET['bahan_page'], 1) : 1;
$rawMaterialsOffset = ($rawMaterialsPage - 1) * $rawMaterialsLimit;

try {
    $conn = $db;

    // Hitung total baris untuk bahan baku dengan filter pencarian
    $queryTotalRaw = "SELECT COUNT(*) FROM raw_materials WHERE type = 'bahan'";
    if (!empty($searchQueryRaw)) {
        $queryTotalRaw .= " AND name LIKE :search_raw_term";
    }
    $stmtTotalRaw = $conn->prepare($queryTotalRaw);
    if (!empty($searchQueryRaw)) {
        $stmtTotalRaw->bindValue(':search_raw_term', '%' . $searchQueryRaw . '%', PDO::PARAM_STR);
    }
    $stmtTotalRaw->execute();
    $totalRawMaterialsRows = $stmtTotalRaw->fetchColumn();
    $totalRawMaterialsPages = ceil($totalRawMaterialsRows / $rawMaterialsLimit);

    // Pastikan halaman tidak melebihi total halaman yang ada untuk bahan baku
    if ($rawMaterialsPage > $totalRawMaterialsPages && $totalRawMaterialsPages > 0) {
        $rawMaterialsPage = $totalRawMaterialsPages;
        $rawMaterialsOffset = ($rawMaterialsPage - 1) * $rawMaterialsLimit;
    }

    // Query untuk mengambil bahan baku dengan LIMIT, OFFSET, dan filter pencarian
    $queryRaw = "SELECT id, name, unit, purchase_price_per_unit, default_package_quantity, current_stock, type FROM raw_materials WHERE type = 'bahan'";
    if (!empty($searchQueryRaw)) {
        $queryRaw .= " AND name LIKE :search_raw_term";
    }
    $queryRaw .= " ORDER BY name ASC LIMIT :limit OFFSET :offset";

    $stmtRaw = $conn->prepare($queryRaw);
    if (!empty($searchQueryRaw)) {
        $stmtRaw->bindValue(':search_raw_term', '%' . $searchQueryRaw . '%', PDO::PARAM_STR);
    }
    $stmtRaw->bindParam(':limit', $rawMaterialsLimit, PDO::PARAM_INT);
    $stmtRaw->bindParam(':offset', $rawMaterialsOffset, PDO::PARAM_INT);
    $stmtRaw->execute();
    $rawMaterials = $stmtRaw->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error di halaman Bahan Baku (fetch bahan): " . $e->getMessage());
    $message = "Terjadi kesalahan saat memuat data bahan baku.";
    $message_type = "error";
}

// --- Logika Pagination dan Pengambilan Data untuk KEMASAN ---
$packagingMaterials = [];
$totalPackagingMaterialsRows = 0;
$totalPackagingMaterialsPages = 1;
$packagingMaterialsLimitOptions = [5, 10, 25, 50]; // Pilihan jumlah item per halaman, bisa sama atau beda
$packagingMaterialsLimit = isset($_GET['kemasan_limit']) && in_array((int)$_GET['kemasan_limit'], $packagingMaterialsLimitOptions) ? (int)$_GET['kemasan_limit'] : 5;
$packagingMaterialsPage = isset($_GET['kemasan_page']) ? max((int)$_GET['kemasan_page'], 1) : 1;
$packagingMaterialsOffset = ($packagingMaterialsPage - 1) * $packagingMaterialsLimit;

try {
    // Hitung total baris untuk kemasan dengan filter pencarian
    $queryTotalPackaging = "SELECT COUNT(*) FROM raw_materials WHERE type = 'kemasan'";
    if (!empty($searchQueryPackaging)) {
        $queryTotalPackaging .= " AND name LIKE :search_kemasan_term";
    }
    $stmtTotalPackaging = $conn->prepare($queryTotalPackaging);
    if (!empty($searchQueryPackaging)) {
        $stmtTotalPackaging->bindValue(':search_kemasan_term', '%' . $searchQueryPackaging . '%', PDO::PARAM_STR);
    }
    $stmtTotalPackaging->execute();
    $totalPackagingMaterialsRows = $stmtTotalPackaging->fetchColumn();
    $totalPackagingMaterialsPages = ceil($totalPackagingMaterialsRows / $packagingMaterialsLimit);

    // Pastikan halaman tidak melebihi total halaman yang ada untuk kemasan
    if ($packagingMaterialsPage > $totalPackagingMaterialsPages && $totalPackagingMaterialsPages > 0) {
        $packagingMaterialsPage = $totalPackagingMaterialsPages;
        $packagingMaterialsOffset = ($packagingMaterialsPage - 1) * $packagingMaterialsLimit;
    }

    // Query untuk mengambil kemasan dengan LIMIT, OFFSET, dan filter pencarian
    $queryPackaging = "SELECT id, name, unit, purchase_price_per_unit, default_package_quantity, current_stock, type FROM raw_materials WHERE type = 'kemasan'";
    if (!empty($searchQueryPackaging)) {
        $queryPackaging .= " AND name LIKE :search_kemasan_term";
    }
    $queryPackaging .= " ORDER BY name ASC LIMIT :limit OFFSET :offset";

    $stmtPackaging = $conn->prepare($queryPackaging);
    if (!empty($searchQueryPackaging)) {
        $stmtPackaging->bindValue(':search_kemasan_term', '%' . $searchQueryPackaging . '%', PDO::PARAM_STR);
    }
    $stmtPackaging->bindParam(':limit', $packagingMaterialsLimit, PDO::PARAM_INT);
    $stmtPackaging->bindParam(':offset', $packagingMaterialsOffset, PDO::PARAM_INT);
    $stmtPackaging->execute();
    $packagingMaterials = $stmtPackaging->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error di halaman Bahan Baku (fetch kemasan): " . $e->getMessage());
    // Pesan error sudah di atas, tidak perlu timpa kecuali ada error spesifik
}

// Fungsi helper untuk membangun URL pagination dengan mempertahankan parameter lain
function buildPaginationUrl($baseUrl, $paramsToUpdate) {
    $queryParams = $_GET;
    foreach ($paramsToUpdate as $key => $value) {
        $queryParams[$key] = $value;
    }
    return $baseUrl . '?' . http_build_query($queryParams);
}

?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>
<div class="flex h-screen bg-gray-100 font-sans">
    <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col">
        <main class="flex-1 p-8 overflow-y-auto">
            <div class="container mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Manajemen Bahan Baku & Kemasan</h1>

                <!-- Catatan di atas formulir -->
                <p class="text-sm text-gray-600 mb-6 p-3 bg-blue-50 rounded-lg border border-blue-200">
                    <strong>Catatan:</strong> Gunakan formulir di bawah ini untuk menambahkan bahan baku atau kemasan <strong> baru </strong>ke dalam sistem Anda.
                    Jika Anda melakukan pembelian ulang untuk bahan baku atau kemasan yang sudah ada,
                    Anda <strong> cukup mengedit </strong>jumlah 'Stok Saat Ini' di daftar bahan baku atau kemasan di bawah ini.
                </p>

                <!-- Form Tambah/Edit Bahan Baku/Kemasan -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4" id="form-title">Tambah Bahan Baku/Kemasan Baru</h2>
                    <?php if (!empty($message)): ?>
                        <div class="p-3 mb-4 text-sm rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    <form action="../process/simpan_bahan_baku.php" method="POST">
                        <input type="hidden" name="bahan_baku_id" id="bahan_baku_id">

                        <div class="mb-4">
                            <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Nama Item:</label>
                            <input type="text" id="name" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                        <div class="mb-4">
                            <label for="type" class="block text-gray-700 text-sm font-bold mb-2">Jenis:</label>
                            <select id="type" name="type" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                <?php foreach ($typeOptions as $typeOption): ?>
                                    <option value="<?php echo htmlspecialchars($typeOption); ?>"><?php echo ucfirst($typeOption); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="unit" class="block text-gray-700 text-sm font-bold mb-2">Satuan Unit Stok (e.g., kg, pcs, liter):</label>
                            <select id="unit" name="unit" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                <?php foreach ($unitOptions as $unitOption): ?>
                                    <option value="<?php echo htmlspecialchars($unitOption); ?>"><?php echo htmlspecialchars($unitOption); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="default_package_quantity" class="block text-gray-700 text-sm font-bold mb-2">Volume Default Paket (Jumlah per Satuan Stok, e.g., 1.5 untuk 1.5kg):</label>
                            <input type="number" step="0.001" id="default_package_quantity" name="default_package_quantity" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" min="0">
                            <p class="text-xs text-gray-500 mt-1">Isi jika item ini biasanya dibeli dalam paket dengan volume/berat tertentu. Misalnya, untuk tepung 1.5kg, isi "1.5".</p>
                        </div>
                        <div class="mb-4">
                            <label for="purchase_price_per_unit" class="block text-gray-700 text-sm font-bold mb-2">Harga Beli per Unit Stok (Rp):</label>
                            <input type="number" step="0.01" id="purchase_price_per_unit" name="purchase_price_per_unit" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" min="0" required>
                        </div>
                        <div class="mb-6">
                            <label for="current_stock" class="block text-gray-700 text-sm font-bold mb-2">Stok Saat Ini (dalam Satuan Unit Stok):</label>
                            <input type="number" step="0.001" id="current_stock" name="current_stock" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" min="0" required>
                        </div>
                        <div class="flex items-center justify-between">
                            <button type="submit" id="submit_button" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Tambah Item
                            </button>
                            <button type="button" id="cancel_edit_button" class="hidden bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline ml-4" onclick="resetForm()">
                                Batal Edit
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Bagian Daftar Bahan Baku dan Kemasan (Dua Kolom) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Daftar Bahan Baku -->
                    <div>
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h2 class="text-2xl font-bold text-gray-800 mb-4">Daftar Bahan Baku</h2>
                            <div class="mb-4 flex flex-col md:flex-row justify-between items-center space-y-2 md:space-y-0">
                                <div class="flex items-center">
                                    <label for="bahan_limit" class="text-gray-700 text-sm font-bold mr-2">Tampilkan:</label>
                                    <select id="bahan_limit" onchange="window.location.href = 'bahan_baku.php?bahan_limit=' + this.value + '&bahan_page=1&kemasan_limit=<?php echo $packagingMaterialsLimit; ?>&kemasan_page=<?php echo $packagingMaterialsPage; ?>&search_raw=<?php echo urlencode($searchQueryRaw); ?>&search_kemasan=<?php echo urlencode($searchQueryPackaging); ?>'" class="shadow border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                        <?php foreach ($rawMaterialsLimitOptions as $option): ?>
                                            <option value="<?php echo $option; ?>" <?php echo $rawMaterialsLimit == $option ? 'selected' : ''; ?>><?php echo $option; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="ml-2 text-gray-700 text-sm">entri</span>
                                </div>
                                <div class="flex items-center">
                                    <input type="text" id="search_raw" onkeyup="filterRawMaterials(event)" placeholder="Cari Bahan Baku..." class="shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    <button onclick="applySearch('raw')" class="ml-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-3 rounded">Cari</button>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">Total Bahan Baku: <?php echo $totalRawMaterialsRows; ?></p>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Satuan</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Volume Default Paket</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Beli/Unit</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php if (!empty($rawMaterials)): ?>
                                            <?php foreach ($rawMaterials as $material): ?>
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($material['name']); ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($material['unit']); ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <?php echo $material['default_package_quantity'] !== null ? number_format($material['default_package_quantity'], 3, ',', '.') : '-'; ?> <?php echo htmlspecialchars($material['unit']); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Rp <?php echo number_format($material['purchase_price_per_unit'], 0, ',', '.'); ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo number_format($material['current_stock'], 0, ',', '.'); ?> <?php echo htmlspecialchars($material['unit']); ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <button onclick="editBahanBaku(<?php echo htmlspecialchars(json_encode($material)); ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                                        <a href="../process/simpan_bahan_baku.php?action=delete&id=<?php echo $material['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Apakah Anda yakin ingin menghapus bahan baku ini? Tindakan ini tidak dapat dibatalkan dan akan memengaruhi resep yang menggunakannya.');">Hapus</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Belum ada bahan baku tercatat.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Pagination Controls Bahan Baku -->
                            <div class="mt-6 flex justify-between items-center">
                                <?php if ($totalRawMaterialsPages > 1): ?>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <!-- Previous Button -->
                                        <?php if ($rawMaterialsPage > 1): ?>
                                            <a href="<?php echo buildPaginationUrl('bahan_baku.php', ['bahan_page' => $rawMaterialsPage - 1, 'bahan_limit' => $rawMaterialsLimit]); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <span class="sr-only">Previous</span>
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </a>
                                        <?php endif; ?>

                                        <!-- Page Numbers -->
                                        <?php
                                        $startPageRawDisplay = max(1, $rawMaterialsPage - 1);
                                        $endPageRawDisplay = min($totalRawMaterialsPages, $rawMaterialsPage + 1);

                                        if ($startPageRawDisplay > 1) {
                                            echo '<a href="' . buildPaginationUrl('bahan_baku.php', ['bahan_page' => 1, 'bahan_limit' => $rawMaterialsLimit]) . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                                            if ($startPageRawDisplay > 2) {
                                                echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                            }
                                        }

                                        for ($i = $startPageRawDisplay; $i <= $endPageRawDisplay; $i++):
                                        ?>
                                            <a href="<?php echo buildPaginationUrl('bahan_baku.php', ['bahan_page' => $i, 'bahan_limit' => $rawMaterialsLimit]); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $rawMaterialsPage == $i ? 'bg-indigo-50 border-indigo-500 text-indigo-600' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>

                                        <?php
                                        if ($endPageRawDisplay < $totalRawMaterialsPages) {
                                            if ($endPageRawDisplay < $totalRawMaterialsPages - 1) {
                                                echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                            }
                                            echo '<a href="' . buildPaginationUrl('bahan_baku.php', ['bahan_page' => $totalRawMaterialsPages, 'bahan_limit' => $rawMaterialsLimit]) . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $totalRawMaterialsPages . '</a>';
                                        }
                                        ?>

                                        <!-- Next Button -->
                                        <?php if ($rawMaterialsPage < $totalRawMaterialsPages): ?>
                                            <a href="<?php echo buildPaginationUrl('bahan_baku.php', ['bahan_page' => $rawMaterialsPage + 1, 'bahan_limit' => $rawMaterialsLimit]); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <span class="sr-only">Next</span>
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </a>
                                        <?php endif; ?>
                                    </nav>
                                <?php endif; ?>
                                <p class="text-sm text-gray-600">Halaman <?php echo $rawMaterialsPage; ?> dari <?php echo $totalRawMaterialsPages; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Daftar Bahan Kemasan -->
                    <div>
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h2 class="text-2xl font-bold text-gray-800 mb-4">Daftar Kemasan</h2>
                            <div class="mb-4 flex flex-col md:flex-row justify-between items-center space-y-2 md:space-y-0">
                                <div class="flex items-center">
                                    <label for="kemasan_limit" class="text-gray-700 text-sm font-bold mr-2">Tampilkan:</label>
                                    <select id="kemasan_limit" onchange="window.location.href = 'bahan_baku.php?kemasan_limit=' + this.value + '&kemasan_page=1&bahan_limit=<?php echo $rawMaterialsLimit; ?>&bahan_page=<?php echo $rawMaterialsPage; ?>&search_raw=<?php echo urlencode($searchQueryRaw); ?>&search_kemasan=<?php echo urlencode($searchQueryPackaging); ?>'" class="shadow border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                        <?php foreach ($packagingMaterialsLimitOptions as $option): ?>
                                            <option value="<?php echo $option; ?>" <?php echo $packagingMaterialsLimit == $option ? 'selected' : ''; ?>><?php echo $option; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="ml-2 text-gray-700 text-sm">entri</span>
                                </div>
                                <div class="flex items-center">
                                    <input type="text" id="search_kemasan" onkeyup="filterPackagingMaterials(event)" placeholder="Cari Kemasan..." class="shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    <button onclick="applySearch('kemasan')" class="ml-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-3 rounded">Cari</button>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">Total Kemasan: <?php echo $totalPackagingMaterialsRows; ?></p>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Satuan</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Volume Default Paket</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Beli/Unit</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php if (!empty($packagingMaterials)): ?>
                                            <?php foreach ($packagingMaterials as $material): ?>
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($material['name']); ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($material['unit']); ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <?php echo $material['default_package_quantity'] !== null ? number_format($material['default_package_quantity'], 3, ',', '.') : '-'; ?> <?php echo htmlspecialchars($material['unit']); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Rp <?php echo number_format($material['purchase_price_per_unit'], 0, ',', '.'); ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo number_format($material['current_stock'], 0, ',', '.'); ?> <?php echo htmlspecialchars($material['unit']); ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <button onclick="editBahanBaku(<?php echo htmlspecialchars(json_encode($material)); ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                                        <a href="../process/simpan_bahan_baku.php?action=delete&id=<?php echo $material['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Apakah Anda yakin ingin menghapus bahan baku ini? Tindakan ini tidak dapat dibatalkan dan akan memengaruhi resep yang menggunakannya.');">Hapus</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Belum ada bahan kemasan tercatat.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Pagination Controls Kemasan -->
                            <div class="mt-6 flex justify-between items-center">
                                <?php if ($totalPackagingMaterialsPages > 1): ?>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <!-- Previous Button -->
                                        <?php if ($packagingMaterialsPage > 1): ?>
                                            <a href="<?php echo buildPaginationUrl('bahan_baku.php', ['kemasan_page' => $packagingMaterialsPage - 1, 'kemasan_limit' => $packagingMaterialsLimit]); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <span class="sr-only">Previous</span>
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </a>
                                        <?php endif; ?>

                                        <!-- Page Numbers -->
                                        <?php
                                        $startPagePackagingDisplay = max(1, $packagingMaterialsPage - 1);
                                        $endPagePackagingDisplay = min($totalPackagingMaterialsPages, $packagingMaterialsPage + 1);

                                        if ($startPagePackagingDisplay > 1) {
                                            echo '<a href="' . buildPaginationUrl('bahan_baku.php', ['kemasan_page' => 1, 'kemasan_limit' => $packagingMaterialsLimit]) . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                                            if ($startPagePackagingDisplay > 2) {
                                                echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                            }
                                        }

                                        for ($i = $startPagePackagingDisplay; $i <= $endPagePackagingDisplay; $i++):
                                        ?>
                                            <a href="<?php echo buildPaginationUrl('bahan_baku.php', ['kemasan_page' => $i, 'kemasan_limit' => $packagingMaterialsLimit]); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $packagingMaterialsPage == $i ? 'bg-indigo-50 border-indigo-500 text-indigo-600' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>

                                        <?php
                                        if ($endPagePackagingDisplay < $totalPackagingMaterialsPages) {
                                            if ($endPagePackagingDisplay < $totalPackagingMaterialsPages - 1) {
                                                echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                            }
                                            echo '<a href="' . buildPaginationUrl('bahan_baku.php', ['kemasan_page' => $totalPackagingMaterialsPages, 'kemasan_limit' => $packagingMaterialsLimit]) . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $totalPackagingMaterialsPages . '</a>';
                                        }
                                        ?>

                                        <!-- Next Button -->
                                        <?php if ($packagingMaterialsPage < $totalPackagingMaterialsPages): ?>
                                            <a href="<?php echo buildPaginationUrl('bahan_baku.php', ['kemasan_page' => $packagingMaterialsPage + 1, 'kemasan_limit' => $packagingMaterialsLimit]); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <span class="sr-only">Next</span>
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </a>
                                        <?php endif; ?>
                                    </nav>
                                <?php endif; ?>
                                <p class="text-sm text-gray-600">Halaman <?php echo $packagingMaterialsPage; ?> dari <?php echo $totalPackagingMaterialsPages; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- JavaScript untuk Edit Form dan Pencarian -->
<script>
    // Definisikan unitOptions dan typeOptions di JavaScript juga untuk reset form yang benar
    const unitOptions = <?php echo json_encode($unitOptions); ?>;
    const typeOptions = <?php echo json_encode($typeOptions); ?>;

    function editBahanBaku(material) {
        document.getElementById('bahan_baku_id').value = material.id;
        document.getElementById('name').value = material.name;
        document.getElementById('type').value = material.type; // Set the type dropdown
        document.getElementById('unit').value = material.unit; // Set the unit dropdown
        // Periksa jika default_package_quantity adalah null, tampilkan string kosong
        document.getElementById('default_package_quantity').value = material.default_package_quantity !== null ? material.default_package_quantity : '';
        document.getElementById('purchase_price_per_unit').value = material.purchase_price_per_unit;
        document.getElementById('current_stock').value = material.current_stock;

        document.getElementById('form-title').textContent = 'Edit Item'; // Ubah teks form title
        const submitButton = document.getElementById('submit_button');
        const cancelButton = document.getElementById('cancel_edit_button');
        
        submitButton.textContent = 'Update Item'; // Ubah teks tombol submit
        submitButton.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        submitButton.classList.add('bg-indigo-600', 'hover:bg-indigo-700');
        cancelButton.classList.remove('hidden');

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function resetForm() {
        document.getElementById('bahan_baku_id').value = '';
        document.getElementById('name').value = '';
        document.getElementById('type').value = typeOptions[0]; // Reset ke pilihan pertama (bahan)
        document.getElementById('unit').value = unitOptions[0]; // Reset ke pilihan pertama (kg)
        document.getElementById('default_package_quantity').value = ''; // Reset volume default paket
        document.getElementById('purchase_price_per_unit').value = ''; 
        document.getElementById('current_stock').value = '';

        document.getElementById('form-title').textContent = 'Tambah Bahan Baku/Kemasan Baru'; // Kembalikan teks form title
        const submitButton = document.getElementById('submit_button');
        const cancelButton = document.getElementById('cancel_edit_button');
        
        submitButton.textContent = 'Tambah Item'; // Kembalikan teks tombol submit
        submitButton.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
        submitButton.classList.add('bg-blue-600', 'hover:bg-blue-700');
        cancelButton.classList.add('hidden');
    }

    // Fungsi untuk menerapkan pencarian
    function applySearch(type) {
        const searchInputId = type === 'raw' ? 'search_raw' : 'search_kemasan';
        const searchTerm = document.getElementById(searchInputId).value;
        let currentUrl = new URL(window.location.href);

        if (type === 'raw') {
            currentUrl.searchParams.set('search_raw', searchTerm);
            currentUrl.searchParams.set('bahan_page', '1'); // Reset halaman ke 1 saat mencari
        } else {
            currentUrl.searchParams.set('search_kemasan', searchTerm);
            currentUrl.searchParams.set('kemasan_page', '1'); // Reset halaman ke 1 saat mencari
        }
        
        window.location.href = currentUrl.toString();
    }

    // Mendengarkan tombol enter pada input pencarian
    document.getElementById('search_raw').addEventListener('keyup', function(event) {
        if (event.key === 'Enter') {
            applySearch('raw');
        }
    });

    document.getElementById('search_kemasan').addEventListener('keyup', function(event) {
        if (event.key === 'Enter') {
            applySearch('kemasan');
        }
    });

    // Mempertahankan nilai search input setelah halaman dimuat ulang
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const searchRaw = urlParams.get('search_raw');
        const searchKemasan = urlParams.get('search_kemasan');

        if (searchRaw) {
            document.getElementById('search_raw').value = searchRaw;
        }
        if (searchKemasan) {
            document.getElementById('search_kemasan').value = searchKemasan;
        }
    });

</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
