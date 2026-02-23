# Fact2PDF

Système de gestion comptable : clients, factures, export PDF/CSV.
Stack : PHP 8.3 pur (MVC), MySQL, Nginx, Docker — API REST pour app mobile React Native.

---

## Prérequis

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) 24+
- [Git](https://git-scm.com/) 2.40+
- [Node.js](https://nodejs.org/) 20+ (pour l'app mobile uniquement)

---

## Installation (Dev Local)

```bash
# 1. Cloner le repo
git clone <repo-url> Fact2PDF
cd Fact2PDF

# 2. Configurer l'environnement
cp .env.example .env
# Éditer .env avec vos valeurs

# 3. Lancer la stack Docker
docker compose up -d --build

# 4. Initialiser la base de données
docker compose exec web php bin/migrate.php

# 5. (Optionnel) Charger les données de test
docker compose exec web php bin/seed.php
```

### URLs de développement

| Service      | URL                          |
|--------------|------------------------------|
| Application  | http://localhost:8080        |
| API REST     | http://localhost:8080/api/v1 |
| phpMyAdmin   | http://localhost:8081        |
| MySQL        | localhost:3306               |

---

## Structure du Projet

```
Fact2PDF/
├── api/            # Endpoints REST (mobile)
│   └── v1/
├── bin/            # Scripts CLI (migrate, seed)
├── config/         # Configuration (DB, routes, app)
├── database/       # Schema SQL + seeds
├── docker/         # Dockerfile + config Nginx
├── mobile/         # App React Native
├── public/         # Entrée HTTP + assets statiques
│   └── assets/
├── src/            # Code PHP MVC
│   ├── Controllers/
│   ├── Core/       # Router, Database, Auth
│   ├── Helpers/
│   ├── Models/
│   └── Views/
└── storage/        # Uploads, logs, cache (gitignorés)
```

---

## Branches Git

| Branche         | Usage                              |
|-----------------|------------------------------------|
| `main`          | Production stable                  |
| `dev`           | Intégration continue               |
| `feature/*`     | Nouvelles fonctionnalités          |
| `fix/*`         | Corrections de bugs                |

---

## Commandes Utiles

```bash
# Logs en temps réel
docker compose logs -f web

# Shell dans le container PHP
docker compose exec web bash

# Arrêter la stack
docker compose down

# Arrêter + supprimer les volumes (reset DB)
docker compose down -v
```

---

## Déploiement (Vercel)

```bash
# Installer Vercel CLI
npm i -g vercel

# Deploy
vercel --prod
```

Voir [vercel.json](vercel.json) pour la configuration du routing PHP serverless.

---

## Sécurité

- Mots de passe hashés avec `password_hash()` (bcrypt)
- Requêtes via PDO + prepared statements (anti SQL injection)
- JWT signé pour l'API mobile
- Sessions sécurisées (httponly, samesite=strict)
- Variables d'environnement pour tous les secrets

---

Version : 1.0 | Projet scolaire | Groupe : Freeway, CTO, IA
