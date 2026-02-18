-- ==========================================================
-- Script DML — Jeu d'essai Innov'Events
-- Rédigé manuellement | Auteur : Florian | Février 2026
-- Données réelles issues des tests fonctionnels
-- ==========================================================

USE innovevents;

-- ---- Tables de référence ----

INSERT INTO event_types (id, name, description) VALUES
  (1, 'Séminaire',            'Réunion de travail ou de formation professionnelle'),
  (2, 'Conférence',           'Présentation devant un public'),
  (3, 'Soirée d\'entreprise', 'Événement festif pour les collaborateurs'),
  (4, 'Team Building',        'Activités de cohésion d\'équipe'),
  (5, 'Autre',                'Autre type d\'événement');

INSERT INTO themes (id, name) VALUES
  (1, 'Élégant'),
  (2, 'Tropical'),
  (3, 'Rétro'),
  (4, 'High-Tech'),
  (5, 'Nature'),
  (6, 'Industriel');

INSERT INTO app_settings (id, setting_key, setting_value) VALUES
  (1, 'quote_success_message',
   'Merci pour votre demande. Chloé vous recontactera dans les plus brefs délais pour discuter de votre projet.');

-- ---- Utilisateurs ----
-- Mot de passe en clair pour tous les comptes : Test1234!

INSERT INTO users (id, last_name, first_name, username, email,
  password, role, is_active, email_verified, must_change_password) VALUES
  (1, 'Dubois', 'Chloé', 'chloe_admin',
   'chloe@innovevents.com',
   '$2y$10$cCr5ripU738CnCeNHnYJx./qH.XN7zj/8Y/iksUvmKOFVPaugoPha',
   'admin', 1, 1, 0),
  (2, 'Dupont', 'Alexandre', 'alexandredupont',
   'alexandre@innovevents.com',
   '$2y$10$NmujWBc5/5eeEUWuDcxMRO8H2F3/2Hxcl9kcMa/RtnMLm4576pFqe',
   'employee', 1, 1, 0),
  (3, 'Renard', 'Camille', NULL,
   'ateliernova@nova.fr',
   '$2y$10$mqq6zSkZ86F15tOFKpueruKWNvBNifylRPaYszIwdLG/jiyIymx8a',
   'client', 1, 0, 1),
  (4, 'Durand', 'Sophie', NULL,
   'sophiedurand@durand.fr',
   '$2y$10$kIAq1qi1QTxYOggP5r6HZOk1VjFObPHy7aJ8/i7mG8ZCaoKvIWt4O',
   'client', 1, 1, 0);

-- ---- Clients ----

INSERT INTO clients (id, user_id, company_name, phone, address) VALUES
  (1, 3, 'Atelier Nova',    '06 58 20 11 90', 'Toulouse'),
  (2, 4, 'Maison Durand',   '06 42 19 77 05', '12 rue des Chartrons, 33000 Bordeaux');

-- ---- Prospects ----

INSERT INTO prospects (id, company_name, last_name, first_name,
  email, phone, location, event_type, planned_date,
  estimated_participants, needs_description, image_path, status) VALUES
  (1, 'Orbis Conseil', 'Morel', 'Nina',
   'ninamorel@orbis.com', '06 09 66 31 44',
   'Lille', 'seminaire', '2026-04-18', 35,
   'Séminaire direction + salle calme + pauses café + déjeuner. Besoin planning et devis détaillé.',
   '/uploads/prospects/prospect_1_1771344228.jpg',
   'to_contact'),
  (2, 'Atelier Nova', 'Renard', 'Camille',
   'ateliernova@nova.fr', '06 58 20 11 90',
   'Toulouse', 'conference', '2026-03-26', 180,
   'Conférence + 2 intervenants + captation vidéo + accueil. Besoin technique son/projection + traiteur pauses.',
   NULL,
   'converted');

-- ---- Événements ----

INSERT INTO events (id, client_id, name, description,
  start_date, end_date, location, attendees_count, budget,
  image_path, event_type, theme, status, is_visible) VALUES
  (1, 1, 'Conférence — Atelier Nova Camille Renard',
   NULL,
   '2026-03-26 09:00:00', '2026-03-26 18:00:00',
   'Toulouse — Espace Compans', NULL, NULL,
   '/uploads/events/event_1_1771355250.png',
   'Conférence', 'High-Tech', 'accepted', 1),
  (2, 1, 'Team Building — Atelier Nova Camille Renard',
   NULL,
   '2026-04-12 10:00:00', '2026-04-12 17:00:00',
   'Toulouse — Domaine extérieur', NULL, NULL,
   NULL,
   'Team Building', 'Nature', 'draft', 0),
  (3, 2, 'Soirée d\'entreprise — Maison Durand',
   '',
   '2026-03-05 19:00:00', '2026-03-05 23:30:00',
   'Bordeaux — Les Chartrons', 0, 0.00,
   '/uploads/events/event_3_1771355967.jpg',
   'Soirée d\'entreprise', 'Élégant', 'in_progress', 1),
  (4, 2, 'Séminaire — Bilan 2025',
   NULL,
   '2026-01-15 09:00:00', '2026-01-15 17:00:00',
   'Bordeaux', NULL, NULL,
   NULL,
   'Séminaire', 'Rétro', 'completed', 1);

-- ---- Devis ----

INSERT INTO quotes (id, event_id, total_ht, tax_rate,
  total_ttc, issue_date, status) VALUES
  (1, 1, 7900.00, 20.00, 9480.00, '2026-02-17', 'pending'),
  (2, 3, 7600.00, 20.00, 9120.00, '2026-02-17', 'accepted');

-- ---- Prestations de service ----

INSERT INTO services (id, quote_id, label, description, unit_price_ht) VALUES
  (1, 1, 'Location salle + technique',   'Salle + régie + installation',           2800.00),
  (2, 1, 'Traiteur (déjeuner + pauses)', '120 personnes, options végétariennes',    4200.00),
  (3, 1, 'Captation vidéo',              '1 caméra + livrable MP4',                 900.00),
  (4, 2, 'DJ & animation',               'DJ + micro + playlist',                  1500.00),
  (5, 2, 'Décoration & éclairage',       'Ambiance Élégant',                       2300.00),
  (6, 2, 'Cocktail dînatoire',           '80 personnes',                           3800.00);

-- ---- Tâches ----

INSERT INTO tasks (id, event_id, assigned_to, title, description, status, due_date) VALUES
  (1, 1, 2, 'Valider prestataire vidéo',          '', 'todo',        '2026-03-10'),
  (2, 1, 2, 'Repérage salle + test projection',   '', 'in_progress', '2026-03-20'),
  (3, 3, 2, 'Confirmer DJ + matériel',            '', 'done',        '2026-02-28');

-- ---- Notes ----

INSERT INTO notes (id, event_id, author_id, content, is_global) VALUES
  (1, NULL, 1, 'Démo soutenance : montrer devis PDF + envoi mail + espace client.', 1),
  (2, 1,    1, 'Prévoir micro HF + répétition intervenants 30 min avant.', 0);

-- ---- Avis clients ----

INSERT INTO reviews (id, event_id, client_id, rating, comment, status, reviewed_by) VALUES
  (1, 4, 2, 5, 'Organisation fluide, équipe réactive, excellente coordination.', 'approved', 2);
