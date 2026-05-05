<?php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'Meca');
define('DB_USER', 'root');
define('DB_PASS', 'root');

function getDB() {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . ;dbname=' . DB_NAME . ';charset=utf8',
            DB_USER,
            DB_PASS
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die(json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données.']));
    }
}
?>
