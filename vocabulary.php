<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_user();
$pageTitle = 'Лексика';

$levels = get_levels();
$activeLevelId = selected_level_id($user);
$activeLevel = get_level_by_id($activeLevelId);

$stmt = db()->prepare(
    'SELECT v.*, t.title AS text_title
     FROM vocabulary v
     LEFT JOIN texts t ON t.id = v.text_id
     WHERE v.level_id = ?
     ORDER BY v.theme, v.spanish'
);
$stmt->execute([$activeLevelId]);
$words = $stmt->fetchAll();

$themes = [];
foreach ($words as $word) {
    $themes[$word['theme']][] = $word;
}

$exercises = get_exercises('vocabulary', $activeLevelId);
$checked = null;
$submittedAnswers = submitted_answers();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $exercises) {
    verify_csrf();
    $checked = check_exercises($exercises, $submittedAnswers);
    save_progress((int) $user['id'], 'vocabulary', $activeLevelId, (float) $checked['score']);
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div>
        <span class="eyebrow">словарь и закрепление</span>
        <h1>Лексика <?= e($activeLevel['code'] ?? '') ?></h1>
        <p>Тематические группы слов связаны с текстами и упражнениями уровня.</p>
    </div>
    <?php render_level_tabs($levels, $activeLevelId, 'vocabulary.php'); ?>
</section>

<?php if ($checked): ?>
    <section class="result-banner">
        <div>
            <span>Результат</span>
            <strong><?= e($checked['score']) ?>%</strong>
        </div>
        <div>
            <span>Верных ответов</span>
            <strong><?= (int) $checked['correct'] ?> / <?= (int) $checked['total'] ?></strong>
        </div>
        <a class="button" href="progress.php">Открыть прогресс</a>
    </section>
<?php endif; ?>

<?php if (!$words): ?>
    <section class="empty-state">
        <h2>Словарь пока пуст</h2>
        <p>Учебные материалы не включены в эту копию. Их можно добавить локально после проверки прав на распространение.</p>
    </section>
<?php else: ?>
<section class="vocab-layout">
    <div class="vocab-themes">
        <?php foreach ($themes as $theme => $items): ?>
            <article class="vocab-panel">
                <h2><?= e($theme) ?></h2>
                <div class="word-grid">
                    <?php foreach ($items as $word): ?>
                        <div class="word-chip">
                            <strong><?= e($word['spanish']) ?></strong>
                            <span><?= e($word['russian']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <aside class="side-panel sticky">
        <h2>Как работать</h2>
        <p>Сначала просмотрите слова по темам, затем выполните упражнения. Если результат выше <?= PASS_SCORE ?>%, лексический блок уровня будет отмечен как завершённый.</p>
    </aside>
</section>

<section class="exercise-section">
    <div class="section-heading">
        <span class="eyebrow">практика словаря</span>
        <h2>Лексические задания</h2>
    </div>

    <?php if (!$exercises): ?>
        <div class="empty-state">
            <h2>Упражнений пока нет</h2>
            <p>Учебные задания не включены в эту копию. Их можно добавить локально после проверки прав на распространение.</p>
        </div>
    <?php else: ?>
    <form class="exercise-form" method="post">
        <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
        <?php foreach ($exercises as $index => $exercise): ?>
            <?php
            $eid = (int) $exercise['id'];
            render_exercise_field($exercise, $submittedAnswers[$eid] ?? null, $checked['results'][$eid] ?? null, $index + 1);
            ?>
        <?php endforeach; ?>

        <?php if ($exercises): ?>
            <div class="sticky-actions">
                <span><?= count($exercises) ?> заданий</span>
                <button class="button primary" type="submit">Проверить лексику</button>
            </div>
        <?php endif; ?>
    </form>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
