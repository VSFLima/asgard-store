-- =============================================
-- Asgard Store - Database Schema
-- Marketplace para compra e venda de contas
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------
-- Tabela de Usuarios
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL,
  `sobrenome` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `senha` VARCHAR(255) NOT NULL,
  `telefone` VARCHAR(20),
  `telegram` VARCHAR(100),
  `avatar` VARCHAR(255) DEFAULT 'default.png',
  `chave_pix` VARCHAR(255),
  `tipo_pix` ENUM('cpf','cnpj','email','telefone','aleatoria') DEFAULT 'aleatoria',
  `saldo` DECIMAL(10,2) DEFAULT 0.00,
  `total_vendas` INT DEFAULT 0,
  `nota_media` DECIMAL(3,2) DEFAULT 0.00,
  `status` ENUM('ativo','suspenso','banido') DEFAULT 'ativo',
  `admin` TINYINT(1) DEFAULT 0,
  `senha_temporaria` TINYINT(1) DEFAULT 0,
  `token_recuperacao` VARCHAR(64),
  `token_expira` DATETIME,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_email` (`email`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------
-- Tabela de Jogos
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `jogos` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `icone` VARCHAR(255) DEFAULT 'default-game.png',
  `moeda_nome` VARCHAR(50),
  `moeda_icone` VARCHAR(255),
  `ativo` TINYINT(1) DEFAULT 1,
  `ordem` INT DEFAULT 0,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------
-- Tabela de Categorias (por jogo)
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `jogo_id` INT UNSIGNED NOT NULL,
  `nome` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`jogo_id`) REFERENCES `jogos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------
