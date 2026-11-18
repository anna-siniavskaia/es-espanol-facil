<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_user();
$pageTitle = 'Профиль';

$levelId = user_level_id($user);
$level = get_level_by_id($levelId);

$counts = [];
$queries = [
    'texts' => ['text', 'texts'],
    'grammar' => ['grammar', 'grammar_topics'],
];

foreach ($queries as $key => [$sectionType, $table]) {
    $totalStmt = db()->prepare("SELECT COUNT(*) AS total FROM {$table} WHERE level_id = ?");
    $totalStmt->execute([$levelId]);
    $completedStmt = db()->prepare(
        "SELECT COUNT(*) AS total
         FROM user_progress up
         JOIN {$table} item ON item.id = up.section_id
         WHERE up.user_id = ? AND up.section_type = ? AND up.status = 'completed' AND item.level_id = ?"
    );
    $completedStmt->execute([(int) $user['id'], $sectionType, $levelId]);

    $counts[$key] = [
        'total' => (int) $totalStmt->fetch()['total'],
        'completed' => (int) $completedStmt->fetch()['total'],
    ];
}

$vocabProgressStmt = db()->prepare(
    "SELECT status, score, attempts, last_activity_at FROM user_progress
     WHERE user_id = ? AND section_type = 'vocabulary' AND section_id = ?"
);
$vocabProgressStmt->execute([(int) $user['id'], $levelId]);
$vocabProgress = $vocabProgressStmt->fetch();

$avgStmt = db()->prepare('SELECT AVG(score) AS average_score, MAX(last_activity_at) AS last_activity FROM user_progress WHERE user_id = ?');
$avgStmt->execute([(int) $user['id']]);
$avg = $avgStmt->fetch();

$testsStmt = db()->prepare(
    'SELECT tr.*, l.code AS determined_code
     FROM test_results tr
     LEFT JOIN levels l ON l.id = tr.determined_level_id
     WHERE tr.user_id = ?
     ORDER BY tr.created_at DESC
     LIMIT 5'
);
$testsStmt->execute([(int) $user['id']]);
$tests = $testsStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="profile-head">
    <div>
        <span class="eyebrow">профиль пользователя</span>
        <h1><?= e($user['login']) ?></h1>
        <p><?= e($user['email']) ?></p>
    </div>
    <div class="profile-level">
        <span>Текущий уровень</span>
        <strong><?= e($level['code'] ?? 'не определён') ?></strong>
        <?php if (!$user['has_taken_level_test']): ?>
            <a class="button primary small" href="level_test.php">Пройти тест</a>
        <?php else: ?>
            <a class="button small" href="level_test.php">Повторить тест</a>
        <?php endif; ?>
    </div>
</section>

<section class="stats-grid">
    <article class="stat-card">
        <span>Тексты</span>
        <strong><?= $counts['texts']['completed'] ?> / <?= $counts['texts']['total'] ?></strong>
        <p>завершено на уровне <?= e($level['code'] ?? 'A1') ?></p>
    </article>
    <article class="stat-card">
        <span>Грамматика</span>
        <strong><?= $counts['grammar']['completed'] ?> / <?= $counts['grammar']['total'] ?></strong>
        <p>темы с результатом от <?= PASS_SCORE ?>%</p>
    </article>
    <article class="stat-card">
        <span>Лексика</span>
        <strong><?= $vocabProgress ? e(progress_label($vocabProgress['status'])) : 'не начато' ?></strong>
        <p><?= $vocabProgress ? 'лучший балл: ' . e($vocabProgress['score']) . '%' : 'откройте словарь уровня' ?></p>
    </article>
    <article class="stat-card">
        <span>Средний балл</span>
        <strong><?= $avg['average_score'] ? round((float) $avg['average_score'], 1) . '%' : '0%' ?></strong>
        <p>последняя активность: <?= e($avg['last_activity'] ?: 'нет данных') ?></p>
    </article>
</section>

<section class="two-column">
    <article class="info-panel">
        <h2>Рекомендуемый маршрут</h2>
        <ol class="route-list">
            <li><a href="texts.php?level=<?= $levelId ?>">Прочитать адаптированный текст уровня <?= e($level['code'] ?? 'A1') ?></a></li>
            <li><a href="grammar.php?level=<?= $levelId ?>">Закрепить грамматическую тему</a></li>
            <li><a href="vocabulary.php?level=<?= $levelId ?>">Повторить тематический словарь</a></li>
            <li><a href="progress.php">Проверить слабые разделы по прогрессу</a></li>
        </ol>
    </article>

    <article class="info-panel">
        <h2>Последние тесты</h2>
        <?php if (!$tests): ?>
            <p class="muted">Тесты ещё не проходились.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr><th>Тип</th><th>Балл</th><th>Уровень</th><th>Дата</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($tests as $test): ?>
                        <tr>
                            <td><?= e($test['test_type']) ?></td>
                            <td><?= e($test['score']) ?>%</td>
                            <td><?= e($test['determined_code'] ?: '-') ?></td>
                            <td><?= e($test['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
