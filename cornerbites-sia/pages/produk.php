
<?php
// pages/produk.php
// Halaman untuk manajemen data produk (daftar, tambah, edit).

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php'; // Sertakan file koneksi database

$products = [];
try {
    $conn = $db;
    
    // Pagination setup
    $limit_options = [10, 25, 50, 100];
    $limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $limit_options) ? (int)$_GET['limit'] : 10;
    $page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
    $offset = ($page - 1) * $limit;

    // Hitung total produk
    $countStmt = $conn->query("SELECT COUNT(*) FROM products");
    $totalProducts = $countStmt->fetchColumn();
    $totalPages = ceil($totalProducts / $limit);

    // Mengambil semua kolom yang relevan dari tabel products dengan pagination
    $stmt = $conn->prepare("SELECT id, name, unit, cost_price, sale_price, stock FROM products ORDER BY name ASC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error di halaman Produk: " . $e->getMessage());
}

// Pesan sukses atau error setelah proses simpan
$message = '';
$message_type = ''; // 'success' or 'error'
if (isset($_SESSION['product_message'])) {
    $message = $_SESSION['product_message']['text'];
    $message_type = $_SESSION['product_message']['type'];
    unset($_SESSION['product_message']);
}
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>
<div class="flex h-screen bg-gray-100 font-sans">
    <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="flex items-center justify-between h-16 bg-white border-b border-gray-200 px-6 shadow-sm">
            <h1 class="text-xl font-semibold text-gray-800">Manajemen Produk</h1>
        </header>
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-200 p-6">
            <div class="container mx-auto">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Manajemen Produk</h2>

                <?php if ($message): ?>
                    <div class="mb-4 p-4 rounded-md <?php echo ($message_type == 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'); ?>" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Form Tambah Produk Baru -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">Tambah / Edit Produk</h3>
                    <p class="text-sm text-gray-600 mb-4">Isi detail produk baru Anda atau gunakan form ini untuk mengedit produk yang sudah ada.</p>
                    <form action="/cornerbites-sia/process/simpan_produk.php" method="POST">
                        <input type="hidden" name="product_id" id="product_id_to_edit" value="">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="product_name" class="block text-gray-700 text-sm font-semibold mb-2">Nama Produk:</label>
                                <input type="text" id="product_name" name="name" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2.5" placeholder="Contoh: Kopi Latte, Donat Cokelat" required>
                            </div>
                            <div>
                                <label for="unit" class="block text-gray-700 text-sm font-semibold mb-2">Satuan (misal: pcs, kg, liter):</label>
                                <input type="text" id="unit" name="unit" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2.5" placeholder="Contoh: pcs, porsi, bungkus" required>
                            </div>
                            <div>
                                <label for="stock" class="block text-gray-700 text-sm font-semibold mb-2">Stok Awal/Saat Ini:</label>
                                <input type="number" id="stock" name="stock" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2.5" placeholder="Contoh: 100" min="0" required>
                            </div>
                            <div>
                                <label for="cost_price" class="block text-gray-700 text-sm font-semibold mb-2">Harga Beli (Rp):</label>
                                <input type="number" id="cost_price" name="cost_price" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2.5" step="any" placeholder="Contoh: 15000" min="0" required>
                            </div>
                            <div>
                                <label for="sale_price" class="block text-gray-700 text-sm font-semibold mb-2">Harga Jual (Rp):</label>
                                <input type="number" id="sale_price" name="sale_price" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2.5" step="any" placeholder="Contoh: 25000" min="0" required>
                            </div>
                        </div>

                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors duration-200" id="submit_button">
                            Tambah Produk
                        </button>
                        <button type="button" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors duration-200 ml-2 hidden" id="cancel_edit_button" onclick="resetForm()">
                            Batal Edit
                        </button>
                    </form>
                </div>

                <!-- Daftar Produk -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">Daftar Produk Anda (<?php echo number_format($totalProducts); ?> produk)</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 rounded-lg overflow-hidden">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Produk</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Satuan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok Saat Ini</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Beli (Rp)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Jual (Rp)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (!empty($products)): ?>
                                    <?php foreach ($products as $product): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($product['unit']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($product['stock']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Rp <?php echo number_format($product['cost_price'], 0, ',', '.'); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Rp <?php echo number_format($product['sale_price'], 0, ',', '.'); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)" 
                                                        class="inline-flex items-center px-3 py-1 border border-indigo-300 text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-50 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mr-2">
                                                    Edit
                                                </button>
                                                <a href="/cornerbites-sia/process/simpan_produk.php?action=delete&id=<?php echo htmlspecialchars($product['id']); ?>" 
                                                   class="inline-flex items-center px-3 py-1 border border-red-300 text-sm leading-4 font-medium rounded-md text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                                   onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?');">
                                                    Hapus
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Belum ada produk yang tercatat.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Kontrol Navigasi dan Limit -->
                    <div class="mt-6 flex flex-col md:flex-row justify-between items-center gap-4 px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <!-- Info Pagination -->
                        <div class="text-sm text-gray-700">
                            Menampilkan <?php echo number_format($offset + 1); ?> sampai <?php echo number_format(min($offset + $limit, $totalProducts)); ?> dari <?php echo number_format($totalProducts); ?> produk
                        </div>

                        <!-- Dropdown Limit -->
                        <div class="flex items-center space-x-2">
                            <form id="limitForm" method="get" class="flex items-center space-x-2">
                                <label for="limitSelect" class="text-sm text-gray-700">Per halaman:</label>
                                <select name="limit" id="limitSelect" onchange="document.getElementById('limitForm').submit()"
                                        class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <?php foreach ($limit_options as $opt): ?>
                                        <option value="<?php echo $opt; ?>" <?php echo ($limit == $opt) ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="page" value="1">
                            </form>
                        </div>

                        <!-- Navigasi Halaman -->
                        <?php if ($totalPages > 1): ?>
                        <div class="flex items-center space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?limit=<?php echo $limit; ?>&page=<?php echo $page - 1; ?>" 
                                   class="px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition duration-200">‹ Prev</a>
                            <?php endif; ?>

                            <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            for ($i = $startPage; $i <= $endPage; $i++): 
                            ?>
                                <a href="?limit=<?php echo $limit; ?>&page=<?php echo $i; ?>" 
                                   class="px-3 py-2 text-sm rounded <?php echo ($i == $page) ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition duration-200">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?limit=<?php echo $limit; ?>&page=<?php echo $page + 1; ?>" 
                                   class="px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition duration-200">Next ›</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // JavaScript untuk mengisi form saat tombol edit diklik
    function editProduct(product) {
        document.getElementById('product_id_to_edit').value = product.id;
        document.getElementById('product_name').value = product.name;
        document.getElementById('unit').value = product.unit;
        // Menggunakan 'stock' sesuai dengan kolom di DB
        document.getElementById('stock').value = product.stock; 
        // Menggunakan 'cost_price' sesuai dengan kolom di DB
        document.getElementById('cost_price').value = product.cost_price;
        document.getElementById('sale_price').value = product.sale_price;

        document.getElementById('submit_button').textContent = 'Update Produk';
        document.getElementById('submit_button').classList.remove('bg-green-600', 'hover:bg-green-700');
        document.getElementById('submit_button').classList.add('bg-blue-600', 'hover:bg-blue-700');
        document.getElementById('cancel_edit_button').classList.remove('hidden');
        
        // Scroll to top to make the form visible
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // JavaScript untuk mereset form
    function resetForm() {
        document.getElementById('product_id_to_edit').value = '';
        document.getElementById('product_name').value = '';
        document.getElementById('unit').value = '';
        document.getElementById('stock').value = ''; // Menggunakan 'stock'
        document.getElementById('cost_price').value = ''; // Menggunakan 'cost_price'
        document.getElementById('sale_price').value = '';

        document.getElementById('submit_button').textContent = 'Tambah Produk';
        document.getElementById('submit_button').classList.remove('bg-blue-600', 'hover:bg-blue-700');
        document.getElementById('submit_button').classList.add('bg-green-600', 'hover:bg-green-700');
        document.getElementById('cancel_edit_button').classList.add('hidden');
    }
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
