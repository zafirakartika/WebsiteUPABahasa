<?php
// api/admin/stats.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

include_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $stats = [];
    
    // Total registrations
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM elpt_registrations");
    $stats['total_registrations'] = $stmt->fetch()['count'];
    
    // Pending payments
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM elpt_registrations WHERE payment_status = 'pending'");
    $stats['pending_payments'] = $stmt->fetch()['count'];
    
    // Confirmed for today and future
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM elpt_registrations WHERE payment_status = 'confirmed' AND test_date >= CURDATE()");
    $stats['upcoming_tests'] = $stmt->fetch()['count'];
    
    // Total students
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    $stats['total_students'] = $stmt->fetch()['count'];
    
    // Results entered today
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM elpt_results WHERE DATE(created_at) = CURDATE()");
    $stats['results_today'] = $stmt->fetch()['count'];
    
    // Tests this week
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM elpt_registrations 
        WHERE payment_status = 'confirmed' 
        AND test_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ");
    $stats['tests_this_week'] = $stmt->fetch()['count'];
    
    // Average score (last 30 days)
    $stmt = $pdo->query("
        SELECT AVG(total_score) as avg_score 
        FROM elpt_results 
        WHERE test_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $result = $stmt->fetch();
    $stats['average_score'] = $result['avg_score'] ? round($result['avg_score'], 1) : 0;
    
    // Pass rate (last 30 days)
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN total_score >= 450 THEN 1 ELSE 0 END) as passed
        FROM elpt_results 
        WHERE test_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $result = $stmt->fetch();
    $stats['pass_rate'] = $result['total'] > 0 ? round(($result['passed'] / $result['total']) * 100, 1) : 0;
    
    // Monthly registrations (last 6 months)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM elpt_registrations 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthly_registrations = $stmt->fetchAll();
    
    // Daily tests this week
    $stmt = $pdo->query("
        SELECT 
            test_date,
            COUNT(*) as count
        FROM elpt_registrations 
        WHERE payment_status = 'confirmed'
        AND test_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        GROUP BY test_date
        ORDER BY test_date ASC
    ");
    $daily_tests = $stmt->fetchAll();
    
    // Faculty distribution
    $stmt = $pdo->query("
        SELECT 
            u.fakultas,
            COUNT(*) as count
        FROM elpt_registrations r
        JOIN users u ON r.user_id = u.id
        WHERE r.created_at >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
        GROUP BY u.fakultas
        ORDER BY count DESC
        LIMIT 10
    ");
    $faculty_distribution = $stmt->fetchAll();
    
    // Purpose distribution
    $stmt = $pdo->query("
        SELECT 
            purpose,
            COUNT(*) as count
        FROM elpt_registrations 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
        GROUP BY purpose
        ORDER BY count DESC
    ");
    $purpose_distribution = $stmt->fetchAll();
    
    // Recent activities (last 10)
    $stmt = $pdo->query("
        SELECT 
            'registration' as type,
            u.name,
            r.created_at as timestamp,
            CONCAT('Daftar ELPT - ', r.purpose) as description
        FROM elpt_registrations r
        JOIN users u ON r.user_id = u.id
        ORDER BY r.created_at DESC
        LIMIT 5
        
        UNION ALL
        
        SELECT 
            'result' as type,
            u.name,
            er.created_at as timestamp,
            CONCAT('Hasil ELPT - Score: ', er.total_score) as description
        FROM elpt_results er
        JOIN users u ON er.user_id = u.id
        ORDER BY er.created_at DESC
        LIMIT 5
        
        ORDER BY timestamp DESC
        LIMIT 10
    ");
    $recent_activities = $stmt->fetchAll();
    
    // System health
    $system_health = [
        'database_status' => 'online',
        'disk_usage' => rand(45, 75), // Simulated
        'memory_usage' => rand(30, 60), // Simulated
        'active_sessions' => rand(10, 50), // Simulated
        'last_backup' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 24) . ' hours'))
    ];
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'charts' => [
            'monthly_registrations' => $monthly_registrations,
            'daily_tests' => $daily_tests,
            'faculty_distribution' => $faculty_distribution,
            'purpose_distribution' => $purpose_distribution
        ],
        'recent_activities' => $recent_activities,
        'system_health' => $system_health,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>