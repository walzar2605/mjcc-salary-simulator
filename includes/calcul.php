<?php
/**
 * MJCC — calcul.php v2.3
 * Barème IR 2026 (LF 2025) — Déductions famille 500 DH/pers (LF 2025)
 * AMO obligatoire toujours incluse — Mutuelle = part complémentaire uniquement
 */

require_once __DIR__ . '/../config/config.php';

// ── CONSTANTES MUTUELLES ─────────────────────────────────────
// L'AMO (2,5% via CNOPS) est TOUJOURS obligatoire pour les fonctionnaires.
// Le choix ici concerne uniquement la mutuelle complémentaire (MGPAP, MGEN…)
// 'aucune' = AMO seule, sans mutuelle complémentaire
define('MUTUELLES', [
    'aucune' => ['libelle' => 'AMO seule (sans mutuelle complémentaire)', 'taux_mutuelle' => 0.00],
    'mgpap'  => ['libelle' => 'MGPAP',   'taux_mutuelle' => 0.025],
    'mgen'   => ['libelle' => 'MGEN',    'taux_mutuelle' => 0.025],
    'douanes'=> ['libelle' => 'DOUANES', 'taux_mutuelle' => 0.025],
    'police' => ['libelle' => 'POLICE',  'taux_mutuelle' => 0.025],
    'faux'   => ['libelle' => 'F.AUX',   'taux_mutuelle' => 0.025],
    'omfam'  => ['libelle' => 'OMFAM',   'taux_mutuelle' => 0.025],
]);

// ── CACHE GRADES ─────────────────────────────────────────────
function getGradesCaches(): array
{
    $cacheKey = 'mjcc_grades_actifs';
    if (function_exists('apcu_fetch')) {
        $grades = apcu_fetch($cacheKey, $success);
        if ($success) return $grades;
    }
    $grades = getDB()
        ->query('SELECT * FROM grades WHERE actif=1 ORDER BY CAST(echelle AS UNSIGNED) DESC, libelle ASC')
        ->fetchAll();
    if (function_exists('apcu_store')) apcu_store($cacheKey, $grades, 3600);
    return $grades;
}

function invaliderCacheGrades(): void
{
    if (function_exists('apcu_delete')) apcu_delete('mjcc_grades_actifs');
}

// ── CALCULS ───────────────────────────────────────────────────
function calculerIndiceBrut(array $grade, int $echelon): int
{
    $echelon = max(1, min(12, $echelon));
    $prog    = ($grade['indice_maximal'] - $grade['indice_minimal']) / 11;
    return (int) round($grade['indice_minimal'] + ($echelon - 1) * $prog);
}

function calculerTraitementBase(int $indice): float
{
    return round($indice * VALEUR_POINT, 2);
}

function calculerCMR(float $base): float
{
    return round($base * TAUX_CMR, 2);
}

function calculerMutuelle(float $base, string $mutuelleOrg = 'aucune'): float
{
    $mutuelles  = MUTUELLES;
    $taux_mut   = $mutuelles[$mutuelleOrg]['taux_mutuelle'] ?? 0.00;
    $taux_total = TAUX_AMO + $taux_mut; // AMO obligatoire + mutuelle complémentaire
    return round($base * $taux_total, 2);
}

function calculerDeductionsFamille(string $situation, int $nbEnfants): float
{
    $d = 0.0;
    if (str_starts_with($situation, 'marie')) $d += DEDUCTION_CONJOINT;
    $d += min($nbEnfants, 6) * DEDUCTION_ENFANT; // Plafond légal : 6 personnes (Art. 74 CGI)
    return $d;
}

