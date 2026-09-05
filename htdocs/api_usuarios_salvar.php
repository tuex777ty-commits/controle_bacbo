<?php
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json');
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método inválido.']);
    exit;
}

$nome  = trim($_POST['nome'] ?? '');
$senha = (string) ($_POST['senha'] ?? '');
$isAdmin = !empty($_POST['is_admin']) ? 1 : 0;

if ($nome === '' || strlen($senha) < 3) {
    echo json_encode(['ok' => false, 'msg' => 'Preencha nome de usuário e senha (mín. 3 caracteres).']);
    exit;
}

$stmt = db()->prepare('SELECT id FROM usuarios WHERE nome = ?');
$stmt->execute([$nome]);
if ($stmt->fetch()) {
    echo json_encode(['ok' => false, 'msg' => 'Já existe um usuário com esse nome.']);
    exit;
}

$hash = password_hash($senha, PASSWORD_DEFAULT);
db()->prepare('INSERT INTO usuarios (nome, senha_hash, is_admin) VALUES (?, ?, ?)')
    ->execute([$nome, $hash, $isAdmin]);

echo json_encode(['ok' => true]);
