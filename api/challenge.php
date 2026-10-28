<?php

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/game_service.php';
require_once __DIR__ . '/../includes/challenge_service.php';

$user = current_user();
if ($user === null) {
    json_response(['status' => 'error', 'message' => 'Please sign in again.'], 401);
}

$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $decoded = json_decode(file_get_contents('php://input'), true);
    $input = is_array($decoded) ? $decoded : [];
}

$action = $_GET['action'] ?? ($input['action'] ?? '');

try {
    if ($action === 'start' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $difficulty = (string) ($input['difficulty'] ?? '');
        $match = start_match($user['id'], $difficulty);
        json_response(['status' => 'success'] + $match);
    }

    if ($action === 'puzzle' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        json_response(['status' => 'success'] + issue_puzzle());
    }

    if ($action === 'answer' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $timeout = !empty($input['timeout']);
        $answer = null;
        if (!$timeout) {
            $raw = $input['answer'] ?? null;
            $valid = is_int($raw) || (is_string($raw) && preg_match('/^[0-9]$/', $raw) === 1);
            if (!$valid) {
                json_response(['status' => 'error', 'message' => 'Choose a number from 0 to 9.'], 400);
            }
            $answer = (int) $raw;
            if ($answer < 0 || $answer > 9) {
                json_response(['status' => 'error', 'message' => 'Choose a number from 0 to 9.'], 400);
            }
        }

        $result = resolve_round($user['id'], $answer, $timeout);
        json_response(['status' => 'success'] + $result);
    }
} catch (InvalidArgumentException $exception) {
    json_response(['status' => 'error', 'message' => $exception->getMessage()], 400);
} catch (RuntimeException $exception) {
    json_response(['status' => 'error', 'message' => $exception->getMessage()], 409);
}

json_response(['status' => 'error', 'message' => 'Invalid request.'], 405);
