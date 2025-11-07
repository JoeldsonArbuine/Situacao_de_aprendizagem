<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css"> 
    <title>Processando...</title>
</head>
<body>
    <?php
    // Verifica se a mensagem e o tipo de ação foram passados
    $mensagem = $_GET['msg'] ?? 'Ocorreu uma ação.';
    $tipo = $_GET['type'] ?? 'success'; // 'success' ou 'error'
    $redirecionamento = $_GET['redirect'] ?? null;
    $voltar = isset($_GET['back']); // Verifica se é para voltar (erro)

    // Configura a URL de redirecionamento ou a ação de voltar
    $acao_js = '';
    if ($redirecionamento) {
        // Redireciona para uma URL específica após o Toast
        $acao_js = "window.location.href = '{$redirecionamento}';";
    } elseif ($voltar) {
        // Volta para a página anterior (útil para erros)
        $acao_js = "window.history.back();";
    }

    // Código JavaScript que exibe o Toast
    echo "<script>
            const toast = document.createElement('div');
            toast.className = 'toast-message toast-{$tipo}';
            toast.textContent = '{$mensagem}';
            document.body.appendChild(toast);
            
            // Mostra o Toast
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);

            // Esconde o Toast e executa a ação (redirecionar/voltar) após 3 segundos
            setTimeout(() => {
                toast.classList.remove('show');
                {$acao_js} 
            }, 3000); 
          </script>";
    ?>
    </body>
</html>