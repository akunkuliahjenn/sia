
<?php 
// includes/sidebar.php 
// File ini berisi struktur sidebar navigasi, disesuaikan berdasarkan role pengguna. 
// Pastikan session sudah dimulai dan user_role tersedia 
$user_role = $_SESSION['user_role'] ?? 'guest'; // Default ke 'guest' jika tidak ada session role

// Determine current page for highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>

<aside class="flex flex-col w-64 bg-gray-50 shadow-xl border-r border-gray-200">
    <!-- Logo Aplikasi -->
    <div class="flex items-center justify-center h-20 bg-gradient-to-r from-blue-500 to-blue-600 shadow-lg">
        <div class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                </svg>
            </div>
            <span class="text-lg font-bold text-white tracking-wide">Corner Bites SIA</span>
        </div>
    </div>

    <!-- Navigasi Menu -->
    <nav class="flex-1 mt-6 px-4 pb-4 overflow-y-auto">
        <?php if ($user_role == 'user'): ?>
            <!-- Menu untuk USER BIASA -->
            <div class="space-y-1">
                <a href="/cornerbites-sia/pages/dashboard.php" class="flex items-center py-3 px-4 rounded-xl text-gray-600 hover:bg-blue-50 hover:text-blue-700 transition-all duration-200 <?php echo ($current_page == 'dashboard.php' && $current_dir == 'pages') ? 'bg-blue-50 text-blue-700 shadow-sm border-l-4 border-blue-500' : ''; ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </a>

                <a href="/cornerbites-sia/pages/transaksi.php?type=pemasukan" class="flex items-center py-3 px-4 rounded-xl text-gray-600 hover:bg-emerald-50 hover:text-emerald-700 transition-all duration-200 <?php echo ($current_page == 'transaksi.php' && isset($_GET['type']) && $_GET['type'] == 'pemasukan') ? 'bg-emerald-50 text-emerald-700 shadow-sm border-l-4 border-emerald-500' : ''; ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    Penjualan
                </a>

                <a href="/cornerbites-sia/pages/transaksi.php?type=pengeluaran" class="flex items-center py-3 px-4 rounded-xl text-gray-600 hover:bg-red-50 hover:text-red-700 transition-all duration-200 <?php echo ($current_page == 'transaksi.php' && isset($_GET['type']) && $_GET['type'] == 'pengeluaran') ? 'bg-red-50 text-red-700 shadow-sm border-l-4 border-red-500' : ''; ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Pengeluaran
                </a>

                <a href="/cornerbites-sia/pages/produk.php" class="flex items-center py-3 px-4 rounded-xl text-gray-600 hover:bg-purple-50 hover:text-purple-700 transition-all duration-200 <?php echo ($current_page == 'produk.php') ? 'bg-purple-50 text-purple-700 shadow-sm border-l-4 border-purple-500' : ''; ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                    </svg>
                    Manajemen Produk
                </a>

                <a href="/cornerbites-sia/pages/resep_produk.php" class="flex items-center py-3 px-4 rounded-xl text-gray-600 hover:bg-orange-50 hover:text-orange-700 transition-all duration-200 <?php echo ($current_page == 'resep_produk.php') ? 'bg-orange-50 text-orange-700 shadow-sm border-l-4 border-orange-500' : ''; ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    Manajemen Resep & HPP
                </a>

                <a href="/cornerbites-sia/pages/bahan_baku.php" class="flex items-center py-3 px-4 rounded-xl text-gray-600 hover:bg-teal-50 hover:text-teal-700 transition-all duration-200 <?php echo ($current_page == 'bahan_baku.php') ? 'bg-teal-50 text-teal-700 shadow-sm border-l-4 border-teal-500' : ''; ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    Manajemen Bahan Baku
                </a>

                <a href="/cornerbites-sia/pages/laporan_keuangan.php" class="flex items-center py-3 px-4 rounded-xl text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 transition-all duration-200 <?php echo ($current_page == 'laporan_keuangan.php') ? 'bg-indigo-50 text-indigo-700 shadow-sm border-l-4 border-indigo-500' : ''; ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 2v-6m2 9H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Laporan Keuangan
                </a>
            </div>

        <?php elseif ($user_role == 'admin'): ?>
            <!-- Menu untuk ADMIN -->
            <div class="space-y-1">
                <a href="/cornerbites-sia/admin/dashboard.php" class="flex items-center py-3 px-4 rounded-xl text-gray-600 hover:bg-blue-50 hover:text-blue-700 transition-all duration-200 <?php echo ($current_page == 'dashboard.php' && $current_dir == 'admin') ? 'bg-blue-50 text-blue-700 shadow-sm border-l-4 border-blue-500' : ''; ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.515-1.378 2.053-1.378 2.568 0L15.34 9.17c.338.903.882 1.63 1.64 2.11l4.897 2.915c1.378.818 1.378 2.316 0 3.134l-4.897 2.915c-.758.48-1.302 1.207-1.64 2.11L12.893 21.683c-.515 1.378-2.053 1.378-2.568 0L7.66 16.83c-.338-.903-.882-1.63-1.64-2.11l-4.897-2.915c-1.378-.818-1.378-2.316 0-3.134l4.897-2.915c.758-.48 1.302-1.207 1.64-2.11L10.325 4.317z"></path>
                    </svg>
                    Admin Dashboard
                </a>

                <a href="/cornerbites-sia/admin/users.php" class="flex items-center py-3 px-4 rounded-xl text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 transition-all duration-200 <?php echo ($current_page == 'users.php') ? 'bg-indigo-50 text-indigo-700 shadow-sm border-l-4 border-indigo-500' : ''; ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M12 20a9 9 0 100-18 9 9 0 000 18zm-2-9a2 2 0 110-4 2 2 0 010 4zm7 0a2 2 0 110-4 2 2 0 010 4zm-7 7a2 2 0 110-4 2 2 0 010 4zm7 0a2 2 0 110-4 2 2 0 010 4z"></path>
                    </svg>
                    Kelola Pengguna
                </a>

                <a href="/cornerbites-sia/pages/bahan_baku.php" class="flex items-center py-3 px-4 rounded-xl text-gray-600 hover:bg-teal-50 hover:text-teal-700 transition-all duration-200 <?php echo ($current_page == 'bahan_baku.php') ? 'bg-teal-50 text-teal-700 shadow-sm border-l-4 border-teal-500' : ''; ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    Manajemen Bahan Baku
                </a>

                <a href="/cornerbites-sia/admin/semua_transaksi.php" class="flex items-center py-3 px-4 rounded-xl text-gray-600 hover:bg-emerald-50 hover:text-emerald-700 transition-all duration-200 <?php echo ($current_page == 'semua_transaksi.php') ? 'bg-emerald-50 text-emerald-700 shadow-sm border-l-4 border-emerald-500' : ''; ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 2v6m0 4v2m-6-1h12"></path>
                    </svg>
                    Semua Transaksi
                </a>

                <a href="/cornerbites-sia/admin/statistik.php" class="flex items-center py-3 px-4 rounded-xl text-gray-600 hover:bg-purple-50 hover:text-purple-700 transition-all duration-200 <?php echo ($current_page == 'statistik.php') ? 'bg-purple-50 text-purple-700 shadow-sm border-l-4 border-purple-500' : ''; ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                    </svg>
                    Statistik Global
                </a>
            </div>
        <?php endif; ?>
    </nav>

    <!-- Tombol Logout di bagian bawah sidebar -->
    <div class="p-4 border-t border-gray-100">
        <a href="/cornerbites-sia/auth/logout.php" class="flex items-center justify-center py-3 px-4 rounded-xl bg-gradient-to-r from-red-500 to-red-600 text-white font-semibold hover:from-red-600 hover:to-red-700 transition-all duration-200 shadow-lg transform hover:scale-[1.02]">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
            </svg>
            Logout
        </a>
    </div>
</aside>
