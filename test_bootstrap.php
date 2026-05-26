<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing bootstrap...\n";

// Test config
require_once __DIR__ . '/config.php';
echo "✓ Config loaded\n";
echo "  ENCRYPTION_KEY length: " . strlen(ENCRYPTION_KEY) . "\n";
echo "  ENCRYPTION_KEY valid: " . (strlen(ENCRYPTION_KEY) === 64 ? 'YES' : 'NO') . "\n";
if (defined('ENCRYPTION_KEY_TEMP')) {
    echo "  ENCRYPTION_KEY_TEMP defined: YES\n";
}

// Test database init
require_once __DIR__ . '/database.php';
echo "✓ Database module loaded\n";

try {
    Database::init();
    echo "✓ Database initialized\n";
} catch (Exception $e) {
    echo "✗ Database init failed: " . $e->getMessage() . "\n";
}

// Test crypto
require_once __DIR__ . '/crypto.php';
echo "✓ Crypto module loaded\n";

try {
    $test = Crypto::encrypt('test_key');
    echo "✓ Encryption works\n";
    $decrypted = Crypto::decrypt($test['encrypted'], $test['iv'], $test['authTag']);
    echo "✓ Decryption works: $decrypted\n";
} catch (Exception $e) {
    echo "✗ Crypto failed: " . $e->getMessage() . "\n";
}

echo "\nAll tests completed!\n";
