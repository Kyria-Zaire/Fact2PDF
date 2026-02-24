# Guide de déploiement production — Fact2PDF

Ce document décrit les étapes et la checklist pour déployer Fact2PDF en **production** de manière sécurisée (audit Senior Security Engineer & Clean Code).

---

## 1. Prérequis

- PHP 8.3+
- MySQL 8+ ou MariaDB
- Composer (dépendances : TCPDF, PHPSpreadsheet, PHPMailer, Intervention/Image)
- Serveur web (Nginx ou Apache) ou plateforme (Vercel avec runtime vercel-php)
- Accès HTTPS (certificat valide)

---

## 2. Configuration serveur

### 2.1 Variables d’environnement (.env)

En production, définir au minimum :

```env
APP_ENV=production
APP_URL=https://votre-domaine.com
APP_SECRET=<clé_secrète_32_caracteres_min>

# Base de données (valeurs réelles)
DB_HOST=...
DB_NAME=...
DB_USER=...
DB_PASS=...

# JWT (API mobile)
JWT_SECRET=<secret_32_caracteres_min>
JWT_EXPIRY=3600

# Sécurité production
FORCE_HTTPS=1
SESSION_SECURE=1
CORS_ORIGINS=https://votre-app-mobile-origin.com
```

- **FORCE_HTTPS=1** : redirige tout le trafic HTTP vers HTTPS (301).
- **SESSION_SECURE=1** : cookie de session envoyé uniquement en HTTPS (et détection X-Forwarded-Proto si derrière proxy).
- **CORS_ORIGINS** : origine(s) autorisée(s) pour l’API (éviter `*` en prod).

### 2.2 Document root

- **Web** : pointer le document root vers le dossier `public/` (accès uniquement à `public/index.php` et `public/assets/`, pas à `src/`, `config/`, etc.).

### 2.3 Nginx (exemple)

```nginx
server {
    listen 443 ssl http2;
    server_name votre-domaine.com;
    root /var/www/fact2pdf/public;

    ssl_certificate     /path/to/fullchain.pem;
    ssl_certificate_key /path/to/privkey.pem;

    location / {
        try_files $uri /index.php?$query_string;
    }
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_param HTTP_X_FORWARDED_PROTO $scheme;
    }
}
```

### 2.4 Vercel

- Utiliser `vercel.json` fourni ; définir les variables d’environnement dans le dashboard Vercel (APP_ENV, FORCE_HTTPS, SESSION_SECURE, CORS_ORIGINS, DB_*, JWT_SECRET, etc.).
- La route par défaut pointe vers `public/index.php` ; les routes `/api/v1/*` vers `api/v1/*.php`.

---

## 3. Sécurité

### 3.1 Déjà en place (après audit)

- Sessions : httponly, samesite=Strict, secure en prod, régénération après login.
- CSRF : token sur tous les formulaires POST.
- Mots de passe : bcrypt (cost 12).
- PDO : prepared statements, pas d’émulation.
- Uploads : validation MIME réelle, taille, nom aléatoire.
- JWT : vérification alg HS256, décodage Base64 validé, expiration.
- Rate limiting API : global + login (voir `src/Core/RateLimiter.php`).
- En-têtes : X-Content-Type-Options, X-Frame-Options, Referrer-Policy.
- CORS API : configurable via `CORS_ORIGINS`.

### 3.2 À faire côté hébergeur

- Activer HTTPS et rediriger HTTP → HTTPS (ou utiliser FORCE_HTTPS=1).
- Ne pas exposer `.env`, `config/`, `src/`, `storage/logs/` (document root = `public/`).
- Limiter les accès admin (IP, VPN) si besoin.

---

## 4. Checklist avant mise en production

- [ ] **APP_ENV=production**
- [ ] **APP_SECRET** et **JWT_SECRET** forts (≥ 32 caractères), uniques, non commités
- [ ] **FORCE_HTTPS=1** et **SESSION_SECURE=1**
- [ ] **CORS_ORIGINS** défini (pas `*`) si l’API est appelée depuis un domaine connu
- [ ] Document root = **public/** uniquement
- [ ] **storage/** et **storage/logs/** hors web, permissions 0750 ou 0755
- [ ] **composer install --no-dev** (pas de dépendances dev en prod)
- [ ] Migrations DB exécutées (**php bin/migrate.php**)
- [ ] Vérifier que **display_errors = 0** (géré par APP_ENV=production)
- [ ] Sauvegardes DB et fichiers uploads planifiées
- [ ] Logs et rétention conformes (RGPD si données personnelles dans les logs)

---

## 5. Déploiement Vercel

Le projet utilise le runtime **vercel-php** (voir `vercel.json`). En serverless, le système de fichiers est en lecture seule sauf `/tmp` ; l’app configure automatiquement les sessions dans `/tmp` lorsque `VERCEL=1` est défini.

Le fichier `vercel.json` définit `installCommand: composer install --no-dev` pour installer les dépendances (TCPDF, PhpSpreadsheet, etc.). Aucune autre commande de build n’est nécessaire.

**Variables d’environnement obligatoires** (à définir dans *Project Settings → Environment Variables* sur Vercel) :

- **APP_ENV** = `production`
- **APP_URL** = `https://fact2-pdf.vercel.app` (ou ton domaine)
- **APP_SECRET** = clé secrète ≥ 32 caractères
- **DB_HOST**, **DB_NAME**, **DB_USER**, **DB_PASS** (base MySQL/MariaDB accessible depuis Internet, ex. PlanetScale, Railway, etc.)
- **JWT_SECRET** = secret JWT ≥ 32 caractères (API mobile)
- **FORCE_HTTPS** = `1` et **SESSION_SECURE** = `1` recommandés

Sans ces variables (notamment DB_* et APP_SECRET), l’application peut renvoyer une erreur **500**. Vérifier aussi les *Runtime Logs* dans le dashboard Vercel en cas d’erreur.

---

## 6. Après déploiement

- Tester la connexion web (login, dashboard).
- Tester l’API (POST /api/v1/auth/login puis une requête authentifiée).
- Vérifier la redirection HTTPS et le cookie de session (Secure, HttpOnly).
- Consulter `AUDIT_REPORT.md` pour la liste des points audités et corrigés.

---

## 7. Références

- **AUDIT_REPORT.md** : rapport d’audit sécurité et clean code.
- **README.md** : installation et commandes du projet.
- **.env.example** : variables d’environnement documentées.
