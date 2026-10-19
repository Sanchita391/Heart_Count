<?php

function is_allowed_puzzle_url(string $url): bool
{
    $parts = parse_url($url);
    if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https') {
        return false;
    }

    $host = strtolower($parts['host'] ?? '');

    return in_array($host, ['www.sanfoh.com', 'sanfoh.com', 'marcconrad.com', 'www.marcconrad.com'], true);
}

function fetch_puzzle(): ?array
{
    for ($attempt = 0; $attempt < 3; $attempt++) {
        $puzzle = request_puzzle();
        if ($puzzle !== null) {
            return $puzzle;
        }
    }

    return null;
}

function request_puzzle(): ?array
{
    // HTTP GET to the Banana API. Curl runs on the PHP server, not in the browser.
    $ch = curl_init(BANANA_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'HeartCount/1.0',
    ]);

    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!is_string($body) || $status !== 200) {
        return null;
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        return null;
    }

    $image = $data['question'] ?? '';
    if (!is_string($image) || !is_allowed_puzzle_url($image)) {
        return null;
    }

    if (!is_numeric($data['solution'] ?? null)) {
        return null;
    }

    $solution = (int) $data['solution'];
    if ($solution < 0 || $solution > 9) {
        return null;
    }

    return [
        'image' => $image,
        'solution' => $solution,
    ];
}

function record_answer(int $userId, int $answer, int $correct, bool $timedOut = false, ?bool $wasCorrect = null): bool
{
    $isCorrect = $wasCorrect === null ? (!$timedOut && $answer === $correct) : $wasCorrect;
    $correctFlag = $isCorrect ? 1 : 0;
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $insert = $pdo->prepare(
            'INSERT INTO game_sessions (user_id, user_answer, correct_answer, is_correct, timed_out)
             VALUES (:user_id, :user_answer, :correct_answer, :is_correct, :timed_out)'
        );
        $insert->execute([
            ':user_id' => $userId,
            ':user_answer' => $answer,
            ':correct_answer' => $correct,
            ':is_correct' => $correctFlag,
            ':timed_out' => $timedOut ? 1 : 0,
        ]);

        $update = $pdo->prepare(
            'UPDATE users
             SET total_games = total_games + 1,
                 games_won = games_won + :won,
                 games_lost = games_lost + :lost
             WHERE id = :id'
        );
        $update->execute([
            ':won' => $correctFlag,
            ':lost' => $correctFlag ? 0 : 1,
            ':id' => $userId,
        ]);

        $pdo->commit();
        return true;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return false;
    }
}

function user_stats(int $userId): ?array
{
    $pdo = db();
    $userStmt = $pdo->prepare(
        'SELECT id, username, email, total_games, games_won, games_lost, created_at
         FROM users WHERE id = :id'
    );
    $userStmt->execute([':id' => $userId]);
    $user = $userStmt->fetch();

    if (!$user) {
        return null;
    }

    $gamesStmt = $pdo->prepare(
        'SELECT user_answer, correct_answer, is_correct, timed_out, created_at
         FROM game_sessions
         WHERE user_id = :id
         ORDER BY created_at DESC, id DESC
         LIMIT 5'
    );
    $gamesStmt->execute([':id' => $userId]);

    return [
        'user' => $user,
        'recent_games' => $gamesStmt->fetchAll(),
    ];
}

function list_players(): array
{
    return db()->query(
        'SELECT id, username, email, role, total_games, games_won, games_lost, last_login, created_at
         FROM users
         ORDER BY role DESC, username ASC'
    )->fetchAll();
}

function recent_games_for_all(): array
{
    return db()->query(
        'SELECT game_sessions.user_answer, game_sessions.correct_answer, game_sessions.is_correct,
                game_sessions.timed_out, game_sessions.created_at, users.username
         FROM game_sessions
         INNER JOIN users ON users.id = game_sessions.user_id
         ORDER BY game_sessions.created_at DESC, game_sessions.id DESC
         LIMIT 8'
    )->fetchAll();
}

function delete_player(int $adminId, int $userId): bool
{
    if ($adminId === $userId) {
        return false;
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = :id');
    $stmt->execute([':id' => $userId]);
    if ($stmt->fetchColumn() !== 'user') {
        return false;
    }

    $pdo->beginTransaction();
    try {
        $games = $pdo->prepare('DELETE FROM game_sessions WHERE user_id = :id');
        $games->execute([':id' => $userId]);
        $matches = $pdo->prepare('DELETE FROM matches WHERE user_id = :id');
        $matches->execute([':id' => $userId]);
        $user = $pdo->prepare('DELETE FROM users WHERE id = :id AND role = :role');
        $user->execute([':id' => $userId, ':role' => 'user']);
        $pdo->commit();

        return $user->rowCount() === 1;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return false;
    }
}
