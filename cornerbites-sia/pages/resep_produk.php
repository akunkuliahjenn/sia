
<?php
// pages/resep_produk.php
// Halaman untuk mengelola resep produk (komposisi bahan baku/kemasan untuk setiap produk jadi) dengan kalkulasi HPP

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$message = '';
$message_type = '';
if (isset($_SESSION['resep_message'])) {
    $message = $_SESSION['resep_message']['text'];
    $message_type = $_SESSION['resep_message']['type'];
    unset($_SESSION['resep_message']);
}

$products = [];
$rawMaterialsAndPackaging = [];
$productRecipes = [];
$selectedProductId = $_GET['product_id'] ?? null;
$selectedProduct = null;
$hppCalculation = null;

// Satuan yang umum digunakan dalam resep
$recipeUnitOptions = ['gram', 'kg', 'ml', 'liter', 'pcs', 'buah', 'sendok teh', 'sendok makan', 'cangkir'];

// Inisialisasi variabel pencarian dan pagination untuk resep
$searchQueryRecipe = $_GET['search_recipe'] ?? '';
$totalRecipesRows = 0;
$totalRecipesPages = 1;
$recipesLimitOptions = [6, 12, 18, 24];
$recipesLimit = isset($_GET['recipe_limit']) && in_array((int)$_GET['recipe_limit'], $recipesLimitOptions) ? (int)$_GET['recipe_limit'] : 6;
$recipesPage = isset($_GET['recipe_page']) ? max((int)$_GET['recipe_page'], 1) : 1;
$recipesOffset = ($recipesPage - 1) * $recipesLimit;

