# Config validation — P0-02.2

- `php bin/console lint:yaml config --parse-tags` → OK
- `php bin/console lint:container --env=dev` → OK
- `composer validate --no-check-all --no-check-publish` → OK

Horodatage: $(date -Iseconds)
