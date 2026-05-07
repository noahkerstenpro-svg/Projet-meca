<?php
session_start();

// save_or.php

// ⚙️ Connexion BDD
try {
    $pdo = new PDO(
        "mysql:host=mysql;dbname=Meca;charset=utf8mb4",
        "root",
        "root",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// 1) Client
$sql = "INSERT INTO Clients (
            prenom,
            nom,
            adresse_mail,
            mots_de_passe,
            numéro,
            adresse_postal
        )
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
$sql = "INSERT INTO Vehicules (
            vin,
            marque,
            modele,
            client_id
        )
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
$sql = "INSERT INTO intervention (
            vehicule_id,
            prestation_id,
            date_intervention,
            commentaire
        )
        VALUES (?, 1, NOW(), ?)";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $vehicule_id,
    $_POST['info_client'] ?? ''
]);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ordre enregistré</title>

    <style>
        body {
            font-family: Arial;
            background: #f3f4f6;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .box {
            background: white;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        h2 {
            color: #16a34a;
        }

        a {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 20px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 8px;
        }

        a:hover {
            background: #1d4ed8;
        }
    </style>
</head>
<body>

<div class="box">

    <h2>✔ Ordre de réparation enregistré !</h2>

    <?php if ($_SESSION['role'] === 'prof'): ?>

        <a href="prof.php">
            Retour à l'accueil professeur
        </a>

    <?php elseif ($_SESSION['role'] === 'eleve'): ?>

        <a href="eleve.php">
            Retour à l'accueil élève
        </a>

    <?php endif; ?>

</div>

</body>
</html>
