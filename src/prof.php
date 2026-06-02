<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'prof') {
    header('Location: login.php');
    exit;
}

$name = $_SESSION['name'] ?? $_SESSION['username'];

// Compteur de RDV en attente de confirmation
$pdo = new PDO("mysql:host=meca-mysql;port=3306;dbname=Meca;charset=utf8mb4", 'root', 'root');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$nb_attente = (int)$pdo->query("SELECT COUNT(*) FROM intervention WHERE source = 'reservation' AND statut_rdv = 'en_attente'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace Professeur - Atelier mécanique</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #1e3a8a, #111827);
            color: #111827;
        }

        header {
            padding: 25px 40px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h1 { margin: 0; font-size: 26px; }

        .badge {
            background: rgba(255,255,255,0.15);
            padding: 10px 16px;
            border-radius: 999px;
            font-weight: bold;
        }

        main { padding: 40px 20px; }

        .container { max-width: 1000px; margin: auto; }

        .welcome-card {
            background: white;
            border-radius: 22px;
            padding: 35px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.30);
        }

        .logo {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            background: #2563eb;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            margin-bottom: 20px;
        }

        h2 { margin: 0; font-size: 28px; }

        .subtitle {
            color: #6b7280;
            margin-top: 8px;
            margin-bottom: 30px;
        }

        .actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
        }

        .action-card {
            text-decoration: none;
            color: #111827;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 22px;
            transition: 0.2s;
        }

        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
            border-color: #2563eb;
        }

        .icon { font-size: 34px; margin-bottom: 12px; }

        .action-card h3 { margin: 0 0 8px; font-size: 18px; }

        .action-card p { margin: 0; color: #6b7280; font-size: 14px; }

        .logout { background: #fee2e2; border-color: #fecaca; }
        .logout:hover { border-color: #dc2626; }

        /* Badge notification */
        .notif-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ef4444;
            color: white;
            font-size: 11px;
            font-weight: bold;
            min-width: 20px;
            height: 20px;
            border-radius: 50%;
            padding: 0 5px;
            margin-left: 6px;
            vertical-align: middle;
        }

        .footer {
            margin-top: 25px;
            text-align: center;
            color: rgba(255,255,255,0.75);
            font-size: 13px;
        }
    </style>
</head>

<body>

<header>
    <h1>Atelier mécanique</h1>
    <div class="badge">Espace Professeur</div>
</header>

<main>
    <div class="container">
        <div class="welcome-card">
            <div class="logo">👨‍🏫</div>

            <h2>Bienvenue <?= htmlspecialchars($name) ?></h2>
            <p class="subtitle">Vous êtes connecté en tant que professeur.</p>

            <div class="actions">
                <a class="action-card" href="ordre_reparation.php">
                    <div class="icon">📝</div>
                    <h3>Créer un ordre</h3>
                    <p>Créer un nouvel ordre de réparation pour l'atelier.</p>
                </a>

                <a class="action-card" href="rdv_client.php">
                    <div class="icon">📅</div>
                    <h3>Agenda des RDV clients
                        <?php if ($nb_attente > 0): ?>
                            <span class="notif-badge" id="badge-notif"><?= $nb_attente ?></span>
                        <?php endif; ?>
                    </h3>
                    <p>Consulter et confirmer les rendez-vous clients.</p>
                </a>

                <a class="action-card" href="recherche_or.php">
                    <div class="icon">🔍</div>
                    <h3>Réparations en cours</h3>
                    <p>Consulter et modifier les ordres de réparation en cours.</p>
                </a>

                <a class="action-card" href="validation.php">
                    <div class="icon">✅</div>
                    <h3>OR complets / Validés</h3>
                    <p>Valider les ordres terminés et consulter les OR validés.</p>
                </a>

                <a class="action-card" href="http://192.168.11.11:3000/" target="_blank">
                    <div class="icon">🛠️</div>
                    <h3>Accéder à GLPI</h3>
                    <p>Demande de support informatique.</p>
                </a>

                <a class="action-card logout" href="logout.php">
                    <div class="icon">🚪</div>
                    <h3>Déconnexion</h3>
                    <p>Fermer votre session en toute sécurité.</p>
                </a>
            </div>
        </div>

        <div class="footer">
            Projet Mécanique SEP - Lycée Brocéliande
        </div>
    </div>
</main>

</body>
</html>
