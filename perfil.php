<?php
session_start();
require 'conexao.php'; 
require 'funcoes.php'; // Inclui a função de redirecionamento unificada
require 'ml_api_config.php'; // Para simular a API de ML

// Variável para marcar o link ativo na sidebar
$page_active = 'perfil.php';

// 1. Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    redirecionarParaMensagem('⚠️ Você precisa estar logado para acessar o Perfil.', 'error', 'Login.html');
}

$usuario_id = $_SESSION['usuario_id'];
$dados_usuario = null;
$historico_pedidos = []; 
$itens_venda = []; // Itens que o usuário está vendendo
$recomendacoes_ml = []; // Previsões de estoque

// Mapeamento de Status
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

try {
    // --- 2. Busca os dados do usuário ---
    $sql_user = "SELECT nome, email, data_cadastro FROM usuarios WHERE id = :id LIMIT 1";
    $stmt_user = $pdo->prepare($sql_user);
    $stmt_user->execute([':id' => $usuario_id]);
    $dados_usuario = $stmt_user->fetch(PDO::FETCH_ASSOC);

    // REMOVIDO: O bloco que disparava a mensagem "Sessão inválida". Se $dados_usuario for nulo,
    // o fluxo cairá no 'else' do HTML, que exibe uma mensagem genérica, ou na verificação inicial.
    // if (!$dados_usuario) { ... } 

    // --- 3. Busca o Histórico de Pedidos ---
    $sql_pedidos = "SELECT id, data_pedido, valor_total, status 
                    FROM pedidos 
                    WHERE usuario_id = :id 
                    ORDER BY data_pedido DESC";
    $stmt_pedidos = $pdo->prepare($sql_pedidos);
    $stmt_pedidos->execute([':id' => $usuario_id]);
    $historico_pedidos = $stmt_pedidos->fetchAll(PDO::FETCH_ASSOC);

    // --- 4. Busca os Itens à Venda do Usuário ---
    $sql_itens = "SELECT id, nome, preco, quantidade FROM itens WHERE usuario_id = :id ORDER BY nome ASC";
    $stmt_itens = $pdo->prepare($sql_itens);
    $stmt_itens->execute([':id' => $usuario_id]);
    $itens_venda = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);
    
    // --- 5. Busca Recomendações de Estoque (Simulação de ML) ---
    $raw_recomendacoes = simularPrevisaoML('estoque', ['usuario_id' => $usuario_id]); 
    
    $map_recomendacoes = [];
    foreach ($raw_recomendacoes as $rec) {
        $map_recomendacoes[$rec[0]] = [
            'previsao' => $rec[1],
            'recomendado' => $rec[2],
            'tendencia' => $rec[3],
        ];
    }
    $recomendacoes_ml = $map_recomendacoes;

} catch (\PDOException $e) {
    // Em caso de erro crítico de BD, o usuário é deslogado silenciosamente ou a página mostra erro.
    $dados_usuario = null;
    $historico_pedidos = [];
    $itens_venda = [];
    $recomendacoes_ml = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css"> 
    <title>Meu Perfil | GreenTech</title>
    <style>
        /* CSS LOCAL: (Omitido por brevidade, mas deve ser o código completo do Passo 21) */
        /* ... */
        body {
            min-height: 100vh;
            display: block; 
            padding-top: 55px; 
            padding-bottom: 80px; 
            background-color: #f0fdf4;
        }
        main {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            color: #000;
        }
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            border-top: 5px solid #4CAF50;
        }
        .profile-header h1 {
            color: #4CAF50;
            font-size: 2.5em;
            margin-bottom: 5px;
        }
        .profile-header p {
            color: #555;
            font-size: 1em;
        }
        
        .section-box {
            background-color: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        .section-box h2 {
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
            font-size: 1.5em;
        }
        
        /* Estilos de Pedidos e Itens */
        .order-item-list a {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
            text-decoration: none;
            color: #333;
            transition: background-color 0.3s;
        }
        .order-item-list a:hover {
            background-color: #e8f5e9; 
            border-color: #4CAF50;
        }
        .order-info, .item-info {
            flex-grow: 1;
        }
        .order-info p, .item-info p {
            margin: 0;
            font-size: 0.9em;
            color: #666;
        }
        .order-info h4 {
            margin: 0;
            font-size: 1.1em;
            color: #4CAF50;
        }

        /* Tags de Status (Reutilizadas em detalhe_pedido.php) */
        .order-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.8em;
            color: white;
            min-width: 120px;
            text-align: center;
        }
        .status-success { background-color: #4CAF50; }
        .status-pending { background-color: #ff9800; }
        .status-error { background-color: #f44336; }
        .status-delivered { background-color: #2196F3; }
        .status-default { background-color: #9e9e9e; }
        
        /* Estilos de Recomendações ML */
        .ml-recommendation {
            margin-top: 15px;
            padding: 10px;
            border-left: 4px solid #03A9F4; 
            background-color: #e1f5fe;
            border-radius: 5px;
            font-size: 0.9em;
            color: #039BE5;
        }
        .ml-recommendation .bx {
            margin-right: 5px;
        }
        .ml-recommendation.positive {
            border-left: 4px solid #4CAF50;
            background-color: #e8f5e9;
            color: #388E3C;
        }
        .ml-recommendation.negative {
            border-left: 4px solid #FF9800;
            background-color: #fff3e0;
            color: #EF6C00;
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
        <?php if ($dados_usuario): ?>
            <div class="profile-header">
                <h1><?= htmlspecialchars($dados_usuario['nome']) ?></h1>
                <p><?= htmlspecialchars($dados_usuario['email']) ?></p>
                <p>Membro desde: <?= date('d/m/Y', strtotime($dados_usuario['data_cadastro'])) ?></p>
                <a href="logout.php" style="color: #f44336; margin-top: 10px; display: inline-block;">
                    <i class="bx bx-log-out"></i> Sair da Conta
                </a>
            </div>
            
            <div class="section-box">
                <h2>📦 Seus Itens à Venda (Vendedor)</h2>
                <?php if (!empty($itens_venda)): ?>
                    <div class="order-item-list">
                        <?php foreach ($itens_venda as $item): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9;">
                                <div class="item-info">
                                    <h4><?= htmlspecialchars($item['nome']) ?></h4>
                                    <p>Preço: R$ <?= number_format($item['preco'], 2, ',', '.') ?></p>
                                    <p>Estoque: <span style="font-weight: 600; color: <?= $item['quantidade'] < 5 ? '#f44336' : '#4CAF50' ?>;"><?= (int)$item['quantidade'] ?></span> unidades</p>
                                </div>
                                
                                <?php if (isset($recomendacoes_ml[$item['id']])): 
                                    $rec = $recomendacoes_ml[$item['id']];
                                    
                                    // Lógica ajustada para definir a classe, ícone e mensagem
                                    if ($rec['tendencia'] == 1) {
                                        $class = 'positive';
                                        $icon = 'bx bxs-up-arrow-alt';
                                        $msg = 'Aumente o Estoque';
                                    } elseif ($rec['tendencia'] == -1) {
                                        $class = 'negative';
                                        $icon = 'bx bxs-down-arrow-alt';
                                        $msg = 'Mantenha o Estoque'; 
                                    } else {
                                        // Caso a tendência seja 0 (Estável)
                                        $class = 'default';
                                        $icon = 'bx bxs-minus-circle';
                                        $msg = 'Estável / Revisão';
                                    }
                                ?>
                                    <div class="ml-recommendation <?= $class ?>">
                                        <i class="<?= $icon ?>"></i> Previsão ML: <?= $msg ?>
                                        <p style="margin: 0; font-size: 0.8em;">Recomendado: <?= (int)$rec['recomendado'] ?> un.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #555;">Você ainda não cadastrou nenhum item para vender. <a href="cadastrar_item.html" style="color: #4CAF50;">Cadastre um item!</a></p>
                <?php endif; ?>
            </div>


            <div class="section-box">
                <h2>🧾 Histórico de Pedidos (Comprador)</h2>
                <?php if (!empty($historico_pedidos)): ?>
                    <div class="order-item-list">
                        <?php foreach ($historico_pedidos as $pedido): 
                            $status_info = getStatusInfo($pedido['status']);
                            $status_class = $status_info['class'];
                            $status_exibicao = $status_info['label'];
                        ?>
                            <a href="detalhe_pedido.php?id=<?= $pedido['id'] ?>">
                                <div class="order-info">
                                    <h4>Pedido #<?= $pedido['id'] ?></h4>
                                    <p>Data: <?= date('d/m/Y', strtotime($pedido['data_pedido'])) ?></p>
                                    <p>Total: R$ <?= number_format($pedido['valor_total'], 2, ',', '.') ?></p>
                                </div>
                                <span class="order-status status-<?= $status_class ?>">
                                    <?= $status_exibicao ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #555;">Você não realizou nenhum pedido ainda. 🛒</p>
                <?php endif; ?>
            </div>
            
        <?php else: ?>
             <p style="color: red; text-align: center;">Erro ao carregar dados do usuário.</p>
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