<?php
// download-certificate.php - Certificate download handler
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
        WHERE r.id = ? AND r.is_passed = 1
    ");
    $stmt->execute([$result_id]);
    $result = $stmt->fetch();
    
    if (!$result) {
        showAlert('Hasil tes tidak ditemukan atau belum lulus', 'error');
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
    if ($result['total_score'] < MIN_PASSING_SCORE) {
        showAlert('Skor belum mencapai batas minimum untuk mendapatkan sertifikat', 'warning');
        header('Location: student/test-results.php');
        exit;
    }
    
    // Log certificate download
    logActivity('certificate_download', "Downloaded certificate for result ID: $result_id");
    
    // Update download count
    $stmt = $pdo->prepare("UPDATE certificates SET download_count = download_count + 1 WHERE result_id = ?");
    $stmt->execute([$result_id]);
    
    // Generate certificate
    generateCertificatePDF($result);
    
} catch (Exception $e) {
    error_log('Certificate download error: ' . $e->getMessage());
    showAlert('Terjadi kesalahan saat mengunduh sertifikat', 'error');
    header('Location: student/test-results.php');
    exit;
}

function generateCertificatePDF($result) {
    // For production, use a proper PDF library like TCPDF, FPDF, or mPDF
    // This is a simplified version using HTML output
    
    $certificate_id = $result['certificate_number'] ?: 'ELPT-' . $result['id'] . '-' . date('Y', strtotime($result['test_date']));
    $test_date = formatDate($result['test_date']);
    $issue_date = formatDate(date('Y-m-d'));
    
    // Certificate HTML
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>ELPT Certificate - ' . htmlspecialchars($result['name']) . '</title>
        <style>
            @page {
                size: A4 landscape;
                margin: 0;
            }
            body {
                font-family: "Times New Roman", serif;
                margin: 0;
                padding: 40px;
                background: white;
                color: #333;
            }
            .certificate {
                max-width: 1000px;
                margin: 0 auto;
                border: 15px solid #2c5aa0;
                padding: 60px;
                text-align: center;
                position: relative;
                background: #fff;
            }
            .watermark {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) rotate(-45deg);
                font-size: 200px;
                color: rgba(44, 90, 160, 0.05);
                font-weight: bold;
                z-index: 0;
            }
            .content {
                position: relative;
                z-index: 1;
            }
            .header {
                margin-bottom: 30px;
            }
            .logo-container {
                margin-bottom: 20px;
            }
            .logo {
                font-size: 32px;
                font-weight: bold;
                color: #2c5aa0;
                margin-bottom: 10px;
            }
            .university {
                font-size: 18px;
                color: #666;
                margin-bottom: 5px;
            }
            .title {
                font-size: 48px;
                font-weight: bold;
                color: #1a365d;
                margin: 30px 0 20px 0;
                text-transform: uppercase;
                letter-spacing: 3px;
            }
            .subtitle {
                font-size: 24px;
                color: #4a5568;
                margin-bottom: 40px;
            }
            .certify-text {
                font-size: 20px;
                margin-bottom: 30px;
                font-style: italic;
            }
            .student-name {
                font-size: 36px;
                font-weight: bold;
                color: #2c5aa0;
                margin: 30px 0;
                text-transform: uppercase;
                letter-spacing: 2px;
                border-bottom: 3px solid #2c5aa0;
                display: inline-block;
                padding-bottom: 10px;
            }
            .student-info {
                font-size: 18px;
                margin: 20px 0;
                color: #666;
            }
            .achievement-text {
                font-size: 20px;
                margin: 30px 0;
                line-height: 1.6;
            }
            .scores-container {
                margin: 40px auto;
                max-width: 600px;
            }
            .scores-table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }
            .scores-table th,
            .scores-table td {
                border: 2px solid #e2e8f0;
                padding: 15px;
                text-align: center;
                font-size: 18px;
            }
            .scores-table th {
                background-color: #2c5aa0;
                color: white;
                font-weight: bold;
                text-transform: uppercase;
            }
            .scores-table tr:nth-child(even) {
                background-color: #f7fafc;
            }
            .total-score {
                font-size: 32px;
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
                width: 300px;
            }
            .signature-line {
                border-top: 2px solid #333;
                margin-top: 80px;
                padding-top: 10px;
                font-weight: bold;
                font-size: 16px;
            }
            .signature-title {
                font-size: 14px;
                color: #666;
                margin-top: 5px;
            }
            .footer {
                margin-top: 40px;
                font-size: 14px;
                color: #666;
                border-top: 2px solid #e2e8f0;
                padding-top: 20px;
            }
            .certificate-id {
                font-size: 16px;
                color: #888;
                margin-top: 20px;
                font-weight: bold;
            }
            .qr-code {
                position: absolute;
                bottom: 60px;
                right: 60px;
                width: 100px;
                height: 100px;
                border: 2px solid #2c5aa0;
                padding: 5px;
                background: white;
            }
            .seal {
                position: absolute;
                right: 120px;
                top: 200px;
                width: 120px;
                height: 120px;
                border: 4px solid #2c5aa0;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 14px;
                color: #2c5aa0;
                font-weight: bold;
                text-align: center;
                line-height: 1.2;
                background: rgba(255, 255, 255, 0.9);
                transform: rotate(-15deg);
            }
            @media print {
                body {
                    margin: 0;
                    padding: 0;
                }
                .certificate {
                    border-width: 10px;
                    padding: 40px;
                }
                .no-print {
                    display: none !important;
                }
            }
        </style>
    </head>
    <body>
        <div class="certificate">
            <div class="watermark">UPNVJ</div>
            
            <div class="content">
                <div class="header">
                    <div class="logo-container">
                        <div class="logo">UPA BAHASA UPNVJ</div>
                        <div class="university">Unit Pelayanan Akademik Bahasa</div>
                        <div class="university">Universitas Pembangunan Nasional Veteran Jakarta</div>
                    </div>
                </div>
                
                <div class="title">Certificate of Achievement</div>
                <div class="subtitle">English Language Proficiency Test (ELPT)</div>
                
                <div class="certify-text">This is to certify that</div>
                
                <div class="student-name">' . strtoupper(htmlspecialchars($result['name'])) . '</div>
                
                <div class="student-info">
                    <strong>NIM:</strong> ' . htmlspecialchars($result['nim']) . '<br>
                    <strong>Program Studi:</strong> ' . htmlspecialchars($result['program_studi']) . ' - ' . htmlspecialchars($result['jenjang']) . '<br>
                    <strong>Fakultas:</strong> ' . htmlspecialchars($result['fakultas']) . '
                </div>
                
                <div class="achievement-text">
                    has successfully completed the English Language Proficiency Test<br>
                    conducted on <strong>' . $test_date . '</strong> with the following results:
                </div>
                
                <div class="scores-container">
                    <table class="scores-table">
                        <tr>
                            <th>Test Section</th>
                            <th>Score</th>
                            <th>Maximum</th>
                        </tr>
                        <tr>
                            <td>Listening Comprehension</td>
                            <td><strong>' . $result['listening_score'] . '</strong></td>
                            <td>250</td>
                        </tr>
                        <tr>
                            <td>Structure & Written Expression</td>
                            <td><strong>' . $result['structure_score'] . '</strong></td>
                            <td>250</td>
                        </tr>
                        <tr>
                            <td>Reading Comprehension</td>
                            <td><strong>' . $result['reading_score'] . '</strong></td>
                            <td>250</td>
                        </tr>
                    </table>
                </div>
                
                <div class="total-score">
                    Total Score: ' . $result['total_score'] . ' / 750
                </div>
                
                <div class="achievement-text">
                    This certificate demonstrates proficiency in English language skills<br>
                    as measured by the ELPT assessment and meets the minimum requirement<br>
                    of <strong>' . MIN_PASSING_SCORE . '</strong> for academic purposes at UPN Veteran Jakarta.
                </div>
                
                <div class="signatures">
                    <div class="signature">
                        <div class="signature-line">
                            Dr. [Nama Kepala UPA]
                        </div>
                        <div class="signature-title">
                            Kepala UPA Bahasa UPNVJ
                        </div>
                    </div>
                    <div class="signature">
                        <div class="signature-line">
                            [Nama Koordinator]
                        </div>
                        <div class="signature-title">
                            Koordinator Akademik
                        </div>
                    </div>
                </div>
                
                <div class="footer">
                    <strong>Issued on:</strong> ' . $issue_date . '<br>
                    This certificate is valid for academic purposes at UPN Veteran Jakarta<br>
                    For verification, please contact UPA Bahasa UPNVJ at upabahasa@upnvj.ac.id
                </div>
                
                <div class="certificate-id">
                    Certificate ID: ' . htmlspecialchars($certificate_id) . '
                </div>
                
                <div class="seal">
                    OFFICIAL<br>
                    SEAL<br>
                    UPA BAHASA<br>
                    UPNVJ
                </div>
                
                <div class="qr-code no-print">
                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #666;">
                        QR Code<br>Verification
                    </div>
                </div>
            </div>
        </div>
        
        <div class="no-print" style="text-align: center; margin-top: 20px;">
            <button onclick="window.print()" style="padding: 10px 30px; font-size: 16px; background: #2c5aa0; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Print Certificate
            </button>
            <button onclick="window.close()" style="padding: 10px 30px; font-size: 16px; background: #666; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
                Close
            </button>
        </div>
        
        <script>
            // Auto-print on load
            window.onload = function() {
                window.print();
            };
        </script>
    </body>
    </html>';
    
    // Output as HTML (for simple implementation)
    // In production, use proper PDF generation
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    exit;
}