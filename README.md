# Dartech Backend (Symfony 7.3)

Backend API en Symfony 7.3 (PHP 8.4). Stack basée sur Docker (PostgreSQL 16 + Redis 7), logs **JSON** (dev: fichier, prod: stderr) et intégration **Sentry** via Monolog.

## Stack
- PHP 8.4, Symfony 7.3
- PostgreSQL 16 (Docker)
- Redis 7 (Docker)
- Monolog JSON (dev: `var/log/dev.json.log`, prod: `stderr`)
- Sentry (breadcrumbs INFO+, events ERROR+)

## Prérequis
- **Docker / Docker Compose v2**
- PHP 8.4 + **Composer 2**
- (Optionnel) Symfony CLI
- Make (recommandé)

---

## Quickstart (dev)

### 1) Lancer l’infra (profil `dev`)
```bash
make dc-up
make dc-ps   # doit afficher postgres/redis = healthy
2) Base de données dev
Vérifier/créer la base dartech_dev dans le conteneur :

bash
Copy code
docker exec -i dartech-postgres psql -U dartech -d postgres -tAc \
  "SELECT 1 FROM pg_database WHERE datname='dartech_dev'" | grep -q 1 \
  || docker exec -i dartech-postgres psql -U dartech -d postgres -c "CREATE DATABASE dartech_dev;"
L’URL dev est stockée dans .env :

DATABASE_URL="postgresql://dartech:dartech@127.0.0.1:5432/dartech_dev?serverVersion=16&charset=utf8"

3) Healthcheck applicatif
bash
Copy code
symfony server:start -d 2>/dev/null || true
curl -s https://127.0.0.1:8000/healthz | (jq . 2>/dev/null || python3 -m json.tool)
# attendu: {"status":"ok","checks":{"db":"ok","redis":"ok"}}
4) Logs JSON (dev & prod local)
bash
Copy code
# DEV → écrit une ligne JSON dans var/log/dev.json.log
php bin/console app:log-demo "hello json" --level=info --context='{"foo":"bar"}'
tail -n 1 var/log/dev.json.log | (jq . 2>/dev/null || python3 -m json.tool)

# PROD local → JSON sur stderr (redirigé vers un fichier)
: > /tmp/prod.json.log
APP_ENV=prod APP_DEBUG=0 APP_VERSION="0.1.0+local" SENTRY_DSN="" \
php bin/console app:log-demo "boom prod" --level=error 2>> /tmp/prod.json.log
grep -E '^\s*\{' /tmp/prod.json.log | tail -n 1 | (jq . 2>/dev/null || python3 -m json.tool)
5) Tests (DB suffixée _test)
Doctrine ajoute un suffixe _test en environnement test (voir config/packages/test/doctrine.yaml).

Créer la base de test et valider :

bash
Copy code
php bin/console cache:clear --env=test --no-debug
docker exec -i dartech-postgres psql -U dartech -d postgres -tAc \
  "SELECT 1 FROM pg_database WHERE datname='dartech_dev_test'" | grep -q 1 \
  || docker exec -i dartech-postgres psql -U dartech -d postgres -c "CREATE DATABASE dartech_dev_test;"

php bin/console doctrine:query:sql "SELECT 1" --env=test
php bin/phpunit --no-coverage
Makefile (DX)
dc-up / dc-down / dc-ps / dc-logs / dc-restart

db-shell (psql), redis-shell

healthz → appelle GET /healthz

prod-smoke → produit un event JSON ERROR en “prod” local

Qualité/tests : composer cs:check, composer stan, php bin/phpunit

Environnements & .env
.env : valeurs par défaut sans secrets (dev).

.env.test : hérite de .env + suffixe _test (Doctrine).

Jamais de secrets en git → utiliser .env.local, .env.test.local.

Prod : variables d’environnement ou composer dump-env prod.

Vars importantes :

DATABASE_URL (PostgreSQL), REDIS_URL

APP_VERSION (tag/sha)

SENTRY_DSN (si vide ⇒ aucun envoi)

Observabilité
Logs JSON : 1 événement = 1 ligne JSON.

Sentry : breadcrumbs INFO+; envoi d’events ERROR+.

Endpoints
GET /healthz → {"status":"ok","checks":{"db":"ok","redis":"ok"}}

Troubleshooting
Ports occupés : modifier les ports dans docker-compose.dev.yml puis make dc-restart.

Redis “Memory overcommit” (warning kernel) :

bash
Copy code
sudo sysctl vm.overcommit_memory=1
/healthz redirige HTTPS (serveur Symfony) : utiliser l’URL en https sur 127.0.0.1:8000.

Connexion DB échouée : vérifier DATABASE_URL et que la base existe (dartech_dev, dartech_dev_test).

Runbook (ops)
Voir RUNBOOK.md pour les opérations (prod), Sentry, migrations, et checks.

---

## Request Correlation (X-Request-Id)

**But** : corréler une requête HTTP dans toute la stack (headers ↔ logs ↔ Sentry).

### Règles
- Entrant : on lit `X-Request-Id` (ou `X-Correlation-Id`). Si absent/invalid → génération **UUIDv4**.
- Sortant : on renvoie toujours `X-Request-Id` dans la réponse.
- Logs : Monolog ajoute `extra.request_id`.
- Sentry : tag `request_id`.

### Smokes rapides
```bash
# génération auto
curl -is http://<host>/healthz | grep -i '^X-Request-Id:'

# réutilisation fournie
curl -is http://<host>/healthz -H 'X-Request-Id: foo-123' | grep -i '^X-Request-Id:'
Journalisation (extrait JSON)
json
Copy code
{
  "message": "smoke-log",
  "channel": "app",
  "level": 200,
  "extra": { "request_id": "foo-123" }
}
Paramètres (config/services.yaml)
app.request_id.header = "X-Request-Id"

app.request_id.synonyms = ["X-Correlation-Id"]

app.request_id.trust_client_id = true (accepter l’ID client s’il est sûr)

Notes
En CLI (pas de requête), extra.request_id peut être absent — normal.

SENTRY_DSN vide ⇒ taggage ignoré sans erreur.