-- Tabela de Anuncios (contas a venda)
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `anuncios` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED NOT NULL,
  `jogo_id` INT UNSIGNED NOT NULL,
  `titulo` VARCHAR(255) NOT NULL,
  `descricao` TEXT,
  `nivel_rank` VARCHAR(100),
  `itens_especiais` TEXT,
  `servidor` VARCHAR(50),
  `screenshots` JSON,
  `video_url` VARCHAR(500),
  `preco` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pendente','aprovado','reprovado','vendido') DEFAULT 'pendente',
  `motivo_reprovacao` TEXT,
  `visualizacoes` INT DEFAULT 0,
  `destaque` TINYINT(1) DEFAULT 0,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`jogo_id`) REFERENCES `jogos`(`id`) ON DELETE CASCADE,
  INDEX `idx_status` (`status`),
  INDEX `idx_jogo_status` (`jogo_id`, `status`),
  INDEX `idx_jogo` (`jogo_id`),
  INDEX `idx_preco` (`preco`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------
-- Tabela de Compras
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `compras` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `anuncio_id` INT UNSIGNED NOT NULL,
  `comprador_id` INT UNSIGNED NOT NULL,
  `vendedor_id` INT UNSIGNED NOT NULL,
  `valor` DECIMAL(10,2) NOT NULL,
  `comissao` DECIMAL(10,2) NOT NULL,
  `valor_vendedor` DECIMAL(10,2) NOT NULL,
  `metodo_pagamento` ENUM('pix','crypto') NOT NULL,
  `comprovante_pix` VARCHAR(255),
  `wallet_crypto` VARCHAR(255),
  `status` ENUM('aguardando_pagamento','pagamento_confirmado','entregando','entregue','em_disputa','concluido','cancelado') DEFAULT 'aguardando_pagamento',
  `confirmado_comprador` TINYINT(1) DEFAULT 0,
  `confirmado_vendedor` TINYINT(1) DEFAULT 0,
  `observacoes_admin` TEXT,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`anuncio_id`) REFERENCES `anuncios`(`id`),
  FOREIGN KEY (`comprador_id`) REFERENCES `usuarios`(`id`),
  FOREIGN KEY (`vendedor_id`) REFERENCES `usuarios`(`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_comprador` (`comprador_id`),
  INDEX `idx_vendedor` (`vendedor_id`),
  INDEX `idx_status_created` (`status`, `criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------
-- Tabela de Dados da Conta (entrega)
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `dados_conta` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `compra_id` INT UNSIGNED NOT NULL UNIQUE,
  `email_conta` VARCHAR(255),
  `senha_conta` VARCHAR(255),
  `nivel_conta` VARCHAR(100),
  `observacoes` TEXT,
  `entregue_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`compra_id`) REFERENCES `compras`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------
-- Tabela de Creditos (pacotes de moeda)
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `creditos` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `jogo_id` INT UNSIGNED NOT NULL,
  `nome` VARCHAR(100) NOT NULL,
  `descricao` TEXT,
  `quantidade` INT NOT NULL,
  `moeda_jogo` VARCHAR(50),
  `preco_original` DECIMAL(10,2) NOT NULL,
  `desconto_percentual` INT DEFAULT 0,
  `preco_final` DECIMAL(10,2) NOT NULL,
  `estoque` INT DEFAULT 0,
  `ativo` TINYINT(1) DEFAULT 1,
  `ordem` INT DEFAULT 0,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`jogo_id`) REFERENCES `jogos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------
-- Tabela de Compra de Creditos
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `compra_creditos` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED NOT NULL,
  `credito_id` INT UNSIGNED NOT NULL,
  `quantidade` INT DEFAULT 1,
  `valor_pago` DECIMAL(10,2) NOT NULL,
  `metodo_pagamento` ENUM('pix','crypto') NOT NULL,
  `comprovante` VARCHAR(255),
  `codigo_entregue` TEXT,
  `status` ENUM('pendente','pago','entregue','cancelado') DEFAULT 'pendente',
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`),
  FOREIGN KEY (`credito_id`) REFERENCES `creditos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------
-- Tabela de Saques
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `saques` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED NOT NULL,
  `valor` DECIMAL(10,2) NOT NULL,
  `metodo` ENUM('pix','crypto') NOT NULL,
  `chave_pix` VARCHAR(255),
  `wallet_crypto` VARCHAR(255),
  `status` ENUM('pendente','processando','pago','rejeitado') DEFAULT 'pendente',
  `motivo_rejeicao` TEXT,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `processado_em` DATETIME,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------
-- Tabela de Mensagens (Chat)
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `mensagens` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `compra_id` INT UNSIGNED NOT NULL,
  `remetente_id` INT UNSIGNED NOT NULL,
  `conteudo` TEXT NOT NULL,
  `lida` TINYINT(1) DEFAULT 0,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`compra_id`) REFERENCES `compras`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`remetente_id`) REFERENCES `usuarios`(`id`),
  INDEX `idx_compra` (`compra_id`),
  INDEX `idx_lida` (`lida`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------
-- Tabela de Notificacoes
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `notificacoes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED NOT NULL,
  `titulo` VARCHAR(255) NOT NULL,
  `mensagem` TEXT NOT NULL,
  `tipo` VARCHAR(50),
  `link` VARCHAR(255),
  `lida` TINYINT(1) DEFAULT 0,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  INDEX `idx_usuario` (`usuario_id`),
  INDEX `idx_lida` (`lida`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------
-- Tabela de Telegram Users
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `telegram_users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED NOT NULL,
  `chat_id` BIGINT NOT NULL,
  `username` VARCHAR(100),
  `notificar_compras` TINYINT(1) DEFAULT 1,
  `notificar_vendas` TINYINT(1) DEFAULT 1,
  `notificar_saque` TINYINT(1) DEFAULT 1,
  `ativo` TINYINT(1) DEFAULT 1,
  `vinculado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_chat` (`chat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------
-- Tabela de Favoritos
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `favoritos` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED NOT NULL,
  `anuncio_id` INT UNSIGNED NOT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`anuncio_id`) REFERENCES `anuncios`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_fav` (`usuario_id`, `anuncio_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------