function calculerIR(float $base, float $cmr, float $mutuelle, string $situation, int $nbEnfants): float
{
    // ── Étape 1 : Revenu brut imposable annuel ────────────────────────────────
    // CMR et AMO/Mutuelle sont des cotisations obligatoires déductibles de l'assiette IR
    $brutAnnuel = ($base - $cmr - $mutuelle) * 12;

    // ── Étape 2 : Abattement frais professionnels (Art. 59 CGI) ──────────────
    // 17% du revenu brut imposable, plafonné à 26 000 MAD/an
    $abattement   = min($brutAnnuel * ABATTEMENT_FP, ABATTEMENT_FP_MAX);
    $netImposable = max(0, $brutAnnuel - $abattement);

    // ── Étape 3 : Application du barème (méthode rapide officielle DGI) ──────
    // On parcourt toutes les tranches et on garde la dernière dont le seuil min
    // est dépassé — ce qui donne la tranche dans laquelle se trouve le RNI.
    // Formule : IR brut = (RNI × taux_tranche) - déduction_tranche
    // Cette méthode est mathématiquement équivalente au calcul progressif tranche
    // par tranche et est la méthode officielle publiée par la DGI Maroc.
    $irBrut = 0.0;
    foreach (BAREME_IR as $t) {
        if ($netImposable > $t['min']) {
            $irBrut = ($netImposable * $t['taux']) - $t['ded'];
        }
    }
    $irBrut = max(0.0, $irBrut);

    // ── Étape 4 : Déductions pour charges de famille (Art. 74 CGI — LF 2025) ─
    // 500 DH/an par personne à charge (conjoint + enfants), plafond 6 personnes
    $deducFam    = calculerDeductionsFamille($situation, $nbEnfants);
    $irAnnuelNet = max(0.0, $irBrut - $deducFam);

    // ── Étape 5 : Ramener à mensuel ───────────────────────────────────────────
    return round($irAnnuelNet / 12, 2);
}

// ── SIMULATION ────────────────────────────────────────────────
function simulerRemuneration(
    int    $gradeId,
    int    $echelon,
    string $situation,
    int    $nbEnfants,
    string $mutuelleOrg = 'aucune',
    string $corps       = ''
): array {
    $grade = null;
    foreach (getGradesCaches() as $g) {
        if ((int)$g['id'] === $gradeId) { $grade = $g; break; }
    }
    if (!$grade) {
        $stmt = getDB()->prepare('SELECT * FROM grades WHERE id = :id AND actif = 1');
        $stmt->execute([':id' => $gradeId]);
        $grade = $stmt->fetch();
    }
    if (!$grade) throw new InvalidArgumentException("Grade introuvable (ID: {$gradeId}).");

    $mutuelles  = MUTUELLES;
    $mutInfo    = $mutuelles[$mutuelleOrg] ?? $mutuelles['aucune'];
    $taux_mut   = TAUX_AMO + $mutInfo['taux_mutuelle'];

    $indice   = calculerIndiceBrut($grade, $echelon);
    $base     = calculerTraitementBase($indice);
    $indem    = (float)$grade['indemnite_base'];
    $cmr      = calculerCMR($base);
    $mutuelle = calculerMutuelle($base, $mutuelleOrg);
    $ir       = calculerIR($base, $cmr, $mutuelle, $situation, $nbEnfants);
    $retenues = $cmr + $mutuelle + $ir;
    $brut     = $base + $indem;
    $net      = $brut - $retenues;
    $tauxRet  = $brut > 0 ? round(($retenues / $brut) * 100, 2) : 0;
    $conseil  = genererConseil($tauxRet, $situation);
    $alerte   = $tauxRet < 20 ? 'success' : ($tauxRet < 30 ? 'warning' : 'danger');

    return [
        'grade'               => $grade,
        'echelon'             => $echelon,
        'situation_familiale' => $situation,
        'nb_enfants'          => $nbEnfants,
        'mutuelle_org'        => $mutuelleOrg,
        'mutuelle_libelle'    => $mutInfo['libelle'],
        'taux_mutuelle_total' => $taux_mut,
        'corps'               => $corps,
        'indice_brut'         => $indice,
        'traitement_base'     => $base,
        'indemnite_base'      => $indem,
        'primes_total'        => 0.0,
        'brut_total'          => $brut,
        'retenue_cmr'         => $cmr,
        'retenue_mutuelle'    => $mutuelle,
        'retenue_ir'          => $ir,
        'retenues_total'      => $retenues,
        'net_a_payer'         => $net,
        'taux_retenue'        => $tauxRet,
        'niveau_alerte'       => $alerte,
        'message_conseil'     => $conseil,
    ];
}

