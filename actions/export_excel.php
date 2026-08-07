<?php
/**
 * MJCC — Export Excel (CSV UTF-8 BOM → ouverture native dans Excel)
 * Aucune librairie externe requise.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/calcul.php';

exigerConnexion();

$db    = getDB();
$uid   = (int)$_SESSION['user_id'];
$isAdm = estAdmin();

// ── Filtres ───────────────────────────────────────────────────────────────
$fGrade  = (int)($_GET['grade_id']   ?? 0);
$fAgent  = (int)($_GET['agent_id']   ?? 0);
$fNomEmp = trim($_GET['nom_employe'] ?? '');
$fDateDe = $_GET['date_de']          ?? '';
$fDateA  = $_GET['date_a']           ?? '';

$where  = $isAdm ? [] : ['s.user_id = :uid'];
$params = $isAdm ? [] : [':uid' => $uid];

if ($fGrade  > 0)            { $where[] = 's.grade_id = :gid';   $params[':gid']  = $fGrade; }
if ($fAgent  > 0 && $isAdm)  { $where[] = 's.user_id = :aid';   $params[':aid']  = $fAgent; }
if ($fNomEmp !== '')          { $where[] = '(s.nom_employe LIKE :nmp OR u.nom LIKE :nmp2)';
                                $params[':nmp'] = "%$fNomEmp%"; $params[':nmp2'] = "%$fNomEmp%"; }
if ($fDateDe !== '')          { $where[] = 'DATE(s.date_simulation) >= :dde'; $params[':dde'] = $fDateDe; }
if ($fDateA  !== '')          { $where[] = 'DATE(s.date_simulation) <= :da';  $params[':da']  = $fDateA; }

$wSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare("
    SELECT s.id, s.echelon, s.situation_familiale, s.nb_enfants,
           s.nom_employe, s.mutuelle_org,
           s.indice_brut, s.traitement_base, s.indemnite_base,
           s.retenues_total, s.net_a_payer, s.taux_retenue,
           s.date_simulation,
           g.libelle AS grade_libelle, g.echelle,
           u.nom AS agent_nom
    FROM simulations s
    JOIN grades g ON s.grade_id = g.id
    JOIN users  u ON s.user_id  = u.id
    {$wSQL}
    ORDER BY s.date_simulation DESC
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

logAction($uid, 'EXPORT_EXCEL', ['nb_lignes' => count($rows)]);

// ── Headers HTTP ──────────────────────────────────────────────────────────
$filename = 'mjcc_historique_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: no-cache');

$out = fopen('php://output', 'w');

// BOM UTF-8 (Excel détecte l'encodage correct)
fwrite($out, "\xEF\xBB\xBF");

// Directive séparateur — force Excel à utiliser ";" comme délimiteur
fwrite($out, "sep=;\r\n");

// ── Ligne d'info ──────────────────────────────────────────────────────────
fputcsv($out, [
    'MJCC — Historique des Simulations de Rémunération',
    'Exporté le : ' . date('d/m/Y à H:i'),
    count($rows) . ' simulation(s)',
    $isAdm ? 'Vue Administrateur' : 'Vue Agent',
], ';');

fputcsv($out, [], ';'); // ligne vide

// ── En-têtes colonnes ─────────────────────────────────────────────────────
fputcsv($out, [
    'Réf.',
    'Date',
    'Agent',
    'Employé simulé',
    'Grade',
    'Échelle',
    'Échelon',
    'Situation familiale',
    'Nb enfants',
    'Mutuelle',
    'Indice',
    'Traitement de base (MAD)',
    'Indemnité (MAD)',
    'Total retenues (MAD)',
    'Net à payer (MAD)',
    'Taux retenue (%)',
], ';');

// ── Données ───────────────────────────────────────────────────────────────
foreach ($rows as $r) {
    fputcsv($out, [
        'SIM-' . str_pad($r['id'], 5, '0', STR_PAD_LEFT),
        date('d/m/Y H:i', strtotime($r['date_simulation'])),
        $r['agent_nom'],
        !empty($r['nom_employe']) ? $r['nom_employe'] : '',
        $r['grade_libelle'],
        $r['echelle'],
        (int)$r['echelon'],
        libelleSituation($r['situation_familiale']),
        (int)$r['nb_enfants'],
        MUTUELLES[$r['mutuelle_org'] ?? 'aucune']['libelle'] ?? '',
        (int)$r['indice_brut'],
        number_format((float)$r['traitement_base'], 2, ',', ' '),
        number_format((float)$r['indemnite_base'],  2, ',', ' '),
        number_format((float)$r['retenues_total'],  2, ',', ' '),
        number_format((float)$r['net_a_payer'],     2, ',', ' '),
        number_format((float)$r['taux_retenue'],    1, ',', ''),
    ], ';');
}

fclose($out);
exit;