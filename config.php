<?php
declare(strict_types=1);

const APP_NAME = 'Es Español Fácil';
const APP_SUBTITLE = 'Интерактивное веб-приложение для изучения испанского языка';
const PASS_SCORE = 70;

date_default_timezone_set('Europe/Moscow');

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'es_espanol_facil');
define('DB_USER', getenv('DB_USER') ?: 'app_user');
define('DB_PASS', getenv('DB_PASSWORD') ?: '');

define('APP_BASE_URL', rtrim((string) (getenv('APP_BASE_URL') ?: ''), '/'));
