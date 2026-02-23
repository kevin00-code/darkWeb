<?php
require_once 'auth_check.php';
verify_access();

header('Content-Type: application/json');

if (!isset($_GET['target'])) {
    echo json_encode(['found' => false, 'error' => 'MISSING_TARGET']);
    exit;
}

$target = urlencode($_GET['target']);

// Querying XposedOrNot (No Key Required for basic checks)
$url = "https://api.xposedornot.com/v1/check-email/" . $target;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $data = json_decode($response, true);
    echo json_encode(['found' => true, 'breaches' => $data['breaches'][0]]);
} else {
    echo json_encode(['found' => false, 'message' => 'No breaches found or service unavailable']);
}
?>
