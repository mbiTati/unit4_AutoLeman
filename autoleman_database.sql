-- ============================================
-- BASE DE DONNÉES AUTOLEMAN
-- Concession automobile multi-marques
-- Exercice d'entraînement BTEC LO3
-- ============================================

DROP DATABASE IF EXISTS autoleman;
CREATE DATABASE autoleman CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE autoleman;

-- ============================================
-- TABLE 1: CONCESSIONS
-- ============================================
CREATE TABLE Concessions (
    concession_id INT PRIMARY KEY AUTO_INCREMENT,
    nom_concession VARCHAR(100) NOT NULL,
    canton VARCHAR(50) NOT NULL,
    ville VARCHAR(50) NOT NULL,
    adresse VARCHAR(200),
    telephone VARCHAR(15),
    email VARCHAR(100),
    directeur VARCHAR(100),
    date_ouverture DATE,
    superficie_m2 INT
);

-- ============================================
-- TABLE 2: VENDEURS
-- (Employés commerciaux de la concession)
-- Problèmes volontaires: pas de contrainte sur commission_rate
-- ============================================
CREATE TABLE Vendeurs (
    vendeur_id INT PRIMARY KEY AUTO_INCREMENT,
    concession_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    telephone VARCHAR(15),
    date_embauche DATE,
    role VARCHAR(50), -- 'Vendeur', 'Senior', 'Directeur Commercial'
    salaire_base DECIMAL(8,2),
    commission_pourcent DECIMAL(5,2) DEFAULT 2.00, -- Pas de contrainte CHECK
    actif BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (concession_id) REFERENCES Concessions(concession_id)
);

-- ============================================
-- TABLE 3: CLIENTS
-- ============================================
CREATE TABLE Clients (
    client_id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    telephone VARCHAR(15),
    adresse VARCHAR(200),
    canton VARCHAR(50),
    type_client VARCHAR(20), -- 'Particulier' ou 'Entreprise'
    entreprise VARCHAR(150),
    date_inscription DATE,
    concession_preferee INT,
    FOREIGN KEY (concession_preferee) REFERENCES Concessions(concession_id)
);

-- ============================================
-- TABLE 4: VEHICULES
-- Catalogue des véhicules avec stock par concession
-- Problèmes volontaires: pas de contrainte CHECK sur prix
-- ============================================
CREATE TABLE Vehicules (
    vehicule_id INT PRIMARY KEY AUTO_INCREMENT,
    concession_id INT NOT NULL,
    marque VARCHAR(50) NOT NULL,
    modele VARCHAR(100) NOT NULL,
    annee YEAR,
    type_carburant VARCHAR(20), -- 'Essence', 'Diesel', 'Hybride', 'Electrique'
    type_vehicule VARCHAR(50), -- 'Berline', 'SUV', 'Compacte', 'Break', 'Sportive', 'Utilitaire'
    couleur VARCHAR(50),
    kilometrage INT DEFAULT 0,
    neuf BOOLEAN DEFAULT TRUE,
    prix_achat DECIMAL(10,2), -- Prix d'achat par la concession
    prix_vente DECIMAL(10,2) NOT NULL, -- Pas de contrainte > prix_achat
    statut VARCHAR(20) DEFAULT 'Disponible', -- 'Disponible', 'Réservé', 'Vendu', 'En atelier'
    date_arrivee DATE,
    FOREIGN KEY (concession_id) REFERENCES Concessions(concession_id)
);

-- ============================================
-- TABLE 5: VENTES
-- Transactions de vente
-- Problèmes volontaires: pas de contrainte sur date_livraison >= date_vente
-- ============================================
CREATE TABLE Ventes (
    vente_id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    vendeur_id INT NOT NULL,
    vehicule_id INT NOT NULL,
    concession_id INT NOT NULL,
    date_vente DATE NOT NULL,
    date_livraison DATE,
    prix_negocie DECIMAL(10,2) NOT NULL,
    remise_pourcent DECIMAL(5,2) DEFAULT 0,
    mode_paiement VARCHAR(50), -- 'Comptant', 'Crédit', 'Leasing'
    statut VARCHAR(20) DEFAULT 'En cours', -- 'En cours', 'Livré', 'Annulé'
    notes TEXT,
    FOREIGN KEY (client_id) REFERENCES Clients(client_id),
    FOREIGN KEY (vendeur_id) REFERENCES Vendeurs(vendeur_id),
    FOREIGN KEY (vehicule_id) REFERENCES Vehicules(vehicule_id),
    FOREIGN KEY (concession_id) REFERENCES Concessions(concession_id)
);

