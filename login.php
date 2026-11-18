<?php
require_once __DIR__ . '/includes/functions.php';

if (current_user()) {
    redirect('profile.php');
}

$pageTitle = 'Вход';
$errors = [];
$login = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $login = trim((string) ($_POST['login'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!preg_match('/^[A-Za-zА-Яа-яЁё0-9_.-]{3,40}$/u', $login)) {
        $errors[] = 'Логин должен содержать от 3 до 40 символов: буквы, цифры, точку, дефис или подчёркивание.';
    } elseif ($action === 'login') {
        $stmt = db()->prepare('SELECT id, password_hash FROM users WHERE login = ? LIMIT 1');
        $stmt->execute([$login]);
        $existing = $stmt->fetch();

        if (!$existing || empty($existing['password_hash']) || !password_verify($password, $existing['password_hash'])) {
            $errors[] = 'Не удалось войти. Проверьте логин и пароль или создайте новый профиль.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $existing['id'];
            if (password_needs_rehash($existing['password_hash'], PASSWORD_DEFAULT)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                db()->prepare('UPDATE users SET password_hash = ?, last_login_at = NOW() WHERE id = ?')
                    ->execute([$hash, (int) $existing['id']]);
            } else {
                db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')
                    ->execute([(int) $existing['id']]);
            }
            flash('success', 'С возвращением! Ваш прогресс загружен.');
            redirect('profile.php');
        }
    } elseif ($action === 'register') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Укажите корректный адрес электронной почты.';
        }
        if (strlen($password) < 12 || strlen($password) > 72) {
            $errors[] = 'Пароль должен содержать от 12 до 72 байт.';
        }

        if (!$errors) {
            $stmt = db()->prepare('SELECT id FROM users WHERE login = ? LIMIT 1');
            $stmt->execute([$login]);
            if ($stmt->fetch()) {
                $errors[] = 'Этот логин уже используется. Войдите в существующий профиль.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = db()->prepare(
                    'INSERT INTO users (login, email, password_hash, created_at, last_login_at)
                     VALUES (?, ?, ?, NOW(), NOW())'
                );
                $stmt->execute([$login, $email, $hash]);
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) db()->lastInsertId();
                flash('success', 'Профиль создан. Теперь определим стартовый уровень.');
                redirect('level_test.php');
            }
        }
    } else {
        $errors[] = 'Выберите вход или создание профиля.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="form-layout single">
    <form class="panel-form" method="post">
        <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
        <span class="eyebrow">вход и регистрация</span>
        <h1 class="auth-title">Войдите или создайте профиль</h1>

        <?php if ($errors): ?>
            <div class="form-errors">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <label>
            <span>Логин</span>
            <input type="text" name="login" value="<?= e($login) ?>" autocomplete="username" required minlength="3" maxlength="40">
        </label>

        <label>
            <span>Электронная почта для нового профиля</span>
            <input type="email" name="email" value="<?= e($email) ?>" autocomplete="email">
        </label>

        <label>
            <span>Пароль</span>
            <input type="password" name="password" autocomplete="current-password" required minlength="12" maxlength="72">
        </label>

        <div class="hero-actions">
            <button class="button primary" type="submit" name="action" value="login">Войти</button>
            <button class="button" type="submit" name="action" value="register">Создать профиль</button>
        </div>
        <p class="muted">Для демонстрационной версии указывайте только вымышленные данные.</p>
    </form>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
