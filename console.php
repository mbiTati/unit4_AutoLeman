<?php
require_once 'connexion.php';
exiger_authentification();

$pdo = get_pdo();
$role = get_role_utilisateur();

if (!isset($_SESSION['sql_historique'])) {
    $_SESSION['sql_historique'] = [];
}

$requete = '';
$resultats = null;
$colonnes = [];
$nb_lignes = 0;
$temps_execution = 0;
$message = '';
$type_message = '';
$lignes_affectees = 0;

$mots_interdits = ['DROP DATABASE', 'DROP SCHEMA', 'CREATE USER', 'DROP USER', 'GRANT ', 'REVOKE ', 'SHUTDOWN'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'effacer_historique') {
        $_SESSION['sql_historique'] = [];
        header('Location: console.php');
        exit;
    }

    $requete = trim($_POST['requete'] ?? '');

    if (empty($requete)) {
        $message = 'Veuillez saisir une requête SQL.';
        $type_message = 'erreur';
    } elseif (strlen($requete) > 5000) {
        $message = 'Requête trop longue (max 5000 caractères).';
        $type_message = 'erreur';
    } else {
        $requete_upper = strtoupper($requete);
        $interdit_trouve = null;
        foreach ($mots_interdits as $mot) {
            if (strpos($requete_upper, $mot) !== false) {
                $interdit_trouve = $mot;
                break;
            }
        }

        if ($interdit_trouve) {
            $message = "Requête bloquée par l'application : '$interdit_trouve' n'est pas autorisé.";
            $type_message = 'erreur';
        } else {
            try {
                $debut = microtime(true);
                $stmt = $pdo->query($requete);
                $temps_execution = round((microtime(true) - $debut) * 1000, 2);
                $premier_mot = strtoupper(strtok(trim($requete), " \t\n"));

                if (in_array($premier_mot, ['SELECT', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN'])) {
                    $resultats = $stmt->fetchAll();
                    $nb_lignes = count($resultats);
                    if ($nb_lignes > 0) $colonnes = array_keys($resultats[0]);
                    $limite_affichage = 200;
                    if ($nb_lignes > $limite_affichage) {
                        $resultats = array_slice($resultats, 0, $limite_affichage);
                        $message = "Requête exécutée en {$temps_execution} ms. {$nb_lignes} lignes retournées (affichage limité à {$limite_affichage}).";
                        $type_message = 'warning';
                    } else {
                        $message = "Requête exécutée en {$temps_execution} ms. {$nb_lignes} ligne(s) retournée(s).";
                        $type_message = 'succes';
                    }
                } else {
                    $lignes_affectees = $stmt->rowCount();
                    $message = "Requête exécutée en {$temps_execution} ms. {$lignes_affectees} ligne(s) affectée(s).";
                    $type_message = 'succes';
                }

                array_unshift($_SESSION['sql_historique'], [
                    'requete' => $requete, 'date' => date('H:i:s'),
                    'statut' => 'OK', 'temps' => $temps_execution
                ]);
                if (count($_SESSION['sql_historique']) > 20) {
                    $_SESSION['sql_historique'] = array_slice($_SESSION['sql_historique'], 0, 20);
                }
            } catch (PDOException $e) {
                $message = 'Erreur MySQL : ' . $e->getMessage();
                $type_message = 'erreur';
                array_unshift($_SESSION['sql_historique'], [
                    'requete' => $requete, 'date' => date('H:i:s'),
                    'statut' => 'ERREUR', 'temps' => 0
                ]);
                if (count($_SESSION['sql_historique']) > 20) {
                    $_SESSION['sql_historique'] = array_slice($_SESSION['sql_historique'], 0, 20);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Console SQL - AutoLeman</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .sql-editor {
            width: 100%; min-height: 180px; padding: 15px;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            font-size: 14px; line-height: 1.5;
            color: #e6edf3; background: #1e2936;
            border: 1px solid #2d3a4d; border-radius: 8px;
            resize: vertical; tab-size: 4;
        }
        .sql-editor:focus { outline: none; border-color: #f39c12; box-shadow: 0 0 0 3px rgba(243, 156, 18, 0.15); }
        .console-toolbar { display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap; align-items: center; }
        .btn-execute { background: #27ae60; font-weight: 600; padding: 12px 24px; font-size: 15px; }
        .btn-execute:hover { background: #229954; }
        .keyboard-hint { color: #7f8c8d; font-size: 12px; margin-left: auto; }
        kbd { background: #f4f6f9; border: 1px solid #d0d7de; border-radius: 3px; padding: 1px 6px; font-family: 'Consolas', monospace; font-size: 11px; }
        .historique-list { display: flex; flex-direction: column; gap: 5px; max-height: 400px; overflow-y: auto; }
        .historique-item { padding: 10px 14px; background: #f8f9fa; border-radius: 4px; font-size: 13px; cursor: pointer; border-left: 3px solid #27ae60; }
        .historique-item:hover { background: #eef5fb; }
        .historique-item.erreur { border-left-color: #e74c3c; }
        .historique-item .meta { display: flex; justify-content: space-between; color: #7f8c8d; font-size: 11px; margin-bottom: 4px; }
        .historique-item code { font-family: 'Consolas', monospace; color: #2c3e50; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .resultats-meta { display: flex; gap: 20px; padding: 10px 15px; background: #f4f6f9; border-radius: 6px; margin-bottom: 15px; font-size: 13px; }
        .resultats-meta strong { color: #1e3a5f; }
        .table-resultats th { background: #1e3a5f; color: white; font-weight: 600; }
        .table-resultats td { font-size: 13px; max-width: 350px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .table-resultats td.null-value { color: #adb5bd; font-style: italic; }
        .erreur-mysql { background: #fee; border: 1px solid #f5c6cb; border-left: 4px solid #e74c3c; padding: 15px; border-radius: 6px; font-family: 'Consolas', monospace; font-size: 13px; color: #721c24; white-space: pre-wrap; line-height: 1.5; }
        .erreur-mysql strong { display: block; margin-bottom: 8px; font-family: 'Segoe UI', sans-serif; color: #c0392b; }
        .info-box { background: #eef5fb; border-left: 3px solid #3498db; padding: 12px 15px; border-radius: 4px; font-size: 13px; color: #2c3e50; margin-bottom: 20px; }
        .info-box strong { color: #1e3a5f; }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <div class="logo-icon">AL</div>
            <div><h1>AutoLeman</h1><p>Console SQL</p></div>
        </div>
        <div class="user-info">
            <span>Connecté : <strong><?= h($_SESSION['db_user']) ?></strong></span>
            <span class="role-badge"><?= h($role) ?></span>
            <a href="logout.php" class="btn-logout">Déconnexion</a>
        </div>
    </header>

    <nav>
        <a href="index.php">Dashboard</a>
        <a href="console.php" class="active">Console SQL</a>
        <a href="clients.php">Clients</a>
        <a href="vehicules.php">Véhicules</a>
        <a href="ventes.php">Ventes</a>
        <a href="rapports.php">Rapports</a>
    </nav>

    <main>
        <div class="info-box">
            <strong>Console SQL connectée à la base <code>autoleman</code>.</strong><br>
            Vos requêtes s'exécutent avec les permissions de votre compte MySQL <code><?= h($_SESSION['db_user']) ?></code>.
            Si une opération vous est refusée, c'est MySQL qui bloque selon vos droits GRANT.
        </div>

        <div class="card">
            <h2>Éditeur de requêtes SQL</h2>
            <form method="POST" id="sql-form">
                <textarea name="requete" class="sql-editor" id="sql-editor"
                    placeholder="-- Saisissez votre requête SQL ici&#10;-- Exemple : SELECT * FROM Vehicules LIMIT 10;"
                    spellcheck="false" autofocus><?= h($requete) ?></textarea>
                <div class="console-toolbar">
                    <button type="submit" class="btn-execute">▶ Exécuter</button>
                    <button type="button" class="btn btn-warning" onclick="document.getElementById('sql-editor').value=''; document.getElementById('sql-editor').focus();">Effacer</button>
                    <span class="keyboard-hint">Astuce : <kbd>Ctrl</kbd>+<kbd>Entrée</kbd> pour exécuter</span>
                </div>
            </form>
        </div>

        <?php if ($message): ?>
            <div class="card">
                <?php if ($type_message === 'erreur'): ?>
                    <div class="erreur-mysql"><strong>✗ Erreur d'exécution</strong><?= h($message) ?></div>
                <?php else: ?>
                    <div class="alert <?= h($type_message) ?>"><?= h($message) ?></div>
                <?php endif; ?>

                <?php if ($resultats !== null && $nb_lignes > 0): ?>
                    <div class="resultats-meta">
                        <span><strong>Lignes :</strong> <?= $nb_lignes ?></span>
                        <span><strong>Colonnes :</strong> <?= count($colonnes) ?></span>
                        <span><strong>Temps :</strong> <?= $temps_execution ?> ms</span>
                    </div>
                    <div class="table-wrapper">
                        <table class="table-resultats">
                            <thead><tr><?php foreach ($colonnes as $col): ?><th><?= h($col) ?></th><?php endforeach; ?></tr></thead>
                            <tbody>
                                <?php foreach ($resultats as $ligne): ?>
                                    <tr>
                                        <?php foreach ($colonnes as $col): ?>
                                            <?php $val = $ligne[$col]; ?>
                                            <td<?= ($val === null) ? ' class="null-value"' : '' ?> title="<?= h((string)$val) ?>">
                                                <?= ($val === null) ? 'NULL' : h((string)$val) ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif ($resultats !== null && $nb_lignes === 0): ?>
                    <div class="alert info">La requête s'est exécutée correctement mais n'a retourné aucun résultat.</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3>Historique
                <?php if (!empty($_SESSION['sql_historique'])): ?>
                    <form method="POST" style="display:inline; float:right;">
                        <input type="hidden" name="action" value="effacer_historique">
                        <button type="submit" class="btn btn-warning" style="padding:4px 10px; font-size:11px;">Effacer</button>
                    </form>
                <?php endif; ?>
            </h3>
            <?php if (empty($_SESSION['sql_historique'])): ?>
                <p class="text-muted" style="font-size:13px;">Aucune requête exécutée pour l'instant.</p>
            <?php else: ?>
                <div class="historique-list">
                    <?php foreach ($_SESSION['sql_historique'] as $idx => $h_item): ?>
                        <div class="historique-item <?= $h_item['statut'] === 'ERREUR' ? 'erreur' : '' ?>"
                             onclick="rejouer(<?= $idx ?>)" title="Cliquer pour réutiliser cette requête">
                            <div class="meta">
                                <span><?= h($h_item['date']) ?></span>
                                <span><?= h($h_item['statut']) ?> · <?= $h_item['temps'] ?>ms</span>
                            </div>
                            <code><?= h(substr($h_item['requete'], 0, 120)) ?><?= strlen($h_item['requete']) > 120 ? '...' : '' ?></code>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card mt-20">
            <h3>Conseils d'utilisation</h3>
            <ul style="line-height:1.8; padding-left:20px; color:#495057; font-size:14px;">
                <li>Vous pouvez exécuter des requêtes <strong>SELECT, INSERT, UPDATE, DELETE</strong> selon vos droits.</li>
                <li>Les requêtes <strong>DROP DATABASE, CREATE USER, GRANT, REVOKE</strong> sont bloquées par l'application.</li>
                <li>L'affichage est limité à <strong>200 lignes</strong>. Utilisez <code>LIMIT</code> pour cibler vos résultats.</li>
                <li>Si vous voyez "command denied", c'est que votre compte n'a pas les droits MySQL nécessaires.</li>
            </ul>
        </div>
    </main>

    <footer>AutoLeman Console SQL - École Schulz - BTEC LO2/LO3</footer>

    <script>
        const HISTORIQUE = <?= json_encode(array_map(fn($i) => $i['requete'], $_SESSION['sql_historique'])) ?>;
        function rejouer(idx) {
            if (HISTORIQUE[idx]) {
                document.getElementById('sql-editor').value = HISTORIQUE[idx];
                document.getElementById('sql-editor').focus();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }
        document.getElementById('sql-editor').addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('sql-form').submit();
            }
            if (e.key === 'Tab') {
                e.preventDefault();
                const start = this.selectionStart;
                const end = this.selectionEnd;
                this.value = this.value.substring(0, start) + '    ' + this.value.substring(end);
                this.selectionStart = this.selectionEnd = start + 4;
            }
        });
    </script>
</body>
</html>
