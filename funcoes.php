<?php
// funcoes.php

/**
 * Redireciona para a página de mensagem (Toast) com parâmetros.
 *
 * @param string $mensagem A mensagem a ser exibida no Toast.
 * @param string $tipo 'success' ou 'error'.
 * @param string|null $redirecionarPara URL para redirecionar após o Toast.
 * @param bool $voltar Se true, o navegador volta para a página anterior.
 */
function redirecionarParaMensagem($mensagem, $tipo, $redirecionarPara = null, $voltar = false) {
    $url = 'mensagem.php?msg=' . urlencode($mensagem) . '&type=' . urlencode($tipo);
    if ($redirecionarPara) {
        $url .= '&redirect=' . urlencode($redirecionarPara);
    }
    if ($voltar) {
        $url .= '&back=1';
    }
    header('Location: ' . $url);
    exit;
}
?>