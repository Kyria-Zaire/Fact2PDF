# Product Requirements Document (PRD) - Fact2PDF

## 1. Introduction
### 1.1 Objectif du Document
Ce PRD définit les exigences fonctionnelles et non fonctionnelles pour le projet Fact2PDF, un site web et une application mobile pour la gestion comptable d'entreprise. Il sert de référence pour la conception, le développement, les tests et la présentation.

### 1.2 Portée du Projet
- Site web dynamique pour consultation, interaction et administration de contenus (clients, factures).
- Application mobile connectée pour login, gestion clients et visualisation factures.
- Respect des consignes : Sans framework PHP, CRUD complet, gestion de droits, export PDF/CSV, config serveur (vhost, URL rewrite).
- Extensions ambitieuses : Suivi projets, multi-contacts par entreprise, notifications, dashboard stats.

### 1.3 Public Cible
- Utilisateurs : Entrepreneurs, comptables (rôles : admin, user, viewer).
- Visiteurs : Consultation basique (sans login pour certaines pages publiques ?).
- Mobile : Utilisateurs mobiles pour ajout rapide de clients avec photos.

### 1.4 Hypothèses et Contraintes
- Hypothèses : Accès internet, appareils modernes (browsers récents, iOS/Android 12+).
- Contraintes : Pas de framework PHP (pure PHP), groupe de 3 (nous + IA), présentation 20 min + rapport.
- Budget/Temps : Projet scolaire, focus sur autonomie et ambition.

### 1.5 Définitions et Acronymes
- CRUD : Create, Read, Update, Delete.
- API : Application Programming Interface (pour connexion mobile-web).
- MVP : Minimum Viable Product.

## 2. Exigences Fonctionnelles
### 2.1 Fonctionnalités du Site Web
- **Gestion Clients** : Ajouter/visualiser/éditer/supprimer clients (nom, adresse, logo, contacts multiples).
- **Gestion Factures** : Formulaire pour créer factures (client, items, montant, TVA), générer PDF téléchargeable.
- **Synthèse Factures** : Tableau listant toutes factures, export CSV pour Excel.
- **CRUD Complet** : Sur clients, factures, références (si extension).
- **Gestion Droits** : Rôles (admin : tout ; user : CRUD propre ; viewer : read-only). Auth via login/password.
- **Interactions** : Commentaires sur factures, suivi avancement projets (timeline, status).
- **Admin Panel** : Interface pour gérer contenus, users, logs.

### 2.2 Fonctionnalités de l'App Mobile
- **Login** : Formulaire login (pas de signup).
- **Gestion Clients** : Liste clients, ajout avec photo logo (caméra/galerie), détail client.
- **Visualisation Factures** : Liste factures par client dans détail.
- **Extensions** : Édition client, sync offline.

### 2.3 Intégrations
- API REST pour connexion mobile-web (sécurisée JWT).
- Libs : TCPDF (PDF), PHPSpreadsheet (CSV), PHPMailer (emails), Intervention/Image (photos).

## 3. Exigences Non Fonctionnelles
### 3.1 Performances
- Temps réponse < 2s pour pages/requêtes.
- Scalable : Support 100+ users simultanés (optim DB, caching).

### 3.2 Sécurité
- HTTPS, protection SQL injection (PDO), hash passwords, roles-based access.
- GDPR-compliant : Consentement données, suppression user.

### 3.3 Usabilité
- UI responsive (Bootstrap), accessible (WCAG basics).
- Multi-langues : FR/EN.

### 3.4 Fiabilité
- Backup DB auto, error handling (logs).
- Tests : Unitaires (PHPUnit), manuels.

### 3.5 Infrastructure
- Dev : Docker (conteneurs PHP, MySQL, Nginx).
- Prod : Vercel pour web (adapter PHP serverless), DB externe (PlanetScale ou similar).
- Versioning : Git, branches (main/dev/feature).

## 4. Conception
### 4.1 Architecture
- Backend : PHP MVC from scratch (controllers/models/views).
- Frontend : HTML/JS/CSS.
- Mobile : React Native.
- DB Schema : Tables (users, clients, contacts, factures, projects).

### 4.2 Wireframes/UI
- [Insérer liens Figma ou descriptions].

### 4.3 Flux Utilisateur
- Ex. : Login → Dashboard → Créer Facture → Générer PDF.

## 5. Plan de Projet
- Milestones : Conception, Backend, Frontend, Mobile, Tests, Déploiement.
- Risques : Délais IA, bugs sécurité – mitigation : Reviews CTO.

## 6. Critères d'Acceptation
- MVP : Toutes features core fonctionnelles, démo sans crash.
- Succès : Note jury, potentiel réel (SEO, stores).

## 7. Annexes
- Schema DB.
- Grille évaluation (de l'annexe).
- Bilan perso (à ajouter post-projet).

---
Version : 1.0 | Date : 23/02/2026 | Auteur : Freeway (Dev Full-Stack), (CTO), IA (Assistant). https://github.com/Kyria-Zaire/Fact2PDF.git
