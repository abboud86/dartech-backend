#!/usr/bin/env bash
set -euo pipefail
echo "=== GIT ==="; git rev-parse --abbrev-ref HEAD; git status -sb; echo
echo "=== ENV/KERNEL ==="; php bin/console about | sed -n '1,25p'; echo
echo "=== DB (dev/test) ==="
php bin/console doctrine:query:sql 'select current_database()' | sed -n '3p'
php bin/console --env=test doctrine:query:sql 'select current_database()' | sed -n '3p'; echo
echo "=== ROUTES (auth) ==="
php bin/console debug:router --show-controllers | egrep '/v1/auth|/healthz|/v1/catalogue' || true; echo
echo "=== SECURITY RULES (access_control) ==="
awk '/access_control:/,0{print}' config/packages/security.yaml; echo
echo "=== SECURITY CLASSES ==="
ls -1 src/Security | sort; echo
echo "=== AUTH CLASSES ==="
grep -R --line-number -E 'class TokenIssuer|class TokenRotator|class TokenRevoker|class AuthController' src || true; echo
echo "=== MIGRATIONS ==="
php bin/console doctrine:migrations:list | tail -n +1; echo
echo "=== QA SHORT ==="
( composer run cs:check   >/dev/null 2>&1 && echo "CS=OK"      ) || echo "CS=KO"
( vendor/bin/phpstan analyse --no-progress >/dev/null 2>&1 && echo "PHPStan=OK" ) || echo "PHPStan=KO"
( ./bin/phpunit  >/dev/null 2>&1 && echo "PHPUnit=OK" ) || echo "PHPUnit=KO"
