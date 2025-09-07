# RUNBOOK (Ops)

## 1. Variables d'environnement (prod)
- `APP_ENV=prod`, `APP_DEBUG=0`
- `DATABASE_URL` (PostgreSQL)
- `REDIS_URL`
- `APP_VERSION` (tag/sha build)
- `SENTRY_DSN` (optionnel → si vide, aucun envoi)

> En local prod, vous pouvez utiliser `.env.prod.local` (non commité) :
> ```dotenv
> APP_VERSION="0.1.0+local"
> SENTRY_DSN=""
> ```

## 2. Démarrage infra
```bash
make dc-up && make dc-ps
3. Healthcheck applicatif
bash
Copy code
curl -s http://<host>/healthz | (jq . 2>/dev/null || python3 -m json.tool)
# {"status":"ok","checks":{"db":"ok","redis":"ok"}}
4. Logs JSON
Dev : var/log/dev.json.log

Prod : stderr (récupéré par l’orchestrateur)

Lire la dernière ligne JSON :

bash
Copy code
grep -E '^\s*\{' /path/to/log | tail -n 1 | (jq . 2>/dev/null || python3 -m json.tool)
5. Sentry
Breadcrumbs via Monolog (INFO+) ; Events envoyés pour ERROR+.

Vérifier un envoi :

bash
Copy code
APP_ENV=prod APP_DEBUG=0 APP_VERSION=<version> SENTRY_DSN=<dsn> \
php bin/console app:log-demo "ops check sentry" --level=error 2>/dev/null
6. Base de données
Migrations :

bash
Copy code
php bin/console doctrine:migrations:migrate --no-interaction
Test connectivité :

bash
Copy code
php bin/console doctrine:query:sql "SELECT 1"
7. Redis
bash
Copy code
redis-cli -h <host> -p 6379 PING  # ou: docker exec -it dartech-redis redis-cli PING
8. Cibles Make utiles
make healthz : GET /healthz

make prod-smoke : event JSON ERROR en prod local

9. Troubleshooting
Env manquants (APP_VERSION, SENTRY_DSN) : définir via env (ou .env.prod.local) puis cache:clear --env=prod.

DB refusée : DATABASE_URL incorrecte ou DB non créée.

Logs multi-lignes : filtrer uniquement les lignes JSON (grep -E '^\{' | tail -n 1).

/healthz redirige : serveur Symfony local, utiliser l’URL en https sur 127.0.0.1:8000.