-- ============================================
-- TABLE 6: SERVICES_APRES_VENTE
-- Interventions de maintenance et réparation
-- ============================================
CREATE TABLE Services_Apres_Vente (
    sav_id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    vehicule_id INT NOT NULL,
    concession_id INT NOT NULL,
    date_entree DATE NOT NULL,
    date_sortie DATE,
    type_service VARCHAR(50), -- 'Entretien', 'Réparation', 'Garantie', 'Carrosserie'
    description TEXT,
    cout_pieces DECIMAL(8,2) DEFAULT 0,
    cout_main_oeuvre DECIMAL(8,2) DEFAULT 0,
    cout_total DECIMAL(8,2) NOT NULL,
    sous_garantie BOOLEAN DEFAULT FALSE,
    statut VARCHAR(20) DEFAULT 'En cours', -- 'En cours', 'Terminé', 'Facturé'
    FOREIGN KEY (client_id) REFERENCES Clients(client_id),
    FOREIGN KEY (vehicule_id) REFERENCES Vehicules(vehicule_id),
    FOREIGN KEY (concession_id) REFERENCES Concessions(concession_id)
);

-- ============================================
-- INSERTION DES DONNÉES DE TEST
-- ============================================

-- CONCESSIONS (4 concessions)
INSERT INTO Concessions (nom_concession, canton, ville, adresse, telephone, email, directeur, date_ouverture, superficie_m2) VALUES
('AutoLeman Genève', 'Genève', 'Genève', 'Route de Meyrin 240', '022 345 67 00', 'geneve@autoleman.ch', 'Bertrand Salesse', '2010-04-12', 3500),
('AutoLeman Lausanne', 'Vaud', 'Lausanne', 'Avenue de Provence 18', '021 624 33 50', 'lausanne@autoleman.ch', 'Caroline Demont', '2012-09-15', 4200),
('AutoLeman Sion', 'Valais', 'Sion', 'Route de Riddes 45', '027 322 88 11', 'sion@autoleman.ch', 'Jean-Marc Pittet', '2015-06-20', 2800),
('AutoLeman Fribourg', 'Fribourg', 'Fribourg', 'Route de la Glâne 78', '026 401 22 33', 'fribourg@autoleman.ch', 'Sylvain Gobet', '2018-03-10', 3100);

-- VENDEURS (10 commerciaux)
INSERT INTO Vendeurs (concession_id, nom, prenom, email, telephone, date_embauche, role, salaire_base, commission_pourcent, actif) VALUES
(1, 'Salesse', 'Bertrand', 'b.salesse@autoleman.ch', '079 100 11 22', '2010-04-12', 'Directeur Commercial', 8500.00, 1.50, TRUE),
(1, 'Cottet', 'Magalie', 'm.cottet@autoleman.ch', '078 200 22 33', '2014-09-01', 'Senior', 6200.00, 2.50, TRUE),
(1, 'Reynard', 'Olivier', 'o.reynard@autoleman.ch', '076 300 33 44', '2019-03-15', 'Vendeur', 4800.00, 3.00, TRUE),
(2, 'Demont', 'Caroline', 'c.demont@autoleman.ch', '079 400 44 55', '2012-09-15', 'Directeur Commercial', 8800.00, 1.50, TRUE),
(2, 'Burnier', 'Olivier', 'o.burnier@autoleman.ch', '077 500 55 66', '2016-02-10', 'Senior', 6500.00, 2.50, TRUE),
(2, 'Aubry', 'Sandrine', 's.aubry@autoleman.ch', '076 600 66 77', '2020-06-20', 'Vendeur', 4900.00, 3.00, TRUE),
(3, 'Pittet', 'Jean-Marc', 'jm.pittet@autoleman.ch', '079 700 77 88', '2015-06-20', 'Directeur Commercial', 8400.00, 1.50, TRUE),
(3, 'Roduit', 'Christian', 'c.roduit@autoleman.ch', '078 800 88 99', '2018-04-12', 'Senior', 6100.00, 2.50, TRUE),
(4, 'Gobet', 'Sylvain', 's.gobet@autoleman.ch', '079 900 99 11', '2018-03-10', 'Directeur Commercial', 8500.00, 1.50, TRUE),
(4, 'Pasquier', 'Lionel', 'l.pasquier@autoleman.ch', '077 100 22 33', '2021-08-15', 'Vendeur', 4700.00, 3.00, TRUE);

