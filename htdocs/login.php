<?php
require_once __DIR__ . '/auth.php';

if (current_user()) {
    header('Location: dashboard.php');
    exit;
}

$erro = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $senha = (string) ($_POST['senha'] ?? '');

    $stmt = db()->prepare('SELECT * FROM usuarios WHERE nome = ? LIMIT 1');
    $stmt->execute([$nome]);
    $user = $stmt->fetch();

    if ($user && password_verify($senha, $user['senha_hash'])) {
        login_user($user);
        db()->prepare('UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?')->execute([$user['id']]);
        header('Location: dashboard.php');
        exit;
    }
    $erro = 'Usuário ou senha inválidos.';
}
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Entrar | Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body{ background:#0e1118; color:#eaf2ff; min-height:100vh; display:flex; align-items:center; justify-content:center; }
  .glass{ background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.10); border-radius:18px; padding:32px; width:100%; max-width:380px; }
  .form-control{ background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.18); color:#eaf2ff; border-radius:12px; }
  .form-control:focus{ background:rgba(255,255,255,.10); color:#eaf2ff; box-shadow:none; border-color:#7f8cff; }
  .btn-entrar{ border-radius:12px; }
  a{ color:#8ad7ff; }
</style>
</head>
<body>
  <div class="glass">
    <h4 class="fw-bold mb-3 text-center">Entrar</h4>
    <?php if ($erro): ?>
      <div class="alert alert-danger py-2"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Nome de usuário</label>
        <input type="text" name="nome" class="form-control" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label">Senha</label>
        <input type="password" name="senha" class="form-control" required>
      </div>
      <button class="btn btn-light w-100 btn-entrar" type="submit">Entrar</button>
    </form>
    <p class="text-center mt-3 mb-0"><a href="register.php">Criar uma conta</a></p>
  </div>
</body>
</html>
