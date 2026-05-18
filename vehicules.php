<?php
require_once 'connexion.php';
exiger_authentification();
$pdo = get_pdo();
$role = get_role_utilisateur();

$filtre_concession = $_GET['concession'] ?? '';
$filtre_statut = $_GET['statut'] ?? '';
$filtre_carburant = $_GET['carburant'] ?? '';
$filtre_neuf = $_GET['neuf'] ?? '';

$vehicules = [];
$erreur = '';

try {
    $sql = "SELECT v.vehicule_id, v.marque, v.modele, v.annee, v.type_carburant,
                   v.type_vehicule, v.couleur, v.kilometrage, v.neuf, v.prix_vente, v.statut,
                   c.nom_concession, c.ville
            FROM Vehicules v
            JOIN Concessions c ON v.concession_id = c.concession_id
            WHERE 1=1";
    $params = [];
    if ($filtre_concession) { $sql .= " AND v.concession_id = :co"; $params['co'] = (int)$filtre_concession; }
    if ($filtre_statut) { $sql .= " AND v.statut = :s"; $params['s'] = $filtre_statut; }
    if ($filtre_carburant) { $sql .= " AND v.type_carburant = :ca"; $params['ca'] = $filtre_carburant; }
    if ($filtre_neuf === 'oui') $sql .= " AND v.neuf = TRUE";
    elseif ($filtre_neuf === 'non') $sql .= " AND v.neuf = FALSE";
    $sql .= " ORDER BY c.nom_concession, v.marque, v.modele";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $vehicules = $stmt->fetchAll();
    $concessions = $pdo->query("SELECT concession_id, nom_concession FROM Concessions ORDER BY nom_concession")->fetchAll();
    $carburants = $pdo->query("SELECT DISTINCT type_carburant FROM Vehicules ORDER BY type_carburant")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $erreur = "Vous n'avez pas les droits : " . $e->getMessage();
    $concessions = [];
    $carburants = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Véhicules - AutoLeman</title><link rel="stylesheet" href="style.css"></head>
<body>
    <header>
        <div class="logo"><div class="logo-icon">AL</div><div><h1>AutoLeman</h1><p>Parc automobile</p></div></div>
        <div class="user-info">
            <span>Connecté : <strong><?= h($_SESSION['db_user']) ?></strong></span>
            <span class="role-badge"><?= h($role) ?></span>
            <a href="logout.php" class="btn-logout">Déconnexion</a>
        </div>
    </header>

    <nav>
        <a href="index.php">Dashboard</a><a href="console.php">Console SQL</a>
        <a href="clients.php">Clients</a><a href="vehicules.php" class="active">Véhicules</a>
        <a href="ventes.php">Ventes</a><a href="rapports.php">Rapports</a>
    </nav>

    <main>
        <?php if ($erreur): ?>
            <div class="alert erreur"><?= h($erreur) ?></div>
        <?php else: ?>
            <div class="card">
                <h2>Filtrer (<?= count($vehicules) ?> résultat<?= count($vehicules) > 1 ? 's' : '' ?>)</h2>
                <form method="GET">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Concession</label>
                            <select name="concession">
                                <option value="">Toutes</option>
                                <?php foreach ($concessions as $c): ?>
                                    <option value="<?= h($c['concession_id']) ?>" <?= $filtre_concession == $c['concession_id'] ? 'selected' : '' ?>><?= h($c['nom_concession']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Statut</label>
                            <select name="statut">
                                <option value="">Tous</option>
                                <option value="Disponible" <?= $filtre_statut === 'Disponible' ? 'selected' : '' ?>>Disponible</option>
                                <option value="Réservé" <?= $filtre_statut === 'Réservé' ? 'selected' : '' ?>>Réservé</option>
                                <option value="Vendu" <?= $filtre_statut === 'Vendu' ? 'selected' : '' ?>>Vendu</option>
                                <option value="En atelier" <?= $filtre_statut === 'En atelier' ? 'selected' : '' ?>>En atelier</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Carburant</label>
                            <select name="carburant">
                                <option value="">Tous</option>
                                <?php foreach ($carburants as $cb): ?>
                                    <option value="<?= h($cb) ?>" <?= $filtre_carburant === $cb ? 'selected' : '' ?>><?= h($cb) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>État</label>
                            <select name="neuf">
                                <option value="">Tous</option>
                                <option value="oui" <?= $filtre_neuf === 'oui' ? 'selected' : '' ?>>Neuf</option>
                                <option value="non" <?= $filtre_neuf === 'non' ? 'selected' : '' ?>>Occasion</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex:0;"><label>&nbsp;</label><button type="submit">Filtrer</button></div>
                        <div class="form-group" style="flex:0;"><label>&nbsp;</label><a href="vehicules.php" class="btn btn-warning">Réinitialiser</a></div>
                    </div>
                </form>
            </div>

            <div class="card">
                <h2>Parc véhicules</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr><th>Marque</th><th>Modèle</th><th>Année</th><th>Carburant</th><th>Type</th><th>Concession</th><th>Km</th><th>État</th><th>Statut</th><th>Prix</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($vehicules)): ?>
                                <tr><td colspan="10" class="text-center text-muted">Aucun véhicule.</td></tr>
                            <?php else: foreach ($vehicules as $v): ?>
                                <tr>
                                    <td><strong><?= h($v['marque']) ?></strong></td>
                                    <td><?= h($v['modele']) ?></td>
                                    <td><?= h($v['annee']) ?></td>
                                    <td><span class="badge <?= strtolower($v['type_carburant']) ?>"><?= h($v['type_carburant']) ?></span></td>
                                    <td><?= h($v['type_vehicule']) ?></td>
                                    <td><?= h($v['nom_concession']) ?></td>
                                    <td><?= number_format($v['kilometrage'], 0, '.', "'") ?> km</td>
                                    <td><?= $v['neuf'] ? '<span class="badge neuf">Neuf</span>' : '<span class="badge occasion">Occasion</span>' ?></td>
                                    <td><span class="badge <?= strtolower(str_replace(' ','_',$v['statut'])) ?>"><?= h($v['statut']) ?></span></td>
                                    <td style="text-align:right;"><strong><?= number_format($v['prix_vente'], 0, '.', "'") ?> CHF</strong></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>
    <footer>AutoLeman - École Schulz</footer>
</body>
</html>
