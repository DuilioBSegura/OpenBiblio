<?php
/**
 * OpenBiblio - Cadastro de Novo Livro
 */
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Cadastrar Livro - OpenBiblio';
$pageHeaderTitle = 'Novo Cadastro de Livro';

$errors = [];
$titulo = '';
$autor = '';
$isbn = '';
$editora = '';
$ano_publicacao = date('Y');
$categoria = '';
$descricao = '';
$quantidade = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('danger', 'Erro de validação de segurança (CSRF token inválido).');
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
    $quantidade     = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);

    // Validações Server-side
    if (empty($titulo)) {
        $errors[] = 'O Título do livro é obrigatório.';
    }
    if (empty($autor)) {
        $errors[] = 'O Autor do livro é obrigatório.';
    }
    if (!$quantidade || $quantidade < 1) {
        $errors[] = 'A quantidade de exemplares deve ser de pelo menos 1.';
    }
    if ($ano_publicacao && ($ano_publicacao < 1000 || $ano_publicacao > (date('Y') + 1))) {
        $errors[] = 'Informe um ano de publicação válido.';
    }

    $db = getDBConnection();

    // Validar ISBN único se informado
    if (!empty($isbn)) {
        $stmtCheck = $db->prepare("SELECT id FROM livros WHERE isbn = ? AND ativo = 1 LIMIT 1");
        $stmtCheck->execute([$isbn]);
        if ($stmtCheck->fetch()) {
            $errors[] = "O ISBN '{$isbn}' já está cadastrado em outro livro do acervo.";
        }
    }

    // Processamento de Upload de Capa Opcional com Validação Rígida
    $nomeCapa = null;
    if (isset($_FILES['capa']) && $_FILES['capa']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath   = $_FILES['capa']['tmp_name'];
        $fileName      = $_FILES['capa']['name'];
        $fileSize      = $_FILES['capa']['size'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $allowedMimes      = ['image/jpeg', 'image/png', 'image/webp'];

        if ($fileSize > 2 * 1024 * 1024) {
            $errors[] = 'A imagem da capa não pode ultrapassar 2MB.';
        } elseif (!in_array($fileExtension, $allowedExtensions)) {
            $errors[] = 'Formato de imagem inválido para a capa. Utilize JPG, PNG ou WEBP.';
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $fileTmpPath);
            finfo_close($finfo);

            $imageInfo = @getimagesize($fileTmpPath);

            if (!in_array($mimeType, $allowedMimes) || $imageInfo === false) {
                $errors[] = 'O arquivo enviado não é uma imagem válida.';
            } else {
                $uploadFileDir = __DIR__ . '/uploads/capas/';
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }
                $nomeCapa = bin2hex(random_bytes(16)) . '.' . $fileExtension;
                $dest_path = $uploadFileDir . $nomeCapa;
                move_uploaded_file($fileTmpPath, $dest_path);
            }
        }
    }

    // Se não houver erros, insere livro e exemplares em Transação PDO
    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Inserir livro
            $stmtInsertBook = $db->prepare("
                INSERT INTO livros (titulo, autor, isbn, editora, ano_publicacao, categoria, descricao, capa, quantidade_total, quantidade_disponivel, status, ativo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Disponível', 1)
            ");
            $stmtInsertBook->execute([
                $titulo,
                $autor,
                $isbn ?: null,
                $editora ?: null,
                $ano_publicacao ?: null,
                $categoria ?: null,
                $descricao ?: null,
                $nomeCapa,
                $quantidade,
                $quantidade // Inicialmente todos estão disponíveis
            ]);
            $livroId = $db->lastInsertId();

            // Inserir exemplares automaticamente
            $stmtInsertExemplar = $db->prepare("
                INSERT INTO exemplares (livro_id, codigo_exemplar, status, observacoes)
                VALUES (?, ?, 'Disponível', 'Exemplar criado no cadastro inicial do livro')
            ");

            for ($i = 1; $i <= $quantidade; $i++) {
                $codigoExemplar = sprintf('EX-%05d-%03d', $livroId, $i);
                $stmtInsertExemplar->execute([$livroId, $codigoExemplar]);
            }

            $db->commit();
            auditLog('LIVRO_CADASTRADO', "Livro '{$titulo}' (ID: {$livroId}) cadastrado com {$quantidade} exemplares.");

            setFlashMessage('success', "Livro cadastrado com sucesso! {$quantidade} exemplares gerados automaticamente.");
            header('Location: livros.php');
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Erro ao cadastrar livro: " . $e->getMessage());
            $errors[] = 'Erro interno no servidor ao tentar salvar o livro.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="font-outfit fw-bold m-0 text-navy">Cadastrar Novo Livro</h2>
        <p class="text-muted m-0">Preencha as informações descritivas e a quantidade de exemplares para o acervo.</p>
    </div>
    <a href="livros.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Voltar à Listagem
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <h5 class="alert-heading font-outfit fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i> Atenção: Corrija os erros abaixo</h5>
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card-ob">
    <div class="card-ob-header">
        <span><i class="bi bi-journal-plus me-2 text-warning"></i> Formulario de Inclusão de Obra</span>
    </div>
    <div class="card-ob-body">
        <form action="livro_cadastrar.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <div class="row g-3 mb-3">
                <div class="col-md-8">
                    <label for="titulo" class="form-label fw-bold">Título do Livro *</label>
                    <input type="text" class="form-control" id="titulo" name="titulo" value="<?= htmlspecialchars($titulo) ?>" placeholder="Ex: 1984" required>
                    <div class="invalid-feedback">O título é obrigatório.</div>
                </div>

                <div class="col-md-4">
                    <label for="autor" class="form-label fw-bold">Autor *</label>
                    <input type="text" class="form-control" id="autor" name="autor" value="<?= htmlspecialchars($autor) ?>" placeholder="Ex: George Orwell" required>
                    <div class="invalid-feedback">O autor é obrigatório.</div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="isbn" class="form-label fw-bold">Código ISBN</label>
                    <input type="text" class="form-control" id="isbn" name="isbn" value="<?= htmlspecialchars($isbn) ?>" placeholder="Ex: 978-85-359-1002-3">
                    <small class="text-muted">Informe se houver. O ISBN deve ser único.</small>
                </div>

                <div class="col-md-4">
                    <label for="editora" class="form-label fw-bold">Editora</label>
                    <input type="text" class="form-control" id="editora" name="editora" value="<?= htmlspecialchars($editora) ?>" placeholder="Ex: Companhia das Letras">
                </div>

                <div class="col-md-4">
                    <label for="ano_publicacao" class="form-label fw-bold">Ano de Publicação</label>
                    <input type="number" class="form-control" id="ano_publicacao" name="ano_publicacao" value="<?= htmlspecialchars($ano_publicacao) ?>" min="1000" max="<?= date('Y') + 1 ?>">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="categoria" class="form-label fw-bold">Categoria / Gênero</label>
                    <input type="text" class="form-control" id="categoria" name="categoria" value="<?= htmlspecialchars($categoria) ?>" placeholder="Ex: Ficção Científica, Romance, História">
                </div>

                <div class="col-md-4">
                    <label for="quantidade" class="form-label fw-bold">Quantidade de Exemplares Físicos *</label>
                    <input type="number" class="form-control" id="quantidade" name="quantidade" value="<?= htmlspecialchars($quantidade) ?>" min="1" max="100" required>
                    <small class="text-muted">Os códigos dos exemplares serão gerados automaticamente.</small>
                </div>

                <div class="col-md-4">
                    <label for="capa" class="form-label fw-bold">Imagem da Capa (Opcional)</label>
                    <input type="file" class="form-control" id="capa" name="capa" accept="image/jpeg,image/png,image/webp">
                </div>
            </div>

            <div class="mb-4">
                <label for="descricao" class="form-label fw-bold">Descrição / Sinopse</label>
                <textarea class="form-control" id="descricao" name="descricao" rows="4" placeholder="Insira a sinopse ou observações sobre a obra..."><?= htmlspecialchars($descricao) ?></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2 border-top pt-3">
                <a href="livros.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-gold fw-bold">
                    <i class="bi bi-check-circle me-1"></i> Salvar Livro e Gerar Exemplares
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
