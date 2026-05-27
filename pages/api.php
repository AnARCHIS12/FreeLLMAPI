<?php
/**
 * API Documentation Page
 */
?>
<div class="api-container">
    <div class="api-header">
        <h1 class="cyber-title">
            <span class="title-glow">API Documentation</span>
        </h1>
        <p class="api-subtitle">Integrate FreeLLMAPI into your applications</p>
    </div>

    <!-- Quick Start -->
    <div class="api-section">
        <h2><i class="fa-solid fa-play" aria-hidden="true"></i> Quick Start</h2>
        <div class="quick-start-card">
            <p>Use the unified endpoint to chat with AI models. The system automatically routes your request to the best available provider.</p>
            <div class="code-block">
                <div class="code-header">
                    <span>Endpoint</span>
                    <button class="copy-btn" onclick="copyCode('endpoint')">Copy</button>
                </div>
                <pre id="endpoint">POST /api/chat.php</pre>
            </div>
            <div class="code-block">
                <div class="code-header">
                    <span>Authentication</span>
                    <button class="copy-btn" onclick="copyCode('auth')">Copy</button>
                </div>
                <pre id="auth">Authorization: Bearer <?php echo htmlspecialchars($unifiedApiKey); ?></pre>
            </div>
        </div>
    </div>

    <!-- Chat Endpoint -->
    <div class="api-section">
        <h2><i class="fa-solid fa-comments" aria-hidden="true"></i> Chat Completion</h2>
        <p>Send a message and receive an AI response. Supports streaming for real-time output.</p>
        
        <h3>Request Format</h3>
        <div class="code-block">
            <div class="code-header">
                <span>Example Request</span>
                <button class="copy-btn" onclick="copyCode('chat-request')">Copy</button>
            </div>
            <pre id="chat-request">{
  "messages": [
    {"role": "user", "content": "Hello, how are you?"}
  ],
  "model_id": null,
  "stream": true,
  "temperature": 0.7,
  "max_tokens": 1024
}</pre>
        </div>

        <h3>Parameters</h3>
        <table class="params-table">
            <thead>
                <tr>
                    <th>Parameter</th>
                    <th>Type</th>
                    <th>Required</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>messages</code></td>
                    <td>array</td>
                    <td>Yes</td>
                    <td>Array of message objects with <code>role</code> and <code>content</code></td>
                </tr>
                <tr>
                    <td><code>model_id</code></td>
                    <td>integer|null</td>
                    <td>No</td>
                    <td>Specific model ID from database, or null for auto-routing</td>
                </tr>
                <tr>
                    <td><code>stream</code></td>
                    <td>boolean</td>
                    <td>No</td>
                    <td>Enable streaming response (default: true)</td>
                </tr>
                <tr>
                    <td><code>temperature</code></td>
                    <td>float</td>
                    <td>No</td>
                    <td>Response creativity (0.0-2.0, default: 0.7)</td>
                </tr>
                <tr>
                    <td><code>max_tokens</code></td>
                    <td>integer</td>
                    <td>No</td>
                    <td>Maximum tokens in response (default: 1024)</td>
                </tr>
            </tbody>
        </table>

        <h3>Streaming Response Format</h3>
        <div class="code-block">
            <div class="code-header">
                <span>Server-Sent Events</span>
            </div>
            <pre>data: {"choices":[{"delta":{"content":"Hello"}}]}
data: {"choices":[{"delta":{"content":"!"}}]}
data: [DONE]</pre>
        </div>

        <h3>Example Code</h3>
        <div class="code-block">
            <div class="code-header">
                <span>JavaScript Fetch</span>
                <button class="copy-btn" onclick="copyCode('js-example')">Copy</button>
            </div>
            <pre id="js-example">async function chat(message) {
  const response = await fetch('/api/chat.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer <?php echo $unifiedApiKey; ?>'
    },
    body: JSON.stringify({
      messages: [{ role: 'user', content: message }],
      stream: true
    })
  });

  const reader = response.body.getReader();
  const decoder = new TextDecoder();

  while (true) {
    const { done, value } = await reader.read();
    if (done) break;
    
    const chunk = decoder.decode(value);
    const lines = chunk.split('\n');
    
    for (const line of lines) {
      if (line.startsWith('data: ')) {
        const data = line.slice(6);
        if (data === '[DONE]') continue;
        
        const parsed = JSON.parse(data);
        const delta = parsed.choices?.[0]?.delta?.content || '';
        console.log(delta);
      }
    }
  }
}</pre>
        </div>

        <div class="code-block">
            <div class="code-header">
                <span>Python Example</span>
                <button class="copy-btn" onclick="copyCode('py-example')">Copy</button>
            </div>
            <pre id="py-example">import requests
import json

url = "http://localhost:3001/api/chat.php"
headers = {
    "Content-Type": "application/json",
    "Authorization": "Bearer <?php echo $unifiedApiKey; ?>"
}

data = {
    "messages": [{"role": "user", "content": "Hello!"}],
    "stream": True
}

response = requests.post(url, headers=headers, json=data, stream=True)

