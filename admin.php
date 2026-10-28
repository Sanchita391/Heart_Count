<?php
// Admin screen. Only the admin role can open it. An admin can see players and delete a normal user.

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/game_service.php';

$admin = require_admin();
$notice = '';

if (!empty($_SESSION['flash'])) {
    $notice = (string) $_SESSION['flash'];
    unset($_SESSION['flash']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid() || ($_POST['action'] ?? '') !== 'delete') {
        $_SESSION['flash'] = 'The form expired. Please try again.';
        redirect('admin.php');
    }

    $userId = (int) ($_POST['user_id'] ?? 0);
    if (delete_player($admin['id'], $userId)) {
        $_SESSION['flash'] = 'Player removed.';
    } else {
        $_SESSION['flash'] = 'That account could not be removed.';
    }
    redirect('admin.php');
}

$players = list_players();
$recentGames = recent_games_for_all();
$playerCount = 0;
$gameCount = 0;
$wonCount = 0;

foreach ($players as $player) {
    if ($player['role'] === 'user') {
        $playerCount++;
    }
    $gameCount += (int) $player['total_games'];
    $wonCount += (int) $player['games_won'];
}

$pageTitle = 'Manage Players';
$barTitle = 'Manage Players';
$backHref = 'home.php';
$pageScripts = ['assets/js/admin.js'];
require __DIR__ . '/includes/header.php';
?>
<main class="page">
    <?php if ($notice): ?>
        <p class="notice" role="status"><?= e($notice) ?></p>
    <?php endif; ?>

    <section class="card stats-card">
        <h1>Players</h1>
        <div class="stat-row">
            <div>
                <strong><?= e((string) $playerCount) ?></strong>
                <span>Players</span>
            </div>
            <div>
                <strong><?= e((string) $gameCount) ?></strong>
                <span>Games</span>
            </div>
            <div>
                <strong><?= e(format_accuracy($wonCount, $gameCount)) ?>%</strong>
                <span>Accuracy</span>
            </div>
        </div>
    </section>

    <h2>Accounts</h2>
    <?php foreach ($players as $player): ?>
        <?php
        $total = (int) $player['total_games'];
        $won = (int) $player['games_won'];
        $isAdmin = $player['role'] === 'admin';
        ?>
        <article class="card player-card">
            <div class="player-heading">
                <h2><?= e($player['username']) ?></h2>
                <span class="role-badge <?= $isAdmin ? 'role-admin' : 'role-user' ?>"><?= $isAdmin ? 'Admin' : 'User' ?></span>
            </div>
            <p class="muted"><?= e($player['email']) ?></p>
            <p><?= e((string) $total) ?> games · <?= e((string) $won) ?> won · <?= e(format_accuracy($won, $total)) ?>%</p>
            <?php if (!$isAdmin): ?>
                <button
                    type="button"
                    class="btn btn-danger btn-small"
                    data-delete-id="<?= e((string) $player['id']) ?>"
                    data-delete-name="<?= e($player['username']) ?>"
                >Remove player</button>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>

    <h2>Recent games</h2>
    <?php if (!$recentGames): ?>
        <section class="card empty-state">
            <p>No games played yet</p>
        </section>
    <?php else: ?>
        <section class="card history">
            <?php foreach ($recentGames as $game): ?>
                <?php
                $timedOut = !empty($game['timed_out']);
                $correct = !$timedOut && (int) $game['is_correct'] === 1;
                ?>
                <article class="history-row">
                    <span class="<?= $correct ? 'mark-ok' : 'mark-bad' ?>"><?= $correct ? '✓' : '✕' ?></span>
                    <div>
                        <strong><?= e($game['username']) ?></strong>
                        <p class="muted"><?= $timedOut ? 'Time ran out. Correct ' . e((string) $game['correct_answer']) : ($correct ? 'Correct' : 'Answer ' . e((string) $game['user_answer']) . ', correct ' . e((string) $game['correct_answer'])) ?></p>
                    </div>
                    <time><?= e(format_date($game['created_at'])) ?></time>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <dialog id="delete-dialog">
        <form method="post" action="<?= e(url('admin.php')) ?>">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_id" id="delete-user-id">
            <h2>Remove player</h2>
            <p id="delete-text">Remove this player and their saved games?</p>
            <div class="dialog-actions">
                <button type="button" class="btn btn-outline" id="delete-cancel">Cancel</button>
                <button type="submit" class="btn btn-danger">Remove</button>
            </div>
        </form>
    </dialog>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
