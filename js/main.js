/**
 * OpenBiblio - Scripts JavaScript Vanilla
 * Sistema de Gerenciamento para Bibliotecas Comunitárias
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Toggle Sidebar em telas menores
    const sidebarToggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');

    if (sidebarToggleBtn && sidebar) {
        sidebarToggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('active');
        });
    }

    // 2. Auto-close de Alertas Bootstrap após 6 segundos
    const alertList = document.querySelectorAll('.alert-dismissible');
    alertList.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 6000);
    });

    // 3. Validação de Formulários Bootstrap Client-side
    const forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // 4. Carregamento dinâmico de exemplares disponíveis ao escolher um livro (empréstimo_novo.php)
    const selectLivro = document.getElementById('selectLivroEmprestimo');
    const selectExemplar = document.getElementById('selectExemplarEmprestimo');

    if (selectLivro && selectExemplar) {
        selectLivro.addEventListener('change', function () {
            const livroId = this.value;
            selectExemplar.innerHTML = '<option value="">Carregando exemplares disponíveis...</option>';
            selectExemplar.disabled = true;

            if (!livroId) {
                selectExemplar.innerHTML = '<option value="">Selecione primeiro um livro...</option>';
                return;
            }

            // Buscar exemplares disponíveis via AJAX / Fetch
            fetch('api_exemplares.php?livro_id=' + livroId)
                .then(response => response.json())
                .then(data => {
                    selectExemplar.innerHTML = '';
                    if (data.status === 'success' && data.exemplares.length > 0) {
                        selectExemplar.innerHTML = '<option value="">-- Selecione o Exemplar --</option>';
                        data.exemplares.forEach(ex => {
                            const option = document.createElement('option');
                            option.value = ex.id;
                            option.textContent = ex.codigo_exemplar + ' (' + ex.status + ')';
                            selectExemplar.appendChild(option);
                        });
                        selectExemplar.disabled = false;
                    } else {
                        selectExemplar.innerHTML = '<option value="">Nenhum exemplar disponível para este livro</option>';
                    }
                })
                .catch(err => {
                    console.error('Erro ao buscar exemplares:', err);
                    selectExemplar.innerHTML = '<option value="">Erro ao carregar exemplares</option>';
                });
        });
    }

    // 5. Verificação de limite do leitor (empréstimo_novo.php)
    const selectLeitor = document.getElementById('selectLeitorEmprestimo');
    const leitorBadge = document.getElementById('leitorStatusBadge');
    const btnSubmitEmprestimo = document.getElementById('btnSubmitEmprestimo');

    if (selectLeitor && leitorBadge) {
        selectLeitor.addEventListener('change', function () {
            const option = this.options[this.selectedIndex];
            const ativos = parseInt(option.getAttribute('data-ativos') || '0', 10);

            if (!this.value) {
                leitorBadge.className = 'badge bg-secondary';
                leitorBadge.textContent = 'Empréstimos ativos: 0/3';
                if (btnSubmitEmprestimo) btnSubmitEmprestimo.disabled = false;
                return;
            }

            leitorBadge.textContent = `Empréstimos ativos: ${ativos}/3`;
            if (ativos >= 3) {
                leitorBadge.className = 'badge bg-danger fs-6 ms-2';
                leitorBadge.textContent = `Limite atingido (${ativos}/3)!`;
                if (btnSubmitEmprestimo) btnSubmitEmprestimo.disabled = true;
            } else if (ativos === 2) {
                leitorBadge.className = 'badge bg-warning text-dark fs-6 ms-2';
                if (btnSubmitEmprestimo) btnSubmitEmprestimo.disabled = false;
            } else {
                leitorBadge.className = 'badge bg-success fs-6 ms-2';
                if (btnSubmitEmprestimo) btnSubmitEmprestimo.disabled = false;
            }
        });
    }
});
