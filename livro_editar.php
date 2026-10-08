<?php
/**
 * OpenBiblio - Edição de Livro Existente
 */
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Editar Livro - OpenBiblio';
$pageHeaderTitle = 'Edição de Cadastro de Livro';

$db = getDBConnection();
$errors = [];

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?? filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    setFlashMessage('danger', 'ID de livro inválido.');
    header('Location: livros.php');
    exit;
}

// Buscar dados atuais do livro
$stmtBook = $db->prepare("SELECT * FROM livros WHERE id = ? AND ativo = 1 LIMIT 1");
$stmtBook->execute([$id]);
$livro = $stmtBook->fetch();

if (!$livro) {
    setFlashMessage('danger', 'Livro não encontrado ou inativo.');
    header('Location: livros.php');
    exit;
}

// Contagem real de exemplares ativos
$stmtEx = $db->prepare("SELECT COUNT(*) FROM exemplares WHERE livro_id = ? AND status != 'Inativo'");
$stmtEx->execute([$id]);
$totalExemplaresReais = (int)$stmtEx->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('danger', 'Erro de validação CSRF.');
        header('Location: livros.php');
        exit;
    }

    $titulo         = trim($_POST['titulo'] ?? '');
    $autor          = trim($_POST['autor'] ?? '');
    $isbn           = trim($_POST['isbn'] ?? '');
    $editora        = trim($_POST['editora'] ?? '');
    $ano_publicacao = filter_input(INPUT_POST, 'ano_publicacao', FILTER_VALIDATE_INT);
    $categoria      = trim($_POST['categoria'] ?? '');
    $descricao      = trim($_POST['descricao'] ?? '');
    $status         = $_POST['status'] ?? 'Disponível';
    $novaQuantidade = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);

    if (empty($titulo)) $errors[] = 'O Título do livro é obrigatório.';
    if (empty($autor)) $errors[] = 'O Autor do livro é obrigatório.';
    if (!$novaQuantidade || $novaQuantidade < 1) $errors[] = 'A quantidade de exemplares deve ser maior ou igual a 1.';

    // Validação de ISBN Único para outros livros
    if (!empty($isbn)) {
        $stmtIsbn = $db->prepare("SELECT id FROM livros WHERE isbn = ? AND id != ? AND ativo = 1 LIMIT 1");
        $stmtIsbn->execute([$isbn, $id]);
        if ($stmtIsbn->fetch()) {
            $errors[] = "O ISBN '{$isbn}' já está em uso por outro livro.";
        }
    }

    // Upload de nova capa opcional
    $nomeCapa = $livro['capa'];
    if (isset($_FILES['capa']) && $_FILES['capa']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['capa']['tmp_name'];
        $fileName = $_FILES['capa']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $uploadFileDir = __DIR__ . '/uploads/capas/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            $nomeCapa = md5(time() . $fileName) . '.' . $fileExtension;
            move_uploaded_file($fileTmpPath, $uploadFileDir . $nomeCapa);
        } else {
            $errors[] = 'Formato de imagem inválido para capa.';
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Ajustar exemplares se a quantidade mudou
            if ($novaQuantidade > $totalExemplaresReais) {
                // Adicionar novos exemplares
                $diferenca = $novaQuantidade - $totalExemplaresReais;
                $stmtInsertEx = $db->prepare("INSERT INTO exemplares (livro_id, codigo_exemplar, status) VALUES (?, ?, 'Disponível')");
                for ($i = 1; $i <= $diferenca; $i++) {
                    $proxNum = $totalExemplaresReais + $i;
                    $codigoEx = sprintf('EX-%05d-%03d', $id, $proxNum);
                    $stmtInsertEx->execute([$id, $codigoEx]);
                }
            } elseif ($novaQuantidade < $totalExemplaresReais) {
                // Reduzir exemplares disponíveis (nunca inativar emprestados)
                $diferenca = $totalExemplaresReais - $novaQuantidade;
                $stmtInativar = $db->prepare("
                    UPDATE exemplares 
                    SET status = 'Inativo' 
                    WHERE livro_id = ? AND status = 'Disponível' 
                    ORDER BY id DESC 
                    LIMIT {$diferenca}
                ");
                $stmtInativar->execute([$id]);
            }

            // Recalcular estoques reais pós-ajuste
            $stmtDispCount = $db->prepare("SELECT COUNT(*) FROM exemplares WHERE livro_id = ? AND status = 'Disponível'");
            $stmtDispCount->execute([$id]);
            $disponiveisAtuais = (int)$stmtDispCount->fetchColumn();

            $stmtTotalCount = $db->prepare("SELECT COUNT(*) FROM exemplares WHERE livro_id = ? AND status != 'Inativo'");
            $stmtTotalCount->execute([$id]);
            $totaisAtuais = (int)$stmtTotalCount->fetchColumn();

            // Atualizar registro principal do livro
            $stmtUpdate = $db->prepare("
                UPDATE livros 
                SET titulo = ?, autor = ?, isbn = ?, editora = ?, ano_publicacao = ?, 
                    categoria = ?, descricao = ?, capa = ?, quantidade_total = ?, 
                    quantidade_disponivel = ?, status = ?
                WHERE id = ?
            ");
            $stmtUpdate->execute([
                $titulo, $autor, $isbn ?: null, $editora ?: null, $ano_publicacao ?: null,
                $categoria ?: null, $descricao ?: null, $nomeCapa, $totaisAtuais,
                $disponiveisAtuais, $status, $id
            ]);

            $db->commit();
            auditLog('LIVRO_EDITADO', "Livro ID {$id} ('{$titulo}') atualizado com sucesso.");

            setFlashMessage('success', "Livro '{$titulo}' atualizado com sucesso!");
            header('Location: livros.php');
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Erro na edição do livro: " . $e->getMessage());
            $errors[] = "Erro ao atualizar livro: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="font-outfit fw-bold m-0 text-navy">Editar Livro #<?= $livro['id'] ?></h2>
        <p class="text-muted m-0">Atualize as informações do acervo.</p>
    </div>
    <a href="livros.php" class="btn btn-outline-secondary">
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
        <form action="livro_editar.php?id=<?= $livro['id'] ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="id" value="<?= $livro['id'] ?>">

            <div class="row g-3 mb-3">
                <div class="col-md-8">
                    <label class="form-label fw-bold">Título *</label>
                    <input type="text" name="titulo" class="form-control" value="<?= htmlspecialchars($livro['titulo']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Autor *</label>
                    <input type="text" name="autor" class="form-control" value="<?= htmlspecialchars($livro['autor']) ?>" required>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">ISBN</label>
                    <input type="text" name="isbn" class="form-control" value="<?= htmlspecialchars($livro['isbn'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Editora</label>
                    <input type="text" name="editora" class="form-control" value="<?= htmlspecialchars($livro['editora'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Ano</label>
                    <input type="number" name="ano_publicacao" class="form-control" value="<?= htmlspecialchars($livro['ano_publicacao'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Status Geral</label>
                    <select name="status" class="form-select">
                        <option value="Disponível" <?= $livro['status'] === 'Disponível' ? 'selected' : '' ?>>Disponível</option>
                        <option value="Indisponível" <?= $livro['status'] === 'Indisponível' ? 'selected' : '' ?>>Indisponível</option>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Categoria</label>
                    <input type="text" name="categoria" class="form-control" value="<?= htmlspecialchars($livro['categoria'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Quantidade de Exemplares</label>
                    <input type="number" name="quantidade" class="form-control" value="<?= $totalExemplaresReais ?>" min="1">
                    <small class="text-muted">Ajusta automaticamente os exemplares físicos.</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Alterar Capa</label>
                    <input type="file" name="capa" class="form-control" accept="image/*">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Descrição</label>
                <textarea name="descricao" class="form-control" rows="4"><?= htmlspecialchars($livro['descricao'] ?? '') ?></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2 border-top pt-3">
                <a href="livros.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-navy fw-bold">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
