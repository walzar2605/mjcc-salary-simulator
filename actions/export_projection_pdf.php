<?php
/**
 * MJCC — Export PDF Projection de Carrière
 * Librairie : DomPDF (vendor/dompdf)
 * Appel : index.php?page=export_projection_pdf&grade_id=X&situation=Y&nb_enfants=Z
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/calcul.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

exigerConnexion();

$gradeId   = (int)($_GET['grade_id']   ?? 0);
$situation = $_GET['situation']        ?? 'celibataire';
$nbEnfants = max(0, min(6, (int)($_GET['nb_enfants'] ?? 0)));

if ($gradeId <= 0) {
    die('<p style="font-family:sans-serif;color:#B91C1C;padding:2rem">Paramètres invalides.</p>');
}

// ── Calculer les 12 projections ───────────────────────────────────────────
$grades      = getGradesCaches();
$gradeActuel = null;
foreach ($grades as $g) {
    if ((int)$g['id'] === $gradeId) { $gradeActuel = $g; break; }
}

if (!$gradeActuel) {
    die('<p style="font-family:sans-serif;color:#B91C1C;padding:2rem">Grade introuvable.</p>');
}

$projections = [];
for ($ech = 1; $ech <= 12; $ech++) {
    $projections[] = simulerRemuneration($gradeId, $ech, $situation, $nbEnfants);
}

$netMin   = min(array_column($projections, 'net_a_payer'));
$netMax   = max(array_column($projections, 'net_a_payer'));
$gainTotal = $netMax - $netMin;

$sitLib   = libelleSituation($situation);
$dateAuj  = date('d/m/Y');
$gradeLib = htmlspecialchars($gradeActuel['libelle']);
$echelle  = htmlspecialchars($gradeActuel['echelle']);
$agentNom = htmlspecialchars($_SESSION['user_nom'] ?? 'Agent');

$fmt = fn(float $n) => number_format($n, 2, ',', ' ') . ' MAD';

logAction((int)$_SESSION['user_id'], 'EXPORT_PROJECTION_PDF', [
    'grade' => $gradeLib,
    'situation' => $situation,
]);

// ── Lignes du tableau ─────────────────────────────────────────────────────
$tableRows = '';
foreach ($projections as $i => $p) {
    $ech     = $i + 1;
    $isFirst = $ech === 1;
    $isLast  = $ech === 12;
    $bg      = ($ech % 2 === 0) ? '#F8FAFC' : '#FFFFFF';
    if ($isFirst) $bg = '#EFF6FF';
    if ($isLast)  $bg = '#F0FDF4';

    $tauxColor = (float)$p['taux_retenue'] < 20 ? '#15803D' : ((float)$p['taux_retenue'] < 30 ? '#B45309' : '#B91C1C');

    $tableRows .= "
    <tr style='background:{$bg};'>
      <td style='text-align:center;font-weight:" . ($isFirst||$isLast?'bold':'normal') . ";'>{$ech}</td>
      <td style='text-align:center;'>{$p['indice_brut']}</td>
      <td style='text-align:right;'>" . $fmt((float)$p['traitement_base']) . "</td>
      <td style='text-align:right;'>" . $fmt((float)$p['indemnite_base'])  . "</td>
      <td style='text-align:right;font-weight:600;'>" . $fmt((float)$p['brut_total']) . "</td>
      <td style='text-align:right;color:#B91C1C;'>− " . $fmt((float)$p['retenues_total']) . "</td>
      <td style='text-align:right;font-weight:700;color:#0B3C5D;'>" . $fmt((float)$p['net_a_payer']) . "</td>
      <td style='text-align:center;color:{$tauxColor};font-weight:700;'>" . number_format((float)$p['taux_retenue'], 1) . "%</td>
    </tr>";
}

// ── HTML du PDF ───────────────────────────────────────────────────────────
$html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1E293B; background: #fff; }

  /* EN-TÊTE */
  .header { background: #0B3C5D; color: white; padding: 16px 20px; }
  .header-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
  .header-title { font-size: 15px; font-weight: bold; }
  .header-sub { font-size: 8px; color: #93C5FD; margin-top: 3px; }
  .header-ref { font-size: 8px; color: #93C5FD; text-align: right; }
  .header-ref strong { color: white; font-size: 10px; display: block; }
  .header-divider { border-top: 1px solid #1F5A82; margin: 8px 0; }
  .header-grade { font-size: 11px; font-weight: bold; }
  .header-detail { font-size: 8px; color: #BFDBFE; margin-top: 3px; }

  /* KPI CARDS */
  .kpis { display: table; width: 100%; margin: 14px 0; border-spacing: 8px; }
  .kpis-row { display: table-row; }
  .kpi { display: table-cell; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 6px; padding: 10px 12px; width: 25%; }
  .kpi-label { font-size: 7.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; color: #64748B; margin-bottom: 4px; }
  .kpi-value { font-size: 13px; font-weight: bold; color: #0B3C5D; }
  .kpi-value.green { color: #15803D; }
  .kpi-value.blue  { color: #1D4ED8; }

  /* SECTION TITRE */
  .section-title { font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.07em; color: #64748B; border-bottom: 1px solid #E2E8F0; padding-bottom: 4px; margin: 16px 0 10px; }

  /* TABLEAU */
  table.data { width: 100%; border-collapse: collapse; }
  table.data th {
    background: #1F5A82; color: white; padding: 7px 8px;
    font-size: 8px; font-weight: bold; text-transform: uppercase;
    letter-spacing: 0.05em; border: 1px solid #0B3C5D;
    text-align: center;
  }
  table.data td {
    padding: 6px 8px; font-size: 8.5px; border: 1px solid #E2E8F0;
    color: #374151;
  }
  table.data tr:last-child td { border-bottom: 2px solid #0B3C5D; }

  /* PIED DE PAGE */
  .footer { border-top: 1px solid #E2E8F0; margin-top: 16px; padding-top: 8px; text-align: center; color: #94A3B8; font-size: 7.5px; }

  /* Note légale */
  .note { background: #FFFBEB; border: 1px solid #FCD34D; border-radius: 4px; padding: 8px 10px; margin-top: 12px; font-size: 7.5px; color: #92400E; }
</style>
</head>
<body>

<!-- EN-TÊTE -->
<div class="header">
  <div class="header-top">
    <div>
      <div class="header-title">Projection de Carrière — Simulation de Rémunération</div>
      <div class="header-sub">Ministère de la Jeunesse, de la Culture et de la Communication — DRH</div>
    </div>
    <div class="header-ref">
      <strong>PROJ-{$gradeId}</strong>
      Généré le {$dateAuj}
    </div>
  </div>
  <div class="header-divider"></div>
  <div class="header-grade">{$gradeLib} — Échelle {$echelle}</div>
  <div class="header-detail">Agent : {$agentNom} &nbsp;|&nbsp; Situation : {$sitLib} &nbsp;|&nbsp; Enfants : {$nbEnfants}</div>
</div>

<!-- KPI -->
<div class="kpis">
  <div class="kpis-row">
    <div class="kpi">
      <div class="kpi-label">Net Échelon 1</div>
      <div class="kpi-value">{$fmt($netMin)}</div>
    </div>
    <div class="kpi">
      <div class="kpi-label">Net Échelon 12</div>
      <div class="kpi-value green">{$fmt($netMax)}</div>
    </div>
    <div class="kpi">
      <div class="kpi-label">Gain Total Carrière</div>
      <div class="kpi-value blue">+{$fmt($gainTotal)}</div>
    </div>
    <div class="kpi">
      <div class="kpi-label">Grade / Échelle</div>
      <div class="kpi-value">{$gradeLib}</div>
    </div>
  </div>
</div>

<!-- TABLEAU DES 12 ÉCHELONS -->
<div class="section-title">Détail par échelon — Les 12 échelons de carrière</div>

<table class="data">
  <thead>
    <tr>
      <th>Échelon</th>
      <th>Indice</th>
      <th>Base</th>
      <th>Indemnité</th>
      <th>BRUT</th>
      <th>Retenues</th>
      <th>NET À PAYER</th>
      <th>Taux</th>
    </tr>
  </thead>
  <tbody>
    {$tableRows}
  </tbody>
</table>

<!-- NOTE LÉGALE -->
<div class="note">
  ⚠ Document simulatif à titre indicatif — Non contractuel. Les montants sont calculés selon le barème IR 2026 (LF 2025), CMR 10%, AMO 2,5%. Consultez le Service de la Paie pour toute information officielle.
</div>

<!-- PIED DE PAGE -->
<div class="footer">
  © {date('Y')} Ministère de la Jeunesse, de la Culture et de la Communication — Royaume du Maroc — MJCC v{APP_VERSION} &nbsp;|&nbsp; Page 1/1
</div>

</body>
</html>
HTML;

// Remplacer les appels de fonction PHP dans le heredoc
$html = str_replace('{date(\'Y\')}', date('Y'), $html);
$html = str_replace('{APP_VERSION}', APP_VERSION, $html);

// ── Générer le PDF avec DomPDF ────────────────────────────────────────────
$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'mjcc_projection_' . preg_replace('/[^a-z0-9]/i', '_', $gradeLib) . '_' . date('Ymd') . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
exit;
