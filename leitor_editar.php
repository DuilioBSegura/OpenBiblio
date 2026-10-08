<?php
/**
 * OpenBiblio - Edição de Leitor
 */
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Editar Leitor - OpenBiblio';
$pageHeaderTitle = 'Edição de Leitor';

$db = getDBConnection();
$errors = [];

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?? filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    setFlashMessage('danger', 'ID de leitor inválido.');
    header('Location: leitores.php');
    exit;
}

$stmtLeitor = $db->prepare("SELECT * FROM leitores WHERE id = ? LIMIT 1");
$stmtLeitor->execute([$id]);
$leitor = $stmtLeitor->fetch();

if (!$leitor) {
    setFlashMessage('danger', 'Leitor não encontrado.');
    header('Location: leitores.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('danger', 'Erro CSRF.');
        header('Location: leitores.php');
        exit;
    }

    $nome_completo = trim($_POST['nome_completo'] ?? '');
    $cpf           = trim($_POST['cpf'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $telefone      = trim($_POST['telefone'] ?? '');
    $endereco      = trim($_POST['endereco'] ?? '');
    $ativo         = isset($_POST['ativo']) ? 1 : 0;

    if (empty($nome_completo)) $errors[] = 'O Nome Completo é obrigatório.';

    if (!empty($cpf)) {
        $stmtCpf = $db->prepare("SELECT id FROM leitores WHERE cpf = ? AND id != ? LIMIT 1");
        $stmtCpf->execute([$cpf, $id]);
        if ($stmtCpf->fetch()) {
            $errors[] = "O CPF '{$cpf}' já está cadastrado para outro leitor.";
        }
    }

    if (empty($errors)) {
        try {
            $stmtUpdate = $db->prepare("
                UPDATE leitores 
                SET nome_completo = ?, cpf = ?, email = ?, telefone = ?, endereco = ?, ativo = ?
                WHERE id = ?
            ");
            $stmtUpdate->execute([
                $nome_completo,
                $cpf ?: null,
                $email ?: null,
                $telefone ?: null,
                $endereco ?: null,
                $ativo,
                $id
            ]);

            auditLog('LEITOR_EDITADO', "Leitor ID {$id} ('{$nome_completo}') atualizado.");
            setFlashMessage('success', "Cadastro de '{$nome_completo}' atualizado com sucesso!");
            header('Location: leitores.php');
            exit;
        } catch (Exception $e) {
            error_log("Erro na edição de leitor: " . $e->getMessage());
            $errors[] = "Erro interno no servidor ao tentar atualizar os dados do leitor.";
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="font-outfit fw-bold m-0 text-navy">Editar Leitor #<?= $leitor['id'] ?></h2>
        <p class="text-muted m-0">Atualize as informações do tomador.</p>
    </div>
    <a href="leitores.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Voltar
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
        <span><i class="bi bi-pencil-square me-2 text-warning"></i> Formulario de Edição</span>
    </div>
    <div class="card-ob-body">
        <form action="leitor_editar.php?id=<?= $leitor['id'] ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="id" value="<?= $leitor['id'] ?>">

            <div class="row g-3 mb-3">
                <div class="col-md-7">
                    <label class="form-label fw-bold">Nome Completo *</label>
                    <input type="text" name="nome_completo" class="form-control" value="<?= htmlspecialchars($leitor['nome_completo']) ?>" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-bold">CPF</label>
                    <input type="text" name="cpf" class="form-control" value="<?= htmlspecialchars($leitor['cpf'] ?? '') ?>">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">E-mail</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($leitor['email'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Telefone</label>
                    <input type="text" name="telefone" class="form-control" value="<?= htmlspecialchars($leitor['telefone'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Endereço Residencial</label>
                <textarea name="endereco" class="form-control" rows="3"><?= htmlspecialchars($leitor['endereco'] ?? '') ?></textarea>
            </div>

            <div class="mb-4 form-check form-switch">
                <input class="form-check-input" type="checkbox" name="ativo" id="ativo" value="1" <?= (int)$leitor['ativo'] === 1 ? 'checked' : '' ?>>
                <label class="form-check-label fw-bold" for="ativo">Leitor Ativo no Sistema</label>
            </div>

            <div class="d-flex justify-content-end gap-2 border-top pt-3">
                <a href="leitores.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-navy fw-bold">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
