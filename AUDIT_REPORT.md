# Audit Fact2PDF — Sécurité, Clean Code, Conformité

**Rôle :** Senior Security Engineer & Clean Code Architect  
**Date :** 2026  
**Périmètre :** Repo complet (PHP pur, MVC, API REST, Docker, Vercel)

---

## 1. Synthèse exécutive

Le projet Fact2PDF est globalement bien structuré (MVC, PDO préparé, CSRF sur les formulaires, JWT pour l’API). Plusieurs points de sécurité et de robustesse doivent être corrigés pour un déploiement production-ready et une conformité stricte aux consignes (PHP pur sans framework, bonnes pratiques).

---

## 2. Problèmes identifiés par niveau de risque

### Critique

| # | Problème | Fichier(s) | Détail |
|---|----------|------------|--------|
| C1 | **Cookie de session non forcé en Secure derrière reverse proxy** | `Auth.php` | `secure => isset($_SERVER['HTTPS'])` est faux quand Nginx/Vercel mettent HTTPS en amont ; le cookie peut être envoyé en HTTP. |
| C2 | **JWT : décodage Base64 non validé** | `JwtAuth.php` | `base64_decode()` peut retourner `false` ; `json_decode(false)` donne `null` → payload null retourné sans erreur. |
| C3 | **JWT : algorithme non vérifié (algorithm confusion)** | `JwtAuth.php` | Le header `alg` n’est pas contrôlé ; bonne pratique = n’accepter que `HS256`. |

### Haute

| # | Problème | Fichier(s) | Détail |
|---|----------|------------|--------|
| H1 | **Pas de rate limiting sur l’API** | `api/v1/index.php` | Brute-force possible sur `/auth/login` et surcharge des endpoints. |
| H2 | **CORS API en `*` en production** | `api/v1/index.php` | `Access-Control-Allow-Origin: *` autorise tout domaine ; en prod, restreindre aux origines connues. |
| H3 | **Risque de path traversal dans `view()`** | `helpers.php` | Si un contrôleur passait une entrée utilisateur en nom de vue, `../` permettrait une inclusion de fichier. Défense en profondeur : rejeter `..` et `/` dans le nom de vue. |
| H4 | **SQL : `orderBy` non listé en whitelist dans BaseModel** | `BaseModel.php` | `all($orderBy, $dir)` injecte `$orderBy` dans la requête ; si jamais issu de la requête, risque d’injection. Whitelist des colonnes autorisées. |
| H5 | **En-têtes de sécurité absents** | `public/index.php` | Pas de X-Content-Type-Options, X-Frame-Options, Referrer-Policy, etc. |
| H6 | **Pas de redirection HTTPS en production** | `public/index.php` | En prod, forcer HTTPS pour éviter downgrade. |

### Moyenne

| # | Problème | Fichier(s) | Détail |
|---|----------|------------|--------|
| M1 | **Logs : email en clair (GDPR)** | `AuthController.php`, `helpers.php` | `logMessage('info', "Connexion : user #id ({$user['email']})")` ; préférer logger uniquement l’ID. |
| M2 | **Gestion d’erreurs API : message brut en prod** | `api/v1/index.php` | `catch (\Exception $e) { apiError(500, $e->getMessage()); }` peut exposer chemins ou détails internes. |
| M3 | **Session : régénération après login uniquement** | `Auth.php` | Bonne régénération à la connexion ; pas de timeout d’inactivité (optionnel). |
| M4 | **PHPDoc / constantes manquants** | Plusieurs contrôleurs / modèles | PSR-12 et lisibilité : constantes pour rôles, PHPDoc sur méthodes publiques. |
| M5 | **Pas de politique de mot de passe forte** | `User.php`, `AdminController` | `password_hash` avec cost 12 est bien ; pas de contrainte min length/symboles côté app (optionnel). |
| M6 | **SEO : meta description / titre par page** | `layouts/main.php` | Titre dynamique présent ; meta description manquante pour les pages principales. |

---

## 3. Conformité consignes PDF / PRD

- **PHP pur sans framework** : OK (Composer utilisé pour libs autorisées : TCPDF, PHPSpreadsheet, PHPMailer, Intervention/Image).
- **CRUD complet, rôles, export PDF/CSV** : OK.
- **API REST JWT** : OK après corrections JWT (C2, C3).
- **Config serveur (vhost, rewrite)** : OK (Docker Nginx, Vercel routes).
- **Docker + Vercel** : Conservés comme demandé.

---

## 4. Plan de corrections appliquées

Les corrections suivantes sont fournies dans le repo :

1. **Auth.php** : Cookie `secure` basé sur `X-Forwarded-Proto` ou `HTTPS`.
2. **JwtAuth.php** : Validation du décodage Base64 + vérification explicite `alg === HS256`.
3. **RateLimiter.php** (nouveau) : Limitation par IP pour l’API (ex. auth/login).
4. **api/v1/index.php** : Application du rate limiter, CORS configurable via env, erreurs 500 génériques en prod.
5. **helpers.php** : `view()` refuse les noms contenant `..` ou `/`.
6. **BaseModel.php** : `all()` n’accepte que des colonnes listées en whitelist (défaut : `id`).
7. **public/index.php** : En-têtes de sécurité (X-Content-Type-Options, X-Frame-Options, Referrer-Policy) + redirection HTTPS optionnelle en prod.
8. **config/app.php** : Options `cors_origins`, `force_https`, `session.secure`.
9. **AuthController.php** / **helpers.php** : Log de connexion sans email (user_id uniquement).
10. **README_PRODUCTION_DEPLOYMENT.md** : Guide déploiement + checklist.

---

## 5. Bonnes pratiques déjà en place

- Sessions : httponly, samesite=Strict, régénération après login.
- CSRF : token sur tous les formulaires POST, vérification côté serveur.
- Mots de passe : bcrypt (cost 12).
- PDO : prepared statements, `ATTR_EMULATE_PREPARES => false`.
- Uploads : validation MIME réelle, taille, nom aléatoire, path réel pour suppression.
- XSS : helper `e()` utilisé dans les vues pour l’affichage.
- Modèles : whitelist `$fillable` contre le mass assignment.
