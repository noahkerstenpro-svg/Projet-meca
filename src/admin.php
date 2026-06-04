<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$name = $_SESSION['name'] ?? $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Administration - Atelier mécanique</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family: Arial, sans-serif;
    min-height:100vh;
    background: linear-gradient(135deg,#0f172a 0%, #1e293b 50%, #2563eb 100%);
    color:white;
}

header{
    padding:25px 40px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.title{
    font-size:30px;
    font-weight:bold;
}

.user-box{
    background:rgba(255,255,255,0.12);
    padding:12px 18px;
    border-radius:14px;
    backdrop-filter: blur(8px);
}

main{
    display:flex;
    justify-content:center;
    align-items:center;
    padding:30px;
}

.dashboard{
    width:100%;
    max-width:1200px;

    background:rgba(255,255,255,0.10);
    border:1px solid rgba(255,255,255,0.1);

    backdrop-filter: blur(10px);

    border-radius:25px;
    padding:40px;

    box-shadow:0 15px 40px rgba(0,0,0,0.3);
}

.welcome{
    margin-bottom:40px;
}

.welcome h1{
    font-size:38px;
    margin-bottom:10px;
}

.welcome p{
    color:rgba(255,255,255,0.75);
}

.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
    gap:25px;
}

.card{
    background:white;
    color:#111827;
    border-radius:20px;
    padding:30px;
    text-decoration:none;
    transition:.25s;
    box-shadow:0 10px 25px rgba(0,0,0,.15);
}

.card:hover{
    transform:translateY(-8px);
    box-shadow:0 18px 40px rgba(0,0,0,.2);
}

.icon{
    font-size:50px;
    margin-bottom:15px;
}

.card h3{
    margin-bottom:10px;
}

.card p{
    color:#6b7280;
    line-height:1.5;
}

.logout{
    background:#dc2626;
    color:white;
}

.logout p{
    color:rgba(255,255,255,.85);
}

.footer{
    text-align:center;
    margin-top:25px;
    color:rgba(255,255,255,.7);
}

</style>
</head>

<body>

<header>
    <div class="title">
        ⚙️ Administration
    </div>

    <div class="user-box">
        👤 <?= htmlspecialchars($name) ?>
    </div>
</header>

<main>

<div class="dashboard">

    <div class="welcome">
        <h1>Bienvenue <?= htmlspecialchars($name) ?></h1>

        <p>
            Espace administration du système de gestion atelier.
        </p>
    </div>

    <div class="cards">

        <a class="card" href="factures.php">
            <div class="icon">💰</div>
            <h3>Factures</h3>
            <p>
                Créer, consulter et imprimer les factures clients.
            </p>
        </a>

        <a class="card" href="clients.php">
            <div class="icon">👥</div>
            <h3>Clients</h3>
            <p>
                Consulter et gérer les informations clients.
            </p>
        </a>

        <a class="card" href="vehicules.php">
            <div class="icon">🚗</div>
            <h3>Véhicules</h3>
            <p>
                Accéder à l'historique des véhicules.
            </p>
        </a>

        <a class="card" href="recherche_or.php">
            <div class="icon">📋</div>
            <h3>Ordres de réparation</h3>
            <p>
                Rechercher et consulter tous les OR.
            </p>
        </a>

        <a class="card" href="statistiques.php">
            <div class="icon">📊</div>
            <h3>Statistiques</h3>
            <p>
                Visualiser les indicateurs de l'atelier.
            </p>
        </a>

        <a class="card" href="sauvegardes.php">
            <div class="icon">💾</div>
            <h3>Sauvegardes</h3>
            <p>
                Gérer les sauvegardes de la base de données.
            </p>
        </a>

        <a class="card logout" href="logout.php">
            <div class="icon">🚪</div>
            <h3>Déconnexion</h3>
            <p>
                Fermer votre session administrateur.
            </p>
        </a>

    </div>

    <div class="footer">
        Projet Atelier mécanique - Administration
    </div>

</div>

</main>

</body>
</html>
