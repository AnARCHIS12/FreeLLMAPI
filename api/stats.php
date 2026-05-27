<?php
/**
 * Stats API Endpoint - Get usage statistics
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';

// Initialize database if needed
if (!file_exists(DB_PATH)) {
    Database::init();
}

$db = Database::getInstance();

// Get today's date range
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Count requests today
$stmt = $db->prepare("SELECT COUNT(*) as count FROM requests WHERE created_at >= :today AND created_at < :tomorrow");
$stmt->bindValue(':today', $today, SQLITE3_TEXT);
$stmt->bindValue(':tomorrow', $tomorrow, SQLITE3_TEXT);
$result = $stmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);
$requestsToday = (int)($row['count'] ?? 0);

// Count tokens used today
$stmt = $db->prepare("SELECT COALESCE(SUM(input_tokens + output_tokens), 0) as total FROM requests WHERE created_at >= :today AND created_at < :tomorrow");
$stmt->bindValue(':today', $today, SQLITE3_TEXT);
$stmt->bindValue(':tomorrow', $tomorrow, SQLITE3_TEXT);
$result = $stmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);
$tokensUsed = (int)($row['total'] ?? 0);

// Calculate average latency for successful requests today
$stmt = $db->prepare("SELECT AVG(latency_ms) as avg_latency FROM requests WHERE created_at >= :today AND created_at < :tomorrow AND status = 'success'");
$stmt->bindValue(':today', $today, SQLITE3_TEXT);
$stmt->bindValue(':tomorrow', $tomorrow, SQLITE3_TEXT);
$result = $stmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);
$avgLatency = (int)round($row['avg_latency'] ?? 0);

// Calculate success rate
$stmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success FROM requests WHERE created_at >= :today AND created_at < :tomorrow");
$stmt->bindValue(':today', $today, SQLITE3_TEXT);
$stmt->bindValue(':tomorrow', $tomorrow, SQLITE3_TEXT);
$result = $stmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);
$totalRequests = (int)($row['total'] ?? 0);
$successCount = (int)($row['success'] ?? 0);
$successRate = $totalRequests > 0 ? round($successCount / $totalRequests, 2) : 1.0;

// Count active providers (providers with at least one enabled key)
$result = $db->query("SELECT COUNT(DISTINCT platform) as count FROM api_keys WHERE enabled = 1 AND status != 'invalid'");
$row = $result->fetchArray(SQLITE3_ASSOC);
$activeProviders = (int)($row['count'] ?? 0);

echo json_encode([
    'requests_today' => $requestsToday,
    'tokens_used' => $tokensUsed,
    'avg_latency' => $avgLatency,
    'success_rate' => $successRate,
    'active_providers' => $activeProviders,
]);
