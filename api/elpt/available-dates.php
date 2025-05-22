<?php
// api/elpt/available-dates.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

include_once '../config/database.php';

try {
    // Get available dates (next 30 days, only Tue/Thu/Sat)
    $available_dates = [];
    $start_date = new DateTime('+1 day');
    $end_date = new DateTime('+30 days');
    
    while ($start_date <= $end_date) {
        $day_of_week = $start_date->format('N');
        if (in_array($day_of_week, [2, 4, 6])) { // Tuesday, Thursday, Saturday
            $date_str = $start_date->format('Y-m-d');
            
            // Check how many registered for this date
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM elpt_registrations WHERE test_date = ? AND payment_status IN ('pending', 'confirmed')");
            $stmt->execute([$date_str]);
            $count = $stmt->fetch()['count'];
            
            // Get time slots for this day
            $time_slots = [];
            if ($day_of_week == 6) { // Saturday
                $time_slots = [
                    ['time' => '07:00-09:30', 'label' => 'Pagi'],
                    ['time' => '09:30-12:00', 'label' => 'Siang'],
                    ['time' => '13:00-15:30', 'label' => 'Sore']
                ];
            } else { // Tuesday, Thursday
                $time_slots = [
                    ['time' => '09:30-12:00', 'label' => 'Pagi'],
                    ['time' => '13:00-15:30', 'label' => 'Siang']
                ];
            }
            
            $available_dates[] = [
                'date' => $date_str,
                'day' => $start_date->format('l'),
                'formatted' => $start_date->format('l, d F Y'),
                'display' => $start_date->format('d M Y'),
                'count' => $count,
                'slots' => 30 - $count,
                'available' => $count < 30,
                'time_slots' => $time_slots
            ];
        }
        $start_date->modify('+1 day');
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'dates' => $available_dates
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Gagal memuat tanggal tersedia'
    ]);
}
?>