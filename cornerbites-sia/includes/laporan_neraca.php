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
                <td class="px-6 py-2 whitespace-nowrap text-base text-gray-700 text-right">
                    Rp <?php echo number_format($data['aset_lancar']['kas_bank'], 0, ',', '.'); ?>
                </td>
            </tr>
            <tr>
                <td class="px-8 py-2 whitespace-nowrap text-base text-gray-700">Persediaan Barang Dagang</td>
                <td class="px-6 py-2 whitespace-nowrap text-base text-gray-700 text-right">
                    Rp <?php echo number_format($data['aset_lancar']['persediaan'], 0, ',', '.'); ?>
                </td>
            </tr>
            <tr class="bg-gray-50">
                <td class="px-6 py-3 whitespace-nowrap text-lg font-bold text-gray-900">TOTAL ASET</td>
                <td class="px-6 py-3 whitespace-nowrap text-lg font-bold text-gray-900 text-right">
                    Rp <?php echo number_format($data['aset_lancar']['kas_bank'] + $data['aset_lancar']['persediaan'], 0, ',', '.'); ?>
                </td>
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
                <td class="px-6 py-2 whitespace-nowrap text-base text-gray-700 text-right">
                    Rp <?php echo number_format($data['kewajiban']['hutang_usaha'], 0, ',', '.'); ?>
                </td>
            </tr>
            <tr>
                <td class="px-6 py-3 whitespace-now
