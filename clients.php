<?php
require_once 'connexion.php';
exiger_authentification();
$pdo = get_pdo();
$role = get_role_utilisateur();

$filtre_canton = $_GET['canton'] ?? '';
$filtre_type = $_GET['type'] ?? '';

$clients = [];
$erreur = '';

try {
    $sql = "SELECT client_id, nom, prenom, email, telephone, canton, type_client, entreprise, date_inscription
            FROM Clients WHERE 1=1";
    $params = [];
    if ($filtre_canton) { $sql .= " AND canton = :c"; $params['c'] = $filtre_canton; }
    if ($filtre_type) { $sql .= " AND type_client = :t"; $params['t'] = $filtre_type; }
    $sql .= " ORDER BY nom, prenom";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $clients = $stmt->fetchAll();
    $cantons = $pdo->query("SELECT DISTINCT canton FROM Clients ORDER BY canton")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $erreur = "Vous n'avez pas les droits : " . $e->getMessage();
    $cantons = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Clients - AutoLeman</title><link rel="stylesheet" href="style.css"></head>
<body>
    <header>
        <div class="logo"><div class="logo-icon">AL</div><div><h1>AutoLeman</h1><p>Liste des clients</p></div></div>
        <div class="user-info">
            <span>Connecté : <strong><?= h($_SESSION['db_user']) ?></strong></span>
            <span class="role-badge"><?= h($role) ?></span>
            <a href="logout.php" class="btn-logout">Déconnexion</a>
        </div>
    </header>

    <nav>
        <a href="index.php">Dashboard</a><a href="console.php">Console SQL</a>
        <a href="clients.php" class="active">Clients</a><a href="vehicules.php">Véhicules</a>
        <a href="ventes.php">Ventes</a><a href="rapports.php">Rapports</a>
    </nav>

    <main>
        <?php if ($erreur): ?>
            <div class="alert erreur"><?= h($erreur) ?></div>
        <?php else: ?>
            <div class="card">
                <h2>Filtrer (<?= count($clients) ?> résultat<?= count($clients) > 1 ? 's' : '' ?>)</h2>
                <form method="GET">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Canton</label>
                            <select name="canton">
                                <option value="">Tous</option>
                                <?php foreach ($cantons as $c): ?>
                                    <option value="<?= h($c) ?>" <?= $filtre_canton === $c ? 'selected' : '' ?>><?= h($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Type</label>
                            <select name="type">
                                <option value="">Tous</option>
                                <option value="Particulier" <?= $filtre_type === 'Particulier' ? 'selected' : '' ?>>Particulier</option>
                                <option value="Entreprise" <?= $filtre_type === 'Entreprise' ? 'selected' : '' ?>>Entreprise</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex:0;"><label>&nbsp;</label><button type="submit">Filtrer</button></div>
                        <div class="form-group" style="flex:0;"><label>&nbsp;</label><a href="clients.php" class="btn btn-warning">Réinitialiser</a></div>
                    </div>
                </form>
            </div>

            <div class="card">
                <h2>Liste</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Email</th><th>Canton</th><th>Type</th><th>Entreprise</th><th>Inscription</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($clients)): ?>
                                <tr><td colspan="8" class="text-center text-muted">Aucun client.</td></tr>
                            <?php else: foreach ($clients as $c): ?>
                                <tr>
                                    <td><?= h($c['client_id']) ?></td>
                                    <td><strong><?= h($c['nom']) ?></strong></td>
                                    <td><?= h($c['prenom']) ?></td>
                                    <td><?= h($c['email']) ?></td>
                                    <td><?= h($c['canton']) ?></td>
                                    <td><span class="badge <?= strtolower($c['type_client']) ?>"><?= h($c['type_client']) ?></span></td>
                                    <td><?= h($c['entreprise'] ?? '—') ?></td>
                                    <td><?= h(date('d/m/Y', strtotime($c['date_inscription']))) ?></td>
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
