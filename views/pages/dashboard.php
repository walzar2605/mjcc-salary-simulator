<?php
$db    = getDB();
$uid   = (int)$_SESSION['user_id'];
$isAdm = estAdmin();
$BP    = BASE_PATH;

$scopeWhere = $isAdm ? '' : 'WHERE s.user_id = :uid';
$scopeParam = $isAdm ? [] : [':uid' => $uid];

$stmtStats = $db->prepare("SELECT COUNT(*) AS total, AVG(net_a_payer) AS moy_net, AVG(taux_retenue) AS moy_taux, SUM(CASE WHEN DATE(date_simulation)=CURDATE() THEN 1 ELSE 0 END) AS today FROM simulations s {$scopeWhere}");
$stmtStats->execute($scopeParam);
$stats = $stmtStats->fetch();

$stmtLast = $db->prepare("SELECT s.id,s.net_a_payer,s.taux_retenue,s.niveau_alerte,s.date_simulation,g.libelle AS grade_libelle,s.echelon,u.nom AS agent_nom FROM simulations s JOIN grades g ON s.grade_id=g.id JOIN users u ON s.user_id=u.id " . ($isAdm?'':'WHERE s.user_id=:uid') . " ORDER BY s.date_simulation DESC LIMIT 8");
$stmtLast->execute($isAdm?[]:[':uid'=>$uid]);
$dernieres = $stmtLast->fetchAll();

$stmtGrade = $db->prepare("SELECT g.libelle,COUNT(*) AS nb,AVG(s.net_a_payer) AS moy FROM simulations s JOIN grades g ON s.grade_id=g.id " . ($isAdm?'':'WHERE s.user_id=:uid') . " GROUP BY g.id ORDER BY nb DESC LIMIT 5");
$stmtGrade->execute($isAdm?[]:[':uid'=>$uid]);
$parGrade = $stmtGrade->fetchAll();
$maxNb = !empty($parGrade) ? max(array_column($parGrade,'nb')) : 1;

$taux = (float)($stats['moy_taux'] ?? 0);
$tauxColor = $taux > 30 ? 'var(--danger)' : ($taux > 20 ? 'var(--warning)' : 'var(--success)');
?>

