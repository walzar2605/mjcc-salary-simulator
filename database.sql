-- ============================================================
-- MJCC — Script SQL complet
-- Base de données : m_salary_db
-- ============================================================

CREATE DATABASE IF NOT EXISTS m_salary_db
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE m_salary_db;

CREATE TABLE IF NOT EXISTS users (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom        VARCHAR(100)  NOT NULL,
    email      VARCHAR(150)  NOT NULL,
    password   VARCHAR(255)  NOT NULL,
    role       ENUM('admin','agent') NOT NULL DEFAULT 'agent',
    actif      TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_email (email),
    INDEX idx_role  (role),
    INDEX idx_actif (actif)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS grades (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libelle        VARCHAR(150) NOT NULL,
    echelle        VARCHAR(20)  NOT NULL DEFAULT '10',
    indice_minimal INT UNSIGNED NOT NULL,
    indice_maximal INT UNSIGNED NOT NULL DEFAULT 0,
    indemnite_base DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    actif          TINYINT(1) NOT NULL DEFAULT 1,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS simulations (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED NOT NULL,
    grade_id            INT UNSIGNED NOT NULL,
    echelon             TINYINT UNSIGNED NOT NULL DEFAULT 1,
    situation_familiale ENUM('celibataire','marie_sans_enfant','marie_1enfant','marie_2enfants','marie_3enfants','marie_4enfants') NOT NULL DEFAULT 'celibataire',
    nb_enfants          TINYINT UNSIGNED NOT NULL DEFAULT 0,
    indice_brut         INT UNSIGNED NOT NULL DEFAULT 0,
    traitement_base     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    indemnite_base      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    primes_total        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    brut_total          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    retenue_cmr         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    retenue_mutuelle    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    retenue_ir          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    retenues_total      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    net_a_payer         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    taux_retenue        DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
    niveau_alerte       ENUM('success','warning','danger') NOT NULL DEFAULT 'success',
    message_conseil     TEXT NULL,
    date_simulation     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (grade_id) REFERENCES grades(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_user_id         (user_id),
    INDEX idx_grade_id        (grade_id),
    INDEX idx_date_simulation (date_simulation)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS logs (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NULL,
    action     VARCHAR(100) NOT NULL,
    details    TEXT NULL,
    ip         VARCHAR(45)  NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action  (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ── Grades (10 grades FP marocaine) ──────────────────────────
INSERT INTO grades (libelle, echelle, indice_minimal, indice_maximal, indemnite_base) VALUES
('Adjoint Administratif',       '6',             244,  390,  800.00),
('Secrétaire Administratif',    '7',             280,  440, 1000.00),
('Technicien',                  '8',             320,  500, 1200.00),
('Rédacteur',                   '9',             370,  560, 1400.00),
('Attaché Administratif',       '10',            420,  620, 1600.00),
('Inspecteur',                  '10',            460,  670, 1700.00),
('Administrateur',              '11',            510,  750, 2000.00),
('Administrateur Principal',    '11',            580,  830, 2200.00),
('Chef de Division',            'HORS ECHELLE',  700,  960, 2800.00),
('Directeur de Service',        'HORS ECHELLE',  820, 1100, 3500.00);

-- ── Comptes de test ───────────────────────────────────────────
-- Mot de passe pour tous : Admin@2024
-- Hash généré avec password_hash('Admin@2024', PASSWORD_BCRYPT, ['cost'=>10])
INSERT INTO users (nom, email, password, role, actif) VALUES
('Administrateur MJCC',   'admin@mjcc.gov.ma',         '$2y$10$YourHashHere', 'admin', 1),
('Ahmed Benali',          'a.benali@mjcc.gov.ma',      '$2y$10$YourHashHere', 'agent', 1),
('Fatima Zahra Idrissi',  'f.idrissi@mjcc.gov.ma',     '$2y$10$YourHashHere', 'agent', 1),
('Youssef El Mansouri',   'y.elmansouri@mjcc.gov.ma',  '$2y$10$YourHashHere', 'agent', 0);

-- IMPORTANT : Après import, exécutez ce script pour générer les vrais hash :
-- UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
-- Ce hash correspond au mot de passe : password
-- Changez-le immédiatement en production !
