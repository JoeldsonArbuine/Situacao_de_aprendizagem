<?php
session_start();
require 'conexao.php';
require 'funcoes.php'; // Inclui a função de redirecionamento unificada

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        redirecionarParaMensagem('⚠️ Preencha todos os campos.', 'error', null, true);
    }

    $sql = "SELECT id, nome, senha_hash FROM usuarios WHERE email = :email LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // 1. Verifica se o usuário existe E se a senha confere
    if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
        // LOGIN BEM-SUCEDIDO
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        
        // Redireciona para a página principal com mensagem de sucesso
        redirecionarParaMensagem("✅ Bem-vindo(a), " . htmlspecialchars($usuario['nome']) . "!", 'success', 'index.php');
        
    } else {
        // ERRO DE LOGIN
        redirecionarParaMensagem('❌ E-mail ou senha incorretos.', 'error', null, true);
    }
} else {
    // Se não for POST, redireciona para a página de login
    header('Location: Login.html');
    exit;
}
?>