<?php

function challenge_rules(string $difficulty): ?array
{
    $rules = [
        'easy' => ['label' => 'Easy', 'time_limit' => 20, 'lives' => 3, 'rounds' => 8],
        'medium' => ['label' => 'Medium', 'time_limit' => 12, 'lives' => 3, 'rounds' => 10],
        'hard' => ['label' => 'Hard', 'time_limit' => 7, 'lives' => 2, 'rounds' => 12],
    ];

    return $rules[$difficulty] ?? null;
}

function active_match(): ?array
{
    $match = $_SESSION['match'] ?? null;

    return is_array($match) ? $match : null;
}

function start_match(int $userId, string $difficulty): array
{
    $rules = challenge_rules($difficulty);
    if ($rules === null) {
        throw new InvalidArgumentException('Choose easy, medium, or hard.');
    }

    $current = active_match();
    if ($current && ($current['status'] ?? '') === 'active') {
        finish_match_record($current, 'abandoned');
    }

    $stmt = db()->prepare(
        'INSERT INTO matches (user_id, difficulty, status) VALUES (:user_id, :difficulty, :status)'
    );
    $stmt->execute([
        ':user_id' => $userId,
        ':difficulty' => $difficulty,
        ':status' => 'active',
    ]);

    $_SESSION['match'] = [
        'id' => (int) db()->lastInsertId(),
        'user_id' => $userId,
        'difficulty' => $difficulty,
        'time_limit' => $rules['time_limit'],
        'lives' => $rules['lives'],
        'max_lives' => $rules['lives'],
        'round' => 1,
        'round_limit' => $rules['rounds'],
        'score' => 0,
        'streak' => 0,
        'best_streak' => 0,
        'correct_count' => 0,
        'status' => 'active',
        'solution' => null,
        'image' => null,
        'issued_at' => null,
    ];

    return public_match();
}

function issue_puzzle(): array
{
    $match = require_active_match();

    if ($match['solution'] !== null && $match['image'] !== null && $match['issued_at'] !== null) {
        return public_puzzle($match);
    }

    $puzzle = fetch_puzzle();
    if ($puzzle === null) {
        throw new RuntimeException('The heart puzzle service is unavailable. Please try again.');
    }

    $match['solution'] = $puzzle['solution'];
    $match['image'] = $puzzle['image'];
    $match['issued_at'] = microtime(true);
    $_SESSION['match'] = $match;

    return public_puzzle($match);
}

function resolve_round(int $userId, ?int $answer, bool $clientTimeout): array
{
    $match = require_active_match();
    if ($match['solution'] === null || $match['issued_at'] === null) {
        throw new RuntimeException('This puzzle has already been answered.');
    }

    $elapsed = microtime(true) - (float) $match['issued_at'];
    $timedOut = $clientTimeout || $elapsed > ((float) $match['time_limit'] + 0.75);
    $correctAnswer = (int) $match['solution'];
    $isCorrect = !$timedOut && $answer !== null && $answer === $correctAnswer;
    $points = 0;

    if ($isCorrect) {
        $remaining = max(0, (float) $match['time_limit'] - $elapsed);
        $match['streak']++;
        $match['best_streak'] = max((int) $match['best_streak'], (int) $match['streak']);
        $match['correct_count']++;
        $points = 100 + (int) floor($remaining * 10) + (((int) $match['streak'] - 1) * 25);
        $match['score'] += $points;
    } else {
        $match['streak'] = 0;
        $match['lives']--;
    }

    $saved = record_answer($userId, $timedOut ? 0 : (int) $answer, $correctAnswer, $timedOut, $isCorrect);
    if (!$saved) {
        throw new RuntimeException('Your answer could not be saved. Please try again.');
    }

    $match['solution'] = null;
    $match['image'] = null;
    $match['issued_at'] = null;

    $reason = null;
    if ((int) $match['lives'] <= 0) {
        $reason = 'lives';
    } elseif ((int) $match['round'] >= (int) $match['round_limit']) {
        $reason = 'complete';
    } else {
        $match['round']++;
    }

    if ($reason !== null) {
        $match['status'] = 'finished';
        finish_match_record($match, $reason);
    } else {
        save_match_progress($match);
    }

    $_SESSION['match'] = $match;

    return [
        'correct' => $isCorrect,
        'timed_out' => $timedOut,
        'correct_answer' => $correctAnswer,
        'points' => $points,
        'finished' => $reason !== null,
        'reason' => $reason,
        'best_score' => best_match_score($userId),
    ] + public_match();
}

