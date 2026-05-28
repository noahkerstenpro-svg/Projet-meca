<?php
// recherche_vin.php — Endpoint AJAX : retourne les véhicules dont le VIN commence par la saisie

header('Content-Type: application/json; charset=utf-8');

$host   = 'meca-mysql';
$dbname = 'Meca';
$user   = 'root';
$pass   = 'root';

$vin = trim($_GET['vin'] ?? '');

if (strlen($vin) < 3) {
    echo json_encode([]);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;port=3306;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("
        SELECT
            v.id_vehicules,
            v.vin,
            v.`marque/modèle`   AS marque_modele,
            v.immatriculation,
            v.km,
            v.mise_circulation,
            v.type_veh,
            c.id_clients,
            c.prenom            AS client_prenom,
            c.nom               AS client_nom,
            c.adresse_postal    AS client_adresse,
            c.`numéro`          AS client_tel,
            c.adresse_mail      AS client_email
        FROM Vehicules v
        LEFT JOIN Clients c ON c.id_clients = v.client_id
        WHERE v.vin LIKE :vin
        LIMIT 8
    ");
    $stmt->execute([':vin' => $vin . '%']);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);

} catch (PDOException $e) {
    echo json_encode([]);
}
