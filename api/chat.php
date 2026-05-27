<?php
/**
 * Chat API Endpoint - Main unified chat interface
 * Handles routing, rate limiting, and streaming responses
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../router.php';
require_once __DIR__ . '/../providers/ProviderRegistry.php';

// Initialize database if needed
if (!file_exists(DB_PATH)) {
    Database::init();
}

// Verify API key
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$providedKey = str_replace('Bearer ', '', $authHeader);
$unifiedKey = Database::getUnifiedApiKey();

if ($providedKey !== $unifiedKey) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'code' => 401]);
    exit;
}

// Parse request body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$messages = $input['messages'] ?? [];
$modelId = $input['model_id'] ?? null;
$stream = $input['stream'] ?? true;
$temperature = $input['temperature'] ?? 0.7;
$maxTokens = $input['max_tokens'] ?? 1024;

if (empty($messages)) {
    http_response_code(400);
    echo json_encode(['error' => 'Messages array is required']);
    exit;
}

$db = Database::getInstance();

// Determine which model to use
$preferredModelDbId = null;
if ($modelId !== null) {
    $stmt = $db->prepare("SELECT id FROM models WHERE id = :id AND enabled = 1");
    $stmt->bindValue(':id', (int)$modelId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    if ($row) {
        $preferredModelDbId = (int)$modelId;
    }
}

// Estimate tokens for rate limiting
$estimatedTokens = 0;
foreach ($messages as $msg) {
    $estimatedTokens += ceil(str_word_count($msg['content'] ?? '') * 1.3);
}
$estimatedTokens = max($estimatedTokens, 100);

// Route the request
$routed = RouterService::routeRequest($estimatedTokens, null, $preferredModelDbId);

if (!$routed) {
    http_response_code(503);
    echo json_encode(['error' => 'No providers available', 'code' => 503]);
    exit;
}

// Get provider instance
$provider = ProviderRegistry::getProvider($routed['provider']);
if (!$provider) {
    http_response_code(500);
    echo json_encode(['error' => 'Provider not found: ' . $routed['provider']]);
    exit;
}

// Record start time
$startTime = microtime(true);

try {
    // Prepare options
    $options = [
        'temperature' => $temperature,
        'max_tokens' => $maxTokens,
    ];

    if ($stream) {
        // Enable streaming
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
        
        // Disable output buffering
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        try {
            foreach ($provider->streamChatCompletion($routed['apiKey'], $messages, $routed['modelId'], $options) as $chunk) {
                echo "data: " . json_encode($chunk) . "\n\n";
                flush();
                
                // Check for completion
                if (isset($chunk['choices'][0]['finish_reason']) && $chunk['choices'][0]['finish_reason'] !== null) {
                    break;
                }
            }
            
            echo "data: [DONE]\n\n";
            flush();
            
            // Record success
            RouterService::recordSuccess($routed['modelDbId']);
            
            // Calculate latency
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);
            
            // Log request
            $stmt = $db->prepare("INSERT INTO requests (platform, model_id, key_id, status, latency_ms) VALUES (:platform, :model_id, :key_id, 'success', :latency_ms)");
            $stmt->bindValue(':platform', $routed['platform'], SQLITE3_TEXT);
            $stmt->bindValue(':model_id', $routed['modelId'], SQLITE3_TEXT);
            $stmt->bindValue(':key_id', $routed['keyId'], SQLITE3_INTEGER);
            $stmt->bindValue(':latency_ms', $latencyMs, SQLITE3_INTEGER);
            $stmt->execute();
            
        } catch (Exception $e) {
            // Handle streaming error
            echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
            echo "data: [DONE]\n\n";
            flush();
            
            // Record failure
            RouterService::recordRateLimitHit($routed['modelDbId']);
            
            // Log error
            $stmt = $db->prepare("INSERT INTO requests (platform, model_id, key_id, status, error) VALUES (:platform, :model_id, :key_id, 'error', :error)");
            $stmt->bindValue(':platform', $routed['platform'], SQLITE3_TEXT);
            $stmt->bindValue(':model_id', $routed['modelId'], SQLITE3_TEXT);
            $stmt->bindValue(':key_id', $routed['keyId'], SQLITE3_INTEGER);
            $stmt->bindValue(':error', $e->getMessage(), SQLITE3_TEXT);
            $stmt->execute();
        }
    } else {
        // Non-streaming response
        $result = $provider->chatCompletion($routed['apiKey'], $messages, $routed['modelId'], $options);
        
        // Record success
        RouterService::recordSuccess($routed['modelDbId']);
        
        // Calculate latency
        $latencyMs = (int)((microtime(true) - $startTime) * 1000);
        
        // Extract token counts if available
        $inputTokens = $result['usage']['prompt_tokens'] ?? 0;
        $outputTokens = $result['usage']['completion_tokens'] ?? 0;
        
        // Log request
        $stmt = $db->prepare("INSERT INTO requests (platform, model_id, key_id, status, input_tokens, output_tokens, latency_ms) VALUES (:platform, :model_id, :key_id, 'success', :input_tokens, :output_tokens, :latency_ms)");
        $stmt->bindValue(':platform', $routed['platform'], SQLITE3_TEXT);
        $stmt->bindValue(':model_id', $routed['modelId'], SQLITE3_TEXT);
        $stmt->bindValue(':key_id', $routed['keyId'], SQLITE3_INTEGER);
        $stmt->bindValue(':input_tokens', $inputTokens, SQLITE3_INTEGER);
        $stmt->bindValue(':output_tokens', $outputTokens, SQLITE3_INTEGER);
        $stmt->bindValue(':latency_ms', $latencyMs, SQLITE3_INTEGER);
        $stmt->execute();
        
        // Add routing info
        $result['_routed_via'] = [
            'platform' => $routed['platform'],
            'model' => $routed['modelId'],
            'display_name' => $routed['displayName'],
        ];
        
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'code' => 500,
    ]);
    
    // Log error
    try {
        $stmt = $db->prepare("INSERT INTO requests (platform, model_id, key_id, status, error) VALUES (:platform, :model_id, :key_id, 'error', :error)");
        $stmt->bindValue(':platform', $routed['platform'] ?? 'unknown', SQLITE3_TEXT);
        $stmt->bindValue(':model_id', $routed['modelId'] ?? 'unknown', SQLITE3_TEXT);
        $stmt->bindValue(':key_id', $routed['keyId'] ?? 0, SQLITE3_INTEGER);
        $stmt->bindValue(':error', $e->getMessage(), SQLITE3_TEXT);
        $stmt->execute();
    } catch (Exception $logError) {
        // Ignore logging errors
    }
}
