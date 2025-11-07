<?php
session_start();
require 'conexao.php';
require 'funcoes.php'; // Para a função de redirecionamento

// Variável para marcar o link ativo na sidebar
$page_active = 'perfil.php';

// 1. Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    redirecionarParaMensagem('⚠️ Você precisa estar logado para ver os detalhes do pedido.', 'error', 'Login.html');
}

$usuario_id = $_SESSION['usuario_id'];
$pedido_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// 2. Valida o ID do pedido
if (!$pedido_id || $pedido_id < 1) {
    redirecionarParaMensagem('❌ ID do pedido inválido.', 'error', 'perfil.php');
}

$dados_pedido = null;
$itens_pedido = [];

// Mapeamento de Status (duplicado do perfil.php para consistência)
function getStatusInfo($status) {
    $map = [
        'Processando' => ['class' => 'pending', 'label' => 'Aguardando Pagamento'],
        'Concluido'   => ['class' => 'success', 'label' => 'Enviado'],
        'Cancelado'   => ['class' => 'error', 'label' => 'Cancelado'],
        'Entregue'    => ['class' => 'delivered', 'label' => 'Entregue'],
        'Criado'      => ['class' => 'pending', 'label' => 'Aguardando Ação'],
    ];
    return $map[$status] ?? ['class' => 'default', 'label' => $status];
}

// Funções de formatação de data
function formatarData($data) {
    return date('d/m/Y H:i', strtotime($data));
}

try {
    // 3. Busca os dados do pedido, garantindo que pertença ao usuário logado
    $sql_pedido = "SELECT id, data_pedido, valor_total, status 
                   FROM pedidos 
                   WHERE id = :pedido_id AND usuario_id = :usuario_id LIMIT 1";
    $stmt_pedido = $pdo->prepare($sql_pedido);
    $stmt_pedido->execute([
        ':pedido_id' => $pedido_id,
        ':usuario_id' => $usuario_id
    ]);
    $dados_pedido = $stmt_pedido->fetch(PDO::FETCH_ASSOC);

    if (!$dados_pedido) {
        redirecionarParaMensagem('❌ Pedido não encontrado ou você não tem permissão para visualizá-lo.', 'error', 'perfil.php');
    }

    // 4. Busca os itens do pedido
    $sql_itens = "SELECT nome_item, preco_unitario, quantidade, (preco_unitario * quantidade) as subtotal
                  FROM detalhes_pedido
                  WHERE pedido_id = :pedido_id";
    $stmt_itens = $pdo->prepare($sql_itens);
    $stmt_itens->execute([':pedido_id' => $pedido_id]);
    $itens_pedido = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);

} catch (\PDOException $e) {
    // Em caso de erro de banco de dados
    redirecionarParaMensagem('❌ Erro ao buscar detalhes do pedido.', 'error', 'perfil.php');
}

$status_info = getStatusInfo($dados_pedido['status']);
$status_class = $status_info['class'];
$status_exibicao = $status_info['label'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css"> 
    <title>Detalhes do Pedido #<?= $pedido_id ?> | GreenTech</title>
    <style>
        /* CSS LOCAL: Estilos específicos para o layout do detalhe do pedido */
        body {
            min-height: 100vh;
            display: block; 
            padding-top: 55px; /* Ajuste para dar espaço ao botão hamburguer no topo */
            padding-bottom: 80px; /* Padding extra para telas grandes com menu inferior */
            background-color: #f0fdf4;
        }
        main {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .header-pedido {
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        .header-pedido h1 {
            color: #4CAF50;
            font-size: 2.2em;
            margin: 0;
        }
        .header-pedido p {
            color: #666;
            margin: 5px 0;
            font-size: 1em;
        }
        
        /* Status Tag (Reutiliza classes do perfil.php) */
        .status-tag {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 1em;
            color: white;
            margin-top: 10px;
        }
        .status-success { background-color: #4CAF50; }
        .status-pending { background-color: #ff9800; }
        .status-error { background-color: #f44336; }
        .status-delivered { background-color: #2196F3; }
        .status-default { background-color: #9e9e9e; }

        /* Tabela de Itens */
        .itens-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }
        .itens-table th, .itens-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .itens-table th {
            background-color: #f0fdf4;
            color: #333;
            font-weight: 600;
        }
        .itens-table td {
            color: #555;
        }
        .itens-table tr:hover {
            background-color: #f9f9f9;
        }

        .total-final {
            text-align: right;
            font-size: 1.4em;
            font-weight: 700;
            color: #4CAF50;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #ddd;
        }

        .action-button-container {
            margin-top: 30px;
            text-align: center;
        }
        .action-button-container a {
            padding: 12px 25px;
            background-color: #ff9800; /* Laranja para Ação Pendente */
            color: white;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .action-button-container a:hover {
            background-color: #e68900;
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
    
    <main>
        <div class="header-pedido">
            <h1>Detalhes do Pedido #<?= $dados_pedido['id'] ?></h1>
            <p>Data do Pedido: <?= formatarData($dados_pedido['data_pedido']) ?></p>
            <span class="status-tag status-<?= $status_class ?>">
                <?= $status_exibicao ?>
            </span>
        </div>

        <h2 style="color: #333; font-size: 1.5em; margin-bottom: 15px;">Itens Comprados</h2>
        
        <table class="itens-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Preço Unitário</th>
                    <th>Qtd.</th>
                    <th style="text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itens_pedido as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['nome_item']) ?></td>
                    <td>R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                    <td><?= (int)$item['quantidade'] ?></td>
                    <td style="text-align: right;">R$ <?= number_format($item['subtotal'], 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total-final">
            Total Final do Pedido: R$ <?= number_format($dados_pedido['valor_total'], 2, ',', '.') ?>
        </div>
        
        <?php if ($dados_pedido['status'] === 'Criado' || $dados_pedido['status'] === 'Processando'): ?>
            <div class="action-button-container">
                <a href="pagamento.php?pedido_id=<?= $dados_pedido['id'] ?>">
                    <i class='bx bxs-credit-card'></i> Finalizar Pagamento
                </a>
            </div>
        <?php endif; ?>

    </main>
    
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
        function toggleMenu() {
            const sidebar = document.getElementById('sidebar-menu');
            const overlay = document.getElementById('overlay');
            sidebar.classList.toggle('open');
            overlay.classList.toggle('visible');
        }
    </script>
</body>
</html>