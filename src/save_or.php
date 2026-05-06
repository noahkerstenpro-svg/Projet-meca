<?php
// save_or.php

// ⚙️ Connexion BDD
$host = "localhost";
$db   = "Meca";
$user = "root";
$pass = ""; // adapte si besoin

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

/*
  Tables :
  - Clients(id_clients, prenom, nom, adresse_mail, mots_de_passe, numéro, adresse_postal)
  - Vehicules(id_vehicules, vin, marque, modele, client_id)
  - intervention(id_intervention, vehicule_id, prestation_id, date_intervention, commentaire)
*/

// 1) Client
$prenom        = $_POST['client_prenom'] ?? ($_POST['client_nom'] ?? '');
$nom           = $_POST['client_nom'] ?? '';
$adresse_mail  = $_POST['client_email'] ?? '';
$telephone     = $_POST['client_tel'] ?? '';
$adresse_post  = $_POST['client_adresse'] ?? '';

$sqlClient = "INSERT INTO Clients (prenom, nom, adresse_mail, mots_de_passe, numéro, adresse_postal)
              VALUES (:prenom, :nom, :mail, :mdp, :tel, :adresse)";
$stmt = $pdo->prepare($sqlClient);
$stmt->execute([
    ':prenom'  => $prenom,
    ':nom'     => $nom,
    ':mail'    => $adresse_mail,
    ':mdp'     => '',          // pas de mot de passe ici
    ':tel'     => $telephone,
    ':adresse' => $adresse_post
]);
$client_id = $pdo->lastInsertId();

// 2) Véhicule
$vin    = $_POST['vin']    ?? '';
$marque = $_POST['marque'] ?? '';
$modele = $_POST['modele'] ?? '';

$sqlVeh = "INSERT INTO Vehicules (vin, marque, modele, client_id)
           VALUES (:vin, :marque, :modele, :client_id)";
$stmt = $pdo->prepare($sqlVeh);
$stmt->execute([
    ':vin'       => $vin,
    ':marque'    => $marque,
    ':modele'    => $modele,
    ':client_id' => $client_id
]);
$vehicule_id = $pdo->lastInsertId();

// 3) Intervention
$commentaire = $_POST['info_client'] ?? ''; // texte principal de demande
$date_inter  = $_POST['date_reception'] ?: date('Y-m-d');

$sqlInt = "INSERT INTO intervention (vehicule_id, prestation_id, date_intervention, commentaire)
           VALUES (:vehicule_id, :prestation_id, :date_intervention, :commentaire)";
$stmt = $pdo->prepare($sqlInt);
$stmt->execute([
    ':vehicule_id'      => $vehicule_id,
    ':prestation_id'    => 1, // tu pourras plus tard lier à ta table Prestation
    ':date_intervention'=> $date_inter,
    ':commentaire'      => $commentaire
]);

// 4) Confirmation simple
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>OR enregistré</title>
<style>
  body{font-family:Arial, sans-serif; padding:20px;}
  a{color:#2c2c6e; text-decoration:none;}
  a:hover{text-decoration:underline;}
</style>
</head>
<body>
  <h2>✅ Ordre de réparation enregistré</h2>
  <p>Client : <strong><?= htmlspecialchars($prenom . " " . $nom) ?></strong></p>
  <p>Véhicule : <strong><?= htmlspecialchars($marque . " " . $modele) ?></strong> (VIN : <?= htmlspecialchars($vin) ?>)</p>
  <p><a href="ordre.php">↩ Retour au formulaire</a> | <a href="recherche_or.php">🔍 Rechercher un OR</a></p>
</body>
</html>

