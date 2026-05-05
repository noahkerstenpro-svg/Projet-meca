<?php
session_start();

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
    <title>Espace Élève</title>

    <style>
        body {
            font-family: Arial;
            background: #f3f4f6;
            margin: 0;
        }

        header {
            background: #1e3a8a;
            color: white;
            padding: 20px;
        }

        main {
            padding: 40px;
        }

        .container {
            max-width: 900px;
            margin: auto;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .actions {
            margin-top: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .btn {
            padding: 15px 25px;
            border-radius: 8px;
            text-decoration: none;
            color: white;
            font-weight: bold;
        }

        .blue { background: #2563eb; }
        .green { background: #16a34a; }
        .red { background: #dc2626; }
    </style>
</head>

<body>

<header>
    <h1>Atelier mécanique - Élève</h1>
</header>

<main>
    <div class="container">
        <div class="card">
            <h2>Bienvenue <?= htmlspecialchars($name) ?></h2>
            <p>Rôle : Élève</p>

            <div class="actions">

                <a class="btn blue" href="ordre_reparation.php">
                    Créer un ordre de réparation
                </a>

                <a class="btn green" href="mes_reparations.php">
                    Voir mes réparations
                </a>

                <a class="btn red" href="logout.php">
                    Déconnexion
                </a>

            </div>
        </div>
    </div>
</main>

</body>
</html>
