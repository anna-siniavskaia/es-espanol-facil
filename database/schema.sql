CREATE DATABASE IF NOT EXISTS es_espanol_facil
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE es_espanol_facil;

CREATE TABLE IF NOT EXISTS levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(2) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    description TEXT NOT NULL,
    sort_order INT NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(40) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    current_level_id INT NULL,
    has_taken_level_test TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at DATETIME NULL,
    CONSTRAINT fk_users_current_level
        FOREIGN KEY (current_level_id) REFERENCES levels(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS texts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level_id INT NOT NULL,
    title VARCHAR(220) NOT NULL,
    content LONGTEXT NOT NULL,
    word_count INT NOT NULL DEFAULT 0,
    topic VARCHAR(140) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_text_level_title (level_id, title),
    KEY idx_texts_level (level_id),
    CONSTRAINT fk_texts_level
        FOREIGN KEY (level_id) REFERENCES levels(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS grammar_topics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level_id INT NOT NULL,
    title VARCHAR(220) NOT NULL,
    explanation LONGTEXT NOT NULL,
    examples LONGTEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 1,
    UNIQUE KEY uq_grammar_level_title (level_id, title),
    KEY idx_grammar_level (level_id),
    CONSTRAINT fk_grammar_level
        FOREIGN KEY (level_id) REFERENCES levels(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vocabulary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level_id INT NOT NULL,
    text_id INT NULL,
    spanish VARCHAR(160) NOT NULL,
    russian VARCHAR(220) NOT NULL,
    theme VARCHAR(140) NOT NULL,
    UNIQUE KEY uq_vocabulary_item (level_id, spanish, theme),
    KEY idx_vocabulary_level (level_id),
    KEY idx_vocabulary_text (text_id),
    CONSTRAINT fk_vocabulary_level
        FOREIGN KEY (level_id) REFERENCES levels(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_vocabulary_text
        FOREIGN KEY (text_id) REFERENCES texts(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exercises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level_id INT NOT NULL,
    parent_type ENUM('text', 'grammar', 'vocabulary', 'test') NOT NULL,
    parent_id INT NOT NULL DEFAULT 0,
    exercise_type ENUM('multiple_choice', 'fill_blank', 'matching', 'transformation', 'sequence') NOT NULL,
    question VARCHAR(700) NOT NULL,
    options LONGTEXT NULL,
    correct_answer LONGTEXT NOT NULL,
    explanation LONGTEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 1,
    UNIQUE KEY uq_exercise_position (parent_type, parent_id, sort_order),
    KEY idx_exercises_level (level_id),
    KEY idx_exercises_parent (parent_type, parent_id),
    CONSTRAINT fk_exercises_level
        FOREIGN KEY (level_id) REFERENCES levels(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    section_type ENUM('text', 'grammar', 'vocabulary', 'test') NOT NULL,
    section_id INT NOT NULL,
    status ENUM('not_started', 'in_progress', 'completed') NOT NULL DEFAULT 'not_started',
    score DECIMAL(5, 1) NOT NULL DEFAULT 0,
    attempts INT NOT NULL DEFAULT 0,
    last_activity_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    UNIQUE KEY uq_user_section (user_id, section_type, section_id),
    KEY idx_progress_user (user_id),
    CONSTRAINT fk_progress_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS test_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    test_type ENUM('placement', 'section') NOT NULL,
    score DECIMAL(5, 1) NOT NULL DEFAULT 0,
    determined_level_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_test_results_user (user_id),
    KEY idx_test_results_level (determined_level_id),
    CONSTRAINT fk_test_results_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_test_results_level
        FOREIGN KEY (determined_level_id) REFERENCES levels(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
