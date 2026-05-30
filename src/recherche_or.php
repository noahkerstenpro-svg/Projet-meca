<?php
session_start();

if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['prof', 'eleve'])) {
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

// ════════════════════════════════════════════
// ENDPOINT AJAX — recherche client (utilisé par ordre_reparation.php)
// ════════════════════════════════════════════
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $prenom = trim($_GET['prenom'] ?? '');
    $nom    = trim($_GET['nom']    ?? '');

    $stmt = $pdo->prepare("
        SELECT id_clients, prenom, nom, adresse_postal, `numéro` AS numero, adresse_mail
        FROM Clients
        WHERE (:prenom = '' OR prenom LIKE :prenom2)
          AND (:nom    = '' OR nom    LIKE :nom2)
        ORDER BY nom, prenom
        LIMIT 10
    ");
    $stmt->execute([
        ':prenom'  => $prenom,
        ':prenom2' => $prenom . '%',
        ':nom'     => $nom,
        ':nom2'    => $nom . '%',
    ]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// ════════════════════════════════════════════
// CHARGEMENT DES OR depuis la BDD
// ════════════════════════════════════════════
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
";

$params = [];
// Les OR validés n'apparaissent que dans validation.php
$where  = ["i.source = 'ordre'", "i.statut != 'valide'"];

if ($search) {
    $where[] = "(c.prenom LIKE :q OR c.nom LIKE :q OR v.vin LIKE :q
                 OR v.marque LIKE :q OR v.modele LIKE :q OR v.immatriculation LIKE :q
                 OR i.Probleme LIKE :q)";
    $params[':q'] = '%' . $search . '%';
}

if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY i.date_intervention DESC, i.id_intervention DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ordres = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcul statut complet / incomplet
function statutOR($or) {
    // Champs client
    $clientOk = !empty(trim($or['client_prenom'] ?? '')) && !empty(trim($or['client_nom'] ?? ''));

    // Champs véhicule essentiels
    $vehiculeOk = !empty(trim($or['marque_modele'] ?? '')) && !empty(trim($or['vin'] ?? ''));

    // Tous les champs (client + véhicule + problème + immat + km)
    $toutOk = $clientOk && $vehiculeOk
           && !empty(trim($or['Probleme']        ?? ''))
           && !empty(trim($or['immatriculation'] ?? ''))
           && !empty(trim($or['km']              ?? ''));

    if ($toutOk)    return 'complet';   // ✅ tout est renseigné
    if ($clientOk && $vehiculeOk) return 'partiel';  // 🟡 client + véhicule ok
    return 'incomplet';                 // 🔴 client ou véhicule manquant
}

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
    <title>Suivi des réparations — Méca Brocéliande</title>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, sans-serif;
            background-color: #f1f2f3;
            min-height: 100vh;
        }

        /* ── HEADER ── */
        header {
            background-color: #525151;
            color: white;
            padding: 20px;
            text-align: center;
        }

        header h1 { font-size: 24px; margin: 0; }

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
            min-width: 140px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #eb5e00;
        }

        .stat-value.vert  { color: #27ae60; }
        .stat-value.rouge { color: #e74c3c; }

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
            width: 300px;
            outline: none;
            transition: border-color 0.2s;
        }

        .search-input:focus { border-color: #eb5e00; }

        .btn-search {
            padding: 10px 24px;
            background-color: #eb5e00;
            color: white;
            border: none;
            border-radius: 50px;
            font-family: Arial, sans-serif;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-search:hover { background-color: #d65300; }

        .filter-btn {
            padding: 9px 20px;
            border-radius: 50px;
            border: 1px solid #ccc;
            background: white;
            font-family: Arial, sans-serif;
            font-size: 13px;
            cursor: pointer;
            color: #525151;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background-color: #eb5e00;
            border-color: #eb5e00;
            color: white;
        }

        .btn-nouveau {
            padding: 10px 24px;
            background-color: #525151;
            color: white;
            border: none;
            border-radius: 50px;
            font-family: Arial, sans-serif;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.2s;
        }

        .btn-nouveau:hover { background-color: #333; }

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

        /* Bande statut */
        .or-statut-bande {
            width: 10px;
        }

        .or-card.complet   .or-statut-bande { background: #27ae60; }
        .or-card.partiel   .or-statut-bande { background: #f39c12; }
        .or-card.incomplet .or-statut-bande { background: #e74c3c; }

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

        .or-probleme.vide-text {
            background: #f5f5f5;
            color: #bbb;
        }

        .or-commentaire {
            font-size: 12px;
            color: #999;
            font-style: italic;
        }

        /* Badges statut */
        .badge-statut {
            display: inline-block;
            font-size: 11px;
            font-weight: bold;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .badge-complet   { background: #e6f9f0; color: #27ae60; }
        .badge-partiel   { background: #fff8e6; color: #f39c12; }
        .badge-incomplet { background: #fdecea; color: #e74c3c; }

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
            background-color: #eb5e00;
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

        .btn-ouvrir:hover { background-color: #d65300; }

        .btn-supprimer {
            padding: 6px 14px;
            background: none;
            color: #e74c3c;
            border: 1px solid #fecaca;
            border-radius: 50px;
            font-family: Arial, sans-serif;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-supprimer:hover {
            background: #fdecea;
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
        }
    </style>
</head>
<body>

<header>
    <h1>Atelier Mécanique - Bac Professionnel de Brocéliande</h1>
</header>

<?php
$total = count($ordres);
?>

<!-- Stats -->
<div class="stats-bar">
    <div class="stat-card">
        <div class="stat-value"><?= $total ?></div>
        <div class="stat-label">Ordres en cours</div>
    </div>
</div>

<!-- Recherche -->
<div class="search-bar">
    <form class="search-form" method="GET" action="recherche_or.php">
        <input class="search-input" type="text" name="q"
               value="<?= htmlspecialchars($search) ?>"
               placeholder="Rechercher client, VIN, véhicule, problème…">
        <button class="btn-search" type="submit">🔍 Rechercher</button>
        <?php if ($search): ?>
            <a href="recherche_or.php" style="font-size:13px;color:#888;text-decoration:none;">✕ Effacer</a>
        <?php endif; ?>
    </form>
</div>

<!-- Liste des OR -->
<div class="liste">

<?php if (empty($ordres)): ?>
    <div class="vide">
        <div class="vide-icon">🔧</div>
        <?php if ($search): ?>
            Aucun ordre trouvé pour "<strong><?= htmlspecialchars($search) ?></strong>"
        <?php else: ?>
            Aucun ordre de réparation enregistré
        <?php endif; ?>
    </div>

<?php else: ?>
    <?php foreach ($ordres as $or):
        $statut = statutOR($or);
        $client  = trim(($or['client_prenom'] ?? '') . ' ' . ($or['client_nom'] ?? ''));
        $vehic   = trim($or['marque_modele'] ?? '');
        $immat   = trim($or['immatriculation'] ?? '');
        $vin     = trim($or['vin'] ?? '');
        $pb      = trim($or['Probleme'] ?? '');
        $comm    = trim($or['commentaire'] ?? '');
    ?>
    <div class="or-card <?= $statut ?>">
        <!-- Bande statut -->
        <div class="or-statut-bande"></div>

        <!-- Corps -->
        <div class="or-body">
            <div class="or-top">
                <span class="or-id">#<?= $or['id_intervention'] ?></span>
                <span class="or-client">
                    <?= $client ? htmlspecialchars($client) : '<span style="color:#ccc;">Client inconnu</span>' ?>
                </span>
                <span class="or-date">📅 <?= dateFR($or['date_intervention']) ?></span>
            </div>

            <?php if ($vehic || $immat): ?>
            <div class="or-vehicule">
                <?= htmlspecialchars($vehic ?: '—') ?>
                <?= $immat ? ' — <strong>' . htmlspecialchars($immat) . '</strong>' : '' ?>
            </div>
            <?php endif; ?>

            <?php if ($vin): ?>
            <div class="or-vin">VIN : <?= htmlspecialchars($vin) ?></div>
            <?php endif; ?>

            <?php if ($pb): ?>
                <div class="or-probleme">🔧 <?= htmlspecialchars($pb) ?></div>
            <?php elseif ($or['prestation_nom']): ?>
                <div class="or-probleme">🔧 <?= htmlspecialchars($or['prestation_nom']) ?></div>
            <?php else: ?>
                <div class="or-probleme vide-text">Problème non renseigné</div>
            <?php endif; ?>

            <?php if ($comm): ?>
            <div class="or-commentaire">Travaux : <?= htmlspecialchars(mb_substr($comm, 0, 120)) ?><?= mb_strlen($comm) > 120 ? '…' : '' ?></div>
            <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="or-actions">
            <a href="ordre_reparation.php?intervention_id=<?= $or['id_intervention'] ?>"
               class="btn-ouvrir">✏️ Ouvrir / Modifier</a>
            <button class="btn-supprimer"
                    onclick="supprimerOR(<?= $or['id_intervention'] ?>, this)">
                🗑 Supprimer
            </button>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

</div>

<footer>
    <p>© 2026 Méca Brocéliande</p>
</footer>

<script>
function supprimerOR(id, btn) {
    if (!confirm('Supprimer cet ordre de réparation ? Cette action est irréversible.')) return;

    fetch('supprimer_or.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'intervention_id=' + id
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const card = btn.closest('.or-card');
            card.style.transition = 'opacity 0.3s, transform 0.3s';
            card.style.opacity = '0';
            card.style.transform = 'translateX(20px)';
            setTimeout(() => card.remove(), 300);
        } else {
            alert('Erreur lors de la suppression : ' + (data.error || 'inconnue'));
        }
    })
    .catch(() => alert('Erreur réseau.'));
}
</script>

</body>
</html>
