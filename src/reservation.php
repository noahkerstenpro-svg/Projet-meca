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

            // Rediriger vers la page de confirmation
            $_SESSION['confirm_rdv'] = [
                'vehicule' => $vehicule,
                'date'     => $date,
                'heure'    => $heure,
                'probleme' => $probleme,
            ];
            header('Location: confirmation_reservation.php');
            exit;

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

        /* ── PAGE CONFIRMATION ── */
        .confirmation-page {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 80vh;
            padding: 20px;
        }

        .confirmation-card {
            background: white;
            border-radius: 24px;
            padding: 50px 60px;
            max-width: 520px;
            width: 100%;
            box-shadow: 0 8px 30px rgba(0,0,0,0.10);
            text-align: center;
            animation: fadeUp 0.4s ease;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .confirm-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .confirmation-card h2 {
            color: #27ae60;
            font-size: 26px;
            margin-bottom: 8px;
        }

        .confirm-subtitle {
            color: #6b7280;
            font-size: 15px;
            margin-bottom: 30px;
        }

        .confirm-details {
            background: #f9fafb;
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 24px;
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .confirm-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 10px;
        }

        .confirm-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .confirm-label {
            color: #6b7280;
            font-weight: normal;
        }

        .confirm-value {
            font-weight: bold;
            color: #111827;
            text-align: right;
        }

        .confirm-note {
            font-size: 13px;
            color: #9ca3af;
            margin-bottom: 28px;
            font-style: italic;
        }

        .confirm-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
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
    </div> <!-- ferme .container -->

    <div class="confirmation-page">
        <div class="confirmation-card">
            <div class="confirm-icon">✅</div>
            <h2>Réservation confirmée !</h2>
            <p class="confirm-subtitle">Votre rendez-vous a bien été pris en compte.</p>

            <div class="confirm-details">
                <div class="confirm-row">
                    <span class="confirm-label">👤 Client</span>
                    <span class="confirm-value"><?= htmlspecialchars($_SESSION['client_prenom'] . ' ' . $_SESSION['client_nom']) ?></span>
                </div>
                <div class="confirm-row">
                    <span class="confirm-label">🚗 Véhicule</span>
                    <span class="confirm-value"><?= htmlspecialchars(trim(($_POST['marque'] ?? '') . ' ' . ($_POST['modele'] ?? ''))) ?></span>
                </div>
                <div class="confirm-row">
                    <span class="confirm-label">📅 Date</span>
                    <span class="confirm-value"><?= htmlspecialchars($_POST['date'] ?? '') ?></span>
                </div>
                <div class="confirm-row">
                    <span class="confirm-label">🕐 Heure</span>
                    <span class="confirm-value"><?= htmlspecialchars($_POST['heure'] ?? '') ?></span>
                </div>
                <div class="confirm-row">
                    <span class="confirm-label">🔧 Problème</span>
                    <span class="confirm-value"><?= htmlspecialchars(($_POST['probleme'] ?? '') === 'Autre' ? ($_POST['probleme_autre'] ?? '') : ($_POST['probleme'] ?? '')) ?></span>
                </div>
            </div>

            <p class="confirm-note">L'atelier vous accueillera à l'heure indiquée. En cas d'empêchement, contactez-nous.</p>

            <div class="confirm-actions">
                <button onclick="window.location.href='reservation.php'">📅 Nouvelle réservation</button>
                <button class="logout" onclick="window.location.href='logout1.php'">🚪 Se déconnecter</button>
            </div>
        </div>
    </div>

    <?php else: ?> <!-- pas de message → on affiche le formulaire normalement -->
    <div class="container" style="display:block"><!-- réouverture container pour le formulaire -->

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

<?php endif; ?>

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
