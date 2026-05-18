# AutoLeman - Plateforme web BTEC Unit 4 LO3

Application web complète accompagnant l'exercice d'entraînement **Unit4_Entrainement2_LO3_AutoLeman**.

## Installation (Windows + WAMP/XAMPP)

### 1. Importer la base de données

Dans phpMyAdmin ou MySQL Workbench, exécuter dans l'ordre :

```
1. autoleman_database.sql  (création BDD + données)
2. autoleman_users.sql     (création des 3 utilisateurs MySQL)
```

> **Note** : le second script doit être exécuté en tant que `root` car il fait du `CREATE USER` et du `GRANT`.

### 2. Déployer les fichiers PHP

Copier tous les fichiers `.php` et `style.css` dans :
- WAMP : `C:\wamp64\www\autoleman\`
- XAMPP : `C:\xampp\htdocs\autoleman\`

### 3. Accéder à l'application

Ouvrir dans le navigateur : http://localhost/autoleman/login.php

## Comptes de connexion

| Identifiant | Mot de passe | Rôle | Droits |
|-------------|--------------|------|--------|
| `vendeur_junior` | `VendeurP@ss2025` | Vendeur débutant | SELECT limité + INSERT ventes |
| `vendeur_senior` | `SeniorP@ss2025` | Vendeur senior | Lecture + INSERT/UPDATE ventes, véhicules, clients, SAV |
| `directeur_commercial` | `AdminP@ss2025` | Directeur commercial | Tous les droits |

## Structure des fichiers

| Fichier | Rôle |
|---------|------|
| `style.css` | Thème bleu auto premium partagé |
| `connexion.php` | Helpers PDO + auth (require_once dans toutes les pages) |
| `login.php` | Formulaire de connexion (auth contre MySQL) |
| `logout.php` | Déconnexion + destruction session |
| `index.php` | Dashboard avec KPIs + recherche client |
| `console.php` | Console SQL libre (200 lignes max, historique) |
| `clients.php` | Liste clients avec filtres (canton, type particulier/entreprise) |
| `vehicules.php` | Parc véhicules avec filtres (concession, statut, carburant, neuf/occasion) |
| `ventes.php` | Liste ventes + action "Livrer" (test des permissions) |
| `rapports.php` | 5 rapports agrégés (CA concession, parc par statut, top marques, SAV récents, performance vendeurs) |

## Pédagogie

- **LO3/P4** : les élèves utilisent la **console SQL** pour exécuter leurs 15 cas de tests
- **LO3/M4** : les **données volontairement incomplètes** dans la BDD (pas de contrainte sur prix_vente > prix_achat, pas de check sur commission_pourcent, etc.) permettent aux tests de détecter de vraies défaillances
- **3 comptes** différents pour observer les blocages MySQL en action

## Règles métier à tester (R1-R9)

| Règle | Description | Faiblesse base actuelle |
|-------|-------------|-------------------------|
| R1 | prix_vente > prix_achat | ❌ Aucun CHECK |
| R2 | commission_pourcent entre 0 et 10 | ❌ Aucun CHECK |
| R3 | kilometrage = 0 si neuf | ❌ Aucun CHECK |
| R4 | date_livraison ≥ date_vente | ❌ Aucun CHECK |
| R5 | remise_pourcent ≤ 20 | ❌ Aucun CHECK |
| R6 | email valide | ❌ Pas de format check |
| R7 | véhicule 'Vendu' non revendable | ❌ Pas de trigger |
| R8 | concession avec véhicules non supprimable | ✅ FK protège |
| R9 | cout_total = cout_pieces + cout_main_oeuvre | ❌ Pas de cohérence calculée |

Ces faiblesses sont **volontaires** : les élèves doivent les détecter et proposer des corrections.

## Mots-clés SQL bloqués par l'application

Pour des raisons de sécurité, les requêtes contenant ces mots-clés sont rejetées dans la console :
- `DROP DATABASE`, `DROP SCHEMA`
- `CREATE USER`, `DROP USER`
- `GRANT `, `REVOKE `
- `SHUTDOWN`

Tout le reste passe par les permissions MySQL natives.

---
*École Schulz - BTEC Unit 4 - 2025/2026*
