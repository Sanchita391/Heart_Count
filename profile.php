<?php
// Profile screen. The player can change their own username, email and password.

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

$pageTitle = 'My Profile';
$barTitle = 'My Profile';
$backHref = 'home.php';
$pageScripts = ['assets/js/profile.js'];
require __DIR__ . '/includes/header.php';

$total = (int) ($stats['user']['total_games'] ?? 0);
$won = (int) ($stats['user']['games_won'] ?? 0);
$rate = format_accuracy($won, $total);
?>
<main class="page">
    <?php if ($error): ?>
        <div class="center error-block">
            <h1>Unable to load statistics</h1>
            <p class="muted"><?= e($error) ?></p>
            <a class="btn btn-primary" href="<?= e(url('profile.php')) ?>">Try Again</a>
        </div>
    <?php else: ?>
        <section class="card profile-card">
            <div class="avatar" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-3.3 0-8 1.7-8 5v1h16v-1c0-3.3-4.7-5-8-5z"/></svg>
            </div>
            <h1><?= e($stats['user']['username']) ?></h1>
            <p class="muted"><?= e($stats['user']['email']) ?></p>
            <p class="player-label"><?= $user['role'] === 'admin' ? 'Administrator' : 'Heart Game Player' ?></p>
        </section>

        <h2>Game Statistics</h2>
        <div class="stat-grid">
            <?php
            $cards = [
                ['Total Games', (string) $total],
                ['Games Won', (string) $won],
                ['Win Rate', $rate . '%'],
                ['Accuracy', $rate . '%'],
            ];
            foreach ($cards as $card):
            ?>
                <article class="card stat-tile">
                    <strong><?= e($card[1]) ?></strong>
                    <span><?= e($card[0]) ?></span>
                </article>
            <?php endforeach; ?>
        </div>

        <h2>Recent Activity</h2>
        <?php if (!$stats['recent_games']): ?>
            <section class="card empty-state">
                <p>No games played yet</p>
                <p class="muted">Play some games to see your history here!</p>
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
                            <strong class="<?= $correct ? 'text-ok' : 'text-bad' ?>"><?= $timedOut ? 'Time ran out' : 'Answer: ' . e((string) $game['user_answer']) ?></strong>
                            <p class="muted"><?= $correct ? 'Correct!' : 'Correct: ' . e((string) $game['correct_answer']) ?></p>
                        </div>
                        <time><?= e(format_date($game['created_at'])) ?></time>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <button type="button" class="btn btn-danger" id="logout-open">Logout</button>
        <dialog id="logout-dialog">
            <form method="post" action="<?= e(url('logout.php')) ?>">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <h2>Logout</h2>
                <p>Are you sure you want to logout?</p>
                <div class="dialog-actions">
                    <button type="button" class="btn btn-outline" id="logout-cancel">Cancel</button>
                    <button type="submit" class="btn btn-danger">Logout</button>
                </div>
            </form>
        </dialog>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
