<?php
/**
 * Project presentation page.
 */
?>
<div class="presentation-page">
    <section class="landing-hero">
        <span class="landing-badge">
            <span class="landing-badge-dot"></span>
            <?php echo count($models); ?> modeles libres · <?php echo count($providers); ?> providers · auto-heberge
        </span>
        <h1>Une seule cle.<br><span>Plusieurs providers.</span><br>Un proxy local.</h1>
        <p class="landing-subtitle">
            FreeLLMAPI route tes requetes vers les providers disponibles, garde tes cles chiffrees et expose une API simple pour tes apps.
        </p>
        <div class="landing-actions">
            <a class="landing-btn landing-btn-primary" href="?page=home">
                Ouvrir la console
                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
            <a class="landing-btn landing-btn-secondary" href="?page=admin">Ajouter mes cles</a>
        </div>
    </section>

    <section class="landing-token-card">
        <p>Capacite locale configuree</p>
        <strong><?php echo count($models); ?></strong>
        <span>modeles · <?php echo count($apiKeys); ?> cles actives · <?php echo count($recentRequests); ?> requetes recentes</span>
    </section>

    <section class="landing-section">
        <div class="landing-section-header">
            <p>Le catalogue</p>
            <h2>Les providers branches sur ton proxy</h2>
            <span>Une grille simple pour comprendre ce que l'instance peut router.</span>
        </div>
        <div class="landing-provider-grid">
            <?php foreach ($providers as $provider): ?>
            <?php
                $providerModels = array_values(array_filter($models, fn($model) => $model['platform'] === $provider->platform));
                $firstModel = $providerModels[0]['display_name'] ?? 'Aucun modele';
            ?>
            <article class="landing-provider-card">
                <div class="landing-provider-name">
                    <span class="landing-provider-icon"><i class="fa-solid fa-cube" aria-hidden="true"></i></span>
                    <?php echo htmlspecialchars($provider->name); ?>
                </div>
                <div class="landing-provider-count">
                    <strong><?php echo count($providerModels); ?></strong>
                    <span>modeles</span>
                </div>
                <p><?php echo htmlspecialchars($firstModel); ?></p>
            </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="landing-section">
        <div class="landing-section-header">
            <p>Demarrage</p>
            <h2>Trois etapes</h2>
        </div>
        <div class="landing-steps">
            <article>
                <span>1</span>
                <h3>Ajoute tes cles</h3>
                <p>Va dans Admin, colle les cles providers, elles sont chiffrees localement.</p>
            </article>
            <article>
                <span>2</span>
                <h3>Teste la console</h3>
                <p>Envoie une requete, le routeur selectionne un modele disponible.</p>
            </article>
            <article>
                <span>3</span>
                <h3>Branche ton app</h3>
                <p>Utilise l'endpoint unifie <code>/api/chat.php</code> depuis ton code.</p>
            </article>
        </div>
    </section>

    <section class="landing-terminal">
        <div class="landing-terminal-top">
            <span></span><span></span><span></span>
            <small>local api</small>
        </div>
        <pre><code>curl -X POST http://localhost:3001/api/chat.php \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_UNIFIED_KEY" \
  -d '{"messages":[{"role":"user","content":"Hello"}],"stream":false}'</code></pre>
    </section>
</div>
