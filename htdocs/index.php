<?php
// Página raiz: manda quem já está logado direto pro dashboard,
// e quem não está pro login.
require_once __DIR__ . '/auth.php';

if (current_user()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
