-- =============================================================================
-- AUTOLEMAN - Création des utilisateurs MySQL avec permissions
-- À exécuter EN TANT QUE root APRÈS autoleman_database.sql
-- =============================================================================

USE autoleman;

-- Nettoyage si existants
DROP USER IF EXISTS 'vendeur_junior'@'localhost';
DROP USER IF EXISTS 'vendeur_senior'@'localhost';
DROP USER IF EXISTS 'directeur_commercial'@'localhost';

-- =============================================================================
-- UTILISATEUR 1 : VENDEUR_JUNIOR (droits limités)
-- =============================================================================
CREATE USER 'vendeur_junior'@'localhost' IDENTIFIED BY 'VendeurP@ss2025';

-- SELECT sur les tables de consultation
GRANT SELECT ON autoleman.Clients TO 'vendeur_junior'@'localhost';
GRANT SELECT ON autoleman.Vehicules TO 'vendeur_junior'@'localhost';
GRANT SELECT ON autoleman.Concessions TO 'vendeur_junior'@'localhost';

-- INSERT uniquement sur Ventes (création de nouvelles ventes)
GRANT INSERT, SELECT ON autoleman.Ventes TO 'vendeur_junior'@'localhost';

-- Lecture sur SAV pour consulter (pas modifier)
GRANT SELECT ON autoleman.Services_Apres_Vente TO 'vendeur_junior'@'localhost';

-- PAS d'accès à Vendeurs (RH) - confidentialité des salaires/commissions

-- =============================================================================
-- UTILISATEUR 2 : VENDEUR_SENIOR (droits étendus)
-- =============================================================================
CREATE USER 'vendeur_senior'@'localhost' IDENTIFIED BY 'SeniorP@ss2025';

-- Lecture sur tout
GRANT SELECT ON autoleman.* TO 'vendeur_senior'@'localhost';

-- INSERT/UPDATE sur Ventes (gestion des ventes complète, livraison)
GRANT INSERT, UPDATE ON autoleman.Ventes TO 'vendeur_senior'@'localhost';

-- INSERT/UPDATE sur Vehicules (mise à jour statut, prix négocié)
GRANT INSERT, UPDATE ON autoleman.Vehicules TO 'vendeur_senior'@'localhost';

-- INSERT/UPDATE sur Clients (création de nouveaux clients)
GRANT INSERT, UPDATE ON autoleman.Clients TO 'vendeur_senior'@'localhost';

-- INSERT/UPDATE sur SAV (peut ouvrir un SAV)
GRANT INSERT, UPDATE ON autoleman.Services_Apres_Vente TO 'vendeur_senior'@'localhost';

-- =============================================================================
-- UTILISATEUR 3 : DIRECTEUR_COMMERCIAL (tous les droits)
-- =============================================================================
CREATE USER 'directeur_commercial'@'localhost' IDENTIFIED BY 'AdminP@ss2025';

GRANT ALL PRIVILEGES ON autoleman.* TO 'directeur_commercial'@'localhost';

-- =============================================================================
FLUSH PRIVILEGES;
SELECT 'Utilisateurs AutoLeman créés avec succès' AS message;
