# RUNBOOK — DarTech Backend (P0)

**Scope**: P0 — API stateless Symfony 7.3 (Fondations)  
**Invariants**
- Aucun secret en Git (utiliser `.env.local`, ignoré).
- Logs **JSON** : dev → fichier, prod → **stderr** (collecté par l’orchestrateur).
- CI verte obligatoire avant merge (PR protégées).

---

## 0) Checklist rapide (avant / après)
- [ ] Docker `db` + `redis` **UP** (healthy)
- [ ] `GET /healthz` : **200** (ok) ou **503** (degraded) avec `checks`
- [ ] `GET /v1/ping` : **401** sans token, **200** avec `Bearer dev-token`
- [ ] **CORS** : préflight `OPTIONS /v1/ping` ≠ 401 + headers présents
- [ ] **Logs** : dev → `var/log/app_dev.json`; prod → **stderr**
- [ ] **Sentry** (si DSN) : `/_sentry-test` génère **500** capturé

---

## 1) Démarrer / Arrêter (dev local)

### Prérequis
- PHP ≥ 8.4, Composer ≥ 2.8, Docker + Docker Compose

### Services (DB + Redis)
```bash
docker compose up -d
docker compose ps
docker logs dartech_db --tail=50
docker logs dartech_redis --tail=50
# Stop:
docker compose down
```

### Application Symfony
```bash
php -S 127.0.0.1:8000 -t public
# Sanity:
curl -i http://127.0.0.1:8000/healthz
```

---

## 2) Santé (Liveness / Readiness)

### Endpoints
- **Liveness**: `GET /healthz` → `200 {"status":"ok","time":...}`
- **Readiness**: `GET /healthz` → `200/503` + `checks.db|redis: up|down`

### Diagnostics
```bash
curl -s http://127.0.0.1:8000/healthz | jq .
docker compose exec -T db pg_isready -U "$POSTGRES_USER"
docker compose exec -T redis redis-cli PING
```

---

## 3) Sécurité (stateless)

- Firewall `main` **stateless** ; authenticator **Bearer**.
- 401 JSON via `JsonUnauthorizedEntryPoint`.
- Throttling login (rate limiter).

### Exemples
```bash
# Sans token → 401 JSON
curl -i http://127.0.0.1:8000/v1/ping

# Avec token dev → 200
curl -i -H "Authorization: Bearer ${API_DEV_TOKEN:-dev-token}" http://127.0.0.1:8000/v1/ping
```

---

## 4) Observabilité (Logs JSON + Sentry)

### Logs
- **dev** → `var/log/app_dev.json` (JSON lignes)
```bash
tail -n1 var/log/app_dev.json
```
- **prod** → **stderr** (JSON)
```bash
APP_ENV=prod APP_DEBUG=0 php -S 127.0.0.1:8000 -t public 1>/dev/null 2>/tmp/phpserver.err &
sleep 1; curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/v1/ping
kill %1
tail -n 50 /tmp/phpserver.err
```

### Sentry
- DSN via `SENTRY_DSN` (ne jamais committer de valeur réelle).
- Route test : `GET /_sentry-test` → **500** (capture attendue).

---

## 5) CORS (Nelmio)

- Préflight **autorisé** sur `/v1/*`.
```bash
curl -i -X OPTIONS   -H 'Origin: http://localhost:3000'   -H 'Access-Control-Request-Method: GET'   http://127.0.0.1:8000/v1/ping | sed -n '1p;/^Access-Control-Allow-Origin/p;/^Access-Control-Allow-Methods/p'
```

---

## 6) Tests & CI

### Tests PHPUnit
- Healthz : `tests/Controller/HealthzTest.php`
- Security : `tests/Security/SecurityTest.php`
```bash
php bin/phpunit --testdox
```

### CI (GitHub Actions)
- Job requis : **CI / php** (composer validate).
- PR : Draft → CI verte → Review (CODEOWNERS) → **Squash & Merge**.

---

## 7) SLO / SLI (P0)

- Objectif initial : **p95 < 300 ms** (endpoints critiques).
- Mesure minimale via logs ; métriques complètes en P5/P6.

---

## 8) Playbooks Incidents

**8.1 API 5xx**
1. `GET /healthz` (readiness)
2. Logs (dev: fichier, prod: **stderr**)
3. DB/Redis via `docker compose exec`

**8.2 401 inattendus**
1. Vérifier header `Authorization: Bearer …`
2. Throttling actif (rate limiter)
3. Logs channel **security** (prod: stderr)

**8.3 Sentry**
1. DSN configuré ?
2. `/_sentry-test` → 500 → événement capturé ?

**8.4 Cache/Config prod**
```bash
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear --no-warmup
APP_ENV=prod APP_DEBUG=0 php bin/console cache:warmup
```

---

## 9) Restauration (P0)
- DB : volume compose `dartech-backend_pg_data`
- Redis : volatile
- Secrets : `.env.local` (jamais en Git)

---

## Annexes

### Variables d’environnement (exemples)
```ini
API_DEV_TOKEN=dev-token
SENTRY_DSN=
CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:\d+)?$
POSTGRES_USER=dartech
POSTGRES_PASSWORD=changeme
POSTGRES_DB=dartech_dev
```

### Endpoints utiles
- `GET /healthz` — liveness/readiness
- `GET /v1/ping` — protégé (401/200)
- `GET /_sentry-test` — génère 500 (Sentry)
