one,
   ':adresse' => $adresse_post
;
<?php
// save_or.php

// ⚙️ Connexion BDD
$host = "localhost";
$db   = "Meca";
$user = "root";
$pass = "root"; // adapte si besoin

try {
    $pdo = new PDO(
        "mysql:host=192.168.11.11;dbname=Meca;charset=utf8mb4",
        "root",
        "root",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// 1) Client
$sql = "INSERT INTO Clients (prenom, nom, adresse_mail, mots_de_passe, numéro, adresse_postal)
        VALUES (?, ?, ?, '', ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    $_POST['client_prenom'] ?? '',
    $_POST['client_nom'] ?? '',
    $_POST['client_email'] ?? '',
    $_POST['client_tel'] ?? '',
    $_POST['client_adresse'] ?? ''
]);

$client_id = $pdo->lastInsertId();

// 2) Véhicule
$sql = "INSERT INTO Vehicules (vin, marque, modele, client_id)
        VALUES (?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    $_POST['vin'] ?? '',
    $_POST['marque'] ?? '',
    $_POST['modele'] ?? '',
    $client_id
]);

$vehicule_id = $pdo->lastInsertId();

// 3) Intervention
$sql = "INSERT INTO intervention (vehicule_id, prestation_id, date_intervention, commentaire)
        VALUES (?, 1, NOW(), ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    $vehicule_id,
    $_POST['info_client'] ?? ''
]);

echo "<h2>✔ Ordre de réparation enregistré !</h2>";

if ($_SESSION['role'] === 'prof') {
    echo "<a href='prof.php'>Retour à l'accueil professeur</a>";
} elseif ($_SESSION['role'] === 'eleve') {
    echo "<a href='eleve.php'>Retour à l'accueil élève</a>";
}

