# Manual do Usuário - OpenBiblio
*Sistema de Gerenciamento para Bibliotecas Comunitárias*

---

## 1. Visão Geral do Sistema

O **OpenBiblio** é um sistema web desenvolvido para automatizar e organizar as rotinas operacionais de bibliotecas comunitárias. Com uma interface limpa, moderna e responsiva, o sistema permite controlar o acervo bibliográfico, os leitores cadastrados, os empréstimos ativos, o envio de recibos e a auditoria histórica de devoluções.

---

## 2. Acesso e Autenticação (Login & Logout)

1. Acesse a URL do sistema no seu navegador: `http://localhost/SistemadeLivrosBiblio`
2. Informe o seu e-mail cadastrado e a senha de acesso:
   - **E-mail:** `admin@openbiblio.local`
   - **Senha:** `Admin@123`
3. Clique em **Entrar no Sistema**.
4. Para encerrar a sessão com segurança, clique na sua foto de perfil no canto superior direito e selecione **Sair do Sistema**.

---

## 3. Painel de Controle (Dashboard)

O Dashboard exibe uma síntese gráfica e numérica do estado da biblioteca em tempo real:
- **Cards de Indicadores:** Total de Livros, Total de Exemplares, Exemplares Disponíveis, Exemplares Emprestados, Leitores Ativos, Empréstimos Realizados Hoje, Devoluções Efetuadas Hoje e Empréstimos Atrasados.
- **Gráfico de Acervo:** Exibe a porcentagem de livros disponíveis vs. emprestados.
- **Livros Mais Procurados:** Ranking das obras com maior volume de retiras.
- **Últimos Empréstimos:** Tabela rápida com acesso aos recibos e botão para dar baixa em devoluções.

---

## 4. Gestão do Acervo Bibliográfico (Livros)

### 4.1 Cadastrar Novo Livro
1. No menu lateral, clique em **Acervo / Livros** e depois no botão **+ Novo Livro**.
2. Preencha os campos obrigatórios (*): **Título** e **Autor**.
3. Preencha os campos opcionais: **ISBN** (deve ser único), **Editora**, **Ano**, **Categoria** e **Descrição**.
4. Informe a **Quantidade de Exemplares Físicos** (ex: 3). O sistema criará automaticamente os códigos de tombamento dos exemplares (`EX-00001-001`, `EX-00001-002`, etc.).
5. (Opcional) Faça o upload da imagem de capa da obra.
6. Clique em **Salvar Livro e Gerar Exemplares**.

### 4.2 Pesquisar e Filtrar Livros
- Utilize o campo de busca na página de livros para pesquisar por título, autor, código ISBN ou ID.
- Utilize o filtro de status para selecionar obras *Disponíveis* ou *Indisponíveis*.

### 4.3 Editar e Ajustar Estoque de Livros
1. Na lista de livros, clique no ícone de lápis azul **(Editar)**.
2. Altere as informações desejadas.
3. Se aumentar a quantidade de exemplares, o sistema gerará os novos tombamentos. Se reduzir, os exemplares não-emprestados serão inativados com segurança.

### 4.4 Remoção / Inativação de Livro (RN-05)
1. Clique no ícone de lixeira vermelha **(Excluir)**.
2. Confirme a ação na modal.
3. **Regra de Proteção (RN-05):** Se a obra possuir histórico prévio de empréstimos, o sistema bloqueará a exclusão física e inativará o livro automaticamente para preservar o histórico da biblioteca.

---

## 5. Gestão de Leitores / Membros

### 5.1 Cadastrar Leitor
1. No menu lateral, acesse **Leitores / Membros** e clique em **+ Novo Leitor**.
2. Preencha o **Nome Completo** (*), **CPF** (único), **E-mail**, **Telefone** e **Endereço**.
3. Clique em **Salvar Leitor**.

### 5.2 Limite de Empréstimos por Leitor (RN-04)
- Na tabela de leitores, o sistema exibe a quantidade de empréstimos ativos (ex: `1/3 Ativos`).
- Se o leitor atingir 3 empréstimos ativos simultâneos, o sistema exibirá o alerta **"Limite Atingido (3/3)"** e bloqueará novos empréstimos até que ele efetue devoluções.

---

## 6. Circulação de Empréstimos

### 6.1 Registrar Novo Empréstimo
1. Clique em **Circulação / Empréstimos** e em **+ Novo Empréstimo**.
2. Selecione o **Leitor**.
3. Selecione a **Obra / Livro**. O sistema filtrará apenas livros que possuam exemplares disponíveis em estoque.
4. Selecione o **Exemplar Físico** específico.
5. Verifique as datas (prazo padrão de 14 dias corridos - RN-03).
6. Clique em **Confirmar e Registrar Empréstimo**. O sistema redirecionará automaticamente para a tela do Recibo.

### 6.2 Identificação de Empréstimos Atrasados
- Empréstimos cujo prazo tenha vencido e o status continue *Ativo* serão destacados em **vermelho** com o selo **VENCIDO / ATRASADO**.

### 6.3 Renovar Empréstimo
1. Na tabela de empréstimos ativos, clique no botão **Renovar**.
2. Selecione a nova data de devolução e clique em **Confirmar Renovação**.

### 6.4 Cancelar Registro Incorreto
1. Se um empréstimo foi lançado por engano, clique no ícone de **Cancelar**.
2. Ao confirmar, o empréstimo passa para o status *Cancelado* e o exemplar retorna imediatamente à disponibilidade física no acervo.

---

## 7. Devolução de Livros e Log Auditável

### 7.1 Registrar Devolução (Baixa de Estoque)
1. Na tabela de empréstimos ativos, clique no botão verde **Devolver**.
2. Informe o **Estado do Exemplar Devolvido** (*Perfeito estado*, *Pequena avaria*, *Danificado*, *Em manutenção*, *Extraviado*).
3. Insira observações opcionais de entrega.
4. Clique em **Dar Baixa e Sincronizar Estoque**.
5. O sistema executará uma transação atômica (`BEGIN TRANSACTION...COMMIT`), alterando o empréstimo para *Concluído*, disponibilizando o livro novamente e salvando o log de devolução.

### 7.2 Histórico Imutável de Devoluções
- Acesse **Histórico de Devoluções** no menu lateral.
- Esta tela exibe o log imutável e auditável de todas as entregas já realizadas na biblioteca.
- Não é permitida a exclusão desses registros para fins de prestação de contas.

---

## 8. Emitir Recibos e Imprimir Relatórios

- **Recibo de Empréstimo:** Acesse qualquer empréstimo e clique em **Recibo**. Clique em **Imprimir Recibo** para gerar a versão física ou PDF formatada.
- **Relatórios:** Acesse **Relatórios e Indicadores**, selecione o tipo (*Livros Mais Emprestados*, *Leitores Mais Ativos*, *Empréstimos Atrasados*, *Devoluções no Período*), defina o intervalo de datas e clique em **Imprimir Relatório**.
