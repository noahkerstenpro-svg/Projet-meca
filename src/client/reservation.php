<?php
session_start();
if (!isset($_SESSION['client_id'])) {
    header('Location: connexion.php');
    exit;
}

$message = '';
$erreur  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicule = trim($_POST['vehicule'] ?? '');
    $date     = trim($_POST['date']     ?? '');
    $heure    = trim($_POST['heure']    ?? '');
    $probleme = trim($_POST['probleme'] ?? '');
    $client_id = $_SESSION['client_id'];

    if ($vehicule && $date && $heure && $probleme) {

        // --- Connexion BDD ---
        $host = '172.0.0.1';
        $dbname = 'Meca';
        $user   = 'root';
        $pass   = 'root';

        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 1) Insérer le véhicule dans Vehicules (marque/modèle + client_id)
            //    On génère un VIN factice si le client n'en a pas.
            $stmtVeh = $pdo->prepare("
                INSERT INTO Vehicules (vin, `marque/modèle`, client_id)
                VALUES (:vin, :marque, :client_id)
            ");
            $vin = strtoupper(substr(md5(uniqid()), 0, 10)); // VIN provisoire
            $stmtVeh->execute([
                ':vin'       => $vin,
                ':marque'    => $vehicule,
                ':client_id' => $client_id,
            ]);
            $vehicule_id = $pdo->lastInsertId();

            // 2) Insérer l'intervention (prestation_id = NULL car non choisi ici)
            $stmtInt = $pdo->prepare("
                INSERT INTO intervention (vehicule_id, prestation_id, date_intervention, heure_de_préstation, commentaire)
                VALUES (:vehicule_id, NULL, :date, :heure, :commentaire)
            ");
            $stmtInt->execute([
                ':vehicule_id'  => $vehicule_id,
                ':date'         => $date,
                ':heure'        => $heure,
                ':commentaire'  => $probleme,
            ]);

            $message = "Réservation confirmée pour votre $vehicule le $date à $heure.";

        } catch (PDOException $e) {
            $erreur = "Erreur base de données : " . $e->getMessage();
        }
    } else {
        $erreur = "Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réservation - Méca Brocéliande</title>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f1f2f3;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background-color: #525151;
            color: white;
            padding: 20px;
            text-align: center;
        }

        .container {
            max-width: 500px;
            margin: 80px auto;
            background: white;
            padding: 50px 70px;
            border-radius: 20px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        .form {
            margin-bottom: 15px;
        }

        label {
            font-size: 14px;
            color: #555;
        }

        input, select {
            width: 100%;
            padding: 10px;
            line-height: 20px;
            margin-top: 5px;
            border-radius: 10px;
            border: 1px solid #ccc;
            font-size: 14px;
            height: 42px;
            box-sizing: border-box;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #eb5e00;
        }

        input[type="date"] {
            height: 42px;
        }

        button {
            margin-top: 20px;
            width: 100%;
            padding: 12px;
            background-color: #eb5e00;
            color: white;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #d65300;
        }

        .logout {
            background-color: #525151;
            margin-top: 15px;
        }

        .logout:hover {
            background-color: #333;
        }

        .message-ok {
            background: #e6f9e6;
            color: #2a7a2a;
            border: 1px solid #a3d9a3;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }

        .message-err {
            background: #fdecea;
            color: #b00020;
            border: 1px solid #f5c2c7;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }

        footer {
            position: fixed;
            bottom: 19px;
            left: 0;
            width: 100%;
            text-align: center;
        }
    </style>
</head>

<body>

<header>
    <h1>Atelier Mécanique - Bac Professionnel de Brocéliande</h1>
</header>

<div class="container">
    <h2>Réserver un créneau</h2>

    <p style="text-align:center; color:#525151; margin-bottom: 20px;">
        Bonjour, <strong><?= htmlspecialchars($_SESSION['client_prenom'] . ' ' . $_SESSION['client_nom']) ?></strong>
    </p>

    <?php if ($message): ?>
        <div class="message-ok"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($erreur): ?>
        <div class="message-err"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <form method="POST" action="reservation.php">
        <div class="form">
            <label>Véhicule</label>
            <input name="vehicule" placeholder="Ex : Renault Clio" required>
        </div>

        <div class="form">
            <label>Date</label>
            <input type="date" name="date" required>
        </div>

        <div class="form">
            <label>Heure</label>
            <select name="heure" required>
                <option value="" disabled selected>Choisir une heure</option>
                <option>09:00</option>
                <option>10:00</option>
                <option>11:00</option>
                <option>14:00</option>
                <option>15:00</option>
                <option>16:00</option>
            </select>
        </div>

        <div class="form">
            <label>Problème</label>
            <input name="probleme" placeholder="Décrivez votre problème" required>
        </div>

        <button type="submit">Réserver</button>
    </form>

    <button class="logout" onclick="window.location.href='logout1.php'">Se déconnecter</button>
</div>

<footer>
    <p>© 2026 Méca Brocéliande</p>
</footer>

</body>
</html>
