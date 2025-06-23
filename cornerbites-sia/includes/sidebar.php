<?php
// includes/sidebar.php
// File ini berisi struktur sidebar navigasi, disesuaikan berdasarkan role pengguna.

// Pastikan session sudah dimulai dan user_role tersedia
$user_role = $_SESSION['user_role'] ?? 'guest'; // Default ke 'guest' jika tidak ada session role
?>
<aside class="flex flex-col w-64 bg-gray-800 text-white shadow-lg rounded-r-lg overflow-hidden">
    <!-- Logo Aplikasi -->
    <div class="flex items-center justify-center h-20 shadow-md bg-gray-900 rounded-r-lg">
        <span class="text-2xl font-bold text-white tracking-wide">Corner Bites SIA</span>
    </div>
    <!-- Navigasi Menu -->
    <nav class="flex-1 mt-6 px-4 pb-4 overflow-y-auto">
        <?php if ($user_role == 'user'): ?>
            <!-- Menu untuk USER BIASA -->
            <a href="/cornerbites-sia/pages/dashboard.php" class="flex items-center py-3 px-4 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200 mb-2">
                <!-- Dashboard Icon -->
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Dashboard
            </a>
            <a href="/cornerbites-sia/pages/transaksi.php?type=pemasukan" class="flex items-center py-3 px-4 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200 mb-2">
                <!-- Penjualan Icon -->
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                Penjualan
            </a>
            <a href="/cornerbites-sia/pages/transaksi.php?type=pengeluaran" class="flex items-center py-3 px-4 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200 mb-2">
                <!-- Pengeluaran Icon -->
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Pengeluaran
            </a>
            <a href="/cornerbites-sia/pages/produk.php" class="flex items-center py-3 px-4 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200 mb-2">
                <!-- Manajemen Produk Icon -->
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                Manajemen Produk
            </a>
            <!-- NEW: Manajemen Resep Produk -->
            <a href="/cornerbites-sia/pages/resep_produk.php" class="flex items-center py-3 px-4 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200 mb-2">
                <!-- Recipe Icon -->
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                Manajemen Resep Produk
            </a>
            <a href="/cornerbites-sia/pages/bahan_baku.php" class="flex items-center py-3 px-4 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200 mb-2">
                <!-- Raw Materials Icon -->
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7v.01M12 7v.01M16 7v.01M9 19h6a2 2 0 002-2V7a2 2 0 00-2-2H9a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                Manajemen Bahan Baku
            </a>
            <!-- NEW: Perhitungan HPP Manual -->
            <a href="/cornerbites-sia/pages/hitung_hpp_manual.php" class="flex items-center py-3 px-4 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200 mb-2">
                <!-- Calculation Icon -->
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c-1.381 0-2.25-.829-2.25-2.25V7.5M9 19c0 1.421-.869 2.25-2.25 2.25H4.5A2.25 2.25 0 012.25 19V6.75A2.25 2.25 0 014.5 4.5h1.5c1.421 0 2.25.869 2.25 2.25V19zm10-5a2 2 0 11-4 0 2 2 0 014 0zm-7 4a2 2 0 110-4 2 2 0 010 4z"></path></svg>
                Hitung HPP Manual
            </a>
            <a href="/cornerbites-sia/pages/laporan.php?type=laba_rugi" class="flex items-center py-3 px-4 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200 mb-2">
                <!-- Laporan Laba Rugi Icon -->
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 2v-6m2 9H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Laporan Laba Rugi
            </a>
            <a href="/cornerbites-sia/pages/laporan.php?type=neraca" class="flex items-center py-3 px-4 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200 mb-2">
                <!-- Laporan Neraca Icon -->
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2H10a2 2 0 01-2-2v-4m0 0H5c-1.5 0-2.25-.85-2.25-2.25S3.5 9.5 5 9.5h1"></path></svg>
                Laporan Neraca
            </a>
        <?php elseif ($user_role == 'admin'): ?>
            <!-- Menu untuk ADMIN -->
            <a href="/cornerbites-sia/admin/dashboard.php" class="flex items-center py-3 px-4 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200 mb-2">
                <!-- Admin Dashboard Icon -->
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.515-1.378 2.053-1.378 2.568 0L15.34 9.17c.338.903.882 1.63 1.64 2.11l4.897 2.915c1.378.818 1.378 2.316 0 3.134l-4.897 2.915c-.758.48-1.302 1.207-1.64 2.11L12.893 21.683c-.515 1.378-2.053 1.378-2.568 0L7.66 16.83c-.338-.903-.882-1.63-1.64-2.11l-4.897-2.915c-1.378-.818-1.378-2.316 0-3.134l4.897-2.915c.758-.48 1.302-1.207 1.64-2.11L10.325 4.317z"></path></svg>
                Admin Dashboard
            </a>
            <a href="/cornerbites-sia/admin/users.php" class="flex items-center py-3 px-4 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200 mb-2">
                <!-- Manage Users Icon -->
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M12 20a9 9 0 100-18 9 9 0 000 18zm-2-9a2 2 0 110-4 2 2 0 010 4zm7 0a2 2 0 110-4 2 2 0 010 4zm-7 7a2 2 0 110-4 2 2 0 010 4zm7 0a2 2 0 110-4 2 2 0 010 4z"></path></svg>
                Kelola Pengguna
            </a>
            <a href="/cornerbites-sia/pages/bahan_baku.php" class="flex items-center py-3 px-4 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200 mb-2">
                <!-- Raw Materials Icon -->
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7v.01M12 7v.01M16 7v.01M9 19h6a2 2 0 002-2V7a2 2 0 00-2-2H9a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                Manajemen Bahan Baku
            </a>
            <a href="/cornerbites-sia/admin/semua_transaksi.php" class="flex items-center py-3 px-4 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200 mb-2">
                <!-- All Transactions Icon -->
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 2v6m0 4v2m-6-1h12"></path></svg>
                Semua Transaksi
            </a>
            <a href="/cornerbites-sia/admin/statistik.php" class="flex items-center py-3 px-4 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200 mb-2">
                <!-- Statistics Icon -->
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                Statistik Global
            </a>
        <?php endif; ?>
    </nav>
    <!-- Tombol Logout di bagian bawah sidebar -->
    <div class="p-4 border-t border-gray-700">
        <a href="/cornerbites-sia/auth/logout.php" class="flex items-center justify-center py-3 px-4 rounded-lg bg-red-600 text-white font-bold hover:bg-red-700 transition-colors duration-200 shadow-md">
            <!-- Logout Icon -->
            <svg class="sidebar-icon mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            Logout
        </a>
    </div>
</aside>
