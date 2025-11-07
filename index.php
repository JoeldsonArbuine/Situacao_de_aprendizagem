<?php
session_start();
require 'conexao.php'; 
require 'funcoes.php'; 

// --- Lógica de Busca de Todos os Itens para a Grade Principal ---
$itens_grade = []; 
try {
    $sql_grade = "SELECT i.*, u.nome AS nome_vendedor 
                  FROM itens i 
                  JOIN usuarios u ON i.usuario_id = u.id 
                  ORDER BY i.data_cadastro DESC";
            
    $stmt_grade = $pdo->query($sql_grade);
    $itens_grade = $stmt_grade->fetchAll(PDO::FETCH_ASSOC); 
} catch (\PDOException $e) {
    // Em caso de erro, a grade principal fica vazia
}

// Variável para marcar o link ativo na sidebar
$page_active = 'index.php'; 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css"> 
    <title>Início | GreenTech Solutions</title>
    <style>
        /* CSS LOCAL: Para estruturar o conteúdo dinâmico (mantido aqui por ser específico do layout de itens) */
        body {
            min-height: 100vh;
            display: block; 
            padding-top: 55px; /* Ajuste para dar espaço ao botão hamburguer no topo */
            padding-bottom: 80px; /* Padding extra para telas grandes com menu inferior */
            background-color: #f0fdf4;
        }
        .items-grid {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        .item-card {
            background-color: #fff; 
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            text-align: center;
        }
        .item-card:hover {
            transform: translateY(-5px);
        }
        .item-card h2 {
             font-size: 1.5em; 
            margin-bottom: 5px;
            color: #333;
        }
        .item-card p {
            margin-bottom: 10px;
            color: #666;
            font-size: 14px;
        }
        .item-price {
            font-size: 1.2em;
            font-weight: 600;
            color: #4CAF50; 
            margin: 10px 0;
        }
        .item-stock {
            font-size: 0.9em;
            color: #777;
            margin-bottom: 15px;
        }
        .buy-button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s ease;
            text-decoration: none; 
            display: block; 
        }
        .buy-button:hover {
            background-color: #45a049;
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
    
    <main style="text-align: center; padding-bottom: 20px;">
        <h1 style="color: #000; margin-bottom: 20px;">Catálogo Completo de Itens Sustentáveis</h1>
        
        <div class="items-grid">
        <?php if (!empty($itens_grade)): ?>
                <?php foreach ($itens_grade as $item): ?>
                    <div class="item-card">
                        <h2><?= htmlspecialchars($item['nome']) ?></h2>
                        <p class="description"><?= htmlspecialchars($item['descricao']) ?></p>
                        <p class="seller">Vendedor: <?= htmlspecialchars($item['nome_vendedor']) ?></p>

                        <p class="item-price">R$ <?= number_format($item['preco'], 2, ',', '.') ?></p>
                        <p class="item-stock">Estoque: <?= (int)$item['quantidade'] ?></p>
                        
                        <?php if ((int)$item['quantidade'] > 0): ?>
                            <a href="carrinho_acao.php?action=add&id=<?= $item['id'] ?>" class="buy-button">
                                <i class="bx bxs-cart-add"></i> Adicionar ao Carrinho
                            </a>
                        <?php else: ?>
                            <button class="buy-button" disabled style="background-color: #aaa; cursor: not-allowed;">
                                Esgotado
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="grid-column: 1 / -1; color: #555; font-size: 1.2em;">
                    Nenhum item cadastrado ainda. <a href="cadastrar_item.html" style="color: #4CAF50;">Cadastre o primeiro!</a>
                </p>
            <?php endif; ?>
        </div>
        
    </main>

    <nav class="navigation-bar">
        <a href="index.php" class="nav-item active">
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