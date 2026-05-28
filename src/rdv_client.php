<?php
session_start();

// --- Connexion BDD ---
$host   = 'meca-mysql';
$dbname = 'Meca';
$user   = 'root';
$pass   = 'root';

try {
    $pdo = new PDO("mysql:host=$host;port=3306;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer tous les RDV avec infos client, véhicule, prestation
    $stmt = $pdo->query("
        SELECT
            i.id_intervention,
            i.date_intervention,
            i.`heure_de_préstation`,
            i.Probleme,
            i.commentaire,
            v.`marque/modèle`   AS vehicule,
            c.nom               AS client_nom,
            c.prenom            AS client_prenom,
            c.telephone         AS client_tel,
            p.designation       AS prestation_nom,
            p.prix              AS prestation_prix,
            p.reference         AS prestation_ref
        FROM intervention i
        JOIN Vehicules v  ON v.id_vehicules  = i.vehicule_id
        JOIN Clients   c  ON c.id_clients    = v.client_id
        LEFT JOIN Prestation p ON p.id_prestation = i.prestation_id
        ORDER BY i.date_intervention ASC, i.`heure_de_préstation` ASC
    ");
    $rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Grouper par date
    $rdvParDate = [];
    foreach ($rdvs as $rdv) {
        $rdvParDate[$rdv['date_intervention']][] = $rdv;
    }

} catch (PDOException $e) {
    $erreur = "Erreur BDD : " . $e->getMessage();
    $rdvParDate = [];
}

// Formater une date FR
function dateFR($date) {
    $ts = strtotime($date);
    $jours = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
    $mois  = ['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
    return $jours[date('w', $ts)] . ' ' . date('j', $ts) . ' ' . $mois[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

// Couleur par heure
function couleurHeure($heure) {
    $h = (int)substr($heure, 0, 2);
    if ($h < 12) return 'matin';
    return 'aprem';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda RDV — Méca Brocéliande</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">

    <style>
        :root {
            --orange:    #eb5e00;
            --orange-dk: #c44d00;
            --grey-900:  #1a1a1a;
            --grey-800:  #2c2c2c;
            --grey-700:  #3e3e3e;
            --grey-200:  #e8e8e8;
            --grey-100:  #f4f4f4;
            --white:     #ffffff;
            --matin:     #fff7f0;
            --matin-bd:  #ffd4b0;
            --aprem:     #f0f6ff;
            --aprem-bd:  #b0ccff;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--grey-100);
            color: var(--grey-900);
            min-height: 100vh;
        }

        /* ── HEADER ── */
        header {
            background: var(--grey-900);
            padding: 0 40px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 3px solid var(--orange);
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .header-logo {
            width: 38px;
            height: 38px;
            background: var(--orange);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 18px;
            color: white;
        }

        .header-title {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 18px;
            color: var(--white);
            letter-spacing: -0.3px;
        }

        .header-title span {
            color: var(--orange);
        }

        .header-meta {
            font-size: 13px;
            color: #888;
        }

        /* ── STATS BAR ── */
        .stats-bar {
            background: var(--white);
            border-bottom: 1px solid var(--grey-200);
            padding: 20px 40px;
            display: flex;
            gap: 40px;
            align-items: center;
        }

        .stat {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .stat-value {
            font-family: 'Syne', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: var(--orange);
            line-height: 1;
        }

        .stat-label {
            font-size: 12px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-divider {
            width: 1px;
            height: 40px;
            background: var(--grey-200);
        }

        /* ── RECHERCHE ── */
        .search-bar {
            padding: 20px 40px;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .search-input {
            flex: 1;
            max-width: 380px;
            padding: 10px 16px 10px 40px;
            border: 2px solid var(--grey-200);
            border-radius: 30px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            background: var(--white) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23999' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.099zm-5.242 1.656a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11z'/%3E%3C/svg%3E") no-repeat 14px center;
            outline: none;
            transition: border-color .2s;
        }

        .search-input:focus { border-color: var(--orange); }

        .filter-btn {
            padding: 10px 18px;
            border-radius: 30px;
            border: 2px solid var(--grey-200);
            background: var(--white);
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            cursor: pointer;
            transition: all .2s;
            color: var(--grey-700);
        }

        .filter-btn:hover, .filter-btn.active {
            background: var(--orange);
            border-color: var(--orange);
            color: white;
        }

        /* ── AGENDA ── */
        .agenda {
            padding: 0 40px 60px;
            display: flex;
            flex-direction: column;
            gap: 32px;
        }

        /* ── BLOC JOUR ── */
        .jour-bloc {
            animation: fadeUp .4s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .jour-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 14px;
        }

        .jour-date {
            font-family: 'Syne', sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--grey-900);
        }

        .jour-date .jour-nom {
            color: var(--orange);
        }

        .jour-count {
            background: var(--grey-900);
            color: white;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
            font-family: 'Syne', sans-serif;
            letter-spacing: 0.3px;
        }

        .jour-line {
            flex: 1;
            height: 1px;
            background: var(--grey-200);
        }

        /* ── TIMELINE ── */
        .timeline {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        /* ── CARTE RDV ── */
        .rdv-card {
            display: grid;
            grid-template-columns: 80px 1fr auto;
            align-items: start;
            gap: 0;
            background: var(--white);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: transform .2s, box-shadow .2s;
            cursor: default;
        }

        .rdv-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.10);
        }

        /* Bande heure */
        .rdv-heure {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px 10px;
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 20px;
            line-height: 1;
            gap: 4px;
        }

        .rdv-card.matin .rdv-heure {
            background: var(--matin);
            color: #c44d00;
            border-right: 3px solid var(--matin-bd);
        }

        .rdv-card.aprem .rdv-heure {
            background: var(--aprem);
            color: #2255bb;
            border-right: 3px solid var(--aprem-bd);
        }

        .rdv-heure .heure-label {
            font-size: 10px;
            font-weight: 400;
            font-family: 'DM Sans', sans-serif;
            opacity: 0.7;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Corps de la carte */
        .rdv-body {
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .rdv-client {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 15px;
            color: var(--grey-900);
        }

        .rdv-vehicule {
            font-size: 13px;
            color: #888;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .rdv-vehicule::before {
            content: '🚗';
            font-size: 14px;
        }

        .rdv-prestation {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff3eb;
            color: var(--orange-dk);
            font-size: 12px;
            font-weight: 500;
            padding: 4px 10px;
            border-radius: 20px;
            width: fit-content;
            margin-top: 2px;
        }

        .rdv-prestation.autre {
            background: #f0f0f0;
            color: #555;
        }

        /* Infos droite */
        .rdv-meta {
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: space-between;
            gap: 8px;
            min-width: 120px;
        }

        .rdv-tel {
            font-size: 12px;
            color: #aaa;
            text-align: right;
        }

        .rdv-prix {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 18px;
            color: var(--orange);
        }

        .rdv-prix.gratuit {
            font-size: 12px;
            color: #aaa;
            font-weight: 400;
            font-family: 'DM Sans', sans-serif;
        }

        .rdv-ref {
            font-size: 11px;
            color: #bbb;
            font-family: 'Syne', sans-serif;
            letter-spacing: 0.5px;
        }

        /* ── VIDE ── */
        .vide {
            text-align: center;
            padding: 80px 20px;
            color: #bbb;
        }

        .vide-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .vide-text {
            font-family: 'Syne', sans-serif;
            font-size: 18px;
            color: #ccc;
        }

        /* ── ERREUR ── */
        .erreur-bdd {
            margin: 40px;
            background: #fdecea;
            color: #b00020;
            border: 1px solid #f5c2c7;
            border-radius: 12px;
            padding: 20px;
            font-size: 14px;
        }

        /* ── FOOTER ── */
        footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #bbb;
            border-top: 1px solid var(--grey-200);
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 600px) {
            header, .stats-bar, .search-bar, .agenda { padding-left: 16px; padding-right: 16px; }
            .stats-bar { gap: 20px; flex-wrap: wrap; }
            .rdv-card { grid-template-columns: 70px 1fr; }
            .rdv-meta { display: none; }
        }
    </style>
</head>
<body>

<header>
    <div class="header-brand">
        <div class="header-logo">MB</div>
        <div class="header-title">Méca <span>Brocéliande</span></div>
    </div>
    <div class="header-meta">Agenda des rendez-vous</div>
</header>

<?php if (isset($erreur)): ?>
    <div class="erreur-bdd"><?= htmlspecialchars($erreur) ?></div>
<?php else: ?>

<!-- Stats -->
<div class="stats-bar">
    <?php
        $totalRdv   = array_sum(array_map('count', $rdvParDate));
        $totalJours = count($rdvParDate);
        $today      = date('Y-m-d');
        $rdvFuturs  = 0;
        foreach ($rdvParDate as $date => $list) {
            if ($date >= $today) $rdvFuturs += count($list);
        }
    ?>
    <div class="stat">
        <span class="stat-value"><?= $totalRdv ?></span>
        <span class="stat-label">RDV au total</span>
    </div>
    <div class="stat-divider"></div>
    <div class="stat">
        <span class="stat-value"><?= $totalJours ?></span>
        <span class="stat-label">Jours planifiés</span>
    </div>
    <div class="stat-divider"></div>
    <div class="stat">
        <span class="stat-value"><?= $rdvFuturs ?></span>
        <span class="stat-label">À venir</span>
    </div>
</div>

<!-- Barre de recherche -->
<div class="search-bar">
    <input class="search-input" type="text" id="recherche" placeholder="Rechercher un client, véhicule, prestation…" oninput="filtrer()">
    <button class="filter-btn active" onclick="setFiltre('tous', this)">Tous</button>
    <button class="filter-btn" onclick="setFiltre('matin', this)">Matin</button>
    <button class="filter-btn" onclick="setFiltre('aprem', this)">Après-midi</button>
</div>

<!-- Agenda -->
<div class="agenda" id="agenda">

<?php if (empty($rdvParDate)): ?>
    <div class="vide">
        <div class="vide-icon">📅</div>
        <div class="vide-text">Aucun rendez-vous enregistré</div>
    </div>
<?php else: ?>

<?php foreach ($rdvParDate as $date => $rdvs): ?>
    <div class="jour-bloc" data-date="<?= $date ?>">
        <div class="jour-header">
            <div class="jour-date">
                <?php
                    $parts = explode(' ', dateFR($date));
                    echo '<span class="jour-nom">' . $parts[0] . '</span> ' . implode(' ', array_slice($parts, 1));
                ?>
            </div>
            <span class="jour-count"><?= count($rdvs) ?> RDV</span>
            <div class="jour-line"></div>
        </div>

        <div class="timeline">
        <?php foreach ($rdvs as $r): ?>
            <?php $moment = couleurHeure($r['heure_de_préstation']); ?>
            <div class="rdv-card <?= $moment ?>"
                 data-client="<?= htmlspecialchars(strtolower($r['client_prenom'].' '.$r['client_nom'])) ?>"
                 data-vehicule="<?= htmlspecialchars(strtolower($r['vehicule'])) ?>"
                 data-prestation="<?= htmlspecialchars(strtolower($r['prestation_nom'] ?? $r['Probleme'] ?? '')) ?>"
                 data-moment="<?= $moment ?>">

                <!-- Heure -->
                <div class="rdv-heure">
                    <?= htmlspecialchars(substr($r['heure_de_préstation'], 0, 5)) ?>
                    <span class="heure-label"><?= $moment === 'matin' ? 'matin' : 'aprèm' ?></span>
                </div>

                <!-- Corps -->
                <div class="rdv-body">
                    <div class="rdv-client">
                        <?= htmlspecialchars($r['client_prenom'] . ' ' . $r['client_nom']) ?>
                    </div>
                    <div class="rdv-vehicule">
                        <?= htmlspecialchars($r['vehicule']) ?>
                    </div>
                    <?php if ($r['prestation_nom']): ?>
                        <div class="rdv-prestation">
                            🔧 <?= htmlspecialchars($r['prestation_nom']) ?>
                        </div>
                    <?php elseif ($r['Probleme']): ?>
                        <div class="rdv-prestation autre">
                            ✏️ <?= htmlspecialchars($r['Probleme']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Meta droite -->
                <div class="rdv-meta">
                    <?php if ($r['client_tel']): ?>
                        <div class="rdv-tel">📞 <?= htmlspecialchars($r['client_tel']) ?></div>
                    <?php endif; ?>

                    <?php if ($r['prestation_ref']): ?>
                        <div class="rdv-ref"><?= htmlspecialchars($r['prestation_ref']) ?></div>
                    <?php endif; ?>

                    <?php if ($r['prestation_prix']): ?>
                        <div class="rdv-prix"><?= number_format($r['prestation_prix'], 0) ?> €</div>
                    <?php else: ?>
                        <div class="rdv-prix gratuit">Prix à définir</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>

<?php endif; ?>
</div>

<?php endif; ?>

<footer>© 2026 Méca Brocéliande — Agenda interne</footer>

<script>
let filtreActif = 'tous';

function setFiltre(f, btn) {
    filtreActif = f;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    filtrer();
}

function filtrer() {
    const q = document.getElementById('recherche').value.toLowerCase().trim();

    document.querySelectorAll('.jour-bloc').forEach(jour => {
        let visibles = 0;

        jour.querySelectorAll('.rdv-card').forEach(card => {
            const matchFiltre = filtreActif === 'tous' || card.dataset.moment === filtreActif;
            const matchSearch = !q ||
                card.dataset.client.includes(q) ||
                card.dataset.vehicule.includes(q) ||
                card.dataset.prestation.includes(q);

            if (matchFiltre && matchSearch) {
                card.style.display = '';
                visibles++;
            } else {
                card.style.display = 'none';
            }
        });

        jour.style.display = visibles > 0 ? '' : 'none';
    });
}
</script>

</body>
</html>
