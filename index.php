<?php
require_once 'connexion.php';
exiger_authentification();

$pdo = get_pdo();
$role = get_role_utilisateur();

$message = '';
$type_message = '';
$resultats = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'rechercher_client') {
        $recherche = trim($_POST['recherche'] ?? '');
        if (empty($recherche)) {
            $message = 'Veuillez saisir un terme de recherche.';
            $type_message = 'erreur';
        } elseif (strlen($recherche) < 2) {
            $message = 'La recherche doit contenir au moins 2 caractères.';
            $type_message = 'erreur';
        } else {
            try {
                $sql = "SELECT client_id, nom, prenom, email, telephone, canton, type_client, entreprise
                        FROM Clients
                        WHERE nom LIKE :r OR prenom LIKE :r OR email LIKE :r OR entreprise LIKE :r
                        ORDER BY nom, prenom LIMIT 50";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['r' => '%' . $recherche . '%']);
                $resultats = $stmt->fetchAll();
                if (empty($resultats)) {
                    $message = 'Aucun client trouvé pour : ' . h($recherche);
                    $type_message = 'info';
                } else {
                    $message = count($resultats) . ' résultat(s) trouvé(s).';
                    $type_message = 'succes';
                }
            } catch (PDOException $e) {
                $message = 'Vous n\'avez pas les droits pour effectuer cette recherche.';
                $type_message = 'erreur';
            }
        }
    }
}

$nb_concessions = '-';
$nb_vehicules_dispo = '-';
$nb_ventes_cours = '-';
$ca_mois = '-';

try { $nb_concessions = $pdo->query("SELECT COUNT(*) FROM Concessions")->fetchColumn(); } catch (PDOException $e) {}
try { $nb_vehicules_dispo = $pdo->query("SELECT COUNT(*) FROM Vehicules WHERE statut = 'Disponible'")->fetchColumn(); } catch (PDOException $e) {}
try { $nb_ventes_cours = $pdo->query("SELECT COUNT(*) FROM Ventes WHERE statut = 'En cours'")->fetchColumn(); } catch (PDOException $e) {}
try {
    $ca_mois = $pdo->query("SELECT SUM(prix_negocie) FROM Ventes WHERE statut = 'Livré' AND date_vente >= DATE_SUB((SELECT MAX(date_vente) FROM Ventes), INTERVAL 30 DAY)")->fetchColumn();
    $ca_mois = number_format($ca_mois ?? 0, 0, '.', "'");
} catch (PDOException $e) {}

