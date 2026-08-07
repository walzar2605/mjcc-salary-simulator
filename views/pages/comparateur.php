<?php
/**
 * MJCC — Comparateur de Simulations
 */
$grades = getGradesCaches();
$BP     = BASE_PATH;

$simA = null; $simB = null; $errA = null; $errB = null;

$paramsA = [
  'grade_id'    => (int)($_GET['a_grade'] ?? 0),
  'echelon'     => max(1, min(12, (int)($_GET['a_echelon'] ?? 1))),
  'situation'   => $_GET['a_situation'] ?? 'celibataire',
  'nb_enfants'  => max(0, min(6, (int)($_GET['a_enfants'] ?? 0))),
];

$paramsB = [
  'grade_id'    => (int)($_GET['b_grade'] ?? 0),
  'echelon'     => max(1, min(12, (int)($_GET['b_echelon'] ?? 1))),
  'situation'   => $_GET['b_situation'] ?? 'celibataire',
  'nb_enfants'  => max(0, min(6, (int)($_GET['b_enfants'] ?? 0))),
];

if ($paramsA['grade_id'] > 0) {
  try {
    $simA = simulerRemuneration($paramsA['grade_id'], $paramsA['echelon'], $paramsA['situation'], $paramsA['nb_enfants']);
  } catch (Exception $e) {
    $errA = $e->getMessage();
  }
}

if ($paramsB['grade_id'] > 0) {
  try {
    $simB = simulerRemuneration($paramsB['grade_id'], $paramsB['echelon'], $paramsB['situation'], $paramsB['nb_enfants']);
  } catch (Exception $e) {
    $errB = $e->getMessage();
  }
}

