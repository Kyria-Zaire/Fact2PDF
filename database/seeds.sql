-- ============================================================
-- Fact2PDF - Données de test (seeds)
-- NE PAS utiliser en production
-- ============================================================

-- ---- Admin par défaut ----
-- Password: password (bcrypt cost 12)
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`) VALUES
('admin',   'admin@fact2pdf.local',   '$2y$12$3pMLf2ZtxeIxw4KeUFoj7OKHBX5KRfK5UxuArgdF8xF/91uG2TrA6', 'admin'),
('freeway', 'freeway@fact2pdf.local', '$2y$12$3pMLf2ZtxeIxw4KeUFoj7OKHBX5KRfK5UxuArgdF8xF/91uG2TrA6', 'user'),
('viewer',  'viewer@fact2pdf.local',  '$2y$12$3pMLf2ZtxeIxw4KeUFoj7OKHBX5KRfK5UxuArgdF8xF/91uG2TrA6', 'viewer');

-- ---- Clients de test ----
INSERT INTO `clients` (`name`, `email`, `phone`, `address`, `city`, `postal_code`, `country`, `created_by`) VALUES
('Acme Corp',       'contact@acme.com',       '01 23 45 67 89', '12 rue de la Paix',  'Paris',  '75001', 'FR', 1),
('Tech Solutions',  'info@techsolutions.fr',  '04 56 78 90 12', '5 avenue des Alpes', 'Lyon',   '69001', 'FR', 1),
('StartupXYZ',      'hello@startupxyz.io',    '06 11 22 33 44', '8 boulevard du Port','Nantes', '44000', 'FR', 2);

-- ---- Contacts ----
INSERT INTO `contacts` (`client_id`, `name`, `email`, `phone`, `role`, `is_primary`) VALUES
(1, 'Jean Dupont',   'jean@acme.com',        '06 00 11 22 33', 'Directeur',   1),
(1, 'Marie Martin',  'marie@acme.com',       '06 44 55 66 77', 'Comptable',   0),
(2, 'Paul Bernard',  'paul@techsolutions.fr','07 88 99 00 11', 'CTO',         1),
(3, 'Alice Dubois',  'alice@startupxyz.io',  '06 22 33 44 55', 'CEO',         1);

-- ---- Factures ----
INSERT INTO `invoices` (`client_id`, `number`, `status`, `issue_date`, `due_date`, `subtotal`, `tax_rate`, `tax_amount`, `total`, `created_by`) VALUES
(1, 'FACT-2026-0001', 'paid',    '2026-01-05', '2026-02-05', 1000.00, 20.00, 200.00, 1200.00, 1),
(1, 'FACT-2026-0002', 'pending', '2026-01-20', '2026-02-20', 500.00,  20.00, 100.00,  600.00, 1),
(2, 'FACT-2026-0003', 'overdue', '2025-12-01', '2026-01-01', 750.00,  20.00, 150.00,  900.00, 1),
(3, 'FACT-2026-0004', 'draft',   '2026-02-10', '2026-03-10', 2000.00, 20.00, 400.00, 2400.00, 2);

-- ---- Lignes de facture ----
INSERT INTO `invoice_items` (`invoice_id`, `position`, `description`, `quantity`, `unit_price`, `total`) VALUES
(1, 0, 'Développement site web',         5.00,  200.00, 1000.00),
(2, 0, 'Maintenance mensuelle',          1.00,  500.00,  500.00),
(3, 0, 'Audit sécurité',                 3.00,  250.00,  750.00),
(4, 0, 'Développement application mobile', 10.00, 150.00, 1500.00),
(4, 1, 'Formation équipe',               2.00,  250.00,  500.00);

-- ---- Projets de test ----
INSERT INTO `projects` (`client_id`, `invoice_id`, `name`, `description`, `status`, `priority`, `start_date`, `end_date`, `timeline`, `created_by`) VALUES
(1, 1, 'Refonte Site Web Acme', 'Modernisation complète du site corporate', 'in_progress', 'high', '2026-01-05', '2026-03-31',
 '[{"label":"Audit existant","date":"2026-01-05","done":true},{"label":"Maquettes Figma","date":"2026-01-20","done":true},{"label":"Développement","date":"2026-02-01","done":false},{"label":"Recette","date":"2026-03-15","done":false},{"label":"Mise en prod","date":"2026-03-31","done":false}]',
 1),
(2, 3, 'Audit Sécurité Infra', 'Pentest + rapport de sécurité complet', 'review', 'critical', '2025-12-01', '2026-02-28',
 '[{"label":"Reconnaissance","date":"2025-12-05","done":true},{"label":"Tests intrusion","date":"2025-12-20","done":true},{"label":"Rapport draft","date":"2026-01-15","done":true},{"label":"Rapport final","date":"2026-02-15","done":false}]',
 1),
(3, 4, 'App Mobile StartupXYZ', 'Développement app React Native iOS/Android', 'todo', 'medium', '2026-02-10', '2026-06-30',
 '[{"label":"Specs techniques","date":"2026-02-15","done":false},{"label":"MVP","date":"2026-04-01","done":false},{"label":"Beta test","date":"2026-05-15","done":false},{"label":"Store submission","date":"2026-06-30","done":false}]',
 2);
