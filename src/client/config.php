<?php
define('DB_HOST', 'localhost'); // Si PHP est sur le même serveur que la BDD
define('DB_NAME', 'Meca');
define('DB_USER', 'root');
define('DB_PASS', 'root'); // ou '' selon votre config

function getDB() {
    try {
        // Correction de la chaîne de connexion :
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8';
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        // On renvoie une erreur JSON propre au lieu de juste "die"
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()]);
        exit;
    }
}
?>