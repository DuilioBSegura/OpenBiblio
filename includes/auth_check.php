<?php
/**
 * OpenBiblio - Verificação Obrigatória de Autenticação
 */

require_once __DIR__ . '/../config/auth.php';

// Exige que o usuário esteja logado
requireLogin();
