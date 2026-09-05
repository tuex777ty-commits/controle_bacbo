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
$bancaInicial = (float) str_replace(',', '.', $_POST['banca_inicial'] ?? '0');
$metaPct      = (float) str_replace(',', '.', $_POST['meta_pct'] ?? '0');
$stoplossPct  = (float) str_replace(',', '.', $_POST['stoploss_pct'] ?? '0');

$stmt = db()->prepare('
    INSERT INTO banca_config (usuario_id, mes_ano, banca_inicial, meta_pct, stoploss_pct)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        banca_inicial = VALUES(banca_inicial),
        meta_pct = VALUES(meta_pct),
        stoploss_pct = VALUES(stoploss_pct)
');
$stmt->execute([$user['id'], $mesAno, $bancaInicial, $metaPct, $stoplossPct]);

echo json_encode(['ok' => true]);
