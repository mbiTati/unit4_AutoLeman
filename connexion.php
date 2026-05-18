<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'autoleman');
define('DB_CHARSET', 'utf8mb4');

function tenter_connexion_mysql($username, $password) {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            $username, $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        return false;
    }
}

function get_pdo() {
    if (!isset($_SESSION['db_user']) || !isset($_SESSION['db_pass'])) {
        header('Location: login.php?msg=session_expiree');
        exit;
    }
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            $_SESSION['db_user'], $_SESSION['db_pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        session_destroy();
        header('Location: login.php?msg=erreur_connexion');
        exit;
    }
}

function exiger_authentification() {
    if (!isset($_SESSION['db_user'])) {
        header('Location: login.php');
        exit;
    }
}

function get_role_utilisateur() {
    if (!isset($_SESSION['db_user'])) return 'inconnu';
    $user = strtolower($_SESSION['db_user']);
    if (strpos($user, 'directeur') !== false || $user === 'root') return 'directeur_commercial';
    if (strpos($user, 'senior') !== false) return 'vendeur_senior';
    if (strpos($user, 'junior') !== false) return 'vendeur_junior';
    return 'utilisateur';
}

function h($texte) {
    return htmlspecialchars($texte ?? '', ENT_QUOTES, 'UTF-8');
}
