<?php

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function base_url(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');

    if (substr($dir, -4) === '/api') {
        $dir = substr($dir, 0, -4);
    }

    if ($dir === '/' || $dir === '.' || $dir === '') {
        return '';
    }

    return $dir;
}

function url(string $path): string
{
    return base_url() . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }

    return $_SESSION['csrf'];
}

function csrf_valid(): bool
{
    $sent = $_POST['csrf'] ?? '';
    $stored = $_SESSION['csrf'] ?? '';

    return is_string($sent) && is_string($stored) && $stored !== '' && hash_equals($stored, $sent);
}

function validate_username(string $username): ?string
{
    if ($username === '') {
        return 'Please enter a username';
    }
    if (strlen($username) < 3) {
        return 'Username must be at least 3 characters';
    }
    if (strlen($username) > 50) {
        return 'Username must be 50 characters or fewer';
    }

    return null;
}

function validate_email(string $email): ?string
{
    if ($email === '') {
        return 'Please enter your email';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Please enter a valid email';
    }

    return null;
}

function validate_password(string $password): ?string
{
    if ($password === '') {
        return 'Please enter your password';
    }
    if (strlen($password) < 6) {
        return 'Password must be at least 6 characters';
    }

    return null;
}

function format_accuracy(int $score, int $total): string
{
    if ($total <= 0) {
        return '0';
    }

    return number_format(($score / $total) * 100, 1, '.', '');
}

function format_date(string $value, bool $withTime = false): string
{
    $time = strtotime($value);
    if ($time === false) {
        return '';
    }

    return $withTime ? date('H:i', $time) : date('j/n/Y', $time);
}

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode($data);
    exit;
}
