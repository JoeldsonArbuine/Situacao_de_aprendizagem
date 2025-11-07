-- Cria o banco de dados se não existir
CREATE DATABASE IF NOT EXISTS `greentech_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `greentech_db`;

-- --------------------------------------------------------
-- Tabela de Usuários (Autenticação)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `senha_hash` VARCHAR(255) NOT NULL,
  `data_cadastro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_unico` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Tabela de Itens (Produtos)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `itens` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(255) NOT NULL,
  `descricao` TEXT,
  `preco` DECIMAL(10, 2) NOT NULL,
  `quantidade` INT(11) NOT NULL,
  `usuario_id` INT(11) NOT NULL, -- Vendedor
  `data_cadastro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_item_usuario` (`usuario_id`),
  CONSTRAINT `fk_item_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Tabela de Pedidos (Transações)
-- CORREÇÃO: Adicionada a coluna 'data_confirmacao'
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11) NOT NULL, -- Comprador
  `data_pedido` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `valor_total` DECIMAL(10, 2) NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'Processando',
  `data_confirmacao` DATETIME NULL DEFAULT NULL, -- NOVO CAMPO para registrar quando o pagamento foi confirmado
  PRIMARY KEY (`id`),
  KEY `fk_pedido_usuario` (`usuario_id`),
  CONSTRAINT `fk_pedido_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Tabela de Detalhes do Pedido (Itens de cada pedido)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `detalhes_pedido` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` INT(11) NOT NULL,
  `item_id` INT(11) NOT NULL,
  `nome_item` VARCHAR(255) NOT NULL,
  `preco_unitario` DECIMAL(10, 2) NOT NULL,
  `quantidade` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_detalhe_pedido` (`pedido_id`),
  KEY `fk_detalhe_item` (`item_id`),
  CONSTRAINT `fk_detalhe_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_detalhe_item` FOREIGN KEY (`item_id`) REFERENCES `itens` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Se você precisar de dados de exemplo para teste (OPCIONAL)
-- (Insira um usuário e alguns itens para testar o índice)
-- --------------------------------------------------------
/*
-- Insere um usuário de teste (Senha: '123456')
INSERT INTO usuarios (nome, email, senha_hash) VALUES 
('Vendedor Teste', 'teste@greentech.com', '$2y$10$wT8hM0mN3U7M4F5T6R7S8U9V0W1X2Y3Z4A5B6C7D8E9F0G1H2'); 

-- Insere alguns itens de teste
INSERT INTO itens (nome, descricao, preco, quantidade, usuario_id) VALUES 
('Semente Orgânica', 'Semente de couve orgânica e sustentável.', 15.50, 50, 1),
('Composto Natural', 'Fertilizante orgânico para jardim.', 49.90, 20, 1),
('Ecolâmpada LED', 'Lâmpada LED de baixo consumo.', 25.00, 100, 1);
*/