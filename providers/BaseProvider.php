<?php
/**
 * Base provider class for all LLM providers
 */

abstract class BaseProvider {
    /**
     * Non-streaming chat completion
     */
    abstract public function chatCompletion(
        string $apiKey,
        array $messages,
        string $modelId,
        ?array $options = null
    ): array;
    
    /**
     * Streaming chat completion
     */
    abstract public function streamChatCompletion(
        string $apiKey,
        array $messages,
        string $modelId,
        ?array $options = null
    );
    
    /**
     * Validate API key
     */
    abstract public function validateKey(string $apiKey): bool;
    
    /**
     * Make HTTP request with timeout
     */
    protected function fetchWithTimeout(string $url, array $opts, int $timeoutMs = 15000): array {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT_MS => $timeoutMs,
            CURLOPT_CONNECTTIMEOUT_MS => min($timeoutMs, 5000),
            CURLOPT_HTTPHEADER => array_merge(
                $opts['headers'] ?? [],
                ['User-Agent: FreeLLMAPI/1.0']
            ),
            CURLOPT_POST => isset($opts['body']),
            CURLOPT_POSTFIELDS => $opts['body'] ?? '',
            CURLOPT_CUSTOMREQUEST => $opts['method'] ?? 'POST',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("HTTP request failed (Error $errno): $error");
        }
        
        return [
            'status' => $httpCode,
            'body' => $response,
        ];
    }
    
    /**
     * Generate unique ID
     */
    protected function makeId(): string {
        return 'chatcmpl-' . time() . '-' . substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 6);
    }
}
