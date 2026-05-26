<?php
/**
 * FreeLLMAPI - PHP SQLite Version
 * Configuration file
 */

// Database configuration
define('DB_PATH', __DIR__ . '/data/freeapi.db');

// Server configuration
define('PORT', 3001);
define('HOST', '0.0.0.0');

// Encryption key (must be 64 hex characters = 32 bytes)
// Generate with: bin2hex(random_bytes(32))
$encryptionKey = getenv('ENCRYPTION_KEY') ?: null;
if (!$encryptionKey) {
    // Try to load from database for dev mode
    if (file_exists(DB_PATH)) {
        try {
            $db = new SQLite3(DB_PATH);
            $result = $db->querySingle("SELECT value FROM settings WHERE key = 'encryption_key'");
            if ($result) {
                $encryptionKey = $result;
            }
            $db->close();
        } catch (Exception $e) {
            // Database might be corrupted or inaccessible, will generate new key
        }
    }
}
// If no key found, generate one automatically (for first-run on shared hosting)
if (!$encryptionKey || strlen($encryptionKey) !== 64) {
    $encryptionKey = bin2hex(random_bytes(32));
    // Store it temporarily, will be saved to DB when DB is initialized
    define('ENCRYPTION_KEY_TEMP', $encryptionKey);
}
define('ENCRYPTION_KEY', $encryptionKey);

// Unified API key stored in database
define('UNIFIED_KEY_PREFIX', 'freellmapi-');

// Rate limit windows
define('WINDOW_MINUTE', 60000);
define('WINDOW_DAY', 86400000);

// Sticky session TTL (30 minutes)
define('STICKY_TTL_MS', 30 * 60 * 1000);

// Max retry attempts for fallback
define('MAX_RETRIES', 20);

// Health check interval (5 minutes)
define('HEALTH_CHECK_INTERVAL', 5 * 60 * 1000);

// Consecutive failures before auto-disable
define('CONSECUTIVE_FAILURES_TO_DISABLE', 3);

// Penalty settings for dynamic priority
define('PENALTY_PER_429', 3);
define('MAX_PENALTY', 10);
define('PENALTY_DECAY_INTERVAL', 2 * 60 * 1000);
define('PENALTY_DECAY_AMOUNT', 1);
