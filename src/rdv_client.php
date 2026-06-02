<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'prof') {
    header('Location: login.php');
    exit;
}

// --- Connexion BDD ---
$host   = 'meca-mysql';
$dbname = 'Meca';
$user   = 'root';
$pass   = 'root';

try {
    $pdo = new PDO("mysql:host=$host;port=3306;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("
        SELECT
            i.id_intervention,
            i.date_intervention,
            i.`heure_de_préstation`,
            i.Probleme,
            i.commentaire,
            i.statut,
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
        WHERE i.source = 'reservation'
        ORDER BY i.date_intervention ASC, i.`heure_de_préstation` ASC
    ");
    $rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Compter les en_attente pour le bandeau
    $nbEnAttente = 0;
    foreach ($rdvs as $r) {
        if (($r['statut'] ?? '') === 'en_attente') $nbEnAttente++;
    }

    // Grouper par date
    $rdvParDate = [];
    foreach ($rdvs as $rdv) {
        $rdvParDate[$rdv['date_intervention']][] = $rdv;
    }

} catch (PDOException $e) {
    $erreur = "Erreur BDD : " . $e->getMessage();
    $rdvParDate = [];
    $nbEnAttente = 0;
}

function dateFR($date) {
    $ts = strtotime($date);
    $jours = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
    $mois  = ['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
    return $jours[date('w', $ts)] . ' ' . date('j', $ts) . ' ' . $mois[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

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
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, sans-serif;
            background-color: #f1f2f3;
            min-height: 100vh;
        }

        header {
            background-color: #525151;
            color: white;
            padding: 20px;
            text-align: center;
        }

        header h1 { font-size: 24px; margin: 0; }

        /* ── BANDEAU EN ATTENTE ── */
        .bandeau-attente {
            background: #fff8e1;
            border-bottom: 2px solid #f59e0b;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            font-size: 15px;
            color: #92400e;
            font-weight: bold;
        }

        .bandeau-attente .badge-nb {
            background: #f59e0b;
            color: white;
            border-radius: 999px;
            padding: 2px 12px;
            font-size: 14px;
        }

        /* ── TOAST CONFIRMATION ── */
        .toast {
            display: none;
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 9999;
            background: #27ae60;
            color: white;
            padding: 14px 24px;
            border-radius: 16px;
            font-size: 15px;
            font-weight: bold;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease;
        }

        .toast.annule { background: #dc2626; }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(30px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        /* ── STATS BAR ── */
        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            padding: 24px 20px 10px;
        }

        .stat-card {
            background: white;
            border-radius: 25px;
            padding: 16px 32px;
            text-align: center;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            min-width: 130px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #eb5e00;
        }

        .stat-label {
            font-size: 12px;
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
            padding: 16px 20px;
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

        .filter-btn {
            padding: 10px 18px;
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

        .filter-btn.active-attente {
            background-color: #f59e0b;
            border-color: #f59e0b;
            color: white;
        }

        /* ── AGENDA ── */
        .agenda {
            max-width: 900px;
            margin: 0 auto;
            padding: 10px 20px 100px;
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

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

        .jour-date .jour-nom { color: #eb5e00; }

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
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }

        /* Bordure gauche colorée selon statut réservation */
        .rdv-card.statut-en_attente {
            border-left: 5px solid #f59e0b;
        }
        .rdv-card.statut-confirme {
            border-left: 5px solid #27ae60;
        }
        .rdv-card.statut-annule {
            border-left: 5px solid #dc2626;
            opacity: 0.6;
        }

        .rdv-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
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

        .rdv-body {
            padding: 14px 18px;
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

        .rdv-vehicule::before { content: '🚗 '; }

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

        /* ── BADGES STATUT ── */
        .badge-statut {
            display: inline-block;
            font-size: 11px;
            font-weight: bold;
            padding: 2px 10px;
            border-radius: 20px;
            margin-left: 8px;
            vertical-align: middle;
        }

        .badge-en-cours   { background: #dbeafe; color: #2563eb; }
        .badge-termine    { background: #fef3c7; color: #b45309; }
        .badge-valide     { background: #e6f9f0; color: #27ae60; }
        .badge-en_attente { background: #fff3cd; color: #92400e; }
        .badge-confirme   { background: #e6f9f0; color: #27ae60; }
        .badge-annule     { background: #fee2e2; color: #dc2626; }

        /* ── META DROITE ── */
        .rdv-meta {
            padding: 14px 18px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: space-between;
            gap: 6px;
            min-width: 140px;
        }

        .rdv-tel   { font-size: 12px; color: #aaa; }
        .rdv-ref   { font-size: 11px; color: #bbb; }

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

        /* ── BOUTONS CONFIRMER / ANNULER ── */
        .rdv-actions {
            display: flex;
            gap: 6px;
            margin-top: 6px;
            flex-wrap: wrap;
        }

        .btn-confirmer,
        .btn-annuler {
            border: none;
            border-radius: 20px;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            font-family: Arial, sans-serif;
            transition: background-color 0.2s, transform 0.1s;
        }

        .btn-confirmer {
            background: #dcfce7;
            color: #16a34a;
        }

        .btn-confirmer:hover {
            background: #16a34a;
            color: white;
            transform: scale(1.05);
        }

        .btn-annuler {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-annuler:hover {
            background: #dc2626;
            color: white;
            transform: scale(1.05);
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

        footer {
            position: fixed;
            bottom: 19px;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 13px;
            color: #999;
        }

        .back-btn {
            display: inline-block;
            margin: 20px auto 0;
            padding: 8px 20px;
            background: #525151;
            color: white;
            border-radius: 30px;
            text-decoration: none;
            font-size: 13px;
        }

        .back-btn:hover { background: #333; }

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

<!-- Toast de retour après action -->
<?php if (isset($_GET['ok'])): ?>
<?php $isConfirm = ($_GET['action'] ?? '') === 'confirmer'; ?>
<div class="toast <?= $isConfirm ? '' : 'annule' ?>" id="toast">
    <?= $isConfirm ? '✅ RDV confirmé avec succès !' : '❌ RDV annulé.' ?>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const t = document.getElementById('toast');
        t.style.display = 'block';
        setTimeout(() => t.style.display = 'none', 3500);
    });
</script>
<?php endif; ?>

<?php if (isset($erreur)): ?>
    <div class="erreur-bdd"><?= htmlspecialchars($erreur) ?></div>
<?php else: ?>

<!-- Bandeau en attente -->
<?php if ($nbEnAttente > 0): ?>
<div class="bandeau-attente">
    ⏳ <span class="badge-nb"><?= $nbEnAttente ?></span>
    réservation<?= $nbEnAttente > 1 ? 's' : '' ?> en attente de votre confirmation
</div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-bar">
    <?php
        $totalRdv   = array_sum(array_map('count', $rdvParDate));
        $totalJours = count($rdvParDate);
        $today      = date('Y-m-d');
        $rdvFuturs  = 0;
        $nbEnCours  = 0; $nbTermine = 0; $nbValide = 0; $nbConfirme = 0; $nbAnnule = 0;
        foreach ($rdvParDate as $date => $list) {
            if ($date >= $today) $rdvFuturs += count($list);
            foreach ($list as $r) {
                $s = $r['statut'] ?? '';
                if ($s === 'en_cours')  $nbEnCours++;
                elseif ($s === 'termine')  $nbTermine++;
                elseif ($s === 'valide')   $nbValide++;
                elseif ($s === 'confirme') $nbConfirme++;
                elseif ($s === 'annule')   $nbAnnule++;
            }
        }
    ?>
    <div class="stat-card">
        <div class="stat-value"><?= $totalRdv ?></div>
        <div class="stat-label">RDV total</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:#f59e0b;"><?= $nbEnAttente ?></div>
        <div class="stat-label">En attente</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:#27ae60;"><?= $nbConfirme ?></div>
        <div class="stat-label">Confirmés</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:#dc2626;"><?= $nbAnnule ?></div>
        <div class="stat-label">Annulés</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:#eb5e00;"><?= $rdvFuturs ?></div>
        <div class="stat-label">À venir</div>
    </div>
</div>

<!-- Barre de recherche + filtres -->
<div class="search-bar">
    <input class="search-input" type="text" id="recherche" placeholder="Rechercher un client, véhicule…" oninput="filtrer()">
    <button class="filter-btn active" onclick="setFiltre('tous', this)">Tous</button>
    <button class="filter-btn" onclick="setFiltre('matin', this)">Matin</button>
    <button class="filter-btn" onclick="setFiltre('aprem', this)">Après-midi</button>
    <button class="filter-btn" id="btn-attente" onclick="setFiltreStatut('en_attente', this)">⏳ En attente</button>
    <button class="filter-btn" onclick="setFiltreStatut('confirme', this)">✅ Confirmés</button>
    <button class="filter-btn" onclick="setFiltreStatut('annule', this)">❌ Annulés</button>
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
            <?php
                $moment = couleurHeure($r['heure_de_préstation']);
                $statut = $r['statut'] ?? 'en_cours';
            ?>
            <div class="rdv-card <?= $moment ?> statut-<?= htmlspecialchars($statut) ?>"
                 data-client="<?= htmlspecialchars(strtolower($r['client_prenom'].' '.$r['client_nom'])) ?>"
                 data-vehicule="<?= htmlspecialchars(strtolower($r['vehicule'])) ?>"
                 data-prestation="<?= htmlspecialchars(strtolower($r['prestation_nom'] ?? $r['Probleme'] ?? '')) ?>"
                 data-moment="<?= $moment ?>"
                 data-statut="<?= htmlspecialchars($statut) ?>">

                <!-- Heure -->
                <div class="rdv-heure">
                    <?= htmlspecialchars(substr($r['heure_de_préstation'], 0, 5)) ?>
                    <span class="heure-label"><?= $moment === 'matin' ? 'matin' : 'aprèm' ?></span>
                </div>

                <!-- Corps -->
                <div class="rdv-body">
                    <div class="rdv-client">
                        <?= htmlspecialchars($r['client_prenom'] . ' ' . $r['client_nom']) ?>
                        <?php if ($statut === 'en_attente'): ?>
                            <span class="badge-statut badge-en_attente">⏳ En attente</span>
                        <?php elseif ($statut === 'confirme'): ?>
                            <span class="badge-statut badge-confirme">✅ Confirmé</span>
                        <?php elseif ($statut === 'annule'): ?>
                            <span class="badge-statut badge-annule">❌ Annulé</span>
                        <?php elseif ($statut === 'valide'): ?>
                            <span class="badge-statut badge-valide">✅ Validé</span>
                        <?php elseif ($statut === 'termine'): ?>
                            <span class="badge-statut badge-termine">🏁 Terminé</span>
                        <?php else: ?>
                            <span class="badge-statut badge-en-cours">🔧 En cours</span>
                        <?php endif; ?>
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

                    <!-- Boutons d'action (seulement si en_attente) -->
                    <?php if ($statut === 'en_attente'): ?>
                    <div class="rdv-actions">
                        <form method="POST" action="traiter_rdv.php" style="display:inline;">
                            <input type="hidden" name="id"     value="<?= (int)$r['id_intervention'] ?>">
                            <input type="hidden" name="action" value="confirmer">
                            <button type="submit" class="btn-confirmer">✅ Confirmer</button>
                        </form>
                        <form method="POST" action="traiter_rdv.php" style="display:inline;">
                            <input type="hidden" name="id"     value="<?= (int)$r['id_intervention'] ?>">
                            <input type="hidden" name="action" value="annuler">
                            <button type="submit" class="btn-annuler">❌ Annuler</button>
                        </form>
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

<div style="text-align:center; margin-bottom: 60px;">
    <a class="back-btn" href="prof.php">← Retour à l'espace professeur</a>
</div>

<footer>
    <p>© 2026 Méca Brocéliande</p>
</footer>

<script>
let filtreActif  = 'tous';
let filtreStatut = 'tous';

function setFiltre(f, btn) {
    filtreActif = f;
    document.querySelectorAll('.filter-btn').forEach(b => {
        if (['tous','matin','aprem'].some(v => b.getAttribute('onclick')?.includes("'"+v+"'"))) {
            b.classList.remove('active');
        }
    });
    btn.classList.add('active');
    filtrer();
}

function setFiltreStatut(s, btn) {
    filtreStatut = filtreStatut === s ? 'tous' : s;
    document.querySelectorAll('.filter-btn').forEach(b => {
        if (['en_attente','confirme','annule'].some(v => b.getAttribute('onclick')?.includes("'"+v+"'"))) {
            b.classList.remove('active');
            b.classList.remove('active-attente');
        }
    });
    if (filtreStatut !== 'tous') {
        if (filtreStatut === 'en_attente') btn.classList.add('active-attente');
        else btn.classList.add('active');
    }
    filtrer();
}

function filtrer() {
    const q = document.getElementById('recherche').value.toLowerCase().trim();
    document.querySelectorAll('.jour-bloc').forEach(jour => {
        let visibles = 0;
        jour.querySelectorAll('.rdv-card').forEach(card => {
            const matchMoment = filtreActif === 'tous' || card.dataset.moment === filtreActif;
            const matchStatut = filtreStatut === 'tous' || card.dataset.statut === filtreStatut;
            const matchSearch = !q ||
                card.dataset.client.includes(q) ||
                card.dataset.vehicule.includes(q) ||
                card.dataset.prestation.includes(q);
            if (matchMoment && matchStatut && matchSearch) {
                card.style.display = '';
                visibles++;
            } else {
                card.style.display = 'none';
            }
        });
        jour.style.display = visibles > 0 ? '' : 'none';
    });
}

// Si l'URL contient ?action=... au chargement, activer le filtre en_attente pour montrer ce qui reste
<?php if (isset($_GET['ok'])): ?>
// Scroll en haut pour voir le toast
window.scrollTo(0, 0);
<?php endif; ?>
</script>

</body>
</html>
