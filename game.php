<?php
// Practice game. Same job as the Flutter game screen. Clicks and number keys are handled in assets/js/game.js.

require_once __DIR__ . '/includes/bootstrap.php';

require_login();

if (isset($_GET['fresh'])) {
    reset_run();
    redirect('game.php');
}

$stats = run_stats();
$pageTitle = 'Heart Game';
$barTitle = 'Heart Game';
$backHref = 'home.php';
$barActions = '<button type="button" class="icon-button" id="refresh-game" aria-label="New puzzle">'
    . '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M17.6 6.4A8 8 0 1 0 20 12h-2a6 6 0 1 1-1.8-4.2L13 11h7V4l-2.4 2.4z"/></svg></button>';
$pageScripts = ['assets/js/game.js'];
require __DIR__ . '/includes/header.php';
?>
<main class="page game-page" data-api="<?= e(url('api/game.php')) ?>">
    <div id="game-loading" class="loading-block">
        <div class="spinner spinner-blue" role="status" aria-label="Loading puzzle"></div>
    </div>

    <div id="game-error" class="center hidden" role="alert">
        <p id="game-error-text">Failed to load game</p>
        <button type="button" class="btn btn-primary" id="retry-game">Retry</button>
    </div>

    <div id="game-play" class="hidden">
        <h1 class="question">Enter the missing digit</h1>
        <img id="puzzle-image" alt="Banana puzzle" width="280" height="280">
        <p id="result-text" class="result hidden" aria-live="polite"></p>
        <p id="answer-text" class="muted center hidden"></p>
        <p class="key-hint">On a keyboard, press 0 to 9.</p>

        <div class="answers" id="answers">
            <?php for ($number = 1; $number <= 9; $number++): ?>
                <button type="button" class="answer" data-answer="<?= $number ?>"><?= $number ?></button>
            <?php endfor; ?>
            <button type="button" class="answer answer-zero" data-answer="0">0</button>
        </div>

        <section class="card stats-card">
            <div class="stat-row">
                <div>
                    <strong id="stat-score"><?= e((string) $stats['score']) ?></strong>
                    <span>Score</span>
                </div>
                <div>
                    <strong id="stat-games"><?= e((string) $stats['total']) ?></strong>
                    <span>Games</span>
                </div>
                <div>
                    <strong id="stat-accuracy"><?= e($stats['accuracy']) ?>%</strong>
                    <span>Accuracy</span>
                </div>
            </div>
        </section>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
