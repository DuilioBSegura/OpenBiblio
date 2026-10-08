<?php
/**
 * OpenBiblio - Rodapé (Footer)
 */
$subPath = getAppSubPath();
?>
        </main> <!-- /main-content -->
        
        <footer class="text-center py-3 border-top bg-white text-muted" style="font-size: 0.85rem;">
            <div class="container-fluid">
                <strong>OpenBiblio</strong> &copy; <?= date('Y') ?> - Sistema de Gerenciamento para Bibliotecas Comunitárias.
            </div>
        </footer>
    </div> <!-- /content -->
</div> <!-- /wrapper -->

<!-- Bootstrap 5 Bundle JS com Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Script Principal OpenBiblio -->
<script src="<?= $subPath ?>/js/main.js"></script>
</body>
</html>
