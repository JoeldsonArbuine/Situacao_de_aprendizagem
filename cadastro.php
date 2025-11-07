<?php
require 'conexao.php';
require 'funcoes.php'; // Inclui a função de redirecionamento unificada

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome      = trim($_POST['nome'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $senha     = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    // Validações
    if ($senha !== $confirmar) {
        redirecionarParaMensagem('❌ As senhas não conferem. Tente novamente.', 'error', null, true);
    }

    if (empty($nome) || empty($email) || empty($senha)) {
        redirecionarParaMensagem('⚠️ Preencha todos os campos.', 'error', null, true);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirecionarParaMensagem('❌ Email inválido.', 'error', null, true);
    }
    
    // Hash da senha (segurança)
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // SQL para inserção
    $sql = "INSERT INTO usuarios (nome, email, senha_hash) VALUES (:nome, :email, :senha)";
    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute([
            ':nome'  => $nome,
            ':email' => $email,
            ':senha' => $senha_hash
        ]);
        
        // SUCESSO
        redirecionarParaMensagem('✅ Cadastro realizado com sucesso! Faça seu login.', 'success', 'Login.html');
        
    } catch (PDOException $e) {
        // '23000' é o código SQLSTATE para violação de chave única (UNIQUE constraint), 
        // o que geralmente significa que o e-mail já está cadastrado.
        if ($e->getCode() === '23000') {
            // ERRO: E-mail duplicado
            redirecionarParaMensagem('❌ Este e-mail já está cadastrado. Faça login ou use outro e-mail.', 'error', 'cadastro.html');
        } else {
            // Outro erro de banco de dados
            redirecionarParaMensagem('❌ Erro no banco de dados ao tentar cadastrar.', 'error', null, true);
        }
    }
} else {
    // Se não for POST, redireciona para a página de cadastro
    header('Location: cadastro.html');
    exit;
}
?>