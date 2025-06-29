
<?php
// pages/export_laporan.php
// File untuk export laporan ke Excel, CSV, dan PDF

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$export_format = $_POST['export'] ?? $_GET['export'] ?? '';
$search = $_POST['search'] ?? $_GET['search'] ?? '';
$date_filter = $_POST['date_filter'] ?? $_GET['date_filter'] ?? 'semua';
$custom_start = $_POST['custom_start'] ?? $_GET['custom_start'] ?? '';
$custom_end = $_POST['custom_end'] ?? $_GET['custom_end'] ?? '';

// Function to get all data without pagination limit
function getAllTransactionData($conn, $search, $date_filter, $custom_start, $custom_end) {
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
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($export_format == 'excel') {
    // Export Excel - Fixed to get all data
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment;filename="jurnal_umum_' . date('Y-m-d_H-i-s') . '.xls"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    
    echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
    echo "<meta charset='UTF-8'>\n";
    echo "<style>";
    echo "table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }";
    echo "th { background-color: #f3f4f6; font-weight: bold; padding: 12px 8px; border: 1px solid #d1d5db; text-align: left; }";
    echo "td { padding: 8px; border: 1px solid #d1d5db; }";
    echo ".number { text-align: right; }";
    echo ".center { text-align: center; }";
    echo ".total-row { background-color: #e5e7eb; font-weight: bold; }";
    echo "</style>";
    echo "<table>";
    echo "<tr>";
    echo "<th>Tanggal</th>";
    echo "<th>Deskripsi</th>";
    echo "<th>Tipe</th>";
    echo "<th>Debit (Rp)</th>";
    echo "<th>Kredit (Rp)</th>";
    echo "</tr>";
    
    try {
        $conn = $db;
        $data = getAllTransactionData($conn, $search, $date_filter, $custom_start, $custom_end);
        
        $totalDebit = 0;
        $totalKredit = 0;
        
        foreach ($data as $row) {
            echo "<tr>";
            echo "<td>" . date('d/m/Y', strtotime($row['date'])) . "</td>";
            echo "<td>" . htmlspecialchars($row['description']) . "</td>";
            echo "<td class='center'>" . ucfirst($row['type']) . "</td>";
            
            if ($row['type'] == 'pemasukan') {
                echo "<td class='number'>" . $row['amount'] . "</td>";
                echo "<td class='number'>-</td>";
                $totalDebit += $row['amount'];
            } else {
                echo "<td class='number'>-</td>";
                echo "<td class='number'>" . $row['amount'] . "</td>";
                $totalKredit += $row['amount'];
            }
            echo "</tr>";
        }
        
        // Total row
        echo "<tr class='total-row'>";
        echo "<td colspan='3'>TOTAL:</td>";
        echo "<td class='number'>" . number_format($totalDebit, 0, ',', '.') . "</td>";
        echo "<td class='number'>" . number_format($totalKredit, 0, ',', '.') . "</td>";
        echo "</tr>";
        
        // Summary info
        echo "<tr><td colspan='5'></td></tr>";
        echo "<tr><td colspan='5'><strong>Total Transaksi: " . count($data) . "</strong></td></tr>";
        echo "<tr><td colspan='5'>Diekspor pada: " . date('d/m/Y H:i:s') . "</td></tr>";
        
    } catch (PDOException $e) {
        echo "<tr><td colspan='5' style='color: red;'>Error: " . $e->getMessage() . "</td></tr>";
    }
    
    echo "</table>";
    
} elseif ($export_format == 'csv') {
    // Export CSV - Fixed to get all data
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment;filename="jurnal_umum_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    
    // Output UTF-8 BOM for proper encoding
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // Header CSV
    fputcsv($output, ['Tanggal', 'Deskripsi', 'Tipe', 'Debit (Rp)', 'Kredit (Rp)'], ';');
    
    try {
        $conn = $db;
        $data = getAllTransactionData($conn, $search, $date_filter, $custom_start, $custom_end);
        
        $totalDebit = 0;
        $totalKredit = 0;
        
        foreach ($data as $row) {
            $debit = ($row['type'] == 'pemasukan') ? $row['amount'] : 0;
            $kredit = ($row['type'] == 'pengeluaran') ? $row['amount'] : 0;
            
            if ($row['type'] == 'pemasukan') {
                $totalDebit += $row['amount'];
            } else {
                $totalKredit += $row['amount'];
            }
            
            fputcsv($output, [
                date('d/m/Y', strtotime($row['date'])),
                $row['description'],
                ucfirst($row['type']),
                $debit ? number_format($debit, 0, ',', '.') : 0,
                $kredit ? number_format($kredit, 0, ',', '.') : 0
            ], ';');
        }
        
        // Total row
        fputcsv($output, [
            '',
            '',
            'TOTAL:',
            number_format($totalDebit, 0, ',', '.'),
            number_format($totalKredit, 0, ',', '.')
        ], ';');
        
        // Summary info
        fputcsv($output, ['', '', '', '', ''], ';');
        fputcsv($output, ['Total Transaksi: ' . count($data), '', '', '', ''], ';');
        fputcsv($output, ['Diekspor pada: ' . date('d/m/Y H:i:s'), '', '', '', ''], ';');
        
    } catch (PDOException $e) {
        fputcsv($output, ['Error: ' . $e->getMessage(), '', '', '', ''], ';');
    }
    
    fclose($output);
    
} elseif ($export_format == 'pdf') {
    // Export PDF using HTML print-friendly format
    header('Content-Type: text/html; charset=utf-8');
    
    echo '<!DOCTYPE html>';
    echo '<html><head>';
    echo '<meta charset="UTF-8">';
    echo '<title>Jurnal Umum - Corner Bites</title>';
    echo '<style>';
    echo 'body { font-family: "Arial", sans-serif; margin: 0; padding: 20px; background: #fff; color: #333; }';
    echo '.header { text-align: center; margin-bottom: 40px; border-bottom: 3px solid #3b82f6; padding-bottom: 25px; background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); border-radius: 8px; padding: 30px; }';
    echo '.company-name { font-size: 28px; font-weight: bold; color: #1e40af; margin-bottom: 8px; letter-spacing: 1px; }';
    echo '.report-title { font-size: 20px; color: #475569; margin-bottom: 12px; font-weight: 600; }';
    echo '.report-period { font-size: 14px; color: #64748b; background: #e0f2fe; padding: 8px 16px; border-radius: 20px; display: inline-block; }';
    echo 'table { width: 100%; border-collapse: collapse; margin-top: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; }';
    echo 'th { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; padding: 16px 12px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border: none; }';
    echo 'td { padding: 12px; border-bottom: 1px solid #e2e8f0; font-size: 14px; }';
    echo 'tr:nth-child(even) { background-color: #f8fafc; }';
    echo 'tr:hover { background-color: #e0f2fe; }';
    echo '.number { text-align: right; font-weight: 500; font-family: "Courier New", monospace; }';
    echo '.center { text-align: center; }';
    echo '.total-row { background: linear-gradient(135deg, #059669 0%, #047857 100%) !important; color: white; font-weight: bold; }';
    echo '.total-row td { border: none; padding: 16px 12px; font-size: 15px; }';
    echo '.footer { margin-top: 40px; font-size: 12px; color: #64748b; border-top: 2px solid #e2e8f0; padding-top: 20px; background: #f8fafc; padding: 20px; border-radius: 8px; }';
    echo '.footer div { margin-bottom: 8px; }';
    echo '@media print { body { margin: 0; padding: 15px; } .header { break-inside: avoid; } }';
    echo '@page { margin: 1in; }';
    echo '</style>';
    echo '<script>window.onload = function(){ window.print(); }</script>';
    echo '</head><body>';
    
    echo '<div class="header">';
    echo '<div class="company-name">CORNER BITES</div>';
    echo '<div class="report-title">JURNAL UMUM</div>';
    
    $period = 'Semua Periode';
    if ($date_filter == 'hari_ini') {
        $period = 'Hari Ini - ' . date('d/m/Y');
    } elseif ($date_filter == 'bulan_ini') {
        $period = 'Bulan ' . date('F Y');
    } elseif ($date_filter == 'tahun_ini') {
        $period = 'Tahun ' . date('Y');
    } elseif ($date_filter == 'custom' && !empty($custom_start) && !empty($custom_end)) {
        $period = date('d/m/Y', strtotime($custom_start)) . ' s/d ' . date('d/m/Y', strtotime($custom_end));
    }
    echo '<div class="report-period">Periode: ' . $period . '</div>';
    echo '</div>';
    
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th style="width: 12%;">Tanggal</th>';
    echo '<th style="width: 40%;">Deskripsi</th>';
    echo '<th style="width: 12%;">Tipe</th>';
    echo '<th style="width: 18%;">Debit (Rp)</th>';
    echo '<th style="width: 18%;">Kredit (Rp)</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    try {
        $conn = $db;
        $data = getAllTransactionData($conn, $search, $date_filter, $custom_start, $custom_end);
        
        $totalDebit = 0;
        $totalKredit = 0;
        
        foreach ($data as $row) {
            echo '<tr>';
            echo '<td>' . date('d/m/Y', strtotime($row['date'])) . '</td>';
            echo '<td>' . htmlspecialchars($row['description']) . '</td>';
            echo '<td class="center">' . ucfirst($row['type']) . '</td>';
            
            if ($row['type'] == 'pemasukan') {
                echo '<td class="number">Rp ' . number_format($row['amount'], 0, ',', '.') . '</td>';
                echo '<td class="number">-</td>';
                $totalDebit += $row['amount'];
            } else {
                echo '<td class="number">-</td>';
                echo '<td class="number">Rp ' . number_format($row['amount'], 0, ',', '.') . '</td>';
                $totalKredit += $row['amount'];
            }
            echo '</tr>';
        }
        
        // Total row
        echo '<tr class="total-row">';
        echo '<td colspan="3"><strong>TOTAL</strong></td>';
        echo '<td class="number"><strong>Rp ' . number_format($totalDebit, 0, ',', '.') . '</strong></td>';
        echo '<td class="number"><strong>Rp ' . number_format($totalKredit, 0, ',', '.') . '</strong></td>';
        echo '</tr>';
        
    } catch (PDOException $e) {
        echo '<tr><td colspan="5" style="color: red; text-align: center;">Error: ' . $e->getMessage() . '</td></tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
    echo '<div class="footer">';
    echo '<div><strong>Total Transaksi:</strong> ' . (isset($data) ? count($data) : 0) . '</div>';
    echo '<div><strong>Dicetak pada:</strong> ' . date('d/m/Y H:i:s') . '</div>';
    echo '<div><strong>Dicetak oleh:</strong> ' . htmlspecialchars($_SESSION['username'] ?? 'System') . '</div>';
    echo '</div>';
    
    echo '</body></html>';
    
} else {
    // Invalid format
    echo "Format export tidak valid.";
}
?>
