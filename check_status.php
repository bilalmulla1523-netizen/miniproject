<?php
// check_status.php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['active' => false, 'error' => 'Invalid method']);
    exit;
}

$url = $_POST['url'] ?? '';
$url = trim($url);

if ($url === '') {
    http_response_code(400);
    echo json_encode(['active' => false, 'error' => 'Missing URL']);
    exit;
}

// Make sure URL has scheme
if (!preg_match('#^https?://#i', $url)) {
    $url = 'https://' . $url;
}

// Basic validate
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(['active' => false, 'error' => 'Invalid URL']);
    exit;
}

// Use cURL to check if website responds
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_NOBODY         => true,      // we only need headers
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 5,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_errno($ch);
curl_close($ch);

// Consider 2xx and 3xx as "active"
$isActive = !$curlErr && $httpCode >= 200 && $httpCode < 400;

echo json_encode([
    'active'   => $isActive,
    'httpCode' => $httpCode,
]);
