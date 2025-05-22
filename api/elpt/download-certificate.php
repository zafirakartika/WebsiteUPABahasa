<?php
// download-certificate.php
require_once 'config/database.php';
requireLogin();

// Get result ID from URL
$result_id = $_GET['id'] ?? null;

if (!$result_id || !is_numeric($result_id)) {
    header('Location: student/test-results.php');
    exit;
}

try {
    // Get test result with user data
    $stmt = $pdo->prepare("
        SELECT r.*, u.name, u.nim, u.program_studi, u.fakultas, u.jenjang,
               reg.keperluan, reg.test_date as registration_date
        FROM elpt_results r 
        JOIN users u ON r.user_id = u.id 
        JOIN elpt_registrations reg ON r.registration_id = reg.id
        WHERE r.id = ?
    ");
    $stmt->execute([$result_id]);
    $result = $stmt->fetch();
    
    if (!$result) {
        showAlert('Hasil tes tidak ditemukan', 'error');
        header('Location: student/test-results.php');
        exit;
    }
    
    // Check if user owns this result (for students)
    if ($_SESSION['user_role'] === 'student' && $result['user_id'] != $_SESSION['user_id']) {
        showAlert('Anda tidak memiliki akses ke sertifikat ini', 'error');
        header('Location: student/test-results.php');
        exit;
    }
    
    // Check if score meets minimum requirement
    if ($result['total_score'] < 450) {
        showAlert('Skor belum mencapai batas minimum untuk mendapatkan sertifikat', 'warning');
        header('Location: student/test-results.php');
        exit;
    }
    
    // Generate certificate HTML
    $certificate_html = generateCertificateHTML($result);
    
    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="ELPT_Certificate_' . $result['name'] . '_' . date('Y-m-d', strtotime($result['test_date'])) . '.pdf"');
    
    // Generate PDF using simple HTML to PDF conversion
    // For production, you might want to use libraries like TCPDF, FPDF, or mPDF
    generatePDF($certificate_html, $result);
    
} catch (Exception $e) {
    showAlert('Terjadi kesalahan saat mengunduh sertifikat', 'error');
    header('Location: student/test-results.php');
    exit;
}

function generateCertificateHTML($result) {
    $test_date = date('d F Y', strtotime($result['test_date']));
    $issue_date = date('d F Y');
    $certificate_id = 'ELPT-' . $result['id'] . '-' . date('Y');
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>ELPT Certificate</title>
        <style>
            body {
                font-family: "Times New Roman", serif;
                margin: 0;
                padding: 40px;
                background: white;
                color: #333;
            }
            .certificate {
                max-width: 800px;
                margin: 0 auto;
                border: 8px solid #2c5aa0;
                padding: 60px;
                text-align: center;
                background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            }
            .header {
                margin-bottom: 40px;
            }
            .logo {
                font-size: 28px;
                font-weight: bold;
                color: #2c5aa0;
                margin-bottom: 10px;
            }
            .university {
                font-size: 16px;
                color: #666;
                margin-bottom: 5px;
            }
            .title {
                font-size: 36px;
                font-weight: bold;
                color: #1a365d;
                margin: 40px 0 20px 0;
                text-transform: uppercase;
                letter-spacing: 2px;
            }
            .subtitle {
                font-size: 20px;
                color: #4a5568;
                margin-bottom: 40px;
            }
            .content {
                margin: 40px 0;
                line-height: 1.8;
            }
            .certify-text {
                font-size: 18px;
                margin-bottom: 30px;
            }
            .student-name {
                font-size: 32px;
                font-weight: bold;
                color: #2c5aa0;
                margin: 20px 0;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            .student-info {
                font-size: 16px;
                margin: 20px 0;
                color: #666;
            }
            .achievement-text {
                font-size: 18px;
                margin: 30px 0;
            }
            .scores-table {
                margin: 30px auto;
                border-collapse: collapse;
                width: 60%;
            }
            .scores-table th,
            .scores-table td {
                border: 1px solid #ddd;
                padding: 12px;
                text-align: center;
            }
            .scores-table th {
                background-color: #2c5aa0;
                color: white;
                font-weight: bold;
            }
            .total-score {
                font-size: 24px;
                font-weight: bold;
                color: #2c5aa0;
                margin: 30px 0;
            }
            .signatures {
                margin-top: 80px;
                display: flex;
                justify-content: space-between;
            }
            .signature {
                text-align: center;
                width: 200px;
            }
            .signature-line {
                border-top: 2px solid #333;
                margin-top: 60px;
                padding-top: 10px;
                font-weight: bold;
            }
            .footer {
                margin-top: 40px;
                font-size: 12px;
                color: #666;
                border-top: 1px solid #ddd;
                padding-top: 20px;
            }
            .certificate-id {
                font-size: 14px;
                color: #888;
                margin-top: 20px;
            }
            .seal {
                position: absolute;
                right: 100px;
                top: 200px;
                width: 100px;
                height: 100px;
                border: 3px solid #2c5aa0;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                color: #2c5aa0;
                font-weight: bold;
                text-align: center;
                line-height: 1.2;
            }
        </style>
    </head>
    <body>
        <div class="certificate">
            <div class="header">
                <div class="logo">UPA BAHASA UPNVJ</div>
                <div class="university">Unit Pelayanan Akademik Bahasa</div>
                <div class="university">Universitas Pembangunan Nasional Veteran Jakarta</div>
            </div>
            
            <div class="title">Certificate of Achievement</div>
            <div class="subtitle">English Language Proficiency Test (ELPT)</div>
            
            <div class="content">
                <div class="certify-text">This is to certify that</div>
                
                <div class="student-name">' . strtoupper($result['name']) . '</div>
                
                <div class="student-info">
                    NIM: ' . $result['nim'] . '<br>
                    ' . $result['program_studi'] . ' - ' . $result['jenjang'] . '<br>
                    ' . $result['fakultas'] . '
                </div>
                
                <div class="achievement-text">
                    has successfully completed the English Language Proficiency Test<br>
                    conducted on ' . $test_date . ' with the following results:
                </div>
                
                <table class="scores-table">
                    <tr>
                        <th>Test Section</th>
                        <th>Score</th>
                    </tr>
                    <tr>
                        <td>Listening Comprehension</td>
                        <td>' . $result['listening_score'] . '</td>
                    </tr>
                    <tr>
                        <td>Structure & Written Expression</td>
                        <td>' . $result['structure_score'] . '</td>
                    </tr>
                    <tr>
                        <td>Reading Comprehension</td>
                        <td>' . $result['reading_score'] . '</td>
                    </tr>
                </table>
                
                <div class="total-score">
                    Total Score: ' . $result['total_score'] . ' / 750
                </div>
                
                <div class="achievement-text">
                    This certificate demonstrates proficiency in English language skills<br>
                    as measured by the ELPT assessment and meets the minimum<br>
                    requirement for academic purposes at UPN Veteran Jakarta.
                </div>
            </div>
            
            <div class="signatures">
                <div class="signature">
                    <div class="signature-line">
                        Director<br>
                        UPA Bahasa UPNVJ
                    </div>
                </div>
                <div class="signature">
                    <div class="signature-line">
                        Academic Coordinator<br>
                        UPA Bahasa UPNVJ
                    </div>
                </div>
            </div>
            
            <div class="footer">
                Certificate issued on: ' . $issue_date . '<br>
                This certificate is valid for academic purposes at UPN Veteran Jakarta<br>
                For verification, please contact UPA Bahasa UPNVJ at upabahasa@upnvj.ac.id
            </div>
            
            <div class="certificate-id">
                Certificate ID: ' . $certificate_id . '
            </div>
            
            <div class="seal">
                OFFICIAL<br>
                SEAL<br>
                UPA BAHASA<br>
                UPNVJ
            </div>
        </div>
    </body>
    </html>';
}

function generatePDF($html, $result) {
    // Simple HTML to PDF conversion
    // For production, replace this with a proper PDF library like TCPDF or mPDF
    
    // Option 1: Use DomPDF (if available)
    if (class_exists('Dompdf\Dompdf')) {
        $dompdf = new Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('certificate.pdf', array('Attachment' => 1));
        return;
    }
    
    // Option 2: Use wkhtmltopdf (if available)
    if (shell_exec('which wkhtmltopdf')) {
        $temp_html = tempnam(sys_get_temp_dir(), 'cert') . '.html';
        $temp_pdf = tempnam(sys_get_temp_dir(), 'cert') . '.pdf';
        
        file_put_contents($temp_html, $html);
        
        $command = "wkhtmltopdf --page-size A4 --orientation Landscape --margin-top 10mm --margin-bottom 10mm --margin-left 10mm --margin-right 10mm '$temp_html' '$temp_pdf'";
        exec($command);
        
        if (file_exists($temp_pdf)) {
            readfile($temp_pdf);
            unlink($temp_html);
            unlink($temp_pdf);
            return;
        }
    }
    
    // Option 3: Fallback - output HTML with print styles
    header('Content-Type: text/html');
    header('Content-Disposition: inline');
    
    echo '
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #2c5aa0;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            z-index: 1000;
        }
    </style>
    <button class="print-button no-print" onclick="window.print()">Print Certificate</button>
    <script>
        // Auto-print after page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000);
        };
    </script>
    ';
    
    echo $html;
}
?>