<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_user();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = db()->prepare(
    'SELECT t.*, l.code AS level_code, l.name AS level_name
     FROM texts t
     JOIN levels l ON l.id = t.level_id
     WHERE t.id = ?'
);
$stmt->execute([$id]);
$text = $stmt->fetch();

if (!$text) {
    flash('error', 'Текст не найден.');
    redirect('texts.php');
}

$pageTitle = (string) $text['title'];
$exercises = get_exercises('text', (int) $text['id']);
$checked = null;
$submittedAnswers = submitted_answers();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $exercises) {
    verify_csrf();
    $checked = check_exercises($exercises, $submittedAnswers);
    save_progress((int) $user['id'], 'text', (int) $text['id'], (float) $checked['score']);
}

$vocabStmt = db()->prepare('SELECT * FROM vocabulary WHERE text_id = ? ORDER BY theme, spanish');
$vocabStmt->execute([(int) $text['id']]);
$vocabulary = $vocabStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div>
        <span class="eyebrow">уровень <?= e($text['level_code']) ?> · <?= e($text['topic']) ?></span>
        <h1><?= e($text['title']) ?></h1>
        <p><?= (int) $text['word_count'] ?> слов · <?= e($text['level_name']) ?></p>
    </div>
    <a class="button quiet" href="texts.php?level=<?= (int) $text['level_id'] ?>">Все тексты уровня</a>
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

<section class="learning-layout">
    <article class="reading-panel">
        <h2>Texto</h2>
        <div class="reading-text">
            <?= nl2br(e($text['content'])) ?>
        </div>
    </article>

    <aside class="side-panel">
        <h2>Глоссарий</h2>
        <?php if (!$vocabulary): ?>
            <p class="muted">Словарь для текста не заполнен.</p>
        <?php else: ?>
            <dl class="glossary-list">
                <?php foreach ($vocabulary as $word): ?>
                    <div>
                        <dt><?= e($word['spanish']) ?></dt>
                        <dd><?= e($word['russian']) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>
    </aside>
</section>

<section class="exercise-section">
    <div class="section-heading">
        <span class="eyebrow">проверка понимания</span>
        <h2>Задания к тексту</h2>
    </div>

    <?php if (!$exercises): ?>
        <div class="empty-state">
            <h2>Заданий к тексту пока нет</h2>
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
            <button class="button primary" type="submit">Проверить задания</button>
        </div>
    </form>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