-- CLIENTS (15 clients : 10 particuliers + 5 entreprises)
INSERT INTO Clients (nom, prenom, email, telephone, adresse, canton, type_client, entreprise, date_inscription, concession_preferee) VALUES
('Cottier', 'Marc', 'marc.cottier@email.ch', '079 111 22 33', 'Chemin des Roches 5, 1208 Genève', 'Genève', 'Particulier', NULL, '2022-03-15', 1),
('Lopez', 'Anna', 'anna.lopez@email.ch', '078 222 33 44', 'Avenue Vibert 22, 1227 Genève', 'Genève', 'Particulier', NULL, '2023-01-20', 1),
('Volet', 'Paul', 'p.volet@constructions.ch', '076 333 44 55', 'Route de Meyrin 100, 1214 Genève', 'Genève', 'Entreprise', 'Volet Constructions SA', '2021-11-10', 1),
('Renaud', 'Sophie', 'sophie.renaud@email.ch', '079 444 55 66', 'Avenue de Rumine 18, 1005 Lausanne', 'Vaud', 'Particulier', NULL, '2022-08-22', 2),
('Tatti', 'Marco', 'm.tatti@logistic.ch', '077 555 66 77', 'Avenue de Provence 50, 1007 Lausanne', 'Vaud', 'Entreprise', 'Tatti Logistic Sàrl', '2020-05-15', 2),
('Rey', 'Catherine', 'c.rey@email.ch', '078 666 77 88', 'Rue de Lausanne 12, 1950 Sion', 'Valais', 'Particulier', NULL, '2023-04-10', 3),
('Imhof', 'Stefan', 'stefan.imhof@email.ch', '079 777 88 99', 'Avenue Tivoli 8, 1700 Fribourg', 'Fribourg', 'Particulier', NULL, '2022-09-05', 4),
('Jaccard', 'Nicolas', 'n.jaccard@email.ch', '076 888 99 11', 'Place de la Riponne 14, 1003 Lausanne', 'Vaud', 'Particulier', NULL, '2024-01-15', 2),
('Pellet', 'Marie', 'm.pellet@email.ch', '077 999 11 22', 'Rue du Rhône 33, 1950 Sion', 'Valais', 'Particulier', NULL, '2023-07-20', 3),
('Genoud', 'Philippe', 'p.genoud@batiment.ch', '079 111 33 55', 'Route de la Glâne 22, 1700 Fribourg', 'Fribourg', 'Entreprise', 'Genoud Bâtiment SA', '2021-03-18', 4),
('Bovay', 'Daniel', 'daniel.bovay@email.ch', '078 222 44 66', 'Quai Wilson 6, 1201 Genève', 'Genève', 'Particulier', NULL, '2022-12-01', 1),
('Maurer', 'Esther', 'esther.maurer@email.ch', '076 333 55 77', 'Avenue de France 14, 1004 Lausanne', 'Vaud', 'Particulier', NULL, '2023-10-12', 2),
('Bagnoud', 'Régis', 'r.bagnoud@taxis.ch', '079 444 66 88', 'Route de Riddes 12, 1950 Sion', 'Valais', 'Entreprise', 'Bagnoud Taxis Sàrl', '2020-06-25', 3),
('Schaller', 'Thomas', 't.schaller@email.ch', '077 555 77 99', 'Rue Pourtalès 8, 2000 Neuchâtel', 'Neuchâtel', 'Particulier', NULL, '2023-12-05', 1),
('Equipe Foot Sion', 'Equipe Foot Sion', 'contact@fcsion.ch', '027 322 11 22', 'Stade de Tourbillon, 1950 Sion', 'Valais', 'Entreprise', 'FC Sion', '2022-02-20', 3);

