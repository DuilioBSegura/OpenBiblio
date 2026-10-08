-- OpenBiblio - Schema do Banco de Dados MySQL / MariaDB
-- Sistema de Gerenciamento para Bibliotecas Comunitárias

CREATE DATABASE IF NOT EXISTS `openbiblio` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `openbiblio`;

-- Desabilitar verificação de chaves estrangeiras temporariamente para reinstalação limpa
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `logs_sistema`;
DROP TABLE IF EXISTS `devolucoes`;
DROP TABLE IF EXISTS `emprestimos`;
DROP TABLE IF EXISTS `exemplares`;
DROP TABLE IF EXISTS `livros`;
DROP TABLE IF EXISTS `leitores`;
DROP TABLE IF EXISTS `usuarios`;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Tabela: usuarios
-- --------------------------------------------------------
CREATE TABLE `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `senha` VARCHAR(255) NOT NULL,
  `perfil` ENUM('admin', 'bibliotecario') NOT NULL DEFAULT 'bibliotecario',
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_usuarios_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela: leitores
-- --------------------------------------------------------
CREATE TABLE `leitores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome_completo` VARCHAR(150) NOT NULL,
  `cpf` VARCHAR(14) UNIQUE NULL,
  `email` VARCHAR(150) NULL,
  `telefone` VARCHAR(20) NULL,
  `endereco` TEXT NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_leitores_cpf` (`cpf`),
  INDEX `idx_leitores_nome` (`nome_completo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela: livros
-- --------------------------------------------------------
CREATE TABLE `livros` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `titulo` VARCHAR(200) NOT NULL,
  `autor` VARCHAR(150) NOT NULL,
  `isbn` VARCHAR(20) UNIQUE NULL,
  `editora` VARCHAR(100) NULL,
  `ano_publicacao` INT NULL,
  `categoria` VARCHAR(100) NULL,
  `descricao` TEXT NULL,
  `capa` VARCHAR(255) NULL,
  `quantidade_total` INT NOT NULL DEFAULT 0,
  `quantidade_disponivel` INT NOT NULL DEFAULT 0,
  `status` ENUM('Disponível', 'Indisponível') NOT NULL DEFAULT 'Disponível',
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_livros_titulo` (`titulo`),
  INDEX `idx_livros_autor` (`autor`),
  INDEX `idx_livros_isbn` (`isbn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela: exemplares
-- --------------------------------------------------------
CREATE TABLE `exemplares` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `livro_id` INT NOT NULL,
  `codigo_exemplar` VARCHAR(50) NOT NULL UNIQUE,
  `status` ENUM('Disponível', 'Emprestado', 'Manutenção', 'Inativo') NOT NULL DEFAULT 'Disponível',
  `observacoes` TEXT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_exemplares_livro` FOREIGN KEY (`livro_id`) REFERENCES `livros` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX `idx_exemplares_codigo` (`codigo_exemplar`),
  INDEX `idx_exemplares_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela: emprestimos
-- --------------------------------------------------------
CREATE TABLE `emprestimos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `leitor_id` INT NOT NULL,
  `livro_id` INT NOT NULL,
  `exemplar_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `data_emprestimo` DATE NOT NULL,
  `data_prevista_devolucao` DATE NOT NULL,
  `data_cancelamento` DATETIME NULL,
  `status` ENUM('Ativo', 'Concluído', 'Cancelado') NOT NULL DEFAULT 'Ativo',
  `observacoes` TEXT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_emprestimos_leitor` FOREIGN KEY (`leitor_id`) REFERENCES `leitores` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_emprestimos_livro` FOREIGN KEY (`livro_id`) REFERENCES `livros` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_emprestimos_exemplar` FOREIGN KEY (`exemplar_id`) REFERENCES `exemplares` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_emprestimos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX `idx_emprestimos_status` (`status`),
  INDEX `idx_emprestimos_datas` (`data_emprestimo`, `data_prevista_devolucao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela: devolucoes
-- --------------------------------------------------------
CREATE TABLE `devolucoes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `emprestimo_id` INT NOT NULL UNIQUE,
  `usuario_id` INT NOT NULL,
  `data_devolucao_real` DATETIME NOT NULL,
  `estado_exemplar` ENUM('Perfeito estado', 'Pequena avaria', 'Danificado', 'Extraviado', 'Em manutenção') NOT NULL DEFAULT 'Perfeito estado',
  `observacoes` TEXT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_devolucoes_emprestimo` FOREIGN KEY (`emprestimo_id`) REFERENCES `emprestimos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_devolucoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela: logs_sistema
-- --------------------------------------------------------
CREATE TABLE `logs_sistema` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NULL,
  `acao` VARCHAR(100) NOT NULL,
  `detalhes` TEXT NULL,
  `ip` VARCHAR(45) NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_logs_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