-- Tabela de Disputas
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `disputas` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `compra_id` INT UNSIGNED NOT NULL,
  `aberto_por` INT UNSIGNED NOT NULL,
  `motivo` TEXT NOT NULL,
  `status` ENUM('aberta','em_analise','resolvida','fechada') DEFAULT 'aberta',
  `resolucao` TEXT,
  `resolvido_por` INT UNSIGNED,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `resolvido_em` DATETIME,
  FOREIGN KEY (`compra_id`) REFERENCES `compras`(`id`),
  FOREIGN KEY (`aberto_por`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------
-- Tabela de Tickets de Suporte
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `suporte_tickets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED NOT NULL,
  `assunto` VARCHAR(255) NOT NULL,
  `mensagem` TEXT NOT NULL,
  `status` ENUM('aberto','em_andamento','respondido','fechado') DEFAULT 'aberto',
  `prioridade` ENUM('baixa','media','alta') DEFAULT 'media',
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------
-- Tabela de Respostas do Suporte
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `suporte_respostas` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED NOT NULL,
  `mensagem` TEXT NOT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `suporte_tickets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------
-- Tabela de Log de Acoes do Admin
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_log` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT UNSIGNED NOT NULL,
  `acao` VARCHAR(100) NOT NULL,
  `descricao` TEXT,
  `tipo` ENUM('aprovacao','reprovacao','usuario','config','financeiro','outro') DEFAULT 'outro',
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`admin_id`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------
-- Tabela de Configuracoes
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `configuracoes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `chave` VARCHAR(100) NOT NULL UNIQUE,
  `valor` TEXT,
  `descricao` VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------
-- Dados Iniciais
-- -------------------------------------------

-- Admin padrao (senha: admin123)
INSERT INTO `usuarios` (`nome`, `sobrenome`, `email`, `senha`, `admin`, `status`) VALUES
('Admin', 'Asgard Store', 'admin@asgard.store', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'ativo');

-- Configuracoes padrao
INSERT INTO `configuracoes` (`chave`, `valor`, `descricao`) VALUES
('comissao_padrao', '10', 'Comissao da plataforma em %'),
('minimo_saque', '30', 'Valor minimo para saque em R$'),
('garantia_horas', '24', 'Horas de garantia apos entrega'),
('whatsapp_suporte', '', 'Numero do WhatsApp do suporte'),
('telegram_suporte', '', '@ do Telegram do suporte'),
('telegram_bot_token', '', 'Token do bot Telegram'),
('site_nome', 'Asgard Store', 'Nome do site'),
('site_descricao', 'Marketplace de contas e creditos de jogos', 'Descricao do site');

-- Jogos padrao
INSERT INTO `jogos` (`nome`, `slug`, `icone`, `moeda_nome`, `moeda_icone`, `ordem`) VALUES
('Free Fire', 'free-fire', 'free-fire.png', 'Diamantes', 'diamantes-ff.png', 1),
('COD Mobile', 'cod-mobile', 'cod-mobile.png', 'CP', 'cp-cod.png', 2),
('Roblox', 'roblox', 'roblox.png', 'Robux', 'robux.png', 3),
('PUBG Mobile', 'pubg-mobile', 'pubg-mobile.png', 'UC', 'uc-pubg.png', 4),
('Mobile Legends', 'mobile-legends', 'mobile-legends.png', 'Diamantes', 'diamantes-ml.png', 5),
('Genshin Impact', 'genshin-impact', 'genshin-impact.png', 'Genesis Crystals', 'genesis.png', 6),
('Valorant', 'valorant', 'valorant.png', 'VP', 'vp-valorant.png', 7),
('League of Legends: Wild Rift', 'wild-rift', 'wild-rift.png', 'Porrins', 'porrins.png', 8);

-- Categorias padrao (Free Fire)
INSERT INTO `categorias` (`jogo_id`, `nome`, `slug`) VALUES
(1, 'Contas com Skin', 'contas-com-skin'),
(1, 'Contas Lendarias', 'contas-lendarias'),
(1, 'Contas Ranking Alto', 'contas-ranking-alto');

-- Creditos padrao (Free Fire)
INSERT INTO `creditos` (`jogo_id`, `nome`, `descricao`, `quantidade`, `moeda_jogo`, `preco_original`, `desconto_percentual`, `preco_final`, `estoque`, `ordem`) VALUES
(1, '110 Diamantes', '100 + 10 bonus', 110, 'Diamantes', 4.49, 10, 4.04, 100, 1),
(1, '341 Diamantes', '310 + 31 bonus', 341, 'Diamantes', 13.99, 10, 12.59, 100, 2),
(1, '572 Diamantes', '520 + 52 bonus', 572, 'Diamantes', 20.99, 10, 18.89, 100, 3),
(1, '1.166 Diamantes', '1060 + 106 bonus', 1166, 'Diamantes', 44.99, 10, 40.49, 100, 4),
(1, '2.398 Diamantes', '2180 + 218 bonus', 2398, 'Diamantes', 87.99, 10, 79.19, 100, 5),
(1, '6.160 Diamantes', '5600 + 560 bonus', 6160, 'Diamantes', 209.99, 10, 188.99, 100, 6);

-- Creditos padrao (COD Mobile)
INSERT INTO `creditos` (`jogo_id`, `nome`, `descricao`, `quantidade`, `moeda_jogo`, `preco_original`, `desconto_percentual`, `preco_final`, `estoque`, `ordem`) VALUES
(2, '30 CP', 'Pacote basico', 30, 'CP', 2.61, 5, 2.48, 100, 1),
(2, '80 CP', 'Pacote medio', 80, 'CP', 5.39, 5, 5.12, 100, 2),
(2, '420 CP', 'Pacote grande', 420, 'CP', 26.99, 5, 25.64, 100, 3),
(2, '880 CP', 'Pacote mega', 880, 'CP', 53.99, 5, 51.29, 100, 4),
(2, '2.400 CP', 'Pacote ultra', 2400, 'CP', 134.99, 5, 128.24, 100, 5),
(2, '5.000 CP', 'Pacote lendario', 5000, 'CP', 269.99, 5, 256.49, 100, 6);

-- Creditos padrao (Roblox)
INSERT INTO `creditos` (`jogo_id`, `nome`, `descricao`, `quantidade`, `moeda_jogo`, `preco_original`, `desconto_percentual`, `preco_final`, `estoque`, `ordem`) VALUES
(3, '80 Robux', 'Pacote iniciante', 80, 'Robux', 5.00, 0, 5.00, 100, 1),
(3, '160 Robux', 'Pacote basico', 160, 'Robux', 9.00, 0, 9.00, 100, 2),
(3, '400 Robux', 'Pacote medio', 400, 'Robux', 20.00, 0, 20.00, 100, 3),
(3, '800 Robux', 'Pacote grande', 800, 'Robux', 38.00, 0, 38.00, 100, 4),
(3, '1.700 Robux', 'Pacote mega', 1700, 'Robux', 75.00, 0, 75.00, 100, 5),
(3, '4.500 Robux', 'Pacote premium', 4500, 'Robux', 195.00, 0, 195.00, 100, 6);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- Tabela de Destaque Premium (Anuncios Pagos)
-- ============================================
CREATE TABLE IF NOT EXISTS `destaques_premium` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `anuncio_id` INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED NOT NULL,
  `valor_pago` DECIMAL(10,2) NOT NULL,
  `duracao_dias` INT NOT NULL DEFAULT 7,
  `data_inicio` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `data_fim` DATETIME NOT NULL,
  `status` ENUM('pendente','ativo','expirado','cancelado') DEFAULT 'pendente',
  `metodo_pagamento` ENUM('pix','crypto','saldo'),
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`anuncio_id`) REFERENCES `anuncios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabela de Redes Sociais do Site
-- ============================================
CREATE TABLE IF NOT EXISTS `redes_sociais` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `icone` VARCHAR(50),
  `cor` VARCHAR(7),
  `url` VARCHAR(255),
  `ativo` TINYINT(1) DEFAULT 1,
  `ordem` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Redes sociais padrao
INSERT INTO `redes_sociais` (`nome`, `slug`, `icone`, `cor`, `url`, `ativo`, `ordem`) VALUES
('Telegram', 'telegram', 'fab fa-telegram', '#0088CC', '#', 1, 1),
('TikTok', 'tiktok', 'fab fa-tiktok', '#000000', '#', 1, 2),
('WhatsApp', 'whatsapp', 'fab fa-whatsapp', '#25D366', '#', 1, 3),
('Discord', 'discord', 'fab fa-discord', '#5865F2', '#', 1, 4),
('Instagram', 'instagram', 'fab fa-instagram', '#E4405F', '#', 1, 5),
('YouTube', 'youtube', 'fab fa-youtube', '#FF0000', '#', 1, 6);

-- Configuracoes de preco de destaque
INSERT INTO `configuracoes` (`chave`, `valor`, `descricao`) VALUES
('destaque_preco_7dias', '9.99', 'Preco do destaque por 7 dias'),
('destaque_preco_14dias', '14.99', 'Preco do destaque por 14 dias'),
('destaque_preco_30dias', '19.99', 'Preco do destaque por 30 dias'),
('redes_sociais_ativas', '1', 'Mostrar redes sociais no site');

-- Colunas de redes sociais do usuario
ALTER TABLE `usuarios`
  ADD COLUMN `telegram_link` VARCHAR(255) DEFAULT NULL AFTER `telegram`,
  ADD COLUMN `whatsapp_link` VARCHAR(255) DEFAULT NULL AFTER `telefone`,
  ADD COLUMN `tiktok_link` VARCHAR(255) DEFAULT NULL AFTER `telegram_link`,
  ADD COLUMN `instagram_link` VARCHAR(255) DEFAULT NULL AFTER `tiktok_link`,
  ADD COLUMN `youtube_link` VARCHAR(255) DEFAULT NULL AFTER `instagram_link`,
  ADD COLUMN `discord_link` VARCHAR(255) DEFAULT NULL AFTER `youtube_link`;
