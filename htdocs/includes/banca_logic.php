<?php
require_once __DIR__ . '/../db.php';

function dias_no_mes(string $mesAno): int {
    [$y, $m] = array_map('intval', explode('-', $mesAno));
    return (int) date('t', mktime(0, 0, 0, $m, 1, $y));
}

function mes_anterior(string $mesAno): string {
    [$y, $m] = array_map('intval', explode('-', $mesAno));
    $m -= 1;
    if ($m < 1) { $m = 12; $y -= 1; }
    return sprintf('%04d-%02d', $y, $m);
}

function mes_label_curto(string $mesAno): string {
    $meses = ['01'=>'Jan','02'=>'Fev','03'=>'Mar','04'=>'Abr','05'=>'Mai','06'=>'Jun',
              '07'=>'Jul','08'=>'Ago','09'=>'Set','10'=>'Out','11'=>'Nov','12'=>'Dez'];
    [$y, $m] = explode('-', $mesAno);
    return ($meses[$m] ?? $m) . '/' . substr($y, 2);
}

// Garante que existe uma config para aquele mês. Se não existir, cria uma nova
// semeada a partir da banca final do mês anterior (mesmo comportamento do
// front-end original quando o usuário navega pra um mês novo).
function ensure_config(int $usuarioId, string $mesAno): array {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM banca_config WHERE usuario_id = ? AND mes_ano = ?');
    $stmt->execute([$usuarioId, $mesAno]);
    $cfg = $stmt->fetch();
    if ($cfg) return $cfg;

    $bancaInicial = 50.0;
    $meta = 20.0;
    $stop = 20.0;

    $prevRef = mes_anterior($mesAno);
    $stmtPrev = $pdo->prepare('SELECT * FROM banca_config WHERE usuario_id = ? AND mes_ano = ?');
    $stmtPrev->execute([$usuarioId, $prevRef]);
    $prevCfg = $stmtPrev->fetch();

    if ($prevCfg) {
        $prevState = compute_month_state($usuarioId, $prevRef, $prevCfg);
        $bancaInicial = $prevState['resumo']['banca_atual'];
        $meta = (float)$prevCfg['meta_pct'];
        $stop = (float)$prevCfg['stoploss_pct'];
    }

    $ins = $pdo->prepare('INSERT INTO banca_config (usuario_id, mes_ano, banca_inicial, meta_pct, stoploss_pct) VALUES (?,?,?,?,?)');
    $ins->execute([$usuarioId, $mesAno, $bancaInicial, $meta, $stop]);

    $stmt->execute([$usuarioId, $mesAno]);
    return $stmt->fetch();
}

// Recalcula todo o estado de um mês (banca dia a dia, relatório, série diária)
// a partir da config + dos lançamentos salvos no banco.
function compute_month_state(int $usuarioId, string $mesAno, array $cfg): array {
    $pdo = db();
    $totalDias = dias_no_mes($mesAno);

    $stmt = $pdo->prepare('SELECT dia, banca_fim FROM banca_dias WHERE usuario_id = ? AND mes_ano = ?');
    $stmt->execute([$usuarioId, $mesAno]);
    $rows = [];
    foreach ($stmt->fetchAll() as $r) {
        $rows[$r['dia']] = $r['banca_fim'];
    }

    $diario = [];
    $runningBank = (float)$cfg['banca_inicial'];
    $lastKnownBank = $runningBank;
    $diasPos = 0; $diasNeg = 0; $diasSem = 0; $streak = 0; $curStreak = 0;

    for ($d = 1; $d <= $totalDias; $d++) {
        $date = sprintf('%s-%02d', $mesAno, $d);
        $bancaInicio = $runningBank;
        $bancaFimRaw = $rows[$date] ?? null;
        $hasResult = $bancaFimRaw !== null;
        $bancaFim = $hasResult ? (float)$bancaFimRaw : null;

        if ($hasResult) {
            $diff = $bancaFim - $bancaInicio;
            if ($diff > 0)      { $diasPos++; $curStreak++; }
            elseif ($diff < 0)  { $diasNeg++; $curStreak = 0; }
            else                { $diasSem++; $curStreak = 0; }
            $streak = max($streak, $curStreak);
            $runningBank = $bancaFim;
            $lastKnownBank = $bancaFim;
        }

        $diario[] = [
            'data'         => $date,
            'banca_inicio' => round($bancaInicio, 2),
            'banca_fim'    => $hasResult ? round($bancaFim, 2) : null,
        ];
    }

    $resumo = [
        'banca_inicial' => round((float)$cfg['banca_inicial'], 2),
        'banca_atual'   => round($lastKnownBank, 2),
    ];

    $totalLancados = $diasPos + $diasNeg + $diasSem;
    $relatorio = [
        'dias_pos'        => $diasPos,
        'dias_neg'        => $diasNeg,
        'dias_sem'        => count($diario) - $totalLancados,
        'streak_vitorias' => $streak,
        'hit_rate'        => $totalLancados ? ($diasPos / $totalLancados) * 100 : 0,
    ];

    $seriesDiaria = ['labels' => [], 'valores' => []];
    foreach ($diario as $d) {
        if ($d['banca_fim'] !== null) {
            $seriesDiaria['labels'][] = 'Dia ' . (int)substr($d['data'], -2);
            $seriesDiaria['valores'][] = $d['banca_fim'];
        }
    }

    return [
        'config'        => $cfg,
        'resumo'        => $resumo,
        'relatorio'     => $relatorio,
        'diario'        => $diario,
        'series_diaria' => $seriesDiaria,
    ];
}

// Monta o payload completo que api_dashboard.php devolve pro front-end.
function build_payload(int $usuarioId, string $mesAno): array {
    $pdo = db();
    $cfg = ensure_config($usuarioId, $mesAno);
    $state = compute_month_state($usuarioId, $mesAno, $cfg);

    $stmt = $pdo->prepare('SELECT DISTINCT mes_ano FROM banca_config WHERE usuario_id = ? ORDER BY mes_ano ASC');
    $stmt->execute([$usuarioId]);
    $meses = array_column($stmt->fetchAll(), 'mes_ano');
    if (!in_array($mesAno, $meses, true)) $meses[] = $mesAno;
    sort($meses);

    $labels = []; $valores = [];
    foreach ($meses as $m) {
        $c = ensure_config($usuarioId, $m);
        $s = compute_month_state($usuarioId, $m, $c);
        $labels[] = mes_label_curto($m);
        $valores[] = round($s['resumo']['banca_atual'] - $s['resumo']['banca_inicial'], 2);
    }

    return [
        'ok' => true,
        'config' => [
            'banca_inicial' => (float)$state['config']['banca_inicial'],
            'meta_pct'      => (float)$state['config']['meta_pct'],
            'stoploss_pct'  => (float)$state['config']['stoploss_pct'],
        ],
        'resumo'        => $state['resumo'],
        'relatorio'     => $state['relatorio'],
        'diario'        => $state['diario'],
        'series_diaria' => $state['series_diaria'],
        'series_mensal' => ['labels' => $labels, 'valores' => $valores],
    ];
}
