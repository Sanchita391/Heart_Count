<?php

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $schema = file_get_contents(__DIR__ . '/../sql/schema.sql');
    if ($schema === false) {
        throw new RuntimeException('Database schema file is missing.');
    }

    foreach (preg_split('/;\s*\n/', $schema) as $statement) {
        $statement = trim($statement);
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }

    $pdo->exec('USE `' . DB_NAME . '`');
    ensure_roles($pdo);
    ensure_challenge($pdo);

    return $pdo;
}

function ensure_challenge(PDO $pdo): void
{
    $column = $pdo->query("SHOW COLUMNS FROM game_sessions LIKE 'timed_out'")->fetch();
    if (!$column) {
        $pdo->exec('ALTER TABLE game_sessions ADD COLUMN timed_out TINYINT(1) NOT NULL DEFAULT 0 AFTER is_correct');
    }
}

function ensure_roles(PDO $pdo): void
{
    $column = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch();
    if (!$column) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('user','admin') NOT NULL DEFAULT 'user' AFTER password");
    }

    $admins = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if ($admins > 0) {
        return;
    }

    $email = 'admin@heartcount.local';
    $existing = $pdo->prepare('SELECT id FROM users WHERE email = :email');
    $existing->execute([':email' => $email]);
    if ($existing->fetch()) {
        return;
    }

    $username = 'admin';
    $name = $pdo->prepare('SELECT id FROM users WHERE username = :username');
    $name->execute([':username' => $username]);
    if ($name->fetch()) {
        $username = 'heartadmin';
    }

    $insert = $pdo->prepare(
        'INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)'
    );
    $insert->execute([
        ':username' => $username,
        ':email' => $email,
        ':password' => password_hash('admin123', PASSWORD_DEFAULT),
        ':role' => 'admin',
    ]);
}
