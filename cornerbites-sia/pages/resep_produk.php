<?php
// pages/resep_produk.php
// Halaman untuk mengelola resep produk (komposisi bahan baku/kemasan untuk setiap produk jadi)

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

// Satuan yang umum digunakan dalam resep
$recipeUnitOptions = ['gram', 'kg', 'ml', 'liter', 'pcs', 'buah', 'sendok teh', 'sendok makan', 'cangkir'];

try {
    $conn = $db;

    // Ambil daftar produk untuk dropdown
    $stmtProducts = $conn->query("SELECT id, name FROM products ORDER BY name ASC");
    $products = $stmtProducts->fetchAll(PDO::FETCH_ASSOC);

    // Ambil semua bahan baku dan kemasan untuk dropdown resep
    $stmtRawMaterials = $conn->query("SELECT id, name, unit, type FROM raw_materials ORDER BY name ASC");
    $rawMaterialsAndPackaging = $stmtRawMaterials->fetchAll(PDO::FETCH_ASSOC);

    // Jika ada produk yang dipilih, ambil resepnya
    if ($selectedProductId) {
        $stmtRecipes = $conn->prepare("
            SELECT pr.id, pr.product_id, pr.raw_material_id, pr.quantity_used, pr.unit_measurement,
                   rm.name AS raw_material_name, rm.unit AS raw_material_stock_unit, rm.purchase_price_per_unit, rm.default_package_quantity, rm.type AS raw_material_type
            FROM product_recipes pr
            JOIN raw_materials rm ON pr.raw_material_id = rm.id
            WHERE pr.product_id = :product_id
            ORDER BY rm.name ASC
        ");
        $stmtRecipes->bindParam(':product_id', $selectedProductId, PDO::PARAM_INT);
        $stmtRecipes->execute();
        $productRecipes = $stmtRecipes->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log("Error di halaman Resep Produk: " . $e->getMessage());
    $message = "Terjadi kesalahan saat memuat data resep atau produk/bahan baku.";
    $message_type = "error";
}

?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>
<div class="flex h-screen bg-gray-100 font-sans">
    <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col">
        <main class="flex-1 p-8 overflow-y-auto">
            <div class="container mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Manajemen Resep Produk</h1>

                <!-- Pesan Notifikasi -->
                <?php if (!empty($message)): ?>
                    <div class="p-3 mb-4 text-sm rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Bagian Pilih Produk -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Pilih Produk</h2>
                    <div class="mb-4">
                        <label for="product_select" class="block text-gray-700 text-sm font-bold mb-2">Pilih Produk untuk Dikelola Resepnya:</label>
                        <select id="product_select" onchange="if(this.value) window.location.href = 'resep_produk.php?product_id=' + this.value;" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="">-- Pilih Produk --</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo htmlspecialchars($product['id']); ?>" <?php echo $selectedProductId == $product['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <?php if ($selectedProductId): ?>
                    <!-- Form Tambah/Edit Item Resep -->
                    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4" id="form-resep-title">Tambah Item ke Resep</h2>
                        <form action="../process/simpan_resep_produk.php" method="POST">
                            <input type="hidden" name="recipe_item_id" id="recipe_item_id">
                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($selectedProductId); ?>">

                            <div class="mb-4">
                                <label for="raw_material_id" class="block text-gray-700 text-sm font-bold mb-2">Bahan Baku/Kemasan:</label>
                                <select id="raw_material_id" name="raw_material_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                    <option value="">-- Pilih Bahan Baku/Kemasan --</option>
                                    <?php foreach ($rawMaterialsAndPackaging as $item): ?>
                                        <option value="<?php echo htmlspecialchars($item['id']); ?>" data-unit="<?php echo htmlspecialchars($item['unit']); ?>" data-type="<?php echo htmlspecialchars($item['type']); ?>">
                                            <?php echo htmlspecialchars($item['name']); ?> (<?php echo htmlspecialchars(ucfirst($item['type'])); ?> - <?php echo htmlspecialchars($item['unit']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="quantity_used" class="block text-gray-700 text-sm font-bold mb-2">Jumlah Digunakan dalam Resep:</label>
                                <input type="number" step="0.001" id="quantity_used" name="quantity_used" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" min="0" required>
                            </div>
                            <div class="mb-6">
                                <label for="unit_measurement" class="block text-gray-700 text-sm font-bold mb-2">Satuan Pengukuran Resep:</label>
                                <select id="unit_measurement" name="unit_measurement" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                    <?php foreach ($recipeUnitOptions as $unitOption): ?>
                                        <option value="<?php echo htmlspecialchars($unitOption); ?>"><?php echo htmlspecialchars($unitOption); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Ini adalah satuan yang akan digunakan dalam resep Anda (contoh: gram, ml). Pastikan konsisten dengan input 'Jumlah Digunakan'.</p>
                            </div>
                            <div class="flex items-center justify-between">
                                <button type="submit" id="submit_resep_button" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                    Tambah Item Resep
                                </button>
                                <button type="button" id="cancel_edit_resep_button" class="hidden bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline ml-4" onclick="resetResepForm()">
                                    Batal Edit
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Bagian Daftar Resep Produk -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4">Resep Produk: <?php echo htmlspecialchars($products[array_search($selectedProductId, array_column($products, 'id'))]['name'] ?? 'Tidak Ditemukan'); ?></h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Item</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Komposisi (Jumlah & Satuan Resep)</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Beli/Unit Stok</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Satuan Stok Item</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Volume Default Paket</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (!empty($productRecipes)): ?>
                                        <?php foreach ($productRecipes as $item): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['raw_material_name']); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars(ucfirst($item['raw_material_type'])); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo number_format($item['quantity_used'], 3, ',', '.'); ?> <?php echo htmlspecialchars($item['unit_measurement']); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Rp <?php echo number_format($item['purchase_price_per_unit'], 0, ',', '.'); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($item['raw_material_stock_unit']); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo $item['default_package_quantity'] !== null ? number_format($item['default_package_quantity'], 3, ',', '.') . ' ' . htmlspecialchars($item['raw_material_stock_unit']) : '-'; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <button onclick="editResepItem(<?php echo htmlspecialchars(json_encode($item)); ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                                    <a href="../process/simpan_resep_produk.php?action=delete&id=<?php echo $item['id']; ?>&product_id=<?php echo htmlspecialchars($selectedProductId); ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Apakah Anda yakin ingin menghapus item ini dari resep?');">Hapus</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Belum ada item dalam resep produk ini.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
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

<!-- JavaScript untuk Edit Form Resep -->
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

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function resetResepForm() {
        document.getElementById('recipe_item_id').value = '';
        document.getElementById('raw_material_id').value = ''; // Clear selected material
        document.getElementById('quantity_used').value = '';
        document.getElementById('unit_measurement').value = recipeUnitOptions[0]; // Reset ke pilihan pertama (gram)

        document.getElementById('form-resep-title').textContent = 'Tambah Item ke Resep';
        const submitButton = document.getElementById('submit_resep_button');
        const cancelButton = document.getElementById('cancel_edit_resep_button');
        
        submitButton.textContent = 'Tambah Item Resep';
        submitButton.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
        submitButton.classList.add('bg-blue-600', 'hover:bg-blue-700');
        cancelButton.classList.add('hidden');
    }
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