-- VEHICULES (25 véhicules : neufs + occasions)
-- Genève (8)
INSERT INTO Vehicules (concession_id, marque, modele, annee, type_carburant, type_vehicule, couleur, kilometrage, neuf, prix_achat, prix_vente, statut, date_arrivee) VALUES
(1, 'BMW', 'Série 3 320d', 2024, 'Diesel', 'Berline', 'Noir', 0, TRUE, 38500.00, 49900.00, 'Disponible', '2024-09-15'),
(1, 'Tesla', 'Model 3 Long Range', 2024, 'Electrique', 'Berline', 'Blanc', 0, TRUE, 42000.00, 52900.00, 'Vendu', '2024-08-20'),
(1, 'Audi', 'Q5 Quattro', 2024, 'Hybride', 'SUV', 'Gris', 0, TRUE, 51000.00, 64900.00, 'Disponible', '2024-10-05'),
(1, 'VW', 'Golf 8 GTI', 2023, 'Essence', 'Compacte', 'Rouge', 12500, FALSE, 32000.00, 38500.00, 'Disponible', '2024-07-12'),
(1, 'Renault', 'Mégane E-Tech', 2024, 'Electrique', 'Compacte', 'Bleu', 0, TRUE, 28000.00, 36500.00, 'Réservé', '2024-09-28'),
(1, 'Mercedes', 'Classe A AMG', 2023, 'Essence', 'Compacte', 'Noir', 18900, FALSE, 35000.00, 42500.00, 'Disponible', '2024-06-15'),
(1, 'Toyota', 'RAV4 Hybrid', 2024, 'Hybride', 'SUV', 'Argent', 0, TRUE, 38000.00, 47900.00, 'Vendu', '2024-08-10'),
(1, 'Peugeot', '3008 Allure', 2022, 'Diesel', 'SUV', 'Gris', 35000, FALSE, 22000.00, 28900.00, 'Disponible', '2024-05-20'),

-- Lausanne (7)
(2, 'BMW', 'X3 xDrive', 2024, 'Hybride', 'SUV', 'Noir', 0, TRUE, 52000.00, 67900.00, 'Disponible', '2024-09-10'),
(2, 'Audi', 'A4 Avant', 2024, 'Diesel', 'Break', 'Blanc', 0, TRUE, 41000.00, 53900.00, 'Vendu', '2024-08-05'),
(2, 'Tesla', 'Model Y Performance', 2024, 'Electrique', 'SUV', 'Rouge', 0, TRUE, 55000.00, 68900.00, 'Disponible', '2024-10-12'),
(2, 'VW', 'Tiguan R-Line', 2023, 'Essence', 'SUV', 'Bleu', 22500, FALSE, 36000.00, 44900.00, 'Disponible', '2024-07-25'),
(2, 'Skoda', 'Octavia RS', 2024, 'Diesel', 'Break', 'Vert', 0, TRUE, 32000.00, 41900.00, 'Réservé', '2024-09-22'),
(2, 'Mercedes', 'GLC 300', 2023, 'Diesel', 'SUV', 'Argent', 28000, FALSE, 48000.00, 58900.00, 'Disponible', '2024-06-30'),
(2, 'Volvo', 'XC40 Recharge', 2024, 'Electrique', 'SUV', 'Blanc', 0, TRUE, 45000.00, 56900.00, 'Vendu', '2024-08-15'),

-- Sion (5)
(3, 'BMW', 'iX3', 2024, 'Electrique', 'SUV', 'Noir', 0, TRUE, 58000.00, 72900.00, 'Disponible', '2024-09-18'),
(3, 'VW', 'T-Roc R', 2023, 'Essence', 'SUV', 'Jaune', 8500, FALSE, 35000.00, 42900.00, 'Disponible', '2024-07-08'),
(3, 'Toyota', 'Hilux 4x4', 2024, 'Diesel', 'Utilitaire', 'Blanc', 0, TRUE, 42000.00, 53900.00, 'Vendu', '2024-08-22'),
(3, 'Subaru', 'Forester e-Boxer', 2024, 'Hybride', 'SUV', 'Bleu', 0, TRUE, 38000.00, 48900.00, 'Disponible', '2024-09-05'),
(3, 'Dacia', 'Duster Prestige', 2023, 'Essence', 'SUV', 'Marron', 18000, FALSE, 18000.00, 23900.00, 'Vendu', '2024-06-25'),

