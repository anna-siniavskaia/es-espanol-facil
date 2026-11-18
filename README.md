# Es Español Fácil

A PHP and MySQL learning application for Spanish learners, with CEFR placement, graded reading, grammar, vocabulary, exercises, and progress tracking.

## Features

- placement test across CEFR levels A1–C1;
- reading texts and grammar topics with level-based navigation;
- thematic vocabulary and automatically checked exercises;
- learner profiles with saved progress and test history;
- PDO queries, password hashing, session ID rotation, CSRF validation, and escaped HTML output.

The supplied source did not include a license or attribution record for the learning content or hero illustration. They remain in the local working copy, are ignored by Git, and are excluded from the committed snapshot until authorship and distribution rights are confirmed.

## Local setup

Requires Docker Desktop with Docker Compose. Create local credentials and start the app:

```bash
cp .env.example .env
```

Edit `.env` and replace both placeholder passwords with different local secrets. Then run:

```bash
docker compose up --build
```

Open `http://127.0.0.1:8000` by default; set `APP_PORT` in `.env` if that port is occupied. The app and database ports are bound to local Docker networking; MySQL is not published on a host port. The app uses a dedicated MySQL user. Database initialization creates the schema on the first start of an empty database volume. The app starts without the learning content: learning tables remain empty until rights-approved data is imported locally.

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

The MySQL schema contains eight tables: levels, users, texts, grammar topics, vocabulary, exercises, user progress, and test results. The supplied source content contains 50 reading texts, 50 grammar topics, 300 vocabulary entries, and 535 exercises. The content is excluded from the tracked Git snapshot pending rights review, so a fresh clone does not include those learning materials.

## Project structure

```text
config.php
includes/                 Session, output, CSRF, and application helpers
database/schema.sql       Database schema
database/migrations/      Upgrade steps for an earlier local database
docker/php/Dockerfile     PHP Apache image with PDO MySQL
docker-compose.yml        Local app and database services
static/                   Styles and scripts
```

## Current verification status

Docker Compose configuration and image build succeeded. PHP syntax checks passed for all 17 PHP files. On an isolated schema-only database, browser checks verified registration with fictional data, the empty placement-test state, profile loading, sign-out after the session-ID change, and denial of profile access after sign-out. An earlier browser check verified password sign-in and that GET logout returns Method not allowed. The other empty-content pages, malformed-answer handling, and invalid-token CSRF rejection have not been checked end-to-end. The project has no automated test suite. The hero image and learning-content seed remain local but are omitted from the Git snapshot. This does not establish production readiness or complete security.
