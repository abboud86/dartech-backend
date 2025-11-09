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


## État actuel (avant P3-04)

### Endpoints Booking couverts par les tests

- `POST /api/bookings`
  - 401 si non authentifié (tests dans `BookingCrudTest`)
  - 201 + payload booking minimal si authentifié et payload valide
- `GET /api/bookings/{id}`
  - 400 si identifiant invalide
  - 404 si booking inexistant
  - 403 si booking n'appartient pas à l'utilisateur courant
  - 200 si booking appartient à l'utilisateur courant
- `PATCH /api/bookings/{id}`
  - 400 / 422 pour validations métier (dates passées, montant hors plage…)
  - 403 si booking n'appartient pas à l'utilisateur courant
  - 200 si mise à jour autorisée
- `POST /api/bookings/{id}/transition`
  - 401 si non authentifié
  - 400 si transition manquante ou booking_id invalide
  - 404 si booking inexistant
  - 4xx métier (selon les règles de workflow) via subscriber de workflow
- Endpoints `/v1/me` et auth (`/v1/auth/login`, etc.) :
  - Couvert par `MeEndpointTest`, `MeEndpointExtraTest` et `SecurityTest`.

### Tests existants liés à la sécurité Booking

- `tests/Functional/Api/BookingCrudTest.php`
  - Auth, 400/404, 403 ownership, 422 métier.
- `tests/Functional/Api/BookingTransitionApiTest.php`
  - Auth, 400 invalid id/missing transition, 404 not found.
- `tests/Functional/Api/BookingGuardsTest.php`
  - Cas métier de transition interdite & validations liées au workflow Booking.


## Règles d’accès cibles pour Booking (P3-04)

### Principes généraux

- Toute l’API Booking (`/api/bookings`, `/api/bookings/{id}`, `/api/bookings/{id}/transition`, etc.)
  doit être **réservée aux utilisateurs authentifiés**.
- `401 Unauthorized` :
  - retourné quand l’appelant n’est pas authentifié (absence/invalidité du token).
- `403 Forbidden` :
  - retourné quand l’appelant est authentifié mais n’a **pas le droit** d’agir sur la ressource
    (ex. booking qui appartient à un autre utilisateur).
- `404 Not Found` :
  - réservé au cas où la ressource n’existe pas (booking inexistant),
    pas à un problème d’autorisations.

### Matrice cible pour les endpoints Booking

- `POST /api/bookings`
  - 401 si non authentifié.
  - 201 si création OK pour l’utilisateur courant.
- `GET /api/bookings/{id}`
  - 400 si identifiant invalide.
  - 404 si booking inexistant.
  - 403 si booking appartient à un autre utilisateur.
  - 200 si booking appartient à l’utilisateur courant.
- `PATCH /api/bookings/{id}`
  - 400 / 422 si payload invalide ou violation métier.
  - 403 si booking appartient à un autre utilisateur.
  - 200 si mise à jour autorisée (ownership respectée).
- `POST /api/bookings/{id}/transition`
  - 401 si non authentifié.
  - 400 si identifiant invalide ou transition manquante.
  - 404 si booking inexistant.
  - 4xx (403 ou 422/409 selon le cas) si la transition est interdite par les règles métier
    (guards du workflow Booking).
  - 200 si transition autorisée pour ce booking et cet utilisateur.

