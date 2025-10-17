# Public API — Artisan detail

`GET /v1/artisans/{publicId}`

## Response (200)
```json
{
  "slug": "01HXYZ...",
  "displayName": "Ali B.",
  "city": "Alger-Centre",
  "bio": "—",
  "avatarUrl": null,
  "verified": true,
  "services": [
    { "id": "01HY...", "name": "Plomberie", "slug": "plomberie-express", "price": 2500 }
  ],
  "portfolioPreview": [],            // ≤ 4 URLs (actuellement vide faute de source média)
  "updatedAt": "2025-10-13T12:34:56+00:00"
Notes
portfolioPreview: tableau d’URLs publiques (0→4). La source média sera introduite dans une future sous-tâche.

Cache: Cache-Control: public, max-age=300, s-maxage=600.

Accès public (KYC=VERIFIED requis pour l’artisan).

Errors
404: {"error":"artisan_not_found"}
