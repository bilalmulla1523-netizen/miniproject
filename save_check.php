<?php
// save_check.php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Invalid request method";
    exit;
}

$url          = isset($_POST['url']) ? trim($_POST['url']) : '';
$isSuspicious = isset($_POST['is_suspicious']) ? (int)$_POST['is_suspicious'] : 0;

if ($url === '') {
    http_response_code(400);
    echo "Missing URL";
    exit;
}

$checked_at = date('Y-m-d H:i:s');
$ip_address = $_SERVER['REMOTE_ADDR']      ?? null;
$user_agent = $_SERVER['HTTP_USER_AGENT']  ?? null;

try {
    $stmt = $pdo->prepare(
        "INSERT INTO site_checks (url, is_suspicious, checked_at, ip_address, user_agent)
         VALUES (:url, :is_suspicious, :checked_at, :ip_address, :user_agent)"
    );

    $stmt->execute([
        ':url'           => $url,
        ':is_suspicious' => $isSuspicious,
        ':checked_at'    => $checked_at,
        ':ip_address'    => $ip_address,
        ':user_agent'    => $user_agent
    ]);

    echo "OK";
} catch (PDOException $e) {
    http_response_code(500);
    echo "Failed";
}
