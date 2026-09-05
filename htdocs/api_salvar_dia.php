<?php
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json');
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método inválido.']);
    exit;
}

$data = $_POST['data'] ?? '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    echo json_encode(['ok' => false, 'msg' => 'Data inválida.']);
    exit;
}
$mesAno = $_POST['mes_ano'] ?? substr($data, 0, 7);
$bancaFimRaw = $_POST['banca_fim'] ?? '';
$bancaFim = ($bancaFimRaw === '' || $bancaFimRaw === null)
    ? null
    : (float) str_replace(',', '.', $bancaFimRaw);

$stmt = db()->prepare('
    INSERT INTO banca_dias (usuario_id, mes_ano, dia, banca_fim)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE banca_fim = VALUES(banca_fim), mes_ano = VALUES(mes_ano)
');
$stmt->execute([$user['id'], $mesAno, $data, $bancaFim]);

echo json_encode(['ok' => true]);
