# Es Español Fácil

A diploma web project built with PHP and MySQL for Spanish learners, with CEFR placement, graded reading, grammar, vocabulary, exercises, and progress tracking.

## Features

- placement test across CEFR levels A1–C1;
- reading texts and grammar topics with level-based navigation;
- thematic vocabulary and automatically checked exercises;
- learner profiles with saved progress and test history;
- PDO queries, password hashing, session ID rotation, CSRF validation, and escaped HTML output.

The supplied source did not include a license or attribution record for the learning content or hero illustration. They remain in the local working copy, are ignored by Git, and are excluded from the committed snapshot until authorship and distribution rights are confirmed.

## Quick demo

Requires Docker Desktop with Docker Compose. A clean demo with fictional starter content is created by one command:

```bash
sh scripts/demo.sh
```

Open `http://127.0.0.1:8000`; set `APP_PORT` if that port is occupied. MySQL is not published on a host port. The demo uses local-only default credentials, which can be overridden with `DB_PASSWORD` and `MYSQL_ROOT_PASSWORD` in `.env`.

The public seed contains a small original A1 lesson and five placement questions written specifically for this portfolio demo. It is intentionally not a copy of the larger diploma content whose redistribution rights have not been confirmed.

To check the loaded data without putting the root password in shell history:

```bash
docker compose exec -T db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" es_espanol_facil' < database/check_counts.sql
```

## Accounts and data

Registration stores a password hash using PHP's `password_hash`; existing passwords are never stored or displayed. Use fictional data in this local portfolio demo. Email addresses are not verified, password reset and login rate limiting are not implemented, and the app has not been reviewed for production deployment.

If you already have a database from the earlier passwordless prototype, back it up before applying `database/migrations/001_add_password_hash.sql`. Existing profiles have no password hash and cannot log in until they are recreated or reset by a trusted administrator. Do not expose that older database or migrate real personal data into a public demo.

## Security notes

State-changing forms require a session-bound CSRF token. Session cookies use `HttpOnly` and `SameSite=Lax`; the `Secure` flag is enabled when PHP detects HTTPS. The landing page logs database diagnostics server-side and shows visitors a generic setup error.

These changes address specific issues found during a source review; they do not establish that the application is secure in every environment. Keep this project local until authentication, deployment settings, seed-content rights, and the complete source have been reviewed.

## Data model

The MySQL schema contains eight tables: levels, users, texts, grammar topics, vocabulary, exercises, user progress, and test results. A fresh clone includes only a compact, original demo dataset. The larger source collection remains excluded pending a rights review.

## Project structure

```text
config.php
includes/                 Session, output, CSRF, and application helpers
database/schema.sql       Database schema
database/demo_seed.sql    Original synthetic portfolio dataset
database/migrations/      Upgrade steps for an earlier local database
docker/php/Dockerfile     PHP Apache image with PDO MySQL
docker-compose.yml        Local app and database services
static/                   Styles and scripts
```

## Verification

The `scripts/demo.sh` check rebuilds a clean database, waits for the web app, and verifies the starter row counts. GitHub Actions runs the same flow on every push and pull request. Earlier browser checks covered registration, profile loading, sign-out, password sign-in, and rejection of GET logout. This is a portfolio demo, not a production deployment or a complete security certification.
