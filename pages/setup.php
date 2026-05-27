<?php
/**
 * Setup Guide Page - Instructions for getting API keys from free tier providers
 */
?>
<div class="setup-container">
    <div class="setup-header">
        <h1 class="cyber-title">
            <span class="title-glow">Get Your Free API Keys</span>
        </h1>
        <p class="setup-subtitle">Follow these steps to obtain free API keys from various LLM providers</p>
    </div>

    <div class="setup-intro">
        <div class="intro-card">
            <h2><i class="fa-solid fa-bullseye" aria-hidden="true"></i> Why Multiple Providers?</h2>
            <p>Each provider offers different free tier limits. By registering with multiple services, you maximize your available tokens and ensure high availability through automatic fallback.</p>
        </div>
        <div class="intro-card">
            <h2><i class="fa-solid fa-list-check" aria-hidden="true"></i> Quick Start</h2>
            <p>1. Choose providers from the list below<br>
               2. Click the link to visit their website<br>
               3. Sign up for a free account<br>
               4. Generate an API key<br>
               5. Add it in the <a href="?page=admin" class="accent-link">Admin Panel</a></p>
        </div>
    </div>

    <!-- Provider Cards -->
    <div class="provider-grid">
        <!-- Groq -->
        <div class="provider-card" data-provider="groq">
            <div class="provider-logo"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i></div>
            <h3>Groq</h3>
            <div class="provider-limits">
                <span class="limit-badge">30 RPM</span>
                <span class="limit-badge">6K TPM</span>
            </div>
            <p class="provider-desc">Ultra-fast inference with Llama models. Best for real-time applications.</p>
            <ul class="provider-steps">
                <li>Visit <a href="https://console.groq.com" target="_blank" rel="noopener">console.groq.com</a></li>
                <li>Sign up with GitHub or email</li>
                <li>Go to API Keys section</li>
                <li>Create a new key</li>
                <li>Copy and add to Admin panel</li>
            </ul>
            <a href="https://console.groq.com/keys" target="_blank" rel="noopener" class="provider-btn">Get API Key <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>

        <!-- Cerebras -->
        <div class="provider-card" data-provider="cerebras">
            <div class="provider-logo"><i class="fa-solid fa-brain" aria-hidden="true"></i></div>
            <h3>Cerebras</h3>
            <div class="provider-limits">
                <span class="limit-badge">30 RPM</span>
                <span class="limit-badge">60K TPM</span>
            </div>
            <p class="provider-desc">Access to frontier models like Qwen3-Coder 480B with blazing speed.</p>
            <ul class="provider-steps">
                <li>Visit <a href="https://cloud.cerebras.ai" target="_blank" rel="noopener">cloud.cerebras.ai</a></li>
                <li>Create a free account</li>
                <li>Navigate to API section</li>
                <li>Generate your API key</li>
                <li>Add to Admin panel</li>
            </ul>
            <a href="https://cloud.cerebras.ai" target="_blank" rel="noopener" class="provider-btn">Get API Key <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>

        <!-- SambaNova -->
        <div class="provider-card" data-provider="sambanova">
            <div class="provider-logo"><i class="fa-solid fa-wave-square" aria-hidden="true"></i></div>
            <h3>SambaNova</h3>
            <div class="provider-limits">
                <span class="limit-badge">20 RPM</span>
                <span class="limit-badge">200K TPD</span>
            </div>
            <p class="provider-desc">High-performance Llama models with generous daily limits.</p>
            <ul class="provider-steps">
                <li>Go to <a href="https://cloud.sambanova.ai" target="_blank" rel="noopener">cloud.sambanova.ai</a></li>
                <li>Register for free</li>
                <li>Access API dashboard</li>
                <li>Create API credentials</li>
                <li>Submit to Admin panel</li>
            </ul>
            <a href="https://cloud.sambanova.ai" target="_blank" rel="noopener" class="provider-btn">Get API Key <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>

        <!-- OpenRouter -->
        <div class="provider-card" data-provider="openrouter">
            <div class="provider-logo"><i class="fa-solid fa-route" aria-hidden="true"></i></div>
            <h3>OpenRouter</h3>
            <div class="provider-limits">
                <span class="limit-badge">20 RPM</span>
                <span class="limit-badge">Free Models</span>
            </div>
            <p class="provider-desc">Gateway to multiple free models including DeepSeek, Kimi, Qwen.</p>
            <ul class="provider-steps">
                <li>Visit <a href="https://openrouter.ai" target="_blank" rel="noopener">openrouter.ai</a></li>
                <li>Sign in with your account</li>
                <li>Go to Keys section</li>
                <li>Create a new API key</li>
                <li>Add to Admin panel</li>
            </ul>
            <a href="https://openrouter.ai/keys" target="_blank" rel="noopener" class="provider-btn">Get API Key <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>

        <!-- GitHub Models -->
        <div class="provider-card" data-provider="github">
            <div class="provider-logo"><i class="fa-brands fa-github" aria-hidden="true"></i></div>
            <h3>GitHub Models</h3>
            <div class="provider-limits">
                <span class="limit-badge">10 RPM</span>
                <span class="limit-badge">GPT-5 Access</span>
            </div>
            <p class="provider-desc">Access to cutting-edge models including GPT-5 through GitHub.</p>
            <ul class="provider-steps">
                <li>Visit <a href="https://github.com/marketplace/models" target="_blank" rel="noopener">GitHub Models</a></li>
                <li>Sign in with GitHub account</li>
                <li>Enable model access</li>
                <li>Generate personal access token</li>
                <li>Configure in Admin panel</li>
            </ul>
            <a href="https://github.com/marketplace/models" target="_blank" rel="noopener" class="provider-btn">Get API Key <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>

        <!-- Mistral -->
        <div class="provider-card" data-provider="mistral">
            <div class="provider-logo"><i class="fa-solid fa-wind" aria-hidden="true"></i></div>
            <h3>Mistral AI</h3>
            <div class="provider-limits">
                <span class="limit-badge">2 RPM</span>
                <span class="limit-badge">500K TPM</span>
            </div>
            <p class="provider-desc">European leader with powerful Mistral Large and Codestral models.</p>
            <ul class="provider-steps">
                <li>Go to <a href="https://console.mistral.ai" target="_blank" rel="noopener">console.mistral.ai</a></li>
                <li>Create free account</li>
                <li>Navigate to API Keys</li>
                <li>Generate new key</li>
                <li>Add to Admin panel</li>
            </ul>
            <a href="https://console.mistral.ai/api-keys" target="_blank" rel="noopener" class="provider-btn">Get API Key <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>

        <!-- Cohere -->
        <div class="provider-card" data-provider="cohere">
            <div class="provider-logo"><i class="fa-solid fa-comments" aria-hidden="true"></i></div>
            <h3>Cohere</h3>
            <div class="provider-limits">
                <span class="limit-badge">20 RPM</span>
                <span class="limit-badge">33 RPD</span>
            </div>
            <p class="provider-desc">Specialized in enterprise-grade language models with strong reasoning.</p>
            <ul class="provider-steps">
                <li>Visit <a href="https://dashboard.cohere.com" target="_blank" rel="noopener">dashboard.cohere.com</a></li>
                <li>Sign up for trial</li>
                <li>Access API section</li>
                <li>Copy your API key</li>
                <li>Submit to Admin</li>
            </ul>
            <a href="https://dashboard.cohere.com/api-keys" target="_blank" rel="noopener" class="provider-btn">Get API Key <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>

        <!-- Cloudflare -->
        <div class="provider-card" data-provider="cloudflare">
            <div class="provider-logo"><i class="fa-solid fa-cloud" aria-hidden="true"></i></div>
            <h3>Cloudflare Workers AI</h3>
            <div class="provider-limits">
                <span class="limit-badge">~18-45M tokens</span>
                <span class="limit-badge">Monthly</span>
            </div>
            <p class="provider-desc">Run models at the edge with Cloudflare's global network.</p>
            <ul class="provider-steps">
                <li>Go to <a href="https://dash.cloudflare.com" target="_blank" rel="noopener">Cloudflare Dashboard</a></li>
                <li>Create account (free)</li>
                <li>Enable Workers AI</li>
                <li>Generate API token with Account ID</li>
                <li>Format: account_id:token in Admin</li>
            </ul>
            <a href="https://developers.cloudflare.com/workers-ai" target="_blank" rel="noopener" class="provider-btn">Get API Key <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>

        <!-- HuggingFace -->
        <div class="provider-card" data-provider="huggingface">
            <div class="provider-logo"><i class="fa-solid fa-face-smile" aria-hidden="true"></i></div>
            <h3>HuggingFace Router</h3>
            <div class="provider-limits">
                <span class="limit-badge">~1-3M tokens</span>
                <span class="limit-badge">Varies</span>
            </div>
            <p class="provider-desc">Access thousands of open-source models through HF Inference.</p>
            <ul class="provider-steps">
                <li>Visit <a href="https://huggingface.co" target="_blank" rel="noopener">huggingface.co</a></li>
                <li>Create free account</li>
                <li>Go to Settings > Tokens</li>
                <li>Create read token</li>
                <li>Add to Admin panel</li>
            </ul>
            <a href="https://huggingface.co/settings/tokens" target="_blank" rel="noopener" class="provider-btn">Get API Key <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>

        <!-- Zhipu -->
        <div class="provider-card" data-provider="zhipu">
            <div class="provider-logo"><i class="fa-solid fa-globe" aria-hidden="true"></i></div>
            <h3>Zhipu AI (Z.ai)</h3>
            <div class="provider-limits">
                <span class="limit-badge">1M TPD</span>
                <span class="limit-badge">GLM Models</span>
            </div>
            <p class="provider-desc">Chinese provider with powerful GLM series models.</p>
            <ul class="provider-steps">
                <li>Visit <a href="https://open.bigmodel.cn" target="_blank" rel="noopener">open.bigmodel.cn</a></li>
                <li>Register account</li>
                <li>Navigate to API management</li>
                <li>Create API key</li>
                <li>Add to Admin panel</li>
            </ul>
            <a href="https://open.bigmodel.cn/usercenter/apikeys" target="_blank" rel="noopener" class="provider-btn">Get API Key <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>

        <!-- NVIDIA NIM -->
        <div class="provider-card" data-provider="nvidia">
            <div class="provider-logo"><i class="fa-solid fa-microchip" aria-hidden="true"></i></div>
            <h3>NVIDIA NIM</h3>
            <div class="provider-limits">
                <span class="limit-badge">Credits-based</span>
                <span class="limit-badge">Enterprise</span>
            </div>
            <p class="provider-desc">NVIDIA's optimized inference service with accelerated models.</p>
            <ul class="provider-steps">
                <li>Go to <a href="https://build.nvidia.com" target="_blank" rel="noopener">build.nvidia.com</a></li>
                <li>Sign up for free credits</li>
                <li>Select a model</li>
                <li>Get API key</li>
                <li>Configure in Admin</li>
            </ul>
            <a href="https://build.nvidia.com/explore/discover" target="_blank" rel="noopener" class="provider-btn">Get API Key <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>
    </div>

    <!-- Tips Section -->
    <div class="tips-section">
        <h2><i class="fa-solid fa-lightbulb" aria-hidden="true"></i> Pro Tips</h2>
        <div class="tips-grid">
            <div class="tip-card">
                <h3><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Track Your Usage</h3>
                <p>Monitor your token consumption in the Admin panel to avoid hitting limits unexpectedly.</p>
            </div>
            <div class="tip-card">
                <h3><i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i> Rotate Keys</h3>
                <p>Add multiple keys per provider to distribute load and increase effective rate limits.</p>
            </div>
            <div class="tip-card">
                <h3><i class="fa-solid fa-bullseye" aria-hidden="true"></i> Prioritize Speed</h3>
                <p>The auto-router prioritizes faster providers. Add Groq and Cerebras for best latency.</p>
            </div>
            <div class="tip-card">
                <h3><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Security First</h3>
                <p>All API keys are encrypted with AES-256-GCM before storage. Never share your unified key.</p>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="faq-section">
        <h2><i class="fa-solid fa-circle-question" aria-hidden="true"></i> Frequently Asked Questions</h2>
        <div class="faq-list">
            <details class="faq-item">
                <summary>Are these APIs really free?</summary>
                <p>Yes! All listed providers offer free tiers with varying limits. Some require credit card verification but won't charge unless you exceed free limits.</p>
            </details>
            <details class="faq-item">
                <summary>How does the fallback system work?</summary>
                <p>When you send a request, the router tries the highest-ranked available model. If it's rate-limited or fails, it automatically tries the next provider in the chain.</p>
            </details>
            <details class="faq-item">
                <summary>Can I use my own API key directly?</summary>
                <p>Yes! You can also call providers directly using their native APIs. Our unified endpoint simplifies this by handling routing and failover automatically.</p>
            </details>
            <details class="faq-item">
                <summary>What happens when I hit a rate limit?</summary>
                <p>The system automatically switches to another available provider. You'll experience minimal disruption, and the rate-limited provider is temporarily deprioritized.</p>
            </details>
        </div>
    </div>
</div>
