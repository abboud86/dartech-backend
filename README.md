# DarTech Backend

Backend API Symfony (7.x).

## Prérequis (local)
- PHP 8.3+
- Composer 2.7+
- Symfony CLI
- Docker (PostgreSQL)

> La mise en place suivra la **documentation officielle Symfony** et les bonnes pratiques.


# Dartech Backend — Symfony 7.3

## Prérequis
- PHP 8.4, Composer 2
- MySQL 8 (dev) • SQLite (CI)
- Nginx + PHP-FPM (prod/dev)

## Setup local (dev)
1. Installer les dépendances :
   ```bash
   composer install
   ```
2. Créer `.env.local` (pas de secrets en git) :
   ```dotenv
   APP_ENV=dev
   DATABASE_URL="mysql://app:app@127.0.0.1:3306/dartech?serverVersion=8.0.37&charset=utf8mb4"
   ```
3. Créer la base & vérifier la connectivité :
   ```bash
   php bin/console doctrine:database:create
   php bin/console dbal:run-sql "SELECT 1"
   ```
4. Tests & qualité :
   ```bash
   php bin/phpunit
   composer cs:check
   composer stan
   ```

## Makefile (DX)
```bash
make install    # composer install
make qa         # cs + stan
make cs         # style (PSR-12)
make fix        # auto-fix CS
make stan       # PHPStan
make test       # phpunit --no-coverage
```

## Nginx (recommandé Symfony)
```nginx
server {
    server_name _;
    root /ABSOLUTE/PATH/TO/project/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock; # adapte si besoin
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT  $realpath_root;
        internal;
    }

    location ~ \.php$ { return 404; }

    access_log /var/log/nginx/dartech_access.log;
    error_log  /var/log/nginx/dartech_error.log;
}
```

## CI (GitHub Actions)
- PHP 8.4, DB **SQLite**, `composer install --no-scripts`, `SYMFONY_SKIP_AUTO_SCRIPTS=1`
- Jobs : `composer validate`, `composer cs:check`, `composer stan`, `phpunit --no-coverage`
- Aucun secret requis

## Vérifs finales
```bash
composer validate --strict --no-check-publish
composer cs:check
composer stan
php bin/phpunit
curl -I http://127.0.0.1/ | head -n1   # attendu: HTTP/1.1 200
php bin/console dbal:run-sql "SELECT 1"
```

## Notes
- Env dev/test via `.env.local` / `.env.test.local` (jamais de secrets en git).
- Les dépréciations Doctrine sont neutralisées **en dev** via `config/packages/dev/doctrine.yaml`.

## Local dev — Docker Compose (Postgres 16 + Redis 7)

### Prérequis
- Docker & Docker Compose v2
- Make (optionnel mais recommandé)

### Démarrer/arrêter (profil `dev`)
```bash
make dc-up        # démarre postgres + redis
make dc-ps        # statut (doit afficher healthy)
make dc-logs      # logs suivis
make dc-restart   # restart rapide
make dc-down      # stop (conserve volumes)
make dc-down-v    # stop + supprime volumes (⚠ perte données locales)
Services & ports
Postgres: localhost:5432 (db: dartech_dev, user/pass: dartech)

Redis: localhost:6379

Variables d’environnement (exemples)
Copiez .env.example vers vos fichiers locaux non committés :

bash
Copy code
cp .env.example .env.local
DATABASE_URL="postgresql://dartech:dartech@127.0.0.1:5432/dartech_dev?serverVersion=16&charset=utf8"

REDIS_URL="redis://127.0.0.1:6379"

Ne commitez jamais vos .env.local (Symfony lit automatiquement .env.local en dev).

Troubleshooting
Ports occupés : modifiez les mappings dans docker-compose.dev.yml (ex. 15432:5432, 16379:6379) puis make dc-restart.

Reset complet : make dc-down-v puis make dc-up.

Redis "Memory overcommit" (warning kernel) :

bash
Copy code
sudo sysctl vm.overcommit_memory=1
# Perso : echo 'vm.overcommit_memory=1' | sudo tee /etc/sysctl.d/99-redis-overcommit.conf && sudo sysctl -p /etc/sysctl.d/99-redis-overcommit.conf
Vérifs santé rapides :

bash
Copy code
make dc-ps
docker exec -it dartech-postgres psql -U dartech -d dartech_dev -c "SELECT 1;"
docker exec -it dartech-redis redis-cli PING
Politique secrets
Utilisez .env.local pour vos valeurs locales (non committées).

Pour prod/staging : Symfony Secrets et variables d’environnement (pas de secrets en repo).
