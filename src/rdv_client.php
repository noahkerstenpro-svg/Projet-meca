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
            CONCAT(v.marque, ' ', COALESCE(v.modele,'')) AS vehicule,
            c.nom               AS client_nom,
            c.prenom            AS client_prenom,
            c.`numéro`          AS client_tel,
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

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

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

        header h1 {
            font-size: 24px;
            margin: 0;
        }

        /* ── STATS BAR ── */
        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
            padding: 30px 20px 10px;
        }

        .stat-card {
            background: white;
            border-radius: 25px;
            padding: 20px 40px;
            text-align: center;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            min-width: 160px;
        }

        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #eb5e00;
        }

        .stat-label {
            font-size: 13px;
            color: #777;
            margin-top: 4px;
        }

        /* ── RECHERCHE ── */
        .search-bar {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            padding: 20px;
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

        .search-input:focus {
            border-color: #eb5e00;
        }

        .filter-btn {
            padding: 10px 22px;
            border-radius: 50px;
            border: 1px solid #ccc;
            background: white;
            font-family: Arial, sans-serif;
            font-size: 13px;
            cursor: pointer;
            transition: background-color 0.2s;
            color: #525151;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background-color: #eb5e00;
            border-color: #eb5e00;
            color: white;
        }

        /* ── AGENDA ── */
        .agenda {
            max-width: 860px;
            margin: 0 auto;
            padding: 10px 20px 100px;
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        /* ── BLOC JOUR ── */
        .jour-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 12px;
        }

        .jour-date {
            font-size: 18px;
            font-weight: bold;
            color: #525151;
        }

        .jour-date .jour-nom {
            color: #eb5e00;
        }

        .jour-count {
            background: #525151;
            color: white;
            font-size: 11px;
            font-weight: bold;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .jour-line {
            flex: 1;
            height: 1px;
            background: #ddd;
        }

        /* ── CARTE RDV ── */
        .timeline {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .rdv-card {
            display: grid;
            grid-template-columns: 80px 1fr auto;
            background: white;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .rdv-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.13);
        }

        /* Bande heure */
        .rdv-heure {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 18px 10px;
            font-weight: bold;
            font-size: 18px;
            gap: 4px;
        }

        .rdv-card.matin .rdv-heure {
            background: #fff3eb;
            color: #eb5e00;
            border-right: 3px solid #ffd4b0;
        }

        .rdv-card.aprem .rdv-heure {
            background: #f0f6ff;
            color: #2255bb;
            border-right: 3px solid #b0ccff;
        }

        .heure-label {
            font-size: 10px;
            font-weight: normal;
            color: #aaa;
            text-transform: uppercase;
        }

        /* Corps */
        .rdv-body {
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .rdv-client {
            font-size: 15px;
            font-weight: bold;
            color: #111;
        }

        .rdv-vehicule {
            font-size: 13px;
            color: #888;
        }

        .rdv-vehicule::before {
            content: '🚗 ';
        }

        .rdv-prestation {
            display: inline-block;
            background: #fff3eb;
            color: #c44d00;
            font-size: 12px;
            padding: 4px 12px;
            border-radius: 20px;
            margin-top: 2px;
            width: fit-content;
        }

        .rdv-prestation.autre {
            background: #f0f0f0;
            color: #555;
        }

        /* Meta droite */
        .rdv-meta {
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: space-between;
            gap: 6px;
            min-width: 120px;
        }

        .rdv-tel {
            font-size: 12px;
            color: #aaa;
        }

        .rdv-ref {
            font-size: 11px;
            color: #bbb;
        }

        .rdv-prix {
            font-size: 18px;
            font-weight: bold;
            color: #eb5e00;
        }

        .rdv-prix.gratuit {
            font-size: 12px;
            color: #aaa;
            font-weight: normal;
        }

        /* ── VIDE ── */
        .vide {
            text-align: center;
            padding: 60px 20px;
            color: #bbb;
            font-size: 16px;
        }

        .vide-icon {
            font-size: 48px;
            margin-bottom: 14px;
        }

        /* ── ERREUR ── */
        .erreur-bdd {
            margin: 40px auto;
            max-width: 600px;
            background: #fdecea;
            color: #b00020;
            border: 1px solid #f5c2c7;
            border-radius: 25px;
            padding: 20px;
            font-size: 14px;
        }

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
            .rdv-card { grid-template-columns: 70px 1fr; }
            .rdv-meta { display: none; }
        }
    </style>
</head>
<body>

<header>
    <h1>Atelier Mécanique - Bac Professionnel de Brocéliande</h1>
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
    <div class="stat-card">
        <div class="stat-value"><?= $totalRdv ?></div>
        <div class="stat-label">RDV au total</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $totalJours ?></div>
        <div class="stat-label">Jours planifiés</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $rdvFuturs ?></div>
        <div class="stat-label">À venir</div>
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
        <div>Aucun rendez-vous enregistré</div>
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

<footer>
    <p>© 2026 Méca Brocéliande</p>
</footer>

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
