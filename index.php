<?php
$pageTitle = 'Главная';
require_once __DIR__ . '/includes/header.php';

$user = current_user();
$dbReady = true;
$levels = [];

try {
    $levels = get_levels();
} catch (Throwable $error) {
    $dbReady = false;
    error_log('Database setup check failed: ' . $error->getMessage());
}
?>

<?php if (!$dbReady): ?>
    <section class="setup-panel">
        <div>
            <span class="eyebrow">первый запуск</span>
            <h1>База данных пока недоступна</h1>
            <p>Создайте локальный файл конфигурации по инструкции в README и запустите Docker Compose.</p>
        </div>
        <pre><code>cp .env.example .env
docker compose up --build</code></pre>
        <p class="muted">Не удалось подключиться к базе данных. Проверьте локальную конфигурацию и журнал PHP.</p>
    </section>
<?php elseif ($user): ?>
    <section class="hero dashboard-hero">
        <div class="hero-copy">
            <span class="eyebrow">личный маршрут</span>
            <h1>Hola, <?= e($user['login']) ?>!</h1>
            <p>
                Ваш текущий уровень:
                <strong><?= e($user['level_code'] ?: 'ещё не определён') ?></strong>.
                Продолжайте с текстов, грамматики или лексики, а результаты сохранятся в профиле.
            </p>
            <div class="hero-actions">
                <?php if (!$user['has_taken_level_test']): ?>
                    <a class="button primary" href="level_test.php">Пройти тест</a>
                <?php endif; ?>
                <a class="button" href="texts.php">Открыть тексты</a>
                <a class="button quiet" href="profile.php">Профиль</a>
            </div>
        </div>
    </section>

    <section class="quick-grid">
        <a class="feature-card" href="texts.php">
            <span class="feature-icon">T</span>
            <h2>Тексты</h2>
            <p>10 адаптированных текстов на каждом уровне A1-C1 с заданиями и словарём.</p>
        </a>
        <a class="feature-card" href="grammar.php">
            <span class="feature-icon">G</span>
            <h2>Грамматика</h2>
            <p>Правила на русском языке, примеры на испанском и тренировочные упражнения.</p>
        </a>
        <a class="feature-card" href="vocabulary.php">
            <span class="feature-icon">V</span>
            <h2>Лексика</h2>
            <p>Тематические словари, сопоставление, пропуски и закрепление слов в контексте.</p>
        </a>
        <a class="feature-card" href="progress.php">
            <span class="feature-icon">%</span>
            <h2>Прогресс</h2>
            <p>Статусы прохождения, лучшие баллы, попытки и дата последней активности.</p>
        </a>
    </section>
<?php else: ?>
    <section class="hero">
        <div class="hero-copy">
            <span class="eyebrow">A1-C1 · интерактивное обучение</span>
            <h1><?= e(APP_NAME) ?></h1>
            <p><?= e(APP_SUBTITLE) ?>: тексты, грамматика, лексика, тест уровня и сохранение прогресса.</p>
            <div class="hero-actions">
                <a class="button primary" href="login.php">Начать обучение</a>
                <a class="button" href="level_test.php">Посмотреть тест</a>
            </div>
        </div>
    </section>

    <?php if ($levels): ?>
    <section class="level-overview">
        <div class="section-heading">
            <span class="eyebrow">уровни CEFR</span>
            <h2>Материалы распределены от A1 до C1</h2>
        </div>
        <div class="level-grid">
            <?php foreach ($levels as $level): ?>
                <article class="level-card">
                    <strong><?= e($level['code']) ?></strong>
                    <h3><?= e($level['name']) ?></h3>
                    <p><?= e($level['description']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php else: ?>
    <section class="empty-state">
        <h2>Учебные материалы пока не установлены</h2>
        <p>Свежая копия содержит схему приложения, но не включает тексты и задания. Материалы можно добавить локально после проверки прав на распространение.</p>
    </section>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
