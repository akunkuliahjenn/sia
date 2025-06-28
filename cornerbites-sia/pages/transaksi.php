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
<div class="flex h-screen bg-gray-100 font-sans">
    <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-200 p-6">
            <div class="container mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-6"><?php echo $page_title; ?></h1>

                <?php if ($message): ?>
                    <div class="mb-4 p-4 rounded-md <?php echo ($message_type == 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'); ?>" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Form Transaksi -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">Input Transaksi <?php echo ($type == 'pemasukan' ? 'Penjualan' : 'Pengeluaran'); ?></h3>
                    <?php if ($type == 'pengeluaran'): ?>
                        <p class="text-sm text-gray-600 mb-4">Catat setiap pengeluaran operasional atau pembelian bahan baku untuk kebutuhan bisnis Anda di sini.</p>
                    <?php endif; ?>
                    <form action="/cornerbites-sia/process/simpan_transaksi.php" method="POST">
                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                        <input type="hidden" name="transaction_id" id="transaction_id_to_edit" value=""> <!-- Hidden field for edit -->

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="date" class="block text-gray-700 text-sm font-semibold mb-2">Tanggal:</label>
                                <input type="date" id="date" name="date" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2.5" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <?php if ($type == 'pemasukan'): ?>
                                <div>
                                    <label for="product_id" class="block text-gray-700 text-sm font-semibold mb-2">Produk:</label>
                                    <select id="product_id" name="product_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2.5" onchange="updateAmountField()" required>
                                        <option value="">Pilih Produk</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?php echo htmlspecialchars($product['id']); ?>" data-price="<?php echo htmlspecialchars($product['sale_price']); ?>" data-stock="<?php echo htmlspecialchars($product['stock']); ?>">
                                                <?php echo htmlspecialchars($product['name']) . ' (Stok: ' . htmlspecialchars($product['stock']) . ' | Rp ' . number_format($product['sale_price'], 0, ',', '.') . ')'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="quantity" class="block text-gray-700 text-sm font-semibold mb-2">Jumlah Unit:</label>
                                    <input type="number" id="quantity" name="quantity" min="1" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2.5" placeholder="Contoh: 1, 5, 10" oninput="updateAmountField()" required disabled>
                                    <p id="quantity-info" class="text-sm text-gray-500 mt-1">Pilih produk terlebih dahulu untuk mengaktifkan kolom ini.</p>
                                </div>
                            <?php endif; ?>
                            <div>
                                <label for="amount" class="block text-gray-700 text-sm font-semibold mb-2">Jumlah (Rp):</label>
                                <input type="number" id="amount" name="amount" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2.5 <?php echo ($type == 'pemasukan') ? 'bg-gray-50' : ''; ?>" step="any" placeholder="<?php echo ($type == 'pemasukan') ? 'Otomatis terisi' : 'Contoh: 50000, 125000'; ?>" required <?php echo ($type == 'pemasukan') ? 'readonly' : ''; ?>>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="block text-gray-700 text-sm font-semibold mb-2">Deskripsi Transaksi:</label>
                            <textarea id="description" name="description" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2.5" placeholder="<?php echo ($type == 'pemasukan') ? 'Contoh: Penjualan Kopi Latte' : 'Contoh: Pembelian bahan baku gula, Bayar sewa toko'; ?>" required></textarea>
                        </div>

                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors duration-200" id="submit_button">
                            Simpan Transaksi
                        </button>
                        <button type="button" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors duration-200 ml-2 hidden" id="cancel_edit_button" onclick="resetForm()">
                            Batal Edit
                        </button>
                    </form>
                </div>

                <!-- Filter Transaksi -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Filter Transaksi</h3>
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                        
                        <!-- Pencarian -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pencarian</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" 
                                   placeholder="Cari deskripsi atau jumlah..." 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <!-- Filter Tanggal -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Filter Tanggal</label>
                            <select name="date_filter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="semua" <?php echo ($_GET['date_filter'] ?? '') == 'semua' ? 'selected' : ''; ?>>Semua</option>
                                <option value="hari_ini" <?php echo ($_GET['date_filter'] ?? '') == 'hari_ini' ? 'selected' : ''; ?>>Hari Ini</option>
                                <option value="bulan_ini" <?php echo ($_GET['date_filter'] ?? '') == 'bulan_ini' ? 'selected' : ''; ?>>Bulan Ini</option>
                                <option value="tahun_ini" <?php echo ($_GET['date_filter'] ?? '') == 'tahun_ini' ? 'selected' : ''; ?>>Tahun Ini</option>
                            </select>
                        </div>
                        
                        <!-- Per Halaman -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Per Halaman</label>
                            <select name="limit" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                                <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                                <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                            </select>
                        </div>
                        
                        <!-- Tombol Filter -->
                        <div class="flex gap-2">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-200">
                                Filter
                            </button>
                            <button type="button" onclick="resetFilter()" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded transition duration-200">
                                Reset
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Daftar Transaksi -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold text-gray-700">Daftar Transaksi <?php echo ($type == 'pemasukan' ? 'Penjualan' : 'Pengeluaran'); ?></h3>
                        <div class="text-right">
                            <span class="text-sm text-gray-600">Total: </span>
                            <span class="text-lg font-bold <?php echo $type == 'pemasukan' ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo number_format($totalTransactions); ?> transaksi
                            </span>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 rounded-lg overflow-hidden">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal & Waktu</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                    <?php if ($type == 'pemasukan'): ?>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah Unit</th>
                                    <?php endif; ?>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah (Rp)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (!empty($transactions)): ?>
                                    <?php foreach ($transactions as $transaction): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div class="flex flex-col">
                                                    <span class="font-semibold"><?php echo date('d/m/Y', strtotime($transaction['date'])); ?></span>
                                                    <span class="text-xs text-gray-500">
                                                        <?php echo $transaction['created_at'] ? date('H:i:s', strtotime($transaction['created_at'])) : 'N/A'; ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($transaction['description']); ?></td>
                                            <?php if ($type == 'pemasukan'): ?>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($transaction['product_name'] ?? '-'); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($transaction['quantity'] ?? '-'); ?></td>
                                            <?php endif; ?>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <span class="font-semibold <?php echo $type == 'pemasukan' ? 'text-green-600' : 'text-red-600'; ?>">
                                                    Rp <?php echo number_format($transaction['amount'], 0, ',', '.'); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button onclick="editTransaction(<?php echo htmlspecialchars(json_encode($transaction)); ?>, <?php echo htmlspecialchars(json_encode($products)); ?>)" 
                                                        class="inline-flex items-center px-3 py-1 border border-indigo-300 text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-50 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mr-2">
                                                    Edit
                                                </button>
                                                <a href="/cornerbites-sia/process/simpan_transaksi.php?action=delete&id=<?php echo htmlspecialchars($transaction['id']); ?>&type=<?php echo htmlspecialchars($type); ?>" 
                                                   class="inline-flex items-center px-3 py-1 border border-red-300 text-sm leading-4 font-medium rounded-md text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" 
                                                   onclick="return confirm('Apakah Anda yakin ingin menghapus transaksi ini?');">
                                                    Hapus
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?php echo ($type == 'pemasukan' ? '6' : '4'); ?>" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Belum ada transaksi <?php echo ($type == 'pemasukan' ? 'penjualan' : 'pengeluaran'); ?> tercatat.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                                    <!-- Kontrol Navigasi dan Limit -->
                    <div class="mt-6 flex flex-col md:flex-row justify-between items-center gap-4 px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <!-- Info Pagination -->
                        <div class="text-sm text-gray-700">
                            Menampilkan <?php echo number_format($offset + 1); ?> sampai <?php echo number_format(min($offset + $limit, $totalTransactions)); ?> dari <?php echo number_format($totalTransactions); ?> transaksi
                        </div>

                        <!-- Dropdown Limit -->
                        <div class="flex items-center space-x-2">
                            <form id="limitForm" method="get" class="flex items-center space-x-2">
                                <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <input type="hidden" name="date_filter" value="<?php echo htmlspecialchars($date_filter); ?>">
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
                                   class="px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition duration-200">‹ Prev</a>
                            <?php endif; ?>

                            <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            for ($i = $startPage; $i <= $endPage; $i++): 
                            ?>
                                <a href="?<?php echo http_build_query(array_merge($current_params, ['page' => $i])); ?>" 
                                   class="px-3 py-2 text-sm rounded <?php echo ($i == $page) ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition duration-200">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?<?php echo http_build_query(array_merge($current_params, ['page' => $page + 1])); ?>" 
                                   class="px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition duration-200">Next ›</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
            </div>
        </main>
    </div>
