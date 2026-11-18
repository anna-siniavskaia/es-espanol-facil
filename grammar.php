<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_user();
$pageTitle = 'Грамматика';

$levels = get_levels();
$activeLevelId = selected_level_id($user);
$activeLevel = get_level_by_id($activeLevelId);

$stmt = db()->prepare(
    "SELECT gt.*, l.code AS level_code, up.status, up.score
     FROM grammar_topics gt
     JOIN levels l ON l.id = gt.level_id
     LEFT JOIN user_progress up
        ON up.section_type = 'grammar' AND up.section_id = gt.id AND up.user_id = ?
     WHERE gt.level_id = ?
     ORDER BY gt.sort_order, gt.id"
);
$stmt->execute([(int) $user['id'], $activeLevelId]);
$topics = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div>
        <span class="eyebrow">правила и практика</span>
        <h1>Грамматика <?= e($activeLevel['code'] ?? '') ?></h1>
        <p>Темы расположены от базовых конструкций к более сложным моделям.</p>
    </div>
    <?php render_level_tabs($levels, $activeLevelId, 'grammar.php'); ?>
</section>

<?php if (!$topics): ?>
    <section class="empty-state">
        <h2>Тем пока нет</h2>
        <p>Учебные материалы не включены в эту копию. Их можно добавить локально после проверки прав на распространение.</p>
    </section>
<?php else: ?>
<section class="item-grid two">
    <?php foreach ($topics as $topic): ?>
        <article class="item-card">
            <div class="card-meta">
                <span><?= e($topic['level_code']) ?></span>
                <span>тема <?= (int) $topic['sort_order'] ?></span>
                <span class="status <?= e(progress_class($topic['status'] ?? null)) ?>">
                    <?= e(progress_label($topic['status'] ?? 'not_started')) ?>
                </span>
            </div>
            <h2><?= e($topic['title']) ?></h2>
            <p><?= e(summarize_text($topic['explanation'], 260)) ?></p>
            <div class="card-footer">
                <span><?= $topic['score'] ? e($topic['score']) . '%' : 'без результата' ?></span>
                <a class="button small" href="grammar_topic.php?id=<?= (int) $topic['id'] ?>">Изучить</a>
            </div>
        </article>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
