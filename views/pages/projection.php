<?php
/**
 * MJCC — Projection de carrière sur 12 échelons
 */
$grades    = getGradesCaches();
$BP        = BASE_PATH;
$gradeId   = (int)($_GET['grade_id'] ?? 0);
$situation = $_GET['situation'] ?? 'celibataire';
$nbEnfants = max(0, min(6, (int)($_GET['nb_enfants'] ?? 0)));

$projections = [];
$gradeActuel = null;

if ($gradeId > 0) {
    foreach ($grades as $g) {
        if ($g['id'] === $gradeId) { $gradeActuel = $g; break; }
    }
    if ($gradeActuel) {
        for ($ech = 1; $ech <= 12; $ech++) {
            $projections[] = simulerRemuneration($gradeId, $ech, $situation, $nbEnfants);
        }
    }
}

$situations = ['celibataire'=>'Célibataire','marie_sans_enfant'=>'Marié(e) sans enfant','marie_1enfant'=>'Marié(e) — 1 enfant','marie_2enfants'=>'Marié(e) — 2 enfants','marie_3enfants'=>'Marié(e) — 3 enfants','marie_4enfants'=>'Marié(e) — 4 enfants et +'];
?>
<style>
  .proj-wrap { max-width:1000px; }
  .proj-desc { font-size:13.5px; color:var(--muted); margin-bottom:20px; }
  

  .proj-form-card {
    background:white; border-radius:var(--radius-xl);
    border:1px solid var(--rule); box-shadow:var(--shadow-sm);
    padding:20px; margin-bottom:24px;
  }
  .proj-form-header {
    display:flex; align-items:center; gap:10px; margin-bottom:18px;
    padding-bottom:14px; border-bottom:1px solid var(--rule);
  }
  .proj-form-icon {
    width:38px; height:38px; border-radius:10px;
    background:var(--grad); display:flex; align-items:center; justify-content:center;
    box-shadow:0 4px 12px rgba(37,99,235,.25);
  }
  .proj-form-icon svg { width:19px; height:19px; stroke:white; }
  .proj-form-title { font-size:14px; font-weight:700; color:var(--ink); letter-spacing:-.01em; }
  .proj-form-sub   { font-size:11.5px; color:var(--subtle); margin-top:2px; }
  

  .proj-form-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; margin-bottom:18px; }
  @media(max-width:768px) { .proj-form-grid { grid-template-columns:1fr; } }
  .proj-form-grid .input { width:100%; }
  .proj-form-grid .label { font-size:12px; font-weight:600; color:var(--ink-soft); margin-bottom:5px; display:block; }

  .btn-project {
    width:100%; padding:12px; border-radius:var(--radius-lg);
    background:var(--grad); color:white; font-size:14px; font-weight:700;
    border:none; cursor:pointer; transition:all .2s;
    font-family:'Plus Jakarta Sans',sans-serif;
    box-shadow:0 4px 16px rgba(37,99,235,.25);
    display:flex; align-items:center; justify-content:center; gap:8px;
  }
  .btn-project:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(37,99,235,.35); }
  

  /* KPI cards */
  .proj-kpis { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px; }
  @media(max-width:768px) { .proj-kpis { grid-template-columns:1fr 1fr; } }
  .kpi-card {
    background:white; border-radius:var(--radius-lg);
    border:1px solid var(--rule); padding:16px 18px;
    box-shadow:var(--shadow-sm); position:relative; overflow:hidden;
  }
  .kpi-card::before {
    content:''; position:absolute; top:0; left:0; right:0; height:3px;
    background:var(--grad); opacity:0; transition:opacity .2s;
  }
  .kpi-card:hover::before { opacity:1; }
  .kpi-label { font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--subtle); margin-bottom:6px; }
  
  .kpi-value { font-family:'JetBrains Mono',monospace; font-size:16px; font-weight:700; color:var(--blue); line-height:1; }
  .kpi-value.green { color:var(--success); }

  /* Graphique */
  .chart-card { background:white; border-radius:var(--radius-xl); border:1px solid var(--rule); padding:20px; margin-bottom:20px; box-shadow:var(--shadow-sm); }
  .chart-title { font-size:13px; font-weight:700; color:var(--ink); margin-bottom:16px; display:flex; align-items:center; gap:8px; }
  

  /* Table projection */
  .bul-badge-s { background:var(--success-bg); color:var(--success); padding:2px 8px; border-radius:100px; font-size:10px; font-weight:700; }
  .bul-badge-w { background:var(--warning-bg); color:var(--warning); padding:2px 8px; border-radius:100px; font-size:10px; font-weight:700; }
  .bul-badge-d { background:var(--danger-bg);  color:var(--danger);  padding:2px 8px; border-radius:100px; font-size:10px; font-weight:700; }
  .echelon-depart { background:rgba(37,99,235,.05) !important; }
  .echelon-sommet { background:rgba(22,163,74,.05) !important; }

  .empty-proj {
    background:white; border-radius:var(--radius-xl); border:1px solid var(--rule);
    padding:60px 20px; text-align:center; color:var(--subtle);
  }
  .empty-proj svg { width:48px; height:48px; stroke:var(--rule); margin:0 auto 14px; display:block; }
  .empty-proj p { font-size:13.5px; line-height:1.6; }
  
