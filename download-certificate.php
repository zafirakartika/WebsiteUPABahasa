<?php
// download-certificate.php - Official UPNVJ ELPT Certificate (Exact Format Match)
require_once 'config/database.php';
requireLogin();

// Get result ID from URL
$result_id = $_GET['id'] ?? null;

if (!$result_id || !is_numeric($result_id)) {
    showAlert('ID hasil tes tidak valid', 'error');
    header('Location: student/test-results.php');
    exit;
}

try {
    // Get test result with user data
    $stmt = $pdo->prepare("
    SELECT r.*, u.name, u.nim, u.program, u.faculty, u.level, u.no_telpon,
           reg.purpose, reg.test_date as registration_date, reg.billing_number
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
    if ($result['total_score'] < MIN_PASSING_SCORE) {
        showAlert('Skor belum mencapai batas minimum untuk mendapatkan sertifikat (minimum: ' . MIN_PASSING_SCORE . ')', 'warning');
        header('Location: student/test-results.php');
        exit;
    }
    
    // Log certificate download
    logActivity('certificate_download', "Downloaded certificate for result ID: $result_id");
    
    // Generate certificate
    generateOfficialCertificate($result);
    
} catch (Exception $e) {
    error_log('Certificate download error: ' . $e->getMessage());
    showAlert('Terjadi kesalahan saat mengunduh sertifikat', 'error');
    header('Location: student/test-results.php');
    exit;
}

function generateOfficialCertificate($result) {
    // Generate certificate details
    $certificate_number = $result['certificate_number'] ?: 'G25-' . str_pad($result['id'], 2, '0', STR_PAD_LEFT) . '/TF-M-TPT09/' . date('Y') . '/' . str_pad($result['id'], 4, '0', STR_PAD_LEFT);
    $test_date = date('d F Y', strtotime($result['test_date']));
    $issue_date = date('d F Y');
    
    // Determine proficiency level based on total score (matching official format)
    $proficiency_level = '';
    if ($result['total_score'] >= 600) {
        $proficiency_level = 'Advanced';
    } elseif ($result['total_score'] >= 500) {
        $proficiency_level = 'Upper Intermediate';
    } elseif ($result['total_score'] >= 450) {
        $proficiency_level = 'Intermediate';
    } else {
        $proficiency_level = 'Lower Intermediate';
    }
    
    // Format program name (matching official format)
    $program_display = $result['level'] . ' - ' . strtoupper($result['program']);
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>ELPT Certificate - ' . htmlspecialchars($result['name']) . '</title>
        <style>
            @page {
                size: A4 landscape;
                margin: 8mm;
            }
            
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: "Times New Roman", serif;
                background: white;
                color: #333;
                line-height: 1.2;
                font-size: 11px;
            }
            
            .certificate {
                width: 279mm;
                height: 202mm;
                margin: 0 auto;
                background: linear-gradient(135deg, #FFF8DC 0%, #FFFACD 50%, #F0E68C 100%);
                position: relative;
                padding: 8mm;
                border: 4px solid transparent;
                background-clip: padding-box;
            }
            
            .certificate::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                border: 4px solid;
                border-image: repeating-linear-gradient(45deg, #B8860B, #B8860B 8px, #DAA520 8px, #DAA520 16px) 4;
                background: repeating-conic-gradient(from 0deg at 50% 50%, #B8860B 0deg 90deg, #DAA520 90deg 180deg);
                z-index: -1;
            }
            
            .decorative-border {
                position: absolute;
                top: 6mm;
                left: 6mm;
                right: 6mm;
                bottom: 6mm;
                border: 2px solid #CD853F;
                background: repeating-linear-gradient(
                    0deg,
                    transparent,
                    transparent 3px,
                    #CD853F 3px,
                    #CD853F 4px
                );
                opacity: 0.3;
            }
            
            .decorative-border::before {
                content: "";
                position: absolute;
                top: 3mm;
                left: 3mm;
                right: 3mm;
                bottom: 3mm;
                border: 1px solid #CD853F;
                background: repeating-linear-gradient(
                    90deg,
                    transparent,
                    transparent 3px,
                    #CD853F 3px,
                    #CD853F 4px
                );
                opacity: 0.5;
            }
            
            .content {
                position: relative;
                z-index: 10;
                height: 100%;
                display: flex;
                flex-direction: column;
            }
            
            /* Header - Beautiful Gold/Brown Gradient */
            .header {
                background: linear-gradient(135deg, #8B4513 0%, #A0522D 50%, #CD853F 100%);
                color: white;
                padding: 8mm 6mm;
                display: flex;
                align-items: center;
                gap: 6mm;
                border-radius: 4px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
                margin-bottom: 6mm;
            }
            
            .logo-container {
                width: 18mm;
                height: 18mm;
                background: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                box-shadow: 0 2px 6px rgba(0,0,0,0.2);
                /* Logo image will be placed here */
                background-image: url("assets/images/upnvj-logo.png");
                background-size: 14mm 14mm;
                background-repeat: no-repeat;
                background-position: center;
            }
            
            .logo-placeholder {
                color: #8B4513;
                font-weight: bold;
                font-size: 6px;
                text-align: center;
                line-height: 1;
            }
            
            .header-text {
                flex: 1;
                text-align: center;
            }
            
            .university-name {
                font-size: 16px;
                font-weight: bold;
                margin-bottom: 2mm;
                text-transform: uppercase;
                letter-spacing: 1px;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
            }
            
            .unit-name {
                font-size: 14px;
                font-weight: bold;
                margin-bottom: 2mm;
            }
            
            .address {
                font-size: 9px;
                line-height: 1.3;
                opacity: 0.95;
            }
            
            /* Main content area */
            .main-section {
                flex: 1;
                background: white;
                padding: 6mm;
                border-radius: 4px;
                box-shadow: inset 0 0 10px rgba(0,0,0,0.05);
            }
            
            .document-title {
                text-align: center;
                margin-bottom: 5mm;
            }
            
            .result-of {
                font-size: 11px;
                color: #8B4513;
                font-weight: bold;
                margin-bottom: 2mm;
                letter-spacing: 2px;
            }
            
            .main-title {
                font-size: 20px;
                font-weight: bold;
                color: #8B4513;
                text-transform: uppercase;
                letter-spacing: 2px;
                margin-bottom: 2mm;
            }
            
            .subtitle {
                font-size: 12px;
                color: #8B4513;
                font-style: italic;
                font-weight: bold;
            }
            
            /* Student information and scores in two columns */
            .info-scores-container {
                display: flex;
                gap: 8mm;
                margin-top: 5mm;
            }
            
            .student-info {
                flex: 1;
            }
            
            .info-table {
                width: 100%;
                border-collapse: collapse;
            }
            
            .info-table td {
                padding: 1.5mm 0;
                vertical-align: top;
                font-size: 10px;
            }
            
            .info-label {
                width: 45mm;
                font-weight: bold;
                color: #8B4513;
            }
            
            .info-separator {
                width: 4mm;
                text-align: center;
                font-weight: bold;
                color: #8B4513;
            }
            
            .info-value {
                font-weight: bold;
                text-transform: uppercase;
            }
            
            /* Test scores section */
            .scores-section {
                flex: 1;
            }
            
            .scores-title {
                font-size: 12px;
                font-weight: bold;
                color: #8B4513;
                text-transform: uppercase;
                text-align: center;
                margin-bottom: 3mm;
                letter-spacing: 1px;
            }
            
            .scores-table {
                width: 100%;
                border-collapse: collapse;
                border: 2px solid #8B4513;
            }
            
            .scores-table th {
                background: linear-gradient(135deg, #8B4513, #A0522D);
                color: white;
                padding: 2mm;
                font-weight: bold;
                font-size: 9px;
                text-align: center;
                border: 1px solid #654321;
                line-height: 1.2;
            }
            
            .scores-table td {
                padding: 2mm;
                text-align: center;
                border: 1px solid #8B4513;
                font-weight: bold;
                font-size: 10px;
            }
            
            .component-cell {
                text-align: left;
                padding-left: 3mm;
            }
            
            .total-score {
                background: linear-gradient(135deg, #FFF8DC, #F0E68C);
                font-size: 18px;
                color: #8B4513;
                font-weight: bold;
            }
            
            .proficiency-level {
                background: linear-gradient(135deg, #F0E68C, #FFFACD);
                font-size: 11px;
                color: #8B4513;
                font-weight: bold;
            }
            
            /* Bottom section */
            .bottom-section {
                margin-top: 5mm;
                display: flex;
                justify-content: space-between;
                align-items: flex-end;
            }
            
            .validity-note {
                font-size: 9px;
                color: #666;
                font-style: italic;
                max-width: 120mm;
                line-height: 1.3;
            }
            
            .signature-section {
                text-align: center;
                width: 60mm;
            }
            
            .issue-info {
                font-size: 10px;
                margin-bottom: 12mm;
                font-style: italic;
            }
            
            .signature-line {
                border-top: 1px solid #333;
                margin-top: 10mm;
                padding-top: 2mm;
                font-size: 9px;
                font-weight: bold;
                line-height: 1.2;
            }
            
            .signature-title {
                font-size: 9px;
                margin-top: 1mm;
                font-style: italic;
            }
            
            /* Seals */
            .blue-seal {
                position: absolute;
                left: 15mm;
                bottom: 20mm;
                width: 20mm;
                height: 20mm;
                border: 3px solid #4169E1;
                border-radius: 50%;
                background: radial-gradient(circle, rgba(65, 105, 225, 0.3), rgba(65, 105, 225, 0.1));
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 6px;
                color: #4169E1;
                font-weight: bold;
                text-align: center;
                line-height: 1.1;
                transform: rotate(-15deg);
                box-shadow: 0 2px 6px rgba(65, 105, 225, 0.4);
            }
            
            .gold-seal {
                position: absolute;
                right: 15mm;
                bottom: 18mm;
                width: 22mm;
                height: 22mm;
                background: radial-gradient(circle, #FFD700, #DAA520);
                border-radius: 50%;
                border: 3px solid #B8860B;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #8B4513;
                font-weight: bold;
                text-align: center;
                font-size: 7px;
                line-height: 1.1;
                box-shadow: 0 3px 8px rgba(0,0,0,0.3);
                transform: rotate(10deg);
            }
            
            .no-print {
                margin-top: 10mm;
                text-align: center;
                background: #f8f9fa;
                padding: 4mm;
                border-radius: 4px;
                border: 2px solid #dee2e6;
            }
            
            .no-print button {
                margin: 0 2mm;
                padding: 3mm 6mm;
                font-size: 12px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-weight: bold;
                transition: all 0.3s ease;
            }
            
            .print-btn {
                background: linear-gradient(135deg, #8B4513, #CD853F);
                color: white;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            }
            
            .print-btn:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            }
            
            .close-btn {
                background: linear-gradient(135deg, #666, #777);
                color: white;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            }
            
            .close-btn:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            }
            
            @media print {
                body {
                    margin: 0;
                    padding: 0;
                    -webkit-print-color-adjust: exact;
                    color-adjust: exact;
                }
                
                .certificate {
                    margin: 0;
                    width: 100%;
                    height: 100vh;
                    page-break-inside: avoid;
                }
                
                .no-print {
                    display: none !important;
                }
                
                @page {
                    margin: 0;
                    size: A4 landscape;
                }
            }
        </style>
    </head>
    <body>
        <div class="certificate">
            <div class="decorative-border"></div>
            
            <div class="content">
                <!-- Header Section (Purple) -->
                <div class="header">
                    <div class="logo-container">
                        <div class="logo-placeholder"></div>
                    </div>
                    <div class="header-text">
                        <div class="university-name">Universitas Pembangunan Nasional "Veteran" Jakarta</div>
                        <div class="unit-name">Unit Penunjang Akademik Bahasa<br>( UPA Bahasa )</div>
                        <div class="address">Jalan RS Fatmawati, Pondok Labu, Jakarta Selatan<br>Telp./Fax. 021-7669069; Email : upabahasa@upnvj.ac.id</div>
                    </div>
                </div>
                
                <!-- Main Content Section -->
                <div class="main-section">
                    <!-- Document Title -->
                    <div class="document-title">
                        <div class="result-of">RESULT OF</div>
                        <div class="main-title">English Language Proficiency Test</div>
                        <div class="subtitle">( ELPT-UPNVJ )</div>
                    </div>
                    
                    <!-- Student Info and Scores Container -->
                    <div class="info-scores-container">
                        <!-- Left Column - Student Information -->
                        <div class="student-info">
                            <table class="info-table">
                                <tr>
                                    <td class="info-label">Full Name</td>
                                    <td class="info-separator">:</td>
                                    <td class="info-value">' . strtoupper(htmlspecialchars($result['name'])) . '</td>
                                </tr>
                                <tr>
                                    <td class="info-label">ID Number</td>
                                    <td class="info-separator">:</td>
                                    <td class="info-value">' . htmlspecialchars($result['nim']) . '</td>
                                </tr>
                                <tr>
                                    <td class="info-label">Faculty/Study Program</td>
                                    <td class="info-separator">:</td>
                                    <td class="info-value">' . htmlspecialchars($program_display) . '</td>
                                </tr>
                                <tr>
                                    <td class="info-label">Phone (HP)</td>
                                    <td class="info-separator">:</td>
                                    <td class="info-value">' . htmlspecialchars($result['no_telpon'] ?: 'N/A') . '</td>
                                </tr>
                            </table>
                        </div>
                        
                        <!-- Right Column - Additional Info -->
                        <div class="student-info">
                            <table class="info-table">
                                <tr>
                                    <td class="info-label">Certificate Number</td>
                                    <td class="info-separator">:</td>
                                    <td class="info-value">' . htmlspecialchars($certificate_number) . '</td>
                                </tr>
                                <tr>
                                    <td class="info-label">Date of Test</td>
                                    <td class="info-separator">:</td>
                                    <td class="info-value">' . $test_date . '</td>
                                </tr>
                                <tr>
                                    <td class="info-label">Test Form</td>
                                    <td class="info-separator">:</td>
                                    <td class="info-value">ELPT - Offline - CBT</td>
                                </tr>
                                <tr>
                                    <td colspan="3">&nbsp;</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Test Scores Section -->
                    <div class="scores-section">
                        <div class="scores-title">Test Scores</div>
                        <table class="scores-table">
                            <thead>
                                <tr>
                                    <th style="width: 35%;">Components</th>
                                    <th style="width: 15%;">Score</th>
                                    <th style="width: 25%;">UPA Bahasa - ELPT -<br>Total Score</th>
                                    <th style="width: 25%;">Proficiency Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="component-cell">Listening</td>
                                    <td>' . $result['listening_score'] . '</td>
                                    <td rowspan="3" class="total-score">' . $result['total_score'] . '</td>
                                    <td rowspan="3" class="proficiency-level">' . $proficiency_level . '</td>
                                </tr>
                                <tr>
                                    <td class="component-cell">Structure and Written Expressions</td>
                                    <td>' . $result['structure_score'] . '</td>
                                </tr>
                                <tr>
                                    <td class="component-cell">Reading Comprehension</td>
                                    <td>' . $result['reading_score'] . '</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Bottom Section -->
                    <div class="bottom-section">
                        <div class="validity-note">
                            This ELPT Score remains valid as long as the student keeps studying at UPNVJ.
                        </div>
                        
                        <div class="signature-section">
                            <div class="issue-info">Issued in Jakarta, ' . $issue_date . '</div>
                            <div class="signature-line">
                                Ayunita Ajengtiyas S. Mashuri, S.E., M.Accy., M.Comm.
                            </div>
                            <div class="signature-title">Head of UPA Bahasa</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Official Seals -->
            <div class="blue-seal">
                UPA<br>BAHASA<br>UPNVJ<br>SEAL
            </div>
            
            <div class="gold-seal">
                UPNVJ<br><br>UPA<br>BAHASA
            </div>
        </div>
        
        <div class="no-print">
            <h4 style="color: #8B4513; margin-bottom: 3mm;">📜 Official ELPT Certificate</h4>
            <p style="margin-bottom: 3mm; color: #666;">
                <strong>' . htmlspecialchars($result['name']) . '</strong> | 
                Score: <strong>' . $result['total_score'] . '</strong> | 
                Level: <strong>' . $proficiency_level . '</strong>
            </p>
            <button class="print-btn" onclick="window.print()">
                🖨️ Print Certificate
            </button>
            <button class="close-btn" onclick="handleClose()">
                ❌ Close
            </button>
        </div>
        
        <script>
            function handleClose() {
                if (window.opener) {
                    window.close();
                } else {
                    window.history.back();
                }
            }
        </script>
    </body>
    </html>';
    
    // Output as HTML
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    exit;
}
?>