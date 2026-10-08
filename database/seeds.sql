-- OpenBiblio - Dados Iniciais (Seeds)
USE `openbiblio`;

-- --------------------------------------------------------
-- 1. Usuário Administrador Inicial
-- Email: admin@openbiblio.local | Senha: Admin@123
-- --------------------------------------------------------
INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `perfil`, `ativo`) VALUES
(1, 'Administrador OpenBiblio', 'admin@openbiblio.local', '$2y$10$zSnx.Camj1jvcrnJaWfoJuUwO9rE48z1wKqSw9IvzMXhrhe3Sz6Hy', 'admin', 1);

-- --------------------------------------------------------
-- 2. Leitores (10 Leitores Reais)
-- --------------------------------------------------------
INSERT INTO `leitores` (`id`, `nome_completo`, `cpf`, `email`, `telefone`, `endereco`, `ativo`) VALUES
(1, 'Ana Beatriz Malachias de Campos', '123.456.789-00', 'ana.campos@email.com', '(11) 98765-4321', 'Rua das Flores, 123 - Centro', 1),
(2, 'Duílio Brito Segura', '234.567.890-11', 'duilio.brito@email.com', '(11) 97654-3210', 'Av. Paulista, 1000 - Bela Vista', 1),
(3, 'Carlos Eduardo Santos', '345.678.901-22', 'carlos.santos@email.com', '(19) 98111-2233', 'Rua XV de Novembro, 45 - Cambuí', 1),
(4, 'Mariana Oliveira Lima', '456.789.012-33', 'mariana.lima@email.com', '(19) 98222-3344', 'Rua Barão de Jaguara, 500 - Centro', 1),
(5, 'Lucas Pereira da Silva', '567.890.123-44', 'lucas.silva@email.com', '(11) 99333-4455', 'Rua Augusta, 750 - Consolação', 1),
(6, 'Fernanda Costa Rodrigues', '678.901.234-55', 'fernanda.costa@email.com', '(21) 99444-5566', 'Av. Atlântica, 200 - Copacabana', 1),
(7, 'Gabriel Souza Alves', '789.012.345-66', 'gabriel.alves@email.com', '(31) 99555-6677', 'Rua da Bahia, 300 - Lourdes', 1),
(8, 'Beatriz Mendes Rocha', '890.123.456-77', 'beatriz.rocha@email.com', '(41) 99666-7788', 'Rua das Palmeiras, 88 - Batel', 1),
(9, 'Rafael Barbosa Martins', '901.234.567-88', 'rafael.martins@email.com', '(51) 99777-8899', 'Av. Ipiranga, 450 - Praia de Belas', 1),
(10, 'Juliana Carvalho Fernandes', '012.345.678-99', 'juliana.fernandes@email.com', '(81) 99888-9900', 'Rua do Sol, 12 - Boa Vista', 1);

