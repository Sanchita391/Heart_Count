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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>?v=2">
</head>
<body class="<?= e($bodyClass) ?>">
<div class="float-hearts" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>
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
