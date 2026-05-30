<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'prof') {
    header('Location: login.php');
    exit;
}

// ── Connexion BDD ──
$host   = 'meca-mysql';
$dbname = 'Meca';
$user   = 'root';
$pass   = 'root';

$pdo = new PDO("mysql:host=$host;port=3306;dbname=$dbname;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ── Recherche ──
$search = trim($_GET['q'] ?? '');

$sql = "
    SELECT
        i.id_intervention,
        i.date_intervention,
        i.`heure_de_préstation`,
        i.Probleme,
        i.commentaire,
        v.vin,
        CONCAT(v.marque, ' ', COALESCE(v.modele,'')) AS marque_modele,
        v.immatriculation,
        v.km,
        v.type_veh,
        c.prenom            AS client_prenom,
        c.nom               AS client_nom,
        c.`numéro`          AS client_tel,
        p.designation       AS prestation_nom,
        p.prix              AS prestation_prix
    FROM intervention i
    LEFT JOIN Vehicules   v ON v.id_vehicules   = i.vehicule_id
    LEFT JOIN Clients     c ON c.id_clients     = v.client_id
    LEFT JOIN Prestation  p ON p.id_prestation  = i.prestation_id
    WHERE i.source = 'ordre'
      AND c.prenom IS NOT NULL AND c.prenom != ''
      AND c.nom    IS NOT NULL AND c.nom    != ''
      AND v.marque IS NOT NULL AND v.marque != ''
      AND v.vin    IS NOT NULL AND v.vin    != ''
      AND i.Probleme IS NOT NULL AND i.Probleme != ''
      AND v.immatriculation IS NOT NULL AND v.immatriculation != ''
      AND v.km IS NOT NULL
";

$params = [];

if ($search) {
    $sql .= " AND (c.prenom LIKE :q OR c.nom LIKE :q OR v.vin LIKE :q
               OR v.marque LIKE :q OR v.modele LIKE :q OR v.immatriculation LIKE :q
               OR i.Probleme LIKE :q)";
    $params[':q'] = '%' . $search . '%';
}

$sql .= ' ORDER BY i.date_intervention DESC, i.id_intervention DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ordres = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($ordres);

