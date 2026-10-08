<?php
/**
 * OpenBiblio - Cadastro de Novo Leitor
 */
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Cadastrar Leitor - OpenBiblio';
$pageHeaderTitle = 'Novo Cadastro de Leitor';

$errors = [];
$nome_completo = '';
$cpf = '';
$email = '';
$telefone = '';
$endereco = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('danger', 'Erro CSRF Token.');
        header('Location: leitores.php');
        exit;
    }

    $nome_completo = trim($_POST['nome_completo'] ?? '');
    $cpf           = trim($_POST['cpf'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $telefone      = trim($_POST['telefone'] ?? '');
    $endereco      = trim($_POST['endereco'] ?? '');

    if (empty($nome_completo)) {
        $errors[] = 'O Nome Completo é obrigatório.';
    }

    $db = getDBConnection();

    // Validar se CPF já existe no banco (RN-13)
    if (!empty($cpf)) {
        $stmtCpf = $db->prepare("SELECT id FROM leitores WHERE cpf = ? AND ativo = 1 LIMIT 1");
        $stmtCpf->execute([$cpf]);
        if ($stmtCpf->fetch()) {
            $errors[] = "O CPF '{$cpf}' já está cadastrado para outro leitor.";
        }
    }

    if (empty($errors)) {
        try {
            $stmtInsert = $db->prepare("
                INSERT INTO leitores (nome_completo, cpf, email, telefone, endereco, ativo)
                VALUES (?, ?, ?, ?, ?, 1)
            ");
            $stmtInsert->execute([
                $nome_completo,
                $cpf ?: null,
                $email ?: null,
                $telefone ?: null,
                $endereco ?: null
            ]);

            $leitorId = $db->lastInsertId();
            auditLog('LEITOR_CADASTRADO', "Leitor '{$nome_completo}' (ID: {$leitorId}) cadastrado.");

            setFlashMessage('success', "Leitor '{$nome_completo}' cadastrado com sucesso!");
            header('Location: leitores.php');
            exit;
        } catch (Exception $e) {
            error_log("Erro ao cadastrar leitor: " . $e->getMessage());
            $errors[] = 'Erro interno no servidor ao tentar salvar o cadastro do leitor.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="font-outfit fw-bold m-0 text-navy">Cadastrar Novo Leitor</h2>
        <p class="text-muted m-0">Preencha os dados cadastrais do tomador da comunidade.</p>
    </div>
    <a href="leitores.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Voltar à Listagem
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card-ob">
    <div class="card-ob-header">
        <span><i class="bi bi-person-plus me-2 text-warning"></i> Formulário de Cadastro do Leitor</span>
    </div>
    <div class="card-ob-body">
        <form action="leitor_cadastrar.php" method="POST" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <div class="row g-3 mb-3">
                <div class="col-md-7">
                    <label for="nome_completo" class="form-label fw-bold">Nome Completo *</label>
                    <input type="text" class="form-control" id="nome_completo" name="nome_completo" value="<?= htmlspecialchars($nome_completo) ?>" placeholder="Ex: Ana Beatriz Campos" required>
                    <div class="invalid-feedback">O nome completo é obrigatório.</div>
                </div>
                <div class="col-md-5">
                    <label for="cpf" class="form-label fw-bold">CPF</label>
                    <input type="text" class="form-control" id="cpf" name="cpf" value="<?= htmlspecialchars($cpf) ?>" placeholder="Ex: 123.456.789-00">
                    <small class="text-muted">CPF único quando informado.</small>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="email" class="form-label fw-bold">E-mail</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="leitor@email.com">
                </div>
                <div class="col-md-6">
                    <label for="telefone" class="form-label fw-bold">Telefone / WhatsApp</label>
                    <input type="text" class="form-control" id="telefone" name="telefone" value="<?= htmlspecialchars($telefone) ?>" placeholder="(11) 98765-4321">
                </div>
            </div>

            <div class="mb-4">
                <label for="endereco" class="form-label fw-bold">Endereço Residencial</label>
                <textarea class="form-control" id="endereco" name="endereco" rows="3" placeholder="Rua, Número, Bairro, Cidade..."><?= htmlspecialchars($endereco) ?></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2 border-top pt-3">
                <a href="leitores.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-gold fw-bold"><i class="bi bi-check-circle me-1"></i> Salvar Leitor</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
