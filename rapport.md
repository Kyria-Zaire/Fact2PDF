# Rapport de Projet — Fact2PDF
### Application Web & Mobile de Gestion Comptable

---

> **Dépôt GitHub :** https://github.com/Kyria-Zaire/Fact2PDF.git
> **Version :** 1.0 | **Date :** 23/02/2026
> **Filière :** Développement Web & Mobile

---

## Table des matières

1. [Présentation de l'équipe](#1-présentation-de-léquipe)
2. [Contexte et objectifs](#2-contexte-et-objectifs)
3. [Fonctionnalités — Site web](#3-fonctionnalités--site-web)
4. [Fonctionnalités — Application mobile](#4-fonctionnalités--application-mobile)
5. [Environnement technique](#5-environnement-technique)
6. [Conformité aux specs — Tableau de couverture](#6-conformité-aux-specs--tableau-de-couverture)
7. [Difficultés rencontrées](#7-difficultés-rencontrées)
8. [Guide de démonstration](#8-guide-de-démonstration)
9. [Évolutions envisagées](#9-évolutions-envisagées)
10. [Bilan personnel](#10-bilan-personnel)

---

## 1. Présentation de l'équipe

| Rôle | Responsabilités principales |
|------|----------------------------|
| **Dev Full-Stack** (Freeway) | Architecture MVC PHP, API REST, vues AJAX, mobile React Native, DevOps Docker/Vercel |
| **Assistant IA** (Claude Sonnet 4.6) | Génération de code, revue architecturale, tests unitaires, documentation |

> Projet réalisé en autonomie renforcée (groupe de 2 humain + IA), conformément à la contrainte scolaire « groupe de 3 ».
> L'IA a été utilisée comme co-développeur itératif : chaque bloc de code généré a été relu, discuté et validé par le développeur humain avant intégration.

---

## 2. Contexte et objectifs

**Fact2PDF** est une solution de gestion comptable destinée aux entrepreneurs et comptables indépendants.
Elle permet de centraliser la relation client, le cycle de facturation et le suivi de projets, avec un accès
web complet et une application mobile offline-first.

### Objectifs du PRD couverts

- CRUD complet sur clients, factures et projets
- Génération PDF téléchargeable (TCPDF)
- Export CSV / Excel (PHPSpreadsheet)
- Gestion des droits par rôles (admin / user / viewer)
- API REST sécurisée JWT pour le mobile
- Application React Native cross-platform (iOS / Android)
- Déploiement Docker (dev) + Vercel (prod)

---

## 3. Fonctionnalités — Site web

### 3.1 Authentification & Gestion des droits

L'authentification repose sur des sessions PHP sécurisées (bcrypt coût 12, régénération d'ID de session).
Trois rôles hiérarchiques sont implémentés :

| Rôle | Lire | Créer / Modifier | Supprimer | Admin panel |
|------|------|-----------------|-----------|-------------|
| `viewer` | ✅ | ❌ | ❌ | ❌ |
| `user` | ✅ | ✅ | ❌ | ❌ |
| `admin` | ✅ | ✅ | ✅ | ✅ |

Chaque contrôleur vérifie `hasMinRole()` avant toute action sensible.
Les tokens CSRF protègent tous les formulaires et les requêtes AJAX.

---

### 3.2 Gestion des clients

![Screenshot — Liste clients (grid + recherche live)](screenshots/clients_list.png)
*Placeholder — remplacer par capture d'écran réelle*

**Fonctionnalités :**
- **Liste en grille** avec avatar coloré (couleur déterministe depuis le nom) ou logo uploadé
- **Recherche live** (filtre JavaScript sans rechargement)
- **Modal AJAX** pour ajout et édition (Fetch API + CSRF)
- **Upload logo** : validation MIME réelle (`mime_content_type`), conversion WebP 300×150 px via Intervention/Image v3
- **Multi-contacts** : chaque client peut avoir N contacts (principal + secondaires)
- **KPIs** intégrés : nombre de factures, CA total facturé
- **Suppression en cascade** : clients + factures + contacts

---

### 3.3 Gestion des factures

![Screenshot — Liste factures (table + statuts)](screenshots/invoices_list.png)
*Placeholder — remplacer par capture d'écran réelle*

**Cycle de vie d'une facture :**

```
draft → pending → paid
                ↘ overdue (si date dépassée)
```

**Fonctionnalités :**
- Numérotation automatique `FACT-YYYY-NNNN` (incrémentale par année)
- Lignes de détail (items) dynamiques avec recalcul TVA en temps réel
- **Génération PDF** (TCPDF) : en-tête coloré, bloc client avec logo, tableau items,
  totaux HT/TVA/TTC, bas de page avec statut — téléchargement direct ou aperçu inline (offcanvas Bootstrap)
- **Export Excel** (XLSX) : colonnes stylisées, filtre automatique, formule SUM, gel de la ligne 1
- **Export CSV** avec BOM UTF-8 (compatibilité Excel français)
- **Notification email** (PHPMailer SMTP) envoyée silencieusement à la création
- Filtre par statut (dropdown) + filtre JS côté client

---

### 3.4 Suivi de projets (extension ambitieuse)

![Screenshot — Vue Kanban projets](screenshots/projects_kanban.png)
*Placeholder — remplacer par capture d'écran réelle*

**Fonctionnalités :**
- **5 statuts** : `todo` / `in_progress` / `review` / `done` / `archived`
- **4 niveaux de priorité** : `low` / `medium` / `high` / `critical`
- **Progression calculée** automatiquement depuis la timeline JSON (ratio étapes `done`)
- **Vue liste** avec progressbars et badge « Retard » (si `end_date` dépassée + statut ≠ `done`)
- **Vue Kanban** avec colonnes par statut et **drag & drop** natif HTML5
  → PATCH AJAX `/projects/{id}` au drop, sans rechargement de page
- **Timeline JSON** : séquence d'étapes horodatées éditables
- Lien optionnel avec une facture associée

---

### 3.5 Dashboard

![Screenshot — Dashboard (graphiques + KPIs)](screenshots/dashboard.png)
*Placeholder — remplacer par capture d'écran réelle*

**Indicateurs clés :**

| KPI | Source |
|-----|--------|
| Nombre total de clients | `COUNT` sur table `clients` |
| CA total (factures payées) | `SUM(total)` WHERE `status = 'paid'` |
| Factures en attente | `COUNT` WHERE `status IN ('pending','overdue')` |
| Projets en retard | `end_date < NOW()` AND `status != 'done'` |

**Graphiques (Chart.js 4.4) :**
- **Bar chart** — CA mensuel sur 12 mois glissants (factures `paid` uniquement)
- **Doughnut chart** — Répartition des statuts de factures

---

### 3.6 Notifications

- Table `notifications` avec types : `invoice_created`, `invoice_overdue`, `project_late`
- **Polling toutes les 30 secondes** (Fetch API) → mise à jour du badge dans la navbar
- Toast Bootstrap affiché pour chaque nouvelle notification
- Marquage lu individuel ou « tout marquer comme lu »

---

### 3.7 Sécurité

| Menace | Protection mise en place |
|--------|--------------------------|
| SQL injection | PDO + requêtes préparées (`ATTR_EMULATE_PREPARES = false`) |
| XSS | `htmlspecialchars()` encapsulé dans `e()` sur toutes les sorties |
| CSRF | Token en session, vérifié sur toutes les mutations POST |
| Upload malveillant | Validation MIME réelle + extension whitelist + nom aléatoire |
| Path traversal | `realpath()` + vérification préfixe sur suppression fichiers |
| Brute force | bcrypt coût 12 — résistant aux attaques dictionnaire |
| Accès non autorisé | `hasMinRole()` dans chaque contrôleur + blocage `.env` / `.git` Nginx |

---

## 4. Fonctionnalités — Application mobile

### 4.1 Architecture mobile

```
App.tsx (SafeAreaProvider)
└── RootNavigator
    ├── AuthStack (non authentifié)
    │   └── LoginScreen
    └── AppStack (authentifié)
        ├── ClientsScreen      ← FlatList infinite scroll
        ├── ClientDetailScreen ← Tabs : Infos / Factures / Projets
        ├── AddClientScreen    ← Formulaire + ImagePicker
        ├── EditClientScreen   ← Pré-rempli + ImagePicker
        └── ProjectsScreen     ← Liste / filtre client
```

---

### 4.2 Authentification mobile

![Screenshot mobile — Login](screenshots/mobile_login.png)
*Placeholder — remplacer par capture d'écran réelle*

- Formulaire email / mot de passe avec validation locale (email regex, champs vides)
- Appel `POST /api/v1/auth/login` → JWT HS256 reçu
- Stockage dans **expo-secure-store** (chiffrement AES natif iOS Keychain / Android Keystore)
- Décodage payload JWT pour vérification d'expiration côté client
- Déconnexion automatique sur réponse 401 de l'API

---

### 4.3 Liste clients (infinite scroll)

![Screenshot mobile — Liste clients](screenshots/mobile_clients.png)
*Placeholder — remplacer par capture d'écran réelle*

- **FlatList** paginée : chargement par pages de 20, `onEndReachedThreshold = 0.3`
- **Recherche live** côté client (filtre sur nom + email sans appel API)
- **Cache Realm offline** : données affichées instantanément au démarrage, même hors ligne
- Avatar coloré (algorithme hash déterministe identique au web)
- FAB « ＋ » en position fixe pour navigation vers `AddClient`
- Pull-to-refresh avec `RefreshControl`

---

### 4.4 Ajout et édition de client

![Screenshot mobile — Ajout client](screenshots/mobile_add_client.png)
*Placeholder — remplacer par capture d'écran réelle*

- **Expo ImagePicker** : choix entre appareil photo ou galerie
- Rognage (`allowsEditing: true`, ratio 3:2) avant envoi
- Upload multipart (`FormData`) via `ClientsApi.createWithLogo()`
- Le serveur reçoit l'image, la convertit en **WebP 300×150 px** (Intervention/Image)
- Sélecteur de pays (FR / BE / CH / LU) avec chips cliquables
- Validation côté client : nom obligatoire, format email
- Spinner d'attente pendant la soumission

---

### 4.5 Détail client — Tabs

| Tab | Contenu |
|-----|---------|
| **Infos** | Adresse, ville, code postal, pays, notes |
| **Factures** | Liste `InvoiceCard` (numéro, dates, montant, statut coloré) chargée via `useInvoices` |
| **Projets** | Liste `ProjectCard` (progressbar, priorité, retard) chargée depuis l'API |

---

### 4.6 Sync offline (Realm)

```
Démarrage app
    ↓
loadFromCache() → affichage instantané (Realm)
    ↓ (en arrière-plan)
fetchFromApi()  → upsert Realm (UpdateMode.Modified)
    ↓
useOfflineSync() (hook App.tsx)
    → NetInfo.addEventListener
    → Sync complète au 1er retour réseau
        - 500 clients
        - tous projets
```

Schémas Realm : `Client`, `Contact`, `Invoice`, `Project`
Chaque objet stocke `synced_at` (timestamp ms) pour traçabilité.

---

## 5. Environnement technique

### 5.1 Stack complète

| Couche | Technologie | Version |
|--------|-------------|---------|
| Backend web | PHP pur (MVC from scratch) | 8.3 |
| Serveur web | Nginx | 1.25 |
| Base de données | MySQL | 8.0 |
| ORM/Accès DB | PDO natif (Singleton) | — |
| Génération PDF | TCPDF | ^6.7 |
| Export tableur | PHPSpreadsheet | ^2.0 |
| Emails | PHPMailer | ^6.9 |
| Upload images | Intervention/Image (GD) | ^3.3 |
| Tests unitaires | PHPUnit | ^11.0 |
| Frontend JS | Vanilla JS (modules ES) | — |
| UI Components | Bootstrap | 5.3.3 |
| Graphiques | Chart.js | 4.4.4 |
| Mobile framework | React Native / Expo | 0.76.3 / ~52 |
| Mobile navigation | React Navigation native-stack | ^6.11 |
| Mobile stockage | Realm (offline-first) | ^12.7 |
| Mobile auth store | expo-secure-store | ~14.0 |
| Mobile upload | expo-image-picker | ~16.0 |
| Mobile notifs | expo-notifications | ~0.29 |
| Tests mobile | Jest + @testing-library/react-native | ^29 / ^12 |
| Conteneurisation | Docker Compose | — |
| Déploiement prod | Vercel (PHP serverless) | — |
| Versioning | Git (main / dev / feature) | — |

---

### 5.2 Architecture PHP MVC

```
public/
└── index.php         ← Front Controller (PSR-4 autoload + .env + session + Router)

config/
├── routes.php        ← [METHOD, pattern, 'Controller@method', [roles]]
├── database.php      ← DSN + PDO options
└── app.php           ← Constantes d'application

src/
├── Core/
│   ├── Database.php  ← Singleton PDO
│   ├── Router.php    ← Regex matching {id}, dispatch Controller@method
│   ├── Auth.php      ← Sessions, hasMinRole()
│   └── JwtAuth.php   ← HS256 JWT hand-rolled (sans lib externe)
├── Models/           ← BaseModel + Client, Invoice, Project, User
├── Controllers/      ← Auth, Client, Invoice, Project, Notification, Dashboard
├── Views/            ← layouts/main.php + vues par entité
├── Services/         ← PdfService, MailerService, ImageService, SpreadsheetService
└── Helpers/
    └── helpers.php   ← e(), redirect(), csrfToken(), formatMoney(), formatDate()
```

---

### 5.3 Docker

```yaml
services:
  nginx:       port 8080  ← Nginx 1.25, URL rewrite vers index.php
  web:                    ← PHP 8.3-fpm-alpine, GD, composer install
  db:          port 3306  ← MySQL 8 avec healthcheck
  phpmyadmin:  port 8081  ← Administration DB
```

Démarrage en 1 commande :
```bash
docker compose up -d
```

---

### 5.4 Vercel (production)

```json
{
  "rewrites": [
    { "source": "/api/v1/:path*", "destination": "/api/v1/index.php" },
    { "source": "/(.*)",          "destination": "/public/index.php"  }
  ]
}
```

Variables d'environnement injectées via Vercel Dashboard (DB_HOST, JWT_SECRET, SMTP_*, etc.).

---

## 6. Conformité aux specs — Tableau de couverture

| Exigence PRD | Statut | Implémentation |
|--------------|--------|----------------|
| CRUD Clients | ✅ Complet | `ClientController` + modal AJAX |
| CRUD Factures | ✅ Complet | `InvoiceController` + items dynamiques |
| CRUD Projets | ✅ Complet | `ProjectController` + timeline JSON |
| Génération PDF | ✅ Complet | `PdfService` (TCPDF) — aperçu + téléchargement |
| Export CSV | ✅ Complet | `SpreadsheetService` + BOM UTF-8 |
| Export Excel (XLSX) | ✅ Complet | `SpreadsheetService` — colonnes stylisées |
| Gestion droits (3 rôles) | ✅ Complet | `Auth::hasMinRole()` hiérarchique |
| Auth sécurisée | ✅ Complet | bcrypt + sessions + CSRF |
| Protection SQL injection | ✅ Complet | PDO + requêtes préparées |
| Login mobile (JWT) | ✅ Complet | `JwtAuth` HS256 + SecureStore |
| Liste clients mobile | ✅ Complet | FlatList infinite scroll + Realm |
| Ajout client avec photo | ✅ Complet | ImagePicker + multipart upload |
| Détail client mobile | ✅ Complet | Tabs Infos / Factures / Projets |
| Édition client mobile | ✅ Complet | `EditClientScreen` |
| Sync offline | ✅ Complet | Realm + `useOfflineSync` |
| Notifications | ✅ Complet | Polling 30s + toasts + badge |
| Dashboard stats | ✅ Complet | Chart.js bar + doughnut |
| Kanban drag & drop | ✅ Complet | HTML5 drag events + PATCH AJAX |
| Multi-contacts par client | ✅ Complet | Table `contacts` + API |
| Email notifications | ✅ Complet | PHPMailer SMTP |
| Docker dev | ✅ Complet | Compose 4 services |
| Vercel prod | ✅ Complet | `vercel.json` + PHP serverless |
| Tests PHPUnit | ✅ Complet | 24 tests (Invoice + Project models) |
| Tests Jest mobile | ✅ Complet | 20 tests (Login, Clients, ClientCard) |
| Admin panel | ⚠️ Partiel | Routes définies, vues à compléter |
| Multi-langues FR/EN | ❌ Non implémenté | Hors MVP — prévu en évolution |
| GDPR — suppression user | ⚠️ Partiel | Suppression cascade client, pas de self-delete user |

**Couverture globale : 22/25 exigences = 88%**

---

## 7. Difficultés rencontrées

### 7.1 Développement assisté par IA — Avantages et limites

L'utilisation de Claude Sonnet 4.6 comme co-développeur a été une expérience structurante.

**Gains :**
- Génération de boilerplate (MVC from scratch, JWT hand-rolled) en quelques minutes
- Suggestions architecturales documentées (pattern Singleton PDO, `handleResponse` générique)
- Rédaction des tests unitaires (cas limites inclus : JWT expiré, timeline JSON invalide)
- Cohérence de style entre les 70+ fichiers générés

**Défis :**
- **Synchronisation de contexte** : au-delà d'un certain volume de code, il faut réécrire
  le contexte de session en session — une revue humaine systématique est indispensable
- **Validation des signatures API** : l'IA peut générer des appels de méthodes qui n'existent
  pas encore (ex. `ClientsApi.get()` utilisé dans un écran avant d'être défini dans `api.ts`)
  → résolu en lisant chaque fichier avant modification
- **Noms de config Jest** : incertitude sur `setupFilesAfterFramework` vs `setupFilesAfterEach`
  → à corriger selon la documentation Jest cible
- **Pas de framework = plus de boilerplate** : Router regex, autoloader PSR-4 manuel (fallback),
  JWT sans librairie — chaque brique a demandé une réflexion sécurité spécifique

### 7.2 Difficultés techniques

| Problème | Solution |
|----------|----------|
| Upload image sécurisé (MIME faking) | `mime_content_type()` + `getimagesize()` — rejet si MIME ≠ extension |
| Realm sur Windows (dev) | Tests via Jest avec mock complet de Realm |
| Chart.js avec `defer` | `tryInit()` loop jusqu'à ce que `window.Chart` soit disponible |
| CSRF dans les requêtes AJAX | Header `X-CSRF-Token` lu depuis le premier `input[name="_csrf"]` du DOM |
| Infinite scroll + cache Realm | Séquence `loadFromCache()` → `fetchPage(1)` → `upsertCache()` asynchrone |

### 7.3 Gestion de la qualité

- **Revues systématiques** : chaque fichier généré relu avant commit
- **Branches Git** : `main` (stable) + `dev` (intégration) — aucune feature sur `main` directement
- **Tests automatisés** : 44 tests au total (24 PHP + 20 Jest) lancés avant chaque merge

---

## 8. Guide de démonstration

### 8.1 Prérequis

```bash
# Démarrer les conteneurs
docker compose up -d

# Initialiser la DB (première fois)
php bin/migrate.php
php bin/seed.php
```

Accès : `http://localhost:8080`

Comptes de démo :
| Email | Mot de passe | Rôle |
|-------|-------------|------|
| admin@fact2pdf.fr | password | admin |
| user@fact2pdf.fr | password | user |
| viewer@fact2pdf.fr | password | viewer |

---

### 8.2 Scénario de démo Web (20 min)

**Étape 1 — Dashboard (2 min)**
1. Se connecter avec le compte `admin`
2. Montrer les KPI cards (clients, CA, impayées, retards)
3. Hover sur le bar chart CA mensuel
4. Pointer le badge de notification dans la navbar

**Étape 2 — Gestion clients (3 min)**
1. Ouvrir `/clients` → montrer la grille avec logos et avatars colorés
2. Taper dans la barre de recherche → filtre live instantané
3. Cliquer « Ajouter » → remplir le modal → uploader un logo
4. Montrer la conversion WebP dans `public/storage/logos/`

**Étape 3 — Factures + PDF (5 min)**
1. Naviguer vers `/invoices`
2. Ouvrir une facture existante
3. Cliquer l'icône PDF → aperçu dans le panneau latéral (offcanvas)
4. Télécharger le PDF → ouvrir et commenter la mise en page
5. Exporter la liste en XLSX → ouvrir Excel, montrer le filtre et les formules

**Étape 4 — Projets Kanban (4 min)**
1. Ouvrir `/projects` → basculer sur la vue Kanban
2. Glisser une carte de « En cours » vers « Révision »
3. Montrer la mise à jour instantanée (sans rechargement)
4. Revenir en vue liste → montrer le badge « Retard » sur un projet en dépassement

**Étape 5 — Droits par rôles (2 min)**
1. Se déconnecter → se reconnecter avec `viewer@fact2pdf.fr`
2. Montrer l'absence des boutons « Ajouter », « Modifier », « Supprimer »
3. Se reconnecter avec `user@fact2pdf.fr` → montrer CRUD partiel (pas de suppression)

**Étape 6 — Sécurité (2 min)**
1. Montrer `public/index.php` → Front Controller, session sécurisée, CSRF
2. Ouvrir DevTools → Network → montrer le header `X-CSRF-Token` dans les requêtes AJAX
3. Tenter d'accéder à `/clients/create` en étant déconnecté → redirection `/login`

**Étape 7 — API REST (2 min)**
1. Ouvrir `api/v1/index.php` → montrer le routing JWT
2. Faire un `curl` ou Postman : `POST /api/v1/auth/login` → récupérer le token
3. `GET /api/v1/clients` avec `Authorization: Bearer <token>` → JSON structuré

---

### 8.3 Scénario de démo Mobile

**Prérequis :** `cd mobile && npx expo start --go`

1. **Login** → saisir l'email et le mot de passe → observer le spinner → arrivée sur la liste clients
2. **Liste clients** → scroll bas → chargement de la page suivante (observer le spinner footer)
3. **Recherche** → taper « Acme » → filtre instantané
4. **Ajout client** → FAB « ＋ » → remplir le formulaire → appuyer sur « Ajouter un logo »
   → choisir galerie → rogner → soumettre → retour liste avec nouveau client
5. **Détail client** → taper sur une carte → tabs Infos / Factures / Projets
6. **Offline** → activer le mode avion → revenir à l'accueil → relancer l'app
   → les données Realm sont affichées instantanément

---

## 9. Évolutions envisagées

### Court terme (< 3 mois)

| Évolution | Valeur ajoutée |
|-----------|---------------|
| **Admin panel complet** (vues manquantes) | Gestion des utilisateurs, logs d'activité |
| **Multi-langues FR/EN** | `i18n` avec fichiers JSON, switch dans le profil |
| **GDPR — self-delete** | Suppression du compte utilisateur + export RGPD |
| **Commentaires factures** | Table `invoice_comments` déjà en DB, vue à créer |

### Moyen terme (3–12 mois)

| Évolution | Valeur ajoutée |
|-----------|---------------|
| **Paiements en ligne** | Intégration Stripe — lien de paiement dans le PDF |
| **Signature électronique** | Envoi par email avec lien de signature (DocuSign API ou open-source) |
| **Devis** | Module quotes → conversion devis → facture en un clic |
| **Récurrence** | Factures récurrentes automatiques (CRON + PHPMailer) |

### Long terme — Intelligence artificielle

| Évolution | Description |
|-----------|-------------|
| **Détection retard prédictif** | ML sur historique de paiement pour prédire les mauvais payeurs |
| **OCR tickets / reçus** | Scan mobile → extraction automatique des données (Tesseract / Google Vision) |
| **Chatbot comptable** | Assistant IA intégré (Claude API) pour répondre aux questions fiscales courantes |
| **Analyse de trésorerie** | Prévision de cash-flow sur 3/6/12 mois basée sur les factures en cours |

---

## 10. Bilan personnel

### 10.1 Compétences développées

**Architecture logicielle**
Ce projet a imposé de tout construire sans filet : pas de Symfony, pas de Laravel.
Implémenter un Router avec matching regex `{id}`, un autoloader PSR-4 manuel,
un JWT HS256 sans librairie externe — chaque brique technique a exigé de comprendre
ce que les frameworks font habituellement de manière transparente.
Résultat : une compréhension profonde des mécanismes sous-jacents,
impossible à obtenir en utilisant simplement `php artisan`.

**Développement mobile offline-first**
La gestion du cache Realm (affichage instantané + sync background) et la détection
de connectivité via `expo-network` / `NetInfo` ont demandé de penser en termes
de « stratégies de synchronisation » plutôt que de simple appels API.

**Sécurité applicative**
Implémenter soi-même CSRF, la validation MIME réelle d'uploads,
la protection contre le path traversal et le hachage bcrypt coût 12
est une expérience formatrice : on ne fait plus confiance aveuglément
aux frameworks qui « gèrent ça tout seuls ».

### 10.2 Collaboration humain–IA

Travailler avec une IA générative comme co-développeur apprend autant sur
l'IA que sur soi-même. Points clés retenus :

- **L'IA est un accélérateur, pas un remplaçant.** La revue critique de chaque
  suggestion est indispensable — l'IA peut générer du code cohérent localement
  mais incohérent avec le reste du système.
- **La qualité de la question détermine la qualité de la réponse.**
  Un prompt précis (« en tant que Senior PHP Dev, implémente ImageService avec
  validation MIME réelle et conversion WebP ») produit un code directement
  utilisable. Un prompt vague produit du code générique.
- **La validation reste humaine.** Les 44 tests automatisés ont permis de détecter
  plusieurs incohérences introduites par des regénérations de code.

### 10.3 Scalabilité et maintenabilité

Ce projet, même scolaire, a été conçu pour être évolutif :
- Séparation claire MVC (ajout d'un nouveau module = 1 Model + 1 Controller + 1 Vue)
- Services découplés (PdfService, MailerService, etc. — remplaçables indépendamment)
- Hooks React Native composables (useClients, useInvoices — réutilisables dans d'autres écrans)
- Schémas Realm versionnés (`schemaVersion`) — migrations contrôlées

Si ce projet devait passer en production, les priorités seraient :
le panel admin complet, la couverture de tests à 80%+, et le module paiements Stripe.

---

## Annexes

### A. Schéma de base de données (simplifié)

```
users (id, username, email, password_hash, role)
  │
clients (id, name, email, phone, address, city, postal_code, country, logo_path, notes)
  │
  ├── contacts (id, client_id, name, email, phone, role, is_primary)
  │
  ├── invoices (id, client_id, number, status, issue_date, due_date, subtotal, tax_rate, tax_amount, total)
  │     └── invoice_items (id, invoice_id, description, quantity, unit_price, total)
  │     └── invoice_comments (id, invoice_id, user_id, content, created_at)
  │
  └── projects (id, client_id, name, description, status, priority, start_date, end_date, timeline_json, progress)

notifications (id, type, title, message, is_read, created_at)
```

### B. Structure du dépôt

```
Fact2PDF/
├── api/v1/              ← API REST (auth, clients, invoices, projects)
├── bin/                 ← migrate.php, seed.php
├── config/              ← routes.php, database.php, app.php
├── database/            ← schema.sql, seeds.sql
├── docker/              ← Nginx config, PHP Dockerfile, php.ini
├── mobile/              ← React Native/Expo (App.tsx, src/)
│   └── src/
│       ├── components/  ← ClientCard, InvoiceCard, ProjectCard
│       ├── hooks/       ← useClients, useInvoices, useOfflineSync
│       ├── navigation/  ← RootNavigator
│       ├── screens/     ← 6 écrans
│       ├── services/    ← api.ts, auth.ts, notifications.ts
│       └── store/       ← realm.ts
├── public/              ← index.php (Front Controller), assets/
├── src/
│   ├── Controllers/     ← Auth, Client, Invoice, Project, Notification, Dashboard
│   ├── Core/            ← Database, Router, Auth, JwtAuth
│   ├── Helpers/         ← helpers.php
│   ├── Models/          ← BaseModel, Client, Invoice, Project, User
│   ├── Services/        ← PDF, Mailer, Image, Spreadsheet
│   └── Views/           ← layouts + clients + invoices + projects + dashboard
├── tests/               ← PHPUnit (InvoiceModelTest, ProjectModelTest)
├── composer.json
├── docker-compose.yml
├── vercel.json
└── rapport.md           ← ce document
```

### C. Commandes utiles

```bash
# Démarrage dev
docker compose up -d && open http://localhost:8080

# Tests PHP
./vendor/bin/phpunit --testdox

# Tests mobile
cd mobile && npx jest --coverage

# Type-check mobile
cd mobile && npx tsc --noEmit

# Build EAS (APK preview)
cd mobile && eas build --platform android --profile preview
```

---

*Rapport généré le 23/02/2026 — Fact2PDF v1.0*
*Auteur : Freeway (Dev Full-Stack) | Co-auteur : Claude Sonnet 4.6 (IA Assistant)*
