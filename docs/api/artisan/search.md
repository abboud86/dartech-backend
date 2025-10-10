# P2-04 — Recherche artisans (read-only) — umbrella v2

Endpoints:
- GET /v1/artisans

Contract (squelette livré en P2-04.1):
- 200: { data: [], meta: { page, per_page, total } }
- 400: { errors: [{ field, message }] } on invalid params

Note: Cette PR remplace #19 (fermée). Suite: P2-04.2+.
