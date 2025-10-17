# Public Artisan Profile API (v0)

## GET /v1/artisans/{slug}
Auth: none (public)  
Cache: `Cache-Control: public, max-age=300, s-maxage=600`

### 200 OK
{
  "slug": "menuiserie-ali",
  "displayName": "Ali B.",
  "city": "Alger",
  "bio": "Menuisier depuis 12 ans...",
  "avatarUrl": "https://cdn.example.com/ali.jpg",
  "verified": true,
  "services": [
    { "id": "01H...", "name": "Pose porte", "slug": "pose-porte", "price": 12000 }
  ],
  "portfolioPreview": [
    { "mediaUrl": "https://cdn.example.com/p1.jpg", "title": "Cuisine sur mesure" }
  ],
  "updatedAt": "2025-10-10T10:15:00Z"
}

### 404 Not Found
{ "error": "artisan_not_found" }

---

## GET /v1/artisans/{slug}/portfolio
Query: `?page=1&limit=12`

### 200 OK
{
  "items": [
    { "mediaUrl": "https://cdn.example.com/p1.jpg", "title": "Cuisine", "createdAt": "2025-08-12T09:10:00Z" }
  ],
  "page": 1,
  "limit": 12,
  "total": 42
}

### 404 Not Found
{ "error": "artisan_not_found" }

### Notes
- Lecture seule, pas de PII (email/téléphone/KYC docs).
- Champs sérialisés via groups `public:artisan.read`.
- Versioning: nouveaux champs autorisés sous le même group.
