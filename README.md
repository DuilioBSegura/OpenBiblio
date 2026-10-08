# OPENBIBLIO - Sistema de Gerenciamento para Bibliotecas Comunitárias

**OpenBiblio** é um sistema web completo, moderno e de código aberto projetado para gerenciar acervos bibliográficos, leitores, empréstimos, devoluções e relatórios gerenciais em bibliotecas comunitárias.

Desenvolvido utilizando **PHP 8+ Nativo (com PDO)**, **MySQL/MariaDB** e **Bootstrap 5**, garantindo alta performance, segurança robusta contra vulnerabilidades web e fidelidade às regras de negócio acervísticas.

---

## 🛠️ Tecnologias Utilizadas

- **Backend:** PHP 8.2+ (PHP Nativo puro, sem Laravel ou frameworks pesados), PDO (PHP Data Objects) com Prepared Statements em todas as consultas SQL, Sessões PHP nativas.
- **Banco de Dados:** MySQL 8.0+ / MariaDB 10.4+, Foreign Keys, Índices, Constraints, Transações PDO com isolamento de integridade referencial.
- **Frontend:** HTML5, CSS3 Customizado com Design System OpenBiblio, Bootstrap 5.3, Bootstrap Icons, JavaScript Vanilla (AJAX via Fetch API).

---

## 🚀 Requisitos de Instalação Local (XAMPP / WAMP / MAMP)

- **Servidor Web:** Apache 2.4+
- **PHP:** 8.0 ou superior (com extensão PDO MySQL habilitada)
- **Banco de Dados:** MySQL 5.7+ ou MariaDB 10.4+
- **Navegador Web:** Google Chrome, Mozilla Firefox, Microsoft Edge ou Safari.

---

## 📥 Passo a Passo de Instalação no XAMPP

1. **Baixar ou clonar o projeto:**
   Copie a pasta inteira do projeto para o diretório `htdocs` do seu XAMPP:
   `C:\xampp\htdocs\SistemadeLivrosBiblio`

2. **Iniciar o XAMPP Control Panel:**
   - Inicie o módulo **Apache**.
   - Inicie o módulo **MySQL**.

3. **Configuração do Banco de Dados via phpMyAdmin ou Terminal:**
   - Acesse o phpMyAdmin: `http://localhost/phpmyadmin`
   - Importe a estrutura das tabelas executando o arquivo:
     `database/schema.sql`
   - Importe a carga inicial de dados reais de teste executando o arquivo:
     `database/seeds.sql`

   *(Alternativamente via linha de comando no Terminal do Windows/CMD)*:
   ```cmd
   C:\xampp\mysql\bin\mysql.exe -u root < c:\xampp\htdocs\SistemadeLivrosBiblio\database\schema.sql
   C:\xampp\mysql\bin\mysql.exe -u root openbiblio < c:\xampp\htdocs\SistemadeLivrosBiblio\database\seeds.sql
   ```

4. **Acessar o Sistema no Navegador:**
   Abra no seu navegador a URL:
   👉 **`http://localhost/SistemadeLivrosBiblio`**

5. **Credenciais de Acesso Padrão:**
   - **E-mail:** `admin@openbiblio.local`
   - **Senha:** `Admin@123`

---

## 📂 Estrutura Modular do Projeto

```
openbiblio/
├── config/
│   ├── database.php        # Configuração de conexão PDO centralizada
│   └── auth.php            # Sessões, CSRF, utilitários e autenticação
├── database/
│   ├── schema.sql          # DDL completo do banco de dados (tabelas e FKs)
│   └── seeds.sql           # Carga inicial com dados realistas
├── css/
│   └── style.css           # Design system customizado e estilos responsivos
├── js/
│   └── main.js             # Validações client-side e interações AJAX
├── uploads/
│   └── capas/              # Armazenamento de capas de livros enviadas
├── includes/
│   ├── header.php          # Topbar e cabeçalho HTML reutilizável
│   ├── sidebar.php         # Menu lateral responsivo
│   ├── footer.php          # Rodapé e scripts globais
│   ├── alerts.php          # Renderizador de mensagens flash do Bootstrap
│   └── auth_check.php      # Trava de segurança para páginas privadas
├── auth/
│   ├── login.php           # Tela de autenticação profissional
│   ├── process_login.php   # Validação de senha hashed e criação de sessão
│   └── logout.php          # Destruição segura de sessão
├── index.php               # Dashboard com KPIs em tempo real e gráficos
├── livros.php              # Catálogo, busca parametrizada e paginação
├── livro_cadastrar.php     # Inclusão de obra e geração automática de exemplares
├── livro_editar.php        # Edição de metadados e ajuste dinâmico de estoque
├── livro_excluir.php       # Inativação / Remoção segura respeitando histórico
├── leitores.php            # Listagem de membros e contador de limite de empréstimos
├── leitor_cadastrar.php    # Cadastro de leitores com validação de CPF único
├── leitor_editar.php       # Edição e alternância de status
├── leitor_excluir.php      # Remoção / Inativação de leitor
├── emprestimos.php         # Painel de circulação, prazos e renovações
├── emprestimo_novo.php     # Concessão com trava para limite de 3 empréstimos
├── emprestimo_salvar.php   # Processamento atômico transacional PDO (BEGIN...COMMIT)
├── emprestimo_editar.php   # Renovação de prazo
├── emprestimo_cancelar.php # Cancelamento transacional administrativo
├── devolucoes.php          # Log de auditoria imutável de devoluções
├── devolucao_registrar.php # Baixa transacional com sincronização de estoque
├── relatorios.php          # Relatórios gerenciais parametrizados e impressão
├── perfil.php              # Edição de perfil do operador e troca de senha
├── recibo.php              # Recibo de empréstimo impresso (@media print)
├── api_exemplares.php      # Endpoint AJAX para busca de exemplares
├── README.md               # Documentação técnica do sistema
└── docs/
    └── Manual_Usuario.md   # Manual de utilização passo a passo
```

---

## 🔒 Regras de Negócio Implementadas (RNs)

- **RN-01:** Exemplares indisponíveis não podem ser emprestados.
- **RN-02:** Um exemplar já emprestado fica travado para novos empréstimos.
- **RN-03:** Prazo padrão de concessão é de 14 dias corridos.
- **RN-04:** Cada leitor pode ter no máximo 3 empréstimos ativos simultaneamente.
- **RN-05:** Livros com histórico de empréstimos não podem ser excluídos fisicamente do banco (aplicada inativação lógica).
- **RN-06:** Exemplares emprestados não podem ser removidos do acervo.
- **RN-07:** Devoluções só podem ser registradas para empréstimos ativos.
- **RN-08 & RN-09:** A devolução altera atomicamente o empréstimo, o exemplar, a disponibilidade do livro e grava a devolução. Em caso de falha, executa `ROLLBACK`.
- **RN-10:** Mitigação total de SQL Injection via Prepared Statements em todas as queries.
- **RN-11:** Validação server-side e client-side em todos os formulários.
- **RN-12:** Código ISBN único por livro.
- **RN-13:** CPF do leitor único no sistema.
- **RN-14:** Quantidade disponível nunca fica negativa.
- **RN-15:** Acesso restrito via autenticação e controle de sessões.

---

## 📑 Manual do Usuário

O guia completo de navegação e operação do sistema encontra-se no arquivo:
👉 [`docs/Manual_Usuario.md`](docs/Manual_Usuario.md)