// ── PERSISTANCE ───────────────────────────────────────────────
function sauvegarderSimulation(int $userId, array $r): int
{
    $db   = getDB();
    $stmt = $db->prepare(
        'INSERT INTO simulations
         (user_id, grade_id, echelon, situation_familiale, nb_enfants,
          corps, mutuelle_org, nom_employe,
          indice_brut, traitement_base, indemnite_base, primes_total, brut_total,
          retenue_cmr, retenue_mutuelle, retenue_ir, retenues_total,
          net_a_payer, taux_retenue, niveau_alerte, message_conseil)
         VALUES
         (:uid,:gid,:ech,:sit,:nb,:corps,:mut,:nomEmp,:ind,:base,:indem,:pri,:brut,
          :cmr,:mutret,:ir,:ret,:net,:taux,:alerte,:conseil)'
    );
    $stmt->execute([
        ':uid'    => $userId,
        ':gid'    => $r['grade']['id'],
        ':ech'    => $r['echelon'],
        ':sit'    => $r['situation_familiale'],
        ':nb'     => $r['nb_enfants'],
        ':corps'  => $r['corps'] ?? '',
        ':mut'    => $r['mutuelle_org'] ?? 'aucune',
        ':nomEmp' => $r['nom_employe'] ?? '',
        ':ind'    => $r['indice_brut'],
        ':base'   => $r['traitement_base'],
        ':indem'  => $r['indemnite_base'],
        ':pri'    => $r['primes_total'],
        ':brut'   => $r['brut_total'],
        ':cmr'    => $r['retenue_cmr'],
        ':mutret' => $r['retenue_mutuelle'],
        ':ir'     => $r['retenue_ir'],
        ':ret'    => $r['retenues_total'],
        ':net'    => $r['net_a_payer'],
        ':taux'   => $r['taux_retenue'],
        ':alerte' => $r['niveau_alerte'],
        ':conseil'=> $r['message_conseil'],
    ]);

    logAction($userId, 'CREATE_SIMULATION', [
        'grade' => $r['grade']['libelle'],
        'corps' => $r['corps'] ?? '',
        'net'   => $r['net_a_payer'],
    ]);

    return (int)$db->lastInsertId();
}

// ── HELPERS ───────────────────────────────────────────────────
function genererConseil(float $taux, string $situation): string
{
    if ($taux < 15) return "Excellent : Votre taux de retenue est très favorable.";
    if ($taux < 20) return "Votre taux de retenue est optimal. Situation fiscale favorable.";
    if ($taux < 28) {
        if ($situation === 'celibataire')
            return "Taux modéré. En situation de famille, vous bénéficieriez de déductions supplémentaires sur l'IR.";
        return "Taux modéré. Assurez-vous que toutes vos charges de famille sont déclarées auprès de la DGI.";
    }
    if ($taux < 35) return "Taux de retenue élevé. Consultez le service RH pour vérifier les exonérations applicables.";
    return "⚠ Attention : Taux de retenue très élevé (" . number_format($taux, 1) . "%). Une révision de votre situation fiscale est fortement recommandée.";
}

function fmt(float $n): string
{
    return number_format($n, 2, ',', ' ') . ' MAD';
}

function libelleSituation(string $code): string
{
    return match($code) {
        'celibataire'       => 'Célibataire',
        'marie_sans_enfant' => 'Marié(e) sans enfant',
        'marie_1enfant'     => 'Marié(e) — 1 enfant',
        'marie_2enfants'    => 'Marié(e) — 2 enfants',
        'marie_3enfants'    => 'Marié(e) — 3 enfants',
        'marie_4enfants'    => 'Marié(e) — 4 enfants',
        'marie_5enfants'    => 'Marié(e) — 5 enfants',
        'marie_6enfants'    => 'Marié(e) — 6 enfants et +',
        default             => ucfirst(str_replace('_', ' ', $code)),
    };
}

function libelleCorps(string $code): string
{
    $corps = [
        'direction_jeunesse'      => 'Direction de la Jeunesse',
        'div_etablissements'      => 'Division des établissements de jeunesse',
        'div_programmes_jeunesse' => 'Division des programmes de jeunesse',
        'div_colonies'            => 'Division des colonies de vacances',
        'direction_enfance'       => 'Direction de l\'Enfance et des affaires féminines',
        'div_affaires_feminines'  => 'Division des affaires féminines',
        'div_enfance'             => 'Division de l\'Enfance',
        'div_protection_enfance'  => 'Division de la protection de l\'Enfance',
        'direction_admin'         => 'Direction des Affaires Administratives et Générales',
        'div_rh'                  => 'Division des ressources humaines',
        'div_budget'              => 'Division du budget et de la comptabilité',
        'div_si'                  => 'Division des systèmes d\'information',
        'div_installations'       => 'Division des installations et des équipements',
        'div_documentation'       => 'Division de la documentation et de la gestion des risques',
        'direction_cooperation'   => 'Direction de la Coopération, Communication et Études Juridiques',
        'div_cooperation'         => 'Division de la coopération et du partenariat',
        'div_communication'       => 'Division de la Communication',
        'div_juridique'           => 'Division des affaires juridiques',
        'secretariat_general'     => 'Secrétariat Général',
        'inspection_generale'     => 'Inspection Générale',
        'div_planification'       => 'Division de la Planification Stratégique et du Contrôle de Gestion',
    ];
    return $corps[$code] ?? $code;
}