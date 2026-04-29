<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'prof') {
    header('Location: login.php');
    exit;
}
?>

<h1>Accueil professeur</h1>
<p>Bienvenue <?= htmlspecialchars($_SESSION['name']) ?></p>
<a href="logout.php">Déconnexion</a>
