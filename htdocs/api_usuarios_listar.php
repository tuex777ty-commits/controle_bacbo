<?php
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json');
$user = require_admin();

$stmt = db()->query('SELECT id, nome, is_admin, criado_em FROM usuarios ORDER BY id ASC');
echo json_encode(['ok' => true, 'meu_id' => $user['id'], 'usuarios' => $stmt->fetchAll()]);
