<?php
// Leaderboard. Shows finished challenge matches. A normal user and an admin both see this page.

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/challenge_service.php';

require_login();
$rows = leaderboard();

$pageTitle = 'Leaderboard';
$barTitle = 'Leaderboard';
$backHref = 'home.php';
require __DIR__ . '/includes/header.php';
?>
<main class="page">
    <h1>Top challenges</h1>
    <p class="muted">Best finished matches. Practice scores are not included.</p>
    <?php if (!$rows): ?>
        <section class="card empty-state">
            <p>No finished challenges yet</p>
            <p class="muted">Play Easy, Medium, or Hard to appear here.</p>
        </section>
    <?php else: ?>
        <ol class="leaderboard">
            <?php foreach ($rows as $index => $row): ?>
                <li class="card player-card">
                    <div class="player-heading">
                        <h2><?= e((string) ($index + 1)) ?>. <?= e($row['username']) ?></h2>
                        <span class="role-badge role-admin"><?= e((string) $row['score']) ?></span>
                    </div>
                    <p class="muted"><?= e(ucfirst($row['difficulty'])) ?> · <?= e((string) $row['rounds_correct']) ?>/<?= e((string) $row['rounds_played']) ?> correct · streak <?= e((string) $row['best_streak']) ?></p>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
