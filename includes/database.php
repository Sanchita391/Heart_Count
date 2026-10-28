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

    $email = 'admin@gmail.com';
    $hash = password_hash('123456', PASSWORD_DEFAULT);

    $byEmail = $pdo->prepare('SELECT id FROM users WHERE email = :email');
    $byEmail->execute([':email' => $email]);
    $row = $byEmail->fetch();

    if ($row) {
        $role = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = :id");
        $role->execute([':id' => $row['id']]);
        return;
    }

    $admin = $pdo->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1")->fetch();
    if ($admin) {
        $update = $pdo->prepare(
            'UPDATE users SET email = :email, password = :password WHERE id = :id'
        );
        $update->execute([
            ':email' => $email,
            ':password' => $hash,
            ':id' => $admin['id'],
        ]);
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
        ':password' => $hash,
        ':role' => 'admin',
    ]);
}
