<?php
/**
 * SQLite database bootstrap and access helpers for FreeLLMAPI.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models.php';

if (!defined('DB_PATH')) {
    define('DB_PATH', DATA_DIR . '/freeapi.db');
}

class Database {
    private static ?SQLite3 $instance = null;

    public static function getInstance(): SQLite3 {
        if (self::$instance === null) {
            if (!is_dir(DATA_DIR)) {
                mkdir(DATA_DIR, 0755, true);
            }

            self::$instance = new SQLite3(DB_PATH);
            self::$instance->busyTimeout(5000);
            self::$instance->exec('PRAGMA foreign_keys = ON');
            self::init();
        }

        return self::$instance;
    }

    public static function init(): void {
        if (!is_dir(DATA_DIR)) {
            mkdir(DATA_DIR, 0755, true);
        }

        $db = self::$instance ?? new SQLite3(DB_PATH);
        if (self::$instance === null) {
            self::$instance = $db;
        }

        $db->exec('PRAGMA foreign_keys = ON');

        $db->exec("
            CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS api_keys (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                platform TEXT NOT NULL,
                label TEXT DEFAULT '',
                encrypted_key TEXT NOT NULL,
                iv TEXT NOT NULL,
                auth_tag TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'valid',
                enabled INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS models (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                platform TEXT NOT NULL,
                model_id TEXT NOT NULL,
                display_name TEXT NOT NULL,
                intelligence_rank INTEGER NOT NULL DEFAULT 0,
                speed_rank INTEGER NOT NULL DEFAULT 0,
                size_label TEXT DEFAULT '',
                rpm_limit INTEGER DEFAULT NULL,
                rpd_limit INTEGER DEFAULT NULL,
                tpm_limit INTEGER DEFAULT NULL,
                tpd_limit INTEGER DEFAULT NULL,
                monthly_token_budget TEXT DEFAULT NULL,
                context_window INTEGER NOT NULL DEFAULT 0,
                enabled INTEGER NOT NULL DEFAULT 1,
                UNIQUE(platform, model_id)
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS fallback_config (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                model_db_id INTEGER NOT NULL UNIQUE,
                priority INTEGER NOT NULL,
                enabled INTEGER NOT NULL DEFAULT 1,
                FOREIGN KEY(model_db_id) REFERENCES models(id) ON DELETE CASCADE
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS requests (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                platform TEXT NOT NULL,
                model_id TEXT NOT NULL,
                key_id INTEGER DEFAULT 0,
                status TEXT NOT NULL,
                input_tokens INTEGER NOT NULL DEFAULT 0,
                output_tokens INTEGER NOT NULL DEFAULT 0,
                latency_ms INTEGER NOT NULL DEFAULT 0,
                error TEXT DEFAULT NULL,
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS rate_limit_usage (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                platform TEXT NOT NULL,
                model_id TEXT NOT NULL,
                key_id INTEGER NOT NULL,
                kind TEXT NOT NULL,
                tokens INTEGER NOT NULL DEFAULT 0,
                created_at_ms INTEGER NOT NULL
            )
        ");

        $db->exec("
            CREATE INDEX IF NOT EXISTS idx_rate_limit_usage_lookup
            ON rate_limit_usage(platform, model_id, key_id, kind, created_at_ms)
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS rate_limit_cooldowns (
                platform TEXT NOT NULL,
                model_id TEXT NOT NULL,
                key_id INTEGER NOT NULL,
                expires_at_ms INTEGER NOT NULL,
                created_at TEXT NOT NULL DEFAULT (datetime('now')),
                PRIMARY KEY(platform, model_id, key_id)
            )
        ");

        self::ensureUnifiedApiKey();
        self::seedModels();
    }

    public static function getUnifiedApiKey(): string {
        self::ensureUnifiedApiKey();

        $db = self::getInstance();
        $stmt = $db->prepare("SELECT value FROM settings WHERE key = 'unified_api_key'");
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);

        return $row['value'] ?? self::regenerateUnifiedApiKey();
    }

    public static function regenerateUnifiedApiKey(): string {
        $key = UNIFIED_KEY_PREFIX . bin2hex(random_bytes(24));
        $db = self::getInstance();
        $stmt = $db->prepare("
            INSERT INTO settings (key, value)
            VALUES ('unified_api_key', :value)
            ON CONFLICT(key) DO UPDATE SET value = excluded.value
        ");
        $stmt->bindValue(':value', $key, SQLITE3_TEXT);
        $stmt->execute();

        return $key;
    }

    private static function ensureUnifiedApiKey(): void {
        $db = self::getInstance();
        $result = $db->querySingle("SELECT value FROM settings WHERE key = 'unified_api_key'");
        if (!$result) {
            self::regenerateUnifiedApiKey();
        }
    }

    private static function seedModels(): void {
        $db = self::getInstance();
        $count = (int)$db->querySingle('SELECT COUNT(*) FROM models');
        if ($count > 0) {
            self::ensureFallbackConfig();
            return;
        }

        $stmt = $db->prepare("
            INSERT INTO models (
                platform, model_id, display_name, intelligence_rank, speed_rank, size_label,
                rpm_limit, rpd_limit, tpm_limit, tpd_limit, monthly_token_budget, context_window, enabled
            ) VALUES (
                :platform, :model_id, :display_name, :intelligence_rank, :speed_rank, :size_label,
                :rpm_limit, :rpd_limit, :tpm_limit, :tpd_limit, :monthly_token_budget, :context_window, 1
            )
        ");

        foreach (ModelRegistry::getModels() as $model) {
            $stmt->reset();
            $stmt->clear();
            $stmt->bindValue(':platform', $model[0], SQLITE3_TEXT);
            $stmt->bindValue(':model_id', $model[1], SQLITE3_TEXT);
            $stmt->bindValue(':display_name', $model[2], SQLITE3_TEXT);
            $stmt->bindValue(':intelligence_rank', $model[3], SQLITE3_INTEGER);
            $stmt->bindValue(':speed_rank', $model[4], SQLITE3_INTEGER);
            $stmt->bindValue(':size_label', $model[5], SQLITE3_TEXT);
            self::bindNullableInt($stmt, ':rpm_limit', $model[6]);
            self::bindNullableInt($stmt, ':rpd_limit', $model[7]);
            self::bindNullableInt($stmt, ':tpm_limit', $model[8]);
            self::bindNullableInt($stmt, ':tpd_limit', $model[9]);
            $stmt->bindValue(':monthly_token_budget', $model[10], SQLITE3_TEXT);
            $stmt->bindValue(':context_window', $model[11], SQLITE3_INTEGER);
            $stmt->execute();
        }

        self::ensureFallbackConfig();
    }

    private static function ensureFallbackConfig(): void {
        $db = self::getInstance();
        $existing = (int)$db->querySingle('SELECT COUNT(*) FROM fallback_config');
        if ($existing > 0) {
            return;
        }

        $result = $db->query('SELECT id FROM models WHERE enabled = 1 ORDER BY intelligence_rank DESC, speed_rank DESC');
        $priority = 1;
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $stmt = $db->prepare('INSERT INTO fallback_config (model_db_id, priority, enabled) VALUES (:model_db_id, :priority, 1)');
            $stmt->bindValue(':model_db_id', (int)$row['id'], SQLITE3_INTEGER);
            $stmt->bindValue(':priority', $priority++, SQLITE3_INTEGER);
            $stmt->execute();
        }
    }

    private static function bindNullableInt(SQLite3Stmt $stmt, string $name, $value): void {
        if ($value === null || $value === '') {
            $stmt->bindValue($name, null, SQLITE3_NULL);
            return;
        }

        $stmt->bindValue($name, (int)$value, SQLITE3_INTEGER);
    }
}
