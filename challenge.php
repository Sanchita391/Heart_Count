<?php
// Timed challenge. Extra to the Flutter game. The countdown and answer clicks are events in assets/js/challenge.js.

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/challenge_service.php';

require_login();

$difficulty = (string) ($_GET['difficulty'] ?? '');
$rules = challenge_rules($difficulty);
if ($rules === null) {
    redirect('home.php');
}

$pageTitle = $rules['label'] . ' Challenge';
$barTitle = $rules['label'] . ' Challenge';
$backHref = 'home.php';
$pageScripts = ['assets/js/challenge.js'];
require __DIR__ . '/includes/header.php';
?>
<main
    class="page game-page"
    data-api="<?= e(url('api/challenge.php')) ?>"
    data-difficulty="<?= e($difficulty) ?>"
    data-home="<?= e(url('home.php')) ?>"
    data-leaderboard="<?= e(url('leaderboard.php')) ?>"
>
    <div id="game-loading" class="loading-block">
        <div class="spinner spinner-blue" role="status" aria-label="Loading puzzle"></div>
    </div>

    <div id="game-error" class="center hidden" role="alert">
        <p id="game-error-text">Failed to load game</p>
        <button type="button" class="btn btn-primary" id="retry-game">Retry</button>
    </div>

    <div id="game-play" class="hidden">
        <div class="hud">
            <span id="round-label">Round 1</span>
            <span id="lives-label" class="lives">Lives</span>
            <span id="streak-label">Streak 0</span>
        </div>
        <div class="timer-track" aria-hidden="true"><div id="timer-fill" class="timer-fill"></div></div>
        <p id="timer-label" class="timer-label">20s</p>
        <h1 class="question">Enter the missing digit</h1>
        <img id="puzzle-image" alt="Banana puzzle" width="280" height="280">
        <p id="result-text" class="result hidden" aria-live="polite"></p>
        <p id="answer-text" class="muted center hidden"></p>
        <p class="key-hint">On a keyboard, press 0 to 9.</p>
        <div class="answers">
            <?php for ($number = 1; $number <= 9; $number++): ?>
                <button type="button" class="answer" data-answer="<?= $number ?>"><?= $number ?></button>
            <?php endfor; ?>
            <button type="button" class="answer answer-zero" data-answer="0">0</button>
        </div>
        <section class="card stats-card">
            <div class="stat-row">
                <div>
                    <strong id="stat-score">0</strong>
                    <span>Points</span>
                </div>
                <div>
                    <strong id="stat-correct">0</strong>
                    <span>Correct</span>
                </div>
                <div>
                    <strong id="stat-streak">0</strong>
                    <span>Streak</span>
                </div>
            </div>
        </section>
    </div>

    <div id="game-finished" class="hidden">
        <h1 id="finish-title">Challenge over</h1>
        <p id="finish-reason" class="muted center"></p>
        <section class="card stats-card">
            <div class="stat-row">
                <div>
                    <strong id="finish-score">0</strong>
                    <span>Points</span>
                </div>
                <div>
                    <strong id="finish-correct">0</strong>
                    <span>Correct</span>
                </div>
                <div>
                    <strong id="finish-best">0</strong>
                    <span>Best</span>
                </div>
            </div>
        </section>
        <div class="stack">
            <a class="btn btn-primary" href="<?= e(url('challenge.php?difficulty=' . $difficulty)) ?>">Play again</a>
            <a class="btn btn-outline" href="<?= e(url('leaderboard.php')) ?>">Leaderboard</a>
            <a class="btn btn-outline" href="<?= e(url('home.php')) ?>">Home</a>
        </div>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
