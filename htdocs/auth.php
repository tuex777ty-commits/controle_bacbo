<?php
// --- DEBUG TEMPORÁRIO: mostra erros na tela em vez do 500 genérico.
// Depois que o site estiver funcionando, é bom apagar essas 2 linhas.
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/db.php';

// Algumas hospedagens gratuitas restringem a pasta padrão de sessão do PHP,
// o que quebra session_start(). Usamos uma pasta própria dentro do site.
$sessionDir = __DIR__ . '/tmp_sessions';
if (!is_dir($sessionDir)) {
    @mkdir($sessionDir, 0700);
}
if (is_dir($sessionDir) && is_writable($sessionDir)) {
    ini_set('session.save_path', $sessionDir);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user(): ?array {
    if (empty($_SESSION['usuario_id'])) return null;
    return [
        'id'       => (int)$_SESSION['usuario_id'],
        'nome'     => $_SESSION['usuario_nome'] ?? '',
        'is_admin' => !empty($_SESSION['is_admin']),
    ];
}

// Exige usuário logado. Se a chamada for de uma api_*.php, responde 401 em JSON
// em vez de redirecionar (senão o fetch() do front-end quebra).
function require_login(): array {
    $u = current_user();
    if (!$u) {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, 'api_') !== false) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'Sessão expirada, faça login novamente.']);
            exit;
        }
        header('Location: login.php');
        exit;
    }
    return $u;
}

function require_admin(): array {
    $u = require_login();
    if (!$u['is_admin']) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'msg' => 'Acesso restrito a administradores.']);
        exit;
    }
    return $u;
}

function login_user(array $row): void {
    session_regenerate_id(true);
    $_SESSION['usuario_id']   = (int)$row['id'];
    $_SESSION['usuario_nome'] = $row['nome'];
    $_SESSION['is_admin']     = (int)$row['is_admin'] === 1;
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
