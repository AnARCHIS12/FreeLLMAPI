<div align="center">
  <img src="assets/img/logo.png" alt="FreeLLMAPI" width="269" />

  <p><strong>Self-hosted LLM API gateway with routing, fallback, encrypted keys and a unified endpoint.</strong></p>

  <p>
    <img alt="PHP" src="https://img.shields.io/badge/PHP-7.4%2B-ff2d3d?style=for-the-badge&labelColor=0a0a0a" />
    <img alt="SQLite" src="https://img.shields.io/badge/SQLite-local-ff2d3d?style=for-the-badge&labelColor=0a0a0a" />
    <img alt="Providers" src="https://img.shields.io/badge/providers-11-ff2d3d?style=for-the-badge&labelColor=0a0a0a" />
    <img alt="License" src="https://img.shields.io/badge/license-MIT-ff2d3d?style=for-the-badge&labelColor=0a0a0a" />
  </p>

  <p>
    Forked from / inspired by work from
    <a href="https://github.com/LaurentVoanh">LaurentVoanh</a>.
    Thanks to LaurentVoanh and everyone building open tooling around free-tier LLM access.
  </p>

  <p>
    <a href="#features">Features</a> ·
    <a href="#installation">Installation</a> ·
    <a href="#api-usage">API Usage</a> ·
    <a href="#supported-providers">Providers</a>
  </p>
</div>

---


A powerful, self-hosted LLM API gateway that aggregates multiple free-tier AI providers with intelligent routing and automatic fallback.

## Features

- **Multi-Provider Support**: Groq, Cerebras, SambaNova, NVIDIA NIM, Mistral, OpenRouter, GitHub Models, Cohere, Cloudflare, Zhipu AI, HuggingFace
- **Intelligent Routing**: Automatic selection of best available provider based on intelligence rank, speed, and rate limits
- **Auto-Fallback**: Seamless failover when providers are rate-limited or unavailable
- **Rate Limiting**: Per-provider/per-model rate limit tracking (RPM, RPD, TPM, TPD)
- **Encryption**: AES-256-GCM encryption for all stored API keys
- **Streaming Support**: Real-time SSE streaming responses
- **Unified API**: Single endpoint for all providers
- **Red/Black UI**: modern responsive interface with presentation, console, admin, setup and API docs pages

## Project Structure

```
PHP V0/
├── index.php              # Main entry point & router
├── config.php             # Configuration settings
├── database.php           # SQLite database management
├── crypto.php             # Encryption utilities
├── router.php             # Request routing logic
├── ratelimit.php          # Rate limiting service
├── api/
│   ├── chat.php           # Chat completion endpoint
│   ├── models.php         # List available models
│   └── stats.php          # Usage statistics
├── pages/
│   ├── home.php           # Chat interface
│   ├── setup.php          # API key setup guide
│   ├── admin.php          # Admin dashboard
│   └── api.php            # API documentation
├── providers/
│   ├── BaseProvider.php   # Abstract provider base class
│   ├── OpenAICompatProvider.php  # OpenAI-compatible providers
│   └── ProviderRegistry.php      # Provider registration
├── assets/
│   ├── css/style.css      # Red/black responsive styles
│   ├── img/logo.png       # Project logo
│   └── js/app.js          # Frontend JavaScript
└── data/
    └── freeapi.db         # SQLite database (auto-created)
```

## Installation

### Requirements
- PHP 7.4+ with SQLite3 extension
- OpenSSL extension for encryption
- cURL extension for API requests

### Quick Start

For beginners, use the launcher for your system:

| System | Start file |
|--------|------------|
| Windows | Double-click `start.bat` |
| macOS | Double-click `start.command` |
| Linux | Run `./start.sh` |

The launcher tries to install PHP automatically when possible, creates `data/`, starts the local server, and opens:

```text
http://localhost:3001
```

Manual start:

```bash
php -S localhost:3001 -t .
```

Then add your provider keys from the **Admin** page.

### PHP Auto-Install Notes

- Windows uses `winget`.
- macOS uses Homebrew.
- Linux uses `apt`, `dnf`, `pacman`, or `apk` when available.

### Easy Production Deploy

Use this when you have a domain and a VPS/server.

1. Point your domain to the server IP:

```text
A record -> your server IP
```

2. Run on Linux/macOS:

```bash
./start-prod.sh
```

Or double-click on Windows:

```text
start-prod.bat
```

3. Edit `.env.prod`:

```text
DOMAIN=your-domain.com
```

4. Run again on Linux/macOS:

```bash
./start-prod.sh
```

Or double-click again on Windows:

```text
start-prod.bat
```

Done. The app runs at:

```text
https://your-domain.com
```

HTTPS is handled automatically by Caddy.

Windows production requires Docker Desktop.

### GitHub Pages Landing

A static landing page is available in `docs/`.

To publish it on GitHub Pages:

1. Open your repository on GitHub
2. Go to **Settings** -> **Pages**
3. Select **Deploy from a branch**
4. Choose your branch and `/docs`
5. Save

GitHub will publish the landing page. The PHP app itself still needs local/prod hosting.

## Configuration

Edit `config.php` to customize:
- Database path
- Server port/host
- Rate limit windows
- Retry settings
- Penalty system parameters

## API Usage

### Unified Chat Endpoint

```bash
curl -X POST http://localhost:3001/api/chat.php \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_UNIFIED_KEY" \
  -d '{
    "messages": [{"role": "user", "content": "Hello!"}],
    "stream": true
  }'
```

### Get Available Models

```bash
curl http://localhost:3001/api/models.php
```

### Get Statistics

```bash
curl http://localhost:3001/api/stats.php
```

## Supported Providers

| Provider | RPM | TPM | Notes |
|----------|-----|-----|-------|
| Groq | 30 | 6,000 | Ultra-fast Llama inference |
| Cerebras | 30 | 60,000 | Frontier models |
| SambaNova | 20 | 200K/day | High-performance |
| OpenRouter | 20 | Varies | Multiple free models |
| GitHub Models | 10 | 50/day | GPT-5 access |
| Mistral | 2 | 500,000 | European provider |
| Cohere | 20 | 33/day | Enterprise models |
| Cloudflare | - | ~18-45M/month | Edge computing |
| HuggingFace | - | ~1-3M | Open-source models |
| Zhipu AI | - | 1M/day | GLM series |
| NVIDIA NIM | Credits | Credits | Accelerated inference |

## Security

- All API keys encrypted with AES-256-GCM before storage
- Unified API key authentication for endpoints
- CORS headers configured for web access
- No keys exposed in frontend code

## Advanced Features

### Dynamic Priority System
- Penalties applied on rate limit hits
- Time-based penalty decay
- Sticky sessions for consistent routing

### Rate Limiting
- Persistent tracking in SQLite
- Memory-based fallback
- Cooldown periods for rate-limited keys

### Fallback Chain
- Models ranked by intelligence and speed
- Automatic retry on failure
- Skip list to avoid repeated failures

## Troubleshooting

### Database not created
Ensure the `data/` directory is writable by the web server.

### Encryption key error
Set `ENCRYPTION_KEY` environment variable (64 hex characters).

### No providers available
Add at least one valid API key in the Admin panel.

### Streaming not working
Check that output buffering is disabled and `flush()` is supported.

## License

This project is released under the [MIT License](LICENSE).

## Credits

Forked from / inspired by work from [LaurentVoanh](https://github.com/LaurentVoanh).

Thanks to [LaurentVoanh](https://github.com/LaurentVoanh) and everyone building open tooling around free-tier LLM access.
