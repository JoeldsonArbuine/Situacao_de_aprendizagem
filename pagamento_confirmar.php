<?php
session_start();
require 'conexao.php'; 
require 'funcoes.php';

// 1. Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    redirecionarParaMensagem('⚠️ Faça login para confirmar o pagamento.', 'error', 'Login.html');
}

// 2. Obtém o ID do pedido da URL
$pedido_id = filter_input(INPUT_GET, 'pedido_id', FILTER_VALIDATE_INT);

if (!$pedido_id) {
    redirecionarParaMensagem('❌ ID de pedido inválido ou ausente.', 'error', 'perfil.php');
}

$usuario_id = $_SESSION['usuario_id'];
$novo_status = 'Concluido'; 

try {
    // 3. Verifica o status atual do pedido e se pertence ao usuário
    $sql_check = "SELECT status FROM pedidos WHERE id = :id AND usuario_id = :user_id LIMIT 1";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([':id' => $pedido_id, ':user_id' => $usuario_id]);
    $pedido_atual = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$pedido_atual) {
        redirecionarParaMensagem('❌ Pedido não encontrado ou você não tem permissão.', 'error', 'perfil.php');
    }

    if ($pedido_atual['status'] === $novo_status) {
        redirecionarParaMensagem("⚠️ O Pedido #{$pedido_id} já está 'Concluído'.", 'error', 'perfil.php');
    }
    
    if ($pedido_atual['status'] !== 'Processando') {
         redirecionarParaMensagem("⚠️ O Pedido #{$pedido_id} possui o status '{$pedido_atual['status']}'. Apenas pedidos 'Processando' podem ser confirmados.", 'error', 'perfil.php');
    }
    
    // 4. Atualiza o status do pedido para 'Concluído'
    // AGORA COM data_confirmacao (porque a coluna foi adicionada no SQL)
    $sql_update = "UPDATE pedidos SET status = :status, data_confirmacao = NOW() WHERE id = :id AND usuario_id = :user_id";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute([
        ':status' => $novo_status,
        ':id' => $pedido_id,
        ':user_id' => $usuario_id
    ]);

    // 5. Verifica se a atualização foi bem-sucedida
    if ($stmt_update->rowCount() > 0) {
        redirecionarParaMensagem(
            "✅ Pedido #{$pedido_id} confirmado! Seu pedido está sendo processado para envio.", 
            'success', 
            'perfil.php' // Redireciona para o histórico de pedidos
        );
    } else {
        redirecionarParaMensagem('❌ Não foi possível confirmar o pagamento. Tente novamente.', 'error', 'pagamento.php?pedido_id=' . $pedido_id);
    }

} catch (\PDOException $e) {
    // Para depuração
    die('❌ ERRO CRÍTICO DE BANCO DE DADOS NA CONFIRMAÇÃO: ' . $e->getMessage()); 
}

// Fallback de segurança
redirecionarParaMensagem('❌ Ação inválida ou não processada.', 'error', 'perfil.php');
?>