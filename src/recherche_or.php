<?php
$pdo = new PDO(
    "mysql:host=mysql;dbname=garage;charset=utf8",
    "root",
    "root"
);

// récupérer tous les OR
$result = $pdo->query("SELECT * FROM ordre_reparation");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Recherche OR</title>
</head>
<body>

<h2>Liste des ordres de réparation</h2>

<table border="1">
<tr>
    <th>ID</th>
    <th>Client</th>
    <th>Immatriculation</th>
    <th>Date</th>
    <th>Action</th>
</tr>

<?php foreach ($result as $row): ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= $row['client_nom'] ?></td>
    <td><?= $row['immat'] ?></td>
    <td><?= $row['date_reception'] ?></td>
    <td>
        <a href="detail_or.php?id=<?= $row['id'] ?>">Voir</a>
    </td>
</tr>
<?php endforeach; ?>

</table>

</body>
</html>
