<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_user();
$pageTitle = 'Тексты';

$levels = get_levels();
$activeLevelId = selected_level_id($user);
$activeLevel = get_level_by_id($activeLevelId);

$stmt = db()->prepare(
    "SELECT t.*, l.code AS level_code, up.status, up.score, up.attempts
     FROM texts t
     JOIN levels l ON l.id = t.level_id
     LEFT JOIN user_progress up
        ON up.section_type = 'text' AND up.section_id = t.id AND up.user_id = ?
     WHERE t.level_id = ?
     ORDER BY t.id"
);
$stmt->execute([(int) $user['id'], $activeLevelId]);
$texts = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div>
        <span class="eyebrow">чтение и понимание</span>
        <h1>Тексты <?= e($activeLevel['code'] ?? '') ?></h1>
        <p>Каждый текст сопровождается глоссарием и заданиями на понимание содержания.</p>
    </div>
    <?php render_level_tabs($levels, $activeLevelId, 'texts.php'); ?>
</section>

<?php if (!$texts): ?>
    <section class="empty-state">
        <h2>Текстов пока нет</h2>
        <p>Учебные материалы не включены в эту копию. Их можно добавить локально после проверки прав на распространение.</p>
    </section>
<?php else: ?>
<section class="item-grid">
    <?php foreach ($texts as $text): ?>
        <article class="item-card">
            <div class="card-meta">
                <span><?= e($text['level_code']) ?></span>
                <span><?= (int) $text['word_count'] ?> слов</span>
                <span class="status <?= e(progress_class($text['status'] ?? null)) ?>">
                    <?= e(progress_label($text['status'] ?? 'not_started')) ?>
                </span>
            </div>
            <h2><?= e($text['title']) ?></h2>
            <p><?= e(summarize_text($text['content'])) ?></p>
            <div class="card-footer">
                <span><?= e($text['topic']) ?></span>
                <a class="button small" href="text.php?id=<?= (int) $text['id'] ?>">Открыть</a>
            </div>
        </article>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
