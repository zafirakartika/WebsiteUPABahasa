<?php
// api/elpt/available-dates.php - Enhanced with time slot support
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

include_once '../config/database.php';

function getTimeSlots($day_of_week) {
    if ($day_of_week == 6) { // Saturday
        return [
            ['value' => 'pagi', 'label' => 'Pagi (07:00-09:30)', 'time' => '07:00-09:30'],
            ['value' => 'siang', 'label' => 'Siang (09:30-12:00)', 'time' => '09:30-12:00'],
            ['value' => 'sore', 'label' => 'Sore (13:00-15:30)', 'time' => '13:00-15:30']
        ];
    } else { // Tuesday, Thursday
        return [
            ['value' => 'pagi', 'label' => 'Pagi (07:00-09:30)', 'time' => '07:00-09:30'],
            ['value' => 'siang', 'label' => 'Siang (09:30-12:00)', 'time' => '09:30-12:00']
        ];
    }
}

try {
    // Get available dates with time slot information (next 30 days, only Tue/Thu/Sat)
    $available_dates = [];
    $start_date = new DateTime('+1 day');
    $end_date = new DateTime('+30 days');
    
    while ($start_date <= $end_date) {
        $day_of_week = $start_date->format('N');
        if (in_array($day_of_week, [2, 4, 6])) { // Tuesday, Thursday, Saturday
            $date_str = $start_date->format('Y-m-d');
            
            // Get time slots for this day
            $time_slots = getTimeSlots($day_of_week);
            
            // Check quota for each time slot
            foreach ($time_slots as &$slot) {
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM elpt_registrations 
                    WHERE test_date = ? AND time_slot = ? 
                    AND payment_status IN ('pending', 'confirmed')
                ");
                $stmt->execute([$date_str, $slot['value']]);
                $count = $stmt->fetch()['count'];
                
                $max_per_slot = getSystemSetting('max_participants_per_slot', 30);
                
                $slot['quota'] = [
                    'current' => $count,
                    'max' => $max_per_slot,
                    'available' => $count < $max_per_slot,
                    'remaining' => $max_per_slot - $count
                ];
            }
            
            // Calculate total availability for the date
            $total_slots = count($time_slots);
            $available_slots = count(array_filter($time_slots, function($slot) {
                return $slot['quota']['available'];
            }));
            
            $available_dates[] = [
                'date' => $date_str,
                'day' => $start_date->format('l'),
                'formatted' => $start_date->format('l, d F Y'),
                'display' => $start_date->format('d M Y'),
                'day_of_week' => $day_of_week,
                'time_slots' => $time_slots,
                'total_slots' => $total_slots,
                'available_slots' => $available_slots,
                'has_availability' => $available_slots > 0
            ];
        }
        $start_date->modify('+1 day');
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'dates' => $available_dates,
        'meta' => [
            'total_dates' => count($available_dates),
            'max_per_slot' => getSystemSetting('max_participants_per_slot', 30)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Gagal memuat tanggal dan sesi tersedia',
        'error' => $e->getMessage()
    ]);
}
?>