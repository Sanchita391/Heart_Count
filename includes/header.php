<?php
$pageTitle = $pageTitle ?? 'Heart Game';
$showAppBar = $showAppBar ?? true;
$barTitle = $barTitle ?? 'Heart Game';
$bodyClass = $bodyClass ?? '';
$backHref = $backHref ?? null;
$barActions = $barActions ?? '';
$pageScripts = $pageScripts ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<?php if ($showAppBar): ?>
    <header class="app-bar">
        <?php if ($backHref): ?>
            <a class="icon-button" href="<?= e(url($backHref)) ?>" aria-label="Back">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M20 11H7.8l5.6-5.6L12 4l-8 8 8 8 1.4-1.4L7.8 13H20v-2z"/></svg>
            </a>
        <?php else: ?>
            <span class="icon-spacer"></span>
        <?php endif; ?>
        <p class="app-bar-title"><?= e($barTitle) ?></p>
        <div class="app-bar-actions"><?= $barActions ?></div>
    </header>
<?php endif; ?>