function dateFR($date) {
    if (!$date) return '—';
    $ts   = strtotime($date);
    $mois = ['','Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];
    return date('d', $ts) . ' ' . $mois[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation des OR — Méca Brocéliande</title>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, sans-serif;
            background-color: #f1f2f3;
            min-height: 100vh;
        }

        /* ── HEADER ── */
        header {
            background: linear-gradient(135deg, #1e3a8a, #111827);
            color: white;
            padding: 20px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        header h1 { font-size: 22px; margin: 0; }

        .header-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .badge-prof {
            background: rgba(255,255,255,0.15);
            padding: 8px 16px;
            border-radius: 999px;
            font-weight: bold;
            font-size: 13px;
        }

        .btn-retour {
            padding: 8px 18px;
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 50px;
            font-size: 13px;
            text-decoration: none;
            transition: background 0.2s;
        }

        .btn-retour:hover { background: rgba(255,255,255,0.25); }

        /* ── ONGLETS ── */
        .tabs {
            background: white;
            border-bottom: 2px solid #e5e7eb;
            display: flex;
            gap: 0;
            padding: 0 40px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        }

        .tab {
            padding: 16px 24px;
            font-size: 14px;
            font-weight: 600;
            color: #6b7280;
            text-decoration: none;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab:hover { color: #2563eb; }

        .tab.active {
            color: #27ae60;
            border-bottom-color: #27ae60;
        }

        /* ── STATS ── */
        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 24px;
            flex-wrap: wrap;
            padding: 28px 20px 10px;
        }

        .stat-card {
            background: white;
            border-radius: 25px;
            padding: 18px 36px;
            text-align: center;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            min-width: 160px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #27ae60;
        }

        .stat-label {
            font-size: 12px;
            color: #777;
            margin-top: 4px;
        }

        /* ── BARRE RECHERCHE ── */
        .search-bar {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            padding: 18px 20px 10px;
        }

        .search-form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            justify-content: center;
        }

        .search-input {
            padding: 10px 20px;
            border: 1px solid #ccc;
            border-radius: 50px;
            font-size: 14px;
            font-family: Arial, sans-serif;
            width: 320px;
            outline: none;
            transition: border-color 0.2s;
        }

        .search-input:focus { border-color: #27ae60; }

        .btn-search {
            padding: 10px 24px;
            background-color: #27ae60;
            color: white;
            border: none;
            border-radius: 50px;
            font-family: Arial, sans-serif;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-search:hover { background-color: #219150; }

        /* ── LISTE ── */
        .liste {
            max-width: 960px;
            margin: 16px auto 100px;
            padding: 0 20px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        /* ── CARTE OR ── */
        .or-card {
            background: white;
            border-radius: 25px;
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
            display: grid;
            grid-template-columns: 10px 1fr auto;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .or-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.13);
        }

        /* Bande statut verte */
        .or-statut-bande {
            width: 10px;
            background: #27ae60;
        }

        /* Corps */
        .or-body {
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .or-top {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .or-id {
            font-size: 11px;
            background: #f0f0f0;
            color: #555;
            padding: 2px 10px;
            border-radius: 20px;
            font-weight: bold;
        }

        .or-client {
            font-size: 15px;
            font-weight: bold;
            color: #111;
        }

        .or-date {
            font-size: 12px;
            color: #aaa;
            margin-left: auto;
        }

        .or-vehicule {
            font-size: 13px;
            color: #666;
        }

        .or-vehicule::before { content: '🚗 '; }

        .or-vin {
            font-size: 11px;
            color: #aaa;
            font-family: monospace;
            letter-spacing: 0.05em;
        }

        .or-probleme {
            display: inline-block;
            background: #fff3eb;
            color: #c44d00;
            font-size: 12px;
            padding: 3px 12px;
            border-radius: 20px;
            width: fit-content;
            margin-top: 2px;
        }

        .or-commentaire {
            font-size: 12px;
            color: #999;
            font-style: italic;
        }

        /* Badge complet */
        .badge-complet {
            display: inline-block;
            font-size: 11px;
            font-weight: bold;
            padding: 3px 10px;
            border-radius: 20px;
            background: #e6f9f0;
            color: #27ae60;
        }

        /* Actions droite */
        .or-actions {
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
            gap: 8px;
        }

        .btn-ouvrir {
            padding: 8px 18px;
            background-color: #2563eb;
            color: white;
            border: none;
            border-radius: 50px;
            font-family: Arial, sans-serif;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.2s;
            white-space: nowrap;
        }

        .btn-ouvrir:hover { background-color: #1d4ed8; }

        .btn-valider {
            padding: 8px 18px;
            background-color: #27ae60;
            color: white;
            border: none;
            border-radius: 50px;
            font-family: Arial, sans-serif;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.2s;
            white-space: nowrap;
        }

        .btn-valider:hover { background-color: #219150; }

        /* Notification de validation */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #27ae60;
            color: white;
            padding: 14px 24px;
            border-radius: 50px;
            font-size: 14px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.3s, transform 0.3s;
            z-index: 1000;
            pointer-events: none;
        }

        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        /* ── VIDE ── */
        .vide {
            text-align: center;
            padding: 60px 20px;
            color: #bbb;
            font-size: 16px;
        }

        .vide-icon { font-size: 48px; margin-bottom: 14px; }

        /* ── FOOTER ── */
        footer {
            position: fixed;
            bottom: 19px;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 13px;
            color: #999;
        }

        @media (max-width: 600px) {
            .or-card { grid-template-columns: 8px 1fr; }
            .or-actions { display: none; }
            .tabs { padding: 0 16px; }
            header { padding: 16px 20px; }
        }
    </style>
</head>
<body>

<!-- Header -->
<header>
    <h1>✅ Validation des interventions — Méca Brocéliande</h1>
    <div class="header-right">
        <span class="badge-prof">Espace Professeur</span>
        <a class="btn-retour" href="prof.php">← Retour</a>
    </div>
</header>

<!-- Onglets -->
<div class="tabs">
    <a class="tab" href="validation.php">
        ✅ Ordres complets à valider
        <span style="background:#e6f9f0;color:#27ae60;border-radius:20px;padding:1px 8px;font-size:11px;"><?= $total ?></span>
    </a>
    <a class="tab" href="recherche_or.php">
        🔍 Tous les ordres
    </a>
</div>

<!-- Stats -->
<div class="stats-bar">
    <div class="stat-card">
        <div class="stat-value"><?= $total ?></div>
        <div class="stat-label">OR complets à valider</div>
    </div>
</div>

<!-- Recherche -->
<div class="search-bar">
    <form class="search-form" method="GET" action="validation.php">
        <input class="search-input" type="text" name="q"
               value="<?= htmlspecialchars($search) ?>"
               placeholder="Rechercher client, VIN, véhicule, problème…">
        <button class="btn-search" type="submit">🔍 Rechercher</button>
        <?php if ($search): ?>
            <a href="validation.php" style="font-size:13px;color:#888;text-decoration:none;">✕ Effacer</a>
        <?php endif; ?>
    </form>
</div>

<!-- Info -->
<div style="text-align:center;font-size:13px;color:#6b7280;margin-bottom:8px;">
    Seuls les ordres de réparation <strong style="color:#27ae60;">complets</strong> (client + véhicule + VIN + immatriculation + kilométrage + problème renseignés) sont affichés ici.
</div>

<!-- Liste des OR complets -->
<div class="liste">

<?php if (empty($ordres)): ?>
    <div class="vide">
        <div class="vide-icon">✅</div>
        <?php if ($search): ?>
            Aucun OR complet trouvé pour "<strong><?= htmlspecialchars($search) ?></strong>"
        <?php else: ?>
            Aucun ordre de réparation complet à valider pour le moment.
        <?php endif; ?>
    </div>

<?php else: ?>
    <?php foreach ($ordres as $or):
        $client = trim(($or['client_prenom'] ?? '') . ' ' . ($or['client_nom'] ?? ''));
        $vehic  = trim($or['marque_modele'] ?? '');
        $immat  = trim($or['immatriculation'] ?? '');
        $vin    = trim($or['vin'] ?? '');
        $pb     = trim($or['Probleme'] ?? '');
        $comm   = trim($or['commentaire'] ?? '');
        $km     = $or['km'] ?? '';
        $tel    = $or['client_tel'] ?? '';
        $heure  = $or['heure_de_préstation'] ?? '';
        $prix   = $or['prestation_prix'] ?? '';
    ?>
    <div class="or-card" id="card-<?= $or['id_intervention'] ?>">
        <!-- Bande verte -->
        <div class="or-statut-bande"></div>

        <!-- Corps -->
        <div class="or-body">
            <div class="or-top">
                <span class="or-id">#<?= $or['id_intervention'] ?></span>
                <span class="or-client"><?= htmlspecialchars($client) ?></span>
                <span class="badge-complet">✅ Complet</span>
                <span class="or-date">
                    📅 <?= dateFR($or['date_intervention']) ?>
                    <?= $heure ? ' · ' . htmlspecialchars(substr($heure, 0, 5)) : '' ?>
                </span>
            </div>

            <?php if ($vehic || $immat): ?>
            <div class="or-vehicule">
                <?= htmlspecialchars($vehic ?: '—') ?>
                <?= $immat ? ' — <strong>' . htmlspecialchars($immat) . '</strong>' : '' ?>
                <?= $km    ? ' · <span style="color:#aaa;font-size:11px;">' . number_format((int)$km, 0, ',', ' ') . ' km</span>' : '' ?>
            </div>
            <?php endif; ?>

            <?php if ($vin): ?>
            <div class="or-vin">VIN : <?= htmlspecialchars($vin) ?></div>
            <?php endif; ?>

            <?php if ($pb): ?>
            <div class="or-probleme">🔧 <?= htmlspecialchars($pb) ?></div>
            <?php elseif ($or['prestation_nom']): ?>
            <div class="or-probleme">🔧 <?= htmlspecialchars($or['prestation_nom']) ?>
                <?= $prix ? ' — ' . number_format((float)$prix, 2, ',', ' ') . ' €' : '' ?>
            </div>
            <?php endif; ?>

            <?php if ($comm): ?>
            <div class="or-commentaire">Travaux : <?= htmlspecialchars(mb_substr($comm, 0, 150)) ?><?= mb_strlen($comm) > 150 ? '…' : '' ?></div>
            <?php endif; ?>

            <?php if ($tel): ?>
            <div style="font-size:12px;color:#aaa;">📞 <?= htmlspecialchars($tel) ?></div>
            <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="or-actions">
            <a href="ordre_reparation.php?intervention_id=<?= $or['id_intervention'] ?>"
               class="btn-ouvrir">✏️ Ouvrir / Modifier</a>
            <button class="btn-valider"
                    onclick="validerOR(<?= $or['id_intervention'] ?>, this)">
                ✅ Valider
            </button>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

</div>

<footer>
    <p>© 2026 Méca Brocéliande — Espace Professeur</p>
</footer>

<!-- Toast notification -->
<div class="toast" id="toast"></div>

<script>
function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}

function validerOR(id, btn) {
    if (!confirm('Valider cet ordre de réparation #' + id + ' ?')) return;

    btn.disabled = true;
    btn.textContent = '⏳ En cours…';

    fetch('valider_or.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'intervention_id=' + id
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const card = document.getElementById('card-' + id);
            card.style.transition = 'opacity 0.4s, transform 0.4s';
            card.style.opacity    = '0';
            card.style.transform  = 'translateX(30px)';
            setTimeout(() => card.remove(), 400);
            showToast('✅ OR #' + id + ' validé avec succès !');

            // Mise à jour compteur dans l'onglet
            const badge = document.querySelector('.tab .badge-count');
            if (badge) {
                const n = parseInt(badge.textContent) - 1;
                badge.textContent = n >= 0 ? n : 0;
            }
        } else {
            btn.disabled = false;
            btn.textContent = '✅ Valider';
            alert('Erreur : ' + (data.error || 'inconnue'));
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.textContent = '✅ Valider';
        alert('Erreur réseau.');
    });
}
</script>

</body>
</html>
