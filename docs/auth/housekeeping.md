# Auth Housekeeping / Hardening

- **Cache-Control**: All JSON responses under `/v1/auth/*` and `/v1/me` are forced to `Cache-Control: no-store` and `Pragma: no-cache`.  
  Reason: tokens and account payloads are sensitive and must not be cached by browsers, proxies, or intermediaries.

- **401 JSON contract**: Authentication failures always return `{"error":"unauthorized"}` with HTTP 401 (entry point + authenticators aligned).

- **/v1/me rate limit**: limiter `me_get` ensures 401/403 happen before 429 (no premature throttling on unauthenticated requests).

- **Test-only auth**: the `X-Test-User` authenticator is hard-gated by `APP_ENABLE_TEST_AUTH=1` and only used in `test` env.

- **Logout All**: `/v1/auth/logout/all` revokes all access/refresh tokens atomically and is idempotent.
