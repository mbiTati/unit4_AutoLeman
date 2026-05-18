<?php
require_once 'connexion.php';
exiger_authentification();
$pdo = get_pdo();
$role = get_role_utilisateur();

$message = '';
$type_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'livrer') {
    $id = (int)($_POST['vente_id'] ?? 0);
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE Ventes SET statut='Livré', date_livraison=CURDATE() WHERE vente_id = :id AND statut='En cours'");
            $stmt->execute(['id' => $id]);
            $message = "Vente #$id marquée comme livrée.";
            $type_message = 'succes';
        } catch (PDOException $e) {
            $message = "Action refusée par MySQL : " . $e->getMessage();
            $type_message = 'erreur';
        }
    }
}

$filtre_statut = $_GET['statut'] ?? '';
$filtre_concession = $_GET['concession'] ?? '';

$ventes = [];
$erreur = '';

try {
    $sql = "SELECT v.vente_id, v.date_vente, v.date_livraison, v.statut, v.prix_negocie,
                   v.remise_pourcent, v.mode_paiement,
                   c.nom AS client_nom, c.prenom AS client_prenom, c.type_client,
                   ve.nom AS vendeur_nom,
                   veh.marque, veh.modele, veh.annee,
                   co.nom_concession
            FROM Ventes v
            JOIN Clients c ON v.client_id = c.client_id
            JOIN Vendeurs ve ON v.vendeur_id = ve.vendeur_id
            JOIN Vehicules veh ON v.vehicule_id = veh.vehicule_id
            JOIN Concessions co ON v.concession_id = co.concession_id
            WHERE 1=1";
    $params = [];
    if ($filtre_statut) { $sql .= " AND v.statut = :s"; $params['s'] = $filtre_statut; }
    if ($filtre_concession) { $sql .= " AND v.concession_id = :co"; $params['co'] = (int)$filtre_concession; }
    $sql .= " ORDER BY v.date_vente DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ventes = $stmt->fetchAll();
    $concessions = $pdo->query("SELECT concession_id, nom_concession FROM Concessions ORDER BY nom_concession")->fetchAll();
} catch (PDOException $e) {
    $erreur = "Vous n'avez pas les droits : " . $e->getMessage();
    $concessions = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Ventes - AutoLeman</title><link rel="stylesheet" href="style.css"></head>
<body>
    <header>
        <div class="logo"><div class="logo-icon">AL</div><div><h1>AutoLeman</h1><p>Ventes</p></div></div>
        <div class="user-info">
            <span>Connecté : <strong><?= h($_SESSION['db_user']) ?></strong></span>
            <span class="role-badge"><?= h($role) ?></span>
            <a href="logout.php" class="btn-logout">Déconnexion</a>
        </div>
    </header>

    <nav>
        <a href="index.php">Dashboard</a><a href="console.php">Console SQL</a>
        <a href="clients.php">Clients</a><a href="vehicules.php">Véhicules</a>
        <a href="ventes.php" class="active">Ventes</a><a href="rapports.php">Rapports</a>
    </nav>

    <main>
        <?php if ($message): ?>
            <div class="alert <?= h($type_message) ?>"><?= h($message) ?></div>
        <?php endif; ?>
        <?php if ($erreur): ?>
            <div class="alert erreur"><?= h($erreur) ?></div>
        <?php else: ?>
            <div class="card">
                <h2>Filtrer (<?= count($ventes) ?> résultat<?= count($ventes) > 1 ? 's' : '' ?>)</h2>
                <form method="GET">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Statut</label>
                            <select name="statut">
                                <option value="">Tous</option>
                                <option value="Livré" <?= $filtre_statut === 'Livré' ? 'selected' : '' ?>>Livré</option>
                                <option value="En cours" <?= $filtre_statut === 'En cours' ? 'selected' : '' ?>>En cours</option>
                                <option value="Annulé" <?= $filtre_statut === 'Annulé' ? 'selected' : '' ?>>Annulé</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Concession</label>
                            <select name="concession">
                                <option value="">Toutes</option>
                                <?php foreach ($concessions as $c): ?>
                                    <option value="<?= h($c['concession_id']) ?>" <?= $filtre_concession == $c['concession_id'] ? 'selected' : '' ?>><?= h($c['nom_concession']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="flex:0;"><label>&nbsp;</label><button type="submit">Filtrer</button></div>
                        <div class="form-group" style="flex:0;"><label>&nbsp;</label><a href="ventes.php" class="btn btn-warning">Réinitialiser</a></div>
                    </div>
                </form>
            </div>

            <div class="card">
                <h2>Liste des ventes</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr><th>ID</th><th>Date</th><th>Client</th><th>Véhicule</th><th>Vendeur</th><th>Concession</th><th>Statut</th><th>Prix</th><th>Remise</th><th>Paiement</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ventes)): ?>
                                <tr><td colspan="11" class="text-center text-muted">Aucune vente.</td></tr>
                            <?php else: foreach ($ventes as $v): ?>
                                <?php $cl = strtolower(str_replace(' ','_',$v['statut'])); ?>
                                <tr>
                                    <td><?= h($v['vente_id']) ?></td>
                                    <td><?= h(date('d/m/Y', strtotime($v['date_vente']))) ?></td>
                                    <td><?= h($v['client_prenom'] . ' ' . $v['client_nom']) ?></td>
                                    <td><?= h($v['marque'] . ' ' . $v['modele']) ?> <span class="text-muted">(<?= h($v['annee']) ?>)</span></td>
                                    <td><?= h($v['vendeur_nom']) ?></td>
                                    <td><?= h($v['nom_concession']) ?></td>
                                    <td><span class="badge <?= $cl ?>"><?= h($v['statut']) ?></span></td>
                                    <td style="text-align:right;"><strong><?= number_format($v['prix_negocie'], 0, '.', "'") ?> CHF</strong></td>
                                    <td><?= h($v['remise_pourcent']) ?>%</td>
                                    <td><?= h($v['mode_paiement']) ?></td>
                                    <td>
                                        <?php if ($v['statut'] === 'En cours'): ?>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Marquer cette vente comme livrée ?');">
                                                <input type="hidden" name="action" value="livrer">
                                                <input type="hidden" name="vente_id" value="<?= h($v['vente_id']) ?>">
                                                <button type="submit" class="btn btn-success" style="padding:5px 10px; font-size:12px;">Livrer</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size:12px;">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="info-box" style="background:#eef5fb; border-left:3px solid #3498db; padding:12px 15px; border-radius:4px; font-size:13px; margin-top:20px;">
                <strong>À tester :</strong> avec le compte <code>vendeur_junior</code>, le bouton "Livrer" peut être refusé selon vos permissions GRANT. Avec <code>vendeur_senior</code> ou <code>directeur_commercial</code>, l'action fonctionnera. C'est la sécurité au niveau base de données (LO2) en action.
            </div>
        <?php endif; ?>
    </main>
    <footer>AutoLeman - École Schulz</footer>
</body>
</html>