try {
    $conn = $db;

    // Ambil daftar produk untuk dropdown
    $stmtProducts = $conn->query("SELECT id, name, cost_price, sale_price, production_yield FROM products ORDER BY name ASC");
    $products = $stmtProducts->fetchAll(PDO::FETCH_ASSOC);

    // Ambil semua bahan baku dan kemasan untuk dropdown resep
    $stmtRawMaterials = $conn->query("SELECT id, name, unit, type, brand FROM raw_materials ORDER BY name ASC");
    $rawMaterialsAndPackaging = $stmtRawMaterials->fetchAll(PDO::FETCH_ASSOC);

    // Jika ada produk yang dipilih
    if ($selectedProductId) {
        // Ambil detail produk yang dipilih
        $stmtSelectedProduct = $conn->prepare("SELECT id, name, cost_price, sale_price, production_yield FROM products WHERE id = ?");
        $stmtSelectedProduct->execute([$selectedProductId]);
        $selectedProduct = $stmtSelectedProduct->fetch(PDO::FETCH_ASSOC);

        // Hitung total baris untuk resep dengan filter pencarian
        $queryTotalRecipe = "
            SELECT COUNT(*) 
            FROM product_recipes pr
            JOIN raw_materials rm ON pr.raw_material_id = rm.id
            WHERE pr.product_id = :product_id
        ";
        if (!empty($searchQueryRecipe)) {
            $queryTotalRecipe .= " AND rm.name LIKE :search_recipe_term";
        }
        
        $stmtTotalRecipe = $conn->prepare($queryTotalRecipe);
        $stmtTotalRecipe->bindValue(':product_id', $selectedProductId, PDO::PARAM_INT);
        if (!empty($searchQueryRecipe)) {
            $stmtTotalRecipe->bindValue(':search_recipe_term', '%' . $searchQueryRecipe . '%', PDO::PARAM_STR);
        }
        $stmtTotalRecipe->execute();
        $totalRecipesRows = $stmtTotalRecipe->fetchColumn();
        $totalRecipesPages = ceil($totalRecipesRows / $recipesLimit);

        // Pastikan halaman tidak melebihi total halaman yang ada
        if ($recipesPage > $totalRecipesPages && $totalRecipesPages > 0) {
            $recipesPage = $totalRecipesPages;
            $recipesOffset = ($recipesPage - 1) * $recipesLimit;
        }

        // Query untuk mengambil resep dengan LIMIT, OFFSET, dan filter pencarian
        $queryRecipes = "
            SELECT pr.id, pr.product_id, pr.raw_material_id, pr.quantity_used, pr.unit_measurement,
                   rm.name AS raw_material_name, rm.unit AS raw_material_stock_unit, rm.purchase_price_per_unit, 
                   rm.default_package_quantity, rm.type AS raw_material_type, rm.brand AS raw_material_brand
            FROM product_recipes pr
            JOIN raw_materials rm ON pr.raw_material_id = rm.id
            WHERE pr.product_id = :product_id
        ";
        if (!empty($searchQueryRecipe)) {
            $queryRecipes .= " AND rm.name LIKE :search_recipe_term";
        }
        $queryRecipes .= " ORDER BY rm.name ASC LIMIT :limit OFFSET :offset";

        $stmtRecipes = $conn->prepare($queryRecipes);
        $stmtRecipes->bindValue(':product_id', $selectedProductId, PDO::PARAM_INT);
        if (!empty($searchQueryRecipe)) {
            $stmtRecipes->bindValue(':search_recipe_term', '%' . $searchQueryRecipe . '%', PDO::PARAM_STR);
        }
        $stmtRecipes->bindParam(':limit', $recipesLimit, PDO::PARAM_INT);
        $stmtRecipes->bindParam(':offset', $recipesOffset, PDO::PARAM_INT);
        $stmtRecipes->execute();
        $productRecipes = $stmtRecipes->fetchAll(PDO::FETCH_ASSOC);

        // Hitung HPP berdasarkan resep
        if ($selectedProduct && !empty($productRecipes)) {
            $totalCostPerBatch = 0;
            $recipeDetails = [];

            // Ambil semua item resep untuk perhitungan HPP
            $stmtAllRecipes = $conn->prepare("
                SELECT pr.quantity_used, pr.unit_measurement,
                       rm.name AS raw_material_name, rm.purchase_price_per_unit, 
                       rm.default_package_quantity, rm.unit AS raw_material_stock_unit, rm.type
                FROM product_recipes pr
                JOIN raw_materials rm ON pr.raw_material_id = rm.id
                WHERE pr.product_id = ?
                ORDER BY rm.name ASC
            ");
            $stmtAllRecipes->execute([$selectedProductId]);
            $allRecipeItems = $stmtAllRecipes->fetchAll(PDO::FETCH_ASSOC);

            foreach ($allRecipeItems as $item) {
                $costPerItem = 0;
                
                // Hitung biaya berdasarkan harga beli per unit dan quantity used
                if ($item['default_package_quantity'] && $item['default_package_quantity'] > 0) {
                    // Jika ada default package quantity, hitung proporsi
                    $costPerUnit = $item['purchase_price_per_unit'] / $item['default_package_quantity'];
                    $costPerItem = $costPerUnit * $item['quantity_used'];
                } else {
                    // Jika tidak ada default package, gunakan harga langsung
                    $costPerItem = ($item['purchase_price_per_unit'] / 1) * $item['quantity_used'];
                }

                $totalCostPerBatch += $costPerItem;
                
                $recipeDetails[] = [
                    'name' => $item['raw_material_name'],
                    'type' => $item['type'],
                    'quantity_used' => $item['quantity_used'],
                    'unit_measurement' => $item['unit_measurement'],
                    'cost_per_item' => $costPerItem
                ];
            }

            // Hitung HPP per unit berdasarkan production yield
            $productionYield = $selectedProduct['production_yield'] ?? 1;
            $hppPerUnit = $productionYield > 0 ? $totalCostPerBatch / $productionYield : 0;
            
            // Hitung profit margin
            $salePrice = $selectedProduct['sale_price'] ?? 0;
            $profitPerUnit = $salePrice - $hppPerUnit;
            $profitMarginPercent = $salePrice > 0 ? ($profitPerUnit / $salePrice) * 100 : 0;

            $hppCalculation = [
                'total_cost_per_batch' => $totalCostPerBatch,
                'production_yield' => $productionYield,
                'hpp_per_unit' => $hppPerUnit,
                'sale_price' => $salePrice,
                'profit_per_unit' => $profitPerUnit,
                'profit_margin_percent' => $profitMarginPercent,
                'recipe_details' => $recipeDetails
            ];
        }
    }

} catch (PDOException $e) {
    error_log("Error di halaman Resep Produk: " . $e->getMessage());
    $message = "Terjadi kesalahan saat memuat data resep atau produk/bahan baku.";
    $message_type = "error";
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
        <main class="flex-1 p-4 md:p-8 overflow-y-auto">
            <div class="container mx-auto max-w-7xl">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-4 md:mb-6">Manajemen Resep & HPP Produk</h1>

                <!-- Pesan Notifikasi -->
                <?php if (!empty($message)): ?>
                    <div class="p-3 mb-4 text-sm rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Bagian Pilih Produk -->
                <div class="bg-white rounded-lg shadow-md p-4 md:p-6 mb-6 md:mb-8">
                    <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-4">Pilih Produk</h2>
                    <div class="mb-4">
                        <label for="product_select" class="block text-gray-700 text-sm font-bold mb-2">Pilih Produk untuk Dikelola Resepnya:</label>
                        <select id="product_select" onchange="if(this.value) window.location.href = 'resep_produk.php?product_id=' + this.value;" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="">-- Pilih Produk --</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo htmlspecialchars($product['id']); ?>" <?php echo $selectedProductId == $product['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <?php if ($selectedProductId && $selectedProduct): ?>
                    <!-- Bagian Kalkulasi HPP -->
                    <div class="bg-white rounded-lg shadow-md p-4 md:p-6 mb-6 md:mb-8">
                        <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-4">📊 Kalkulasi HPP & Profit</h2>
                        
                        <!-- Form Update Produk Info -->
                        <form action="../process/simpan_resep_produk.php" method="POST" class="mb-6">
                            <input type="hidden" name="action" value="update_product_info">
                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($selectedProductId); ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <label for="production_yield" class="block text-gray-700 text-sm font-bold mb-2">Hasil Produksi (Unit):</label>
                                    <input type="number" step="1" id="production_yield" name="production_yield" value="<?php echo htmlspecialchars($selectedProduct['production_yield'] ?? 1); ?>" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" min="1" required>
                                    <p class="text-xs text-gray-500 mt-1">Berapa unit produk yang dihasilkan dari 1 batch resep</p>
                                </div>
                                
                                <div>
                                    <label for="sale_price" class="block text-gray-700 text-sm font-bold mb-2">Harga Jual per Unit (Rp):</label>
                                    <input type="number" step="1" id="sale_price" name="sale_price" value="<?php echo htmlspecialchars($selectedProduct['sale_price'] ?? 0); ?>" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" min="0" required>
                                </div>
                                
                                <div class="flex items-end">
                                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded focus:outline-none focus:shadow-outline w-full">
                                        Update Info Produk
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Hasil Kalkulasi -->
                        <?php if ($hppCalculation): ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <h3 class="text-sm font-bold text-blue-800 mb-2">Biaya Bahan per Batch</h3>
                                    <p class="text-xl font-bold text-blue-900">Rp <?php echo number_format($hppCalculation['total_cost_per_batch'], 0, ',', '.'); ?></p>
                                </div>
                                
                                <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                                    <h3 class="text-sm font-bold text-indigo-800 mb-2">HPP per Unit</h3>
                                    <p class="text-xl font-bold text-indigo-900">Rp <?php echo number_format($hppCalculation['hpp_per_unit'], 0, ',', '.'); ?></p>
                                </div>
                                
                                <div class="bg-<?php echo $hppCalculation['profit_per_unit'] >= 0 ? 'green' : 'red'; ?>-50 border border-<?php echo $hppCalculation['profit_per_unit'] >= 0 ? 'green' : 'red'; ?>-200 rounded-lg p-4">
                                    <h3 class="text-sm font-bold text-<?php echo $hppCalculation['profit_per_unit'] >= 0 ? 'green' : 'red'; ?>-800 mb-2">Profit per Unit</h3>
                                    <p class="text-xl font-bold text-<?php echo $hppCalculation['profit_per_unit'] >= 0 ? 'green' : 'red'; ?>-900">Rp <?php echo number_format($hppCalculation['profit_per_unit'], 0, ',', '.'); ?></p>
                                </div>
                                
                                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                                    <h3 class="text-sm font-bold text-purple-800 mb-2">Margin Profit</h3>
                                    <p class="text-xl font-bold text-purple-900"><?php echo number_format($hppCalculation['profit_margin_percent'], 1); ?>%</p>
                                </div>
                            </div>

                            <!-- Detail Breakdown -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="font-bold text-gray-800 mb-3">Detail Breakdown Biaya:</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <?php foreach ($hppCalculation['recipe_details'] as $detail): ?>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-700">
                                                <?php echo htmlspecialchars($detail['name']); ?> 
                                                <span class="text-xs text-gray-500">(<?php echo number_format($detail['quantity_used'], 2); ?> <?php echo htmlspecialchars($detail['unit_measurement']); ?>)</span>
                                            </span>
                                            <span class="font-medium text-gray-900">Rp <?php echo number_format($detail['cost_per_item'], 0, ',', '.'); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                                <p class="text-yellow-800">Tambahkan item resep terlebih dahulu untuk melihat kalkulasi HPP</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Form Tambah/Edit Item Resep -->
                    <div class="bg-white rounded-lg shadow-md p-4 md:p-6 mb-6 md:mb-8">
                        <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-4" id="form-resep-title">Tambah Item ke Resep</h2>
                        <form action="../process/simpan_resep_produk.php" method="POST">
                            <input type="hidden" name="recipe_item_id" id="recipe_item_id">
                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($selectedProductId); ?>">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                                <div>
                                    <label for="raw_material_id" class="block text-gray-700 text-sm font-bold mb-2">Bahan Baku/Kemasan:</label>
                                    <select id="raw_material_id" name="raw_material_id" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                        <option value="">-- Pilih Bahan Baku/Kemasan --</option>
                                        <?php foreach ($rawMaterialsAndPackaging as $item): ?>
                                            <option value="<?php echo htmlspecialchars($item['id']); ?>" data-unit="<?php echo htmlspecialchars($item['unit']); ?>" data-type="<?php echo htmlspecialchars($item['type']); ?>">
                                                <?php echo htmlspecialchars($item['name']); ?><?php echo $item['brand'] ? ' - ' . htmlspecialchars($item['brand']) : ''; ?> (<?php echo htmlspecialchars(ucfirst($item['type'])); ?> - <?php echo htmlspecialchars($item['unit']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label for="quantity_used" class="block text-gray-700 text-sm font-bold mb-2">Jumlah Digunakan dalam Resep:</label>
                                    <input type="number" step="0.001" id="quantity_used" name="quantity_used" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" min="0" placeholder="Contoh: 250, 1.5" required>
                                </div>

                                <div class="md:col-span-2">
                                    <label for="unit_measurement" class="block text-gray-700 text-sm font-bold mb-2">Satuan Pengukuran Resep:</label>
                                    <select id="unit_measurement" name="unit_measurement" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                        <?php foreach ($recipeUnitOptions as $unitOption): ?>
                                            <option value="<?php echo htmlspecialchars($unitOption); ?>"><?php echo htmlspecialchars($unitOption); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">Ini adalah satuan yang akan digunakan dalam resep Anda (contoh: gram, ml). Pastikan konsisten dengan input 'Jumlah Digunakan'.</p>
                                </div>
                            </div>

                            <div class="flex flex-col md:flex-row md:items-center md:justify-between mt-6 gap-4">
                                <button type="submit" id="submit_resep_button" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded focus:outline-none focus:shadow-outline">
                                    Tambah Item Resep
                                </button>
                                <button type="button" id="cancel_edit_resep_button" class="hidden bg-gray-500 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded focus:outline-none focus:shadow-outline" onclick="resetResepForm()">
                                    Batal Edit
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Bagian Daftar Resep Produk -->
                    <div class="bg-white rounded-lg shadow-md p-4 md:p-6">
                        <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-4">📋 Daftar Resep: <?php echo htmlspecialchars($selectedProduct['name']); ?></h2>
                        
                        <div class="mb-4 flex flex-col md:flex-row justify-between items-start md:items-center space-y-2 md:space-y-0 gap-2">
                            <div class="flex flex-col md:flex-row items-start md:items-center space-y-2 md:space-y-0 md:space-x-2 w-full md:w-auto">
                                <input type="text" id="search_recipe" placeholder="Cari bahan dalam resep..." value="<?php echo htmlspecialchars($searchQueryRecipe); ?>" class="shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline w-full md:w-auto">
                                <select id="recipe_limit" class="shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline w-full md:w-auto">
                                    <?php foreach ($recipesLimitOptions as $option): ?>
                                        <option value="<?php echo $option; ?>" <?php echo $recipesLimit == $option ? 'selected' : ''; ?>><?php echo $option; ?> per halaman</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <p class="text-sm text-gray-600">Total: <?php echo $totalRecipesRows; ?> item</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                            <?php if (!empty($productRecipes)): ?>
                                <?php foreach ($productRecipes as $item): ?>
                                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <h3 class="font-bold text-gray-800 text-sm md:text-base"><?php echo htmlspecialchars($item['raw_material_name']); ?></h3>
                                                <?php if ($item['raw_material_brand']): ?>
                                                    <p class="text-xs text-gray-500">Merek: <?php echo htmlspecialchars($item['raw_material_brand']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <span class="<?php echo $item['raw_material_type'] === 'bahan' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?> text-xs px-2 py-1 rounded flex-shrink-0">
                                                <?php echo htmlspecialchars(ucfirst($item['raw_material_type'])); ?>
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-600 space-y-1">
                                            <p><strong>Komposisi:</strong> <?php echo number_format($item['quantity_used'], 3, ',', '.'); ?> <?php echo htmlspecialchars($item['unit_measurement']); ?></p>
                                            <p><strong>Harga Beli:</strong> Rp <?php echo number_format($item['purchase_price_per_unit'], 0, ',', '.'); ?></p>
                                            <p><strong>Satuan Stok:</strong> <?php echo htmlspecialchars($item['raw_material_stock_unit']); ?></p>
                                            <?php if ($item['default_package_quantity'] !== null): ?>
                                                <p><strong>Volume Paket:</strong> <?php echo number_format($item['default_package_quantity'], 3, ',', '.'); ?> <?php echo htmlspecialchars($item['raw_material_stock_unit']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mt-3 flex flex-col md:flex-row space-y-1 md:space-y-0 md:space-x-2">
                                            <button onclick="editResepItem(<?php echo htmlspecialchars(json_encode($item)); ?>)" class="text-xs bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-1 rounded">Edit</button>
                                            <a href="../process/simpan_resep_produk.php?action=delete&id=<?php echo $item['id']; ?>&product_id=<?php echo htmlspecialchars($selectedProductId); ?>" class="text-xs bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-center" onclick="return confirm('Hapus item ini dari resep?');">Hapus</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-span-full text-center py-8 text-gray-500">
                                    Belum ada item dalam resep produk ini.
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Pagination Resep -->
                        <?php if ($totalRecipesPages > 1): ?>
                            <div class="flex flex-wrap justify-center items-center space-x-1 space-y-1">
                                <?php if ($recipesPage > 1): ?>
                                    <a href="<?php echo buildPaginationUrl('/cornerbites-sia/pages/resep_produk.php', ['recipe_page' => $recipesPage - 1]); ?>" class="px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300">‹ Prev</a>
                                <?php endif; ?>
                                
                                <?php 
                                $startPage = max(1, $recipesPage - 2);
                                $endPage = min($totalRecipesPages, $recipesPage + 2);
                                for ($i = $startPage; $i <= $endPage; $i++): 
                                ?>
                                    <a href="<?php echo buildPaginationUrl('/cornerbites-sia/pages/resep_produk.php', ['recipe_page' => $i]); ?>" class="px-3 py-2 text-sm <?php echo $i == $recipesPage ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> rounded"><?php echo $i; ?></a>
                                <?php endfor; ?>
                                
                                <?php if ($recipesPage < $totalRecipesPages): ?>
                                    <a href="<?php echo buildPaginationUrl('/cornerbites-sia/pages/resep_produk.php', ['recipe_page' => $recipesPage + 1]); ?>" class="px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Next ›</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-lg shadow-md p-6 text-center text-gray-600">
                        <p>Silakan pilih produk dari daftar di atas untuk mulai mengelola resepnya.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- JavaScript untuk Edit Form Resep dan Real-time Search -->
<script>
    // Definisikan recipeUnitOptions di JavaScript untuk reset form yang benar
    const recipeUnitOptions = <?php echo json_encode($recipeUnitOptions); ?>;

    function editResepItem(item) {
        document.getElementById('recipe_item_id').value = item.id;
        document.getElementById('raw_material_id').value = item.raw_material_id;
        document.getElementById('quantity_used').value = item.quantity_used;
        document.getElementById('unit_measurement').value = item.unit_measurement;

        document.getElementById('form-resep-title').textContent = 'Edit Item Resep';
        const submitButton = document.getElementById('submit_resep_button');
        const cancelButton = document.getElementById('cancel_edit_resep_button');
        
        submitButton.textContent = 'Update Item Resep';
        submitButton.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        submitButton.classList.add('bg-indigo-600', 'hover:bg-indigo-700');
        cancelButton.classList.remove('hidden');

        // Smooth scroll to form
        document.getElementById('form-resep-title').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function resetResepForm() {
        document.getElementById('recipe_item_id').value = '';
        document.getElementById('raw_material_id').value = '';
        document.getElementById('quantity_used').value = '';
        document.getElementById('unit_measurement').value = recipeUnitOptions[0];

        document.getElementById('form-resep-title').textContent = 'Tambah Item ke Resep';
        const submitButton = document.getElementById('submit_resep_button');
        const cancelButton = document.getElementById('cancel_edit_resep_button');
        
        submitButton.textContent = 'Tambah Item Resep';
        submitButton.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
        submitButton.classList.add('bg-blue-600', 'hover:bg-blue-700');
        cancelButton.classList.add('hidden');
    }

    // Real-time search dengan debouncing
    let searchTimeoutRecipe;
    let limitTimeoutRecipe;

    function applySearchRealtimeRecipe(searchTerm, limit = null) {
        let currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('search_recipe', searchTerm);
        currentUrl.searchParams.set('recipe_page', '1');
        if (limit !== null) {
            currentUrl.searchParams.set('recipe_limit', limit);
        }
        
        // Preserve scroll position by storing current position
        const scrollPosition = window.pageYOffset;
        sessionStorage.setItem('scrollPosition', scrollPosition);
        
        window.location.href = currentUrl.toString();
    }

    // Real-time search untuk resep
    document.getElementById('search_recipe').addEventListener('input', function() {
        const searchTerm = this.value;
        clearTimeout(searchTimeoutRecipe);
        searchTimeoutRecipe = setTimeout(() => {
            applySearchRealtimeRecipe(searchTerm);
        }, 500);
    });

    // Real-time limit change untuk resep
    document.getElementById('recipe_limit').addEventListener('change', function() {
        const limit = this.value;
        const searchTerm = document.getElementById('search_recipe').value;
        clearTimeout(limitTimeoutRecipe);
        limitTimeoutRecipe = setTimeout(() => {
            applySearchRealtimeRecipe(searchTerm, limit);
        }, 100);
    });

    // Enter key support untuk search
    document.getElementById('search_recipe').addEventListener('keyup', function(event) {
        if (event.key === 'Enter') {
            clearTimeout(searchTimeoutRecipe);
            applySearchRealtimeRecipe(this.value);
        }
    });

    // Restore scroll position after page load
    window.addEventListener('load', function() {
        const scrollPosition = sessionStorage.getItem('scrollPosition');
        if (scrollPosition) {
            window.scrollTo(0, parseInt(scrollPosition));
            sessionStorage.removeItem('scrollPosition');
        }
    });
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
