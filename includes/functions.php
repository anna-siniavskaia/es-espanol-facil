<?php
declare(strict_types=1);

require_once __DIR__ . '/../db_connect.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $isHttps,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

if (!function_exists('array_is_list')) {
    function array_is_list(array $array): bool
    {
        $expectedKey = 0;
        foreach ($array as $key => $_) {
            if ($key !== $expectedKey++) {
                return false;
            }
        }

        return true;
    }
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (!isset($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['_csrf_token'];
}

function verify_csrf(): void
{
    $submitted = $_POST['_csrf_token'] ?? null;
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(400);
        exit('Invalid request. Please reload the page and try again.');
    }
}

function redirect(string $path): void
{
    $target = APP_BASE_URL !== ''
        ? APP_BASE_URL . '/' . ltrim($path, '/')
        : ltrim($path, '/');
    header('Location: ' . $target);
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }

    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }

    $value = (string) $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $value;
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    try {
        $stmt = db()->prepare(
            'SELECT u.*, l.code AS level_code, l.name AS level_name
             FROM users u
             LEFT JOIN levels l ON l.id = u.current_level_id
             WHERE u.id = ?'
        );
        $stmt->execute([(int) $_SESSION['user_id']]);
        $user = $stmt->fetch();
    } catch (Throwable) {
        return null;
    }

    return $user ?: null;
}

function require_user(): array
{
    $user = current_user();
    if (!$user) {
        flash('warning', 'Сначала войдите в приложение.');
        redirect('login.php');
    }

    return $user;
}

function get_levels(): array
{
    return db()->query('SELECT * FROM levels ORDER BY sort_order')->fetchAll();
}

function get_level_by_code(string $code): ?array
{
    $stmt = db()->prepare('SELECT * FROM levels WHERE code = ? LIMIT 1');
    $stmt->execute([$code]);
    $level = $stmt->fetch();
    return $level ?: null;
}

function get_level_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM levels WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $level = $stmt->fetch();
    return $level ?: null;
}

function user_level_id(array $user): int
{
    if (!empty($user['current_level_id'])) {
        return (int) $user['current_level_id'];
    }

    $a1 = get_level_by_code('A1');
    return (int) ($a1['id'] ?? 1);
}

function selected_level_id(array $user): int
{
    $requested = isset($_GET['level']) ? (int) $_GET['level'] : 0;
    if ($requested > 0 && get_level_by_id($requested)) {
        return $requested;
    }

    return user_level_id($user);
}

function normalize_answer(mixed $value): string
{
    if (!is_scalar($value) && $value !== null) {
        return '';
    }
    $value = trim((string) $value);
    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
    $value = trim($value, " \t\n\r\0\x0B.,;:!?¡¿\"'");

    $lower = function_exists('mb_strtolower')
        ? mb_strtolower($value, 'UTF-8')
        : strtolower($value);

    return strtr($lower, [
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'ü' => 'u',
        'Á' => 'a',
        'É' => 'e',
        'Í' => 'i',
        'Ó' => 'o',
        'Ú' => 'u',
        'Ü' => 'u',
    ]);
}

function decode_json_field(?string $value): mixed
{
    if ($value === null || trim($value) === '') {
        return null;
    }

    $decoded = json_decode($value, true);
    return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
}

function expected_answers(string $answer): array
{
    $decoded = decode_json_field($answer);
    if (is_array($decoded) && array_is_list($decoded)) {
        return array_map('normalize_answer', $decoded);
    }

    return array_map('normalize_answer', explode('|', $answer));
}

function exercise_is_correct(array $exercise, mixed $rawAnswer): bool
{
    $type = (string) $exercise['exercise_type'];

    if ($type === 'matching') {
        $expected = decode_json_field((string) $exercise['correct_answer']);
        if (!is_array($expected) || !is_array($rawAnswer)) {
            return false;
        }

        foreach ($expected as $left => $right) {
            if (!array_key_exists($left, $rawAnswer)) {
                return false;
            }
            if (normalize_answer($rawAnswer[$left]) !== normalize_answer($right)) {
                return false;
            }
        }

        return true;
    }

    $answer = normalize_answer($rawAnswer);
    return in_array($answer, expected_answers((string) $exercise['correct_answer']), true);
}

