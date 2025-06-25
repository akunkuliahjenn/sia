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
$unitOptions = ['kg', 'gram', 'liter', 'ml', 'pcs', 'buah', 'roll', 'meter', 'box', 'botol', 'lembar'];
$typeOptions = ['bahan', 'kemasan'];

// Inisialisasi variabel pencarian
$searchQueryRaw = $_GET['search_raw'] ?? '';
$searchQueryPackaging = $_GET['search_kemasan'] ?? '';

// --- Logika Pagination dan Pengambilan Data untuk BAHAN BAKU ---
$rawMaterials = [];
$totalRawMaterialsRows = 0;
$totalRawMaterialsPages = 1;
$rawMaterialsLimitOptions = [6, 12, 18, 24];
$rawMaterialsLimit = isset($_GET['bahan_limit']) && in_array((int)$_GET['bahan_limit'], $rawMaterialsLimitOptions) ? (int)$_GET['bahan_limit'] : 6;
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
    $queryRaw = "SELECT id, name, brand, unit, purchase_price_per_unit, default_package_quantity, current_stock, type FROM raw_materials WHERE type = 'bahan'";
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
$packagingMaterialsLimitOptions = [6, 12, 18, 24];
$packagingMaterialsLimit = isset($_GET['kemasan_limit']) && in_array((int)$_GET['kemasan_limit'], $packagingMaterialsLimitOptions) ? (int)$_GET['kemasan_limit'] : 6;
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
    $queryPackaging = "SELECT id, name, brand, unit, purchase_price_per_unit, default_package_quantity, current_stock, type FROM raw_materials WHERE type = 'kemasan'";
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

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Nama Bahan:</label>
                                <input type="text" id="name" name="name" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Contoh: Garam, Tepung, Plastik Cup" required>
                            </div>

                            <div>
                                <label for="brand" class="block text-gray-700 text-sm font-bold mb-2">Merek:</label>
                                <input type="text" id="brand" name="brand" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Contoh: Indosalt, Bogasari, dll">
                            </div>

                            <div>
                                <label for="type" class="block text-gray-700 text-sm font-bold mb-2">Jenis:</label>
                                <select id="type" name="type" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                    <option value="bahan">Bahan Baku</option>
                                    <option value="kemasan">Kemasan</option>
                                </select>
                            </div>

                            <div>
                                <label for="purchase_size" class="block text-gray-700 text-sm font-bold mb-2">Ukuran Beli:</label>
                                <input type="number" step="0.001" id="purchase_size" name="purchase_size" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Contoh: 250, 1.5, 100" min="0" required>
                                <p class="text-xs text-gray-500 mt-1">Ukuran per kemasan yang Anda beli (misal: 250 gram, 1.5 kg)</p>
                            </div>

                            <div>
                                <label for="unit" class="block text-gray-700 text-sm font-bold mb-2">Satuan:</label>
                                <select id="unit" name="unit" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                    <?php foreach ($unitOptions as $unitOption): ?>
                                        <option value="<?php echo htmlspecialchars($unitOption); ?>"><?php echo htmlspecialchars($unitOption); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="purchase_price_per_unit" class="block text-gray-700 text-sm font-bold mb-2">Harga Beli (Rp):</label>
                                <input type="number" step="1" id="purchase_price_per_unit" name="purchase_price_per_unit" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Contoh: 10000" min="0" required>
                                <p class="text-xs text-gray-500 mt-1">Harga per kemasan yang Anda beli</p>
                            </div>

                            <div>
                                <label for="current_stock" class="block text-gray-700 text-sm font-bold mb-2">Jumlah Bahan/Kemasan:</label>
                                <input type="number" step="1" id="current_stock" name="current_stock" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Contoh: 5" min="0" required>
                                <p class="text-xs text-gray-500 mt-1">Berapa Bahan/kemasan yang Anda punya</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between mt-6">
                            <button type="submit" id="submit_button" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded focus:outline-none focus:shadow-outline">
                                Tambah Bahan/Kemasan
                            </button>
                            <button type="button" id="cancel_edit_button" class="hidden bg-gray-500 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded focus:outline-none focus:shadow-outline ml-4" onclick="resetForm()">
                                Batal Edit
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Bagian Daftar Bahan Baku dan Kemasan -->
                <div class="grid grid-cols-1 gap-8">
                    <!-- Daftar Bahan Baku -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4">Daftar Bahan Baku</h2>
                        <div class="mb-4 flex flex-col md:flex-row justify-between items-center space-y-2 md:space-y-0">
                            <div class="flex items-center space-x-2">
                                <input type="text" id="search_raw" placeholder="Cari bahan baku..." value="<?php echo htmlspecialchars($searchQueryRaw); ?>" class="shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <select id="bahan_limit" class="shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    <?php foreach ($rawMaterialsLimitOptions as $option): ?>
                                        <option value="<?php echo $option; ?>" <?php echo $rawMaterialsLimit == $option ? 'selected' : ''; ?>><?php echo $option; ?> per halaman</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <p class="text-sm text-gray-600">Total: <?php echo $totalRawMaterialsRows; ?> bahan</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                            <?php if (!empty($rawMaterials)): ?>
                                <?php foreach ($rawMaterials as $material): ?>
                                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($material['name']); ?></h3>
                                                <?php if (!empty($material['brand'])): ?>
                                                    <p class="text-xs text-gray-500">Merek: <?php echo htmlspecialchars($material['brand']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">Bahan</span>
                                        </div>
                                        <div class="text-sm text-gray-600 space-y-1">
                                            <p><strong>Rp. <?php echo number_format($material['purchase_price_per_unit'], 0, ',', '.'); ?></strong></p>
                                            <p>Vol. <?php echo number_format($material['default_package_quantity'], 0, ',', '.'); ?> <?php echo htmlspecialchars($material['unit']); ?></p>
                                            <p>Stok: <?php echo number_format($material['current_stock'], 0, ',', '.'); ?> bahan baku</p>
                                        </div>
                                        <div class="mt-3 flex space-x-2">
                                            <button onclick="editBahanBaku(<?php echo htmlspecialchars(json_encode($material)); ?>)" class="text-xs bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-1 rounded">Edit</button>
                                            <a href="../process/simpan_bahan_baku.php?action=delete&id=<?php echo $material['id']; ?>" class="text-xs bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded" onclick="return confirm('Hapus bahan ini?');">Hapus</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-span-full text-center py-8 text-gray-500">
                                    Belum ada bahan baku tercatat.
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Pagination Bahan Baku -->
                        <?php if ($totalRawMaterialsPages > 1): ?>
                            <div class="flex justify-center items-center space-x-2">
                                <?php if ($rawMaterialsPage > 1): ?>
                                    <a href="<?php echo buildPaginationUrl('/cornerbites-sia/pages/bahan_baku.php', ['bahan_page' => $rawMaterialsPage - 1]); ?>" class="px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300">‹ Prev</a>
                                <?php endif; ?>

                                <?php 
                                $startPage = max(1, $rawMaterialsPage - 2);
                                $endPage = min($totalRawMaterialsPages, $rawMaterialsPage + 2);
                                for ($i = $startPage; $i <= $endPage; $i++): 
                                ?>
                                    <a href="<?php echo buildPaginationUrl('/cornerbites-sia/pages/bahan_baku.php', ['bahan_page' => $i]); ?>" class="px-3 py-2 text-sm <?php echo $i == $rawMaterialsPage ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> rounded"><?php echo $i; ?></a>
                                <?php endfor; ?>

                                <?php if ($rawMaterialsPage < $totalRawMaterialsPages): ?>
                                    <a href="<?php echo buildPaginationUrl('/cornerbites-sia/pages/bahan_baku.php', ['bahan_page' => $rawMaterialsPage + 1]); ?>" class="px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Next ›</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Daftar Kemasan -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4"> Daftar Kemasan</h2>
                        <div class="mb-4 flex flex-col md:flex-row justify-between items-center space-y-2 md:space-y-0">
                            <div class="flex items-center space-x-2">
                                <input type="text" id="search_kemasan" placeholder="Cari kemasan..." value="<?php echo htmlspecialchars($searchQueryPackaging); ?>" class="shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <select id="kemasan_limit" class="shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    <?php foreach ($packagingMaterialsLimitOptions as $option): ?>
                                        <option value="<?php echo $option; ?>" <?php echo $packagingMaterialsLimit == $option ? 'selected' : ''; ?>><?php echo $option; ?> per halaman</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <p class="text-sm text-gray-600">Total: <?php echo $totalPackagingMaterialsRows; ?> kemasan</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                            <?php if (!empty($packagingMaterials)): ?>
                                <?php foreach ($packagingMaterials as $material): ?>
                                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($material['name']); ?></h3>
                                                <?php if (!empty($material['brand'])): ?>
                                                    <p class="text-xs text-gray-500">Merek: <?php echo htmlspecialchars($material['brand']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">Kemasan</span>
                                        </div>
                                        <div class="text-sm text-gray-600 space-y-1">
                                            <p><strong>Rp. <?php echo number_format($material['purchase_price_per_unit'], 0, ',', '.'); ?></strong></p>
                                            <p>Vol. <?php echo number_format($material['default_package_quantity'], 0, ',', '.'); ?> <?php echo htmlspecialchars($material['unit']); ?></p>
                                            <p>Stok: <?php echo number_format($material['current_stock'], 0, ',', '.'); ?> kemasan</p>
                                        </div>
                                        <div class="mt-3 flex space-x-2">
                                            <button onclick="editBahanBaku(<?php echo htmlspecialchars(json_encode($material)); ?>)" class="text-xs bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-1 rounded">Edit</button>
                                            <a href="../process/simpan_bahan_baku.php?action=delete&id=<?php echo $material['id']; ?>" class="text-xs bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded" onclick="return confirm('Hapus kemasan ini?');">Hapus</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-span-full text-center py-8 text-gray-500">
                                    Belum ada kemasan tercatat.
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Pagination Kemasan -->
                        <?php if ($totalPackagingMaterialsPages > 1): ?>
                            <div class="flex justify-center items-center space-x-2">
                                <?php if ($packagingMaterialsPage > 1): ?>
                                    <a href="<?php echo buildPaginationUrl('/cornerbites-sia/pages/bahan_baku.php', ['kemasan_page' => $packagingMaterialsPage - 1]); ?>" class="px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300">‹ Prev</a>
                                <?php endif; ?>

                                <?php 
                                $startPage = max(1, $packagingMaterialsPage - 2);
                                $endPage = min($totalPackagingMaterialsPages, $packagingMaterialsPage + 2);
                                for ($i = $startPage; $i <= $endPage; $i++): 
                                ?>
                                    <a href="<?php echo buildPaginationUrl('/cornerbites-sia/pages/bahan_baku.php', ['kemasan_page' => $i]); ?>" class="px-3 py-2 text-sm <?php echo $i == $packagingMaterialsPage ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> rounded"><?php echo $i; ?></a>
                                <?php endfor; ?>

                                <?php if ($packagingMaterialsPage < $totalPackagingMaterialsPages): ?>
                                    <a href="<?php echo buildPaginationUrl('/cornerbites-sia/pages/bahan_baku.php', ['kemasan_page' => $packagingMaterialsPage + 1]); ?>" class="px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Next ›</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- JavaScript untuk Edit Form dan Pencarian Real-time -->
<script>
    const unitOptions = <?php echo json_encode($unitOptions); ?>;
    const typeOptions = <?php echo json_encode($typeOptions); ?>;

    function editBahanBaku(material) {
        document.getElementById('bahan_baku_id').value = material.id;
        document.getElementById('name').value = material.name;
        document.getElementById('brand').value = material.brand || '';
        document.getElementById('type').value = material.type;
        document.getElementById('unit').value = material.unit;
        document.getElementById('purchase_size').value = material.default_package_quantity !== null ? material.default_package_quantity : '';
        document.getElementById('purchase_price_per_unit').value = material.purchase_price_per_unit;
        document.getElementById('current_stock').value = material.current_stock;

        document.getElementById('form-title').textContent = 'Edit Bahan/Kemasan';
        const submitButton = document.getElementById('submit_button');
        const cancelButton = document.getElementById('cancel_edit_button');

        submitButton.textContent = 'Update Bahan/Kemasan';
        submitButton.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        submitButton.classList.add('bg-indigo-600', 'hover:bg-indigo-700');
        cancelButton.classList.remove('hidden');

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function resetForm() {
        document.getElementById('bahan_baku_id').value = '';
        document.getElementById('name').value = '';
        document.getElementById('brand').value = '';
        document.getElementById('type').value = typeOptions[0];
        document.getElementById('unit').value = unitOptions[0];
        document.getElementById('purchase_size').value = '';
        document.getElementById('purchase_price_per_unit').value = ''; 
        document.getElementById('current_stock').value = '';

        document.getElementById('form-title').textContent = 'Tambah Bahan Baku/Kemasan Baru';
        const submitButton = document.getElementById('submit_button');
        const cancelButton = document.getElementById('cancel_edit_button');

        submitButton.textContent = 'Tambah Bahan/Kemasan';
        submitButton.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
        submitButton.classList.add('bg-blue-600', 'hover:bg-blue-700');
        cancelButton.classList.add('hidden');
    }

    // Real-time search dengan debouncing
    let searchTimeoutRaw;
    let searchTimeoutKemasan;
    let limitTimeoutRaw;
    let limitTimeoutKemasan;

    function applySearchRealtime(type, searchTerm, limit = null) {
        let currentUrl = new URL(window.location.href);

        if (type === 'raw') {
            currentUrl.searchParams.set('search_raw', searchTerm);
            currentUrl.searchParams.set('bahan_page', '1');
            if (limit !== null) {
                currentUrl.searchParams.set('bahan_limit', limit);
            }
        } else {
            currentUrl.searchParams.set('search_kemasan', searchTerm);
            currentUrl.searchParams.set('kemasan_page', '1');
            if (limit !== null) {
                currentUrl.searchParams.set('kemasan_limit', limit);
            }
        }

        window.location.href = currentUrl.toString();
    }

    // Real-time search untuk bahan baku
    document.getElementById('search_raw').addEventListener('input', function() {
        const searchTerm = this.value;
        clearTimeout(searchTimeoutRaw);
        searchTimeoutRaw = setTimeout(() => {
            applySearchRealtime('raw', searchTerm);
        }, 500); // Delay 500ms
    });

    // Real-time search untuk kemasan
    document.getElementById('search_kemasan').addEventListener('input', function() {
        const searchTerm = this.value;
        clearTimeout(searchTimeoutKemasan);
        searchTimeoutKemasan = setTimeout(() => {
            applySearchRealtime('kemasan', searchTerm);
        }, 500); // Delay 500ms
    });

    // Real-time limit change untuk bahan baku
    document.getElementById('bahan_limit').addEventListener('change', function() {
        const limit = this.value;
        const searchTerm = document.getElementById('search_raw').value;
        clearTimeout(limitTimeoutRaw);
        limitTimeoutRaw = setTimeout(() => {
            applySearchRealtime('raw', searchTerm, limit);
        }, 100);
    });

    // Real-time limit change untuk kemasan
    document.getElementById('kemasan_limit').addEventListener('change', function() {
        const limit = this.value;
        const searchTerm = document.getElementById('search_kemasan').value;
        clearTimeout(limitTimeoutKemasan);
        limitTimeoutKemasan = setTimeout(() => {
            applySearchRealtime('kemasan', searchTerm, limit);
        }, 100);
    });

    // Enter key support untuk search
    document.getElementById('search_raw').addEventListener('keyup', function(event) {
        if (event.key === 'Enter') {
            clearTimeout(searchTimeoutRaw);
            applySearchRealtime('raw', this.value);
        }
    });

    document.getElementById('search_kemasan').addEventListener('keyup', function(event) {
        if (event.key === 'Enter') {
            clearTimeout(searchTimeoutKemasan);
            applySearchRealtime('kemasan', this.value);
        }
    });
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>