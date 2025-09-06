#!/usr/bin/env bash
set -euo pipefail

COMPOSE_FILE=${COMPOSE_FILE:-docker-compose.dev.yml}
PROFILE=${PROFILE:-dev}
DC="docker compose -f $COMPOSE_FILE --profile $PROFILE"

echo "==> Ensuring services are up..."
$DC up -d >/dev/null

wait_healthy () {
  local name="$1" tries=30
  echo -n "Waiting for $name to be healthy"
  until [ "$(docker inspect -f '{{.State.Health.Status}}' "$name" 2>/dev/null || echo starting)" = "healthy" ]; do
    ((tries--)) || { echo; echo "✗ $name not healthy"; exit 1; }
    echo -n "."
    sleep 2
  done
  echo " ✓"
}

wait_healthy dartech-postgres
wait_healthy dartech-redis

echo "==> Postgres SELECT 1"
docker exec -i dartech-postgres psql -U dartech -d dartech_dev -c "SELECT 1;" >/dev/null && echo "✓ SELECT 1 ok"

echo "==> Redis PING"
docker exec -i dartech-redis redis-cli PING | grep -q PONG && echo "✓ PONG"

echo "==> Symfony DB check"
if php bin/console doctrine:query:sql "SELECT 1" >/dev/null 2>&1; then
  echo "✓ doctrine:query ok"
else
  echo "✗ doctrine:query failed"; php bin/console doctrine:query:sql "SELECT 1" || true; exit 1
fi

echo "All green ✅"
