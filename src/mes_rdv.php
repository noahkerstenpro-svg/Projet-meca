<?php
session_start();

if (!isset($_SESSION['client_id'])) {
    header('Location: connexion.php');
    exit;
}

$client_id = $_SESSION['client_id'];
$prenom    = $_SESSION['client_prenom'] ?? '';
$nom       = $_SESSION['client_nom']    ?? '';

$pdo = new PDO("mysql:host=meca-mysql;port=3306;dbname=Meca;charset=utf8mb4", 'root', 'root');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->prepare("
    SELECT
        i.id_intervention,
        i.date_intervention,
        i.`heure_de_préstation`  AS heure,
        i.Probleme,
        i.statut_rdv,
        CONCAT(v.marque, ' ', COALESCE(v.modele,'')) AS vehicule
    FROM intervention i
    JOIN Vehicules v ON v.id_vehicules = i.vehicule_id
    WHERE v.client_id = :client_id
      AND i.source = 'reservation'
    ORDER BY i.date_intervention DESC, i.`heure_de_préstation` DESC
");
$stmt->execute([':client_id' => $client_id]);
$rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);

function dateFR($date) {
    $ts   = strtotime($date);
    $jours = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
    $mois  = ['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
    return $jours[date('w',$ts)] . ' ' . date('j',$ts) . ' ' . $mois[(int)date('n',$ts)] . ' ' . date('Y',$ts);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes rendez-vous — Méca Brocéliande</title>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, sans-serif;
            background: #f1f2f3;
            min-height: 100vh;
        }

        header {
            background: #525151;
            color: white;
            padding: 20px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }

        header h1 { font-size: 20px; }

        .btn-retour {
            padding: 8px 18px;
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 50px;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
        }

        .btn-retour:hover { background: rgba(255,255,255,0.25); }

        /* ── INTRO ── */
        .intro {
            text-align: center;
            padding: 36px 20px 10px;
        }

        .intro h2 { font-size: 22px; color: #111827; margin-bottom: 6px; }
        .intro p  { font-size: 14px; color: #6b7280; }

        /* ── LISTE ── */
        .liste {
            max-width: 680px;
            margin: 24px auto 80px;
            padding: 0 20px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        /* ── CARTE ── */
        .rdv-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            display: grid;
            grid-template-columns: 6px 1fr;
            overflow: hidden;
            transition: transform 0.2s;
        }

        .rdv-card:hover { transform: translateY(-2px); }

        .rdv-bande-attente  { background: #f59e0b; }
        .rdv-bande-confirme { background: #27ae60; }
        .rdv-bande-annule   { background: #ef4444; }

        .rdv-body {
            padding: 18px 20px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .rdv-top {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .rdv-vehicule {
            font-size: 15px;
            font-weight: bold;
            color: #111827;
        }

        /* Badges statut */
        .badge {
            font-size: 11px;
            font-weight: bold;
            padding: 3px 12px;
            border-radius: 20px;
        }

        .badge-attente  { background: #fef3c7; color: #b45309; }
        .badge-confirme { background: #e6f9f0; color: #27ae60; }
        .badge-annule   { background: #fee2e2; color: #dc2626; }

        .rdv-date {
            font-size: 13px;
            color: #6b7280;
        }

        .rdv-probleme {
            display: inline-block;
            background: #fff3eb;
            color: #c44d00;
            font-size: 12px;
            padding: 3px 12px;
            border-radius: 20px;
            width: fit-content;
        }

        /* Message statut détaillé */
        .rdv-message {
            font-size: 12px;
            padding: 8px 14px;
            border-radius: 10px;
            margin-top: 4px;
        }

        .rdv-message.attente  { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
        .rdv-message.confirme { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .rdv-message.annule   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        /* ── VIDE ── */
        .vide {
            text-align: center;
            padding: 60px 20px;
            color: #bbb;
            font-size: 15px;
        }

        .vide-icon { font-size: 48px; margin-bottom: 14px; }

        /* ── BOUTON RÉSERVER ── */
        .btn-reserver {
            display: block;
            max-width: 300px;
            margin: 0 auto 30px;
            padding: 13px;
            background: #eb5e00;
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 15px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background 0.2s;
        }

        .btn-reserver:hover { background: #d65300; }

        footer {
            position: fixed;
            bottom: 16px;
            width: 100%;
            text-align: center;
            font-size: 13px;
            color: #999;
        }
    </style>
</head>
<body>

<header>
    <h1>📅 Mes rendez-vous</h1>
    <div style="display:flex;gap:10px;">
        <a class="btn-retour" href="reservation.php">+ Nouveau RDV</a>
        <a class="btn-retour" href="accueil.html">🏠 Accueil</a>
    </div>
</header>

<div class="intro">
    <h2>Bonjour <?= htmlspecialchars($prenom . ' ' . $nom) ?> 👋</h2>
    <p>Voici l'état de vos réservations à l'atelier.</p>
</div>

<div class="liste">

<?php if (empty($rdvs)): ?>
    <div class="vide">
        <div class="vide-icon">📭</div>
        Vous n'avez aucune réservation pour le moment.
    </div>

<?php else: ?>
    <?php foreach ($rdvs as $r):
        $s = $r['statut_rdv'];
    ?>
    <div class="rdv-card">
        <div class="rdv-bande-<?= $s ?>"></div>
        <div class="rdv-body">
            <div class="rdv-top">
                <span class="rdv-vehicule">🚗 <?= htmlspecialchars($r['vehicule']) ?></span>
                <?php if ($s === 'confirme'): ?>
                    <span class="badge badge-confirme">✅ Confirmé</span>
                <?php elseif ($s === 'annule'): ?>
                    <span class="badge badge-annule">❌ Annulé</span>
                <?php else: ?>
                    <span class="badge badge-attente">⏳ En attente</span>
                <?php endif; ?>
            </div>

            <div class="rdv-date">
                📅 <?= dateFR($r['date_intervention']) ?>
                · 🕐 <?= htmlspecialchars(substr($r['heure'], 0, 5)) ?>
            </div>

            <?php if ($r['Probleme']): ?>
            <div class="rdv-probleme">🔧 <?= htmlspecialchars($r['Probleme']) ?></div>
            <?php endif; ?>

            <div class="rdv-message <?= $s ?>">
                <?php if ($s === 'confirme'): ?>
                    ✅ Votre rendez-vous est <strong>confirmé</strong> par l'atelier. À bientôt !
                <?php elseif ($s === 'annule'): ?>
                    ❌ Votre rendez-vous a été <strong>annulé</strong> par l'atelier. Contactez-nous pour en savoir plus.
                <?php else: ?>
                    ⏳ Votre demande est en attente de <strong>confirmation</strong> par l'atelier.
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

</div>

<a class="btn-reserver" href="reservation.php">📅 Prendre un nouveau rendez-vous</a>

<footer>© 2026 Méca Brocéliande</footer>

</body>
</html>
