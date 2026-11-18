<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_user();

$allowedTypes = ['text', 'grammar', 'vocabulary', 'test'];
$parentType = (string) ($_GET['type'] ?? 'text');
$parentId = isset($_GET['id']) ? (int) $_GET['id'] : null;

if (!in_array($parentType, $allowedTypes, true)) {
    flash('error', 'Неизвестный тип раздела.');
    redirect('profile.php');
}

if ($parentType !== 'test' && (!$parentId || $parentId < 1)) {
    flash('error', 'Не указан раздел для упражнений.');
    redirect('profile.php');
}

$pageTitle = 'Упражнения';
$exercises = get_exercises($parentType, $parentId);
$checked = null;
$submittedAnswers = submitted_answers();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $exercises) {
    verify_csrf();
    $checked = check_exercises($exercises, $submittedAnswers);
    save_progress((int) $user['id'], $parentType, (int) ($parentId ?: user_level_id($user)), (float) $checked['score']);
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div>
        <span class="eyebrow">универсальная проверка</span>
        <h1>Упражнения раздела</h1>
        <p><?= e(section_title($parentType, (int) ($parentId ?: user_level_id($user)))) ?></p>
    </div>
    <a class="button quiet" href="profile.php">Профиль</a>
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
    </section>
<?php endif; ?>

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
            <button class="button primary" type="submit">Проверить</button>
        </div>
    <?php else: ?>
        <section class="empty-state">
            <h2>Заданий пока нет</h2>
            <p>Учебные задания не включены в эту копию. Материалы можно добавить локально после проверки прав на распространение.</p>
        </section>
    <?php endif; ?>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
