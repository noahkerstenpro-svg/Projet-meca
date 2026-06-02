<?php
session_start();

if (!isset($_SESSION['client_id']) || !isset($_SESSION['confirm_rdv'])) {
    header('Location: reservation.php');
    exit;
}

$rdv    = $_SESSION['confirm_rdv'];
$prenom = $_SESSION['client_prenom'] ?? '';
$nom    = $_SESSION['client_nom']    ?? '';

unset($_SESSION['confirm_rdv']);

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
    <title>Réservation enregistrée — Méca Brocéliande</title>

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
            max-width: 520px;
            width: 100%;
            box-shadow: 0 8px 30px rgba(0,0,0,0.10);
            text-align: center;
            animation: fadeUp 0.4s ease;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Icône attente */
        .pending-circle {
            width: 90px;
            height: 90px;
            background: #fff8e1;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 46px;
            margin: 0 auto 24px;
            animation: spin-slow 4s linear infinite;
        }

        @keyframes spin-slow {
            0%   { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        h2 {
            color: #f59e0b;
            font-size: 24px;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 10px;
        }

        /* Bandeau statut */
        .status-banner {
            background: #fff8e1;
            border: 1px solid #fde68a;
            border-radius: 14px;
            padding: 14px 20px;
            font-size: 14px;
            color: #92400e;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-align: left;
        }

        .status-banner .icon { font-size: 24px; flex-shrink: 0; }

        /* Récap */
        .recap {
            background: #f9fafb;
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 24px;
            text-align: left;
            display: flex;
            flex-direction: column;
        }

        .recap-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            padding: 11px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .recap-row:last-child { border-bottom: none; }

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

        /* Note */
        .note {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 13px;
            color: #166534;
            margin-bottom: 28px;
            text-align: left;
        }

        /* Boutons */
        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 30px;
            font-size: 15px;
            cursor: pointer;
            margin-bottom: 10px;
            transition: background-color 0.2s;
            font-family: Arial, sans-serif;
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

        .btn-mes-rdv {
            background-color: #f59e0b;
            color: white;
        }

        .btn-mes-rdv:hover { background-color: #d97706; }

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

        <div class="pending-circle">⏳</div>

        <h2>Demande enregistrée !</h2>
        <p class="subtitle">
            Bonjour <strong><?= htmlspecialchars($prenom . ' ' . $nom) ?></strong>,
            votre demande de rendez-vous a bien été reçue.
        </p>

        <!-- Bandeau statut -->
        <div class="status-banner">
            <span class="icon">🔔</span>
            <span>
                Votre rendez-vous est <strong>en attente de confirmation</strong> par un professeur.
                Vous pourrez consulter son statut depuis votre espace personnel.
            </span>
        </div>

        <!-- Récapitulatif -->
        <div class="recap">
            <div class="recap-row">
                <span class="recap-label">🚗 Véhicule</span>
                <span class="recap-value"><?= htmlspecialchars($rdv['vehicule']) ?></span>
            </div>
            <div class="recap-row">
                <span class="recap-label">📅 Date demandée</span>
                <span class="recap-value"><?= dateFR($rdv['date']) ?></span>
            </div>
            <div class="recap-row">
                <span class="recap-label">🕐 Heure demandée</span>
                <span class="recap-value"><?= htmlspecialchars($rdv['heure']) ?></span>
            </div>
            <div class="recap-row">
                <span class="recap-label">🔧 Problème</span>
                <span class="recap-value"><?= htmlspecialchars($rdv['probleme']) ?></span>
            </div>
            <div class="recap-row">
                <span class="recap-label">📋 Statut</span>
                <span class="recap-value" style="color:#f59e0b;">⏳ En attente</span>
            </div>
        </div>

        <!-- Note -->
        <div class="note">
            💡 Dès qu'un professeur confirmera votre RDV, vous pourrez le voir dans votre espace "Mes réservations".
        </div>

        <!-- Boutons -->
        <button class="btn btn-mes-rdv" onclick="window.location.href='mes_rdv.php'">
            📋 Voir mes réservations
        </button>
        <button class="btn btn-primary" onclick="window.location.href='reservation.php'">
            📅 Faire une autre réservation
        </button>
        <button class="btn btn-secondary" onclick="window.location.href='accueil.html'">
            🏠 Retour à l'accueil
        </button>

    </div>
</div>

<footer>
    <p>© 2026 Méca Brocéliande</p>
</footer>

</body>
</html>