<style>
  .kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px; }
  @media(max-width:900px){ .kpi-grid { grid-template-columns:repeat(2,1fr); } }
  @media(max-width:560px){ .kpi-grid { grid-template-columns:1fr; } }

  .kpi-card {
    background:white; border:1px solid var(--rule); border-radius:var(--radius-lg);
    padding:20px; box-shadow:var(--shadow-sm); position:relative; overflow:hidden;
    transition:box-shadow .2s,transform .2s;
  }
  .kpi-card:hover { box-shadow:var(--shadow); transform:translateY(-2px); }
  .kpi-card::before {
    content:''; position:absolute; top:0; left:0; right:0; height:3px;
    background:var(--grad); opacity:0; transition:opacity .2s;
  }
  .kpi-card:hover::before { opacity:1; }
  .kpi-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:var(--subtle); margin-bottom:8px; }
  .kpi-value { font-size:26px; font-weight:800; line-height:1; letter-spacing:-.02em; color:var(--ink); margin-bottom:5px; }
  .kpi-value.grad { background:var(--grad); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
  .kpi-sub { font-size:11.5px; color:var(--subtle); }

  .dash-grid { display:grid; grid-template-columns:3fr 2fr; gap:20px; }
  @media(max-width:960px){ .dash-grid { grid-template-columns:1fr; } }

  .section-header { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid var(--rule); }
  .section-title { font-size:13.5px; font-weight:700; color:var(--ink); }
  .section-link { font-size:12px; color:var(--blue); font-weight:600; text-decoration:none; }
  .section-link:hover { text-decoration:underline; }

  .dash-table { width:100%; border-collapse:collapse; }
  .dash-table th { padding:9px 14px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:var(--subtle); background:var(--surface2); border-bottom:1px solid var(--rule); text-align:left; }
  .dash-table td { padding:10px 14px; font-size:13px; border-bottom:1px solid var(--surface); color:var(--ink-soft); }
  .dash-table tr:last-child td { border-bottom:none; }
  .dash-table tr:hover td { background:var(--surface2); }
  .td-grade { font-size:12.5px; font-weight:600; color:var(--ink); }
  .td-agent { font-size:11px; color:var(--subtle); margin-bottom:2px; }
  .td-net { font-family:'JetBrains Mono',monospace; font-size:13px; font-weight:700; color:var(--success); text-align:right; }
  .td-ech { font-size:13px; font-weight:700; color:var(--blue); text-align:center; }
  .td-date { font-size:11.5px; color:var(--subtle); white-space:nowrap; }
  .td-badge { text-align:center; }

  .taux-badge { display:inline-block; padding:3px 8px; border-radius:100px; font-size:11px; font-weight:700; }
  .taux-s { background:var(--success-bg); color:var(--success); }
  .taux-w { background:var(--warning-bg); color:var(--warning); }
  .taux-d { background:var(--danger-bg);  color:var(--danger); }

  .bar-row { margin-bottom:12px; }
  .bar-label { display:flex; justify-content:space-between; margin-bottom:5px; }
  .bar-name { font-size:12px; color:var(--ink-soft); font-weight:500; max-width:170px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .bar-count { font-size:11.5px; color:var(--subtle); font-weight:600; }
  .bar-track { height:6px; background:var(--surface); border-radius:3px; overflow:hidden; }
  .bar-fill  { height:6px; border-radius:3px; background:var(--grad); transition:width .4s ease; }

  .action-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; padding:20px; }
  .action-btn {
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    gap:8px; padding:16px 12px; border-radius:var(--radius-lg);
    border:1.5px solid var(--rule); background:white; cursor:pointer;
    transition:all .18s; text-decoration:none; color:var(--ink-soft);
  }
  .action-btn:hover { border-color:var(--blue); color:var(--blue); box-shadow:var(--shadow-sm); transform:translateY(-1px); }
  .action-btn-icon { width:32px; height:32px; border-radius:8px; background:var(--grad); display:flex; align-items:center; justify-content:center; }
  .action-btn-icon svg { width:16px; height:16px; stroke:white; }
  .action-btn-label { font-size:12px; font-weight:600; text-align:center; }
  .action-btn-primary { background:var(--grad); color:white; border-color:transparent; box-shadow:0 4px 12px rgba(37,99,235,.25); }
  .action-btn-primary:hover { color:white; box-shadow:0 6px 18px rgba(37,99,235,.35); }
  .action-btn-primary .action-btn-label { color:white; }
</style>

<!-- KPI Cards -->
<div class="kpi-grid">
  <div class="kpi-card">
    <div class="kpi-label"><?= $isAdm ? 'Total Simulations' : 'Mes simulations' ?></div>
    <div class="kpi-value grad"><?= number_format((int)$stats['total']) ?></div>
    <div class="kpi-sub"><?= $isAdm ? 'Toutes les simulations' : 'Simulations enregistrées' ?></div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Net Moyen Simulé</div>
    <div class="kpi-value grad" style="font-size:20px;">
      <?= $stats['moy_net'] ? number_format((float)$stats['moy_net'],0,',',' ').' MAD' : '—' ?>
    </div>
    <div class="kpi-sub">Salaire net après retenues</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Simulations Aujourd'hui</div>
    <div class="kpi-value grad"><?= (int)$stats['today'] ?></div>
    <div class="kpi-sub"><?= date('d / m / Y') ?></div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Taux de Retenue Moyen</div>
    <div class="kpi-value" style="color:<?= $tauxColor ?>"><?= number_format($taux,1) ?>%</div>
    <div class="kpi-sub">CMR + AMO + Mutuelle + IR</div>
  </div>
</div>

<!-- Main grid -->
<div class="dash-grid">

  <!-- Table simulations récentes -->
  <div class="card overflow-hidden">
    <div class="section-header">
      <span class="section-title">Dernières Simulations</span>
      <a href="<?= $BP ?>/index.php?page=historique" class="section-link">Voir tout →</a>
    </div>
    <?php if (empty($dernieres)): ?>
      <div style="padding:48px 20px;text-align:center;color:var(--subtle);">
        <svg width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px;display:block;stroke:var(--rule)"><path stroke-linecap="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        <p style="font-size:13.5px">Aucune simulation enregistrée.</p>
        <a href="<?= $BP ?>/index.php?page=simulateur" class="btn btn-primary" style="margin-top:14px;display:inline-flex;">Première simulation →</a>
      </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
      <table class="dash-table">
        <thead><tr>
          <?php if($isAdm): ?><th>Agent</th><?php endif; ?>
          <th>Grade</th>
          <th class="td-ech" style="text-align:center">Éch.</th>
          <th style="text-align:right">Net à Payer</th>
          <th style="text-align:center">Alerte</th>
          <th>Date</th>
        </tr></thead>
        <tbody>
          <?php foreach ($dernieres as $s):
            $bc = $s['niveau_alerte']==='success' ? 'taux-s' : ($s['niveau_alerte']==='warning' ? 'taux-w' : 'taux-d');
          ?>
          <tr>
            <?php if($isAdm): ?>
            <td><span style="font-size:12px;font-weight:600;color:var(--ink)"><?= htmlspecialchars($s['agent_nom']) ?></span></td>
            <?php endif; ?>
            <td><div class="td-grade"><?= htmlspecialchars($s['grade_libelle']) ?></div></td>
            <td class="td-ech"><?= $s['echelon'] ?></td>
            <td class="td-net"><?= number_format((float)$s['net_a_payer'],2,',',' ') ?></td>
            <td class="td-badge"><span class="taux-badge <?= $bc ?>"><?= number_format((float)$s['taux_retenue'],1) ?>%</span></td>
            <td class="td-date"><?= date('d/m/Y',strtotime($s['date_simulation'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Right column -->
  <div style="display:flex;flex-direction:column;gap:20px;">

    <!-- Actions rapides -->
    <div class="card overflow-hidden">
      <div class="section-header">
        <span class="section-title">Actions Rapides</span>
      </div>
      <div class="action-grid">
        <a href="<?= $BP ?>/index.php?page=simulateur" class="action-btn action-btn-primary" style="grid-column:1/-1;">
          <div style="display:flex;align-items:center;gap:10px;">
            <div class="action-btn-icon" style="background:rgba(255,255,255,.2);">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 4v16m8-8H4"/></svg>
            </div>
            <span class="action-btn-label" style="font-size:13px;">Nouvelle Simulation</span>
          </div>
        </a>
        <a href="<?= $BP ?>/index.php?page=historique" class="action-btn">
          <div class="action-btn-icon"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 8v4l3 3m-9 1A9 9 0 1012 3v1"/><path stroke-linecap="round" d="M3 4v4h4"/></svg></div>
          <span class="action-btn-label">Historique</span>
        </a>
        <?php if(!estAgent()): ?>
        <a href="<?= $BP ?>/index.php?page=comparateur" class="action-btn">
          <div class="action-btn-icon"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></div>
          <span class="action-btn-label">Comparateur</span>
        </a>
        <a href="<?= $BP ?>/index.php?page=projection" class="action-btn">
          <div class="action-btn-icon"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
          <span class="action-btn-label">Projection</span>
        </a>
        <?php endif; ?>
        <a href="<?= $BP ?>/index.php?page=profil" class="action-btn" <?= estAgent() ? '' : '' ?>>
          <div class="action-btn-icon"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></div>
          <span class="action-btn-label">Mon Profil</span>
        </a>
      </div>
    </div>

    <!-- Distribution par grade -->
    <?php if(!empty($parGrade)): ?>
    <div class="card overflow-hidden">
      <div class="section-header">
        <span class="section-title">Distribution par Grade</span>
      </div>
      <div style="padding:18px 20px;">
        <?php foreach ($parGrade as $g):
          $pct = ($g['nb'] / $maxNb) * 100;
        ?>
        <div class="bar-row">
          <div class="bar-label">
            <span class="bar-name"><?= htmlspecialchars($g['libelle']) ?></span>
            <span class="bar-count"><?= $g['nb'] ?></span>
          </div>
          <div class="bar-track">
            <div class="bar-fill" style="width:<?= $pct ?>%"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>