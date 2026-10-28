<?php
// Splash screen. Same first screen as the Flutter app. splash.js waits, then opens login or home.

require_once __DIR__ . '/includes/bootstrap.php';

$next = current_user() ? 'home.php' : 'login.php';
$pageTitle = 'Heart Game';
$bodyClass = 'splash';
$showAppBar = false;
$pageScripts = ['assets/js/splash.js'];

require __DIR__ . '/includes/header.php';
?>
<main class="splash-main">
    <svg class="heart-icon" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="currentColor" d="M12 21s-6.7-4.4-9.3-8.2C.8 10.2 1.1 6.8 3.6 5.2 5.6 3.9 8.2 4.3 10 6.1L12 8.2l2-2.1c1.8-1.8 4.4-2.2 6.4-.9 2.5 1.6 2.8 5 1 7.6C18.7 16.6 12 21 12 21z"/>
    </svg>
    <p class="brand-kicker" style="color:#fff;">Count the hearts</p>
    <h1>Heart Game</h1>
    <p class="muted">A timed quiz with a live Heart API</p>
    <div class="spinner" role="status" aria-label="Loading"></div>
    <a class="splash-continue" href="<?= e(url($next)) ?>">Continue</a>
</main>
<script>document.body.dataset.next = <?= json_encode(url($next)) ?>;</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
