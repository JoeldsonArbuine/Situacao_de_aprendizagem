<?php
session_start();
require 'funcoes.php'; // Inclui a função de redirecionamento unificada

// Destrói todas as variáveis de sessão
$_SESSION = array();

// Se for preciso destruir o cookie de sessão, também
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destrói a sessão.
session_destroy();

// Redireciona para a página de login com uma mensagem de sucesso (via Toast)
redirecionarParaMensagem(
    '✅ Você saiu da sua conta. Até breve!', 
    'success', 
    'Login.html'
);
?>