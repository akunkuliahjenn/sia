
<?php
// pages/export_laporan.php
// File untuk export laporan ke PDF dan Excel

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$export_format = $_GET['export'] ?? '';
$search = $_GET['search'] ?? '';
$date_filter = $_GET['date_filter'] ?? 'semua';
$custom_start = $_GET['custom_start'] ?? '';
$custom_end = $_GET['custom_end'] ?? '';

if ($export_format == 'excel') {
    // Export Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="jurnal_umum_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo "<table border='1'>";
    echo "<tr><th>Tanggal</th><th>Deskripsi</th><th>Tipe</th><th>Debit</th><th>Kredit</th></tr>";
    
    try {
        $conn = $db;
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $whereClause .= " AND (description LIKE :search OR amount LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if ($date_filter == 'hari_ini') {
            $whereClause .= " AND DATE(date) = CURDATE()";
        } elseif ($date_filter == 'bulan_ini') {
            $whereClause .= " AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
        } elseif ($date_filter == 'tahun_ini') {
            $whereClause .= " AND YEAR(date) = YEAR(CURDATE())";
        } elseif ($date_filter == 'custom' && !empty($custom_start) && !empty($custom_end)) {
            $whereClause .= " AND DATE(date) BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $custom_start;
            $params[':end_date'] = $custom_end;
        }
        
        $query = "SELECT date, description, type, amount FROM transactions " . $whereClause . " ORDER BY date DESC";
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($data as $row) {
            echo "<tr>";
            echo "<td>" . date('d/m/Y', strtotime($row['date'])) . "</td>";
            echo "<td>" . htmlspecialchars($row['description']) . "</td>";
            echo "<td>" . ucfirst($row['type']) . "</td>";
            echo "<td>" . ($row['type'] == 'pemasukan' ? number_format($row['amount'], 0, ',', '.') : '-') . "</td>";
            echo "<td>" . ($row['type'] == 'pengeluaran' ? number_format($row['amount'], 0, ',', '.') : '-') . "</td>";
            echo "</tr>";
        }
    } catch (PDOException $e) {
        echo "<tr><td colspan='5'>Error: " . $e->getMessage() . "</td></tr>";
    }
    
    echo "</table>";
    
} elseif ($export_format == 'pdf') {
    // Export PDF (sederhana menggunakan HTML to PDF)
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment;filename="jurnal_umum_' . date('Y-m-d') . '.pdf"');
    
    echo '<html><head><title>Jurnal Umum</title></head><body>';
    echo '<h1>Jurnal Umum - ' . date('d/m/Y') . '</h1>';
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr><th>Tanggal</th><th>Deskripsi</th><th>Tipe</th><th>Debit</th><th>Kredit</th></tr>';
    
    try {
        $conn = $db;
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $whereClause .= " AND (description LIKE :search OR amount LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if ($date_filter == 'hari_ini') {
            $whereClause .= " AND DATE(date) = CURDATE()";
        } elseif ($date_filter == 'bulan_ini') {
            $whereClause .= " AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
        } elseif ($date_filter == 'tahun_ini') {
            $whereClause .= " AND YEAR(date) = YEAR(CURDATE())";
        } elseif ($date_filter == 'custom' && !empty($custom_start) && !empty($custom_end)) {
            $whereClause .= " AND DATE(date) BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $custom_start;
            $params[':end_date'] = $custom_end;
        }
        
        $query = "SELECT date, description, type, amount FROM transactions " . $whereClause . " ORDER BY date DESC";
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($data as $row) {
            echo "<tr>";
            echo "<td>" . date('d/m/Y', strtotime($row['date'])) . "</td>";
            echo "<td>" . htmlspecialchars($row['description']) . "</td>";
            echo "<td>" . ucfirst($row['type']) . "</td>";
            echo "<td>" . ($row['type'] == 'pemasukan' ? 'Rp ' . number_format($row['amount'], 0, ',', '.') : '-') . "</td>";
            echo "<td>" . ($row['type'] == 'pengeluaran' ? 'Rp ' . number_format($row['amount'], 0, ',', '.') : '-') . "</td>";
            echo "</tr>";
        }
    } catch (PDOException $e) {
        echo "<tr><td colspan='5'>Error: " . $e->getMessage() . "</td></tr>";
    }
    
    echo '</table></body></html>';
}
?>
