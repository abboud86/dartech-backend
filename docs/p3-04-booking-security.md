# P3-04 — Booking Security & Access Control

## Objectif

Durcir la sécurité autour du domaine Booking et des endpoints associés.

- Cohérence 401 (non authentifié) / 403 (authentifié mais non autorisé).
- Règles `access_control` claires pour l'API booking.
- Intégration propre avec les authenticators existants (TokenAuthenticator, TestTokenAuthenticator).
- Préparation aux futurs voters métier si nécessaire.

## Livrables

- Configuration `security.yaml` / `access_control` alignée avec le workflow Booking.
- Tests fonctionnels couvrant les scénarios 401/403 critiques.
- Documentation minimale pour les règles d’accès Booking.