-- --------------------------------------------------------
-- 3. Livros (10 Obras de Referência)
-- --------------------------------------------------------
INSERT INTO `livros` (`id`, `titulo`, `autor`, `isbn`, `editora`, `ano_publicacao`, `categoria`, `descricao`, `capa`, `quantidade_total`, `quantidade_disponivel`, `status`, `ativo`) VALUES
(1, '1984', 'George Orwell', '978-85-359-1002-3', 'Companhia das Letras', 1949, 'Ficção Científica / Distopia', 'Romance distópico clássico que trata do totalitarismo, vigilância governamental e manipulação da verdade.', 'capa_1984.jpg', 3, 1, 'Disponível', 1),
(2, 'Dom Casmurro', 'Machado de Assis', '978-85-7232-342-0', 'Garnier', 1899, 'Literatura Brasileira', 'Um dos maiores clássicos da literatura brasileira, onde Bentinho narra sua história e sua dúvida sobre Capitu.', 'capa_dom_casmurro.jpg', 3, 2, 'Disponível', 1),
(3, 'Dom Quixote', 'Miguel de Cervantes', '978-85-359-0234-5', 'Penguin', 1605, 'Clássicos Universais', 'As aventuras do fidalgo Dom Quixote de La Mancha e seu fiel escudeiro Sancho Pança.', 'capa_dom_quixote.jpg', 2, 0, 'Disponível', 1),
(4, 'O Cortiço', 'Aluísio Azevedo', '978-85-08-04021-3', 'Ática', 1890, 'Naturalismo Brasileiro', 'Obra representativa do Naturalismo brasileiro que descreve a vida em um habitação coletiva no Rio de Janeiro.', 'capa_o_cortico.jpg', 2, 2, 'Disponível', 1),
(5, 'A Culpa é das Estrelas', 'John Green', '978-85-8057-226-1', 'Intrínseca', 2012, 'Romance Juvenil', 'A história emocionante de Hazel Grace e Augustus Waters, dois adolescentes que se conhecem em um grupo de apoio.', 'capa_culpa_estrelas.jpg', 2, 1, 'Disponível', 1),
(6, 'Memórias Póstumas de Brás Cubas', 'Machado de Assis', '978-85-359-2475-4', 'Companhia das Letras', 1881, 'Realismo Brasileiro', 'Narrado por um defunto autor, o livro inova na forma e na crítica social da sociedade brasileira do século XIX.', 'capa_bras_cubas.jpg', 2, 2, 'Disponível', 1),
(7, 'O Alquimista', 'Paulo Coelho', '978-85-7542-757-5', 'Sextante', 1988, 'Ficção / Filosofia', 'A jornada de Santiago, um jovem pastor andaluz que viaja em busca de um tesouro nas pirâmides do Egito.', 'capa_o_alquimista.jpg', 2, 2, 'Disponível', 1),
(8, 'Capitães da Areia', 'Jorge Amado', '978-85-359-1406-9', 'Companhia das Letras', 1937, 'Literatura Brasileira', 'A vida de um grupo de menores abandonados que vivem nos trapiches de Salvador.', 'capa_capitaes_areia.jpg', 2, 1, 'Disponível', 1),
(9, 'Grande Sertão: Veredas', 'João Guimarães Rosa', '978-85-209-2325-2', 'Nova Fronteira', 1956, 'Modernismo Brasileiro', 'Riobaldo narra sua vida como jagunço no sertão mineiro e suas reflexões existenciais e amorosas.', 'capa_grande_sertao.jpg', 2, 2, 'Disponível', 1),
(10, 'Ensaio sobre a Cegueira', 'José Saramago', '978-85-359-0021-1', 'Companhia das Letras', 1995, 'Ficção Contemporânea', 'Uma epidemia de cegueira branca se espalha por uma cidade não identificada, revelando o lado mais primitivo da humanidade.', 'capa_ensaio_cegueira.jpg', 2, 1, 'Disponível', 1);

-- --------------------------------------------------------
-- 4. Exemplares Físicos (22 Exemplares)
-- --------------------------------------------------------
INSERT INTO `exemplares` (`id`, `livro_id`, `codigo_exemplar`, `status`, `observacoes`) VALUES
-- 1984 (Livro 1)
(1, 1, 'EX-1984-001', 'Emprestado', 'Exemplar em ótimo estado'),
(2, 1, 'EX-1984-002', 'Emprestado', 'Encadernação renovada'),
(3, 1, 'EX-1984-003', 'Disponível', 'Novo exemplar'),

-- Dom Casmurro (Livro 2)
(4, 2, 'EX-DOMC-001', 'Emprestado', 'Pequeno desgaste na capa'),
(5, 2, 'EX-DOMC-002', 'Disponível', 'Em perfeito estado'),
(6, 2, 'EX-DOMC-003', 'Disponível', 'Em perfeito estado'),

-- Dom Quixote (Livro 3)
(7, 3, 'EX-QUIX-001', 'Emprestado', 'Edição ilustrada'),
(8, 3, 'EX-QUIX-002', 'Emprestado', 'Volume duplo'),

-- O Cortiço (Livro 4)
(9, 4, 'EX-CORT-001', 'Disponível', 'Excelente conservação'),
(10, 4, 'EX-CORT-002', 'Disponível', 'Capa dura'),

-- A Culpa é das Estrelas (Livro 5)
(11, 5, 'EX-CULP-001', 'Emprestado', 'Leve marca amarela nas páginas'),
(12, 5, 'EX-CULP-002', 'Disponível', 'Em ótimo estado'),

-- Memórias Póstumas (Livro 6)
(13, 6, 'EX-BRAS-001', 'Disponível', 'Edição escolar comentada'),
(14, 6, 'EX-BRAS-002', 'Disponível', 'Perfeito estado'),

-- O Alquimista (Livro 7)
(15, 7, 'EX-ALQU-001', 'Disponível', 'Edição comemorativa'),
(16, 7, 'EX-ALQU-002', 'Disponível', 'Em ótimo estado'),

