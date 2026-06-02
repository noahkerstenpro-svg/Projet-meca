<?php
session_start();

if (!isset($_SESSION['client_id'])) {
    header('Location: connexion.php');
    exit;
}

$host   = 'meca-mysql';
$dbname = 'Meca';
$user   = 'root';
$pass   = 'root';

$prenom = $_SESSION['client_prenom'] ?? '';
$nom    = $_SESSION['client_nom']    ?? '';
$rdvs   = [];
$erreur = '';

try {
    $pdo = new PDO("mysql:host=$host;port=3306;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("
        SELECT
            i.id_intervention,
            i.date_intervention,
            i.`heure_de_préstation`,
            i.Probleme,
            i.statut,
            CONCAT(v.marque, ' ', COALESCE(v.modele,'')) AS vehicule,
            p.designation AS prestation_nom
        FROM intervention i
        JOIN Vehicules v ON v.id_vehicules = i.vehicule_id
        LEFT JOIN Prestation p ON p.id_prestation = i.prestation_id
        WHERE v.client_id = ? AND i.source = 'reservation'
        ORDER BY i.date_intervention DESC, i.`heure_de_préstation` DESC
    ");
    $stmt->execute([$_SESSION['client_id']]);
    $rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $erreur = "Erreur BDD : " . $e->getMessage();
}

function dateFR($date) {
    $ts = strtotime($date);
    $jours = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
    $mois  = ['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
    return $jours[date('w', $ts)] . ' ' . date('j', $ts) . ' ' . $mois[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

function statutInfo($statut) {
    switch ($statut) {
        case 'en_attente': return ['label' => '⏳ En attente',   'color' => '#f59e0b', 'bg' => '#fff8e1', 'border' => '#fde68a', 'text' => 'Votre demande est en cours d\'examen par un professeur.'];
        case 'confirme':   return ['label' => '✅ Confirmé',     'color' => '#16a34a', 'bg' => '#f0fdf4', 'border' => '#bbf7d0', 'text' => 'Votre rendez-vous est confirmé. Présentez-vous à l\'heure indiquée.'];
        case 'annule':     return ['label' => '❌ Annulé',       'color' => '#dc2626', 'bg' => '#fee2e2', 'border' => '#fecaca', 'text' => 'Ce rendez-vous a été annulé. Vous pouvez en créer un nouveau.'];
        case 'en_cours':   return ['label' => '🔧 En cours',     'color' => '#2563eb', 'bg' => '#dbeafe', 'border' => '#bfdbfe', 'text' => 'Votre véhicule est en cours de réparation.'];
        case 'termine':    return ['label' => '🏁 Terminé',      'color' => '#b45309', 'bg' => '#fef3c7', 'border' => '#fde68a', 'text' => 'La réparation est terminée.'];
        case 'valide':     return ['label' => '✅ Validé',       'color' => '#16a34a', 'bg' => '#f0fdf4', 'border' => '#bbf7d0', 'text' => 'La réparation a été validée par le professeur.'];
        default:           return ['label' => '❓ Inconnu',      'color' => '#888',    'bg' => '#f9fafb', 'border' => '#e5e7eb', 'text' => ''];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes réservations — Méca Brocéliande</title>

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

        /* ── INTRO ── */
        .intro {
            max-width: 700px;
            margin: 40px auto 0;
            padding: 0 20px;
            text-align: center;
        }

        .intro h2 {
            font-size: 26px;
            color: #333;
            margin-bottom: 6px;
        }

        .intro p {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 30px;
        }

        /* ── REFRESH NOTE ── */
        .refresh-note {
            background: #e0f2fe;
            border: 1px solid #bae6fd;
            border-radius: 12px;
            padding: 10px 18px;
            font-size: 13px;
            color: #0369a1;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 30px;
        }

        /* ── LISTE RDV ── */
        .liste {
            max-width: 700px;
            margin: 0 auto;
            padding: 0 20px 100px;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        /* ── CARTE ── */
        .rdv-card {
            background: white;
            border-radius: 22px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border-left: 6px solid #ccc;
            animation: fadeUp 0.3s ease;
            transition: transform 0.2s;
        }

        .rdv-card:hover {
            transform: translateY(-2px);
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* En-tête de carte */
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 14px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .card-vehicule {
            font-size: 18px;
            font-weight: bold;
            color: #111;
        }

        .card-vehicule::before { content: '🚗 '; }

        .badge-statut {
            font-size: 13px;
            font-weight: bold;
            padding: 6px 16px;
            border-radius: 999px;
            white-space: nowrap;
        }

        /* Info de la carte */
        .card-infos {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 14px;
        }

        .info-item {
            font-size: 14px;
            color: #444;
        }

        .info-label {
            font-size: 11px;
            color: #aaa;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        /* Message statut */
        .statut-message {
            border-radius: 12px;
            padding: 10px 16px;
            font-size: 13px;
            border: 1px solid;
        }

        /* Bouton nouvelle résa */
        .btn-nouvelle {
            display: inline-block;
            margin-top: 6px;
            padding: 6px 16px;
            background: #eb5e00;
            color: white;
            border-radius: 20px;
            font-size: 13px;
            text-decoration: none;
            font-weight: bold;
        }

        .btn-nouvelle:hover { background: #d65300; }

        /* Vide */
        .vide {
            text-align: center;
            padding: 60px 20px;
            color: #bbb;
            font-size: 16px;
        }

        .vide-icon { font-size: 56px; margin-bottom: 14px; }

        /* Erreur */
        .erreur-bdd {
            margin: 40px auto;
            max-width: 600px;
            background: #fdecea;
            color: #b00020;
            border: 1px solid #f5c2c7;
            border-radius: 25px;
            padding: 20px;
            font-size: 14px;
            text-align: center;
        }

        /* Boutons navigation */
        .nav-btns {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
            padding: 0 20px 20px;
        }

        .nav-btn {
            padding: 10px 24px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            transition: background-color 0.2s;
        }

        .nav-btn-primary {
            background: #eb5e00;
            color: white;
        }

        .nav-btn-primary:hover { background: #d65300; }

        .nav-btn-secondary {
            background: #525151;
            color: white;
        }

        .nav-btn-secondary:hover { background: #333; }

        footer {
            margin-top: auto;
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

<div class="intro">
    <h2>📋 Mes réservations</h2>
    <p>Bonjour <strong><?= htmlspecialchars($prenom . ' ' . $nom) ?></strong>, retrouvez ici toutes vos demandes de rendez-vous et leur statut en temps réel.</p>
    <div class="refresh-note">
        🔄 Actualisez la page pour voir les mises à jour de statut
    </div>
</div>

<?php if ($erreur): ?>
    <div class="erreur-bdd"><?= htmlspecialchars($erreur) ?></div>
<?php elseif (empty($rdvs)): ?>
    <div class="vide">
        <div class="vide-icon">📅</div>
        <div>Aucune réservation pour l'instant</div>
        <br>
        <a href="reservation.php" class="nav-btn nav-btn-primary">📅 Prendre un rendez-vous</a>
    </div>
<?php else: ?>

<div class="liste">
    <?php foreach ($rdvs as $r): ?>
        <?php
            $statut = $r['statut'] ?? 'en_attente';
            $info   = statutInfo($statut);
        ?>
        <div class="rdv-card" style="border-left-color: <?= $info['color'] ?>;">

            <div class="card-header">
                <div class="card-vehicule"><?= htmlspecialchars($r['vehicule']) ?></div>
                <span class="badge-statut" style="background:<?= $info['bg'] ?>; color:<?= $info['color'] ?>; border: 1px solid <?= $info['border'] ?>;">
                    <?= $info['label'] ?>
                </span>
            </div>

            <div class="card-infos">
                <div class="info-item">
                    <div class="info-label">📅 Date</div>
                    <?= dateFR($r['date_intervention']) ?>
                </div>
                <div class="info-item">
                    <div class="info-label">🕐 Heure</div>
                    <?= htmlspecialchars(substr($r['heure_de_préstation'], 0, 5)) ?>
                </div>
                <div class="info-item" style="grid-column: 1 / -1;">
                    <div class="info-label">🔧 Prestation / Problème</div>
                    <?= htmlspecialchars($r['prestation_nom'] ?: ($r['Probleme'] ?: '—')) ?>
                </div>
            </div>

            <?php if ($info['text']): ?>
            <div class="statut-message" style="background:<?= $info['bg'] ?>; color:<?= $info['color'] ?>; border-color:<?= $info['border'] ?>;">
                <?= $info['text'] ?>
                <?php if ($statut === 'annule'): ?>
                    <br><a href="reservation.php" class="btn-nouvelle">📅 Nouvelle réservation</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<div class="nav-btns">
    <a href="reservation.php" class="nav-btn nav-btn-primary">📅 Nouvelle réservation</a>
    <a href="accueil.html"    class="nav-btn nav-btn-secondary">🏠 Accueil</a>
</div>

<footer>
    <p>© 2026 Méca Brocéliande</p>
</footer>

</body>
</html>
