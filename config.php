<?php
/**
 * FreeLLMAPI - Version JSON (sans SQLite)
 * Fichier de configuration
 * Compatible Hostinger, OVH, o2Switch et hébergements mutualisés
 */

// Chemin vers le dossier data
define('DATA_DIR', __DIR__ . '/data');

// Serveur
define('PORT', 3001);
define('HOST', '0.0.0.0');

// Clé de chiffrement (64 caractères hex = 32 bytes)
$encryptionKey = getenv('ENCRYPTION_KEY') ?: null;

// Si pas de variable d'environnement, essayer de charger depuis DataStore
if (!$encryptionKey && file_exists(DATA_DIR . '/store.json')) {
    require_once __DIR__ . '/DataStore.php';
    DataStore::init();
    $storedKey = DataStore::get('encryption_key');
    if ($storedKey && strlen($storedKey) === 64) {
        $encryptionKey = $storedKey;
    }
}

// Si toujours pas de clé, en générer une automatiquement
if (!$encryptionKey || strlen($encryptionKey) !== 64) {
    $encryptionKey = bin2hex(random_bytes(32));
    define('ENCRYPTION_KEY_TEMP', $encryptionKey);
    
    // Sauvegarder immédiatement dans DataStore si disponible
    if (class_exists('DataStore')) {
        DataStore::init();
        DataStore::set('encryption_key', $encryptionKey);
    }
}

define('ENCRYPTION_KEY', $encryptionKey);

// Préfixe pour la clé API unifiée
define('UNIFIED_KEY_PREFIX', 'freellmapi-');

// Fenêtres de rate limiting
define('WINDOW_MINUTE', 60000);
define('WINDOW_DAY', 86400000);

// TTL session sticky (30 minutes)
define('STICKY_TTL_MS', 30 * 60 * 1000);

// Max tentatives de retry pour fallback
define('MAX_RETRIES', 20);

// Intervalle health check (5 minutes)
define('HEALTH_CHECK_INTERVAL', 5 * 60 * 1000);

// Échecs consécutifs avant désactivation auto
define('CONSECUTIVE_FAILURES_TO_DISABLE', 3);

// Paramètres de pénalité pour priorité dynamique
define('PENALTY_PER_429', 3);
define('MAX_PENALTY', 10);
define('PENALTY_DECAY_INTERVAL', 2 * 60 * 1000);
define('PENALTY_DECAY_AMOUNT', 1);
