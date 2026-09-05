<?php
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json');
$admin = require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método inválido.']);
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
if ($id === (int) $admin['id']) {
    echo json_encode(['ok' => false, 'msg' => 'Você não pode remover a si mesmo.']);
    exit;
}

db()->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);
echo json_encode(['ok' => true]);