-- Fribourg (5)
(4, 'Audi', 'A3 Sportback', 2024, 'Essence', 'Compacte', 'Gris', 0, TRUE, 32000.00, 41900.00, 'Disponible', '2024-09-20'),
(4, 'Renault', 'Captur E-Tech', 2024, 'Hybride', 'SUV', 'Orange', 0, TRUE, 28000.00, 35900.00, 'Réservé', '2024-10-02'),
(4, 'Ford', 'Kuga PHEV', 2023, 'Hybride', 'SUV', 'Bleu', 25000, FALSE, 32000.00, 39900.00, 'Disponible', '2024-07-15'),
(4, 'Citroën', 'C3 Aircross', 2024, 'Essence', 'Compacte', 'Blanc', 0, TRUE, 22000.00, 28900.00, 'Vendu', '2024-08-28'),
(4, 'Hyundai', 'Tucson N-Line', 2023, 'Hybride', 'SUV', 'Noir', 14500, FALSE, 35000.00, 42900.00, 'Disponible', '2024-06-10');

-- VENTES (20 ventes)
INSERT INTO Ventes (client_id, vendeur_id, vehicule_id, concession_id, date_vente, date_livraison, prix_negocie, remise_pourcent, mode_paiement, statut, notes) VALUES
(1, 2, 2, 1, '2024-09-15', '2024-09-22', 51500.00, 2.65, 'Comptant', 'Livré', 'Tesla M3 - paiement comptant'),
(2, 3, 7, 1, '2024-09-20', '2024-09-30', 47500.00, 0.84, 'Crédit', 'Livré', 'Crédit 5 ans'),
(3, 1, 8, 1, '2024-08-10', '2024-08-25', 27500.00, 4.84, 'Leasing', 'Livré', 'Flotte d''entreprise'),
(4, 5, 10, 2, '2024-09-25', '2024-10-10', 52000.00, 3.52, 'Crédit', 'Livré', 'Reprise ancienne voiture'),
(5, 4, 14, 2, '2024-08-15', '2024-09-01', 56000.00, 1.59, 'Leasing', 'Livré', 'Flotte commerciale'),
(6, 8, 17, 3, '2024-09-05', '2024-09-20', 53500.00, 0.74, 'Crédit', 'Livré', NULL),
(7, 9, 22, 4, '2024-09-12', '2024-09-25', 28500.00, 1.39, 'Comptant', 'Livré', NULL),
(8, 6, 15, 2, '2024-10-01', '2024-10-15', 56500.00, 0.70, 'Leasing', 'Livré', NULL),
(9, 8, 19, 3, '2024-08-30', '2024-09-15', 23500.00, 1.67, 'Comptant', 'Livré', 'Bonne affaire occasion'),
(10, 10, 23, 4, '2024-09-18', '2024-10-05', 39500.00, 1.00, 'Crédit', 'Livré', 'Flotte BTP'),
(11, 2, 4, 1, '2024-08-22', '2024-09-05', 37000.00, 3.90, 'Crédit', 'Livré', 'Sportive vendue'),
(12, 4, 13, 2, '2024-09-30', '2024-10-12', 41000.00, 2.15, 'Leasing', 'Livré', NULL),
(13, 7, 18, 3, '2024-10-08', '2024-10-25', 51000.00, 5.38, 'Crédit', 'Livré', 'Taxi - flotte 3 véhicules'),
(14, 3, 1, 1, '2024-10-15', NULL, 48500.00, 2.81, 'Crédit', 'En cours', 'Livraison prévue mi-novembre'),
(15, 7, 18, 3, '2024-10-20', NULL, 51500.00, 4.45, 'Leasing', 'En cours', 'Flotte FC Sion - 2e véhicule'),
-- Quelques ventes plus anciennes pour le SAV
(1, 2, 4, 1, '2024-07-01', '2024-07-15', 38000.00, 1.30, 'Crédit', 'Livré', 'Première voiture'),
(4, 5, 12, 2, '2024-06-15', '2024-06-30', 44500.00, 0.89, 'Crédit', 'Livré', NULL),
(7, 9, 25, 4, '2024-06-20', '2024-07-05', 42000.00, 2.10, 'Crédit', 'Livré', 'Hybride Tucson'),
(11, 3, 6, 1, '2024-05-10', '2024-05-25', 41500.00, 2.35, 'Comptant', 'Livré', 'Mercedes Classe A'),
(8, 6, 11, 2, '2024-04-15', '2024-05-01', 43500.00, 3.12, 'Crédit', 'Livré', NULL);

