<?php
// Statistics screen. Shows this player's practice results from the database.

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/game_service.php';

$user = require_login();
$stats = null;
$error = '';

try {
    $stats = user_stats($user['id']);
    if ($stats === null) {
        $error = 'Unable to load statistics';
    }
} catch (Throwable $exception) {
    $error = 'Unable to load statistics';
}

$pageTitle = 'Game Statistics';
$barTitle = 'Game Statistics';
$backHref = 'home.php';
$barActions = '<a class="icon-button" href="' . e(url('stats.php')) . '" aria-label="Refresh statistics">'
    . '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M17.6 6.4A8 8 0 1 0 20 12h-2a6 6 0 1 1-1.8-4.2L13 11h7V4l-2.4 2.4z"/></svg></a>';
require __DIR__ . '/includes/header.php';

$total = (int) ($stats['user']['total_games'] ?? 0);
$won = (int) ($stats['user']['games_won'] ?? 0);
$lost = (int) ($stats['user']['games_lost'] ?? 0);
?>
<main class="page">
    <?php if ($error): ?>
        <div class="center error-block">
            <h1>Unable to load statistics</h1>
            <p class="muted"><?= e($error) ?></p>
            <a class="btn btn-primary" href="<?= e(url('stats.php')) ?>">Try Again</a>
        </div>
    <?php else: ?>
        <h1>Game Statistics</h1>
        <div class="stat-grid">
            <?php
            $cards = [
                ['Total Games', (string) $total],
                ['Games Won', (string) $won],
                ['Games Lost', (string) $lost],
                ['Win Rate', format_accuracy($won, $total) . '%'],
            ];
            foreach ($cards as $card):
            ?>
                <article class="card stat-tile">
                    <strong><?= e($card[1]) ?></strong>
                    <span><?= e($card[0]) ?></span>
                </article>
            <?php endforeach; ?>
        </div>

        <h2>Recent Games</h2>
        <?php if (!$stats['recent_games']): ?>
            <section class="card empty-state">
                <p>No recent games</p>
                <p class="muted">Your game history will appear here</p>
            </section>
        <?php else: ?>
            <section class="card history">
                <?php foreach ($stats['recent_games'] as $game): ?>
                    <?php
                    $timedOut = !empty($game['timed_out']);
                    $correct = !$timedOut && (int) $game['is_correct'] === 1;
                    ?>
                    <article class="history-row">
                        <span class="<?= $correct ? 'mark-ok' : 'mark-bad' ?>"><?= $correct ? '✓' : '✕' ?></span>
                        <div>
                            <strong class="<?= $correct ? 'text-ok' : 'text-bad' ?>"><?= $timedOut ? 'Time ran out' : 'Your answer: ' . e((string) $game['user_answer']) ?></strong>
                            <p class="muted"><?= $correct ? 'Correct!' : 'Correct answer: ' . e((string) $game['correct_answer']) ?></p>
                        </div>
                        <div class="history-time">
                            <time><?= e(format_date($game['created_at'], true)) ?></time>
                            <span><?= e(format_date($game['created_at'])) ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
