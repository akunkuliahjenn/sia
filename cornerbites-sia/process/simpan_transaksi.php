<?php
// process/simpan_transaksi.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

try {
    $conn = $db;
    $conn->beginTransaction();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $transaction_id = $_POST['transaction_id'] ?? null;
        $type = trim($_POST['type'] ?? '');
        $date = trim($_POST['date'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $amount = (float) ($_POST['amount'] ?? 0);
        $product_id = $_POST['product_id'] ?? null;
        $quantity = (int) ($_POST['quantity'] ?? 0);
        $created_by = $_SESSION['user_id'] ?? null;

        if (empty($date) || empty($description) || $amount <= 0 || !in_array($type, ['pemasukan', 'pengeluaran'])) {
            $_SESSION['transaction_message'] = ['text' => 'Data transaksi tidak lengkap atau tidak valid.', 'type' => 'error'];
            header("Location: /cornerbites-sia/pages/transaksi.php?type=$type");
            exit();
        }

        if ($type === 'pemasukan') {
            if (empty($product_id) || $quantity <= 0) {
                $_SESSION['transaction_message'] = ['text' => 'Untuk penjualan, produk dan jumlah harus diisi.', 'type' => 'error'];
                header("Location: /cornerbites-sia/pages/transaksi.php?type=$type");
                exit();
            }

            $stmtProduct = $conn->prepare("SELECT sale_price, stock FROM products WHERE id = ?");
            $stmtProduct->execute([$product_id]);
            $product = $stmtProduct->fetch();

            if (!$product) {
                $_SESSION['transaction_message'] = ['text' => 'Produk tidak ditemukan.', 'type' => 'error'];
                header("Location: /cornerbites-sia/pages/transaksi.php?type=$type");
                exit();
            }

            $old_quantity = 0;
            if ($transaction_id) {
                $stmtOld = $conn->prepare("SELECT quantity FROM transactions WHERE id = ?");
                $stmtOld->execute([$transaction_id]);
                $old_quantity = (int) $stmtOld->fetchColumn();
            }

            $current_stock = $product['stock'];
            $simulated_stock = $current_stock + $old_quantity;

            if ($simulated_stock < $quantity) {
                $_SESSION['transaction_message'] = ['text' => 'Stok tidak mencukupi. Stok tersedia: ' . $simulated_stock, 'type' => 'error'];
                header("Location: /cornerbites-sia/pages/transaksi.php?type=$type");
                exit();
            }

            $amount = $product['sale_price'] * $quantity;

            $stmtStock = $conn->prepare("UPDATE products SET stock = stock - ? + ? WHERE id = ?");
            $stmtStock->execute([$quantity, $old_quantity, $product_id]);

            if ($transaction_id) {
                $stmt = $conn->prepare("UPDATE transactions SET date=?, type=?, description=?, amount=?, product_id=?, quantity=?, created_by=? WHERE id=?");
                $stmt->execute([$date, $type, $description, $amount, $product_id, $quantity, $created_by, $transaction_id]);
                $_SESSION['transaction_message'] = ['text' => 'Transaksi berhasil diperbarui!', 'type' => 'success'];
            } else {
                $stmt = $conn->prepare("INSERT INTO transactions (date, type, description, amount, product_id, quantity, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$date, $type, $description, $amount, $product_id, $quantity, $created_by]);
                $_SESSION['transaction_message'] = ['text' => 'Transaksi baru berhasil disimpan!', 'type' => 'success'];
            }

        } else {
            $product_id = null;
            $quantity = null;

            if ($transaction_id) {
                $stmt = $conn->prepare("UPDATE transactions SET date=?, type=?, description=?, amount=?, product_id=?, quantity=?, created_by=? WHERE id=?");
                $stmt->execute([$date, $type, $description, $amount, $product_id, $quantity, $created_by, $transaction_id]);
                $_SESSION['transaction_message'] = ['text' => 'Transaksi berhasil diperbarui!', 'type' => 'success'];
            } else {
                $stmt = $conn->prepare("INSERT INTO transactions (date, type, description, amount, product_id, quantity, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$date, $type, $description, $amount, $product_id, $quantity, $created_by]);
                $_SESSION['transaction_message'] = ['text' => 'Transaksi baru berhasil disimpan!', 'type' => 'success'];
            }
        }

        $conn->commit();
        header("Location: /cornerbites-sia/pages/transaksi.php?type=$type");
        exit();

    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete') {
        $transaction_id = $_GET['id'] ?? null;
        $type_to_delete = $_GET['type'] ?? 'pemasukan';

        if (empty($transaction_id)) {
            $_SESSION['transaction_message'] = ['text' => 'ID transaksi tidak ditemukan.', 'type' => 'error'];
            header("Location: /cornerbites-sia/pages/transaksi.php?type=$type_to_delete");
            exit();
        }

        $stmtDetail = $conn->prepare("SELECT product_id, quantity, type FROM transactions WHERE id = ?");
        $stmtDetail->execute([$transaction_id]);
        $transaction = $stmtDetail->fetch();

        if ($transaction && $transaction['type'] === 'pemasukan' && !empty($transaction['product_id'])) {
            $stmtUpdateStock = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            if (!$stmtUpdateStock->execute([$transaction['quantity'], $transaction['product_id']])) {
                $conn->rollBack();
                $_SESSION['transaction_message'] = ['text' => 'Gagal mengembalikan stok. Transaksi tidak dihapus.', 'type' => 'error'];
                header("Location: /cornerbites-sia/pages/transaksi.php?type=$type_to_delete");
                exit();
            }
        }

        $stmtDelete = $conn->prepare("DELETE FROM transactions WHERE id = ?");
        $stmtDelete->execute([$transaction_id]);

        $conn->commit();
        $_SESSION['transaction_message'] = ['text' => 'Transaksi berhasil dihapus!', 'type' => 'success'];
        header("Location: /cornerbites-sia/pages/transaksi.php?type=$type_to_delete");
        exit();
    } else {
        header("Location: /cornerbites-sia/pages/transaksi.php");
        exit();
    }

} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Transaksi gagal: " . $e->getMessage());
    $_SESSION['transaction_message'] = ['text' => 'Terjadi kesalahan sistem: ' . $e->getMessage(), 'type' => 'error'];
    header("Location: /cornerbites-sia/pages/transaksi.php?type=" . ($_POST['type'] ?? ($_GET['type'] ?? 'pemasukan')));
    exit();
}
