<?php
$pdo = new PDO("mysql:host=localhost;dbname=Meca;charset=utf8", "root", "");

// ================= CLIENT =================
$prenom = $_POST['client_prenom'] ?? '';
$nom = $_POST['client_nom'] ?? '';
$adresse = $_POST['client_adresse'] ?? '';
$mdp = null;

$sqlClient = "INSERT INTO Clients (prenom, nom, adresse, mdp)
VALUES (?, ?, ?, ?)";

$stmt = $pdo->prepare($sqlClient);
$stmt->execute([$prenom, $nom, $adresse, $mdp]);

$id_client = $pdo->lastInsertId();


// ================= VEHICULE =================
$vin = $_POST['vin'] ?? '';
$marque = $_POST['marque'] ?? '';
$modele = $_POST['modele'] ?? '';

$sqlVehicule = "INSERT INTO Vehicules (vin, marque, modele, client_id)
VALUES (?, ?, ?, ?)";

$stmt = $pdo->prepare($sqlVehicule);
$stmt->execute([$vin, $marque, $modele, $id_client]);

$id_vehicule = $pdo->lastInsertId();


// ================= INTERVENTION =================
$date = $_POST['date_reception'] ?? null;
$commentaire = $_POST['info_client'] ?? '';

// ⚠️ IMPORTANT : prestation_id obligatoire
$prestation_id = 1;

$sqlIntervention = "INSERT INTO intervention 
(vehicule_id, prestation_id, date_intervention, commentaire)
VALUES (?, ?, ?, ?)";

$stmt = $pdo->prepare($sqlIntervention);
$stmt->execute([$id_vehicule, $prestation_id, $date, $commentaire]);

echo "✅ Ordre enregistré avec succès !";
?>