-- SERVICES_APRES_VENTE (15 SAV)
INSERT INTO Services_Apres_Vente (client_id, vehicule_id, concession_id, date_entree, date_sortie, type_service, description, cout_pieces, cout_main_oeuvre, cout_total, sous_garantie, statut) VALUES
(1, 4, 1, '2024-08-15', '2024-08-15', 'Entretien', 'Vidange + filtres', 120.00, 180.00, 300.00, FALSE, 'Facturé'),
(2, 7, 1, '2024-10-01', '2024-10-02', 'Garantie', 'Remplacement capteur sous garantie', 250.00, 150.00, 0.00, TRUE, 'Facturé'),
(3, 8, 1, '2024-09-12', '2024-09-15', 'Réparation', 'Embrayage + courroie distribution', 850.00, 950.00, 1800.00, FALSE, 'Facturé'),
(4, 10, 2, '2024-10-05', '2024-10-08', 'Carrosserie', 'Réparation aile avant suite collision', 1200.00, 1500.00, 2700.00, FALSE, 'Facturé'),
(4, 12, 2, '2024-08-20', '2024-08-21', 'Entretien', 'Service annuel', 180.00, 220.00, 400.00, FALSE, 'Facturé'),
(5, 14, 2, '2024-09-28', '2024-09-30', 'Garantie', 'Mise à jour logicielle Tesla', 0.00, 200.00, 0.00, TRUE, 'Facturé'),
(7, 25, 4, '2024-09-15', '2024-09-18', 'Réparation', 'Distribution + pompe à eau', 950.00, 1100.00, 2050.00, FALSE, 'Facturé'),
(8, 11, 2, '2024-09-22', '2024-09-23', 'Entretien', 'Service 30000 km', 280.00, 320.00, 600.00, FALSE, 'Facturé'),
(11, 6, 1, '2024-10-10', '2024-10-12', 'Carrosserie', 'Pare-choc avant suite accident parking', 580.00, 720.00, 1300.00, FALSE, 'Facturé'),
(1, 4, 1, '2024-10-25', '2024-10-26', 'Entretien', 'Pneus hiver + alignement', 420.00, 180.00, 600.00, FALSE, 'Facturé'),
(4, 12, 2, '2024-10-28', NULL, 'Réparation', 'Bruit moteur à diagnostiquer', 0.00, 0.00, 0.00, FALSE, 'En cours'),
(7, 25, 4, '2024-10-30', NULL, 'Entretien', 'Service hivernage', 0.00, 0.00, 0.00, FALSE, 'En cours'),
(8, 11, 2, '2024-11-02', NULL, 'Garantie', 'Problème climatisation', 0.00, 0.00, 0.00, TRUE, 'En cours'),
(13, 18, 3, '2024-11-04', NULL, 'Carrosserie', 'Rayure portière conducteur', 0.00, 0.00, 0.00, FALSE, 'En cours'),
(14, 1, 1, '2024-11-05', NULL, 'Entretien', 'Pré-livraison', 0.00, 0.00, 0.00, FALSE, 'En cours');

-- ============================================
-- VÉRIFICATIONS
-- ============================================
SELECT 'Concessions:' AS Info, COUNT(*) AS Total FROM Concessions
UNION ALL SELECT 'Vendeurs:', COUNT(*) FROM Vendeurs
UNION ALL SELECT 'Clients:', COUNT(*) FROM Clients
UNION ALL SELECT 'Vehicules:', COUNT(*) FROM Vehicules
UNION ALL SELECT 'Vehicules disponibles:', COUNT(*) FROM Vehicules WHERE statut = 'Disponible'
UNION ALL SELECT 'Vehicules vendus:', COUNT(*) FROM Vehicules WHERE statut = 'Vendu'
UNION ALL SELECT 'Ventes:', COUNT(*) FROM Ventes
UNION ALL SELECT 'Ventes livrées:', COUNT(*) FROM Ventes WHERE statut = 'Livré'
UNION ALL SELECT 'SAV total:', COUNT(*) FROM Services_Apres_Vente
UNION ALL SELECT 'SAV en cours:', COUNT(*) FROM Services_Apres_Vente WHERE statut = 'En cours';