function get_exercises(string $parentType, ?int $parentId = null, ?int $levelId = null): array
{
    $sql = 'SELECT * FROM exercises WHERE parent_type = ?';
    $params = [$parentType];

    if ($parentId !== null) {
        $sql .= ' AND parent_id = ?';
        $params[] = $parentId;
    }

    if ($levelId !== null) {
        $sql .= ' AND level_id = ?';
        $params[] = $levelId;
    }

    $sql .= ' ORDER BY sort_order, id';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function submitted_answers(): array
{
    $answers = $_POST['answers'] ?? [];
    return is_array($answers) ? $answers : [];
}

function check_exercises(array $exercises, array $answers): array
{
    $results = [];
    $correct = 0;

    foreach ($exercises as $exercise) {
        $id = (int) $exercise['id'];
        $rawAnswer = $answers[$id] ?? '';
        $isCorrect = exercise_is_correct($exercise, $rawAnswer);

        if ($isCorrect) {
            $correct++;
        }

        $results[$id] = [
            'correct' => $isCorrect,
            'answer' => $rawAnswer,
            'explanation' => (string) $exercise['explanation'],
            'correct_answer' => (string) $exercise['correct_answer'],
        ];
    }

    $total = count($exercises);
    $score = $total > 0 ? round(($correct / $total) * 100, 1) : 0.0;

    return [
        'results' => $results,
        'correct' => $correct,
        'total' => $total,
        'score' => $score,
    ];
}

function save_progress(int $userId, string $sectionType, int $sectionId, float $score): void
{
    $status = $score >= PASS_SCORE ? 'completed' : 'in_progress';
    $completedAt = $status === 'completed' ? date('Y-m-d H:i:s') : null;

    $stmt = db()->prepare(
        'INSERT INTO user_progress
            (user_id, section_type, section_id, status, score, attempts, last_activity_at, completed_at)
         VALUES
            (?, ?, ?, ?, ?, 1, NOW(), ?)
         ON DUPLICATE KEY UPDATE
            attempts = attempts + 1,
            score = GREATEST(score, VALUES(score)),
            status = IF(GREATEST(score, VALUES(score)) >= ?, "completed", "in_progress"),
            last_activity_at = NOW(),
            completed_at = IF(GREATEST(score, VALUES(score)) >= ?, COALESCE(completed_at, NOW()), completed_at)'
    );

    $stmt->execute([$userId, $sectionType, $sectionId, $status, $score, $completedAt, PASS_SCORE, PASS_SCORE]);
}

function progress_label(string $status): string
{
    return match ($status) {
        'completed' => 'завершено',
        'in_progress' => 'в процессе',
        default => 'не начато',
    };
}

function progress_class(?string $status): string
{
    return match ($status) {
        'completed' => 'is-complete',
        'in_progress' => 'is-progress',
        default => 'is-new',
    };
}

function determine_level_by_score(float $score): string
{
    return match (true) {
        $score >= 85 => 'C1',
        $score >= 68 => 'B2',
        $score >= 50 => 'B1',
        $score >= 32 => 'A2',
        default => 'A1',
    };
}

function summarize_text(string $text, int $limit = 210): string
{
    $plain = trim(strip_tags($text));
    if (function_exists('mb_strlen') && mb_strlen($plain, 'UTF-8') <= $limit) {
        return $plain;
    }
    if (!function_exists('mb_substr')) {
        return strlen($plain) <= $limit ? $plain : substr($plain, 0, $limit) . '...';
    }

    return mb_substr($plain, 0, $limit, 'UTF-8') . '...';
}

function render_level_tabs(array $levels, int $activeLevelId, string $target): void
{
    echo '<div class="level-tabs" role="tablist">';
    foreach ($levels as $level) {
        $active = (int) $level['id'] === $activeLevelId ? ' is-active' : '';
        echo '<a class="level-tab' . $active . '" href="' . e($target) . '?level=' . (int) $level['id'] . '">' . e($level['code']) . '</a>';
    }
    echo '</div>';
}

function render_exercise_field(array $exercise, mixed $previousAnswer = null, ?array $result = null, ?int $displayNumber = null): void
{
    $id = (int) $exercise['id'];
    $type = (string) $exercise['exercise_type'];
    $options = decode_json_field($exercise['options'] ?? null);
    $state = '';
    if ($result) {
        $state = $result['correct'] ? ' exercise-correct' : ' exercise-wrong';
    }

    echo '<article class="exercise' . $state . '">';
    echo '<div class="exercise-head">';
    echo '<span class="exercise-type">' . e(exercise_type_label($type)) . '</span>';
    $number = $displayNumber ?? $id;
    echo '<span class="exercise-number">' . (int) $number . '</span>';
    echo '</div>';
    echo '<p class="exercise-question">' . e($exercise['question']) . '</p>';

    if ($type === 'multiple_choice' && is_array($options)) {
        echo '<div class="choice-grid">';
        foreach ($options as $option) {
            $checked = normalize_answer($previousAnswer) === normalize_answer($option) ? ' checked' : '';
            echo '<label class="choice-option">';
            echo '<input type="radio" name="answers[' . $id . ']" value="' . e($option) . '"' . $checked . ' required>';
            echo '<span>' . e($option) . '</span>';
            echo '</label>';
        }
        echo '</div>';
    } elseif ($type === 'matching' && is_array($options)) {
        $leftItems = array_keys($options);
        $rightItems = array_values($options);
        sort($rightItems);
        echo '<div class="matching-grid">';
        foreach ($leftItems as $left) {
            $chosen = is_array($previousAnswer) ? ($previousAnswer[$left] ?? '') : '';
            echo '<label class="matching-row">';
            echo '<span>' . e($left) . '</span>';
            echo '<select name="answers[' . $id . '][' . e($left) . ']" required>';
            echo '<option value="">Выберите пару</option>';
            foreach ($rightItems as $right) {
                $selected = normalize_answer($chosen) === normalize_answer($right) ? ' selected' : '';
                echo '<option value="' . e($right) . '"' . $selected . '>' . e($right) . '</option>';
            }
            echo '</select>';
            echo '</label>';
        }
        echo '</div>';
    } else {
        $placeholder = $type === 'sequence'
            ? 'Введите слова в правильном порядке'
            : 'Введите ответ';
        $inputValue = is_scalar($previousAnswer) ? (string) $previousAnswer : '';
        echo '<input class="answer-input" type="text" name="answers[' . $id . ']" value="' . e($inputValue) . '" placeholder="' . e($placeholder) . '" required>';
    }

    if ($result) {
        echo '<div class="feedback ' . ($result['correct'] ? 'good' : 'bad') . '">';
        echo $result['correct'] ? '<strong>Верно.</strong>' : '<strong>Есть ошибка.</strong>';
        echo ' ' . e($result['explanation']);
        if (!$result['correct'] && $type !== 'matching') {
            echo '<span class="correct-answer">Правильный ответ: ' . e(first_correct_answer((string) $exercise['correct_answer'])) . '</span>';
        }
        echo '</div>';
    }

    echo '</article>';
}

function exercise_type_label(string $type): string
{
    return match ($type) {
        'multiple_choice' => 'выбор ответа',
        'fill_blank' => 'пропуск',
        'matching' => 'сопоставление',
        'transformation' => 'трансформация',
        'sequence' => 'порядок слов',
        default => 'задание',
    };
}

function first_correct_answer(string $answer): string
{
    $decoded = decode_json_field($answer);
    if (is_array($decoded) && array_is_list($decoded)) {
        return (string) ($decoded[0] ?? '');
    }
    if (is_array($decoded)) {
        return implode('; ', array_map(
            static fn ($left, $right) => $left . ' - ' . $right,
            array_keys($decoded),
            array_values($decoded)
        ));
    }

    return explode('|', $answer)[0] ?? $answer;
}

function section_title(string $type, int $id): string
{
    if ($type === 'text') {
        $stmt = db()->prepare('SELECT title FROM texts WHERE id = ?');
    } elseif ($type === 'grammar') {
        $stmt = db()->prepare('SELECT title FROM grammar_topics WHERE id = ?');
    } elseif ($type === 'vocabulary') {
        $stmt = db()->prepare('SELECT CONCAT("Лексика уровня ", code) AS title FROM levels WHERE id = ?');
    } else {
        $stmt = db()->prepare('SELECT CONCAT("Тест уровня ", code) AS title FROM levels WHERE id = ?');
    }

    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return (string) ($row['title'] ?? 'Раздел');
}
