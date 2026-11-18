<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_user();
$pageTitle = 'Прогресс';

$stmt = db()->prepare(
    'SELECT *
     FROM user_progress
     WHERE user_id = ?
     ORDER BY last_activity_at DESC, id DESC'
);
$stmt->execute([(int) $user['id']]);
$rows = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div>
        <span class="eyebrow">учебная статистика</span>
        <h1>Прогресс обучения</h1>
        <p>Здесь сохраняются лучшие результаты, количество попыток и даты последней активности.</p>
    </div>
    <a class="button primary" href="profile.php">Профиль</a>
</section>

<?php if (!$rows): ?>
    <section class="empty-state">
        <h2>Прогресс ещё не накоплен</h2>
        <p>Откройте текст, грамматическую тему или лексический раздел и выполните задания.</p>
        <a class="button" href="texts.php">Перейти к текстам</a>
    </section>
<?php else: ?>
    <section class="info-panel">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Раздел</th>
                    <th>Название</th>
                    <th>Статус</th>
                    <th>Лучший балл</th>
                    <th>Попытки</th>
                    <th>Последняя активность</th>
                    <th>Завершено</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['section_type']) ?></td>
                        <td><?= e(section_title($row['section_type'], (int) $row['section_id'])) ?></td>
                        <td><span class="status <?= e(progress_class($row['status'])) ?>"><?= e(progress_label($row['status'])) ?></span></td>
                        <td><?= e($row['score']) ?>%</td>
                        <td><?= (int) $row['attempts'] ?></td>
                        <td><?= e($row['last_activity_at']) ?></td>
                        <td><?= e($row['completed_at'] ?: '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