$situations = [
  'celibataire'        => 'Célibataire',
  'marie_sans_enfant'  => 'Marié(e) sans enfant',
  'marie_1enfant'      => 'Marié(e) — 1 enfant',
  'marie_2enfants'     => 'Marié(e) — 2 enfants',
  'marie_3enfants'     => 'Marié(e) — 3 enfants',
  'marie_4enfants'     => 'Marié(e) — 4 enfants et +',
];
?>
<style>
  .comp-wrap { max-width:1100px; }
  .comp-desc { font-size:13.5px; color:var(--muted); margin-bottom:20px; line-height:1.5; }

  .comp-profiles-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px; }
  @media(max-width:768px) { .comp-profiles-grid { grid-template-columns:1fr; } }

  .profile-card {
    background:white; border-radius:var(--radius-xl);
    border:1px solid var(--rule); box-shadow:var(--shadow-sm);
    overflow:hidden;
  }
  .profile-card-a { border-top:3px solid var(--blue); }
  .profile-card-b { border-top:3px solid var(--success); }

  .profile-card-head {
    padding:14px 18px; border-bottom:1px solid var(--rule);
    display:flex; align-items:center; gap:10px;
    background:var(--surface2);
  }
  .profile-badge {
    width:28px; height:28px; border-radius:8px;
    display:flex; align-items:center; justify-content:center;
    color:white; font-size:13px; font-weight:800; flex-shrink:0;
  }
  .profile-badge-a { background:var(--blue); }
  .profile-badge-b { background:var(--success); }
  .profile-head-title { font-size:14px; font-weight:700; color:var(--ink); }

  .profile-card-body { padding:18px; }
  .form-group { margin-bottom:14px; }
  .form-group:last-child { margin-bottom:0; }
  .profile-card-body .input { width:100%; }
  .profile-card-body .label { font-size:12px; font-weight:600; color:var(--ink-soft); margin-bottom:5px; display:block; }

  .range-row { display:flex; align-items:center; gap:10px; margin-top:4px; }
  .range-input {
    flex:1; height:5px; border-radius:3px; cursor:pointer; -webkit-appearance:none;
  }
  .range-input-a { accent-color:var(--blue); background:linear-gradient(90deg,var(--blue),var(--cyan)); }
  .range-input-b { accent-color:var(--success); background:linear-gradient(90deg,var(--success),#4ade80); }
  .range-input::-webkit-slider-thumb { -webkit-appearance:none; width:18px; height:18px; border-radius:50%; background:white; border:3px solid currentColor; box-shadow:0 1px 4px rgba(0,0,0,.18); cursor:pointer; }
  .range-val {
    min-width:36px; height:34px; display:flex; align-items:center; justify-content:center;
    color:white; border-radius:var(--radius); font-family:'JetBrains Mono',monospace;
    font-size:14px; font-weight:700; flex-shrink:0;
  }
  .range-val-a { background:var(--blue); box-shadow:0 2px 8px rgba(37,99,235,.25); }
  .range-val-b { background:var(--success); box-shadow:0 2px 8px rgba(22,163,74,.25); }

  .comp-submit { text-align:center; padding:16px 0; }
  .btn-compare {
    padding:12px 40px; border-radius:var(--radius-lg);
    font-size:14px; font-weight:700;
    background:var(--grad); color:white; border:none; cursor:pointer;
    box-shadow:0 4px 16px rgba(37,99,235,.25);
    transition:all .18s; font-family:'Plus Jakarta Sans',sans-serif;
    display:inline-flex; align-items:center; gap:8px;
  }
  .btn-compare:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(37,99,235,.35); }

  /* Résultats */
  .comp-summary {
    border-radius:var(--radius-xl); overflow:hidden;
    margin-bottom:20px;
    background:linear-gradient(135deg,#1e3a8a 0%,#0e7490 100%);
    box-shadow:var(--shadow-lg);
  }
  .comp-summary-inner {
    display:grid; grid-template-columns:1fr auto 1fr;
    gap:0; color:white;
  }
  .comp-sum-block { padding:20px 24px; text-align:center; }
  .comp-sum-label { font-size:9px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:rgba(255,255,255,.45); margin-bottom:6px; }
  .comp-sum-value { font-family:'JetBrains Mono',monospace; font-size:22px; font-weight:700; }
  .comp-sum-sub   { font-size:11px; color:rgba(255,255,255,.45); margin-top:4px; line-height:1.4; }
  .comp-sum-divider {
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    padding:16px; border-left:1px solid rgba(255,255,255,.1); border-right:1px solid rgba(255,255,255,.1);
  }
  .comp-ecart-label { font-size:9px; letter-spacing:.1em; text-transform:uppercase; color:rgba(255,255,255,.4); margin-bottom:6px; }
  .comp-ecart-value { font-family:'JetBrains Mono',monospace; font-size:20px; font-weight:700; }
  .ecart-pos { color:#86efac; }
  .ecart-neg { color:#fca5a5; }
  .ecart-zero { color:rgba(255,255,255,.5); }
  .comp-winner-badge {
    margin-top:6px; padding:3px 10px; border-radius:100px;
    font-size:10px; font-weight:700; background:rgba(255,255,255,.12);
    color:rgba(255,255,255,.7);
  }

  /* Table diff */
  .diff-pos  { color:var(--success); font-weight:600; }
  .diff-neg  { color:var(--danger);  font-weight:600; }
  .diff-zero { color:var(--subtle); }

  .empty-state { padding:60px 20px; text-align:center; color:var(--subtle); }
  .empty-state-icon { font-size:40px; margin-bottom:14px; }
  .empty-state p { font-size:13.5px; line-height:1.6; }
</style>

<div class="comp-wrap">
  <p class="comp-desc">
    Comparez deux profils côte à côte pour visualiser les différences de rémunération.
  </p>

  <form method="GET" action="<?= htmlspecialchars($BP) ?>/index.php">
    <input type="hidden" name="page" value="comparateur">

    <div class="comp-profiles-grid">
      <?php
      // ✅ FIX: foreach صحيح
      foreach ([
        'a' => ['A', 'a', $paramsA],
        'b' => ['B', 'b', $paramsB],
      ] as $k => [$lbl, $cls, $p]):
      ?>
      <div class="profile-card profile-card-<?= htmlspecialchars($cls) ?>">
        <div class="profile-card-head">
          <div class="profile-badge profile-badge-<?= htmlspecialchars($cls) ?>"><?= htmlspecialchars($lbl) ?></div>
          <div class="profile-head-title">Profil <?= htmlspecialchars($lbl) ?></div>
        </div>

        <div class="profile-card-body">
          <div class="form-group">
            <label class="label">Grade</label>
            <select name="<?= htmlspecialchars($k) ?>_grade" class="input">
              <option value="">— Sélectionner —</option>
              <?php foreach($grades as $g): ?>
                <option value="<?= (int)$g['id'] ?>" <?= ((int)$g['id'] === (int)$p['grade_id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($g['libelle']) ?> — Éch. <?= htmlspecialchars($g['echelle']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="label">Échelon</label>
            <div class="range-row">
              <input
                type="range"
                id="<?= htmlspecialchars($k) ?>_echelon"
                name="<?= htmlspecialchars($k) ?>_echelon"
                min="1" max="12"
                value="<?= (int)$p['echelon'] ?>"
                class="range-input range-input-<?= htmlspecialchars($cls) ?>"
              >
              <div class="range-val range-val-<?= htmlspecialchars($cls) ?>" id="<?= htmlspecialchars($k) ?>_ev">
                <?= (int)$p['echelon'] ?>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="label">Situation familiale</label>
            <select name="<?= htmlspecialchars($k) ?>_situation" class="input">
              <?php foreach($situations as $sv => $sl): ?>
                <option value="<?= htmlspecialchars($sv) ?>" <?= ($p['situation'] === $sv) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($sl) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="label">Enfants à charge</label>
            <input type="number" name="<?= htmlspecialchars($k) ?>_enfants" class="input" min="0" max="6" value="<?= (int)$p['nb_enfants'] ?>">
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="comp-submit">
      <button type="submit" class="btn-compare">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
        </svg>
        Comparer les deux profils
      </button>
    </div>
  </form>

  <?php if ($simA && $simB):
    $diffNet = round((float)$simB['net_a_payer'] - (float)$simA['net_a_payer'], 2);
    $winner  = $diffNet > 0 ? 'B' : ($diffNet < 0 ? 'A' : '=');
    $ecartCls= $diffNet > 0 ? 'ecart-pos' : ($diffNet < 0 ? 'ecart-neg' : 'ecart-zero');
  ?>

  <!-- Résumé visuel -->
  <div class="comp-summary" style="margin-top:8px;">
    <div class="comp-summary-inner">
      <div class="comp-sum-block">
        <div class="comp-sum-label">Net — Profil A</div>
        <div class="comp-sum-value"><?= fmt((float)$simA['net_a_payer']) ?></div>
        <div class="comp-sum-sub"><?= htmlspecialchars($simA['grade']['libelle']) ?> · Éch.<?= (int)$simA['echelon'] ?></div>
      </div>

      <div class="comp-sum-divider">
        <div class="comp-ecart-label">Écart net</div>
        <div class="comp-ecart-value <?= htmlspecialchars($ecartCls) ?>">
          <?= ($diffNet >= 0 ? '+' : '') . fmt($diffNet) ?>
        </div>
        <?php if ($winner !== '='): ?>
          <div class="comp-winner-badge">Profil <?= htmlspecialchars($winner) ?> avantageux</div>
        <?php endif; ?>
      </div>

      <div class="comp-sum-block">
        <div class="comp-sum-label">Net — Profil B</div>
        <div class="comp-sum-value"><?= fmt((float)$simB['net_a_payer']) ?></div>
        <div class="comp-sum-sub"><?= htmlspecialchars($simB['grade']['libelle']) ?> · Éch.<?= (int)$simB['echelon'] ?></div>
      </div>
    </div>
  </div>

  <!-- Tableau détaillé -->
  <div class="card overflow-hidden mb-5">
    <div style="padding:14px 18px;border-bottom:1px solid var(--rule);background:var(--surface2);display:flex;align-items:center;gap:8px;">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="var(--blue)" stroke-width="2">
        <path stroke-linecap="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
      </svg>
      <span style="font-size:13px;font-weight:700;color:var(--ink);">Détail des écarts</span>
    </div>

    <div class="overflow-x-auto">
      <table>
        <thead>
          <tr>
            <th>Élément</th>
            <th style="text-align:right">Profil A</th>
            <th style="text-align:right">Profil B</th>
            <th style="text-align:right">Écart (B − A)</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // ✅ FIX: $lignes مصحّحة (حيدنا داك ',' الزايد كامل)
          $lignes = [
            ['Indice brut',         $simA['indice_brut'],       $simB['indice_brut'],       false, ' pts', false],
            ['Traitement de base',  $simA['traitement_base'],   $simB['traitement_base'],   true,  null,  false],
            ['Indemnité (IRF)',     $simA['indemnite_base'],    $simB['indemnite_base'],    true,  null,  false],
            ['Brut total',          $simA['brut_total'],        $simB['brut_total'],        true,  null,  false],
            ['Retenue CMR (10%)',   $simA['retenue_cmr'],       $simB['retenue_cmr'],       true,  null,  true],
            ['AMO + Mutuelle',      $simA['retenue_mutuelle'],  $simB['retenue_mutuelle'],  true,  null,  true],
            ['Retenue IR',          $simA['retenue_ir'],        $simB['retenue_ir'],        true,  null,  true],
            ['Total retenues',      $simA['retenues_total'],    $simB['retenues_total'],    true,  null,  true],
            ['Taux de retenue',     $simA['taux_retenue'],      $simB['taux_retenue'],      false, '%',   true],
            ['NET À PAYER',         $simA['net_a_payer'],       $simB['net_a_payer'],       true,  null,  false],
          ];

          foreach ($lignes as [$lfr, $vA, $vB, $money, $suf, $ret]):
            $d = round((float)$vB - (float)$vA, 2);
            $isLast = ($lfr === 'NET À PAYER');

            $cls = $d > 0
              ? ($ret ? 'diff-neg' : 'diff-pos')
              : ($d < 0 ? ($ret ? 'diff-pos' : 'diff-neg') : 'diff-zero');

            $f = function($v) use ($money, $suf) {
              if ($money) return fmt((float)$v);
              $dec = ($suf === '%') ? 2 : 0;
              return number_format((float)$v, $dec) . ($suf ?? '');
            };
          ?>
          <tr <?= $isLast ? 'style="background:rgba(37,99,235,.05);font-weight:700;"' : '' ?>>
            <td class="<?= $isLast ? '' : 'text-gray-600' ?>" style="<?= $isLast ? 'color:var(--ink);' : '' ?>">
              <?= htmlspecialchars($lfr) ?>
            </td>
            <td style="text-align:right" class="mono"><?= $f($vA) ?></td>
            <td style="text-align:right" class="mono"><?= $f($vB) ?></td>
            <td style="text-align:right" class="mono <?= htmlspecialchars($cls) ?>">
              <?= ($d >= 0 ? '+' : '') . $f($d) ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Graphique -->
  <div class="card p-5">
    <div style="font-size:13px;font-weight:700;color:var(--ink);margin-bottom:16px;display:flex;align-items:center;gap:8px;">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="var(--blue)" stroke-width="2">
        <path stroke-linecap="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
      </svg>
      Visualisation comparative
    </div>

    <canvas id="chartComp" height="90"></canvas>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
  <script>
  new Chart(document.getElementById('chartComp'),{
    type:'bar',
    data:{
      labels:['Base','Indemnité','Brut','Retenues','Net'],
      datasets:[
        {
          label:'A — <?= htmlspecialchars(addslashes($simA['grade']['libelle'])) ?>',
          data:[<?= implode(',', array_map('round', [
            $simA['traitement_base'],
            $simA['indemnite_base'],
            $simA['brut_total'],
            $simA['retenues_total'],
            $simA['net_a_payer']
          ])) ?>],
          backgroundColor:'rgba(37,99,235,.8)', borderRadius:6
        },
        {
          label:'B — <?= htmlspecialchars(addslashes($simB['grade']['libelle'])) ?>',
          data:[<?= implode(',', array_map('round', [
            $simB['traitement_base'],
            $simB['indemnite_base'],
            $simB['brut_total'],
            $simB['retenues_total'],
            $simB['net_a_payer']
          ])) ?>],
          backgroundColor:'rgba(22,163,74,.8)', borderRadius:6
        }
      ]
    },
    options:{
      responsive:true,
      plugins:{
        legend:{ position:'top' },
        tooltip:{ callbacks:{ label:c => c.dataset.label+' : '+new Intl.NumberFormat('fr-MA',{minimumFractionDigits:2}).format(c.raw)+' MAD' } }
      },
      scales:{
        y:{ ticks:{ callback:v => new Intl.NumberFormat('fr-MA',{notation:'compact'}).format(v)+' MAD' } }
      }
    }
  });
  </script>

  <?php elseif ($errA || $errB): ?>
    <div class="flash flash-danger"><?= htmlspecialchars($errA ?? $errB) ?></div>
  <?php else: ?>
    <div class="card">
      <div class="empty-state">
        <div class="empty-state-icon">⚖️</div>
        <p>Sélectionnez deux profils ci-dessus puis cliquez sur <strong>Comparer</strong>.</p>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
['a','b'].forEach(k => {
  const r = document.getElementById(k+'_echelon');
  const v = document.getElementById(k+'_ev');
  if(r && v) r.addEventListener('input', () => v.textContent = r.value);
});
</script>