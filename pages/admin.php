<?php
/**
 * Admin Panel - Manage API keys, models, and view statistics
 */
?>
<div class="admin-container">
    <div class="admin-header">
        <h1 class="cyber-title">
            <span class="title-glow">Admin Dashboard</span>
        </h1>
        <p class="admin-subtitle">Manage your API keys, models, and monitor system performance</p>
    </div>

    <!-- Quick Stats -->
    <div class="admin-stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-key" aria-hidden="true"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?php echo count($apiKeys); ?></span>
                <span class="stat-label">API Keys</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-layer-group" aria-hidden="true"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?php echo count(array_filter($models, fn($m) => $m['enabled'])); ?>/<?php echo count($models); ?></span>
                <span class="stat-label">Active Models</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-chart-line" aria-hidden="true"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?php echo count($recentRequests); ?></span>
                <span class="stat-label">Recent Requests</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-plug-circle-check" aria-hidden="true"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?php echo count($providers); ?></span>
                <span class="stat-label">Providers</span>
            </div>
        </div>
    </div>

    <!-- Unified API Key Section -->
    <div class="admin-section">
        <h2><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Unified API Key</h2>
        <div class="unified-key-card">
            <p>Use this key to access the unified chat endpoint. All requests will be automatically routed.</p>
            <div class="key-display">
                <code id="unifiedKey"><?php echo htmlspecialchars($unifiedApiKey); ?></code>
                <button class="copy-btn" onclick="copyToClipboard('<?php echo $unifiedApiKey; ?>')">Copy</button>
            </div>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="regenerate_unified_key">
                <button type="submit" class="btn btn-warning" data-confirm="Regenerate key? Old key will stop working.">Regenerate</button>
            </form>
        </div>
    </div>

    <!-- Add API Key Section -->
    <div class="admin-section">
        <h2><i class="fa-solid fa-plus" aria-hidden="true"></i> Add New API Key</h2>
        <form method="POST" class="add-key-form">
            <input type="hidden" name="action" value="add_api_key">
            <div class="form-row">
                <div class="form-group">
                    <label for="platform">Provider</label>
                    <select name="platform" id="platform" required>
                        <option value="">Select Provider...</option>
                        <?php foreach ($providers as $provider): ?>
                        <option value="<?php echo htmlspecialchars($provider->platform); ?>">
                            <?php echo htmlspecialchars($provider->name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="label">Label (Optional)</label>
                    <input type="text" name="label" id="label" placeholder="My Groq Key">
                </div>
            </div>
            <div class="form-group">
                <label for="api_key">API Key</label>
                <input type="password" name="api_key" id="api_key" required placeholder="sk-...">
            </div>
            <button type="submit" class="btn btn-primary">Add API Key</button>
        </form>
    </div>

    <!-- Existing API Keys -->
    <div class="admin-section">
        <h2><i class="fa-solid fa-list-check" aria-hidden="true"></i> Existing API Keys</h2>
        <?php if (empty($apiKeys)): ?>
        <div class="empty-state">
            <p>No API keys configured yet. <a href="?page=setup">Get your free API keys here</a>.</p>
        </div>
        <?php else: ?>
        <div class="keys-table">
            <table>
                <thead>
                    <tr>
                        <th>Provider</th>
                        <th>Label</th>
                        <th>Key (Masked)</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($apiKeys as $key): 
                        // Try to decrypt and mask the key
                        try {
                            $decrypted = Crypto::decrypt($key['encrypted_key'], $key['iv'], $key['auth_tag']);
                            $maskedKey = Crypto::maskKey($decrypted);
                        } catch (Exception $e) {
                            $maskedKey = '••••••••';
                        }
                    ?>
                    <tr>
                        <td>
                            <span class="provider-badge"><?php echo htmlspecialchars($key['platform']); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($key['label'] ?: '-'); ?></td>
                        <td><code><?php echo $maskedKey; ?></code></td>
                        <td>
                            <span class="status-badge status-<?php echo $key['status']; ?>">
                                <?php echo htmlspecialchars($key['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('Y-m-d H:i', strtotime($key['created_at'])); ?></td>
                        <td>
                            <form method="POST" style="display: inline;" data-confirm="Delete this key?">
                                <input type="hidden" name="action" value="delete_api_key">
                                <input type="hidden" name="key_id" value="<?php echo $key['id']; ?>">
                                <button type="submit" class="btn btn-small btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Models Management -->
    <div class="admin-section">
        <h2><i class="fa-solid fa-layer-group" aria-hidden="true"></i> Model Management</h2>
        <div class="models-grid">
            <?php foreach ($models as $model): ?>
            <div class="model-card <?php echo $model['enabled'] ? 'enabled' : 'disabled'; ?>">
                <div class="model-header">
                    <h3><?php echo htmlspecialchars($model['display_name']); ?></h3>
                    <span class="model-platform"><?php echo htmlspecialchars($model['platform']); ?></span>
                </div>
                <div class="model-details">
                    <div class="detail-row">
                        <span class="detail-label">Size:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($model['size_label']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Context:</span>
                        <span class="detail-value"><?php echo number_format($model['context_window']); ?> tokens</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Limits:</span>
                        <span class="detail-value">
                            <?php if ($model['rpm_limit']): ?>RPM: <?php echo $model['rpm_limit']; ?> | <?php endif; ?>
                            <?php if ($model['tpm_limit']): ?>TPM: <?php echo number_format($model['tpm_limit']); ?><?php endif; ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Rank:</span>
                        <span class="detail-value">Intelligence: #<?php echo $model['intelligence_rank']; ?> | Speed: #<?php echo $model['speed_rank']; ?></span>
                    </div>
                </div>
                <form method="POST" class="model-toggle-form">
                    <input type="hidden" name="action" value="toggle_model">
                    <input type="hidden" name="model_id" value="<?php echo $model['id']; ?>">
                    <input type="hidden" name="enabled" value="<?php echo $model['enabled'] ? 0 : 1; ?>">
                    <button type="submit" class="btn btn-small <?php echo $model['enabled'] ? 'btn-warning' : 'btn-success'; ?>">
                        <?php echo $model['enabled'] ? 'Disable' : 'Enable'; ?>
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="admin-section">
        <h2><i class="fa-solid fa-chart-simple" aria-hidden="true"></i> Recent Activity</h2>
        <?php if (empty($recentRequests)): ?>
        <div class="empty-state">
            <p>No recent requests. Start chatting to see activity!</p>
        </div>
        <?php else: ?>
        <div class="activity-list">
            <?php foreach (array_slice($recentRequests, 0, 20) as $request): ?>
            <div class="activity-item">
                <div class="activity-icon <?php echo $request['status'] === 'success' ? 'success' : 'error'; ?>">
                    <i class="fa-solid <?php echo $request['status'] === 'success' ? 'fa-check' : 'fa-xmark'; ?>" aria-hidden="true"></i>
                </div>
                <div class="activity-info">
                    <div class="activity-title">
                        <strong><?php echo htmlspecialchars($request['model_id']); ?></strong>
                        <span class="activity-provider">(<?php echo htmlspecialchars($request['platform']); ?>)</span>
                    </div>
                    <div class="activity-meta">
                        <span><i class="fa-solid fa-file-lines" aria-hidden="true"></i> <?php echo ($request['input_tokens'] + $request['output_tokens']); ?> tokens</span>
                        <span><i class="fa-solid fa-stopwatch" aria-hidden="true"></i> <?php echo $request['latency_ms']; ?>ms</span>
                        <span><i class="fa-solid fa-clock" aria-hidden="true"></i> <?php echo date('H:i:s', strtotime($request['created_at'])); ?></span>
                    </div>
                </div>
                <?php if ($request['error']): ?>
                <div class="activity-error"><?php echo htmlspecialchars(substr($request['error'], 0, 50)); ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
