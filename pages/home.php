<?php
/**
 * Home/Chat Page - Main chat interface
 */
?>
<div class="console-container">
    <section class="console-shell">
        <header class="console-header">
            <div>
                <p class="console-kicker">Gateway multi-provider</p>
                <h1>FreeLLMAPI Console</h1>
            </div>
            <div class="console-health">
                <span><i class="fa-solid fa-key" aria-hidden="true"></i> <?php echo count($apiKeys); ?> keys</span>
                <span><i class="fa-solid fa-layer-group" aria-hidden="true"></i> <?php echo count($models); ?> models</span>
            </div>
        </header>

        <div class="console-grid">
            <aside class="control-panel" aria-label="Routing controls">
                <div class="panel-section">
                    <label for="selectedModel">Routing model</label>
                    <select id="selectedModel" name="selectedModel">
                        <option value="auto">Auto routing</option>
                        <?php foreach ($models as $model): ?>
                        <option value="<?php echo htmlspecialchars($model['id']); ?>">
                            <?php echo htmlspecialchars($model['display_name']); ?> 
                            (<?php echo htmlspecialchars($model['platform']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="panel-section metric-list">
                    <div class="metric-row">
                        <span>Requests today</span>
                        <strong id="requestsToday">0</strong>
                    </div>
                    <div class="metric-row">
                        <span>Tokens used</span>
                        <strong id="tokensUsed">0</strong>
                    </div>
                    <div class="metric-row">
                        <span>Avg latency</span>
                        <strong id="avgLatency">0ms</strong>
                    </div>
                </div>

                <div class="panel-section route-note">
                    <i class="fa-solid fa-shuffle" aria-hidden="true"></i>
                    <p>Les requêtes passent par la meilleure clé disponible, avec bascule automatique si un provider bloque.</p>
                </div>
            </aside>

            <section class="chat-workspace" aria-label="Chat workspace">
                <div class="chat-messages" id="chatMessages">
                    <div class="empty-thread">
                        <i class="fa-solid fa-terminal" aria-hidden="true"></i>
                        <h2>Nouvelle requête</h2>
                        <p>Écris ton message. La console route ensuite vers un modèle disponible.</p>
                    </div>
                </div>

                <div class="chat-input-container">
                    <form class="chat-input-form" id="chatForm">
                        <textarea 
                            id="messageInput" 
                            name="message" 
                            placeholder="Message..."
                            rows="3"
                            required
                        ></textarea>
                        <button type="submit" class="send-button" id="sendButton">
                            <span class="button-text">Send</span>
                            <span class="button-loader" style="display: none;"><i class="fa-solid fa-spinner" aria-hidden="true"></i></span>
                        </button>
                    </form>
                    <div class="input-hints">
                        <span>Enter to send, Shift+Enter for new line</span>
                        <span id="tokenCount">0 tokens</span>
                    </div>
                </div>
            </section>
        </div>
    </section>
</div>

<script>
// Chat functionality
(function() {
    const chatForm = document.getElementById('chatForm');
    const messageInput = document.getElementById('messageInput');
    const chatMessages = document.getElementById('chatMessages');
    const sendButton = document.getElementById('sendButton');
    const selectedModel = document.getElementById('selectedModel');
    const tokenCount = document.getElementById('tokenCount');
    
    let conversationHistory = [];
    
    // Auto-resize textarea
    messageInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 150) + 'px';
        
        // Update token count (rough estimate)
        const words = this.value.trim().split(/\s+/).filter(w => w.length > 0);
        const estimatedTokens = Math.ceil(words.length * 1.3);
        tokenCount.textContent = estimatedTokens + ' tokens';
    });
    
    // Handle form submission
    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const message = messageInput.value.trim();
        if (!message) return;
        
        // Add user message to chat
        addMessage(message, 'user');
        conversationHistory.push({ role: 'user', content: message });
        
        // Clear input
        messageInput.value = '';
        messageInput.style.height = 'auto';
        tokenCount.textContent = '0 tokens';
        
        // Show loading state
        setLoading(true);
        
        // Add placeholder for assistant response
        const assistantMessageDiv = addMessage('', 'assistant', true);
        
        try {
            const response = await fetch('/api/chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer <?php echo htmlspecialchars($unifiedApiKey, ENT_QUOTES); ?>',
                },
                body: JSON.stringify({
                    messages: conversationHistory,
                    model_id: selectedModel.value === 'auto' ? null : parseInt(selectedModel.value),
                    stream: true
                })
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || 'Request failed');
            }
            
            // Handle streaming response
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let fullResponse = '';
            
            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                
                const chunk = decoder.decode(value);
                const lines = chunk.split('\n');
                
                for (const line of lines) {
                    if (line.startsWith('data: ')) {
                        const data = line.slice(6);
                        if (data === '[DONE]') continue;
                        
                        try {
                            const parsed = JSON.parse(data);
                            const delta = parsed.choices?.[0]?.delta?.content || '';
                            if (delta) {
                                fullResponse += delta;
                                assistantMessageDiv.textContent = fullResponse;
                                chatMessages.scrollTop = chatMessages.scrollHeight;
                            }
                        } catch (e) {
                            // Ignore parse errors
                        }
                    }
                }
            }
            
            // Add to conversation history
            conversationHistory.push({ role: 'assistant', content: fullResponse });
            
        } catch (error) {
            assistantMessageDiv.textContent = 'Error: ' + error.message;
            assistantMessageDiv.classList.add('error-message');
        } finally {
            setLoading(false);
        }
    });
    
    function addMessage(content, role, isLoading = false) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message message-${role} ${isLoading ? 'loading' : ''}`;
        
        const avatarDiv = document.createElement('div');
        avatarDiv.className = 'message-avatar';
        avatarDiv.innerHTML = role === 'user'
            ? '<i class="fa-solid fa-user" aria-hidden="true"></i>'
            : '<i class="fa-solid fa-terminal" aria-hidden="true"></i>';
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        contentDiv.textContent = content;
        
        if (isLoading) {
            contentDiv.innerHTML = '<span class="typing-indicator"><span></span><span></span><span></span></span>';
        }
        
        messageDiv.appendChild(avatarDiv);
        messageDiv.appendChild(contentDiv);
        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        return contentDiv;
    }
    
    function setLoading(loading) {
        sendButton.disabled = loading;
        sendButton.querySelector('.button-text').style.display = loading ? 'none' : 'inline';
        sendButton.querySelector('.button-loader').style.display = loading ? 'inline' : 'none';
        messageInput.disabled = loading;
    }
    
    // Load stats
    async function loadStats() {
        try {
            const response = await fetch('/api/stats.php');
            const stats = await response.json();
            
            document.getElementById('requestsToday').textContent = stats.requests_today || 0;
            document.getElementById('tokensUsed').textContent = stats.tokens_used || 0;
            document.getElementById('avgLatency').textContent = (stats.avg_latency || 0) + 'ms';
        } catch (e) {
            console.error('Failed to load stats:', e);
        }
    }
    
    // Load stats on page load
    loadStats();
})();
</script>
