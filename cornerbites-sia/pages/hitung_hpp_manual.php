<?php
// pages/hitung_hpp_manual.php
// Halaman untuk perhitungan HPP manual berdasarkan produk dan rentang tanggal.

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php'; // Sertakan file koneksi database

$products = [];
$hpp_result = null;
$error_message = '';

try {
    $conn = $db;

    // Ambil daftar produk untuk dropdown
    // Menggunakan 'cost_price' sesuai dengan struktur tabel Anda
    $stmt = $conn->query("SELECT id, name, cost_price FROM products ORDER BY name ASC");
    $products = $stmt->fetchAll();

    // Proses form jika ada data yang dikirim
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $product_id = $_POST['product_id'] ?? null;
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';

        if (empty($product_id) || empty($start_date) || empty($end_date)) {
            $error_message = 'Semua kolom harus diisi untuk melakukan perhitungan.';
        } else {
            // Ambil harga beli produk yang dipilih
            // Menggunakan 'cost_price' sesuai dengan struktur tabel Anda
            $stmtProduct = $conn->prepare("SELECT name, cost_price FROM products WHERE id = ?");
            $stmtProduct->execute([$product_id]);
            $selectedProduct = $stmtProduct->fetch();

            if (!$selectedProduct) {
                $error_message = 'Produk tidak ditemukan.';
            } else {
                // Hitung total unit yang terjual untuk produk tersebut dalam rentang tanggal
                $stmtTotalSold = $conn->prepare("
                    SELECT SUM(quantity) AS total_quantity_sold
                    FROM transactions
                    WHERE product_id = ?
                    AND type = 'pemasukan'
                    AND date BETWEEN ? AND ?
                ");
                $stmtTotalSold->execute([$product_id, $start_date, $end_date]);
                $totalQuantitySold = $stmtTotalSold->fetchColumn();

                // Jika tidak ada penjualan, set ke 0
                if ($totalQuantitySold === null) {
                    $totalQuantitySold = 0;
                }

                // Hitung HPP menggunakan 'cost_price'
                $hpp_calculated = $totalQuantitySold * $selectedProduct['cost_price'];

                $hpp_result = [
                    'product_name' => $selectedProduct['name'],
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'total_quantity_sold' => $totalQuantitySold,
                    'purchase_price_per_unit' => $selectedProduct['cost_price'], // Tampilkan sebagai "Harga Beli per Unit"
                    'hpp' => $hpp_calculated
                ];
            }
        }
    }

} catch (PDOException $e) {
    error_log("Error di halaman Hitung HPP Manual: " . $e->getMessage());
    $error_message = 'Terjadi kesalahan saat memuat data atau melakukan perhitungan: ' . $e->getMessage();
}

?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>
<div class="flex h-screen bg-gray-100 font-sans">
    <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="flex items-center justify-between h-16 bg-white border-b border-gray-200 px-6 shadow-sm">
            <h1 class="text-xl font-semibold text-gray-800">Perhitungan HPP Manual</h1>
        </header>
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-200 p-6">
            <div class="container mx-auto">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Hitung Harga Pokok Penjualan (HPP) per Produk</h2>

                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">Panduan Perhitungan HPP</h3>
                    <p class="text-gray-600 mb-4">
                        Di sini Anda dapat menghitung HPP (Harga Pokok Penjualan) untuk produk spesifik dalam rentang tanggal tertentu.
                        HPP dihitung berdasarkan **total jumlah unit produk yang terjual** dikalikan dengan **harga beli produk** tersebut.
                    </p>
                    <ol class="list-decimal list-inside text-gray-700 mb-6 space-y-2">
                        <li>Pilih <strong>Produk</strong> yang ingin Anda hitung HPP-nya dari daftar.</li>
                        <li>Tentukan <strong>Tanggal Mulai</strong> periode perhitungan.</li>
                        <li>Tentukan <strong>Tanggal Akhir</strong> periode perhitungan.</li>
                        <li>Klik tombol <strong>"Hitung HPP"</strong> untuk melihat hasilnya.</li>
                    </ol>

                    <?php if ($error_message): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md mb-4" role="alert">
                            <p class="font-bold">Error!</p>
                            <p class="text-sm"><?php echo htmlspecialchars($error_message); ?></p>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="product_id" class="block text-gray-700 text-sm font-semibold mb-2">Pilih Produk:</label>
                                <select id="product_id" name="product_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2.5" required>
                                    <option value="">-- Pilih Produk --</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?php echo htmlspecialchars($product['id']); ?>" <?php echo (isset($_POST['product_id']) && $_POST['product_id'] == $product['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($product['name']) . ' (Harga Beli: Rp ' . number_format($product['cost_price'], 0, ',', '.') . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="start_date" class="block text-gray-700 text-sm font-semibold mb-2">Tanggal Mulai:</label>
                                <input type="date" id="start_date" name="start_date" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2.5" value="<?php echo htmlspecialchars($_POST['start_date'] ?? date('Y-m-01')); ?>" required>
                            </div>
                            <div>
                                <label for="end_date" class="block text-gray-700 text-sm font-semibold mb-2">Tanggal Akhir:</label>
                                <input type="date" id="end_date" name="end_date" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2.5" value="<?php echo htmlspecialchars($_POST['end_date'] ?? date('Y-m-d')); ?>" required>
                            </div>
                        </div>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors duration-200 mt-4">
                            Hitung HPP
                        </button>
                    </form>
                </div>

                <?php if ($hpp_result !== null): ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-xl font-semibold text-gray-700 mb-4">Hasil Perhitungan HPP</h3>
                        <table class="min-w-full divide-y divide-gray-200 rounded-lg overflow-hidden mb-4">
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-3 whitespace-nowrap text-md font-semibold text-gray-800">Produk</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-md text-gray-900"><?php echo htmlspecialchars($hpp_result['product_name']); ?></td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-3 whitespace-nowrap text-md font-semibold text-gray-800">Periode</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-md text-gray-900">
                                        <?php echo date('d M Y', strtotime($hpp_result['start_date'])) . ' - ' . date('d M Y', strtotime($hpp_result['end_date'])); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-3 whitespace-nowrap text-md font-semibold text-gray-800">Harga Beli per Unit</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-md text-gray-900">Rp <?php echo number_format($hpp_result['purchase_price_per_unit'], 0, ',', '.'); ?></td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-3 whitespace-nowrap text-md font-semibold text-gray-800">Total Unit Terjual</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-md text-gray-900"><?php echo number_format($hpp_result['total_quantity_sold'], 0, ',', '.'); ?> Unit</td>
                                </tr>
                                <tr class="bg-blue-50">
                                    <td class="px-6 py-3 whitespace-nowrap text-lg font-bold text-blue-800">Harga Pokok Penjualan (HPP)</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-lg font-bold text-blue-800">Rp <?php echo number_format($hpp_result['hpp'], 0, ',', '.'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="text-sm text-gray-600 mt-2">
                            *HPP dihitung berdasarkan harga beli produk dikalikan total unit yang terjual pada periode yang dipilih.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/footer.php'; ?>
