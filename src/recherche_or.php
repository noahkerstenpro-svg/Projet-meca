<?php
// recherche_or.php

$host = "localhost";
$db   = "Meca";
$user = "root";
$pass = "root"; // adapte si besoin

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$q = $_GET['q'] ?? '';
$resultats = [];

if ($q !== '') {
    $like = "%$q%";
    $sql = "
        SELECT 
            i.id_intervention,
            i.date_intervention,
            i.commentaire,
            c.nom,
            c.prenom,
            c.adresse_mail,
            c.numéro,
            v.marque,
            v.modele,
            v.vin
        FROM intervention i
        JOIN Vehicules v ON i.vehicule_id = v.id_vehicules
        JOIN Clients   c ON v.client_id   = c.id_clients
        WHERE c.nom LIKE :q
           OR c.prenom LIKE :q
           OR v.vin LIKE :q
           OR v.marque LIKE :q
           OR v.modele LIKE :q
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':q' => $like]);
    $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Recherche OR</title>
<style>
  body{font-family:Arial, sans-serif; padding:20px;}
  input[type=text]{padding:6px 8px; width:260px;}
  button{padding:6px 10px; cursor:pointer;}
  table{border-collapse:collapse; width:100%; margin-top:20px;}
  th,td{border:1px solid #ccc; padding:6px 8px; font-size:13px;}
  th{background:#f0f0f5;}
</style>
</head>
<body>

<h2>🔍 Recherche d’un ordre de réparation</h2>

<form method="get">
  <input type="text" name="q" placeholder="Nom, prénom, VIN, marque, modèle..." value="<?= htmlspecialchars($q) ?>" required>
  <button type="submit">Rechercher</button>
  <a href="ordre.php" style="margin-left:10px;">↩ Nouveau OR</a>
</form>

<?php if ($q !== ''): ?>
  <h3>Résultats pour « <?= htmlspecialchars($q) ?> »</h3>

  <?php if (count($resultats) === 0): ?>
    <p>Aucun résultat trouvé.</p>
  <?php else: ?>
    <table>
      <tr>
        <th>ID OR</th>
        <th>Date</th>
        <th>Client</th>
        <th>Contact</th>
        <th>Véhicule</th>
        <th>VIN</th>
        <th>Commentaire</th>
      </tr>
      <?php foreach ($resultats as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['id_intervention']) ?></td>
          <td><?= htmlspecialchars($r['date_intervention']) ?></td>
          <td><?= htmlspecialchars($r['prenom'] . " " . $r['nom']) ?></td>
          <td><?= htmlspecialchars($r['numéro'] . " / " . $r['adresse_mail']) ?></td>
          <td><?= htmlspecialchars($r['marque'] . " " . $r['modele']) ?></td>
          <td><?= htmlspecialchars($r['vin']) ?></td>
          <td><?= nl2br(htmlspecialchars($r['commentaire'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
<?php endif; ?>

</body>
</html>
