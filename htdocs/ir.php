<?php
// Rota de redirecionamento do link de afiliado.
// Aponte o botão do dashboard para "ir.php" — ele registra o clique (opcional)
// e manda o usuário direto pro link de afiliado configurado em config.php.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$usuarioId = $_SESSION['usuario_id'] ?? null;
try {
    db()->prepare('INSERT INTO afiliado_cliques (usuario_id, ip) VALUES (?, ?)')
        ->execute([$usuarioId, $_SERVER['REMOTE_ADDR'] ?? null]);
} catch (Throwable $e) {
    // não deixa um erro de log travar o redirecionamento
}

header('Location: ' . AFILIADO_URL, true, 302);
exit;
