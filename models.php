<?php
/**
 * models.php - Liste des modèles gratuits supportés
 * Compatible avec tous les providers Free Tier
 */

class ModelRegistry {
    
    /**
     * Retourne la liste complète des modèles gratuits
     * Format: [provider_id, model_id, display_name, intelligence_rank, speed_rank, size_label, rpm_limit, rpd_limit, tpm_limit, tpd_limit, monthly_budget, context_window]
     */
    public static function getModels() {
        return [
            // === CODE & DÉVELOPPEMENT ===
            ['mistral', 'codestral-2508', 'Code Master Ultimate (Codestral)', 12, 18, 'Medium', null, null, null, null, '~Free', 256000],
            ['mistral', 'devstral-2512', 'Dev Agent Pro (Full)', 14, 14, 'Large', null, null, null, null, '~Free', 256000],
            ['mistral', 'devstral-medium-2507', 'Dev Agent Medium', 13, 15, 'Large', null, null, null, null, '~Free', 256000],
            ['mistral', 'devstral-small-2507', 'Dev Agent Light', 10, 17, 'Small', null, null, null, null, '~Free', 256000],
            
            // === RAISONNEMENT & HAUTE PERFORMANCE (FLAGSHIPS) ===
            ['mistral', 'mistral-large-2512', 'Mistral Brain Ultra (Next-Gen)', 15, 12, 'XL', null, null, null, null, '~Free', 256000],
            ['mistral', 'mistral-large-2411', 'Mistral Brain Ultra (Legacy)', 14, 13, 'XL', null, null, null, null, '~Free', 256000],
            
            // === MODÈLES INTERMÉDIAIRES & ÉQUILIBRÉS ===
            ['mistral', 'mistral-medium-2508', 'Corporate Engine Pro', 12, 14, 'Medium', null, null, null, null, '~Free', 256000],
            ['mistral', 'mistral-medium-2505', 'Corporate Engine Standard', 11, 15, 'Medium', null, null, null, null, '~Free', 256000],
            
            // === VITESSE, AUTOMATISATION & ÉCO (SMALL) ===
            ['mistral', 'mistral-small-2603', 'Fast Automate Turbo', 9, 19, 'Small', null, null, null, null, '~Free', 256000],
            ['mistral', 'mistral-small-2506', 'Fast Automate Standard', 8, 18, 'Small', null, null, null, null, '~Free', 256000],
            
            // === AGENTS & TRAITEMENTS SPÉCIALISÉS ===
            ['mistral', 'magistral-medium-2509', 'Agent Router Medium', 11, 15, 'Medium', null, null, null, null, '~Free', 256000],
            ['mistral', 'magistral-small-2509', 'Agent Router Small', 9, 17, 'Small', null, null, null, null, '~Free', 256000],
            
            // === CRÉATIVITÉ & EXPÉRIMENTATIONS ===
            ['mistral', 'labs-mistral-small-creative', 'Creative Writer (Uncensored)', 8, 17, 'Small', null, null, null, null, '~Free', 32768],
            
            // === VISION & ANALYSE GRAPHIQUE (MULTIMODAL) ===
            ['mistral', 'pixtral-large-2411', 'Vision Analyzer Max', 13, 13, 'Large', null, null, null, null, '~Free', 256000],
            ['mistral', 'pixtral-12b-2409', 'Vision Analyzer Light', 10, 16, 'Medium', null, null, null, null, '~Free', 256000],
            
            // === MODÈLES EMBARQUÉS / LOCAL (EDGE) ===
            ['mistral', 'ministral-14b-2512', 'Local Engine Heavy', 11, 15, 'Medium', null, null, null, null, '~Free', 256000],
            ['mistral', 'ministral-8b-2512', 'Local Engine Medium', 9, 17, 'Small', null, null, null, null, '~Free', 256000],
            ['mistral', 'ministral-3b-2512', 'Local Engine Micro', 6, 20, 'Micro', null, null, null, null, '~Free', 256000],
            
            // === AUDIO & TRAITEMENT VOCAL ===
            ['mistral', 'voxtral-small-2507', 'Audio Core Small', 10, 14, 'Small', null, null, null, null, '~Free', 256000],
            ['mistral', 'voxtral-mini-2507', 'Audio Core Mini', 7, 18, 'Micro', null, null, null, null, '~Free', 256000],
            
            // === AUTRES PROVIDERS GRATUITS ===
            // Groq
            ['groq', 'llama-3.3-70b-versatile', 'Llama 3.3 70B (Groq)', 10, 20, 'Large', null, null, null, null, '~1M/mo', 131072],
            ['groq', 'gemma2-9b-it', 'Gemma2 9B (Groq)', 7, 22, 'Small', null, null, null, null, '~1M/mo', 131072],
            
            // Cerebras
            ['cerebras', 'llama-3.3-70b', 'Llama 3.3 70B (Cerebras)', 10, 19, 'Large', null, null, null, null, '~1M/mo', 131072],
            
            // SambaNova
            ['sambanova', 'Meta-Llama-3.3-70B-Instruct', 'Llama 3.3 70B (SambaNova)', 10, 18, 'Large', null, null, null, null, '~1M/mo', 131072],
            ['sambanova', 'Meta-Llama-3.2-3B-Instruct', 'Llama 3.2 3B (SambaNova)', 5, 23, 'Small', null, null, null, null, '~1M/mo', 131072],
            
            // OpenRouter (modèles gratuits)
            ['openrouter', 'meta-llama/llama-3.2-3b-instruct:free', 'Llama 3.2 3B (OR Free)', 5, 21, 'Small', null, null, null, null, '~Free', 8192],
            ['openrouter', 'google/gemma-2-9b-it:free', 'Gemma 2 9B (OR Free)', 7, 20, 'Small', null, null, null, null, '~Free', 8192],
            ['openrouter', 'mistralai/mistral-7b-instruct:free', 'Mistral 7B Instruct (OR Free)', 6, 19, 'Small', null, null, null, null, '~Free', 8192],
            
            // GitHub Models
            ['github', 'meta-llama-3.1-8b-instruct', 'Llama 3.1 8B (GitHub)', 7, 18, 'Small', null, null, null, null, '~Free', 131072],
            ['github', 'microsoft/phi-3.5-mini-instruct', 'Phi-3.5 Mini (GitHub)', 6, 20, 'Small', null, null, null, null, '~Free', 131072],
            
            // Cohere
            ['cohere', 'command-r-08-2024', 'Command-R (Cohere)', 8, 16, 'Medium', null, null, null, null, '~1M/mo', 131072],
            
            // Cloudflare Workers AI
            ['cloudflare', '@cf/meta/llama-3.1-70b-instruct', 'Llama 3.1 70B (CF)', 10, 15, 'Large', null, null, null, null, '~18-45M/mo', 131072],
            
            // HuggingFace Inference API
            ['huggingface', 'accounts/fireworks/models/llama-v3p3-70b-instruct', 'Llama 3.3 70B (HF)', 10, 14, 'Large', null, null, null, null, '~1-3M/mo', 131072],
            
            // Zhipu AI
            ['zhipu', 'glm-4.5-flash', 'GLM-4.5 Flash', 9, 17, 'Large', null, null, null, 1000000, '~30M/mo', 131072],
        ];
    }
    
