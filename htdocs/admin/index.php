<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../includes/banca_logic.php';
$admin = require_admin();

$mesAtual = date('Y-m');

$stmt = db()->query('SELECT id, nome, is_admin, criado_em, ultimo_login FROM usuarios ORDER BY id ASC');
$usuarios = $stmt->fetchAll();

foreach ($usuarios as &$u) {
    $cfgStmt = db()->prepare('SELECT * FROM banca_config WHERE usuario_id = ? AND mes_ano = ?');
    $cfgStmt->execute([$u['id'], $mesAtual]);
    $cfg = $cfgStmt->fetch();

    if ($cfg) {
        $state = compute_month_state((int) $u['id'], $mesAtual, $cfg);
        $u['banca_inicial'] = $state['resumo']['banca_inicial'];
        $u['banca_atual']   = $state['resumo']['banca_atual'];
    } else {
        $u['banca_inicial'] = null;
        $u['banca_atual']   = null;
    }

    $diaStmt = db()->prepare('SELECT MAX(atualizado_em) AS ultimo FROM banca_dias WHERE usuario_id = ?');
    $diaStmt->execute([$u['id']]);
    $u['ultima_atualizacao'] = $diaStmt->fetch()['ultimo'] ?? null;
}
unset($u);

function fmt_money($v) {
    if ($v === null) return '—';
    return 'R$ ' . number_format((float) $v, 2, ',', '.');
}
function fmt_data($v) {
    if (!$v) return '—';
    return date('d/m/Y H:i', strtotime($v));
}
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Admin | Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
  body{ background:#0e1118; color:#eaf2ff; }
  .page{ max-width:1200px; margin:0 auto; padding:24px; }
  .glass{ background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.10); border-radius:18px; padding:18px; }
  .table{ --bs-table-color:#eaf2ff; --bs-table-bg:transparent; --bs-table-border-color:rgba(255,255,255,.14); }
  .badge-admin{ background:rgba(0,200,120,.18); color:#7FFFB6; }
  .badge-user{ background:rgba(255,255,255,.10); color:#cdd6e6; }
  a{ color:#8ad7ff; }
</style>
</head>
<body>
<div class="page">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold m-0"><i class="bi bi-shield-lock"></i> Painel administrador</h4>
    <div>
      <a href="../dashboard.php" class="btn btn-outline-light btn-sm me-2">Voltar ao dashboard</a>
      <a href="../logout.php" class="btn btn-outline-light btn-sm">Sair</a>
    </div>
  </div>

  <div class="glass mb-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-person-plus"></i> Novo usuário</h6>
    <div id="msgUsuario"></div>
    <form id="formNovoUsuario" class="row g-2 align-items-end">
      <div class="col-12 col-md-4">
        <label class="form-label small">Nome de usuário</label>
        <input type="text" name="nome" class="form-control form-control-sm" required>
      </div>
      <div class="col-12 col-md-4">
        <label class="form-label small">Senha</label>
        <input type="password" name="senha" class="form-control form-control-sm" minlength="3" required>
      </div>
      <div class="col-6 col-md-2 form-check ms-2">
        <input type="checkbox" class="form-check-input" id="chkAdmin" name="is_admin" value="1">
        <label class="form-check-label small" for="chkAdmin">Admin?</label>
      </div>
      <div class="col-6 col-md-2">
        <button type="submit" class="btn btn-light btn-sm w-100">Criar</button>
      </div>
    </form>
  </div>

  <div class="glass">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Admin?</th>
            <th>Banca inicial (<?= htmlspecialchars($mesAtual) ?>)</th>
            <th>Banca atual</th>
            <th>Última atualização</th>
            <th>Último login</th>
            <th>Cadastrado em</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($usuarios as $u): ?>
            <tr>
              <td><?= (int) $u['id'] ?></td>
              <td><?= htmlspecialchars($u['nome']) ?></td>
              <td>
                <?php if ($u['is_admin']): ?>
                  <span class="badge badge-admin">Sim</span>
                <?php else: ?>
                  <span class="badge badge-user">Não</span>
                <?php endif; ?>
              </td>
              <td><?= fmt_money($u['banca_inicial']) ?></td>
              <td><?= fmt_money($u['banca_atual']) ?></td>
              <td><?= fmt_data($u['ultima_atualizacao']) ?></td>
              <td><?= fmt_data($u['ultimo_login']) ?></td>
              <td><?= fmt_data($u['criado_em']) ?></td>
              <td>
                <?php if ((int) $u['id'] !== (int) $admin['id']): ?>
                  <button class="btn btn-outline-danger btn-sm btn-remover" data-id="<?= (int) $u['id'] ?>" data-nome="<?= htmlspecialchars($u['nome']) ?>">
                    Remover
                  </button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
const msgBox = document.getElementById('msgUsuario');
function mostrarMsg(texto, tipo){
  msgBox.innerHTML = '<div class="alert alert-' + tipo + ' py-2">' + texto + '</div>';
}

document.getElementById('formNovoUsuario').addEventListener('submit', async function(e){
  e.preventDefault();
  const form = e.target;
  const fd = new FormData(form);
  try {
    const res = await fetch('../api_usuarios_salvar.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      mostrarMsg('Usuário criado com sucesso!', 'success');
      setTimeout(() => location.reload(), 700);
    } else {
      mostrarMsg(data.msg || 'Não foi possível criar o usuário.', 'danger');
    }
  } catch (err) {
    mostrarMsg('Erro de conexão ao criar usuário.', 'danger');
  }
});

document.querySelectorAll('.btn-remover').forEach(btn => {
  btn.addEventListener('click', async function(){
    const id = this.dataset.id;
    const nome = this.dataset.nome;
    if (!confirm('Remover o usuário "' + nome + '"? Essa ação não pode ser desfeita.')) return;
    const fd = new FormData();
    fd.append('id', id);
    try {
      const res = await fetch('../api_usuarios_remover.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.ok) {
        location.reload();
      } else {
        alert(data.msg || 'Não foi possível remover o usuário.');
      }
    } catch (err) {
      alert('Erro de conexão ao remover usuário.');
    }
  });
});
</script>
</body>
</html>
