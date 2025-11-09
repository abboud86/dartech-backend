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
