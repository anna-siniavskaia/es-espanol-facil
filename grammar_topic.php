<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_user();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = db()->prepare(
    'SELECT gt.*, l.code AS level_code, l.name AS level_name
     FROM grammar_topics gt
     JOIN levels l ON l.id = gt.level_id
     WHERE gt.id = ?'
);
$stmt->execute([$id]);
$topic = $stmt->fetch();

if (!$topic) {
    flash('error', 'Грамматическая тема не найдена.');
    redirect('grammar.php');
}

$pageTitle = (string) $topic['title'];
$exercises = get_exercises('grammar', (int) $topic['id']);
$checked = null;
$submittedAnswers = submitted_answers();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $exercises) {
    verify_csrf();
    $checked = check_exercises($exercises, $submittedAnswers);
    save_progress((int) $user['id'], 'grammar', (int) $topic['id'], (float) $checked['score']);
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div>
        <span class="eyebrow">уровень <?= e($topic['level_code']) ?> · грамматика</span>
        <h1><?= e($topic['title']) ?></h1>
        <p><?= e($topic['level_name']) ?></p>
    </div>
    <a class="button quiet" href="grammar.php?level=<?= (int) $topic['level_id'] ?>">Все темы уровня</a>
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

<section class="grammar-layout">
    <article class="rule-panel">
        <h2>Правило</h2>
        <div class="prose">
            <?= nl2br(e($topic['explanation'])) ?>
        </div>
    </article>
    <article class="rule-panel accent">
        <h2>Примеры</h2>
        <div class="prose examples">
            <?= nl2br(e($topic['examples'])) ?>
        </div>
    </article>
</section>

<section class="exercise-section">
    <div class="section-heading">
        <span class="eyebrow">тренировка</span>
        <h2>Упражнения по теме</h2>
    </div>

    <?php if (!$exercises): ?>
        <div class="empty-state">
            <h2>Упражнений по теме пока нет</h2>
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

        <div class="sticky-actions">
            <span><?= count($exercises) ?> заданий</span>
            <button class="button primary" type="submit">Проверить упражнения</button>
        </div>
    </form>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
