<?php
// pages/laporan.php
// Halaman untuk menampilkan laporan laba rugi dan neraca.

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php'; // Sertakan file koneksi database

$report_type = $_GET['type'] ?? 'laba_rugi'; // Default report type

$page_title = ($report_type == 'laba_rugi') ? 'Laporan Laba Rugi' : 'Laporan Neraca';

$data = []; // Variabel untuk menyimpan data laporan

try {
    $conn = $db;

    if ($report_type == 'laba_rugi') {
        // Logika untuk Laporan Laba Rugi
        // Pendapatan (Penjualan)
        $stmtPenjualan = $conn->query("SELECT SUM(amount) AS total_penjualan FROM transactions WHERE type = 'pemasukan'");
        $totalPenjualan = $stmtPenjualan->fetchColumn();

        // Harga Pokok Penjualan (HPP) - Ini akan menjadi perhitungan yang disederhanakan
        // Menggunakan 'cost_price' dan 'stock' sesuai DB Anda
        $stmtHPP = $conn->query("
            SELECT SUM(t.quantity * p.cost_price) AS total_hpp
            FROM transactions t
            JOIN products p ON t.product_id = p.id
            WHERE t.type = 'pemasukan' AND t.product_id IS NOT NULL
        ");
        $totalHPP = $stmtHPP->fetchColumn();


        // Beban Operasional (Pengeluaran non-produk)
        $stmtBebanOperasional = $conn->query("SELECT SUM(amount) AS total_beban FROM transactions WHERE type = 'pengeluaran'");
        $totalBebanOperasional = $stmtBebanOperasional->fetchColumn();

        $labaKotor = $totalPenjualan - $totalHPP;
        $labaBersih = $labaKotor - $totalBebanOperasional;

        $data = [
            'pendapatan_penjualan' => $totalPenjualan,
            'hpp' => $totalHPP,
            'laba_kotor' => $labaKotor,
            'beban_operasional' => $totalBebanOperasional,
            'laba_bersih' => $labaBersih
        ];

    } elseif ($report_type == 'neraca') {
        // Logika untuk Laporan Neraca (SANGAT disederhanakan)
        // Dalam konteks UMKM sederhana ini, neraca mungkin hanya mencakup:
        // Aset Lancar: Kas/Bank (saldo akhir)
        // Kewajiban: Belum ada fitur hutang, jadi mungkin kosong
        // Ekuitas: Modal Awal + Laba Ditahan (Laba Bersih Kumulatif)

        // Untuk Kas/Bank, kita bisa mengambil saldo kumulatif dari semua transaksi
        $stmtSaldoKas = $conn->query("SELECT SUM(CASE WHEN type = 'pemasukan' THEN amount ELSE -amount END) AS saldo_kas FROM transactions");
        $saldoKas = $stmtSaldoKas->fetchColumn();

        // Modal (asumsi modal awal 0 atau tidak dicatat secara terpisah, hanya laba ditahan)
        // Untuk laba ditahan, kita bisa mengambil total laba bersih kumulatif
        // Ini perlu disesuaikan jika ada modal awal yang dicatat.
        $stmtTotalPenjualanKumulatif = $conn->query("SELECT SUM(amount) FROM transactions WHERE type = 'pemasukan'");
        $totalPenjualanKumulatif = $stmtTotalPenjualanKumulatif->fetchColumn();

        $stmtTotalPengeluaranKumulatif = $conn->query("SELECT SUM(amount) FROM transactions WHERE type = 'pengeluaran'");
        $totalPengeluaranKumulatif = $stmtTotalPengeluaranKumulatif->fetchColumn();

        $labaBersihKumulatif = $totalPenjualanKumulatif - $totalPengeluaranKumulatif;


        $data = [
            'aset_lancar' => [
                'kas_bank' => $saldoKas,
                // Menggunakan 'stock' dan 'cost_price' sesuai DB Anda
                'persediaan' => array_sum(array_column($conn->query("SELECT stock * cost_price AS value FROM products")->fetchAll(), 'value'))
            ],
            'kewajiban' => [
                'hutang_usaha' => 0 // Belum ada fitur pencatatan hutang
            ],
            'ekuitas' => [
                'modal_awal' => 0, // Asumsi belum dicatat
                'laba_ditahan' => $labaBersihKumulatif
            ]
        ];
    }

} catch (PDOException $e) {
    error_log("Error di halaman Laporan: " . $e->getMessage());
    // echo "Terjadi kesalahan saat memuat laporan.";
}

?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>
<div class="flex h-screen bg-gray-100 font-sans">
    <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="flex items-center justify-between h-16 bg-white border-b border-gray-200 px-6 shadow-sm">
            <h1 class="text-xl font-semibold text-gray-800"><?php echo $page_title; ?></h1>
        </header>
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-200 p-6">
            <div class="container mx-auto">
                <h2 class="text-2xl font-bold text-gray-800 mb-6"><?php echo $page_title; ?></h2>

                <!-- Navigasi Tipe Laporan -->
                <div class="flex space-x-4 mb-6">
                    <a href="?type=laba_rugi" class="py-2 px-4 rounded-lg font-semibold <?php echo ($report_type == 'laba_rugi' ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-gray-700 hover:bg-gray-100'); ?> transition duration-200">
                        Laba Rugi
                    </a>
                    <a href="?type=neraca" class="py-2 px-4 rounded-lg font-semibold <?php echo ($report_type == 'neraca' ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-gray-700 hover:bg-gray-100'); ?> transition duration-200">
                        Neraca
                    </a>
                </div>

                <?php if ($report_type == 'laba_rugi'): ?>
                    <!-- Tampilan Laporan Laba Rugi -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-xl font-semibold text-gray-700 mb-4">Periode: Kumulatif</h3>
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
                <?php elseif ($report_type == 'neraca'): ?>
                    <!-- Tampilan Laporan Neraca -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-xl font-semibold text-gray-700 mb-4">Per Tanggal: <?php echo date('d M Y'); ?></h3>
                        <table class="min-w-full divide-y divide-gray-200 mb-6">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th colspan="2" class="px-6 py-3 text-left text-lg font-bold text-gray-800 uppercase tracking-wider">ASET</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-3 whitespace-nowrap text-lg font-semibold text-gray-900">Aset Lancar</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="px-8 py-2 whitespace-nowrap text-base text-gray-700">Kas & Bank</td>
                                    <td class="px-6 py-2 whitespace-nowrap text-base text-gray-700 text-right">Rp <?php echo number_format($data['aset_lancar']['kas_bank'], 0, ',', '.'); ?></td>
                                </tr>
                                <tr>
                                    <td class="px-8 py-2 whitespace-nowrap text-base text-gray-700">Persediaan Barang Dagang</td>
                                    <td class="px-6 py-2 whitespace-nowrap text-base text-gray-700 text-right">Rp <?php echo number_format($data['aset_lancar']['persediaan'], 0, ',', '.'); ?></td>
                                </tr>
                                <tr class="bg-gray-50">
                                    <td class="px-6 py-3 whitespace-nowrap text-lg font-bold text-gray-900">TOTAL ASET</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-lg font-bold text-gray-900 text-right">Rp <?php echo number_format($data['aset_lancar']['kas_bank'] + $data['aset_lancar']['persediaan'], 0, ',', '.'); ?></td>
                                </tr>
                            </tbody>
                        </table>

                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th colspan="2" class="px-6 py-3 text-left text-lg font-bold text-gray-800 uppercase tracking-wider">KEWAJIBAN DAN EKUITAS</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-3 whitespace-nowrap text-lg font-semibold text-gray-900">Kewajiban</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="px-8 py-2 whitespace-nowrap text-base text-gray-700">Utang Usaha</td>
                                    <td class="px-6 py-2 whitespace-nowrap text-base text-gray-700 text-right">Rp <?php echo number_format($data['kewajiban']['hutang_usaha'], 0, ',', '.'); ?></td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-3 whitespace-nowrap text-lg font-semibold text-gray-900">Ekuitas</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="px-8 py-2 whitespace-nowrap text-base text-gray-700">Modal Awal</td>
                                    <td class="px-6 py-2 whitespace-nowrap text-base text-gray-700 text-right">Rp <?php echo number_format($data['ekuitas']['modal_awal'], 0, ',', '.'); ?></td>
                                </tr>
                                <tr>
                                    <td class="px-8 py-2 whitespace-nowrap text-base text-gray-700">Laba Ditahan / Laba Tahun Berjalan</td>
                                    <td class="px-6 py-2 whitespace-nowrap text-base text-gray-700 text-right">Rp <?php echo number_format($data['ekuitas']['laba_ditahan'], 0, ',', '.'); ?></td>
                                </tr>
                                <tr class="bg-blue-50">
                                    <td class="px-6 py-3 whitespace-nowrap text-xl font-bold text-blue-800">TOTAL KEWAJIBAN & EKUITAS</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-xl font-bold text-blue-800 text-right">Rp <?php echo number_format($data['kewajiban']['hutang_usaha'] + $data['ekuitas']['modal_awal'] + $data['ekuitas']['laba_ditahan'], 0, ',', '.'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/footer.php'; ?>