function leaderboard(int $limit = 10): array
{
    $stmt = db()->prepare(
        "SELECT users.username, matches.score, matches.difficulty, matches.rounds_correct,
                matches.rounds_played, matches.best_streak, matches.finished_at
         FROM matches
         INNER JOIN users ON users.id = matches.user_id
         WHERE matches.status = :status AND matches.finished_reason IN ('complete', 'lives')
         ORDER BY matches.score DESC, matches.finished_at ASC
         LIMIT " . (int) $limit
    );
    $stmt->execute([':status' => 'finished']);

    return $stmt->fetchAll();
}

function best_match_score(int $userId): int
{
    $stmt = db()->prepare(
        "SELECT COALESCE(MAX(score), 0) FROM matches
         WHERE user_id = :user_id AND status = :status AND finished_reason IN ('complete', 'lives')"
    );
    $stmt->execute([':user_id' => $userId, ':status' => 'finished']);

    return (int) $stmt->fetchColumn();
}

function require_active_match(): array
{
    $match = active_match();
    if ($match === null || ($match['status'] ?? '') !== 'active') {
        throw new RuntimeException('Start a challenge first.');
    }

    return $match;
}

function public_match(): array
{
    $match = active_match();
    if ($match === null) {
        return [];
    }

    return [
        'difficulty' => $match['difficulty'],
        'time_limit' => (int) $match['time_limit'],
        'lives' => (int) $match['lives'],
        'max_lives' => (int) $match['max_lives'],
        'round' => (int) $match['round'],
        'round_limit' => (int) $match['round_limit'],
        'score' => (int) $match['score'],
        'streak' => (int) $match['streak'],
        'best_streak' => (int) $match['best_streak'],
        'correct_count' => (int) $match['correct_count'],
        'status' => $match['status'],
    ];
}

function public_puzzle(array $match): array
{
    $elapsed = microtime(true) - (float) $match['issued_at'];
    $remaining = max(0, (float) $match['time_limit'] - $elapsed);

    return public_match() + [
        'image' => $match['image'],
        'caption' => puzzle_caption((string) $match['image']),
        'remaining' => round($remaining, 2),
    ];
}

function save_match_progress(array $match): void
{
    $stmt = db()->prepare(
        'UPDATE matches
         SET score = :score, rounds_played = :rounds_played, rounds_correct = :rounds_correct, best_streak = :best_streak
         WHERE id = :id AND user_id = :user_id'
    );
    $stmt->execute([
        ':score' => (int) $match['score'],
        ':rounds_played' => max(0, (int) $match['round'] - 1),
        ':rounds_correct' => (int) $match['correct_count'],
        ':best_streak' => (int) $match['best_streak'],
        ':id' => (int) $match['id'],
        ':user_id' => (int) $match['user_id'],
    ]);
}

function finish_match_record(array $match, string $reason): void
{
    $played = (int) $match['round'];
    if ($reason === 'abandoned') {
        $played = max(0, $played - (($match['solution'] === null) ? 1 : 0));
    }

    $stmt = db()->prepare(
        'UPDATE matches
         SET score = :score, rounds_played = :rounds_played, rounds_correct = :rounds_correct,
             best_streak = :best_streak, status = :status, finished_reason = :reason, finished_at = NOW()
         WHERE id = :id AND user_id = :user_id AND status = :active'
    );
    $stmt->execute([
        ':score' => (int) $match['score'],
        ':rounds_played' => $played,
        ':rounds_correct' => (int) $match['correct_count'],
        ':best_streak' => (int) $match['best_streak'],
        ':status' => 'finished',
        ':reason' => $reason,
        ':id' => (int) $match['id'],
        ':user_id' => (int) $match['user_id'],
        ':active' => 'active',
    ]);
}