</div>

<script>
    // JavaScript untuk mengupdate field 'amount' secara otomatis saat memilih produk dan mengisi quantity
    function updateAmountField() {
        const productIdSelect = document.getElementById('product_id');
        const quantityInput = document.getElementById('quantity');
        const amountInput = document.getElementById('amount');
        const quantityInfo = document.getElementById('quantity-info'); 

        if (productIdSelect && quantityInput && amountInput && quantityInfo) {
            const selectedOption = productIdSelect.options[productIdSelect.selectedIndex];
            
            // If "Pilih Produk" is selected or no product is selected
            if (selectedOption.value === "") {
                amountInput.value = '';
                quantityInput.value = ''; // Clear quantity
                quantityInput.disabled = true; // Disable quantity input
                quantityInfo.classList.remove('hidden'); // Show message
                return; // Exit early
            }
            
            // If a product is selected, enable quantity input and hide message
            quantityInput.disabled = false;
            quantityInfo.classList.add('hidden'); // Hide message

            const price = parseFloat(selectedOption.dataset.price);
            const currentStock = parseInt(selectedOption.dataset.stock);
            let quantity = parseInt(quantityInput.value);

            // Validate quantity input (ensure it's a positive number)
            if (isNaN(quantity) || quantity <= 0) {
                amountInput.value = '';
                return; // Don't proceed with amount calculation if quantity is invalid
            }

            // Validate quantity against current stock
            if (quantity > currentStock) {
                alert('Jumlah unit yang dimasukkan melebihi stok yang tersedia (' + currentStock + ' unit).');
                quantityInput.value = currentStock; // Set quantity to max available stock
                quantity = currentStock; // Update quantity variable for calculation
            }

            // Calculate and set amount
            if (!isNaN(price) && !isNaN(quantity) && quantity > 0) {
                amountInput.value = (price * quantity).toFixed(0);
            } else {
                amountInput.value = '';
            }
        }
    }

    // Function to populate form for editing a transaction
    function editTransaction(transaction, allProducts) {
        document.getElementById('transaction_id_to_edit').value = transaction.id;
        document.getElementById('date').value = transaction.date;
        document.getElementById('amount').value = parseFloat(transaction.amount); // Ensure amount is number
        document.getElementById('description').value = transaction.description;

        const submitButton = document.getElementById('submit_button');
        const cancelButton = document.getElementById('cancel_edit_button');
        
        submitButton.textContent = 'Update Transaksi';
        submitButton.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        submitButton.classList.add('bg-indigo-600', 'hover:bg-indigo-700'); // Change color for edit
        cancelButton.classList.remove('hidden');

        // Logic for 'pemasukan' (sale) type specific fields
        if (transaction.type === 'pemasukan') {
            const productIdSelect = document.getElementById('product_id');
            const quantityInput = document.getElementById('quantity');
            const quantityInfo = document.getElementById('quantity-info');

            productIdSelect.value = transaction.product_id;
            quantityInput.value = transaction.quantity;
            quantityInput.disabled = false; // Enable quantity input
            quantityInfo.classList.add('hidden'); // Hide message

            // Adjust stock for calculation if product quantity is being edited
            // This is a simplified approach. In a complex system, you'd track stock changes more granularly.
            const selectedProductOption = productIdSelect.options[productIdSelect.selectedIndex];
            if (selectedProductOption) {
                // Temporarily add back the original quantity to stock for correct re-calculation
                // This is crucial for avoiding incorrect stock validation during edit
                let originalStock = parseInt(selectedProductOption.dataset.stock);
                let currentTransQuantity = parseInt(transaction.quantity);
                selectedProductOption.dataset.stock = originalStock + currentTransQuantity;
            }

            updateAmountField(); // Recalculate amount based on pre-filled data
        } else {
            // For 'pengeluaran', ensure product_id and quantity fields are handled if they exist
            const productIdSelect = document.getElementById('product_id');
            const quantityInput = document.getElementById('quantity');
            const quantityInfo = document.getElementById('quantity-info');

            if (productIdSelect) productIdSelect.value = '';
            if (quantityInput) {
                quantityInput.value = '';
                quantityInput.disabled = true;
            }
            if (quantityInfo) quantityInfo.classList.remove('hidden');
        }
        
        // Scroll to top to make the form visible
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Function to reset the form to 'add new' state
    function resetForm() {
        document.getElementById('transaction_id_to_edit').value = '';
        document.getElementById('date').value = '<?php echo date('Y-m-d'); ?>';
        document.getElementById('amount').value = '';
        document.getElementById('description').value = '';

        const submitButton = document.getElementById('submit_button');
        const cancelButton = document.getElementById('cancel_edit_button');

        submitButton.textContent = 'Simpan Transaksi';
        submitButton.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
        submitButton.classList.add('bg-blue-600', 'hover:bg-blue-700'); // Revert color
        cancelButton.classList.add('hidden');

        // Reset fields specific to 'pemasukan'
        const productIdSelect = document.getElementById('product_id');
        const quantityInput = document.getElementById('quantity');
        const quantityInfo = document.getElementById('quantity-info');

        if (productIdSelect) {
            // Restore original stock data attributes for all products
            // This requires storing original product data or refetching it
            // For simplicity, we'll just reset the selected option
            productIdSelect.value = '';
        }
        if (quantityInput) {
            quantityInput.value = '';
            quantityInput.disabled = true;
        }
        if (quantityInfo) quantityInfo.classList.remove('hidden');
        
        // Re-enable amount if it was read-only for sales (only if type is pengeluaran or if we want to allow manual input)
        // This part needs to be careful not to conflict with 'pemasukan' readonly
        const currentType = '<?php echo $type; ?>';
        if (currentType === 'pengeluaran') {
             amountInput.readOnly = false;
             amountInput.classList.remove('bg-gray-50');
        } else {
             amountInput.readOnly = true; // Ensure it's read-only for sales by default
             amountInput.classList.add('bg-gray-50');
        }

        // Re-run initial check for quantity field status
        if (productIdSelect && quantityInput && quantityInfo) {
            if (productIdSelect.value === "") {
                quantityInput.disabled = true;
                quantityInfo.classList.remove('hidden');
            } else {
                quantityInput.disabled = false;
                quantityInfo.classList.add('hidden');
            }
        }
    }

    // Event listeners
    document.addEventListener('DOMContentLoaded', (event) => {
        const quantityInput = document.getElementById('quantity');
        const productIdSelect = document.getElementById('product_id');

        // Initial check for quantity field status on page load
        if (productIdSelect.value === "") {
            quantityInput.disabled = true;
            document.getElementById('quantity-info').classList.remove('hidden');
        } else {
            quantityInput.disabled = false;
            document.getElementById('quantity-info').classList.add('hidden');
            updateAmountField(); 
        }

        if (quantityInput) {
            quantityInput.addEventListener('change', updateAmountField);
            quantityInput.addEventListener('keyup', updateAmountField);
        }
        if (productIdSelect) {
            productIdSelect.addEventListener('change', updateAmountField);
        }
    });

    // Function to reset filter
    function resetFilter() {
        window.location.href = '?type=<?php echo $type; ?>';
    }

</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>