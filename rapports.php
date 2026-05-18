<?php
require_once 'connexion.php';
exiger_authentification();
$pdo = get_pdo();
$role = get_role_utilisateur();

function safe_query($pdo, $sql) {
    try { return $pdo->query($sql)->fetchAll(); }
    catch (PDOException $e) { return null; }
}

// R1 : CA par concession
$rapport_ca = safe_query($pdo, "
    SELECT co.nom_concession, co.ville,
           COUNT(v.vente_id) AS nb_ventes,
           ROUND(SUM(v.prix_negocie), 0) AS ca_total,
           ROUND(AVG(v.prix_negocie), 0) AS ticket_moyen
    FROM Concessions co
    LEFT JOIN Ventes v ON co.concession_id = v.concession_id AND v.statut = 'Livré'
    GROUP BY co.concession_id, co.nom_concession, co.ville
    ORDER BY ca_total DESC
");

// R2 : Répartition véhicules par statut
$rapport_statut = safe_query($pdo, "
    SELECT statut, COUNT(*) AS nb,
           ROUND(SUM(prix_vente), 0) AS valeur_totale
    FROM Vehicules
    GROUP BY statut
    ORDER BY nb DESC
");

// R3 : Top marques / modèles vendus
$rapport_marques = safe_query($pdo, "
    SELECT veh.marque,
           COUNT(v.vente_id) AS nb_ventes,
           ROUND(SUM(v.prix_negocie), 0) AS ca_marque,
           ROUND(AVG(v.prix_negocie), 0) AS prix_moyen
    FROM Vehicules veh
    JOIN Ventes v ON veh.vehicule_id = v.vehicule_id
    WHERE v.statut = 'Livré'
    GROUP BY veh.marque
    ORDER BY nb_ventes DESC
    LIMIT 10
");

// R4 : SAV en cours et terminés
$rapport_sav = safe_query($pdo, "
    SELECT sav.sav_id, sav.date_entree, sav.type_service, sav.statut,
           sav.cout_total, sav.sous_garantie,
           c.nom AS client_nom, c.prenom AS client_prenom,
           veh.marque, veh.modele,
           co.nom_concession
    FROM Services_Apres_Vente sav
    JOIN Clients c ON sav.client_id = c.client_id
    JOIN Vehicules veh ON sav.vehicule_id = veh.vehicule_id
    JOIN Concessions co ON sav.concession_id = co.concession_id
    ORDER BY sav.date_entree DESC
    LIMIT 15
");

// R5 : Performance vendeurs
$rapport_vendeurs = safe_query($pdo, "
    SELECT ve.nom, ve.prenom, ve.role, co.nom_concession,
           COUNT(v.vente_id) AS nb_ventes,
           ROUND(SUM(v.prix_negocie), 0) AS ca_genere,
           ROUND(SUM(v.prix_negocie) * ve.commission_pourcent / 100, 0) AS commission_estimee
    FROM Vendeurs ve
    LEFT JOIN Ventes v ON ve.vendeur_id = v.vendeur_id AND v.statut = 'Livré'
    JOIN Concessions co ON ve.concession_id = co.concession_id
    GROUP BY ve.vendeur_id, ve.nom, ve.prenom, ve.role, co.nom_concession, ve.commission_pourcent
    ORDER BY ca_genere DESC
");
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Rapports - AutoLeman</title><link rel="stylesheet" href="style.css"></head>
<body>
    <header>
        <div class="logo"><div class="logo-icon">AL</div><div><h1>AutoLeman</h1><p>Rapports et tableaux de bord</p></div></div>
        <div class="user-info">
            <span>Connecté : <strong><?= h($_SESSION['db_user']) ?></strong></span>
            <span class="role-badge"><?= h($role) ?></span>
            <a href="logout.php" class="btn-logout">Déconnexion</a>
        </div>
    </header>

    <nav>
        <a href="index.php">Dashboard</a><a href="console.php">Console SQL</a>
        <a href="clients.php">Clients</a><a href="vehicules.php">Véhicules</a>
        <a href="ventes.php">Ventes</a><a href="rapports.php" class="active">Rapports</a>
    </nav>

    <main>
        <div class="card">
            <h2>Chiffre d'affaires par concession</h2>
            <?php if ($rapport_ca === null): ?>
                <div class="alert erreur">Accès refusé.</div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>Concession</th><th>Ville</th><th>Nb ventes</th><th>CA total</th><th>Ticket moyen</th></tr></thead>
                        <tbody>
                            <?php foreach ($rapport_ca as $r): ?>
                                <tr>
                                    <td><strong><?= h($r['nom_concession']) ?></strong></td>
                                    <td><?= h($r['ville']) ?></td>
                                    <td><?= h($r['nb_ventes']) ?></td>
                                    <td style="text-align:right;"><?= number_format($r['ca_total'] ?? 0, 0, '.', "'") ?> CHF</td>
                                    <td style="text-align:right;"><?= number_format($r['ticket_moyen'] ?? 0, 0, '.', "'") ?> CHF</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Répartition du parc par statut</h2>
            <?php if ($rapport_statut === null): ?>
                <div class="alert erreur">Accès refusé.</div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>Statut</th><th>Nb véhicules</th><th>Valeur totale</th></tr></thead>
                        <tbody>
                            <?php foreach ($rapport_statut as $r): ?>
                                <?php $cl = strtolower(str_replace(' ','_',$r['statut'])); ?>
                                <tr>
                                    <td><span class="badge <?= $cl ?>"><?= h($r['statut']) ?></span></td>
                                    <td><?= h($r['nb']) ?></td>
                                    <td style="text-align:right;"><?= number_format($r['valeur_totale'] ?? 0, 0, '.', "'") ?> CHF</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Top marques vendues</h2>
            <?php if ($rapport_marques === null): ?>
                <div class="alert erreur">Accès refusé.</div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>Marque</th><th>Nb ventes</th><th>CA</th><th>Prix moyen</th></tr></thead>
                        <tbody>
                            <?php foreach ($rapport_marques as $r): ?>
                                <tr>
                                    <td><strong><?= h($r['marque']) ?></strong></td>
                                    <td><?= h($r['nb_ventes']) ?></td>
                                    <td style="text-align:right;"><?= number_format($r['ca_marque'], 0, '.', "'") ?> CHF</td>
                                    <td style="text-align:right;"><?= number_format($r['prix_moyen'], 0, '.', "'") ?> CHF</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Services après-vente récents</h2>
            <?php if ($rapport_sav === null): ?>
                <div class="alert erreur">Accès refusé.</div>
            <?php elseif (empty($rapport_sav)): ?>
                <p class="text-muted">Aucun SAV.</p>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>ID</th><th>Date</th><th>Client</th><th>Véhicule</th><th>Concession</th><th>Type</th><th>Statut</th><th>Coût</th><th>Garantie</th></tr></thead>
                        <tbody>
                            <?php foreach ($rapport_sav as $r): ?>
                                <?php $cl = strtolower(str_replace(' ','_',$r['statut'])); ?>
                                <tr>
                                    <td><?= h($r['sav_id']) ?></td>
                                    <td><?= h(date('d/m/Y', strtotime($r['date_entree']))) ?></td>
                                    <td><?= h($r['client_prenom'] . ' ' . $r['client_nom']) ?></td>
                                    <td><?= h($r['marque'] . ' ' . $r['modele']) ?></td>
                                    <td><?= h($r['nom_concession']) ?></td>
                                    <td><?= h($r['type_service']) ?></td>
                                    <td><span class="badge <?= $cl ?>"><?= h($r['statut']) ?></span></td>
                                    <td style="text-align:right;"><?= number_format($r['cout_total'], 0, '.', "'") ?> CHF</td>
                                    <td><?= $r['sous_garantie'] ? '<span class="badge neuf">Oui</span>' : '<span class="text-muted">Non</span>' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Performance par vendeur</h2>
            <?php if ($rapport_vendeurs === null): ?>
                <div class="alert erreur">Accès refusé.</div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>Nom</th><th>Prénom</th><th>Rôle</th><th>Concession</th><th>Ventes</th><th>CA généré</th><th>Commission estimée</th></tr></thead>
                        <tbody>
                            <?php foreach ($rapport_vendeurs as $r): ?>
                                <?php
                                    $rcl = 'vendeur';
                                    if (stripos($r['role'], 'Directeur') !== false) $rcl = 'directeur';
                                    elseif (stripos($r['role'], 'Senior') !== false) $rcl = 'senior';
                                ?>
                                <tr>
                                    <td><strong><?= h($r['nom']) ?></strong></td>
                                    <td><?= h($r['prenom']) ?></td>
                                    <td><span class="badge <?= $rcl ?>"><?= h($r['role']) ?></span></td>
                                    <td><?= h($r['nom_concession']) ?></td>
                                    <td><?= h($r['nb_ventes']) ?></td>
                                    <td style="text-align:right;"><?= number_format($r['ca_genere'] ?? 0, 0, '.', "'") ?> CHF</td>
                                    <td style="text-align:right;"><?= number_format($r['commission_estimee'] ?? 0, 0, '.', "'") ?> CHF</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <footer>AutoLeman - École Schulz</footer>
</body>
</html>
