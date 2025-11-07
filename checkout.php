<?php
session_start();
require 'conexao.php'; 
require 'funcoes.php'; // Centraliza a função de redirecionamento

// 1. Verificações Iniciais
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionarParaMensagem('❌ Acesso inválido.', 'error', 'carrinho.php');
}

if (!isset($_SESSION['usuario_id'])) {
    redirecionarParaMensagem('⚠️ Faça login para finalizar a compra.', 'error', 'Login.html');
}

if (empty($_SESSION['carrinho'])) {
    redirecionarParaMensagem('⚠️ Seu carrinho está vazio!', 'error', 'index.php');
}

$usuario_id = $_SESSION['usuario_id'];
$carrinho = $_SESSION['carrinho'];
$valor_total = 0.0;
$itens_detalhe = []; 

try {
    // --- INÍCIO DA TRANSAÇÃO (Garante atomicidade) ---
    $pdo->beginTransaction();

    // 2. Busca e Validação de Estoque (com bloqueio FOR UPDATE)
    $item_ids = array_keys($carrinho);
    $placeholders = implode(',', array_fill(0, count($item_ids), '?'));
    
    // Bloqueia as linhas na tabela de itens para esta transação
    $sql_itens = "SELECT id, nome, preco, quantidade FROM itens WHERE id IN ({$placeholders}) FOR UPDATE";
    $stmt_itens = $pdo->prepare($sql_itens);
    $stmt_itens->execute($item_ids); 
    $itens_db = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);

    // Validação de itens e estoque (código similar ao anterior)
    foreach ($itens_db as $item) {
        $item_id = $item['id'];
        $qtd_carrinho = $carrinho[$item_id];
        $qtd_estoque = (int)$item['quantidade'];

        if ($qtd_carrinho > $qtd_estoque) {
            $pdo->rollBack();
            redirecionarParaMensagem('⚠️ Estoque insuficiente para o item: ' . htmlspecialchars($item['nome']) . '.', 'error', 'carrinho.php');
        }

        $subtotal = $item['preco'] * $qtd_carrinho;
        $valor_total += $subtotal;

        $itens_detalhe[] = [
            'item_id' => $item_id,
            'nome_item' => $item['nome'],
            'preco_unitario' => (float)$item['preco'],
            'quantidade' => $qtd_carrinho,
        ];
    }
    
    // 3. Inserir o Pedido Principal (tabela 'pedidos') - STATUS INICIAL 'Processando'
    $sql_pedido = "INSERT INTO pedidos (usuario_id, valor_total, status) 
                   VALUES (:user_id, :total, 'Processando')"; // Status inicial
    $stmt_pedido = $pdo->prepare($sql_pedido);
    $stmt_pedido->execute([
        ':user_id' => $usuario_id,
        ':total' => $valor_total,
    ]);
    $pedido_id = $pdo->lastInsertId();

    // 4. Inserir Detalhes do Pedido
    $sql_detalhe = "INSERT INTO detalhes_pedido (pedido_id, item_id, nome_item, preco_unitario, quantidade) 
                    VALUES (:pedido_id, :item_id, :nome, :preco, :qtd)";
    $stmt_detalhe = $pdo->prepare($sql_detalhe);

    // Nota: A atualização do estoque NÃO é feita aqui, mas sim na CONFIRMAÇÃO DO PAGAMENTO
    // Para simplificar a simulação, VAMOS FAZER A ATUALIZAÇÃO DO ESTOQUE AQUI. 
    // Em um sistema real, o estoque seria atualizado SÓ APÓS A CONFIRMAÇÃO DO PAGAMENTO.

    $sql_estoque = "UPDATE itens SET quantidade = quantidade - :qtd WHERE id = :item_id";
    $stmt_estoque = $pdo->prepare($sql_estoque);

    foreach ($itens_detalhe as $detalhe) {
        // Inserir Detalhe
        $stmt_detalhe->execute([
            ':pedido_id' => $pedido_id,
            ':item_id' => $detalhe['item_id'],
            ':nome' => $detalhe['nome_item'],
            ':preco' => $detalhe['preco_unitario'],
            ':qtd' => $detalhe['quantidade'],
        ]);
        
        // Atualizar Estoque (Simplificação: Desconta o estoque no checkout, não no pagamento)
        $stmt_estoque->execute([
            ':qtd' => $detalhe['quantidade'],
            ':item_id' => $detalhe['item_id'],
        ]);
    }

    // --- 5. FINALIZAÇÃO DA TRANSAÇÃO E REDIRECIONAMENTO ---
    $pdo->commit();
    $_SESSION['carrinho'] = []; // Limpa o carrinho
    
    // SUCESSO - Redireciona para a tela de pagamento
    header('Location: pagamento.php?pedido_id=' . $pedido_id);
    exit;
    
} catch (\PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // ERRO
    redirecionarParaMensagem('❌ Erro no processamento da compra. Tente novamente.', 'error', 'carrinho.php');
}
?>