<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_user();
$pageTitle = 'Тест уровня';

$exercises = get_exercises('test');
$checked = null;
$determinedLevel = null;
$submittedAnswers = submitted_answers();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $exercises) {
    verify_csrf();
    $checked = check_exercises($exercises, $submittedAnswers);
    $levelCode = determine_level_by_score((float) $checked['score']);
    $determinedLevel = get_level_by_code($levelCode);

    if ($determinedLevel) {
        db()->prepare(
            'UPDATE users SET current_level_id = ?, has_taken_level_test = 1 WHERE id = ?'
        )->execute([(int) $determinedLevel['id'], (int) $user['id']]);

        db()->prepare(
            'INSERT INTO test_results (user_id, test_type, score, determined_level_id, created_at)
             VALUES (?, "placement", ?, ?, NOW())'
        )->execute([(int) $user['id'], (float) $checked['score'], (int) $determinedLevel['id']]);

        save_progress((int) $user['id'], 'test', (int) $determinedLevel['id'], (float) $checked['score']);
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div>
        <span class="eyebrow">диагностика CEFR</span>
        <h1>Тест на определение уровня</h1>
        <p>Задания охватывают лексику, грамматику и понимание текста. По итоговому проценту система назначит уровень A1-C1.</p>
    </div>
    <a class="button quiet" href="profile.php">Вернуться в профиль</a>
</section>

<?php if ($checked && $determinedLevel): ?>
    <section class="result-banner">
        <div>
            <span>Результат теста</span>
            <strong><?= e($checked['score']) ?>%</strong>
        </div>
        <div>
            <span>Определённый уровень</span>
            <strong><?= e($determinedLevel['code']) ?></strong>
        </div>
        <a class="button primary" href="texts.php?level=<?= (int) $determinedLevel['id'] ?>">Начать обучение</a>
    </section>
<?php endif; ?>

<?php if (!$exercises): ?>
    <section class="empty-state">
        <h2>В базе пока нет тестовых заданий</h2>
        <p>В этой копии учебные задания не включены. Данные появятся после локального импорта материалов с подтверждёнными правами на распространение.</p>
    </section>
<?php else: ?>
    <form class="exercise-form" method="post">
        <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
        <?php foreach ($exercises as $index => $exercise): ?>
            <?php
            $id = (int) $exercise['id'];
            $previous = $submittedAnswers[$id] ?? null;
            $result = $checked['results'][$id] ?? null;
            render_exercise_field($exercise, $previous, $result, $index + 1);
            ?>
        <?php endforeach; ?>

        <div class="sticky-actions">
            <span><?= count($exercises) ?> заданий</span>
            <button class="button primary" type="submit">Проверить и сохранить уровень</button>
        </div>
    </form>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
