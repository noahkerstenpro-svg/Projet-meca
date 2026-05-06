<?php
// recherche_or.php

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

$q = $_GET['q'] ?? '';
$resultats = [];

if ($q !== '') {
    $like = "%$q%";

    $sql = "
        SELECT c.nom, c.prenom, v.marque, v.modele, v.vin, i.date_intervention, i.commentaire
        FROM intervention i
        JOIN Vehicules v ON i.vehicule_id = v.id_vehicules
        JOIN Clients c ON v.client_id = c.id_clients
        WHERE c.nom LIKE ? OR c.prenom LIKE ? OR v.vin LIKE ? OR v.marque LIKE ? OR v.modele LIKE ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$like, $like, $like, $like, $like]);
    $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Recherche OR</title>
</head>
<body>

<h2>Recherche d’un OR</h2>

<form method="GET">
    <input type="text" name="q" placeholder="Nom, VIN, modèle..." required>
    <button type="submit">Rechercher</button>
</form>

<?php if ($q !== ''): ?>
<table border="1" cellpadding="6">
<tr>
  <th>Client</th>
  <th>Véhicule</th>
  <th>VIN</th>
  <th>Date</th>
  <th>Commentaire</th>
</tr>

<?php foreach ($resultats as $r): ?>
<tr>
  <td><?= $r['prenom'] . " " . $r['nom'] ?></td>
  <td><?= $r['marque'] . " " . $r['modele'] ?></td>
  <td><?= $r['vin'] ?></td>
  <td><?= $r['date_intervention'] ?></td>
  <td><?= nl2br($r['commentaire']) ?></td>
</tr>
<?php endforeach; ?>

</table>
<?php endif; ?>

</body>
</html>
