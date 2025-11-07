<?php
session_start();
require 'conexao.php'; 
require 'funcoes.php';

// Variável para marcar o link ativo na sidebar
$page_active = 'perfil.php';

// CÓDIGO PHP DE BUSCA DE PEDIDO (SEM MUDANÇAS)
if (!isset($_SESSION['usuario_id'])) {
    redirecionarParaMensagem('⚠️ Faça login para acessar a página de pagamento.', 'error', 'Login.html');
}

$pedido_id = filter_input(INPUT_GET, 'pedido_id', FILTER_VALIDATE_INT);

if (!$pedido_id) {
    redirecionarParaMensagem('❌ ID de pedido inválido ou ausente.', 'error', 'perfil.php');
}

$pedido = null;
try {
    // Busca o pedido para validar e obter o valor total
    $sql = "SELECT p.id, p.valor_total, p.status, u.nome AS nome_usuario, u.email
            FROM pedidos p 
            JOIN usuarios u ON p.usuario_id = u.id
            WHERE p.id = :id AND p.usuario_id = :user_id LIMIT 1";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $pedido_id, ':user_id' => $_SESSION['usuario_id']]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        redirecionarParaMensagem('❌ Pedido não encontrado ou você não tem permissão para visualizá-lo.', 'error', 'perfil.php');
    }
    
    // Verifica se o pedido já foi pago (Concluido) ou Cancelado
    if ($pedido['status'] === 'Concluido' || $pedido['status'] === 'Entregue') {
         redirecionarParaMensagem("✅ O Pedido #{$pedido_id} já está '{$pedido['status']}'.", 'success', 'detalhe_pedido.php?id=' . $pedido_id);
    }
    if ($pedido['status'] === 'Cancelado') {
         redirecionarParaMensagem("❌ O Pedido #{$pedido_id} foi 'Cancelado'.", 'error', 'detalhe_pedido.php?id=' . $pedido_id);
    }
    
    // IMPORTANTE: Atualiza o status para 'Processando' quando o usuário entra na tela de pagamento
    if ($pedido['status'] === 'Criado') {
        $sql_update = "UPDATE pedidos SET status = 'Processando' WHERE id = :id";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([':id' => $pedido_id]);
        $pedido['status'] = 'Processando'; // Atualiza o array local
    }

} catch (\PDOException $e) {
    redirecionarParaMensagem('❌ Erro de banco de dados ao carregar o pedido.', 'error', 'perfil.php');
}

$valor_formatado = number_format($pedido['valor_total'], 2, ',', '.');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css"> 
    <title>Pagamento Pedido #<?= $pedido_id ?> | GreenTech</title>
    <style>
        /* CSS LOCAL: Estilos específicos para o layout de Pagamento */
        body {
            min-height: 100vh;
            display: block; 
            padding-top: 55px; /* Ajuste para dar espaço ao botão hamburguer no topo */
            padding-bottom: 80px; /* Padding extra para telas grandes com menu inferior */
            background-color: #f0fdf4; 
        }
        .payment-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 25px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .header-pagamento {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
        }
        .header-pagamento h1 {
            color: #4CAF50;
            font-size: 2em;
            margin-bottom: 5px;
        }
        .header-pagamento p {
            font-size: 1.1em;
            color: #333;
            font-weight: 600;
        }
        
        /* Opções de Pagamento */
        .payment-options-list {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
            gap: 10px;
        }
        .payment-option {
            flex-basis: 30%; /* Distribui o espaço entre 3 opções */
            padding: 15px 5px;
            border: 2px solid #ddd;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        .payment-option i {
            font-size: 28px;
            display: block;
            margin-bottom: 5px;
            color: #4CAF50;
        }
        .payment-option:hover, .payment-option.active {
            border-color: #4CAF50;
            background-color: #e8f5e9;
            box-shadow: 0 0 8px rgba(76, 175, 80, 0.3);
        }
        
        /* Detalhes do Método (conteúdo dinâmico) */
        #payment-details {
            border: 1px dashed #4CAF50;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        /* Estilos dos Inputs (reutiliza o estilo do style.css .input-box) */
        .input-box {
            position: relative;
            width: 100%;
            height: 50px;
            margin: 15px 0;
        }
        .input-box input {
            width: 100%;
            height: 100%;
            background: #f0fdf4;
            border: none;
            outline: none;
            border: 2px solid #ddd;
            border-radius: 40px;
            font-size: 16px;
            color: #333;
            padding: 0 45px 0 20px;
        }
        .input-box input:focus {
            border-color: #4CAF50;
        }
        .input-box i {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            color: #4CAF50;
        }
        
        /* Botão de Confirmação (reutiliza o estilo .login do style.css) */
        #confirm-payment-btn {
            width: 100%;
            height: 50px;
            margin-top: 25px;
            background-color: #4CAF50; 
            color: white;
            border: none;
            border-radius: 40px;
            cursor: pointer;
            font-size: 18px;
            font-weight: 600;
            transition: 0.3s ease;
        }
        #confirm-payment-btn:hover:not(:disabled) {
            background-color: #388e3c; /* Verde um pouco mais escuro */
        }
        #confirm-payment-btn:disabled {
            background-color: #aaa;
            cursor: not-allowed;
        }
        
        /* Animação de Loading */
        .loading-animation {
            font-size: 40px;
            color: #4CAF50;
        }
        /* Corrigir padding para mobile com hambúrguer visível */
        @media (max-width: 768px) {
            body { padding-bottom: 20px; }
        }
    </style>