$dernieres_ventes = [];
try {
    $stmt = $pdo->query("SELECT v.vente_id, v.date_vente, v.statut, v.prix_negocie,
                                c.nom AS client_nom, c.prenom AS client_prenom,
                                ve.marque, ve.modele, co.nom_concession
                         FROM Ventes v
                         JOIN Clients c ON v.client_id = c.client_id
                         JOIN Vehicules ve ON v.vehicule_id = ve.vehicule_id
                         JOIN Concessions co ON v.concession_id = co.concession_id
                         ORDER BY v.date_vente DESC LIMIT 5");
    $dernieres_ventes = $stmt->fetchAll();
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - AutoLeman</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="logo">
            <div class="logo-icon">AL</div>
            <div><h1>AutoLeman</h1><p>Tableau de bord - Réseau de concessions</p></div>
        </div>
        <div class="user-info">
            <span>Connecté : <strong><?= h($_SESSION['db_user']) ?></strong></span>
            <span class="role-badge"><?= h($role) ?></span>
            <a href="logout.php" class="btn-logout">Déconnexion</a>
        </div>
    </header>

    <nav>
        <a href="index.php" class="active">Dashboard</a>
        <a href="console.php">Console SQL</a>
        <a href="clients.php">Clients</a>
        <a href="vehicules.php">Véhicules</a>
        <a href="ventes.php">Ventes</a>
        <a href="rapports.php">Rapports</a>
    </nav>

    <main>
        <div class="stats">
            <div class="stat-card success">
                <div class="label">Concessions</div>
                <div class="value"><?= h($nb_concessions) ?></div>
            </div>
            <div class="stat-card info">
                <div class="label">Véhicules disponibles</div>
                <div class="value"><?= h($nb_vehicules_dispo) ?></div>
                <div class="sub">Prêts à vendre</div>
            </div>
            <div class="stat-card warning">
                <div class="label">Ventes en cours</div>
                <div class="value"><?= h($nb_ventes_cours) ?></div>
            </div>
            <div class="stat-card success">
                <div class="label">CA 30 derniers jours (CHF)</div>
                <div class="value"><?= h($ca_mois) ?></div>
            </div>
        </div>

        <div class="card">
            <h2>Rechercher un client</h2>
            <?php if ($message): ?>
                <div class="alert <?= h($type_message) ?>"><?= h($message) ?></div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <input type="hidden" name="action" value="rechercher_client">
                <div class="form-row">
                    <div class="form-group" style="flex:3;">
                        <label for="recherche">Nom, prénom, email ou entreprise</label>
                        <input type="text" id="recherche" name="recherche"
                               placeholder="Ex: Cottier, Lopez, marc.cottier@email.ch..."
                               value="<?= h($_POST['recherche'] ?? '') ?>"
                               required minlength="2" maxlength="100">
                    </div>
                    <div class="form-group" style="flex:0;">
                        <button type="submit">Rechercher</button>
                    </div>
                </div>
            </form>

            <?php if (!empty($resultats)): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Email</th><th>Canton</th><th>Type</th><th>Entreprise</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resultats as $r): ?>
                                <tr>
                                    <td><?= h($r['client_id']) ?></td>
                                    <td><strong><?= h($r['nom']) ?></strong></td>
                                    <td><?= h($r['prenom']) ?></td>
                                    <td><?= h($r['email']) ?></td>
                                    <td><?= h($r['canton']) ?></td>
                                    <td><span class="badge <?= strtolower($r['type_client']) ?>"><?= h($r['type_client']) ?></span></td>
                                    <td><?= h($r['entreprise'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($dernieres_ventes)): ?>
            <div class="card">
                <h2>Dernières ventes</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr><th>Date</th><th>Statut</th><th>Client</th><th>Véhicule</th><th>Concession</th><th>Prix (CHF)</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dernieres_ventes as $v): ?>
                                <tr>
                                    <td><?= h(date('d/m/Y', strtotime($v['date_vente']))) ?></td>
                                    <td><span class="badge <?= strtolower(str_replace(' ','_',$v['statut'])) ?>"><?= h($v['statut']) ?></span></td>
                                    <td><?= h($v['client_prenom'] . ' ' . $v['client_nom']) ?></td>
                                    <td><?= h($v['marque'] . ' ' . $v['modele']) ?></td>
                                    <td><?= h($v['nom_concession']) ?></td>
                                    <td><strong><?= number_format($v['prix_negocie'], 0, '.', "'") ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>Comment travailler sur cette plateforme</h2>
            <p class="text-muted" style="line-height:1.8;">
                <strong>•</strong> La plateforme est livrée clé en main : <a href="clients.php" style="color:#1e3a5f; font-weight:600;">Clients</a>, <a href="vehicules.php" style="color:#1e3a5f; font-weight:600;">Véhicules</a>, <a href="ventes.php" style="color:#1e3a5f; font-weight:600;">Ventes</a> et <a href="rapports.php" style="color:#1e3a5f; font-weight:600;">Rapports</a> sont fonctionnels.<br>
                <strong>•</strong> Vos exercices se concentrent sur l'écriture de <strong>requêtes SQL</strong> dans la <a href="console.php" style="color:#1e3a5f; font-weight:600;">Console SQL</a> et sur la <strong>conception de tests</strong>.<br>
                <strong>•</strong> Vos droits dans la console correspondent à vos permissions GRANT MySQL.<br>
                <strong>•</strong> Testez les pages avec différents comptes pour observer les différences de droits.
            </p>
        </div>
    </main>

    <footer>AutoLeman Demo - École Schulz - BTEC LO2/LO3</footer>
</body>
</html>
