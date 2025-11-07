<?php
session_start();
require 'conexao.php'; // Inclui a conexão com o banco de dados
require 'funcoes.php'; // Centraliza a função de redirecionamento

// Variável para marcar o link ativo na sidebar
$page_active = 'carrinho.php';

// Inicializa o carrinho na sessão se ele não existir
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

$itens_carrinho = [];
$total_carrinho = 0.0;

// Se houver itens no carrinho, buscamos seus detalhes no banco de dados
if (!empty($_SESSION['carrinho'])) {
    // Coleta todos os IDs de itens únicos no carrinho para uma única consulta SQL
    $item_ids = array_keys($_SESSION['carrinho']);
    // Converte os IDs para uma string segura para usar na cláusula IN
    $placeholders = implode(',', array_fill(0, count($item_ids), '?'));

    $sql = "SELECT id, nome, preco, quantidade FROM itens WHERE id IN ({$placeholders})";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($item_ids); // Passa o array de IDs para PDO

    $itens_temp = $stmt->fetchAll(PDO::FETCH_ASSOC); 

    // Inicialize o array final para armazenar itens por ID
    $itens_db = [];

    // Reorganize o array para ter o ID do item como chave (em PHP)
    foreach ($itens_temp as $item) {
        $itens_db[$item['id']] = $item;
    }
    
    // Combina os dados do banco com as quantidades da sessão
    foreach ($_SESSION['carrinho'] as $item_id => $qtd_carrinho) {
        if (isset($itens_db[$item_id])) {
            $item = $itens_db[$item_id];
            $subtotal = $item['preco'] * $qtd_carrinho;
            
            $itens_carrinho[] = [
                'id'         => $item['id'],
                'nome'       => $item['nome'],
                'preco'      => (float)$item['preco'],
                'qtd_carrinho' => $qtd_carrinho,
                'subtotal'   => $subtotal,
                'qtd_estoque' => (int)$item['quantidade'], 
            ];
            $total_carrinho += $subtotal;
        } else {
            // Remove o item do carrinho se ele não existir mais no BD
            unset($_SESSION['carrinho'][$item_id]); 
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css"> 
    <title>Carrinho de Compras | GreenTech</title>
    <style>
        /* CSS LOCAL: Estilos específicos para o layout do carrinho */
        body {
            min-height: 100vh;
            display: block; 
            padding-top: 55px; /* Ajuste para dar espaço ao botão hamburguer no topo */
            padding-bottom: 80px; /* Padding extra para telas grandes com menu inferior */
            color: #000;
            background-color: #f0fdf4;
        }
        .cart-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff; /* Fundo branco */
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .item-details {
            flex-grow: 1;
        }
        .item-details h4 {
            margin: 0;
            color: #4CAF50;
        }
        .item-price-info {
            font-size: 0.9em;
            color: #666;
        }
        .item-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .item-subtotal {
            font-weight: 600;
            color: #4CAF50;
            min-width: 80px;
            text-align: right;
        }
        .remove-btn {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            font-size: 20px;
            transition: color 0.2s;
        }
        .remove-btn:hover {
            color: #bd2130;
        }
        .cart-summary {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #ddd;
            text-align: right;
        }
        .cart-summary h3 {
            font-size: 1.5em;
            color: #000;
        }
        .checkout-btn {
             width: 100%;
             height: 50px;
             margin-top: 15px;
             /* Usa o estilo do botão .login do style.css para consistência */
             background-color: #4CAF50; 
             color: white;
             border: none;
             border-radius: 40px;
             cursor: pointer;
             font-size: 18px;
             font-weight: 600;
             transition: 0.3s ease;
        }
        .checkout-btn:hover {
            background-color: transparent;
            color: #4CAF50;
            border: 2px solid #4CAF50;
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

    <div class="cart-container">
        <h1 style="text-align: center; margin-bottom: 25px; color: #4CAF50;">Seu Carrinho de Compras</h1>

        <?php if (empty($itens_carrinho)): ?>
            <p style="text-align: center; font-size: 1.1em; color: #555;">
                🛒 Seu carrinho está vazio. <a href="index.php" style="color: #4CAF50;">Comece a comprar agora!</a>
            </p>
        <?php else: ?>
            <div class="cart-items-list">
                <?php foreach ($itens_carrinho as $item): ?>
                    <div class="cart-item">
                        <div class="item-details">
                            <h4><?= htmlspecialchars($item['nome']) ?></h4>
                            <p class="item-price-info">
                                R$ <?= number_format($item['preco'], 2, ',', '.') ?> x <?= $item['qtd_carrinho'] ?> un.
                            </p>
                        </div>
                        
                        <div class="item-controls">
                            <a href="carrinho_acao.php?action=remove&id=<?= $item['id'] ?>" class="remove-btn" title="Remover Unidade">
                                <i class="bx bx-minus-circle"></i> 
                            </a>
                            
                            <span class="item-subtotal">
                                R$ <?= number_format($item['subtotal'], 2, ',', '.') ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <form action="checkout.php" method="POST">
                <div class="cart-summary">
                    <h3>Total: R$ <?= number_format($total_carrinho, 2, ',', '.') ?></h3>
                    
                    <button type="submit" class="checkout-btn">
                        <i class="bx bxs-check-circle"></i> Finalizar Compra
                    </button>
                </div>
            </form>
            
        <?php endif; ?>
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
        
        <a href="carrinho.php" class="nav-item active cart">
            <i class="bx bxs-cart"></i>
            <span>Carrinho</span>
        </a>
        
        <a href="perfil.php" class="nav-item">
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