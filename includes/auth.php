<?php
/**
 * MJCC — auth.php complet v2.1
 * Ajout : estInvite(), estAgent()
 */

require_once __DIR__ . '/../config/config.php';

function demarrerSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => BASE_PATH . '/',
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
        if (!isset($_SESSION['_regen']) || time() - $_SESSION['_regen'] > 300) {
            session_regenerate_id(true);
            $_SESSION['_regen'] = time();
        }
    }
}

function exigerConnexion(?string $role = null): void
{
    demarrerSession();
    if (empty($_SESSION['user_id'])) {
        redirect('login', ['msg' => 'session_expiree']);
    }
    if (time() - ($_SESSION['login_time'] ?? 0) > SESSION_LIFETIME) {
        deconnecterUtilisateur();
        redirect('login', ['msg' => 'session_expiree']);
    }
    if ($role && ($_SESSION['user_role'] ?? '') !== $role) {
        http_response_code(403);
        exit('Accès refusé.');
    }
}

function estConnecte(): bool
{
    demarrerSession();
    return !empty($_SESSION['user_id']);
}

function estAdmin(): bool
{
    return ($_SESSION['user_role'] ?? '') === 'admin';
}

function estAgent(): bool
{
    return ($_SESSION['user_role'] ?? '') === 'agent';
}

function estInvite(): bool
{
    return ($_SESSION['user_role'] ?? '') === 'invite';
}

function getUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function authentifier(string $email, string $password): array|false
{
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = :email AND actif = 1 LIMIT 1');
    $stmt->execute([':email' => trim(strtolower($email))]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        unset($user['password']);
        return $user;
    }
    return false;
}

function connecterUtilisateur(array $user): void
{
    $_SESSION['user']       = $user;
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['user_nom']   = $user['nom'];
    $_SESSION['login_time'] = time();
    logAction($user['id'], 'LOGIN', ['email' => $user['email']]);
}

function deconnecterUtilisateur(): void
{
    if (!empty($_SESSION['user_id'])) {
        logAction($_SESSION['user_id'], 'LOGOUT');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 86400,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfCheck(string $token): bool
{
    return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrfToken()) . '">';
}

function logAction(?int $userId, string $action, array $details = []): void
{
    try {
        $db   = getDB();
        $stmt = $db->prepare(
            'INSERT INTO logs (user_id, action, details, ip)
             VALUES (:uid, :act, :det, :ip)'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':act' => strtoupper($action),
            ':det' => !empty($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
            ':ip'  => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Exception $e) {
        error_log('Log error: ' . $e->getMessage());
    }
}