</style>

<div class="proj-wrap">
  <p class="proj-desc">
    Visualisez l'évolution de votre rémunération nette sur l'ensemble des 12 échelons de carrière.
    
  </p>

  <!-- Formulaire -->
  <div class="proj-form-card">
    <div class="proj-form-header">
      <div class="proj-form-icon">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
      </div>
      <div>
        <div class="proj-form-title">
          Paramètres de projection
          
        </div>
        <div class="proj-form-sub">
          Simulation sur les 12 échelons de la grille
          
        </div>
      </div>
    </div>
    <form method="GET" action="<?= $BP ?>/index.php">
      <input type="hidden" name="page" value="projection">
      <div class="proj-form-grid">
        <div>
          <label class="label">
            Grade <span style="color:var(--danger)">*</span>
            </span>
          </label>
          <select name="grade_id" class="input" required>
            <option value="">— Sélectionner —</option>
            <?php foreach($grades as $g): ?>
            <option value="<?= $g['id'] ?>" <?= $g['id']===$gradeId?'selected':'' ?>>
              <?= htmlspecialchars($g['libelle']) ?> — Éch. <?= $g['echelle'] ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="label">
            Situation familiale
            
          </label>
          <select name="situation" class="input">
            <?php foreach($situations as $k=>$v): ?>
            <option value="<?= $k ?>" <?= $situation===$k?'selected':'' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="label">
            Enfants à charge
            
          </label>
          <input type="number" name="nb_enfants" class="input" min="0" max="6" value="<?= $nbEnfants ?>">
        </div>
      </div>
      <button type="submit" class="btn-project">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="18" height="18"><path stroke-linecap="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
        Projeter la carrière
        
      </button>
    </form>
  </div>

  <?php if (!empty($projections)):
    $netMin    = min(array_column($projections,'net_a_payer'));
    $netMax    = max(array_column($projections,'net_a_payer'));
    $gainTotal = round($netMax - $netMin, 2);
    $gainMens  = round($gainTotal / 11, 2);
  ?>

  <!-- KPIs + Bouton PDF -->
  <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap; margin-bottom:0;">
  <div class="proj-kpis" style="flex:1; margin-bottom:0;">
    <div class="kpi-card">
      <div class="kpi-label ">Net Échelon 1</div>
      
      <div class="kpi-value"><?= fmt((float)$netMin) ?></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label ">Net Échelon 12</div>
      
      <div class="kpi-value green"><?= fmt((float)$netMax) ?></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label ">Gain total carrière</div>
      
      <div class="kpi-value green">+<?= fmt($gainTotal) ?></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label ">Grade</div>
      
      <div style="font-size:12px;font-weight:700;color:var(--ink);margin-top:2px;line-height:1.3;">
        <?= htmlspecialchars($gradeActuel['libelle']) ?>
        <span style="color:var(--subtle);font-weight:400"> — Éch.<?= $gradeActuel['echelle'] ?></span>
      </div>
    </div>
  </div>
  <!-- Bouton Télécharger PDF Projection -->
  <div style="display:flex; align-items:center; padding-top:4px;">
    <a href="<?= $BP ?>/index.php?page=export_projection_pdf&grade_id=<?= $gradeId ?>&situation=<?= urlencode($situation) ?>&nb_enfants=<?= $nbEnfants ?>"
       class="btn btn-primary" style="font-size:13px;padding:10px 18px;white-space:nowrap;">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
      Télécharger PDF
    </a>
  </div>
  </div>

  <!-- Graphique -->
  <div class="chart-card">
    <div class="chart-title">
      <svg fill="none" viewBox="0 0 24 24" stroke="var(--blue)" stroke-width="2" width="16" height="16"><path stroke-linecap="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
      Courbe d'évolution — <?= htmlspecialchars($gradeActuel['libelle']) ?>
      
    </div>
    <canvas id="chartProj" height="80"></canvas>
  </div>

  <!-- Tableau détaillé -->
  <div class="card overflow-hidden">
    <div style="padding:14px 18px;border-bottom:1px solid var(--rule);background:var(--surface2);display:flex;align-items:center;gap:8px;">
      <svg fill="none" viewBox="0 0 24 24" stroke="var(--blue)" stroke-width="2" width="16" height="16"><path stroke-linecap="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      <span style="font-size:13px;font-weight:700;color:var(--ink);">
        Détail par échelon
        
      </span>
    </div>
    <div class="overflow-x-auto">
      <table>
        <thead><tr>
          <th style="text-align:center">Échelon</th>
          <th style="text-align:right">Indice</th>
          <th style="text-align:right">Base</th>
          <th style="text-align:right">Indemnité</th>
          <th style="text-align:right">Brut</th>
          <th style="text-align:right">Retenues</th>
          <th style="text-align:right">Net à payer</th>
          <th style="text-align:center">Taux</th>
        </tr></thead>
        <tbody>
          <?php foreach($projections as $i => $p):
            $isFirst = $i === 0;
            $isLast  = $i === 11;
            $gainVs1 = round($p['net_a_payer'] - $projections[0]['net_a_payer'], 2);
            $rowClass = $isFirst ? 'echelon-depart' : ($isLast ? 'echelon-sommet' : '');
          ?>
          <tr class="<?= $rowClass ?>">
            <td style="text-align:center;font-weight:700;">
              <span style="color:<?= $isLast?'var(--success)':'var(--blue)' ?>"><?= $p['echelon'] ?></span>
              <?php if($isFirst): ?><span style="font-size:10px;color:var(--subtle);margin-left:4px;" >(départ)</span><?php endif; ?>
              <?php if($isLast): ?><span style="font-size:10px;color:var(--success);margin-left:4px;" >(sommet)</span><?php endif; ?>
            </td>
            <td style="text-align:right" class="mono" style="font-size:12px;color:var(--subtle)"><?= $p['indice_brut'] ?></td>
            <td style="text-align:right" class="mono" style="font-size:12px"><?= fmt((float)$p['traitement_base']) ?></td>
            <td style="text-align:right" class="mono" style="font-size:12px"><?= fmt((float)$p['indemnite_base']) ?></td>
            <td style="text-align:right" class="mono"><?= fmt((float)$p['brut_total']) ?></td>
            <td style="text-align:right;color:var(--danger)" class="mono" style="font-size:12px">−<?= fmt((float)$p['retenues_total']) ?></td>
            <td style="text-align:right;font-weight:700;color:var(--success)" class="mono"><?= fmt((float)$p['net_a_payer']) ?></td>
            <td style="text-align:center">
              <span class="bul-badge-<?= $p['niveau_alerte']==='success'?'s':($p['niveau_alerte']==='warning'?'w':'d') ?>"><?= number_format((float)$p['taux_retenue'],1) ?>%</span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
  <script>
  const nets  = [<?= implode(',', array_map(fn($p)=>round($p['net_a_payer'],2), $projections)) ?>];
  const bruts = [<?= implode(',', array_map(fn($p)=>round($p['brut_total'],2), $projections)) ?>];
  new Chart(document.getElementById('chartProj'), {
    type:'line',
    data:{
      labels: [<?= implode(',', range(1,12)) ?>].map(l=>'Échelon '+l),
      datasets:[
        {label:'Net à payer', data:nets,  borderColor:'#16A34A', backgroundColor:'rgba(22,163,74,.08)', borderWidth:2.5, pointRadius:5, pointBackgroundColor:'#16A34A', tension:.35, fill:true},
        {label:'Brut total',  data:bruts, borderColor:'#2563EB', backgroundColor:'transparent',          borderWidth:1.5, pointRadius:3, borderDash:[4,3], tension:.35}
      ]
    },
    options:{responsive:true,
      plugins:{legend:{position:'top'},
        tooltip:{callbacks:{label:c=>c.dataset.label+' : '+new Intl.NumberFormat('fr-MA',{minimumFractionDigits:2}).format(c.raw)+' MAD'}}},
      scales:{y:{ticks:{callback:v=>new Intl.NumberFormat('fr-MA',{notation:'compact'}).format(v)+' MAD'}}}}
  });
  </script>

  <?php else: ?>
  <div class="empty-proj">
    <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.5" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
    <p>
      Sélectionnez un grade et une situation familiale pour visualiser l'évolution sur <strong>12 échelons</strong>.
      
    </p>
  </div>
  <?php endif; ?>
</div>
