<?php
// Home screen. Practice matches the original Flutter game. Challenge, leaderboard and admin are extras.

require_once __DIR__ . '/includes/bootstrap.php';

$user = require_login();
$stats = run_stats();

$pageTitle = 'Heart Game';
$barTitle = 'Heart Game';
$barActions = '<a class="icon-button" href="' . e(url('profile.php')) . '" aria-label="My profile">'
    . '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-3.3 0-8 1.7-8 5v1h16v-1c0-3.3-4.7-5-8-5z"/></svg></a>';
require __DIR__ . '/includes/header.php';
?>
<main class="page">
    <svg class="heart-icon heart-icon-red" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="currentColor" d="M12 21s-6.7-4.4-9.3-8.2C.8 10.2 1.1 6.8 3.6 5.2 5.6 3.9 8.2 4.3 10 6.1L12 8.2l2-2.1c1.8-1.8 4.4-2.2 6.4-.9 2.5 1.6 2.8 5 1 7.6C18.7 16.6 12 21 12 21z"/>
    </svg>
    <span class="brand-kicker">HeartCount</span>
    <h1 class="brand-title">Heart Game</h1>
    <p class="muted center">Welcome, <?= e($user['username']) ?>. Count the hearts. Beat the clock.</p>

    <section class="card stats-card">
        <h2>This run</h2>
        <div class="stat-row">
            <div>
                <strong id="home-total"><?= e((string) $stats['total']) ?></strong>
                <span>Total Games</span>
            </div>
            <div>
                <strong id="home-score"><?= e((string) $stats['score']) ?></strong>
                <span>Score</span>
            </div>
            <div>
                <strong id="home-accuracy"><?= e($stats['accuracy']) ?>%</strong>
                <span>Accuracy</span>
            </div>
        </div>
    </section>

    <div class="play-grid">
        <a class="card play-card featured" href="<?= e(url('game.php?fresh=1')) ?>">
            <strong>Practice</strong>
            <span>Unlimited hearts. One point for each correct count.</span>
        </a>
        <a class="card play-card" href="<?= e(url('challenge.php?difficulty=easy')) ?>">
            <strong>Easy challenge</strong>
            <span>20 seconds · 3 lives · 8 rounds</span>
        </a>
        <a class="card play-card" href="<?= e(url('challenge.php?difficulty=medium')) ?>">
            <strong>Medium challenge</strong>
            <span>12 seconds · 3 lives · 10 rounds</span>
        </a>
        <a class="card play-card" href="<?= e(url('challenge.php?difficulty=hard')) ?>">
            <strong>Hard challenge</strong>
            <span>7 seconds · 2 lives · 12 rounds</span>
        </a>
        <a class="card play-card" href="<?= e(url('leaderboard.php')) ?>">
            <strong>Leaderboard</strong>
            <span>Finished challenge scores only</span>
        </a>
        <a class="card play-card" href="<?= e(url('stats.php')) ?>">
            <strong>Statistics</strong>
            <span>Your saved practice history</span>
        </a>
        <a class="card play-card" href="<?= e(url('profile.php')) ?>">
            <strong>My profile</strong>
            <span>Identity, last login, logout</span>
        </a>
        <?php if (is_admin()): ?>
            <a class="card play-card" href="<?= e(url('admin.php')) ?>">
                <strong>Manage players</strong>
                <span>Admin only · checked on the server</span>
            </a>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
