<?php
/**
 * DataStore.php - Système de stockage JSON sans SQLite
 * Remplace SQLite3 pour compatibilité maximale (PHP 7.4 à 8.4+)
 * Compatible Hostinger, OVH, o2Switch et tous mutualisés
 */

class DataStore {
    private static $file = null;
    private static $data = null;
    private static $initialized = false;
    private static $lockResource = null;

    public static function init() {
        if (self::$initialized) return true;

        $dataDir = dirname(__FILE__) . '/data';
        
        if (!is_dir($dataDir)) {
            if (!mkdir($dataDir, 0755, true)) {
                error_log("FreeLLMAPI Error: Impossible de créer le dossier data/");
                return false;
            }
            @chmod($dataDir, 0755);
        }

        self::$file = $dataDir . '/store.json';
        $lockFile = $dataDir . '/store.lock';

        self::$lockResource = fopen($lockFile, 'c');
        if (self::$lockResource) {
            flock(self::$lockResource, LOCK_EX);
        }

        if (!file_exists(self::$file)) {
            $initialData = [
                'encryption_key' => '',
                'keys' => [],
                'settings' => ['default_model' => 'mistral-small-2506', 'theme' => 'dark'],
                'models_override' => [],
                'version' => 1
            ];
            self::save($initialData);
            self::$data = $initialData;
        } else {
            $jsonContent = @file_get_contents(self::$file);
            self::$data = $jsonContent ? json_decode($jsonContent, true) : null;
            
            if (!self::$data || json_last_error() !== JSON_ERROR_NONE) {
                error_log("FreeLLMAPI Error: Corrupted JSON store. Resetting...");
                $backupFile = self::$file . '.bak.' . time();
                @rename(self::$file, $backupFile);
                self::init();
            }
        }

        self::$initialized = true;
        return true;
    }

    private static function save($data = null) {
        if ($data === null) $data = self::$data;
        if (!self::$file) return false;
        
        $dir = dirname(self::$file);
        if (!is_writable($dir)) {
            error_log("FreeLLMAPI Error: Dossier non inscriptible: " . $dir);
            return false;
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $result = file_put_contents(self::$file, $json, LOCK_EX);
        
        if ($result !== false) {
            @chmod(self::$file, 0644);
            return true;
        }
        
        error_log("FreeLLMAPI Error: Échec écriture store.json");
        return false;
    }

    public static function close() {
        if (self::$lockResource) {
            flock(self::$lockResource, LOCK_UN);
            fclose(self::$lockResource);
            self::$lockResource = null;
        }
    }

    public static function get($key, $subKey = null, $default = null) {
        if (!self::$initialized) self::init();
        if ($subKey === null) {
            return self::$data[$key] ?? $default;
        }
        if (isset(self::$data[$key]) && is_array(self::$data[$key])) {
            return self::$data[$key][$subKey] ?? $default;
        }
        return $default;
    }

    public static function set($key, $value, $subKey = null) {
        if (!self::$initialized) self::init();
        if ($subKey !== null) {
            if (!isset(self::$data[$key]) || !is_array(self::$data[$key])) {
                self::$data[$key] = [];
            }
            self::$data[$key][$subKey] = $value;
        } else {
            self::$data[$key] = $value;
        }
        return self::save();
    }

    public static function delete($key, $subKey = null) {
        if (!self::$initialized) self::init();
        if ($subKey !== null && isset(self::$data[$key][$subKey])) {
            unset(self::$data[$key][$subKey]);
            return self::save();
        } elseif ($subKey === null && isset(self::$data[$key])) {
            unset(self::$data[$key]);
            return self::save();
        }
        return false;
    }

    public static function has($key, $subKey = null) {
        if (!self::$initialized) self::init();
        if ($subKey === null) {
            return isset(self::$data[$key]);
        }
        return isset(self::$data[$key][$subKey]);
    }
    
    public static function reset() {
        self::close();
        if (self::$file && file_exists(self::$file)) {
            @unlink(self::$file);
        }
        $lockFile = dirname(self::$file) . '/store.lock';
        if (file_exists($lockFile)) {
            @unlink($lockFile);
        }
        self::$data = null;
        self::$initialized = false;
        return self::init();
    }

    public static function getAll() {
        if (!self::$initialized) self::init();
        return self::$data;
    }
}
