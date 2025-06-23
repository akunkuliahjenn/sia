<!-- Tampilan Laporan Laba Rugi -->
<div class="bg-white rounded-lg shadow-md p-6">
    <h3 class="text-xl font-semibold text-gray-700 mb-4">Periode: Kumulatif</h3>
    <table class="min-w-full divide-y divide-gray-200">
        <tbody class="bg-white divide-y divide-gray-200">
            <tr>
                <td class="px-6 py-3 whitespace-nowrap text-lg font-semibold text-gray-900">Pendapatan Penjualan</td>
                <td class="px-6 py-3 whitespace-nowrap text-lg font-semibold text-gray-900 text-right">
                    Rp <?php echo number_format($data['pendapatan_penjualan'], 0, ',', '.'); ?>
                </td>
            </tr>
            <tr>
                <td class="px-6 py-3 whitespace-nowrap text-lg text-gray-700">Harga Pokok Penjualan (HPP)</td>
                <td class="px-6 py-3 whitespace-nowrap text-lg text-gray-700 text-right">
                    (Rp <?php echo number_format($data['hpp'], 0, ',', '.'); ?>)
                </td>
            </tr>
            <tr class="bg-gray-50">
                <td class="px-6 py-3 whitespace-nowrap text-xl font-bold text-gray-900">Laba Kotor</td>
                <td class="px-6 py-3 whitespace-nowrap text-xl font-bold text-gray-900 text-right">
                    Rp <?php echo number_format($data['laba_kotor'], 0, ',', '.'); ?>
                </td>
            </tr>
            <tr>
                <td class="px-6 py-3 whitespace-nowrap text-lg text-gray-700">Beban Operasional</td>
                <td class="px-6 py-3 whitespace-nowrap text-lg text-gray-700 text-right">
                    (Rp <?php echo number_format($data['beban_operasional'], 0, ',', '.'); ?>)
                </td>
            </tr>
            <tr class="bg-blue-50">
                <td class="px-6 py-3 whitespace-nowrap text-xl font-bold text-blue-800">Laba Bersih</td>
                <td class="px-6 py-3 whitespace-nowrap text-xl font-bold text-blue-800 text-right">
                    Rp <?php echo number_format($data['laba_bersih'], 0, ',', '.'); ?>
                </td>
            </tr>
        </tbody>
    </table>
</div>
