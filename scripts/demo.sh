#!/bin/sh
set -eu

docker compose down --volumes --remove-orphans
docker compose up --detach --build

attempt=0
until curl -fsS "http://127.0.0.1:${APP_PORT:-8000}/" >/dev/null; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 30 ]; then
        docker compose logs --no-color
        exit 1
    fi
    sleep 2
done

demo_check=$(docker compose exec -T db sh -c \
  'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" --batch --skip-column-names es_espanol_facil -e "SELECT IF((SELECT COUNT(*) FROM levels) = 5 AND (SELECT COUNT(*) FROM texts) = 1 AND (SELECT COUNT(*) FROM grammar_topics) = 1 AND (SELECT COUNT(*) FROM vocabulary) = 6 AND (SELECT COUNT(*) FROM exercises) = 8, \"ok\", \"invalid\");"' \
  2>/dev/null)

if [ "$demo_check" != "ok" ]; then
    printf 'Unexpected demo row counts: %s\n' "$demo_check" >&2
    exit 1
fi

printf '\nDemo is ready: http://127.0.0.1:%s\n' "${APP_PORT:-8000}"
