<?php
// api/elpt/download-certificate.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

include_once '../config/database.php';
require_once '../vendor/autoload.php'; // For TCPDF or similar

// Check if result ID is provided
if (!isset($_GET['result_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Result ID is required']);
    exit;
}

$result_id = $_GET['result_id'];

try {
    // Get test result with user data
    $stmt = $pdo->prepare("
        SELECT r.*, u.name, u.nim, u.program, u.faculty 
        FROM elpt_results r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.id = ?
    ");
    $stmt->execute([$result_id]);
    $result = $stmt->fetch();
    
    if (!$result) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Result not found']);
        exit;
    }
    
    // Check if score meets minimum requirement
    if ($result['total_score'] < 450) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Score does not meet minimum requirement']);
        exit;
    }
    
    // Generate PDF certificate
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('UPA Bahasa UPNVJ');
    $pdf->SetAuthor('UPA Bahasa UPNVJ');
    $pdf->SetTitle('ELPT Certificate');
    $pdf->SetSubject('English Language Proficiency Test Certificate');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 12);
    
    // Certificate content
    $html = generateCertificateHTML($result);
    
    // Print text using writeHTMLCell()
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Generate filename
    $filename = 'ELPT_Certificate_' . $result['name'] . '_' . date('Y-m-d', strtotime($result['test_date'])) . '.pdf';
    $filename = preg_replace('/[^A-Za-z0-9_.-]/', '_', $filename);
    
    // Output PDF
    $pdf->Output($filename, 'D'); // 'D' for download
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to generate certificate: ' . $e->getMessage()]);
}

function generateCertificateHTML($result) {
    $test_date = date('d F Y', strtotime($result['test_date']));
    $issue_date = date('d F Y');
    
    return '
    <style>
        .certificate {
            text-align: center;
            padding: 40px;
            font-family: Arial, sans-serif;
        }
        .header {
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2c5aa0;
        }
        .title {
            font-size: 28px;
            font-weight: bold;
            color: #1a365d;
            margin: 20px 0;
        }
        .subtitle {
            font-size: 18px;
            color: #4a5568;
            margin-bottom: 30px;
        }
        .student-info {
            margin: 30px 0;
            font-size: 16px;
        }
        .scores {
            margin: 30px 0;
            text-align: left;
            display: inline-block;
        }
        .score-item {
            margin: 10px 0;
            font-size: 14px;
        }
        .total-score {
            font-size: 18px;
            font-weight: bold;
            color: #2c5aa0;
            margin-top: 20px;
        }
        .footer {
            margin-top: 50px;
            font-size: 12px;
            color: #666;
        }
    </style>
    
    <div class="certificate">
        <div class="header">
            <div class="logo">UPA BAHASA UPNVJ</div>
            <div>Unit Pelayanan Akademik Bahasa</div>
            <div>Universitas Pembangunan Nasional Veteran Jakarta</div>
        </div>
        
        <div class="title">CERTIFICATE OF ACHIEVEMENT</div>
        <div class="subtitle">English Language Proficiency Test (ELPT)</div>
        
        <div style="margin: 40px 0;">
            <div style="font-size: 16px; margin-bottom: 20px;">This is to certify that</div>
            
            <div class="student-info">
                <div style="font-size: 20px; font-weight: bold; margin: 10px 0;">' . strtoupper($result['name']) . '</div>
                <div>NIM: ' . $result['nim'] . '</div>
                <div>' . $result['program'] . '</div>
                <div>' . $result['faculty'] . '</div>
            </div>
            
            <div style="font-size: 16px; margin: 30px 0;">
                has successfully completed the English Language Proficiency Test<br>
                on ' . $test_date . ' with the following scores:
            </div>
        </div>
        
        <div class="scores">
            <div class="score-item">Listening Comprehension: <strong>' . $result['listening_score'] . '</strong></div>
            <div class="score-item">Structure & Written Expression: <strong>' . $result['structure_score'] . '</strong></div>
            <div class="score-item">Reading Comprehension: <strong>' . $result['reading_score'] . '</strong></div>
            <div class="total-score">Total Score: ' . $result['total_score'] . ' / 677</div>
        </div>
        
        <div style="margin-top: 50px; font-size: 16px;">
            This certificate demonstrates proficiency in English language skills<br>
            as measured by the ELPT assessment.
        </div>
        
        <div class="footer">
            <div style="margin-top: 60px;">
                <div style="display: inline-block; width: 200px; text-align: center; margin: 0 50px;">
                    <div style="border-top: 1px solid #000; margin-top: 60px; padding-top: 10px;">
                        <strong>Director</strong><br>
                        UPA Bahasa UPNVJ
                    </div>
                </div>
                <div style="display: inline-block; width: 200px; text-align: center; margin: 0 50px;">
                    <div style="border-top: 1px solid #000; margin-top: 60px; padding-top: 10px;">
                        <strong>Academic Coordinator</strong><br>
                        UPA Bahasa UPNVJ
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 30px; font-size: 10px;">
                Certificate issued on: ' . $issue_date . '<br>
                Certificate ID: ELPT-' . $result['id'] . '-' . date('Y') . '
            </div>
        </div>
    </div>';
}
?>