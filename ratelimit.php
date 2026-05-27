<?php
/**
 * Rate limiting service with SQLite persistence
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

class RateLimitService {
    private static array $windows = [];
    
    private const MINUTE = 60000;
    private const DAY = 86400000;
    
    /**
     * Get or create a window for rate limiting
     */
    private static function getWindow(string $key): array {
        if (!isset(self::$windows[$key])) {
            self::$windows[$key] = [
                'timestamps' => [],
                'tokenCount' => 0,
                'tokenTimestamps' => [],
            ];
        }
        return self::$windows[$key];
    }
    
    /**
     * Prune timestamps outside the window
     */
    private static function pruneTimestamps(array $timestamps, int $windowMs, int $now): array {
        $cutoff = $now - $windowMs;
        return array_values(array_filter($timestamps, fn($ts) => $ts > $cutoff));
    }
    
    /**
     * Record usage in database
     */
    private static function recordUsage(string $platform, string $modelId, int $keyId, string $kind, int $tokens, int $now): void {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                INSERT INTO rate_limit_usage (platform, model_id, key_id, kind, tokens, created_at_ms)
                VALUES (:platform, :model_id, :key_id, :kind, :tokens, :created_at_ms)
            ");
            $stmt->bindValue(':platform', $platform, SQLITE3_TEXT);
            $stmt->bindValue(':model_id', $modelId, SQLITE3_TEXT);
            $stmt->bindValue(':key_id', $keyId, SQLITE3_INTEGER);
            $stmt->bindValue(':kind', $kind, SQLITE3_TEXT);
            $stmt->bindValue(':tokens', $tokens, SQLITE3_INTEGER);
            $stmt->bindValue(':created_at_ms', $now, SQLITE3_INTEGER);
            $stmt->execute();
            
            // Clean old records
            $db->exec("DELETE FROM rate_limit_usage WHERE created_at_ms <= " . ($now - self::DAY));
        } catch (Exception $e) {
            // Ignore DB errors, fall back to memory
        }
    }
    
    /**
     * Count persisted requests in window
     */
    private static function countPersistedRequests(string $platform, string $modelId, int $keyId, int $windowMs, int $now): ?int {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT COUNT(*) AS used
                FROM rate_limit_usage
                WHERE platform = :platform
                  AND model_id = :model_id
                  AND key_id = :key_id
                  AND kind = 'request'
                  AND created_at_ms > :cutoff
            ");
            $stmt->bindValue(':platform', $platform, SQLITE3_TEXT);
            $stmt->bindValue(':model_id', $modelId, SQLITE3_TEXT);
            $stmt->bindValue(':key_id', $keyId, SQLITE3_INTEGER);
            $stmt->bindValue(':cutoff', $now - $windowMs, SQLITE3_INTEGER);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);
            return $row['used'] ?? 0;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Sum persisted tokens in window
     */
    private static function sumPersistedTokens(string $platform, string $modelId, int $keyId, int $windowMs, int $now): ?int {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT COALESCE(SUM(tokens), 0) AS used
                FROM rate_limit_usage
                WHERE platform = :platform
                  AND model_id = :model_id
                  AND key_id = :key_id
                  AND kind = 'tokens'
                  AND created_at_ms > :cutoff
            ");
            $stmt->bindValue(':platform', $platform, SQLITE3_TEXT);
            $stmt->bindValue(':model_id', $modelId, SQLITE3_TEXT);
            $stmt->bindValue(':key_id', $keyId, SQLITE3_INTEGER);
            $stmt->bindValue(':cutoff', $now - $windowMs, SQLITE3_INTEGER);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);
            return (int)($row['used'] ?? 0);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get request count from memory
     */
    private static function memoryRequestCount(string $key, int $windowMs, int $now): int {
        $w = self::getWindow($key);
        $w['timestamps'] = self::pruneTimestamps($w['timestamps'], $windowMs, $now);
        return count($w['timestamps']);
    }
    
    /**
     * Get token count from memory
     */
    private static function memoryTokenCount(string $key, int $windowMs, int $now): int {
        $w = self::getWindow($key);
        $w['tokenTimestamps'] = array_filter($w['tokenTimestamps'], fn($t) => $t['ts'] > $now - $windowMs);
        return array_sum(array_column($w['tokenTimestamps'], 'tokens'));
    }
    
    /**
     * Get total request count (DB + memory)
     */
    private static function requestCount(string $platform, string $modelId, int $keyId, int $windowMs, int $now): int {
        $persisted = self::countPersistedRequests($platform, $modelId, $keyId, $windowMs, $now);
        if ($persisted !== null) {
            return $persisted;
        }
        $type = $windowMs === self::MINUTE ? 'rpm' : 'rpd';
        return self::memoryRequestCount("$platform:$modelId:$keyId:$type", $windowMs, $now);
    }
    
    /**
     * Get total token count (DB + memory)
     */
    private static function tokenCount(string $platform, string $modelId, int $keyId, int $windowMs, int $now): int {
        $persisted = self::sumPersistedTokens($platform, $modelId, $keyId, $windowMs, $now);
        if ($persisted !== null) {
            return $persisted;
        }
        $type = $windowMs === self::MINUTE ? 'tpm' : 'tpd';
        return self::memoryTokenCount("$platform:$modelId:$keyId:$type", $windowMs, $now);
    }
    
    /**
     * Check if a request can be made
     */
    public static function canMakeRequest(string $platform, string $modelId, int $keyId, array $limits): bool {
        $now = (int)(microtime(true) * 1000);
        
        if ($limits['rpm'] !== null && self::requestCount($platform, $modelId, $keyId, self::MINUTE, $now) >= $limits['rpm']) {
            return false;
        }
        
        if ($limits['rpd'] !== null && self::requestCount($platform, $modelId, $keyId, self::DAY, $now) >= $limits['rpd']) {
            return false;
        }
        
        if ($limits['tpm'] !== null && self::tokenCount($platform, $modelId, $keyId, self::MINUTE, $now) >= $limits['tpm']) {
            return false;
        }
        
        if ($limits['tpd'] !== null && self::tokenCount($platform, $modelId, $keyId, self::DAY, $now) >= $limits['tpd']) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Record a request
     */
    public static function recordRequest(string $platform, string $modelId, int $keyId): void {
        $now = (int)(microtime(true) * 1000);
        self::recordUsage($platform, $modelId, $keyId, 'request', 1, $now);
        
        // Also record in memory
        $key = "$platform:$modelId:$keyId:rpm";
        $w = self::getWindow($key);
        $w['timestamps'][] = $now;
    }
    
    /**
     * Record tokens used
     */
    public static function recordTokens(string $platform, string $modelId, int $keyId, int $tokens): void {
        $now = (int)(microtime(true) * 1000);
        self::recordUsage($platform, $modelId, $keyId, 'tokens', $tokens, $now);
        
        // Also record in memory
        $key = "$platform:$modelId:$keyId:tpm";
        $w = self::getWindow($key);
        $w['tokenTimestamps'][] = ['ts' => $now, 'tokens' => $tokens];
    }
    
    /**
     * Set cooldown for a rate-limited key
     */
    public static function setCooldown(string $platform, string $modelId, int $keyId, int $durationMs): void {
        try {
            $db = Database::getInstance();
            $now = (int)(microtime(true) * 1000);
            $expiresAt = $now + $durationMs;
            
            $stmt = $db->prepare("
                INSERT OR REPLACE INTO rate_limit_cooldowns (platform, model_id, key_id, expires_at_ms, created_at)
                VALUES (:platform, :model_id, :key_id, :expires_at_ms, datetime('now'))
            ");
            $stmt->bindValue(':platform', $platform, SQLITE3_TEXT);
            $stmt->bindValue(':model_id', $modelId, SQLITE3_TEXT);
            $stmt->bindValue(':key_id', $keyId, SQLITE3_INTEGER);
            $stmt->bindValue(':expires_at_ms', $expiresAt, SQLITE3_INTEGER);
            $stmt->execute();
        } catch (Exception $e) {
            // Ignore
        }
    }
    
    /**
     * Check if a key is on cooldown
     */
    public static function isOnCooldown(string $platform, string $modelId, int $keyId): bool {
        try {
            $db = Database::getInstance();
            $now = (int)(microtime(true) * 1000);
            
            $stmt = $db->prepare("
                SELECT expires_at_ms FROM rate_limit_cooldowns
                WHERE platform = :platform AND model_id = :model_id AND key_id = :key_id
                AND expires_at_ms > :now
            ");
            $stmt->bindValue(':platform', $platform, SQLITE3_TEXT);
            $stmt->bindValue(':model_id', $modelId, SQLITE3_TEXT);
            $stmt->bindValue(':key_id', $keyId, SQLITE3_INTEGER);
            $stmt->bindValue(':now', $now, SQLITE3_INTEGER);
            $result = $stmt->execute();
            return $result->fetchArray(SQLITE3_ASSOC) !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get next cooldown duration
     */
    public static function getNextCooldownDuration(string $platform, string $modelId, int $keyId): int {
        try {
            $db = Database::getInstance();
            $now = (int)(microtime(true) * 1000);
            
            $stmt = $db->prepare("
                SELECT expires_at_ms FROM rate_limit_cooldowns
                WHERE platform = :platform AND model_id = :model_id AND key_id = :key_id
                ORDER BY expires_at_ms ASC LIMIT 1
            ");
            $stmt->bindValue(':platform', $platform, SQLITE3_TEXT);
            $stmt->bindValue(':model_id', $modelId, SQLITE3_TEXT);
            $stmt->bindValue(':key_id', $keyId, SQLITE3_INTEGER);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);
            
            if ($row && $row['expires_at_ms'] > $now) {
                return $row['expires_at_ms'] - $now;
            }
        } catch (Exception $e) {
            // Ignore
        }
        return 0;
    }
}
