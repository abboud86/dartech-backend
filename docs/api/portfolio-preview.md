# GET /v1/artisans/{id}/portfolio/preview

Renvoie jusqu’à **4** URLs d’images **publiques** du portfolio d’un artisan, triées du **plus récent** au plus ancien.

- **Paramètres**
  - `id` *(path, integer, required)* : ID numérique de l’`ArtisanProfile`.

- **Réponse 200**
  - `application/json` : `string[]` (0..4), chaque élément est une URL publique (≤ 2048 chars).

- **Réponse 404**
  - `{ "error": "Artisan not found" }`

## Règles métier

- `Media.isPublic = true` uniquement.
- Ordre: `createdAt DESC`.
- Limite: `4` éléments max.
- Propriété `publicUrl`: `varchar(2048)`.

## Tests fonctionnels de référence

- `ArtisanPortfolioPreviewTest::testEmptyPortfolioReturnsEmptyArray`
- `ArtisanPortfolioPreviewTest::testPublicOnlyLimit4OrderDesc`
- `ArtisanPortfolioPreviewTest::testPrivateMediaAreIgnored`
