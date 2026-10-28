<?php
// Practice API. GET loads a Heart API picture. POST checks the heart count.
// The correct digit stays in the PHP session. The browser only receives the image.

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/game_service.php';

$user = current_user();
if ($user === null) {
    json_response(['status' => 'error', 'message' => 'Please sign in again.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'new') {
    $puzzle = fetch_puzzle();
    if ($puzzle === null) {
        json_response([
            'status' => 'error',
            'message' => 'The heart puzzle service is unavailable. Please try again.',
        ], 502);
    }

    $_SESSION['puzzle_solution'] = $puzzle['solution'];
    $stats = run_stats();

    json_response([
        'status' => 'success',
        'image' => $puzzle['image'],
        'caption' => $puzzle['caption'],
        'score' => $stats['score'],
        'total' => $stats['total'],
        'accuracy' => $stats['accuracy'],
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $rawAnswer = is_array($input) ? ($input['answer'] ?? null) : null;
    $answerIsWhole = is_int($rawAnswer) || (is_string($rawAnswer) && preg_match('/^[0-9]$/', $rawAnswer) === 1);
    if (!$answerIsWhole) {
        json_response(['status' => 'error', 'message' => 'Choose a number from 0 to 9.'], 400);
    }

    $answer = (int) $rawAnswer;
    if ($answer < 0 || $answer > 9) {
        json_response(['status' => 'error', 'message' => 'Choose a number from 0 to 9.'], 400);
    }

    if (!isset($_SESSION['puzzle_solution'])) {
        json_response(['status' => 'error', 'message' => 'This puzzle has already been answered. Loading a new one.'], 409);
    }

    $correct = (int) $_SESSION['puzzle_solution'];
    $saved = record_answer($user['id'], $answer, $correct);
    if (!$saved) {
        json_response(['status' => 'error', 'message' => 'Your answer could not be saved. Please try again.'], 500);
    }

    unset($_SESSION['puzzle_solution']);
    $_SESSION['run_total'] = (int) ($_SESSION['run_total'] ?? 0) + 1;
    if ($answer === $correct) {
        $_SESSION['run_score'] = (int) ($_SESSION['run_score'] ?? 0) + 1;
    }

    $stats = run_stats();
    json_response([
        'status' => 'success',
        'correct' => $answer === $correct,
        'correct_answer' => $correct,
        'score' => $stats['score'],
        'total' => $stats['total'],
        'accuracy' => $stats['accuracy'],
    ]);
}

json_response(['status' => 'error', 'message' => 'Invalid request.'], 405);
