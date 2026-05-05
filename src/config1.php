<?php
define('DB_HOST', '192.168.11.11');
define('DB_PORT', '8080');
define('DB_NAME', 'Meca');
define('DB_USER', 'root');
define('DB_PASS', 'root');

function getDB() {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8',
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
