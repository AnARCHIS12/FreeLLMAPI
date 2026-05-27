<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing bootstrap...\n";

// Test config
require_once __DIR__ . '/config.php';
echo "[OK] Config loaded\n";
echo "  ENCRYPTION_KEY length: " . strlen(ENCRYPTION_KEY) . "\n";
echo "  ENCRYPTION_KEY valid: " . (strlen(ENCRYPTION_KEY) === 64 ? 'YES' : 'NO') . "\n";
if (defined('ENCRYPTION_KEY_TEMP')) {
    echo "  ENCRYPTION_KEY_TEMP defined: YES\n";
}

// Test DataStore init
require_once __DIR__ . '/DataStore.php';
echo "[OK] DataStore module loaded\n";

try {
    DataStore::init();
    echo "[OK] DataStore initialized\n";
    
    // Test get/set
    DataStore::set('test_key', 'test_value');
    $val = DataStore::get('test_key');
    echo "[OK] DataStore get/set works: $val\n";
} catch (Exception $e) {
    echo "[ERROR] DataStore init failed: " . $e->getMessage() . "\n";
}

// Test crypto
require_once __DIR__ . '/crypto.php';
echo "[OK] Crypto module loaded\n";

try {
    $test = Crypto::encrypt('test_key');
    echo "[OK] Encryption works\n";
    $decrypted = Crypto::decrypt($test['encrypted'], $test['iv'], $test['authTag']);
    echo "[OK] Decryption works: $decrypted\n";
} catch (Exception $e) {
    echo "[ERROR] Crypto failed: " . $e->getMessage() . "\n";
}

echo "\nAll tests completed!\n";
