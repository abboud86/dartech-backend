# P3-05 — Booking Observability & Timeline

## Contexte métier

- Marché cible : Algérie, paiement majoritairement en cash.
- Beaucoup d'échanges hors app : WhatsApp, téléphone, messages directs.
- L'app sert à :
  - structurer les bookings,
  - suivre l'historique,
  - garder une trace fiable des étapes clés.

## Objectifs P3-05

- Améliorer l'observabilité autour des bookings :
  - logs structurés (JSON) pour les événements importants.
  - intégration propre avec le stack de logs/monitoring existant (Monolog, Sentry).
- Enrichir la timeline Booking :
  - événements plus détaillés, orientés métier (changement de statut, replanification, montant estimé, canal de communication...).
  - conservation d'un historique lisible côté artisan / ops.

## Livrables attendus

- Événements de timeline Booking enrichis (new entries ou champs supplémentaires).
- Logs JSON cohérents pour les transitions importantes de Booking.
- Tests fonctionnels/minimaux pour garantir que les événements clés sont bien émis.
- Documentation courte sur :
  - quels événements sont loggés,
  - comment les lire/interpréter.

## Contrat d'événements (verrouillé P3-05)

Events (slug) et champs communs:
- event (string, required)
- booking_id (ULID, required)
- actor_user_id (ULID|null)
- from_status (string|null)
- to_status (string|null)
- estimated_amount_old (int|null)
- estimated_amount_new (int|null)
- scheduled_at_old (datetime|null)
- scheduled_at_new (datetime|null)
- communication_channel (string|null)
- note (string|null, court)
- created_at (datetime, required)
- request_id (string, required)
- trace_id (string|null)

Liste:
- booking.created
- booking.updated
- booking.estimate.changed
- booking.scheduled.changed
- booking.channel.set
- booking.transition.requested
- booking.transition.applied
- booking.transition.denied
- validation.failed
- security.denied
