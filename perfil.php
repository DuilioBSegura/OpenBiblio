<?php
/**
 * OpenBiblio - Perfil de Usuário e Alteração de Senha
 */
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Meu Perfil - OpenBiblio';
$pageHeaderTitle = 'Perfil e Configurações do Operador';

$db = getDBConnection();
$user = getLoggedUser();
$userId = $user['id'];
$errors = [];

// Buscar dados completos do usuário
$stmtUser = $db->prepare("SELECT id, nome, email, perfil, criado_em FROM usuarios WHERE id = ? LIMIT 1");
$stmtUser->execute([$userId]);
$userData = $stmtUser->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('danger', 'Erro CSRF Token.');
        header('Location: perfil.php');
        exit;
    }

    $nome        = trim($_POST['nome'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $senhaAtual  = $_POST['senha_atual'] ?? '';
    $novaSenha   = $_POST['nova_senha'] ?? '';
    $confirmaSenha = $_POST['confirma_senha'] ?? '';

    if (empty($nome)) $errors[] = 'O nome é obrigatório.';
    if (empty($email)) $errors[] = 'O e-mail é obrigatório.';

    // Validação de e-mail único para outros usuários
    $stmtEmailCheck = $db->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ? LIMIT 1");
    $stmtEmailCheck->execute([$email, $userId]);
    if ($stmtEmailCheck->fetch()) {
        $errors[] = 'Este e-mail já está sendo utilizado por outro usuário.';
    }

    // Se informou alteração de senha
    $alterarSenha = !empty($novaSenha);
    if ($alterarSenha) {
        // Buscar a senha hash atual
        $stmtPass = $db->prepare("SELECT senha FROM usuarios WHERE id = ? LIMIT 1");
        $stmtPass->execute([$userId]);
        $hashAtual = $stmtPass->fetchColumn();

        if (!password_verify($senhaAtual, $hashAtual)) {
            $errors[] = 'A senha atual informada está incorreta.';
        }
        if (strlen($novaSenha) < 6) {
            $errors[] = 'A nova senha deve possuir pelo menos 6 caracteres.';
        }
        if ($novaSenha !== $confirmaSenha) {
            $errors[] = 'A confirmação de senha não confere com a nova senha.';
        }
    }

    if (empty($errors)) {
        try {
            if ($alterarSenha) {
                $novoHash = password_hash($novaSenha, PASSWORD_DEFAULT);
                $stmtUpdate = $db->prepare("UPDATE usuarios SET nome = ?, email = ?, senha = ? WHERE id = ?");
                $stmtUpdate->execute([$nome, $email, $novoHash, $userId]);
            } else {
                $stmtUpdate = $db->prepare("UPDATE usuarios SET nome = ?, email = ? WHERE id = ?");
                $stmtUpdate->execute([$nome, $email, $userId]);
            }

            // Atualizar sessão
            $_SESSION['usuario_nome']  = $nome;
            $_SESSION['usuario_email'] = $email;

            auditLog('PERFIL_ATUALIZADO', "Perfil do usuário ID {$userId} atualizado.");
            setFlashMessage('success', 'Seu perfil foi atualizado com sucesso!');
            header('Location: perfil.php');
            exit;
        } catch (Exception $e) {
            error_log("Erro ao atualizar perfil: " . $e->getMessage());
            $errors[] = 'Erro ao atualizar dados: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="font-outfit fw-bold m-0 text-navy">Perfil do Usuário Operador</h2>
        <p class="text-muted m-0">Gerencie suas informações de acesso e credenciais de segurança.</p>
    </div>
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

<div class="row g-4">
    <div class="col-md-4">
        <div class="card-ob text-center p-4">
            <div class="avatar-circle mx-auto mb-3" style="width: 72px; height: 72px; font-size: 2rem;">
                <?= strtoupper(substr($userData['nome'], 0, 1)) ?>
            </div>
            <h4 class="fw-bold text-navy mb-1"><?= htmlspecialchars($userData['nome']) ?></h4>
            <p class="text-muted mb-2"><?= htmlspecialchars($userData['email']) ?></p>
            <span class="badge bg-gold text-dark mb-3 text-capitalize"><?= htmlspecialchars($userData['perfil']) ?></span>
            <hr>
            <small class="text-muted">Membro desde: <?= date('d/m/Y', strtotime($userData['criado_em'])) ?></small>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card-ob">
            <div class="card-ob-header">
                <span><i class="bi bi-person-gear me-2 text-warning"></i> Editar Informações & Alterar Senha</span>
            </div>
            <div class="card-ob-body">
                <form action="perfil.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nome Completo *</label>
                        <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($userData['nome']) ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">E-mail de Acesso *</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($userData['email']) ?>" required>
                    </div>

                    <h5 class="font-outfit fw-bold border-bottom pb-2 mb-3 text-navy">Alteração de Senha (Opcional)</h5>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Senha Atual</label>
                        <input type="password" name="senha_atual" class="form-control" placeholder="Informe somente se for alterar a senha">
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nova Senha</label>
                            <input type="password" name="nova_senha" class="form-control" placeholder="Mínimo de 6 caracteres">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Confirmar Nova Senha</label>
                            <input type="password" name="confirma_senha" class="form-control" placeholder="Repita a nova senha">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <button type="submit" class="btn btn-navy fw-bold"><i class="bi bi-save me-1"></i> Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
