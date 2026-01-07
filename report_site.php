<?php
// report_site.php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Invalid request method";
    exit;
}

$url    = isset($_POST['url'])    ? trim($_POST['url'])    : '';
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

if ($url === '' || $reason === '') {
    http_response_code(400);
    echo "URL and reason are required.";
    exit;
}

$reported_at = date('Y-m-d H:i:s');
$ip_address  = $_SERVER['REMOTE_ADDR']     ?? null;
$user_agent  = $_SERVER['HTTP_USER_AGENT'] ?? null;

try {
    $stmt = $pdo->prepare(
        "INSERT INTO site_reports (url, reason, reported_at, ip_address, user_agent)
         VALUES (:url, :reason, :reported_at, :ip_address, :user_agent)"
    );

    $stmt->execute([
        ':url'         => $url,
        ':reason'      => $reason,
        ':reported_at' => $reported_at,
        ':ip_address'  => $ip_address,
        ':user_agent'  => $user_agent
    ]);

    echo "✅ Report saved successfully.";
} catch (PDOException $e) {
    http_response_code(500);
    echo "❌ Failed to save report.";
}