-- Capitães da Areia (Livro 8)
(17, 8, 'EX-CAPI-001', 'Emprestado', 'Páginas amareladas'),
(18, 8, 'EX-CAPI-002', 'Disponível', 'Em perfeito estado'),

-- Grande Sertão: Veredas (Livro 9)
(19, 9, 'EX-SERT-001', 'Disponível', 'Edição definitiva'),
(20, 9, 'EX-SERT-002', 'Disponível', 'Em ótimo estado'),

-- Ensaio sobre a Cegueira (Livro 10)
(21, 10, 'EX-CEGU-001', 'Emprestado', 'Capa brochura'),
(22, 10, 'EX-CEGU-002', 'Disponível', 'Perfeito estado');

-- --------------------------------------------------------
-- 5. Empréstimos (Ativos, Concluídos, Atrasados, Cancelados)
-- --------------------------------------------------------
INSERT INTO `emprestimos` (`id`, `leitor_id`, `livro_id`, `exemplar_id`, `usuario_id`, `data_emprestimo`, `data_prevista_devolucao`, `data_cancelamento`, `status`, `observacoes`) VALUES
-- Empréstimo 1: Ativo e Dentro do Prazo (Ana Beatriz)
(1, 1, 1, 1, 1, '2026-09-20', '2026-10-04', NULL, 'Ativo', 'Retirada regular na recepção'),

-- Empréstimo 2: ATRASADO (Ana Beatriz) - Prazo vencido em 2026-09-10
(2, 1, 1, 2, 1, '2026-08-27', '2026-09-10', NULL, 'Ativo', 'Leitora notificada por e-mail'),

-- Empréstimo 3: Ativo (Ana Beatriz - 3º Empréstimo para atingir o limite 3/3)
(3, 1, 5, 11, 1, '2026-09-15', '2026-09-29', NULL, 'Ativo', 'Terceiro livro retirado por Ana Beatriz'),

-- Empréstimo 4: ATRASADO (Duílio) - Prazo vencido em 2026-09-15
(4, 2, 3, 7, 1, '2026-09-01', '2026-09-15', NULL, 'Ativo', 'Solicitou prorrogação por WhatsApp'),

-- Empréstimo 5: Ativo (Carlos Eduardo)
(5, 3, 2, 4, 1, '2026-09-18', '2026-10-02', NULL, 'Ativo', 'Leitor frequente'),

-- Empréstimo 6: Ativo (Mariana)
(6, 4, 3, 8, 1, '2026-09-22', '2026-10-06', NULL, 'Ativo', 'Retirado no turno da tarde'),

-- Empréstimo 7: Ativo (Fernanda)
(7, 6, 8, 17, 1, '2026-09-24', '2026-10-08', NULL, 'Ativo', 'Retirado hoje'),

-- Empréstimo 8: Ativo (Gabriel)
(8, 7, 10, 21, 1, '2026-09-24', '2026-10-08', NULL, 'Ativo', 'Retirado hoje'),

-- Empréstimo 9: Concluído (Beatriz)
(9, 8, 4, 9, 1, '2026-08-10', '2026-08-24', NULL, 'Concluído', 'Devolvido no prazo'),

-- Empréstimo 10: Concluído (Rafael)
(10, 9, 6, 13, 1, '2026-08-15', '2026-08-29', NULL, 'Concluído', 'Devolvido em perfeito estado'),

-- Empréstimo 11: Cancelado (Juliana)
(11, 10, 7, 15, 1, '2026-09-05', '2026-09-19', '2026-09-05 14:30:00', 'Cancelado', 'Registro duplicado por engano');

-- --------------------------------------------------------
-- 6. Devoluções (Registros de Auditoria dos Empréstimos Concluídos)
-- --------------------------------------------------------
INSERT INTO `devolucoes` (`id`, `emprestimo_id`, `usuario_id`, `data_devolucao_real`, `estado_exemplar`, `observacoes`) VALUES
(1, 9, 1, '2026-08-22 10:15:00', 'Perfeito estado', 'Devolução realizada dentro do prazo. Nenhuma avaria.'),
(2, 10, 1, '2026-08-28 16:45:00', 'Perfeito estado', 'Livro devolvido com capa em ótimo estado.');

-- --------------------------------------------------------
-- 7. Logs de Sistema
-- --------------------------------------------------------
INSERT INTO `logs_sistema` (`usuario_id`, `acao`, `detalhes`, `ip`) VALUES
(1, 'SISTEMA_INICIALIZADO', 'Carga de dados iniciais (seeds) realizada com sucesso.', '127.0.0.1');
