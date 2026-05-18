<?php
// Retourne en JSON les créneaux déjà pris pour une date donnée

$host   = 'meca-mysql';
$port   = '3306';
$dbname = 'Meca';
$user   = 'root';
$pass   = 'root';

header('Content-Type: application/json');

$date = $_GET['date'] ?? '';

// Valider le format de la date
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode([]);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("
        SELECT heure_de_préstation
        FROM intervention
        WHERE date_intervention = :date
    ");
    $stmt->execute([':date' => $date]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN); // ["09:00", "14:00", ...]

    echo json_encode($rows);

} catch (PDOException $e) {
    echo json_encode([]);
}
