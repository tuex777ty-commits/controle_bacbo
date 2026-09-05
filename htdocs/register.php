<?php
require_once __DIR__ . '/auth.php';

if (current_user()) {
    header('Location: dashboard.php');
    exit;
}

$erro = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = trim($_POST['nome'] ?? '');
    $senha = (string) ($_POST['senha'] ?? '');
    $senha2 = (string) ($_POST['senha2'] ?? '');

    if ($nome === '' || strlen($nome) < 3) {
        $erro = 'Escolha um nome de usuário com pelo menos 3 caracteres.';
    } elseif (strlen($senha) < 3) {
        $erro = 'A senha precisa ter pelo menos 3 caracteres.';
    } elseif ($senha !== $senha2) {
        $erro = 'As senhas não são iguais.';
    } else {
        $stmt = db()->prepare('SELECT id FROM usuarios WHERE nome = ?');
        $stmt->execute([$nome]);
        if ($stmt->fetch()) {
            $erro = 'Esse nome de usuário já está em uso, escolha outro.';
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            // Cadastro público sempre cria usuário comum (is_admin = 0).
            // Contas de administrador só são criadas pelo painel /admin ou
            // pelo modal "Usuários" dentro do dashboard.
            db()->prepare('INSERT INTO usuarios (nome, senha_hash, is_admin) VALUES (?, ?, 0)')
                ->execute([$nome, $hash]);

            $stmt = db()->prepare('SELECT * FROM usuarios WHERE nome = ? LIMIT 1');
            $stmt->execute([$nome]);
            $user = $stmt->fetch();
            login_user($user);
            db()->prepare('UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?')->execute([$user['id']]);
            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Criar conta | Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body{ background:#0e1118; color:#eaf2ff; min-height:100vh; display:flex; align-items:center; justify-content:center; }
  .glass{ background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.10); border-radius:18px; padding:32px; width:100%; max-width:380px; }
  .form-control{ background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.18); color:#eaf2ff; border-radius:12px; }
  .form-control:focus{ background:rgba(255,255,255,.10); color:#eaf2ff; box-shadow:none; border-color:#7f8cff; }
  a{ color:#8ad7ff; }
</style>
</head>
<body>
  <div class="glass">
    <h4 class="fw-bold mb-3 text-center">Criar conta</h4>
    <?php if ($erro): ?>
      <div class="alert alert-danger py-2"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Nome de usuário</label>
        <input type="text" name="nome" class="form-control" required autofocus value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Senha</label>
        <input type="password" name="senha" class="form-control" required minlength="3">
      </div>
      <div class="mb-3">
        <label class="form-label">Repetir senha</label>
        <input type="password" name="senha2" class="form-control" required minlength="3">
      </div>
      <button class="btn btn-light w-100" type="submit">Criar conta</button>
    </form>
    <p class="text-center mt-3 mb-0"><a href="login.php">Já tenho conta, entrar</a></p>
  </div>
</body>
</html>
