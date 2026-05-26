<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'eleve') {
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
    <title>Espace Élève</title>

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{
            font-family: Arial, sans-serif;
            min-height:100vh;
            background:
                linear-gradient(135deg,#2563eb 0%, #1e3a8a 40%, #111827 100%);
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
            border:1px solid rgba(255,255,255,0.1);
        }

        main{
            display:flex;
            justify-content:center;
            align-items:center;
            padding:30px;
        }

        .dashboard{
            width:100%;
            max-width:1100px;

            background:rgba(255,255,255,0.12);
            border:1px solid rgba(255,255,255,0.1);

            backdrop-filter: blur(12px);

            border-radius:25px;
            padding:40px;

            box-shadow:
                0 15px 40px rgba(0,0,0,0.25);
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
            font-size:18px;
        }

        .cards{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
            gap:25px;
        }

        .card{
            background:white;
            color:#111827;

            border-radius:22px;

            padding:30px;

            text-decoration:none;

            transition:0.25s;

            box-shadow:
                0 10px 25px rgba(0,0,0,0.12);
        }

        .card:hover{
            transform:translateY(-8px) scale(1.02);

            box-shadow:
                0 18px 40px rgba(0,0,0,0.18);
        }

        .icon{
            font-size:50px;
            margin-bottom:18px;
        }

        .card h3{
            margin-bottom:10px;
            font-size:22px;
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
            color:rgba(255,255,255,0.85);
        }

        footer{
            text-align:center;
            padding:25px;
            color:rgba(255,255,255,0.7);
            font-size:14px;
        }

        @media(max-width:700px){

            header{
                flex-direction:column;
                gap:15px;
            }

            .welcome h1{
                font-size:28px;
            }

            .dashboard{
                padding:25px;
            }

        }

    </style>
</head>

<body>

<header>

    <div class="title">
        🔧 Atelier mécanique
    </div>

    <div class="user-box">
        👨‍🎓 <?= htmlspecialchars($name) ?>
    </div>

</header>

<main>

    <div class="dashboard">

        <div class="welcome">
            <h1>Bienvenue <?= htmlspecialchars($name) ?></h1>

            <p>
                Accédez rapidement aux outils de gestion des réparations.
            </p>
        </div>

        <div class="cards">

            <a class="card" href="ordre_reparation.php">

                <div class="icon">📝</div>

                <h3>Créer un OR</h3>

                <p>
                    Créer un nouvel ordre de réparation atelier.
                </p>

            </a>

            <a class="card" href="mes_reparations.php">

                <div class="icon">🔧</div>

                <h3>Mes réparations</h3>

                <p>
                    Consulter les interventions enregistrées.
                </p>

            </a>

            <a class="card" href="recherche_or.php">

                <div class="icon">🔍</div>

                <h3>Recherche OR</h3>

                <p>
                    Rechercher rapidement un ordre de réparation.
                </p>

            </a>

            <a class="card logout" href="logout.php">

                <div class="icon">🚪</div>

                <h3>Déconnexion</h3>

                <p>
                    Quitter votre session utilisateur.
                </p>

            </a>

        </div>

    </div>

</main>

<footer>
    Projet Atelier mécanique - Lycée Brocéliande
</footer>

</body>
</html>
