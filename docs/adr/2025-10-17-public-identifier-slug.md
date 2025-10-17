# ADR: Identifiant public artisan — décision minimale (items 1→3)

## (1) Contexte & problème
L’API publique expose aujourd’hui `/v1/artisans/{id}/...` avec un **id interne** (numérique). Problèmes : fuite potentielle d’informations structurelles, identifiant non lisible, non SEO, couplage fort à la DB.

## (2) Options évaluées
- **ID interne (int)** : simple mais non lisible/SEO, révèle la structure, pas idéal pour du public.
- **UUID/ULID** : stable et opaque, mais peu convivial et non SEO.
- **Slug (dérivé de displayName)** : lisible, SEO-friendly, stable si contrainte d’unicité et gestion des collisions.

## (3) Décision
Adopter un **slug public unique** comme identifiant public principal pour `ArtisanProfile`.
## (4) Impacts techniques

- 📌 **Routing** : les endpoints publics `/v1/artisans/{id}/...` doivent accepter `{slug}` à la place de l’id interne pour toutes les routes publiques.
- 🧱 **Entité** : ajout d’un champ `slug` unique et non nul dans `ArtisanProfile` (String, length ≤ 255, unique).
- 🧭 **Migration DB** : nouvelle colonne + index unique, génération initiale depuis `displayName`.
- 🧠 **Résolution interne** : la résolution slug → ArtisanProfile doit être gérée au niveau du ParamConverter ou Repository pour garantir des requêtes propres et performantes.
- 🔐 **Rétrocompatibilité** : l’id interne continuera à exister en interne mais ne sera plus exposé dans les URLs publiques.
- 🌐 **SEO / DX** : les slugs sont lisibles, stables et optimisent les URLs publiques.
## (5) Plan de migration technique

- 🧱 **Schéma** : ajout d’un champ `slug` (string, unique, non nullable) à `ArtisanProfile`.
- ⚙️ **Génération initiale** : slug auto-généré à partir de `display_name` lors de la migration pour les artisans existants.
- 🧪 **Contrainte unique** : ajout d’un index unique sur `slug`.
- 🧰 **Validation** : ajout d’une contrainte `@UniqueEntity(slug)` côté entité.
- 🔁 **Tests** : couverture de la génération, unicité et résolution slug.
- 🚀 **Déploiement** : migration Doctrine générée proprement → exécutée en dev/test avant merge.