for line in response.iter_lines():
    if line.startswith(b'data: '):
        data = line[6:].decode()
        if data == '[DONE]':
            break
        try:
            chunk = json.loads(data)
            content = chunk['choices'][0]['delta'].get('content', '')
            print(content, end='', flush=True)
        except:
            pass</pre>
        </div>

        <div class="code-block">
            <div class="code-header">
                <span>cURL Example</span>
                <button class="copy-btn" onclick="copyCode('curl-example')">Copy</button>
            </div>
            <pre id="curl-example">curl -X POST http://localhost:3001/api/chat.php \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <?php echo $unifiedApiKey; ?>" \
  -d '{
    "messages": [{"role": "user", "content": "Hello!"}],
    "stream": false
  }'</pre>
        </div>
    </div>

    <!-- Models Endpoint -->
    <div class="api-section">
        <h2><i class="fa-solid fa-layer-group" aria-hidden="true"></i> List Available Models</h2>
        <p>Get a list of all available models with their capabilities and limits.</p>
        
        <div class="code-block">
            <div class="code-header">
                <span>Request</span>
            </div>
            <pre>GET /api/models.php</pre>
        </div>

        <div class="code-block">
            <div class="code-header">
                <span>Response</span>
            </div>
            <pre>[
  {
    "id": 1,
    "platform": "groq",
    "model_id": "llama-3.3-70b-versatile",
    "display_name": "Llama 3.3 70B",
    "intelligence_rank": 9,
    "speed_rank": 2,
    "size_label": "Medium",
    "rpm_limit": 30,
    "tpm_limit": 6000,
    "context_window": 131072,
    "enabled": true
  }
]</pre>
        </div>
    </div>

    <!-- Stats Endpoint -->
    <div class="api-section">
        <h2><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Get Statistics</h2>
        <p>Retrieve usage statistics for monitoring and analytics.</p>
        
        <div class="code-block">
            <div class="code-header">
                <span>Request</span>
            </div>
            <pre>GET /api/stats.php</pre>
        </div>

        <div class="code-block">
            <div class="code-header">
                <span>Response</span>
            </div>
            <pre>{
  "requests_today": 150,
  "tokens_used": 45000,
  "avg_latency": 234,
  "success_rate": 0.98,
  "active_providers": 8
}</pre>
        </div>
    </div>

    <!-- Error Handling -->
    <div class="api-section">
        <h2><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Error Handling</h2>
        <p>The API returns standard HTTP status codes with detailed error messages.</p>
        
        <table class="params-table">
            <thead>
                <tr>
                    <th>Status Code</th>
                    <th>Meaning</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>200</code></td>
                    <td>Success</td>
                </tr>
                <tr>
                    <td><code>400</code></td>
                    <td>Bad Request - Invalid parameters</td>
                </tr>
                <tr>
                    <td><code>401</code></td>
                    <td>Unauthorized - Invalid or missing API key</td>
                </tr>
                <tr>
                    <td><code>429</code></td>
                    <td>Rate Limited - Too many requests</td>
                </tr>
                <tr>
                    <td><code>500</code></td>
                    <td>Internal Server Error</td>
                </tr>
                <tr>
                    <td><code>503</code></td>
                    <td>Service Unavailable - No providers available</td>
                </tr>
            </tbody>
        </table>

        <div class="code-block">
            <div class="code-header">
                <span>Error Response Format</span>
            </div>
            <pre>{
  "error": "Rate limit exceeded",
  "code": 429,
  "retry_after": 60
}</pre>
        </div>
    </div>

    <!-- Rate Limits -->
    <div class="api-section">
        <h2><i class="fa-solid fa-list-check" aria-hidden="true"></i> Rate Limits</h2>
        <p>Rate limits vary by provider and model. The system automatically handles fallback when limits are reached.</p>
        
        <div class="limits-info">
            <p><strong>Note:</strong> These are the free tier limits imposed by each provider. Our system aggregates multiple providers to maximize your effective rate limit.</p>
            <ul>
                <li><strong>Groq:</strong> 30 RPM, 6,000 TPM</li>
                <li><strong>Cerebras:</strong> 30 RPM, 60,000 TPM</li>
                <li><strong>SambaNova:</strong> 20 RPM, 200,000 TPD</li>
                <li><strong>OpenRouter:</strong> 20 RPM (varies by model)</li>
                <li><strong>Mistral:</strong> 2 RPM, 500,000 TPM</li>
                <li><strong>GitHub:</strong> 10 RPM, 50 RPD</li>
            </ul>
        </div>
    </div>

    <!-- Best Practices -->
    <div class="api-section">
        <h2><i class="fa-solid fa-lightbulb" aria-hidden="true"></i> Best Practices</h2>
        <div class="tips-grid">
            <div class="tip-card">
                <h3><i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i> Implement Retry Logic</h3>
                <p>Although our system handles fallback automatically, implement client-side retry for transient errors.</p>
            </div>
            <div class="tip-card">
                <h3><i class="fa-solid fa-box-open" aria-hidden="true"></i> Use Streaming</h3>
                <p>Enable streaming for better UX, especially for long responses.</p>
            </div>
            <div class="tip-card">
                <h3><i class="fa-solid fa-bullseye" aria-hidden="true"></i> Specify Model When Needed</h3>
                <p>Use <code>model_id</code> parameter when you need specific model capabilities.</p>
            </div>
            <div class="tip-card">
                <h3><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Secure Your Key</h3>
                <p>Never expose your unified API key in client-side code. Use a backend proxy.</p>
            </div>
        </div>
    </div>
</div>

<script>
function copyCode(elementId) {
    const text = document.getElementById(elementId).textContent;
    window.copyToClipboard(text);
}
</script>
