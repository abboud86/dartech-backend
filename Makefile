.PHONY: install qa cs fix stan test

install:
	composer install

qa: cs stan

cs:
	composer cs:check

fix:
	composer cs:fix

stan:
	composer stan

test:
	php bin/phpunit --no-coverage
