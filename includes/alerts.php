<?php
/**
 * OpenBiblio - Renderizador de Alertas de Feedback
 */

require_once __DIR__ . '/../config/auth.php';

$flashMessages = getFlashMessages();

if (!empty($flashMessages)):
?>
    <div class="alerts-container mb-3">
        <?php foreach ($flashMessages as $msg): 
            $type = $msg['type'] ?? 'info';
            $icon = match($type) {
                'success' => 'bi-check-circle-fill',
                'danger'  => 'bi-exclamation-triangle-fill',
                'warning' => 'bi-exclamation-circle-fill',
                default   => 'bi-info-circle-fill'
            };
        ?>
            <div class="alert alert-<?= htmlspecialchars($type) ?> alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
                <i class="bi <?= $icon ?> fs-5 me-2"></i>
                <div>
                    <?= htmlspecialchars($msg['message']) ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
