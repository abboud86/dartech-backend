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
