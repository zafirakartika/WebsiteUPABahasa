<?php
// Google reCAPTCHA v2 Configuration
define('RECAPTCHA_SITE_KEY', '6LeQxmcrAAAAAHnMVtkS3yrMhtJaa523GqC_EtTh'); 
define('RECAPTCHA_SECRET_KEY', '6LeQxmcrAAAAAAzZo4UwGkUVQyMb3Erw5XJzlmnO'); 

/**
 * Verify reCAPTCHA response
 * @param string $response - The response from reCAPTCHA
 * @return bool - True if valid, false otherwise
 */
function verifyRecaptcha($response) {
    if (empty($response)) {
        return false;
    }
    
    $secretKey = RECAPTCHA_SECRET_KEY;
    $remoteip = $_SERVER['REMOTE_ADDR'];
    
    // Google reCAPTCHA API endpoint
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    
    // Prepare POST data
    $data = array(
        'secret' => $secretKey,
        'response' => $response,
        'remoteip' => $remoteip
    );
    
    // Use cURL to verify
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    if ($result === false) {
        return false;
    }
    
    $json = json_decode($result, true);
    
    return isset($json['success']) && $json['success'] === true;
}
?>