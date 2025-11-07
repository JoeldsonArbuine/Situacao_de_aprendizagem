<?php
require 'conexao.php';
require 'funcoes.php'; // Inclui a função de redirecionamento unificada

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        redirecionarParaMensagem('⚠️ Por favor, insira seu e-mail.', 'error', null, true);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirecionarParaMensagem('❌ E-mail inválido.', 'error', null, true);
    }

    // 1. Verificar se o e-mail existe no banco de dados
    $sql = "SELECT id FROM usuarios WHERE email = :email LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch();

    // NOTA DE SEGURANÇA: Não importa se o e-mail existe ou não, 
    // a mensagem de sucesso é sempre exibida para evitar que invasores 
    // descubram e-mails válidos.

    // 2. Simula o processo (em um sistema real, aqui você enviaria o e-mail)
    
    // SUCESSO OU MENSAGEM GENÉRICA
    redirecionarParaMensagem(
        "✅ Se o e-mail estiver em nosso sistema, as instruções de redefinição de senha foram enviadas. Verifique sua caixa de entrada.", 
        'success', 
        'Login.html' // Redireciona de volta para o login
    );
        
} else {
    // Se não for POST, redireciona para o formulário
    header('Location: esqueci_senha.html');
    exit;
}
?>