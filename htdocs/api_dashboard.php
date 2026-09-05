<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/includes/banca_logic.php';
header('Content-Type: application/json');

$user = require_login();
$mesAno = (isset($_GET['mes_ano']) && preg_match('/^\d{4}-\d{2}$/', $_GET['mes_ano']))
    ? $_GET['mes_ano']
    : date('Y-m');

echo json_encode(build_payload($user['id'], $mesAno));
