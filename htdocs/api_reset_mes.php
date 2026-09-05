<?php
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json');
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método inválido.']);
    exit;
}

$mesAno = (isset($_POST['mes_ano']) && preg_match('/^\d{4}-\d{2}$/', $_POST['mes_ano']))
    ? $_POST['mes_ano']
    : date('Y-m');

db()->prepare('DELETE FROM banca_dias WHERE usuario_id = ? AND mes_ano = ?')
    ->execute([$user['id'], $mesAno]);

echo json_encode(['ok' => true]);
