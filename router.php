<?php
/**
 * Router service - selects best available model for each request
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/ratelimit.php';
require_once __DIR__ . '/crypto.php';

class RouterService {
    private static array $roundRobinIndex = [];
    private static array $rateLimitPenalties = [];
    
    /**
     * Record a rate limit hit for a model
     */
    public static function recordRateLimitHit(int $modelDbId): void {
        $now = (int)(microtime(true) * 1000);
        
        if (isset(self::$rateLimitPenalties[$modelDbId])) {
            self::$rateLimitPenalties[$modelDbId]['count']++;
            self::$rateLimitPenalties[$modelDbId]['lastHit'] = $now;
            self::$rateLimitPenalties[$modelDbId]['penalty'] = min(
                self::$rateLimitPenalties[$modelDbId]['penalty'] + PENALTY_PER_429,
                MAX_PENALTY
            );
        } else {
            self::$rateLimitPenalties[$modelDbId] = [
                'count' => 1,
                'lastHit' => $now,
                'penalty' => PENALTY_PER_429,
            ];
        }
    }
    
    /**
     * Record a success for a model
     */
    public static function recordSuccess(int $modelDbId): void {
        if (isset(self::$rateLimitPenalties[$modelDbId])) {
            self::$rateLimitPenalties[$modelDbId]['penalty'] = max(0, self::$rateLimitPenalties[$modelDbId]['penalty'] - 1);
            if (self::$rateLimitPenalties[$modelDbId]['penalty'] === 0) {
                unset(self::$rateLimitPenalties[$modelDbId]);
            }
        }
    }
    
    /**
     * Get penalty for a model with time-based decay
     */
    private static function getPenalty(int $modelDbId): int {
        if (!isset(self::$rateLimitPenalties[$modelDbId])) {
            return 0;
        }
        
        $entry = &self::$rateLimitPenalties[$modelDbId];
        $now = (int)(microtime(true) * 1000);
        $elapsed = $now - $entry['lastHit'];
        $decaySteps = (int)($elapsed / PENALTY_DECAY_INTERVAL);
        
        if ($decaySteps > 0) {
            $entry['penalty'] = max(0, $entry['penalty'] - ($decaySteps * PENALTY_DECAY_AMOUNT));
            $entry['lastHit'] = $now;
            
            if ($entry['penalty'] === 0) {
                unset(self::$rateLimitPenalties[$modelDbId]);
                return 0;
            }
        }
        
        return $entry['penalty'];
    }
    
    /**
     * Get all penalties
     */
    public static function getAllPenalties(): array {
        $result = [];
        foreach (self::$rateLimitPenalties as $modelDbId => $entry) {
            $penalty = self::getPenalty($modelDbId);
            if ($penalty > 0) {
                $result[] = [
                    'modelDbId' => $modelDbId,
                    'count' => $entry['count'],
                    'penalty' => $penalty,
                ];
            }
        }
        usort($result, fn($a, $b) => $b['penalty'] - $a['penalty']);
        return $result;
    }
    
    /**
     * Route a request to the best available model
     */
    public static function routeRequest(int $estimatedTokens = 1000, ?array $skipKeys = null, ?int $preferredModelDbId = null): ?array {
        $db = Database::getInstance();
        
        // Get fallback chain
        $result = $db->query("
            SELECT fc.model_db_id, fc.priority, fc.enabled
            FROM fallback_config fc
            ORDER BY fc.priority ASC
        ");
        
        $fallbackChain = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $fallbackChain[] = $row;
        }
        
        // Apply dynamic penalties
        foreach ($fallbackChain as &$entry) {
            $entry['effectivePriority'] = $entry['priority'] + self::getPenalty($entry['model_db_id']);
        }
        
        // Sort by effective priority
        usort($fallbackChain, fn($a, $b) => $a['effectivePriority'] - $b['effectivePriority']);
        
        // Sticky session: move preferred model to front
        if ($preferredModelDbId !== null) {
            foreach ($fallbackChain as $i => $entry) {
                if ($entry['model_db_id'] == $preferredModelDbId) {
                    array_unshift($fallbackChain, array_splice($fallbackChain, $i, 1)[0]);
                    break;
                }
            }
        }
        
        // Try each model in the chain
        foreach ($fallbackChain as $entry) {
            if (!$entry['enabled']) {
                continue;
            }
            
            $modelDbId = $entry['model_db_id'];
            
            // Get model details
            $stmt = $db->prepare("SELECT * FROM models WHERE id = :id AND enabled = 1");
            $stmt->bindValue(':id', $modelDbId, SQLITE3_INTEGER);
            $modelResult = $stmt->execute();
            $model = $modelResult->fetchArray(SQLITE3_ASSOC);
            
            if (!$model) {
                continue;
            }
            
            // Get available keys for this platform
            $stmt = $db->prepare("SELECT * FROM api_keys WHERE platform = :platform AND enabled = 1 AND status != 'invalid'");
            $stmt->bindValue(':platform', $model['platform'], SQLITE3_TEXT);
            $keysResult = $stmt->execute();
            
            $keys = [];
            while ($key = $keysResult->fetchArray(SQLITE3_ASSOC)) {
                $keys[] = $key;
            }
            
            if (empty($keys)) {
                continue;
            }
            
            // Round-robin through keys
            if (!isset(self::$roundRobinIndex[$model['platform']])) {
                self::$roundRobinIndex[$model['platform']] = 0;
            }
            
            for ($i = 0; $i < count($keys); $i++) {
                $keyIndex = (self::$roundRobinIndex[$model['platform']] + $i) % count($keys);
                $key = $keys[$keyIndex];
                
                // Check skip list
                $skipKey = "{$model['platform']}:{$model['model_id']}:{$key['id']}";
                if ($skipKeys !== null && in_array($skipKey, $skipKeys)) {
                    continue;
                }
                
                // Check cooldown
                if (RateLimitService::isOnCooldown($model['platform'], $model['model_id'], $key['id'])) {
                    continue;
                }
                
                // Check rate limits
                $limits = [
                    'rpm' => $model['rpm_limit'],
                    'rpd' => $model['rpd_limit'],
                    'tpm' => $model['tpm_limit'],
                    'tpd' => $model['tpd_limit'],
                ];
                
                if (!RateLimitService::canMakeRequest($model['platform'], $model['model_id'], $key['id'], $limits)) {
                    continue;
                }
                
                // Decrypt API key
                try {
                    $apiKey = Crypto::decrypt($key['encrypted_key'], $key['iv'], $key['auth_tag']);
                } catch (Exception $e) {
                    continue;
                }
                
                // Update round-robin index
                self::$roundRobinIndex[$model['platform']] = ($keyIndex + 1) % count($keys);
                
                return [
                    'provider' => $model['platform'],
                    'modelId' => $model['model_id'],
                    'modelDbId' => $modelDbId,
                    'apiKey' => $apiKey,
                    'keyId' => $key['id'],
                    'platform' => $model['platform'],
                    'displayName' => $model['display_name'],
                ];
            }
        }
        
        return null;
    }
}
