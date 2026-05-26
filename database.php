<?php
/**
 * Database initialization and management
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?SQLite3 $db = null;
    
    public static function getInstance(): SQLite3 {
        if (self::$db === null) {
            self::init();
        }
        return self::$db;
    }
    
    public static function init(): void {
        // Create data directory if needed
        $dataDir = dirname(DB_PATH);
        if (!file_exists($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        
        self::$db = new SQLite3(DB_PATH);
        self::$db->enableExceptions(true);
        
        // Enable foreign keys
        self::$db->exec('PRAGMA foreign_keys = ON');
        // Enable WAL mode for better concurrency
        self::$db->exec('PRAGMA journal_mode = WAL');
        
        self::createTables();
        self::initEncryptionKey();
        self::seedModels();
        self::ensureUnifiedKey();
        
        echo "Database initialized at " . DB_PATH . "\n";
    }
    
    private static function createTables(): void {
        $db = self::$db;
        
        $db->exec("
            CREATE TABLE IF NOT EXISTS models (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                platform TEXT NOT NULL,
                model_id TEXT NOT NULL,
                display_name TEXT NOT NULL,
                intelligence_rank INTEGER NOT NULL,
                speed_rank INTEGER NOT NULL,
                size_label TEXT NOT NULL DEFAULT '',
                rpm_limit INTEGER,
                rpd_limit INTEGER,
                tpm_limit INTEGER,
                tpd_limit INTEGER,
                monthly_token_budget TEXT NOT NULL DEFAULT '',
                context_window INTEGER,
                enabled INTEGER NOT NULL DEFAULT 1,
                UNIQUE(platform, model_id)
            )
        ");
        
        $db->exec("
            CREATE TABLE IF NOT EXISTS api_keys (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                platform TEXT NOT NULL,
                label TEXT NOT NULL DEFAULT '',
                encrypted_key TEXT NOT NULL,
                iv TEXT NOT NULL,
                auth_tag TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'unknown',
                enabled INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL DEFAULT (datetime('now')),
                last_checked_at TEXT
            )
        ");
        
        $db->exec("
            CREATE TABLE IF NOT EXISTS requests (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                platform TEXT NOT NULL,
                model_id TEXT NOT NULL,
                key_id INTEGER,
                status TEXT NOT NULL,
                input_tokens INTEGER NOT NULL DEFAULT 0,
                output_tokens INTEGER NOT NULL DEFAULT 0,
                latency_ms INTEGER NOT NULL DEFAULT 0,
                error TEXT,
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            )
        ");
        
        $db->exec("
            CREATE TABLE IF NOT EXISTS rate_limit_usage (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                platform TEXT NOT NULL,
                model_id TEXT NOT NULL,
                key_id INTEGER NOT NULL,
                kind TEXT NOT NULL CHECK (kind IN ('request', 'tokens')),
                tokens INTEGER NOT NULL DEFAULT 0,
                created_at_ms INTEGER NOT NULL,
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            )
        ");
        
        $db->exec("
            CREATE TABLE IF NOT EXISTS rate_limit_cooldowns (
                platform TEXT NOT NULL,
                model_id TEXT NOT NULL,
                key_id INTEGER NOT NULL,
                expires_at_ms INTEGER NOT NULL,
                created_at TEXT NOT NULL DEFAULT (datetime('now')),
                PRIMARY KEY (platform, model_id, key_id)
            )
        ");
        
        $db->exec("
            CREATE TABLE IF NOT EXISTS fallback_config (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                model_db_id INTEGER NOT NULL REFERENCES models(id),
                priority INTEGER NOT NULL,
                enabled INTEGER NOT NULL DEFAULT 1,
                UNIQUE(model_db_id)
            )
        ");
        
        $db->exec("
            CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL
            )
        ");
        
        // Create indexes
        $db->exec("CREATE INDEX IF NOT EXISTS idx_requests_created_at ON requests(created_at)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_requests_platform ON requests(platform)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_rate_limit_usage_lookup ON rate_limit_usage(platform, model_id, key_id, kind, created_at_ms)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_rate_limit_cooldowns_expires ON rate_limit_cooldowns(expires_at_ms)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_api_keys_platform ON api_keys(platform)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_requests_key_id ON requests(key_id)");
    }
    
    private static function initEncryptionKey(): void {
        // Check if already set in memory
        if (defined('ENCRYPTION_KEY') && strlen(ENCRYPTION_KEY) === 64) {
            return;
        }
        
        // Try to get from database
        $result = self::$db->querySingle("SELECT value FROM settings WHERE key = 'encryption_key'");
        if ($result) {
            define('ENCRYPTION_KEY', $result);
            return;
        }
        
        // Check if we have a temp key from config (first-run scenario)
        if (defined('ENCRYPTION_KEY_TEMP')) {
            $newKey = ENCRYPTION_KEY_TEMP;
        } else {
            // Generate new key
            $newKey = bin2hex(random_bytes(32));
        }
        
        $stmt = self::$db->prepare("INSERT INTO settings (key, value) VALUES (:key, :value)");
        $stmt->bindValue(':key', SQLITE3_TEXT, 'encryption_key');
        $stmt->bindValue(':value', SQLITE3_TEXT, $newKey);
        $stmt->execute();
        
        define('ENCRYPTION_KEY', $newKey);
    }
    
    private static function seedModels(): void {
        $count = self::$db->querySingle("SELECT COUNT(*) as cnt FROM models");
        if ($count > 0) {
            return;
        }
        
        $models = [
            // Google
            ['google', 'gemini-2.5-pro', 'Gemini 2.5 Pro', 1, 8, 'Frontier', 5, 100, 250000, null, '~12M', 1048576],
            ['google', 'gemini-2.5-flash', 'Gemini 2.5 Flash', 4, 5, 'Large', 10, 20, 250000, null, '~3M', 1048576],
            ['google', 'gemini-2.5-flash-lite', 'Gemini 2.5 Flash-Lite', 8, 3, 'Medium', 15, 1000, 250000, null, '~120M', 1048576],
            // OpenRouter
            ['openrouter', 'deepseek/deepseek-v3.1:free', 'DeepSeek V3.1 (free)', 2, 10, 'Frontier', 20, 200, null, null, '~6M', 131072],
            ['openrouter', 'moonshotai/kimi-k2:free', 'Kimi K2 (free)', 2, 9, 'Frontier', 20, 200, null, null, '~6M', 131072],
            ['openrouter', 'qwen/qwen3-coder:free', 'Qwen3 Coder (free)', 3, 9, 'Frontier', 20, 200, null, null, '~6M', 262144],
            ['openrouter', 'z-ai/glm-4.5-air:free', 'GLM-4.5 Air (free)', 4, 9, 'Large', 20, 200, null, null, '~6M', 131072],
            // Cerebras
            ['cerebras', 'qwen-3-coder-480b', 'Qwen3-Coder 480B', 2, 1, 'Frontier', 30, null, 60000, 1000000, '~30M', 131072],
            ['cerebras', 'llama-4-maverick-17b-128e-instruct', 'Llama 4 Maverick', 3, 1, 'Frontier', 30, null, 60000, 1000000, '~30M', 131072],
            ['cerebras', 'qwen3-235b', 'Qwen3 235B', 3, 1, 'Large', 30, null, 60000, 1000000, '~30M', 8192],
            ['cerebras', 'gpt-oss-120b', 'GPT-OSS 120B', 3, 1, 'Large', 30, null, 60000, 1000000, '~30M', 131072],
            // GitHub Models
            ['github', 'openai/gpt-5', 'GPT-5 (GitHub)', 1, 7, 'Frontier', 10, 50, null, null, '~18M', 128000],
            // SambaNova
            ['sambanova', 'Meta-Llama-3.3-70B-Instruct', 'Llama 3.3 70B', 6, 9, 'Large', 20, null, null, 200000, '~6M', 8192],
            // Mistral
            ['mistral', 'mistral-large-latest', 'Mistral Large 3', 7, 8, 'Large', 2, null, 500000, null, '~50-100M', 131072],
            ['mistral', 'magistral-medium-latest', 'Magistral Medium', 4, 8, 'Large', 2, null, 500000, null, '~50-100M', 40000],
            ['mistral', 'codestral-latest', 'Codestral', 6, 6, 'Medium', 2, null, 500000, null, '~50-100M', 32000],
            // Groq
            ['groq', 'llama-3.3-70b-versatile', 'Llama 3.3 70B', 9, 2, 'Medium', 30, 1000, 6000, 500000, '~15M', 131072],
            ['groq', 'llama-4-scout-17b-16e-instruct', 'Llama 4 Scout', 10, 2, 'Medium', 30, 1000, 6000, 1000000, '~30M', 131072],
            // NVIDIA NIM (disabled by default)
            ['nvidia', 'meta/llama-3.1-70b-instruct', 'Llama 3.1 70B (NV)', 11, 6, 'Large', 40, null, null, null, 'credits-based', 131072],
            // Cohere
            ['cohere', 'command-r-plus-08-2024', 'Command R+ (08-2024)', 12, 11, 'Large', 20, 33, null, null, '~1-2M', 131072],
            // Cloudflare
            ['cloudflare', '@cf/meta/llama-3.1-70b-instruct', 'Llama 3.1 70B (CF)', 13, 11, 'Medium', null, null, null, null, '~18-45M', 131072],
            // HuggingFace
            ['huggingface', 'accounts/fireworks/models/llama-v3p3-70b-instruct', 'Llama 3.3 70B (HF)', 14, 11, 'Medium', null, null, null, null, '~1-3M', 131072],
            // Zhipu
            ['zhipu', 'glm-4.5-flash', 'GLM-4.5 Flash', 5, 4, 'Large', null, null, null, 1000000, '~30M', 131072],
        ];
        
        $stmt = self::$db->prepare("
            INSERT INTO models (platform, model_id, display_name, intelligence_rank, speed_rank, size_label, rpm_limit, rpd_limit, tpm_limit, tpd_limit, monthly_token_budget, context_window)
            VALUES (:platform, :model_id, :display_name, :intelligence_rank, :speed_rank, :size_label, :rpm_limit, :rpd_limit, :tpm_limit, :tpd_limit, :monthly_token_budget, :context_window)
        ");
        
        foreach ($models as $model) {
            $stmt->bindValue(':platform', SQLITE3_TEXT, $model[0]);
            $stmt->bindValue(':model_id', SQLITE3_TEXT, $model[1]);
            $stmt->bindValue(':display_name', SQLITE3_TEXT, $model[2]);
            $stmt->bindValue(':intelligence_rank', SQLITE3_INTEGER, $model[3]);
            $stmt->bindValue(':speed_rank', SQLITE3_INTEGER, $model[4]);
            $stmt->bindValue(':size_label', SQLITE3_TEXT, $model[5]);
            $stmt->bindValue(':rpm_limit', $model[6] === null ? SQLITE3_NULL : SQLITE3_INTEGER, $model[6]);
            $stmt->bindValue(':rpd_limit', $model[7] === null ? SQLITE3_NULL : SQLITE3_INTEGER, $model[7]);
            $stmt->bindValue(':tpm_limit', $model[8] === null ? SQLITE3_NULL : SQLITE3_INTEGER, $model[8]);
            $stmt->bindValue(':tpd_limit', $model[9] === null ? SQLITE3_NULL : SQLITE3_INTEGER, $model[9]);
            $stmt->bindValue(':monthly_token_budget', SQLITE3_TEXT, $model[10]);
            $stmt->bindValue(':context_window', SQLITE3_INTEGER, $model[11]);
            $stmt->execute();
        }
        
        // Seed fallback config
        $result = self::$db->query("SELECT id, intelligence_rank FROM models ORDER BY intelligence_rank ASC");
        $priority = 1;
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $stmt = self::$db->prepare("INSERT INTO fallback_config (model_db_id, priority, enabled) VALUES (:model_db_id, :priority, 1)");
            $stmt->bindValue(':model_db_id', SQLITE3_INTEGER, $row['id']);
            $stmt->bindValue(':priority', SQLITE3_INTEGER, $priority++);
            $stmt->execute();
        }
        
        echo "Seeded " . count($models) . " models and fallback config\n";
    }
    
    private static function ensureUnifiedKey(): void {
        $result = self::$db->querySingle("SELECT value FROM settings WHERE key = 'unified_api_key'");
        if (!$result) {
            $newKey = UNIFIED_KEY_PREFIX . bin2hex(random_bytes(16));
            $stmt = self::$db->prepare("INSERT INTO settings (key, value) VALUES (:key, :value)");
            $stmt->bindValue(':key', SQLITE3_TEXT, 'unified_api_key');
            $stmt->bindValue(':value', SQLITE3_TEXT, $newKey);
            $stmt->execute();
            echo "Generated new unified API key\n";
        }
    }
    
    public static function getUnifiedApiKey(): string {
        $result = self::$db->querySingle("SELECT value FROM settings WHERE key = 'unified_api_key'");
        return $result ?: '';
    }
    
    public static function regenerateUnifiedApiKey(): string {
        $newKey = UNIFIED_KEY_PREFIX . bin2hex(random_bytes(16));
        $stmt = self::$db->prepare("UPDATE settings SET value = :value WHERE key = :key");
        $stmt->bindValue(':key', SQLITE3_TEXT, 'unified_api_key');
        $stmt->bindValue(':value', SQLITE3_TEXT, $newKey);
        $stmt->execute();
        return $newKey;
    }
}
