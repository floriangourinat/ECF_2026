# Documentation technique — Innov’Events (ECF 2026)

## 1) Présentation et périmètre

**Innov’Events** est une application de gestion d’événements composée de :
- un **backend API** en **PHP 8.2 (Apache)** (`backend/`)
- un **frontend web** en **Angular 20** (`frontend/`)
- une **application mobile** en **Ionic Angular 7 / Angular 17** (`mobile/`)

Cette documentation décrit :
- l’architecture et le fonctionnement technique du projet,
- l’environnement de développement (Docker + outils),
- les principaux composants (API, BDD, logs, emails),
- l’état de la CI/CD et les points connus côté configuration.

---

## 2) Organisation du dépôt

### 2.1 Arborescence principale

- `backend/` : API REST PHP (scripts par domaine)
- `frontend/` : application web Angular
- `mobile/` : application mobile Ionic/Angular
- `docker-compose.yml` : environnement dev (services DB, outils, mail)
- `innovevents.sql` : export SQL pour initialiser MySQL
- `.github/workflows/ci-cd.yml` : pipeline CI/CD

### 2.2 Convention d’API (script-based)

Le backend est structuré par **scripts PHP** :
- 1 fichier = 1 endpoint
- regroupement par domaine sous `backend/api/<domaine>/...`

Exemples :
- `backend/api/auth/login.php`
- `backend/api/events/read_public.php`
- `backend/api/quotes/generate_pdf.php`

---

## 3) Stack technique

### 3.1 Backend (PHP)

Références code :
- `backend/Dockerfile`
- `backend/composer.json`
- `backend/config/*`
- `backend/services/*`

- **PHP 8.2** via Apache (Dockerfile)
- Extensions : `pdo`, `pdo_mysql`, `mongodb`
- Dépendances Composer :
  - `mongodb/mongodb`
  - `tecnickcom/tcpdf` (PDF)
  - `phpmailer/phpmailer` (email)

### 3.2 Frontend web

Référence code : `frontend/package.json`

- Angular 20
- RxJS 7

### 3.3 Mobile

Références code :
- `mobile/package.json`
- `mobile/proxy.conf.json`
- `mobile/src/environments/*`

- Ionic Angular 7
- Angular 17
- Cypress (e2e)

### 3.4 Environnement dev (Docker Compose)

Référence : `docker-compose.yml`

Services dev :
- API backend
- MySQL 8
- MongoDB
- phpMyAdmin
- Mongo Express
- MailHog

---

## 4) Environnement de développement

### 4.1 Prérequis

- Docker
- Docker Compose
- Node.js 20+ et npm (pour lancer le frontend et le mobile en local)

### 4.2 Ports et URLs

Après démarrage de l’environnement :

- Backend API : `http://localhost:8080`
- phpMyAdmin : `http://localhost:8081`
- Mongo Express : `http://localhost:8082`
- MailHog UI : `http://localhost:8090`

Ports techniques :
- MongoDB : `localhost:27017`
- SMTP MailHog : `localhost:1025`

---

## 5) Installation et initialisation (dev)

### 5.1 Démarrer l’environnement (Docker)

Depuis la racine du projet :

    docker compose up -d --build
    docker compose ps

### 5.2 Importer la base MySQL via phpMyAdmin

Références :
- `innovevents.sql`
- service phpMyAdmin (`docker-compose.yml`)

1. Ouvrir `http://localhost:8081`
2. Se connecter :
   - utilisateur : `root`
   - mot de passe : `root`
3. Sélectionner la base `innovevents`
4. Onglet **Importer**
5. Importer `innovevents.sql`
6. Valider l’import

---

## 6) Architecture globale

### 6.1 Vue d’ensemble

- Le frontend et le mobile consomment l’API via HTTP/JSON.
- Le backend persiste les données dans MySQL et utilise MongoDB pour les logs.
- Les emails en dev sont capturés par MailHog.

Schéma logique :

    [Frontend Angular]         [Mobile Ionic]
            |                       |
            | HTTP JSON             | HTTP JSON
            v                       v
               [Backend API PHP (Apache)]
                     |         |
                   [MySQL]   [MongoDB]
                             (Logs)
                     |
                  [MailHog]
                  (dev mail)

---

## 7) Backend API (PHP)

### 7.1 Configuration MySQL

Référence : `backend/config/database.php`

Valeurs attendues en dev :
- host : `db`
- database : `innovevents`
- user : `root`
- password : `root`

### 7.2 Configuration MongoDB

Référence : `backend/config/database_mongo.php`

Valeurs attendues en dev :
- host : `mongo`
- port : `27017`
- database : `innovevents_nosql`
- user : `root`
- password : `root`

### 7.3 JWT (auth)

