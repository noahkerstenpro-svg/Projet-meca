<?php
session_start();

if (!isset($_SESSION['client_id']) || !isset($_SESSION['confirm_rdv'])) {
    header('Location: reservation.php');
    exit;
}

$rdv    = $_SESSION['confirm_rdv'];
$prenom = $_SESSION['client_prenom'] ?? '';
$nom    = $_SESSION['client_nom']    ?? '';

// On vide les données de confirmation pour éviter de reafficher si on revient
unset($_SESSION['confirm_rdv']);

// Formater la date en français
function dateFR($date) {
    $ts   = strtotime($date);
    $jours = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
    $mois  = ['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
    return $jours[date('w', $ts)] . ' ' . date('j', $ts) . ' ' . $mois[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation confirmée — Méca Brocéliande</title>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
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

        header h1 { font-size: 22px; }

        /* ── PAGE CENTRALE ── */
        .page {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        .card {
            background: white;
            border-radius: 24px;
            padding: 50px 60px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 8px 30px rgba(0,0,0,0.10);
            text-align: center;
            animation: fadeUp 0.4s ease;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── ICÔNE SUCCÈS ── */
        .success-circle {
            width: 90px;
            height: 90px;
            background: #e6f9f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 46px;
            margin: 0 auto 24px;
        }

        h2 {
            color: #27ae60;
            font-size: 24px;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 32px;
        }

        /* ── RÉCAP ── */
        .recap {
            background: #f9fafb;
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 28px;
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .recap-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            padding: 11px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .recap-row:last-child {
            border-bottom: none;
        }

        .recap-label {
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .recap-value {
            font-weight: bold;
            color: #111827;
            text-align: right;
            max-width: 60%;
        }

        /* ── NOTE ── */
        .note {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 13px;
            color: #92400e;
            margin-bottom: 28px;
            text-align: left;
        }

        /* ── BOUTONS ── */
        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 30px;
            font-size: 15px;
            cursor: pointer;
            margin-bottom: 10px;
            transition: background-color 0.2s;
        }

        .btn-primary {
            background-color: #eb5e00;
            color: white;
        }

        .btn-primary:hover { background-color: #d65300; }

        .btn-secondary {
            background-color: #525151;
            color: white;
        }

        .btn-secondary:hover { background-color: #333; }

        footer {
            text-align: center;
            padding: 16px;
            font-size: 13px;
            color: #999;
        }
    </style>
</head>
<body>

<header>
    <h1>Atelier Mécanique — Bac Professionnel de Brocéliande</h1>
</header>

<div class="page">
    <div class="card">

        <div class="success-circle">✅</div>

        <h2>Réservation confirmée !</h2>
        <p class="subtitle">
            Bonjour <strong><?= htmlspecialchars($prenom . ' ' . $nom) ?></strong>,
            votre créneau a bien été pris en compte.
        </p>

        <!-- Récapitulatif -->
        <div class="recap">
            <div class="recap-row">
                <span class="recap-label">🚗 Véhicule</span>
                <span class="recap-value"><?= htmlspecialchars($rdv['vehicule']) ?></span>
            </div>
            <div class="recap-row">
                <span class="recap-label">📅 Date</span>
                <span class="recap-value"><?= dateFR($rdv['date']) ?></span>
            </div>
            <div class="recap-row">
                <span class="recap-label">🕐 Heure</span>
                <span class="recap-value"><?= htmlspecialchars($rdv['heure']) ?></span>
            </div>
            <div class="recap-row">
                <span class="recap-label">🔧 Problème</span>
                <span class="recap-value"><?= htmlspecialchars($rdv['probleme']) ?></span>
            </div>
        </div>

        <!-- Note -->
        <div class="note">
            ⚠️ En cas d'empêchement, merci de nous prévenir le plus tôt possible.
            L'atelier vous accueillera à l'heure indiquée.
        </div>

        <!-- Boutons -->
        <button class="btn btn-primary" onclick="window.location.href='reservation.php'">
            📅 Faire une autre réservation
        </button>
        <button class="btn btn-secondary" onclick="window.location.href='accueil.php'">
            🏠 Retour à l'accueil
        </button>

    </div>
</div>

<footer>
    <p>© 2026 Méca Brocéliande</p>
</footer>

</body>
</html>
