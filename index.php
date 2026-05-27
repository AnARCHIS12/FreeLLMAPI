<?php
/**
 * FreeLLMAPI - PHP SQLite Version
 * Main entry point - Web Frontend
 */

// Start session
session_start();

// Load core files
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/crypto.php';
require_once __DIR__ . '/router.php';
require_once __DIR__ . '/providers/ProviderRegistry.php';

// Initialize database if not exists
if (!file_exists(DB_PATH)) {
    Database::init();
}

// Get unified API key
$unifiedApiKey = Database::getUnifiedApiKey();

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_api_key') {
        $platform = $_POST['platform'] ?? '';
        $apiKey = $_POST['api_key'] ?? '';
        $label = $_POST['label'] ?? '';
        
        if ($platform && $apiKey) {
            try {
                // Validate API key with provider
                $provider = ProviderRegistry::getProvider($platform);
                if (!$provider) {
                    throw new Exception("Provider not found: $platform");
                }
                
                // Encrypt the API key
                $encrypted = Crypto::encrypt($apiKey);
                
                // Save to database
                $db = Database::getInstance();
                $stmt = $db->prepare("INSERT INTO api_keys (platform, label, encrypted_key, iv, auth_tag, status) VALUES (:platform, :label, :encrypted_key, :iv, :auth_tag, 'valid')");
                $stmt->bindValue(':platform', $platform, SQLITE3_TEXT);
                $stmt->bindValue(':label', $label, SQLITE3_TEXT);
                $stmt->bindValue(':encrypted_key', $encrypted['encrypted'], SQLITE3_TEXT);
                $stmt->bindValue(':iv', $encrypted['iv'], SQLITE3_TEXT);
                $stmt->bindValue(':auth_tag', $encrypted['authTag'], SQLITE3_TEXT);
                $stmt->execute();
                
                $message = "API key added successfully for $platform!";
                $messageType = 'success';
            } catch (Exception $e) {
                $message = "Error adding API key: " . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($action === 'regenerate_unified_key') {
        $newKey = Database::regenerateUnifiedApiKey();
        $message = "New unified API key generated!";
        $messageType = 'success';
        $unifiedApiKey = $newKey;
    } elseif ($action === 'toggle_model') {
        $modelId = $_POST['model_id'] ?? 0;
        $enabled = $_POST['enabled'] ?? 1;
        
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE models SET enabled = :enabled WHERE id = :id");
        $stmt->bindValue(':enabled', $enabled, SQLITE3_INTEGER);
        $stmt->bindValue(':id', $modelId, SQLITE3_INTEGER);
        $stmt->execute();
        
        $message = "Model updated successfully!";
        $messageType = 'success';
    } elseif ($action === 'delete_api_key') {
        $keyId = $_POST['key_id'] ?? 0;
        
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM api_keys WHERE id = :id");
        $stmt->bindValue(':id', $keyId, SQLITE3_INTEGER);
        $stmt->execute();
        
        $message = "API key deleted successfully!";
        $messageType = 'success';
    }
}

// Fetch data for views
$db = Database::getInstance();

// Get all providers
$providers = ProviderRegistry::getAllProviders();

// Get all API keys
$apiKeysResult = $db->query("SELECT * FROM api_keys ORDER BY created_at DESC");
$apiKeys = [];
while ($row = $apiKeysResult->fetchArray(SQLITE3_ASSOC)) {
    $apiKeys[] = $row;
}

// Get all models
$modelsResult = $db->query("SELECT * FROM models ORDER BY intelligence_rank ASC");
$models = [];
while ($row = $modelsResult->fetchArray(SQLITE3_ASSOC)) {
    $models[] = $row;
}

// Get recent requests
$requestsResult = $db->query("SELECT * FROM requests ORDER BY created_at DESC LIMIT 50");
$recentRequests = [];
while ($row = $requestsResult->fetchArray(SQLITE3_ASSOC)) {
    $recentRequests[] = $row;
}

// Determine current page
$page = $_GET['page'] ?? 'presentation';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreeLLMAPI - Free LLM API Gateway</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="cyber-grid"></div>
    
    <!-- Navigation -->
    <nav class="cyber-nav">
        <div class="nav-brand">
            <span class="logo-glitch">FreeLLM<span class="accent">API</span></span>
        </div>
        <ul class="nav-links">
            <li><a href="?page=presentation" class="<?php echo $page === 'presentation' ? 'active' : ''; ?>">Projet</a></li>
            <li><a href="?page=home" class="<?php echo $page === 'home' ? 'active' : ''; ?>">Console</a></li>
            <li><a href="?page=setup" class="<?php echo $page === 'setup' ? 'active' : ''; ?>">Setup</a></li>
            <li><a href="?page=admin" class="<?php echo $page === 'admin' ? 'active' : ''; ?>">Admin</a></li>
            <li><a href="?page=api" class="<?php echo $page === 'api' ? 'active' : ''; ?>">API</a></li>
        </ul>
        <div class="nav-status">
            <span class="status-indicator <?php echo count($apiKeys) > 0 ? 'online' : 'offline'; ?>"></span>
            <span><?php echo count($apiKeys); ?> Keys Active</span>
        </div>
    </nav>

    <!-- Message Banner -->
    <?php if ($message): ?>
    <div class="message-banner <?php echo $messageType; ?>">
        <span class="message-icon">
            <i class="fa-solid <?php echo $messageType === 'success' ? 'fa-check' : 'fa-xmark'; ?>" aria-hidden="true"></i>
        </span>
        <span><?php echo htmlspecialchars($message); ?></span>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="cyber-main">
        <?php
        switch ($page) {
            case 'presentation':
                include __DIR__ . '/pages/presentation.php';
                break;
            case 'home':
                include __DIR__ . '/pages/home.php';
                break;
            case 'setup':
                include __DIR__ . '/pages/setup.php';
                break;
            case 'admin':
                include __DIR__ . '/pages/admin.php';
                break;
            case 'api':
                include __DIR__ . '/pages/api.php';
                break;
            default:
                include __DIR__ . '/pages/presentation.php';
        }
        ?>
    </main>

    <!-- Footer -->
    <footer class="cyber-footer">
        <div class="footer-content">
            <p>FreeLLMAPI v1.0 - Aggregating Free Tier LLM APIs</p>
            <p class="footer-stats">
                <span><?php echo count($models); ?> Models</span> • 
                <span><?php echo count($providers); ?> Providers</span> • 
                <span><?php echo count($recentRequests); ?> Recent Requests</span>
            </p>
        </div>
    </footer>

    <script src="/assets/js/app.js"></script>
</body>
</html>
