<?php
// Rode este arquivo UMA VEZ pra criar o primeiro administrador.
// Depois de usar, APAGUE este arquivo do servidor por segurança.
require_once __DIR__ . '/db.php';

$count = (int) db()->query('SELECT COUNT(*) c FROM usuarios')->fetchColumn();
if ($count > 0) {
    die('Setup já foi concluído (já existem usuários). Por segurança, apague o arquivo setup.php do servidor.');
}

$erro = null; $ok = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $senha = (string) ($_POST['senha'] ?? '');
    if ($nome !== '' && strlen($senha) >= 6) {
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        db()->prepare('INSERT INTO usuarios (nome, senha_hash, is_admin) VALUES (?, ?, 1)')
            ->execute([$nome, $hash]);
        $ok = true;
    } else {
        $erro = 'Preencha um nome de usuário e uma senha com pelo menos 6 caracteres.';
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Setup inicial</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body{ background:#0e1118; color:#eaf2ff; min-height:100vh; display:flex; align-items:center; justify-content:center; }
  .glass{ background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.10); border-radius:18px; padding:32px; width:100%; max-width:420px; }
  .form-control{ background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.18); color:#eaf2ff; border-radius:12px; }
</style>
</head>
<body>
  <div class="glass">
    <h4 class="fw-bold mb-3 text-center">Criar administrador</h4>
    <?php if ($ok): ?>
      <div class="alert alert-success">Administrador criado! <a href="login.php" class="alert-link">Ir para o login</a>.<br><br><strong>Agora apague o arquivo setup.php do servidor.</strong></div>
    <?php else: ?>
      <?php if ($erro): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
      <form method="post">
        <div class="mb-3">
          <label class="form-label">Nome de usuário</label>
          <input type="text" name="nome" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Senha (mín. 6 caracteres)</label>
          <input type="password" name="senha" class="form-control" required>
        </div>
        <button class="btn btn-light w-100" type="submit">Criar administrador</button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
