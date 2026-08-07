<?php
/**
 * MJCC — Configuration Centrale
 * Connexion PDO + Constantes réglementaires marocaines
 */

// ── Chemin de base de l'application (à adapter si besoin) ────
define('BASE_PATH', '/mjcc_fixed');
define('BASE_URL',  'http://localhost/mjcc_fixed');

// ── Connexion DB ─────────────────────────────────────────────
// ⚠️  SÉCURITÉ : Ne jamais utiliser root/'' en production.
// Créer un utilisateur MySQL dédié avec droits limités à m_salary_db uniquement.
// Exemple : CREATE USER 'mjcc_app'@'localhost' IDENTIFIED BY 'MotDePasseFort';
//           GRANT SELECT, INSERT, UPDATE, DELETE ON m_salary_db.* TO 'mjcc_app'@'localhost';
define('DB_HOST',    'localhost');
define('DB_NAME',    'm_salary_db');
define('DB_USER', 'root');
define('DB_PASS',  '');
define('DB_CHARSET', 'utf8mb4');

// ── Application ──────────────────────────────────────────────
define('APP_NAME',    'Simulation de Rémunération');
define('APP_ACRONYM', 'MJCC');
define('APP_VERSION', '2.0.0');
define('SESSION_LIFETIME', 3600);

// ── Taux de cotisation réglementaires (Maroc — Fonction Publique) ──────────
// CMR : taux pension civile salarial — Caisse Marocaine des Retraites
// AMO : CNOPS décret 2-05-735 — part salariale 2,5% (obligatoire)
// Mutuelle : part complémentaire MGPAP/MGEN etc. — part salariale 2,5%
define('TAUX_CMR',     0.10);
define('TAUX_AMO',     0.025);
define('TAUX_MUTUELLE',0.025);
define('VALEUR_POINT', 51.40); // Valeur du point d'indice FP Maroc (MAD) — à réviser selon décrets

// ── Abattement pour frais professionnels (Art. 59 CGI) ────────────────────
// 17% du revenu brut imposable, plafonné à 26 000 MAD/an
define('ABATTEMENT_FP',     0.17);
define('ABATTEMENT_FP_MAX', 26000.00);

// ── Déductions pour charges de famille (Art. 74 CGI — LF 2025) ────────────
// LF 2025 : réduction portée de 360 DH à 500 DH/an par personne à charge
// Applicable dès janvier 2025 — conjoint + enfants (max 6 personnes)
define('DEDUCTION_CONJOINT', 500.00);
define('DEDUCTION_ENFANT',   500.00);

// ── Barème IR 2026 annuel ──────────────────────────────────────────────────
// Source : LF 2025 applicable au 1er janvier 2025, reconduit pour 2026
// Seuil exonéré relevé à 40 000 MAD, taux marginal abaissé à 37%
// Méthode de calcul rapide (officielle DGI) : IR brut = (RNI × taux) - déduction
define('BAREME_IR', [
    ['min' =>      0, 'max' =>  40000, 'taux' => 0.00, 'ded' =>     0],
    ['min' =>  40001, 'max' =>  60000, 'taux' => 0.10, 'ded' =>  4000],
    ['min' =>  60001, 'max' =>  80000, 'taux' => 0.20, 'ded' => 10000],
    ['min' =>  80001, 'max' => 100000, 'taux' => 0.30, 'ded' => 18000],
    ['min' => 100001, 'max' => 180000, 'taux' => 0.34, 'ded' => 22000],
    ['min' => 180001, 'max' => PHP_INT_MAX, 'taux' => 0.37, 'ded' => 27400],
]);

define('ITEMS_PER_PAGE', 15);

/**
 * Connexion PDO Singleton sécurisée
 */
function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('DB Error: ' . $e->getMessage());
            die('<p style="font-family:sans-serif;color:#B91C1C;padding:2rem">Erreur DB : ' . htmlspecialchars($e->getMessage()) . '</p>');
        }
    }
    return $pdo;
}

/**
 * Redirige vers une page interne du projet
 */
function redirect(string $page, array $params = []): void
{
    $url = BASE_PATH . '/index.php?page=' . $page;
    foreach ($params as $k => $v) {
        $url .= '&' . urlencode($k) . '=' . urlencode($v);
    }
    header('Location: ' . $url);
    exit;
}