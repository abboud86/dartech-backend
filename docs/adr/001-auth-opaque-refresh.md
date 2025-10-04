# ADR-001 — Auth (Access Token opaque + Refresh)

## Contexte
API stateless pour mobile/web. Besoin de révocation server-side, rotation de refresh, throttling des tentatives, et transport Bearer standard.

## Décision
- **Access Token opaque** (aléatoire, TTL court, stocké en DB, validé à chaque requête).
- **Refresh Token** (TTL plus long, rotation à chaque refresh, invalidation de l'ancien).
- Transport via **Authorization: Bearer <token>**.
- Firewall **stateless** avec **access_token handler** (charge l’utilisateur depuis le token).
- **Password hashing** via hasher `auto`.
- **Login throttling** via RateLimiter.

## Alternatives
- JWT signé côté client : plus léger côté lecture mais révocation granulaire plus complexe.
- Session serveur : pas adapté à l’API stateless multi-clients.

## Conséquences
- Lookup DB par requête (coût accepté pour la révocation fine).
- Tables `access_token` et `refresh_token`, rotation/invalidations à gérer.
- Endpoints: `/v1/auth/register`, `/v1/auth/login`, `/v1/auth/token/refresh`, `/v1/auth/logout`, `/v1/me`.

## Sécurité / bonnes pratiques
- Lire le Bearer par l’extracteur par défaut.
- Hasher les mots de passe (`auto`).
- Activer `login_throttling`.
- Prévoir reset-password via bundle officiel plus tard.
