<?php
// process/simpan_produk.php
// File ini menangani logika penyimpanan/pembaruan/penghapusan produk.

require_once __DIR__ . '/../includes/auth_check.php'; // Pastikan user sudah login
require_once __DIR__ . '/../config/db.php'; // Sertakan file koneksi database

// Memulai session jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {
    $conn = $db; // Menggunakan koneksi $db dari db.php

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // --- Proses Tambah/Edit Produk ---
        $product_id = $_POST['product_id'] ?? null;
        $name = trim($_POST['name'] ?? '');
        $unit = trim($_POST['unit'] ?? ''); // Menggunakan 'unit' sesuai DB Anda
        $stock = (int) ($_POST['stock'] ?? 0); // Menggunakan 'stock' sesuai DB Anda
        $cost_price = (float) ($_POST['cost_price'] ?? 0); // Menggunakan 'cost_price' sesuai DB Anda
        $sale_price = (float) ($_POST['sale_price'] ?? 0);

        // Validasi dasar
        if (empty($name) || empty($unit) || $stock < 0 || $cost_price < 0 || $sale_price < 0) {
            $_SESSION['product_message'] = ['text' => 'Data produk tidak lengkap atau tidak valid.', 'type' => 'error'];
            header("Location: /cornerbites-sia/pages/produk.php");
            exit();
        }

        if ($product_id) {
            // --- Update Produk ---
            // Pastikan nama kolom di sini sesuai dengan struktur DB Anda
            $stmt = $conn->prepare("UPDATE products SET name = ?, unit = ?, stock = ?, cost_price = ?, sale_price = ? WHERE id = ?");
            if ($stmt->execute([$name, $unit, $stock, $cost_price, $sale_price, $product_id])) {
                $_SESSION['product_message'] = ['text' => 'Produk berhasil diperbarui!', 'type' => 'success'];
            } else {
                $_SESSION['product_message'] = ['text' => 'Gagal memperbarui produk.', 'type' => 'error'];
            }
        } else {
            // --- Tambah Produk Baru ---
            // Pastikan nama kolom di sini sesuai dengan struktur DB Anda
            $stmt = $conn->prepare("INSERT INTO products (name, unit, stock, cost_price, sale_price) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $unit, $stock, $cost_price, $sale_price])) {
                $_SESSION['product_message'] = ['text' => 'Produk baru berhasil ditambahkan!', 'type' => 'success'];
            } else {
                $_SESSION['product_message'] = ['text' => 'Gagal menambahkan produk baru.', 'type' => 'error'];
            }
        }

        header("Location: /cornerbites-sia/pages/produk.php");
        exit();

    } elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'delete') {
        // --- Proses Hapus Produk ---
        $product_id = $_GET['id'] ?? null;

        if (empty($product_id)) {
            $_SESSION['product_message'] = ['text' => 'ID produk tidak ditemukan untuk dihapus.', 'type' => 'error'];
            header("Location: /cornerbites-sia/pages/produk.php");
            exit();
        }

        // Cek apakah produk terkait dengan transaksi
        $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM transactions WHERE product_id = ?");
        $stmtCheck->execute([$product_id]);
        if ($stmtCheck->fetchColumn() > 0) {
            $_SESSION['product_message'] = ['text' => 'Tidak bisa menghapus produk karena sudah ada transaksi yang terkait dengannya. Produk yang sudah terjual sebaiknya diarsipkan atau ditandai tidak aktif.', 'type' => 'error'];
            header("Location: /cornerbites-sia/pages/produk.php");
            exit();
        }

        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        if ($stmt->execute([$product_id])) {
            $_SESSION['product_message'] = ['text' => 'Produk berhasil dihapus!', 'type' => 'success'];
        } else {
            $_SESSION['product_message'] = ['text' => 'Gagal menghapus produk.', 'type' => 'error'];
        }
        header("Location: /cornerbites-sia/pages/produk.php");
        exit();

    } else {
        // Jika diakses langsung tanpa POST/GET yang valid, redirect
        header("Location: /cornerbites-sia/pages/produk.php");
        exit();
    }

} catch (PDOException $e) {
    error_log("Error simpan/hapus produk: " . $e->getMessage());
    $_SESSION['product_message'] = ['text' => 'Terjadi kesalahan sistem: ' . $e->getMessage(), 'type' => 'error'];
    header("Location: /cornerbites-sia/pages/produk.php");
    exit();
}
