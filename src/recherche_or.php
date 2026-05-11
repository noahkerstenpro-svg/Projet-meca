<?php
// recherche_or.php

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
        SELECT 
            i.id_intervention,
            i.date_intervention,
            i.commentaire,
            c.nom, c.prenom, c.numéro, c.adresse_mail,
            v.marque, v.modele, v.vin
        FROM intervention i
        JOIN Vehicules v ON i.vehicule_id = v.id_vehicules
        JOIN Clients c ON v.client_id = c.id_clients
        WHERE c.nom LIKE ? 
        OR c.prenom LIKE ? 
        OR v.vin LIKE ? 
        OR v.marque LIKE ? 
        OR v.modele LIKE ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$like, $like, $like, $like, $like]);
    $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Recherche OR</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f4f8;
    padding: 20px;
}
h2 {
    color: #2c2c6e;
}
input[type=text] {
    padding: 8px 10px;
    width: 300px;
    border: 1px solid #bbb;
    border-radius: 4px;
}
button {
    padding: 8px 14px;
    background: #2c2c6e;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}
button:hover {
    background: #1a1a5e;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 25px;
    background: white;
    border-radius: 6px;
    overflow: hidden;
}
th {
    background: #2c2c6e;
    color: white;
    padding: 10px;
    text-align: left;
}
td {
    padding: 10px;
    border-bottom: 1px solid #eee;
}
.action-btn {
    padding: 6px 10px;
    background: #4a4a9a;
    color: white;
    border-radius: 4px;
    text-decoration: none;
}
.action-btn:hover {
    background: #2c2c6e;
}
</style>
</head>
<body>

<h2>🔍 Recherche d’un ordre de réparation</h2>

<form method="GET">
    <input type="text" name="q" placeholder="Nom, VIN, modèle..." value="<?= htmlspecialchars($q) ?>" required>
    <button type="submit">Rechercher</button>
</form>

<?php if ($q !== ''): ?>
<h3>Résultats pour « <?= htmlspecialchars($q) ?> »</h3>

<?php if (count($resultats) === 0): ?>
<p>Aucun résultat trouvé.</p>

<?php else: ?>
<table>
<tr>
    <th>ID OR</th>
    <th>Client</th>
    <th>Véhicule</th>
    <th>VIN</th>
    <th>Date</th>
    <th>PDF</th>
</tr>

<?php foreach ($resultats as $r): ?>
<tr>
    <td><?= $r['id_intervention'] ?></td>
    <td><?= $r['prenom'] . " " . $r['nom'] ?></td>
    <td><?= $r['marque'] . " " . $r['modele'] ?></td>
    <td><?= $r['vin'] ?></td>
    <td><?= $r['date_intervention'] ?></td>
    <td>
        <a class="action-btn" href="pdf_or.php?id=<?= $r['id_intervention'] ?>" target="_blank">📄 Voir PDF</a>
    </td>
</tr>
<?php endforeach; ?>

</table>
<?php endif; ?>
<?php endif; ?>

</body>
</html>
