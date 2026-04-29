<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'eleve') {
    header('Location: login.php');
    exit;
}
?>

<h1>Accueil élève</h1>
<p>Bienvenue <?= htmlspecialchars($_SESSION['name']) ?></p>
<a href="logout.php">Déconnexion</a>
