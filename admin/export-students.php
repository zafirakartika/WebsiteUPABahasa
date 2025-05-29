<?php
// admin/export-students.php
require_once '../config/database.php';
requireRole('admin');

// Get export parameters
$format = $_GET['format'] ?? 'excel';
$status = $_GET['status'] ?? 'all';
$level = $_GET['level'] ?? '';
$faculty = $_GET['faculty'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$include_stats = isset($_GET['include_stats']);
$include_course = isset($_GET['include_course']);

// Build query with same filters as main page
$sql = "
    SELECT u.*, 
           COUNT(DISTINCT er.id) as total_registrations,
           COUNT(DISTINCT res.id) as total_results,
           MAX(res.total_score) as best_score,
           COUNT(DISTINCT CASE WHEN res.is_passed = 1 THEN res.id END) as passed_tests,
           c.id as course_id, c.status as course_status, c.current_session, c.total_sessions, c.final_test_date
    FROM users u 
    LEFT JOIN elpt_registrations er ON u.id = er.user_id
    LEFT JOIN elpt_results res ON u.id = res.user_id
    LEFT JOIN courses c ON u.id = c.user_id
    WHERE u.role = 'student'
";

$params = [];

if ($status !== 'all') {
    $sql .= " AND u.is_active = ?";
    $params[] = $status === 'active' ? 1 : 0;
}

if (!empty($level)) {
    $sql .= " AND u.level = ?";
    $params[] = $level;
}

if (!empty($faculty)) {
    $sql .= " AND u.faculty = ?";
    $params[] = $faculty;
}

if (!empty($start_date)) {
    $sql .= " AND u.created_at >= ?";
    $params[] = $start_date . ' 00:00:00';
}

if (!empty($end_date)) {
    $sql .= " AND u.created_at <= ?";
    $params[] = $end_date . ' 23:59:59';
}

$sql .= " GROUP BY u.id ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

if (empty($students)) {
    showAlert('Tidak ada data untuk diekspor', 'warning');
    header('Location: students.php');
    exit;
}

// Export based on format
switch ($format) {
    case 'excel':
        exportToExcel($students, $include_stats, $include_course);
        break;
    case 'csv':
        exportToCSV($students, $include_stats, $include_course);
        break;
    case 'pdf':
        exportToPDF($students, $include_stats, $include_course);
        break;
    default:
        showAlert('Format export tidak valid', 'error');
        header('Location: students.php');
        exit;
}

function exportToExcel($students, $include_stats, $include_course) {
    $filename = 'Data_Mahasiswa_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    // Simple Excel export using HTML table format
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Data Mahasiswa</title>
        <style>
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #000; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
        </style>
    </head>
    <body>
        <h2>Data Mahasiswa UPA Bahasa UPNVJ</h2>
        <p>Exported on: ' . date('d F Y H:i:s') . '</p>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>NIM</th>
                    <th>Email</th>
                    <th>No. Telepon</th>
                    <th>Program Studi</th>
                    <th>Jenjang</th>
                    <th>Fakultas</th>
                    <th>Status</th>
                    <th>Tanggal Bergabung</th>';
    
    if ($include_stats) {
        echo '<th>Total Pendaftaran</th>
              <th>Total Tes</th>
              <th>Tes Lulus</th>
              <th>Best Score</th>';
    }
    
    if ($include_course) {
        echo '<th>Status Kursus</th>
              <th>Progress Kursus</th>
              <th>Final Test Date</th>';
    }
    
    echo '</tr>
            </thead>
            <tbody>';
    
    $no = 1;
    foreach ($students as $student) {
        echo '<tr>
                <td>' . $no++ . '</td>
                <td>' . htmlspecialchars($student['name']) . '</td>
                <td>' . htmlspecialchars($student['nim']) . '</td>
                <td>' . htmlspecialchars($student['email']) . '</td>
                <td>' . htmlspecialchars($student['no_telpon'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($student['program']) . '</td>
                <td>' . htmlspecialchars($student['level']) . '</td>
                <td>' . htmlspecialchars($student['faculty']) . '</td>
                <td>' . ($student['is_active'] ? 'Aktif' : 'Nonaktif') . '</td>
                <td>' . date('d/m/Y H:i', strtotime($student['created_at'])) . '</td>';
        
        if ($include_stats) {
            echo '<td>' . $student['total_registrations'] . '</td>
                  <td>' . $student['total_results'] . '</td>
                  <td>' . $student['passed_tests'] . '</td>
                  <td>' . ($student['best_score'] ?? 'N/A') . '</td>';
        }
        
        if ($include_course) {
            echo '<td>' . ($student['course_status'] ? strtoupper($student['course_status']) : 'Belum Ikut') . '</td>
                  <td>' . ($student['course_status'] ? $student['current_session'] . '/' . $student['total_sessions'] : 'N/A') . '</td>
                  <td>' . ($student['final_test_date'] ? date('d/m/Y', strtotime($student['final_test_date'])) : 'N/A') . '</td>';
        }
        
        echo '</tr>';
    }
    
    echo '</tbody>
        </table>
        <br>
        <p>Total Records: ' . count($students) . '</p>
    </body>
    </html>';
}

function exportToCSV($students, $include_stats, $include_course) {
    $filename = 'Data_Mahasiswa_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Headers
    $headers = ['No', 'Nama', 'NIM', 'Email', 'No. Telepon', 'Program Studi', 'Jenjang', 'Fakultas', 'Status', 'Tanggal Bergabung'];
    
    if ($include_stats) {
        $headers = array_merge($headers, ['Total Pendaftaran', 'Total Tes', 'Tes Lulus', 'Best Score']);
    }
    
    if ($include_course) {
        $headers = array_merge($headers, ['Status Kursus', 'Progress Kursus', 'Final Test Date']);
    }
    
    fputcsv($output, $headers);
    
    // Data
    $no = 1;
    foreach ($students as $student) {
        $row = [
            $no++,
            $student['name'],
            $student['nim'],
            $student['email'],
            $student['no_telpon'] ?? 'N/A',
            $student['program'],
            $student['level'],
            $student['faculty'],
            $student['is_active'] ? 'Aktif' : 'Nonaktif',
            date('d/m/Y H:i', strtotime($student['created_at']))
        ];
        
        if ($include_stats) {
            $row = array_merge($row, [
                $student['total_registrations'],
                $student['total_results'],
                $student['passed_tests'],
                $student['best_score'] ?? 'N/A'
            ]);
        }
        
        if ($include_course) {
            $row = array_merge($row, [
                $student['course_status'] ? strtoupper($student['course_status']) : 'Belum Ikut',
                $student['course_status'] ? $student['current_session'] . '/' . $student['total_sessions'] : 'N/A',
                $student['final_test_date'] ? date('d/m/Y', strtotime($student['final_test_date'])) : 'N/A'
            ]);
        }
        
        fputcsv($output, $row);
    }
    
    fclose($output);
}

function exportToPDF($students, $include_stats, $include_course) {
    $filename = 'Data_Mahasiswa_' . date('Y-m-d_H-i-s') . '.pdf';
    
    // Simple HTML to PDF (fallback method)
    header('Content-Type: text/html; charset=utf-8');
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Data Mahasiswa - UPA Bahasa UPNVJ</title>
        <style>
            @page { 
                size: A4 landscape; 
                margin: 10mm; 
            }
            body { 
                font-family: Arial, sans-serif; 
                font-size: 10px; 
                margin: 0; 
                padding: 0;
            }
            .header { 
                text-align: center; 
                margin-bottom: 20px; 
                border-bottom: 2px solid #333;
                padding-bottom: 10px;
            }
            table { 
                border-collapse: collapse; 
                width: 100%; 
                font-size: 9px;
            }
            th, td { 
                border: 1px solid #ddd; 
                padding: 4px; 
                text-align: left; 
                vertical-align: top;
            }
            th { 
                background-color: #f2f2f2; 
                font-weight: bold; 
                text-align: center;
            }
            .footer {
                margin-top: 20px;
                text-align: center;
                border-top: 1px solid #ddd;
                padding-top: 10px;
                font-size: 8px;
            }
            .print-btn {
                position: fixed;
                top: 10px;
                right: 10px;
                padding: 10px 20px;
                background: #007bff;
                color: white;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                z-index: 1000;
            }
            @media print {
                .print-btn { display: none; }
                body { margin: 0; }
            }
        </style>
    </head>
    <body>
        <button class="print-btn" onclick="window.print()">Print / Save as PDF</button>
        
        <div class="header">
            <h2>DATA MAHASISWA UPA BAHASA UPNVJ</h2>
            <p>Exported on: ' . date('d F Y H:i:s') . ' | Total Records: ' . count($students) . '</p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 3%;">No</th>
                    <th style="width: 15%;">Nama</th>
                    <th style="width: 8%;">NIM</th>
                    <th style="width: 12%;">Email</th>
                    <th style="width: 15%;">Program Studi</th>
                    <th style="width: 5%;">Jenjang</th>
                    <th style="width: 12%;">Fakultas</th>
                    <th style="width: 6%;">Status</th>
                    <th style="width: 8%;">Bergabung</th>';
    
    if ($include_stats) {
        echo '<th style="width: 4%;">Reg</th>
              <th style="width: 4%;">Tes</th>
              <th style="width: 4%;">Score</th>';
    }
    
    if ($include_course) {
        echo '<th style="width: 6%;">Kursus</th>
              <th style="width: 8%;">Progress</th>';
    }
    
    echo '</tr>
            </thead>
            <tbody>';
    
    $no = 1;
    foreach ($students as $student) {
        echo '<tr>
                <td>' . $no++ . '</td>
                <td>' . htmlspecialchars($student['name']) . '</td>
                <td>' . htmlspecialchars($student['nim']) . '</td>
                <td style="font-size: 8px;">' . htmlspecialchars($student['email']) . '</td>
                <td>' . htmlspecialchars($student['program']) . '</td>
                <td>' . htmlspecialchars($student['level']) . '</td>
                <td>' . htmlspecialchars($student['faculty']) . '</td>
                <td>' . ($student['is_active'] ? 'Aktif' : 'Nonaktif') . '</td>
                <td>' . date('d/m/Y', strtotime($student['created_at'])) . '</td>';
        
        if ($include_stats) {
            echo '<td>' . $student['total_registrations'] . '</td>
                  <td>' . $student['total_results'] . '</td>
                  <td>' . ($student['best_score'] ?? '-') . '</td>';
        }
        
        if ($include_course) {
            echo '<td>' . ($student['course_status'] ? strtoupper($student['course_status']) : '-') . '</td>
                  <td>' . ($student['course_status'] ? $student['current_session'] . '/' . $student['total_sessions'] : '-') . '</td>';
        }
        
        echo '</tr>';
    }
    
    echo '</tbody>
        </table>
        
        <div class="footer">
            <p><strong>UPA Bahasa UPNVJ</strong> - Unit Pelayanan Akademik Bahasa</p>
            <p>Universitas Pembangunan Nasional Veteran Jakarta</p>
        </div>
        
        <script>
            // Auto print after page loads (optional)
            // setTimeout(() => window.print(), 1000);
        </script>
    </body>
    </html>';
}
?>