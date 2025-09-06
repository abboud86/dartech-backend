# Makefile — DX pour Docker Compose (profil dev)
SHELL := /bin/bash
DEFAULT_GOAL := help

COMPOSE_FILE ?= docker-compose.dev.yml
PROFILE ?= dev
DC = docker compose -f $(COMPOSE_FILE) --profile $(PROFILE)

.PHONY: help dc-up dc-down dc-down-v dc-ps dc-logs db-shell redis-shell dc-restart

help: ## Affiche cette aide
	@echo "Targets disponibles :"
	@grep -E '^[a-zA-Z0-9_\-]+:.*?##' Makefile | awk -F':|##' '{printf "  %-12s %s\n", $$1, $$3}'

dc-up: ## Démarre les services en arrière-plan
	$(DC) up -d

dc-down: ## Arrête les services (conserve les volumes)
	$(DC) down

dc-down-v: ## Arrête les services et supprime les volumes (⚠️ perte de données)
	$(DC) down -v

dc-ps: ## Liste l'état des services
	$(DC) ps

dc-logs: ## Suivi des logs
	$(DC) logs -f

db-shell: ## Shell psql dans le conteneur Postgres
	docker exec -it dartech-postgres psql -U dartech -d dartech_dev

redis-shell: ## Shell redis-cli dans le conteneur Redis
	docker exec -it dartech-redis redis-cli

dc-restart: ## Redémarre la stack
	$(DC) down
	$(DC) up -d

.PHONY: dc-validate
dc-validate: ## Valide la stack (health + SELECT 1 + PING)
	COMPOSE_FILE=$(COMPOSE_FILE) PROFILE=$(PROFILE) scripts/validate-dev.sh

# ---------- Healthz (HTTP/HTTPS) ----------
.PHONY: healthz
healthz: ## Appelle /healthz et pretty-print (HTTPS par défaut, redirections ok)
	@URL="$${HEALTHZ_URL:-https://127.0.0.1:8000/healthz}"; \
	curl -fsSL -k "$$URL" | (command -v jq >/dev/null 2>&1 && jq . || python3 -m json.tool)
