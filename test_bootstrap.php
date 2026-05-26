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

// Test DataStore init
require_once __DIR__ . '/DataStore.php';
echo "✓ DataStore module loaded\n";

try {
    DataStore::init();
    echo "✓ DataStore initialized\n";
    
    // Test get/set
    DataStore::set('test_key', 'test_value');
    $val = DataStore::get('test_key');
    echo "✓ DataStore get/set works: $val\n";
} catch (Exception $e) {
    echo "✗ DataStore init failed: " . $e->getMessage() . "\n";
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
