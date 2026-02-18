-- ==========================================================
-- Script DDL — Création des tables Innov'Events
-- Rédigé manuellement | Auteur : Florian | Février 2026
-- MySQL 8.0 | utf8mb4
-- ==========================================================

CREATE DATABASE IF NOT EXISTS innovevents
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE innovevents;

-- ==========================================================
-- 1. TABLES DE RÉFÉRENCE (aucune dépendance FK)
-- ==========================================================

CREATE TABLE event_types (
  id          INT NOT NULL AUTO_INCREMENT,
  name        VARCHAR(100) NOT NULL,
  description TEXT,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE themes (
  id   INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE app_settings (
  id            INT NOT NULL AUTO_INCREMENT,
  setting_key   VARCHAR(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  setting_value TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- 2. AUTHENTIFICATION
-- ==========================================================

CREATE TABLE users (
  id                       INT NOT NULL AUTO_INCREMENT,
  last_name                VARCHAR(100) DEFAULT NULL,
  first_name               VARCHAR(100) DEFAULT NULL,
  username                 VARCHAR(100) DEFAULT NULL,
  email                    VARCHAR(150) NOT NULL,
  password                 VARCHAR(255) NOT NULL,
  role                     ENUM('admin','employee','client') DEFAULT 'client',
  is_active                TINYINT(1) DEFAULT 1,
  email_verified           TINYINT(1) DEFAULT 0,
  email_verification_token VARCHAR(255) DEFAULT NULL,
  password_reset_token     VARCHAR(255) DEFAULT NULL,
  password_reset_expires   DATETIME DEFAULT NULL,
  must_change_password     TINYINT(1) DEFAULT 0,
  created_at               TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at               TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY (email),
  UNIQUE KEY (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ==========================================================
-- 3. ENTITÉS MÉTIER
-- ==========================================================

CREATE TABLE clients (
  id           INT NOT NULL AUTO_INCREMENT,
  user_id      INT DEFAULT NULL,
  company_name VARCHAR(100) DEFAULT NULL,
  phone        VARCHAR(20) DEFAULT NULL,
  address      VARCHAR(255) DEFAULT NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY (user_id),
  CONSTRAINT fk_client_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE prospects (
  id                     INT NOT NULL AUTO_INCREMENT,
  company_name           VARCHAR(150) DEFAULT NULL,
  last_name              VARCHAR(100) DEFAULT NULL,
  first_name             VARCHAR(100) DEFAULT NULL,
  email                  VARCHAR(150) DEFAULT NULL,
  phone                  VARCHAR(20) DEFAULT NULL,
  location               VARCHAR(255) DEFAULT NULL,
  event_type             VARCHAR(100) DEFAULT NULL,
  planned_date           DATE DEFAULT NULL,
  estimated_participants INT DEFAULT NULL,
  needs_description      TEXT,
  image_path             VARCHAR(255) DEFAULT NULL,
  status                 ENUM('to_contact','qualification','failed','converted') DEFAULT 'to_contact',
  created_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ==========================================================
-- 4. ÉVÉNEMENTS
-- ==========================================================

CREATE TABLE events (
  id              INT NOT NULL AUTO_INCREMENT,
  client_id       INT DEFAULT NULL,
  name            VARCHAR(255) NOT NULL,
  description     TEXT,
  start_date      DATETIME NOT NULL,
  end_date        DATETIME NOT NULL,
  location        VARCHAR(255) DEFAULT NULL,
  attendees_count INT DEFAULT NULL,
  budget          DECIMAL(10,2) DEFAULT NULL,
  image_path      VARCHAR(255) DEFAULT NULL,
  event_type      VARCHAR(100) DEFAULT NULL,
  theme           VARCHAR(100) DEFAULT NULL,
  status          ENUM('draft','client_review','accepted','in_progress','completed','cancelled') DEFAULT 'draft',
  is_visible      TINYINT(1) DEFAULT 0,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY fk_event_client (client_id),
  CONSTRAINT fk_event_client FOREIGN KEY (client_id)
    REFERENCES clients (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ==========================================================
-- 5. DEVIS ET PRESTATIONS
-- ==========================================================

CREATE TABLE quotes (
  id                  INT NOT NULL AUTO_INCREMENT,
  event_id            INT DEFAULT NULL,
  total_ht            DECIMAL(10,2) DEFAULT NULL,
  tax_rate            DECIMAL(5,2) DEFAULT 20.00,
  total_ttc           DECIMAL(10,2) DEFAULT NULL,
  issue_date          DATE DEFAULT NULL,
  status              ENUM('pending','modification','accepted','refused') DEFAULT 'pending',
  modification_reason TEXT,
  counter_proposal    TEXT,
  counter_proposed_at DATETIME DEFAULT NULL,
  created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY fk_quote_event (event_id),
  CONSTRAINT fk_quote_event FOREIGN KEY (event_id)
    REFERENCES events (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE services (
  id             INT NOT NULL AUTO_INCREMENT,
  quote_id       INT DEFAULT NULL,
  label          VARCHAR(255) NOT NULL,
  description    TEXT,
  unit_price_ht  DECIMAL(10,2) DEFAULT NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY fk_service_quote (quote_id),
  CONSTRAINT fk_service_quote FOREIGN KEY (quote_id)
    REFERENCES quotes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ==========================================================
-- 6. GESTION OPÉRATIONNELLE
-- ==========================================================

CREATE TABLE tasks (
  id          INT NOT NULL AUTO_INCREMENT,
  event_id    INT NOT NULL,
  assigned_to INT DEFAULT NULL,
  title       VARCHAR(255) NOT NULL,
  description TEXT,
  status      ENUM('todo','in_progress','done') DEFAULT 'todo',
  due_date    DATE DEFAULT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY fk_task_event (event_id),
  KEY fk_task_user (assigned_to),
  CONSTRAINT fk_task_event FOREIGN KEY (event_id)
    REFERENCES events (id) ON DELETE CASCADE,
  CONSTRAINT fk_task_user FOREIGN KEY (assigned_to)
    REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE notes (
  id         INT NOT NULL AUTO_INCREMENT,
  event_id   INT DEFAULT NULL,
  author_id  INT NOT NULL,
  content    TEXT NOT NULL,
  is_global  TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY fk_note_event (event_id),
  KEY fk_note_author (author_id),
  CONSTRAINT fk_note_event FOREIGN KEY (event_id)
    REFERENCES events (id) ON DELETE SET NULL,
  CONSTRAINT fk_note_author FOREIGN KEY (author_id)
    REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE reviews (
  id          INT NOT NULL AUTO_INCREMENT,
  event_id    INT NOT NULL,
  client_id   INT NOT NULL,
  rating      TINYINT NOT NULL,
  comment     TEXT,
  status      ENUM('pending','approved','rejected') DEFAULT 'pending',
  reviewed_by INT DEFAULT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY fk_review_event (event_id),
  KEY fk_review_client (client_id),
  KEY fk_review_reviewer (reviewed_by),
  CONSTRAINT fk_review_event FOREIGN KEY (event_id)
    REFERENCES events (id) ON DELETE CASCADE,
  CONSTRAINT fk_review_client FOREIGN KEY (client_id)
    REFERENCES clients (id) ON DELETE CASCADE,
  CONSTRAINT fk_review_reviewer FOREIGN KEY (reviewed_by)
    REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
