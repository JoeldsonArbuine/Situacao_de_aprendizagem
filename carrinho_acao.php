<?php
session_start();
require 'conexao.php';
require 'funcoes.php'; // Centraliza a função de redirecionamento

// 1. Inicializa o carrinho se não existir
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

// Filtra e valida as entradas
$item_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$action = $_GET['action'] ?? 'add'; // Padrão é adicionar

if (!$item_id || $item_id < 1) {
    redirecionarParaMensagem('❌ ID de item inválido.', 'error', 'index.php');
}

try {
    // --- Lógica para ADICIONAR item (action=add) ---
    if ($action === 'add') {
        // 2. Busca informações do item para validação (estoque)
        // Busca o nome para dar feedback e a quantidade para checar o estoque
        $sql = "SELECT nome, quantidade FROM itens WHERE id = :id LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $item_id]);
        $item = $stmt->fetch();

        if (!$item) {
            redirecionarParaMensagem('❌ Item não encontrado.', 'error', 'index.php');
        }

        // 3. Verifica o estoque e adiciona
        $qtd_estoque = (int)$item['quantidade'];
        $qtd_carrinho_atual = $_SESSION['carrinho'][$item_id] ?? 0;

        if ($qtd_carrinho_atual < $qtd_estoque) {
            $_SESSION['carrinho'][$item_id] = $qtd_carrinho_atual + 1;
            
            // REDIRECIONAMENTO DE SUCESSO (melhoria de feedback)
            redirecionarParaMensagem("✅ O item  foi adicionado ao carrinho.", 'success', 'index.php');
        } else {
            redirecionarParaMensagem('⚠️ Estoque esgotado ou limite de estoque atingido para este item.', 'error', 'index.php');
        }
    }
    
    // --- Lógica para REMOVER item (action=remove) ---
    elseif ($action === 'remove') {
        if (isset($_SESSION['carrinho'][$item_id])) {
            // Remove uma unidade
            $_SESSION['carrinho'][$item_id]--;
            
            // Se a quantidade chegar a zero ou menos, remove o item da sessão
            if ($_SESSION['carrinho'][$item_id] <= 0) {
                unset($_SESSION['carrinho'][$item_id]);
                // Melhor: redirecionar para o carrinho para ver a mudança
                redirecionarParaMensagem('✅ Item removido completamente do carrinho.', 'success', 'carrinho.php'); 
            }
            
            redirecionarParaMensagem('✅ Uma unidade do item foi removida.', 'success', 'carrinho.php');
            
        } else {
            redirecionarParaMensagem('❌ Item não está no carrinho.', 'error', 'carrinho.php');
        }
    }

} catch (\PDOException $e) {
    // Em caso de falha de BD
    redirecionarParaMensagem('❌ Erro de banco de dados ao processar o carrinho.', 'error', 'index.php');
}

// Redirecionamento de segurança em caso de ação inválida (fallback)
redirecionarParaMensagem('❌ Ação inválida ou não reconhecida.', 'error', 'index.php');
?>