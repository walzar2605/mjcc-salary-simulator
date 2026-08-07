<?php
$db    = $db ?? getDB();
$BP    = BASE_PATH;
$simId = (int)($_GET['id'] ?? 0);
$sim   = $_SESSION['last_sim'] ?? null;

if (!$sim && $simId > 0) {
    $stmt = $db->prepare('
        SELECT s.*, g.libelle AS gl, g.echelle, u.nom AS un
        FROM simulations s
        JOIN grades g ON s.grade_id = g.id
        JOIN users  u ON s.user_id  = u.id
        WHERE s.id = :id AND (s.user_id = :uid OR :ia = 1)
    ');
    $stmt->execute([':id'=>$simId,':uid'=>$_SESSION['user_id'],':ia'=>(int)estAdmin()]);
    $row = $stmt->fetch();
    if ($row) {
        $sim = [
            'grade'               => ['libelle'=>$row['gl'],'echelle'=>$row['echelle']],
            'echelon'             => $row['echelon'],
            'situation_familiale' => $row['situation_familiale'],
            'nb_enfants'          => $row['nb_enfants'],
            'nom_employe'         => $row['nom_employe'] ?? '',
            'corps'               => $row['corps'] ?? '',
            'mutuelle_org'        => $row['mutuelle_org'] ?? 'aucune',
            'mutuelle_libelle'    => MUTUELLES[$row['mutuelle_org']??'aucune']['libelle'] ?? 'Aucune',
            'taux_mutuelle_total' => TAUX_AMO + (MUTUELLES[$row['mutuelle_org']??'aucune']['taux_mutuelle']??0),
            'indice_brut'         => $row['indice_brut'],
            'traitement_base'     => $row['traitement_base'],
            'indemnite_base'      => $row['indemnite_base'],
            'brut_total'          => (float)$row['traitement_base']+(float)$row['indemnite_base'],
            'retenue_cmr'         => $row['retenue_cmr'],
            'retenue_mutuelle'    => $row['retenue_mutuelle'],
            'retenue_ir'          => $row['retenue_ir'],
            'retenues_total'      => $row['retenues_total'],
            'net_a_payer'         => $row['net_a_payer'],
            'taux_retenue'        => $row['taux_retenue'],
            'niveau_alerte'       => $row['niveau_alerte'],
            'message_conseil'     => $row['message_conseil'],
            'agent_nom'           => $row['un'],
            'date_sim'            => $row['date_simulation'],
        ];
    }
}

if (!$sim) {
    echo '<div class="card p-8 text-center">
        <p style="color:var(--subtle);margin-bottom:16px;">Aucune simulation trouvée.</p>
        <a href="'.$BP.'/index.php?page=simulateur" class="btn btn-primary">Simulateur</a>
    </div>';
    return;
}

$alertBg = ['success'=>'var(--success-bg)','warning'=>'var(--warning-bg)','danger'=>'var(--danger-bg)'][$sim['niveau_alerte']] ?? 'var(--surface)';
$alertBd = ['success'=>'var(--success-bd)','warning'=>'var(--warning-bd)','danger'=>'var(--danger-bd)'][$sim['niveau_alerte']] ?? 'var(--rule)';
$alertTx = ['success'=>'var(--success)',   'warning'=>'var(--warning)',   'danger'=>'var(--danger)'][$sim['niveau_alerte']]   ?? 'var(--ink)';

$mutLib   = $sim['mutuelle_libelle'] ?? 'Aucune mutuelle';
$tauxMut  = number_format(($sim['taux_mutuelle_total'] ?? 0.025)*100, 1);
$mutLabel = $sim['mutuelle_org']==='aucune' ? 'AMO seule (2,5%)' : "AMO + {$mutLib} ({$tauxMut}%)";
$nomEmp   = !empty($sim['nom_employe']) ? $sim['nom_employe'] : ($sim['agent_nom'] ?? '');
$refNum   = $simId ? str_pad($simId, 5, '0', STR_PAD_LEFT) : '—';
?>
<style>
  .bul-wrap { max-width:720px; margin:0 auto; }
  .bul-actions {
    display:flex; align-items:center; gap:10px;
    margin-bottom:20px; flex-wrap:wrap;
  }
  .bul-actions .btn-back {
    display:inline-flex; align-items:center; gap:6px;
    font-size:13px; font-weight:600; color:var(--muted);
    background:white; border:1.5px solid var(--rule);
    border-radius:var(--radius); padding:8px 16px;
    cursor:pointer; transition:all .15s; text-decoration:none;
  }
  .bul-actions .btn-back:hover { border-color:var(--blue); color:var(--blue); }
  .bul-actions-right { margin-left:auto; display:flex; gap:8px; }
  

  /* Bulletin card */
  .bulletin {
    background:white; border-radius:var(--radius-xl);
    border:1px solid var(--rule); box-shadow:var(--shadow-xl);
    overflow:hidden;
  }

  /* Header bleu gradient */
  .bul-header {
    background:linear-gradient(135deg,#1e3a8a 0%,#0e7490 100%);
    padding:24px 28px 20px;
  }
  .bul-header-top {
    display:flex; justify-content:space-between; align-items:flex-start;
    margin-bottom:16px;
  }
  .bul-org-block {}
  .bul-org-name {
    font-size:11px; font-weight:700; color:rgba(255,255,255,.55);
    letter-spacing:.08em; text-transform:uppercase; margin-bottom:3px;
  }
  .bul-org-fr {
    font-size:13px; font-weight:700; color:white;
    line-height:1.4;
  }
  .bul-org-ar {
    font-size:13px;
    color:rgba(255,255,255,.55); margin-top:2px;
  }
  .bul-ref-block { text-align:right; }
  
  .bul-ref-label { font-size:9px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:rgba(255,255,255,.4); }
  .bul-ref-num   { font-family:'JetBrains Mono',monospace; font-size:16px; font-weight:700; color:white; }
  .bul-ref-date  { font-size:11px; color:rgba(255,255,255,.45); margin-top:2px; }

  .bul-type {
    font-size:10.5px; font-weight:700; letter-spacing:.1em;
    text-transform:uppercase; color:rgba(255,255,255,.5); margin-bottom:8px;
  }
  
  .bul-grade {
    font-size:22px; font-weight:800; color:white;
    letter-spacing:-.02em; margin-bottom:6px; line-height:1.2;
  }
  
  .bul-tags { display:flex; flex-wrap:wrap; gap:8px; }
  .bul-tag {
    display:inline-flex; align-items:center; gap:4px;
    background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18);
    border-radius:100px; padding:3px 10px;
    font-size:11px; color:rgba(255,255,255,.8); font-weight:500;
  }
  

  /* Body */
  .bul-body { padding:24px 28px; }

  /* Section titre */
  .bul-sec-title {
    font-size:10px; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
    color:var(--subtle); margin-bottom:10px; padding-bottom:6px;
    border-bottom:1px solid var(--rule);
  }
  

  /* Table bulletin */
  .bul-table { width:100%; border-collapse:collapse; margin-bottom:20px; }
  .bul-table th {
    padding:9px 14px; font-size:10px; font-weight:700; letter-spacing:.08em;
    text-transform:uppercase; color:var(--subtle); background:var(--surface2);
    border-bottom:1px solid var(--rule); text-align:left;
  }
  
  .bul-table td { padding:11px 14px; font-size:13px; border-bottom:1px solid var(--surface); color:var(--ink-soft); }
  
  .bul-table tr:last-child td { border-bottom:none; }
  .bul-table .row-total td { background:rgba(37,99,235,.05); font-weight:700; color:var(--ink); border-top:1px solid rgba(37,99,235,.15); }
  .bul-table .row-deduct td { background:rgba(220,38,38,.02); }
  .bul-table .row-total-neg td { background:rgba(220,38,38,.05); font-weight:700; color:var(--danger); border-top:1px solid rgba(220,38,38,.15); }
  .td-right { text-align:right; font-family:'JetBrains Mono',monospace; font-size:12.5px; }
  .td-center { text-align:center; color:var(--muted); font-size:12px; }
  .td-neg { color:var(--danger); }

  /* Net box */
  .net-box {
    background:linear-gradient(135deg,#1e3a8a,#0e7490);
    border-radius:var(--radius-lg); padding:22px;
    text-align:center; margin-bottom:20px;
    box-shadow:0 6px 20px rgba(37,99,235,.2);
  }
  .net-label { font-size:10px; font-weight:700; color:rgba(255,255,255,.5); letter-spacing:.12em; text-transform:uppercase; margin-bottom:6px; }
  
  .net-value { font-family:'JetBrains Mono',monospace; font-size:36px; font-weight:700; color:white; line-height:1; }
  .net-rate  { font-size:11.5px; color:rgba(255,255,255,.4); margin-top:6px; }

  /* Alert conseil */
  .conseil-box {
    border-radius:var(--radius); padding:14px 16px; margin-bottom:20px;
    border:1.5px solid; font-size:13px; font-weight:500; line-height:1.6;
  }
  

  /* Footer bulletin */
  .bul-footer {
    display:flex; align-items:center; justify-content:space-between;
    padding-top:14px; border-top:1px solid var(--rule); margin-top:4px;
  }
  .bul-footer-org {
    font-size:11.5px; font-weight:600; color:var(--muted);
    line-height:1.5;
  }
  
  .bul-footer-note { font-size:11px; color:var(--subtle); text-align:right; }
  

  @media(max-width:640px) { .bul-header,.bul-body { padding:16px; } .bul-header-top { flex-direction:column; gap:10px; } }
  @media print { .bulletin { box-shadow:none; border:1px solid #ddd; } }
</style>

<div class="bul-wrap">
  <!-- Actions bar -->
  <div class="bul-actions no-print">
    <a href="<?= $BP ?>/index.php?page=simulateur" class="btn-back">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M15 19l-7-7 7-7"/></svg>
      Nouvelle simulation
      
    </a>
    <div class="bul-actions-right">
      <a href="<?= $BP ?>/index.php?page=historique" class="btn btn-secondary" style="font-size:13px;padding:8px 16px;">
        Historique
      </a>
      <!-- Bouton Télécharger PDF bulletin -->
      <a href="<?= $BP ?>/index.php?page=export_pdf&id=<?= $simId ?>" class="btn btn-primary" style="font-size:13px;padding:8px 18px;">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
        Télécharger PDF
      </a>
    </div>
  </div>

  <!-- BULLETIN -->
  <div class="bulletin">
    <!-- Header -->
    <div class="bul-header">
      <div class="bul-header-top">
        <div class="bul-org-block">
          <div class="bul-org-name ">Royaume du Maroc</div>
          
          <div class="bul-org-fr ">Ministère de la Jeunesse, de la Culture<br>et de la Communication</div>
          
        </div>
        <?php if($simId): ?>
        <div class="bul-ref-block">
          <div class="bul-ref-label">Référence</div>
          <div class="bul-ref-num">SIM-<?= $refNum ?></div>
          <div class="bul-ref-date"><?= isset($sim['date_sim']) ? date('d/m/Y à H:i', strtotime($sim['date_sim'])) : date('d/m/Y à H:i') ?></div>
        </div>
        <?php endif; ?>
      </div>

      <div class="bul-type ">Bulletin de Simulation de Rémunération</div>
      

      <div class="bul-grade">
        <?= htmlspecialchars($sim['grade']['libelle']) ?>
        <span style="font-weight:400;color:rgba(255,255,255,.6);font-size:16px;"> — Échelle <?= $sim['grade']['echelle'] ?></span>
      </div>

      <div class="bul-tags">
        <span class="bul-tag">
          Échelon <?= $sim['echelon'] ?>
          
        </span>
        <span class="bul-tag"><?= htmlspecialchars(libelleSituation($sim['situation_familiale'])) ?></span>
        <span class="bul-tag"><?= htmlspecialchars($mutLabel) ?></span>
        <?php if(!empty($sim['corps']) && ($corpLib = libelleCorps($sim['corps']))): ?>
        <span class="bul-tag"><?= htmlspecialchars($corpLib) ?></span>
        <?php endif; ?>
        <?php if(!empty($nomEmp)): ?>
        <span class="bul-tag">👤 <?= htmlspecialchars($nomEmp) ?></span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Body -->
    <div class="bul-body">
      <!-- Rémunération brute -->
      <div class="bul-sec-title ">Rémunération Brute</div>
      

      <table class="bul-table">
        <thead><tr>
          <th>Désignation</th>
          <th class="td-right">Montant (MAD)</th>
        </tr></thead>
        <tbody>
          <tr>
            <td>
              Traitement de Base <small style="color:var(--subtle)">(Indice <?= (int)$sim['indice_brut'] ?> × 51,40 MAD)</small>
              
            </td>
            <td class="td-right"><?= fmt((float)$sim['traitement_base']) ?></td>
          </tr>
          <tr>
            <td>
              Indemnité Représentative de Frais (IRF)
              
            </td>
            <td class="td-right"><?= fmt((float)$sim['indemnite_base']) ?></td>
          </tr>
          <tr class="row-total">
            <td><strong>Total Brut</strong></td>
            <td class="td-right"><strong><?= fmt((float)$sim['brut_total']) ?></strong></td>
          </tr>
        </tbody>
      </table>

      <!-- Retenues -->
      <div class="bul-sec-title ">Retenues Obligatoires</div>
      

      <table class="bul-table">
        <thead><tr>
          <th>Nature</th>
          <th class="td-center">Taux</th>
          <th class="td-right">Montant (MAD)</th>
        </tr></thead>
        <tbody>
          <tr class="row-deduct">
            <td>CMR — Caisse Marocaine des Retraites</td>
            <td class="td-center">10,00%</td>
            <td class="td-right td-neg">− <?= fmt((float)$sim['retenue_cmr']) ?></td>
          </tr>
          <tr class="row-deduct">
            <td><?= htmlspecialchars($mutLabel) ?></td>
            <td class="td-center"><?= $tauxMut ?>%</td>
            <td class="td-right td-neg">− <?= fmt((float)$sim['retenue_mutuelle']) ?></td>
          </tr>
          <tr class="row-deduct">
            <td>Impôt sur le Revenu (Barème progressif CGI)</td>
            <td class="td-center">Variable</td>
            <td class="td-right td-neg">− <?= fmt((float)$sim['retenue_ir']) ?></td>
          </tr>
          <tr class="row-total-neg">
            <td><strong>Total Retenues</strong></td>
            <td class="td-center"><strong><?= number_format((float)$sim['taux_retenue'],1) ?>%</strong></td>
            <td class="td-right"><strong>− <?= fmt((float)$sim['retenues_total']) ?></strong></td>
          </tr>
        </tbody>
      </table>

      <!-- NET -->
      <div class="net-box">
        <div class="net-label ">Net à Payer</div>
        
        <div class="net-value"><?= fmt((float)$sim['net_a_payer']) ?></div>
        <div class="net-rate ">Taux de retenue global : <?= number_format((float)$sim['taux_retenue'],2) ?>%</div>
        
      </div>

      <!-- Conseil -->
      <div class="conseil-box" style="background:<?= $alertBg ?>;border-color:<?= $alertBd ?>;color:<?= $alertTx ?>;">
        <strong> Analyse & Conseil</strong><br>
        <?= htmlspecialchars($sim['message_conseil'] ?? '') ?>
      </div>

      <!-- Footer bulletin : SANS logo, seulement texte -->
      <div class="bul-footer">
        <div class="bul-footer-org">
          Ministère de la Jeunesse, de la Culture et de la Communication<br>Royaume du Maroc
          
        </div>
        <div class="bul-footer-note">
          Document simulatif — Non contractuel
          
        </div>
      </div>
    </div>
  </div>
</div>