</head>
<body>
    
    <div class="hamburger-menu" onclick="toggleMenu()">
        <i class='bx bx-menu'></i>
    </div>
    
    <div class="sidebar-menu" id="sidebar-menu">
        <div class="sidebar-header">
            <span class="logo-text">G R E E N <i class='bx bxl-sketch' style="font-size: 1.3em;"></i> T E C H</span>
            <i class='bx bx-x close-btn' onclick="toggleMenu()"></i>
        </div>
        <ul class="sidebar-list">
            <li><a href="index.php" class="<?= ($page_active === 'index.php') ? 'active' : '' ?>">
                <i class="bx bxs-home"></i> Início
            </a></li>
            <li><a href="cadastrar_item.html" class="<?= ($page_active === 'cadastrar_item.html') ? 'active' : '' ?>">
                <i class="bx bxs-plus-square"></i> Cadastrar Item
            </a></li>
            <li><a href="tendencias.php" class="<?= ($page_active === 'tendencias.php') ? 'active' : '' ?>">
                <i class="bx bxs-analyse"></i> Tendências (ML)
            </a></li>
            <li><a href="carrinho.php" class="<?= ($page_active === 'carrinho.php') ? 'active' : '' ?>">
                <i class="bx bxs-cart"></i> Carrinho
            </a></li>
            <li><a href="perfil.php" class="<?= ($page_active === 'perfil.php') ? 'active' : '' ?>">
                <i class="bx bxs-user-circle"></i> Perfil
            </a></li>
        </ul>
        <ul class="sidebar-list" style="margin-top: auto; border-top: 1px solid #eee;">
             <li><a href="logout.php" style="color: #dc3545;"><i class="bx bx-log-out"></i> Sair</a></li>
        </ul>
    </div>
    
    <div class="overlay" id="overlay" onclick="toggleMenu()"></div>
    
    <div class="payment-container">
        <div class="header-pagamento">
            <h1>Finalizar Pagamento</h1>
            <p>Pedido #<?= $pedido_id ?>: Total R$ <?= $valor_formatado ?></p>
        </div>
        
        <h2>Escolha o Método de Pagamento</h2>
        
        <div class="payment-options-list">
            <div class="payment-option active" data-method="cartao" onclick="showPaymentMethod('cartao', this)">
                <i class='bx bxs-credit-card'></i> Cartão
            </div>
            
            <div class="payment-option" data-method="pix" onclick="showPaymentMethod('pix', this)">
                <i class='bx bxs-scan'></i> Pix
            </div>

            <div class="payment-option" data-method="boleto" onclick="showPaymentMethod('boleto', this)">
                <i class='bx bx-barcode-reader'></i> Boleto
            </div>
        </div>
        
        <div id="payment-details">
            </div>
        
        <button id="confirm-payment-btn" onclick="simulatePaymentConfirmation(<?= $pedido_id ?>)">
            <i class="bx bxs-lock-alt"></i> Confirmar Pagamento
        </button>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="detalhe_pedido.php?id=<?= $pedido_id ?>" style="color: #4CAF50; text-decoration: none;">
                <i class='bx bx-arrow-back'></i> Voltar para o Pedido
            </a>
        </div>
    </div>
    
    <nav class="navigation-bar">
        <a href="index.php" class="nav-item">
            <i class="bx bxs-home"></i>
            <span>Início</span>
        </a>
        
        <a href="cadastrar_item.html" class="nav-item">
            <i class="bx bxs-plus-square"></i>
            <span>Cadastrar</span>
        </a>
        
        <a href="tendencias.php" class="nav-item">
            <i class="bx bxs-analyse"></i>
            <span>Tendências</span>
        </a>
        
        <a href="carrinho.php" class="nav-item cart">
            <i class="bx bxs-cart"></i>
            <span>Carrinho</span>
        </a>
        
        <a href="perfil.php" class="nav-item active">
            <i class="bx bxs-user-circle"></i>
            <span>Perfil</span>
        </a>
    </nav>
    
    <script>
        // Variável passada do PHP para o JS
        const valorFormatado = "<?= $valor_formatado ?>";

        function toggleMenu() {
            const sidebar = document.getElementById('sidebar-menu');
            const overlay = document.getElementById('overlay');
            sidebar.classList.toggle('open');
            overlay.classList.toggle('visible');
        }

        const detailsDiv = document.getElementById('payment-details');
        const confirmButton = document.getElementById('confirm-payment-btn');

        // Adiciona e remove a classe 'active' nos botões de método
        function setActive(element) {
            document.querySelectorAll('.payment-option').forEach(btn => {
                btn.classList.remove('active');
            });
            element.classList.add('active');
            // Reativa o botão de confirmação ao mudar o método
            confirmButton.disabled = false;
            confirmButton.innerHTML = '<i class="bx bxs-lock-alt"></i> Confirmar Pagamento';
        }

        // Exibe o formulário ou instruções do método
        function showPaymentMethod(method, element) {
            setActive(element);
            let content = '';

            switch (method) {
                case 'cartao':
                    content = `
                        <h3>Detalhes do Cartão</h3>
                        <div class="input-box">
                            <input type="text" placeholder="Número do Cartão" required>
                            <i class='bx bx-credit-card-front'></i>
                        </div>
                        <div class="input-box">
                            <input type="text" placeholder="Nome Impresso no Cartão" required>
                            <i class='bx bxs-user'></i>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <div class="input-box" style="flex: 1;">
                                <input type="text" placeholder="MM/AA" required>
                                <i class='bx bx-calendar'></i>
                            </div>
                            <div class="input-box" style="flex: 1;">
                                <input type="text" placeholder="CVV" required>
                                <i class='bx bx-lock-alt'></i>
                            </div>
                        </div>
                    `;
                    break;

                case 'pix':
                    content = `
                        <div style="text-align: center;">
                            <h3>Pagamento por Pix</h3>
                            <p><strong>Valor: R$ ${valorFormatado}</strong></p>
                            <i class='bx bxs-qr' style="font-size: 100px; color: #4CAF50; margin: 10px 0;"></i>
                            <p>Chave Pix (CPF/CNPJ): 123.456.789-00</p>
                            <p style="font-size: 0.9em; color: #555;">Você deve abrir seu app de banco, escanear o QR Code ou usar a chave Pix.</p>
                            <p style="font-size: 0.8em; color: #f44336; font-weight: 600;">Após o pagamento, clique em "Confirmar Pagamento".</p>
                        </div>
                    `;
                    break;
                    
                case 'boleto':
                    // Simulação da linha digitável
                    const boletoCode = '12345.67890 12345.678901 23456.789012 3 00000000000000'; 
                    content = `
                        <div style="text-align: center;">
                            <h3>Pagamento por Boleto</h3>
                            <p><strong>Valor: R$ ${valorFormatado}</strong></p>
                            <p style="margin-top: 15px;">Seu boleto foi gerado com a linha digitável:</p>
                            <p style="font-size: 1.2em; font-weight: 700; color: #4CAF50; word-break: break-all;">
                                ${boletoCode}
                            </p>
                            <p style="font-size: 0.9em; color: #555;">O prazo de compensação do boleto é de até 3 dias úteis.</p>
                        </div>
                    `;
                    break;
            }

            detailsDiv.innerHTML = content;
        }

        // Função para simular a confirmação de pagamento
        function simulatePaymentConfirmation(pedidoId) {
            
            // 1. MENSAGEM DE PROCESSAMENTO
            detailsDiv.innerHTML = `
                <div style="text-align: center;">
                    <i class="bx bx-loader-alt bx-spin loading-animation"></i>
                    <h2>Conferindo seu Pagamento...</h2>
                    <p>Obrigado! Estamos verificando a transação. Isso pode levar alguns segundos.</p>
                </div>
            `;
            confirmButton.disabled = true;
            confirmButton.innerHTML = '<i class="bx bx-time-five bx-spin"></i> Processando...';

            // 2. SIMULA O TEMPO DE PROCESSAMENTO
            setTimeout(() => {
                // 3. REDIRECIONA PARA O SCRIPT PHP QUE ATUALIZA O STATUS NO BD
                window.location.href = `pagamento_confirmar.php?pedido_id=${pedidoId}`;
            }, 3000); // 3 segundos de simulação
        }

        // Carrega o método de pagamento padrão ao iniciar a página
        document.addEventListener('DOMContentLoaded', () => {
            // Inicia com o método "cartão" e aplica a classe 'active' no elemento
            const defaultOption = document.querySelector('.payment-option[data-method="cartao"]');
            showPaymentMethod('cartao', defaultOption);
        });
    </script>
</body>
</html>