    /**
     * Initialise le store avec les modèles par défaut
     */
    public static function seedDataStore() {
        require_once __DIR__ . '/DataStore.php';
        DataStore::init();
        
        $models = self::getModels();
        $storedModels = DataStore::get('models', null, []);
        
        // Si aucun modèle n'est stocké, on initialise avec la liste par défaut
        if (empty($storedModels)) {
            $modelsData = [];
            foreach ($models as $model) {
                $key = $model[0] . ':' . $model[1];
                $modelsData[$key] = [
                    'platform' => $model[0],
                    'model_id' => $model[1],
                    'display_name' => $model[2],
                    'intelligence_rank' => $model[3],
                    'speed_rank' => $model[4],
                    'size_label' => $model[5],
                    'rpm_limit' => $model[6],
                    'rpd_limit' => $model[7],
                    'tpm_limit' => $model[8],
                    'tpd_limit' => $model[9],
                    'monthly_budget' => $model[10],
                    'context_window' => $model[11],
                    'is_active' => true
                ];
            }
            DataStore::set('models', $modelsData);
            return count($modelsData);
        }
        
        return count($storedModels);
    }
    
    /**
     * Récupère un modèle par son ID complet (platform:model_id)
     */
    public static function getModel($fullId) {
        require_once __DIR__ . '/DataStore.php';
        DataStore::init();
        return DataStore::get('models', $fullId, null);
    }
    
    /**
     * Récupère tous les modèles actifs
     */
    public static function getActiveModels() {
        require_once __DIR__ . '/DataStore.php';
        DataStore::init();
        $allModels = DataStore::get('models', null, []);
        $activeModels = [];
        foreach ($allModels as $key => $model) {
            if ($model['is_active'] ?? true) {
                $activeModels[$key] = $model;
            }
        }
        return $activeModels;
    }
}
