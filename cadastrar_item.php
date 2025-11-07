<?php
session_start();
require 'conexao.php'; // Inclui a conexão com o banco de dados
require 'funcoes.php'; // Centraliza a função de redirecionamento

// 1. Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    // Redireciona para o login se não estiver logado
    redirecionarParaMensagem('⚠️ Você precisa estar logado para cadastrar um item.', 'error', 'Login.html');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2. Coletar e limpar os dados
    $nome       = trim($_POST['nome'] ?? '');
    $descricao  = trim($_POST['descricao'] ?? '');
    // Filtra o preço para garantir que seja um float válido
    $preco      = filter_var($_POST['preco'] ?? 0, FILTER_VALIDATE_FLOAT);
    // Filtra a quantidade para garantir que seja um int válido
    $quantidade = filter_var($_POST['quantidade'] ?? 0, FILTER_VALIDATE_INT);
    $usuario_id = $_SESSION['usuario_id']; // ID do usuário logado

    // 3. Validação básica
    if (empty($nome) || empty($descricao) || $preco === false || $quantidade === false || $quantidade < 1 || $preco <= 0) {
        redirecionarParaMensagem('⚠️ Preencha todos os campos corretamente (preço e quantidade devem ser válidos).', 'error', null, true);
    }

    // 4. Inserir no banco de dados
    $sql = "INSERT INTO itens (nome, descricao, preco, quantidade, usuario_id) 
            VALUES (:nome, :descricao, :preco, :quantidade, :usuario_id)";
    
    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute([
            ':nome'       => $nome,
            ':descricao'  => $descricao,
            ':preco'      => $preco,
            ':quantidade' => $quantidade,
            ':usuario_id' => $usuario_id
        ]);
        
        // SUCESSO
        redirecionarParaMensagem('✅ Item "' . htmlspecialchars($nome) . '" cadastrado com sucesso!', 'success', 'index.php');
        
    } catch (PDOException $e) {
        // ERRO
        // Em produção, você logaria $e->getMessage()
        redirecionarParaMensagem('❌ Erro ao cadastrar o item. Tente novamente.', 'error', null, true);
    }
} else {
    // Se não for POST, redireciona para o formulário
    header('Location: cadastrar_item.html');
    exit;
}
?>