Références :
- `backend/config/jwt.php`
- `backend/services/JwtService.php`

- Le backend lit `JWT_SECRET`.
- Si non défini, une valeur par défaut est utilisée (à éviter en production).

### 7.4 Email

Référence : `backend/config/mail.php`

- En dev : envoi vers MailHog (`mailhog:1025`)
- En prod : SMTP Brevo (configuration côté production)

### 7.5 Logs d’activité (MongoDB)

Référence : `backend/services/MongoLogger.php`

Le backend insère des logs (documents) dans MongoDB pour tracer certaines actions métier.
Des endpoints dédiés permettent également de consulter les logs/stats :
- `backend/api/logs/read.php`
- `backend/api/logs/stats.php`

### 7.6 Upload d’images

Références :
- `backend/api/events/upload_image.php`
- `backend/api/prospects/upload_image.php`
- dossiers `backend/uploads/...`

Principe :
- contrôle extension autorisée (images)
- contrôle taille maximale
- génération de nom unique
- stockage dans `backend/uploads/<domaine>/`
- chemin renvoyé sous la forme `/uploads/<domaine>/<fichier>`

---

## 8) Base de données MySQL (modèle)

Référence : `innovevents.sql`

Le dump contient la structure et les données nécessaires au fonctionnement (référentiels, paramétrages, etc.).

Tables principales (lecture fonctionnelle) :
- `users` : authentification + rôle (`admin`, `employee`, `client`), activation, vérification email, statut changement mdp
- `clients` : profil client (lié à `users`)
- `events` : événements (liés aux clients, assignation possible à un employé)
- `quotes` / `quote_items` : devis + lignes de devis
- `prospects` : prospects (convertibles en clients)
- `reviews` : avis (publication selon validation)
- `tasks` : tâches liées aux événements
- `event_notes` : notes liées aux événements
- `services`, `themes`, `event_types` : référentiels
- `app_settings` : paramètres applicatifs

---

## 9) Frontend web (Angular)

Références :
- `frontend/package.json`
- `frontend/src/app/*`

- Consommation de l’API via HTTP.
- Le token JWT est stocké côté client et réutilisé pour les appels authentifiés (via interceptor côté Angular).

Commandes :

    cd frontend
    npm install
    npm start

---

## 10) Mobile (Ionic Angular)

Références :
- `mobile/proxy.conf.json`
- `mobile/src/environments/environment.ts`
- `mobile/src/app/*`

Le mobile utilise un proxy en dev (évite d’avoir à gérer CORS côté navigateur) :
- appels vers `/api/...`
- proxy vers `http://localhost:8080`

Commandes :

    cd mobile
    npm install
    npm start

---

## 11) CORS (dev vs prod)

### 11.1 En développement (local)

En local, la configuration CORS n’est pas strictement uniformisée : elle est gérée principalement pour les usages `localhost` (notamment `4200` et `4300`) et peut varier selon les endpoints.

Remarque : côté mobile, le proxy `/api` permet de limiter les impacts CORS en environnement de développement.

### 11.2 En production

En production, la configuration CORS est bien configurée, uniformisée et restreinte aux domaines autorisés.

---

## 12) CI/CD (GitHub Actions) — état actuel

Référence : `.github/workflows/ci-cd.yml`

- Le pipeline CI exécute les étapes principales (installation, lint, tests, build) sur le frontend et le mobile.
- La partie CD (déploiement automatisé) n’a pas été finalisée : la configuration n’est pas complète et le déploiement automatique n’est pas opérationnel à ce stade.

---

## 13) Tests et qualité

Références :
- `frontend/src/**/*.spec.ts`
- `mobile/src/**/*.spec.ts`
- `mobile/cypress/*`

Frontend :

    cd frontend
    npm test
    npm run lint
    npm run build

Mobile :

    cd mobile
    npm test
    npm run lint
    npm run e2e

---

## 14) Exploitation et maintenance (dev)

Logs :

    docker compose logs -f backend
    docker compose logs -f db
    docker compose logs -f mongo
    docker compose logs -f mailhog

Shell containers :

    docker exec -it ecf_backend bash

Reset complet (⚠️ destructif) :

    docker compose down -v

---

## 15) Dépannage rapide (FAQ)

### 15.1 API inaccessible (8080)

- vérifier `docker compose ps`
- consulter `docker compose logs -f backend`

### 15.2 Erreur MySQL

- vérifier que `db` est Up
- vérifier que `innovevents.sql` a bien été importé (phpMyAdmin)

### 15.3 Emails dev

- vérifier `mailhog` Up
- ouvrir `http://localhost:8090`

---

## 16) Auteur

Florian Gourinat — Projet ECF 2026 — Innov’Events
"""