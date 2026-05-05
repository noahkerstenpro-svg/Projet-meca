<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard AD</title>
</head>
<body>
    <h2>Bienvenue <?php echo htmlspecialchars($_SESSION["user"]); ?></h2>
    <h3>Groupes AD :</h3>
    <ul>
        <?php
        if (!empty($_SESSION["groups"])) {
            foreach ($_SESSION["groups"] as $g) {
                echo "<li>" . htmlspecialchars($g) . "</li>";
            }
        } else {
            echo "<li>Aucun groupe trouvé</li>";
        }
        ?>
    </ul>

    <h3>Applications :</h3>
    <p>Pas d'accès à GLPI</p>

    <a href="logout.php">Se déconnecter</a>
</body>
</html>