<?php
require_once __DIR__ . '/functions.php';
$navUser = current_user();
$pageTitle = $pageTitle ?? APP_NAME;
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="static/css/styles.css">
</head>
<body>
<div class="site-shell">
    <header class="topbar">
        <a class="brand" href="index.php" aria-label="На главную">
            <span class="brand-mark">ES</span>
            <span>
                <strong><?= e(APP_NAME) ?></strong>
                <small>español paso a paso</small>
            </span>
        </a>

        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-nav">☰</button>

        <nav class="main-nav" id="main-nav">
            <a href="texts.php">Тексты</a>
            <a href="grammar.php">Грамматика</a>
            <a href="vocabulary.php">Лексика</a>
            <a href="progress.php">Прогресс</a>
            <?php if ($navUser): ?>
                <a class="nav-pill" href="profile.php"><?= e($navUser['login']) ?></a>
                <form method="post" action="logout.php">
                    <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                    <button class="link-button" type="submit">Выход</button>
                </form>
            <?php else: ?>
                <a class="nav-pill" href="login.php">Войти</a>
            <?php endif; ?>
        </nav>
    </header>

    <?php foreach (['success', 'warning', 'error'] as $kind): ?>
        <?php if ($message = flash($kind)): ?>
            <div class="flash <?= e($kind) ?>"><?= e($message) ?></div>
        <?php endif; ?>
    <?php endforeach; ?>

    <main class="content">
