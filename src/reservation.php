<?php
session_start();
if (!isset($_SESSION['client_id'])) {
    header('Location: connexion.php');
    exit;
}

// --- Connexion BDD (partagée pour tout le fichier) ---
$host   = 'meca-mysql';
$dbname = 'Meca';
$user   = 'root';
$pass   = 'root';

$pdo = new PDO("mysql:host=$host;port=3306;dbname=$dbname;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Charger les prestations depuis la BDD pour le menu déroulant
$prestations = $pdo->query("SELECT id_prestation, designation FROM Prestation ORDER BY id_prestation")->fetchAll(PDO::FETCH_ASSOC);

$message = '';
$erreur  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $marque   = trim($_POST['marque']   ?? '');
    $modele   = trim($_POST['modele']   ?? '');
    $vehicule = trim("$marque $modele");
    $date     = trim($_POST['date']     ?? '');
    $heure    = trim($_POST['heure']    ?? '');
    $client_id = $_SESSION['client_id'];

    $probleme_select = trim($_POST['probleme']       ?? '');
    $probleme_autre  = trim($_POST['probleme_autre'] ?? '');
    $probleme = ($probleme_select === 'Autre') ? $probleme_autre : $probleme_select;

    if ($marque && $modele && $date && $heure && $probleme) {

        try {
            // $pdo est déjà connecté en haut du fichier

            // 1) Vérifier si le véhicule existe déjà pour ce client
            $stmtCheck = $pdo->prepare("
                SELECT id_vehicules FROM Vehicules
                WHERE marque = :marque AND modele = :modele AND client_id = :client_id
                LIMIT 1
            ");
            $stmtCheck->execute([
                ':marque'    => $marque,
                ':modele'    => $modele,
                ':client_id' => $client_id,
            ]);
            $existingVeh = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($existingVeh) {
                // Véhicule déjà connu : on réutilise son id
                $vehicule_id = $existingVeh['id_vehicules'];
            } else {
                // Nouveau véhicule : on l'insère
                $stmtVeh = $pdo->prepare("
                    INSERT INTO Vehicules (vin, marque, modele, client_id)
                    VALUES (NULL, :marque, :modele, :client_id)
                ");
                $stmtVeh->execute([
                    ':marque'    => $marque,
                    ':modele'    => $modele,
                    ':client_id' => $client_id,
                ]);
                $vehicule_id = $pdo->lastInsertId();
            }

            // 2) Récupérer automatiquement l'id_prestation selon la désignation choisie
            $prestation_id = null;
            if ($probleme_select !== 'Autre' && $probleme_select !== '') {
                $stmtPrest = $pdo->prepare("
                    SELECT id_prestation FROM Prestation
                    WHERE designation = :designation
                    LIMIT 1
                ");
                $stmtPrest->execute([':designation' => $probleme_select]);
                $rowPrest = $stmtPrest->fetch(PDO::FETCH_ASSOC);
                if ($rowPrest) {
                    $prestation_id = $rowPrest['id_prestation'];
                }
            }

            // 3) Insérer l'intervention
            //    - Si prestation connue : Probleme = désignation, prestation_id = id trouvé
            //    - Si Autre            : Probleme = texte libre du client, prestation_id = NULL
            $stmtInt = $pdo->prepare("
                INSERT INTO intervention (vehicule_id, prestation_id, date_intervention, `heure_de_préstation`, Probleme, commentaire, source)
                VALUES (:vehicule_id, :prestation_id, :date, :heure, :probleme, :commentaire, 'reservation')
            ");
            $stmtInt->execute([
                ':vehicule_id'   => $vehicule_id,
                ':prestation_id' => $prestation_id,
                ':date'          => $date,
                ':heure'         => $heure,
                ':probleme'      => $probleme,
                ':commentaire'   => '',
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
            <label>Marque</label>
            <input name="marque" id="marque" placeholder="Ex : Renault" required>
        </div>

        <div class="form">
            <label>Modèle</label>
            <input name="modele" id="modele" placeholder="Ex : Clio" required>
        </div>

        <div class="form">
            <label>Date</label>
            <input type="date" name="date" id="date" onchange="majCreneaux()" required>
        </div>

        <div class="form">
            <label>Heure</label>
            <select name="heure" id="heure" required>
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
            <select name="probleme" id="probleme" onchange="afficherAutre()" required>
                <option value="" disabled selected>Choisir une prestation</option>
                <?php foreach ($prestations as $p): ?>
                    <option value="<?= htmlspecialchars($p['designation']) ?>">
                        <?= htmlspecialchars($p['designation']) ?>
                    </option>
                <?php endforeach; ?>
                <option value="Autre">Autre</option>
            </select>
        </div>

        <div class="form" id="autre-champ" style="display:none;">
            <label>Précisez votre problème</label>
            <input type="text" name="probleme_autre" id="probleme_autre" placeholder="Décrivez votre problème">
        </div>

        <button type="submit">Réserver</button>
    </form>

    <button class="logout" onclick="window.location.href='logout1.php'">Se déconnecter</button>
</div>

<footer>
    <p>© 2026 Méca Brocéliande</p>
</footer>

<script>
function afficherAutre() {
    const select = document.getElementById('probleme');
    const autreChamp = document.getElementById('autre-champ');
    const autreInput = document.getElementById('probleme_autre');

    if (select.value === 'Autre') {
        autreChamp.style.display = 'block';
        autreInput.required = true;
    } else {
        autreChamp.style.display = 'none';
        autreInput.required = false;
        autreInput.value = '';
    }
}

const tousLesCreneaux = ['09:00','10:00','11:00','14:00','15:00','16:00'];

async function majCreneaux() {
    const date  = document.getElementById('date').value;
    const select = document.getElementById('heure');

    select.innerHTML = '<option value="" disabled selected>Choisir une heure</option>';

    if (!date) return;

    const resp = await fetch('creneaux_pris.php?date=' + date);
    const pris = await resp.json();

    tousLesCreneaux.forEach(h => {
        const opt = document.createElement('option');
        opt.value = h;
        if (pris.includes(h)) {
            opt.textContent = h + ' — Indisponible';
            opt.disabled = true;
            opt.style.color = '#aaa';
        } else {
            opt.textContent = h;
        }
        select.appendChild(opt);
    });
}
</script>

</body>
</html>
