<?php
/**
 * MJCC — Export PDF via jsPDF (côté navigateur, sans librairie serveur)
 * Accessible aux rôles : admin, agent, invite
 * Appel : index.php?page=export_pdf&id=XXX
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/calcul.php';

exigerConnexion();

$simId = (int)($_GET['id'] ?? 0);
$uid   = (int)$_SESSION['user_id'];

if ($simId <= 0) {
    die('<p style="font-family:sans-serif;color:#B91C1C;padding:2rem">ID de simulation invalide.</p>');
}

$db   = getDB();
$stmt = $db->prepare('
    SELECT s.*, g.libelle AS grade_libelle, g.echelle, u.nom AS agent_nom
    FROM simulations s
    JOIN grades g ON s.grade_id = g.id
    JOIN users  u ON s.user_id  = u.id
    WHERE s.id = :id AND (s.user_id = :uid OR :ia = 1)
');
$stmt->execute([':id' => $simId, ':uid' => $uid, ':ia' => (int)estAdmin()]);
$row = $stmt->fetch();

if (!$row) {
    die('<p style="font-family:sans-serif;color:#B91C1C;padding:2rem">Simulation introuvable ou accès non autorisé.</p>');
}

logAction($uid, 'EXPORT_PDF', ['sim_id' => $simId]);

// ── Données formatées ─────────────────────────────────────────
$ref      = 'SIM-' . str_pad($simId, 5, '0', STR_PAD_LEFT);
$dateSim  = date('d/m/Y H:i', strtotime($row['date_simulation']));
$dateAuj  = date('d/m/Y');
$situation = libelleSituation($row['situation_familiale']);

$f = fn(float $n) => number_format($n, 2, ',', ' ') . ' MAD';

$traitement = $f((float)$row['traitement_base']);
$indemnite  = $f((float)$row['indemnite_base']);
$brutTotal  = $f((float)$row['brut_total']);
$retCmr     = $f((float)$row['retenue_cmr']);
$retMut     = $f((float)$row['retenue_mutuelle']);
$retIr      = $f((float)$row['retenue_ir']);
$retTotal   = $f((float)$row['retenues_total']);
$netPayer   = $f((float)$row['net_a_payer']);
$tauxRet    = number_format((float)$row['taux_retenue'], 2);
$tauxRet1   = number_format((float)$row['taux_retenue'], 1);
$conseil    = addslashes($row['message_conseil'] ?? '');
$agentNom   = htmlspecialchars($row['agent_nom']);
$gradeLib   = htmlspecialchars($row['grade_libelle']);
$echelle    = htmlspecialchars($row['echelle']);
$echelon    = $row['echelon'];
$indice     = $row['indice_brut'];

$alertColor = match($row['niveau_alerte']) {
    'success' => [0.08, 0.50, 0.24],   // vert foncé RGB 0-1
    'warning' => [0.44, 0.25, 0.04],   // orange foncé
    default   => [0.50, 0.11, 0.11],   // rouge foncé
};
$alertBg = match($row['niveau_alerte']) {
    'success' => [0.86, 0.99, 0.89],
    'warning' => [0.99, 0.98, 0.77],
    default   => [0.99, 0.89, 0.89],
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Bulletin <?= $ref ?> — MJCC</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Segoe UI',sans-serif; background:#F4F6F9; display:flex; align-items:center; justify-content:center; min-height:100vh; flex-direction:column; gap:16px; }
    .loader { text-align:center; }
    .spinner { width:48px; height:48px; border:4px solid #E2E8F0; border-top-color:#0B3C5D; border-radius:50%; animation:spin .8s linear infinite; margin:0 auto 12px; }
    @keyframes spin { to { transform:rotate(360deg); } }
    .msg { font-size:14px; color:#475569; }
    .msg strong { color:#0B3C5D; }
    .btn-back { margin-top:8px; padding:8px 20px; background:#0B3C5D; color:#fff; border:none; border-radius:6px; font-size:13px; cursor:pointer; text-decoration:none; display:inline-block; }
    .btn-back:hover { opacity:.88; }
  </style>
</head>
<body>
  <div class="loader">
    <div class="spinner"></div>
    <p class="msg">Génération du PDF en cours…<br><strong><?= $ref ?></strong></p>
  </div>

<script>
window.addEventListener('load', function() {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });

  const W = 210; // largeur A4
  const margin = 18;
  const colW = W - margin * 2;
  let y = 0;

  // ── Couleurs ──────────────────────────────────────────────────
  const C_DARK  = [11,  60,  93];   // #0B3C5D
  const C_MED   = [31,  90, 130];   // #1F5A82
  const C_GREEN = [21, 128,  61];   // #15803D
  const C_RED   = [185, 28,  28];   // #B91C1C
  const C_GRAY  = [100,116,139];    // slate-500
  const C_LGRAY = [241,245,249];    // slate-100
  const C_WHITE = [255,255,255];
  const C_BLUE2 = [239,246,255];    // blue-50
  const C_RED2  = [254,242,242];    // red-50
  const C_ALERT_BG  = [<?= implode(',', array_map(fn($v) => round($v*255), $alertBg)) ?>];
  const C_ALERT_TXT = [<?= implode(',', array_map(fn($v) => round($v*255), $alertColor)) ?>];

  // ── Helper : texte tronqué ────────────────────────────────────
  function truncate(str, maxLen) {
    return str.length > maxLen ? str.substring(0, maxLen - 1) + '…' : str;
  }

  // ── EN-TÊTE bleu ─────────────────────────────────────────────
  doc.setFillColor(...C_DARK);
  doc.rect(0, 0, W, 42, 'F');

  // Ligne décorative
  doc.setFillColor(...C_MED);
  doc.rect(0, 42, W, 1.5, 'F');

  // Titre institution
  doc.setTextColor(...C_WHITE);
  doc.setFontSize(7);
  doc.setFont('helvetica','normal');
  doc.text('ROYAUME DU MAROC', margin, 9);
  doc.text('المملكة المغربية', W - margin, 9, { align: 'right' });

  doc.setFontSize(13);
  doc.setFont('helvetica','bold');
  doc.text('Bulletin de Simulation de Rémunération', margin, 17);

  doc.setFontSize(7.5);
  doc.setFont('helvetica','normal');
  doc.setTextColor(147, 197, 253); // blue-300
  doc.text('Ministère de la Jeunesse, de la Culture et de la Communication', margin, 23);
  doc.text('Direction des Ressources Humaines', margin, 28);

  // Infos droite
  doc.setTextColor(147, 197, 253);
  doc.setFontSize(7);
  doc.text('Document simulatif — Non contractuel', W - margin, 17, { align: 'right' });
  doc.text('<?= $dateSim ?>', W - margin, 22, { align: 'right' });
  doc.setFont('helvetica','bold');
  doc.setTextColor(...C_WHITE);
  doc.text('<?= $ref ?>', W - margin, 27, { align: 'right' });

  // Ligne agent / grade
  doc.setFillColor(...C_MED);
  doc.rect(margin, 32, colW, 0.3, 'F');

  doc.setFont('helvetica','bold');
  doc.setFontSize(8.5);
  doc.setTextColor(...C_WHITE);
  doc.text('<?= $gradeLib ?> — Échelle <?= $echelle ?> — Échelon <?= $echelon ?>', margin, 37.5);

  doc.setFont('helvetica','normal');
  doc.setFontSize(7.5);
  doc.setTextColor(191, 219, 254); // blue-200
  doc.text('Agent : <?= $agentNom ?> | Situation : <?= $situation ?>', margin, 41);

  y = 52;

  // ── Fonction : dessiner une section titre ────────────────────
  function sectionTitle(title, yPos) {
    doc.setFont('helvetica','bold');
    doc.setFontSize(7.5);
    doc.setTextColor(...C_GRAY);
    doc.text(title.toUpperCase(), margin, yPos);
    doc.setDrawColor(...C_LGRAY);
    doc.setLineWidth(0.5);
    doc.line(margin, yPos + 1.5, W - margin, yPos + 1.5);
    return yPos + 7;
  }

  // ── Fonction : ligne de tableau ──────────────────────────────
  function tableRow(label, value, yPos, opts = {}) {
    const rowH = 7.5;
    if (opts.bg) {
      doc.setFillColor(...opts.bg);
      doc.rect(margin, yPos - 5, colW, rowH, 'F');
    }
    doc.setFont('helvetica', opts.bold ? 'bold' : 'normal');
    doc.setFontSize(9);
    doc.setTextColor(...(opts.labelColor || [55, 65, 81]));
    doc.text(truncate(label, 55), margin + 2, yPos);

    doc.setFont('helvetica', opts.bold ? 'bold' : 'normal');
    doc.setTextColor(...(opts.valueColor || [55, 65, 81]));
    doc.text(value, W - margin - 2, yPos, { align: 'right' });

    doc.setDrawColor(241, 245, 249);
    doc.setLineWidth(0.3);
    doc.line(margin, yPos + 2.5, W - margin, yPos + 2.5);
    return yPos + rowH;
  }

  // ── Fonction : ligne 3 colonnes (retenues) ───────────────────
  function tableRow3(label, taux, montant, yPos, opts = {}) {
    const rowH = 7.5;
    if (opts.bg) {
      doc.setFillColor(...opts.bg);
      doc.rect(margin, yPos - 5, colW, rowH, 'F');
    }
    doc.setFont('helvetica', opts.bold ? 'bold' : 'normal');
    doc.setFontSize(9);
    doc.setTextColor(...(opts.labelColor || [55, 65, 81]));
    doc.text(truncate(label, 45), margin + 2, yPos);
    doc.setTextColor(...C_GRAY);
    doc.setFontSize(8);
    doc.text(taux, margin + colW * 0.62, yPos, { align: 'center' });
    doc.setFontSize(9);
    doc.setTextColor(...(opts.valueColor || C_RED));
    doc.text(montant, W - margin - 2, yPos, { align: 'right' });
    doc.setDrawColor(241, 245, 249);
    doc.setLineWidth(0.3);
    doc.line(margin, yPos + 2.5, W - margin, yPos + 2.5);
    return yPos + rowH;
  }

  // ── SECTION 1 : RÉMUNÉRATION BRUTE ───────────────────────────
  y = sectionTitle('Éléments de Rémunération Brute', y);

  // En-tête colonnes
  doc.setFont('helvetica','bold');
  doc.setFontSize(7.5);
  doc.setTextColor(...C_GRAY);
  doc.text('DÉSIGNATION', margin + 2, y);
  doc.text('MONTANT (MAD)', W - margin - 2, y, { align: 'right' });
  y += 5;

  y = tableRow('Traitement de Base  (Indice <?= $indice ?> × 51,40 MAD)', '<?= $traitement ?>', y);
  y = tableRow('Indemnité Représentative de Frais (IRF)', '<?= $indemnite ?>', y);
  y = tableRow('TOTAL BRUT', '<?= $brutTotal ?>', y, {
    bg: C_BLUE2,
    bold: true,
    labelColor: C_DARK,
    valueColor: C_DARK
  });

  y += 6;

  // ── SECTION 2 : RETENUES ─────────────────────────────────────
  y = sectionTitle('Retenues Réglementaires', y);

  // En-tête colonnes 3
  doc.setFont('helvetica','bold');
  doc.setFontSize(7.5);
  doc.setTextColor(...C_GRAY);
  doc.text('NATURE', margin + 2, y);
  doc.text('TAUX', margin + colW * 0.62, y, { align: 'center' });
  doc.text('MONTANT', W - margin - 2, y, { align: 'right' });
  y += 5;

  y = tableRow3('CMR — Caisse Marocaine des Retraites', '10,00%', '− <?= $retCmr ?>', y);
  y = tableRow3('AMO + Mutuelle de la Fonction Publique', '5,00%', '− <?= $retMut ?>', y);
  y = tableRow3('Impôt sur le Revenu (Barème progressif CGI)', 'Variable', '− <?= $retIr ?>', y);
  y = tableRow3('TOTAL RETENUES', '<?= $tauxRet1 ?>%', '− <?= $retTotal ?>', y, {
    bg: C_RED2,
    bold: true,
    labelColor: C_RED,
    valueColor: C_RED
  });

  y += 8;

  // ── NET À PAYER ───────────────────────────────────────────────
  doc.setFillColor(...C_DARK);
  doc.roundedRect(margin, y, colW, 24, 3, 3, 'F');

  doc.setFont('helvetica','normal');
  doc.setFontSize(7.5);
  doc.setTextColor(147, 197, 253);
  doc.text('NET À PAYER', W / 2, y + 7, { align: 'center' });

  doc.setFont('helvetica','bold');
  doc.setFontSize(20);
  doc.setTextColor(...C_WHITE);
  doc.text('<?= $netPayer ?>', W / 2, y + 16, { align: 'center' });

  doc.setFont('helvetica','normal');
  doc.setFontSize(7.5);
  doc.setTextColor(147, 197, 253);
  doc.text('Taux de retenue global : <?= $tauxRet ?>%', W / 2, y + 21.5, { align: 'center' });

  y += 32;

  // ── CONSEIL ───────────────────────────────────────────────────
  doc.setFillColor(...C_ALERT_BG);
  doc.roundedRect(margin, y, colW, 18, 2, 2, 'F');

  doc.setFont('helvetica','bold');
  doc.setFontSize(8);
  doc.setTextColor(...C_ALERT_TXT);
  doc.text('Analyse & Conseil', margin + 4, y + 6);

  doc.setFont('helvetica','normal');
  doc.setFontSize(8);
  const conseilLines = doc.splitTextToSize('<?= $conseil ?>', colW - 8);
  doc.text(conseilLines, margin + 4, y + 12);

  y += 26;

  // ── PIED DE PAGE ──────────────────────────────────────────────
  doc.setDrawColor(...C_LGRAY);
  doc.setLineWidth(0.5);
  doc.line(margin, y, W - margin, y);

  doc.setFont('helvetica','normal');
  doc.setFontSize(7);
  doc.setTextColor(...C_GRAY);
  doc.text('Document simulatif à titre indicatif — Non contractuel', W / 2, y + 5, { align: 'center' });
  doc.text('Consultez le Service de la Paie pour toute information officielle.', W / 2, y + 9, { align: 'center' });
  doc.text('© <?= date('Y') ?> MJCC — Système de Simulation de Rémunération v<?= APP_VERSION ?>', W / 2, y + 13, { align: 'center' });

  // ── Numéro de page ────────────────────────────────────────────
  doc.setFontSize(7);
  doc.setTextColor(200, 200, 200);
  doc.text('Page 1 / 1', W - margin, 290, { align: 'right' });

  // ── Téléchargement ────────────────────────────────────────────
  const filename = 'bulletin_mjcc_<?= $ref ?>_<?= date('Ymd') ?>.pdf';
  doc.save(filename);

  // Rediriger vers résultat après téléchargement
  setTimeout(() => {
    window.location.href = '<?= BASE_PATH ?>/index.php?page=resultat&id=<?= $simId ?>';
  }, 1500);
});
</script>
</body>
</html>