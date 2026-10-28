<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $role = $_SESSION['role'] ?? 'user';
    if ($role !== 'admin') {
        $role = 'user';
    }

    return [
        'id' => (int) $_SESSION['user_id'],
        'username' => (string) ($_SESSION['username'] ?? 'Player'),
        'email' => (string) ($_SESSION['email'] ?? ''),
        'role' => $role,
    ];
}

function is_admin(): bool
{
    $user = current_user();

    return $user !== null && $user['role'] === 'admin';
}

function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        redirect('login.php');
    }

    return $user;
}

function require_admin(): array
{
    $user = require_login();
    $stmt = db()->prepare('SELECT role FROM users WHERE id = :id');
    $stmt->execute([':id' => $user['id']]);
    $role = $stmt->fetchColumn();

    if ($role !== 'admin') {
        redirect('home.php');
    }

    $_SESSION['role'] = 'admin';
    $user['role'] = 'admin';

    return $user;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['username'] = (string) $user['username'];
    $_SESSION['email'] = (string) $user['email'];
    $_SESSION['role'] = ($user['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
    $_SESSION['run_score'] = 0;
    $_SESSION['run_total'] = 0;
    unset($_SESSION['puzzle_solution']);
}

function run_stats(): array
{
    $score = (int) ($_SESSION['run_score'] ?? 0);
    $total = (int) ($_SESSION['run_total'] ?? 0);

    return [
        'score' => $score,
        'total' => $total,
        'accuracy' => format_accuracy($score, $total),
    ];
}

function reset_run(): void
{
    $_SESSION['run_score'] = 0;
    $_SESSION['run_total'] = 0;
    unset($_SESSION['puzzle_solution']);
}

function login_is_locked(): bool
{
    return !empty($_SESSION['lock_until']) && time() < (int) $_SESSION['lock_until'];
}

function login_lock_remaining(): int
{
    return max(0, (int) ($_SESSION['lock_until'] ?? 0) - time());
}

function login_record_failure(): void
{
    $fails = (int) ($_SESSION['login_fails'] ?? 0) + 1;
    $_SESSION['login_fails'] = $fails;
    if ($fails >= 5) {
        $_SESSION['lock_until'] = time() + 300;
    }
}

function login_clear_failures(): void
{
    unset($_SESSION['login_fails'], $_SESSION['lock_until']);
}
