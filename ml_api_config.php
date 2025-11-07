<?php
// ml_api_config.php
// Este arquivo e uma API de Machine Learning.

const ML_API_URL = 'http://api.greentech.ai/v1/predict'; 

/**
 * SIMULA A CHAMADA A UMA API EXTERNA DE ML.
 */
function simularPrevisaoML($endpoint, $data = []) {
    
    // Simulação para tendências de vendas gerais (index.php)
    if ($endpoint === 'vendas') {
        // IDs de itens de baixo número (assumidos como populares)
        $previsoes = [1, 2, 3]; 
        return $previsoes;
    }

    // Simulação para previsão de estoque (perfil.php)
    if ($endpoint === 'estoque' && isset($data['usuario_id'])) {
        $usuario_id = $data['usuario_id'];
        
        // CORREÇÃO FINAL:
        // Vamos garantir que o Item ID 1 (o mais comum de ser o primeiro item de teste)
        // sempre receba uma recomendação POSITIVA.
        // Se o seu item "trem" tem outro ID (ex: 5), você deve mudar os IDs aqui.
        
        // [item_id, previsao_venda_prox_mes, estoque_recomendado, tendencia (1=subir, -1=descer)]
        $recomendacoes = [
            // Item ID 1: Simula alta performance e precisa de estoque
            [1, 150, 200, 1], 
            
            // Item ID 2: Simula performance média/estável
            [2, 30, 40, 1], 
            
            // Item ID 3: Simula performance baixa (vendas em queda)
            [3, 5, 2, -1], 
            
            // Item ID 4: Simula performance média/estável
            [4, 25, 30, 1], 
        ];
        
        return $recomendacoes;
    }

    return [];
}
?>