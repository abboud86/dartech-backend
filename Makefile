
up:
\tdocker --env-file ./.env.docker compose up -d

down:
\tdocker --env-file ./.env.docker compose down -v

ps:
\tdocker --env-file ./.env.docker compose ps

db-sql:
\tphp bin/console doctrine:query:sql 'SELECT 1'

migrate-status:
\tphp bin/console doctrine:migrations:status
