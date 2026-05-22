<?php
session_start();

if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['prof','eleve'])) {
    header('Location: login.php');
    exit;
}

try {
    $pdo = new PDO("mysql:host=192.168.11.11;dbname=Meca;charset=utf8mb4", "root", "root",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Erreur connexion : " . $e->getMessage());
}

// ── 1) CLIENT ────────────────────────────────────────────────
$client_id = intval($_POST['client_id'] ?? 0);
if ($client_id === 0) {
    $s = $pdo->prepare("INSERT INTO Clients (prenom,nom,adresse_mail,mots_de_passe,`numéro`,adresse_postal) VALUES (?,?,?,'',?,?)");
    $s->execute([
        $_POST['client_prenom']  ?? '',
        $_POST['client_nom']     ?? '',
        $_POST['client_email']   ?? '',
        $_POST['client_tel']     ?? '',
        $_POST['client_adresse'] ?? '',
    ]);
    $client_id = $pdo->lastInsertId();
}

// ── 2) VÉHICULE ──────────────────────────────────────────────
$marque_modele = trim(($_POST['marque'] ?? '') . ' ' . ($_POST['modele'] ?? ''));
$km = ($_POST['km'] ?? '') !== '' ? intval($_POST['km']) : null;
$mise_circ = ($_POST['mise_circulation'] ?? '') !== '' ? $_POST['mise_circulation'] : null;

// 7 colonnes => 7 ?
$s = $pdo->prepare("INSERT INTO Vehicules (vin,`marque/modèle`,immatriculation,km,mise_circulation,type_veh,client_id) VALUES (?,?,?,?,?,?,?)");
$s->execute([
    strtoupper($_POST['vin']   ?? ''),  // 1
    $marque_modele,                      // 2
    strtoupper($_POST['immat'] ?? ''),  // 3
    $km,                                 // 4
    $mise_circ,                          // 5
    $_POST['type_veh']         ?? '',   // 6
    $client_id,                          // 7
]);
$vehicule_id = $pdo->lastInsertId();

// ── 3) INTERVENTION ──────────────────────────────────────────
// 15 colonnes dont 3 fixes (NULL, CURDATE(), CURTIME())
// => 12 points d'interrogation, 12 valeurs
$mo  = ($_POST['mo_heures']       ?? '') !== '' ? $_POST['mo_heures']       : null;
$tx  = ($_POST['taux_horaire']    ?? '') !== '' ? $_POST['taux_horaire']    : null;
$dr  = ($_POST['date_restitution']?? '') !== '' ? $_POST['date_restitution']: null;

$s = $pdo->prepare("
    INSERT INTO intervention
        (vehicule_id, prestation_id, date_intervention, `heure_de_préstation`,
         commentaire, immat, info_client, travaux, prof, ordre_num,
         mo_heures, taux_horaire, reservoir, damages, date_restitution)
    VALUES
        (?, NULL, CURDATE(), CURTIME(),
         ?, ?, ?, ?, ?, ?,
         ?, ?, ?, ?, ?)
");
$s->execute([
    $vehicule_id,                        // 1  vehicule_id
    $_POST['info_client']      ?? '',    // 2  commentaire
    strtoupper($_POST['immat'] ?? ''),   // 3  immat
    $_POST['info_client']      ?? '',    // 4  info_client
    $_POST['travaux']          ?? '',    // 5  travaux
    $_POST['prof']             ?? '',    // 6  prof
    $_POST['ordre_num']        ?? '',    // 7  ordre_num
    $mo,                                 // 8  mo_heures
    $tx,                                 // 9  taux_horaire
    $_POST['reservoir']        ?? '',    // 10 reservoir
    $_POST['damages']          ?? '',    // 11 damages
    $dr,                                 // 12 date_restitution
]);
$intervention_id = $pdo->lastInsertId();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ordre enregistré</title>
    <style>
        body { font-family:Arial; background:#f3f4f6; display:flex; justify-content:center; align-items:center; height:100vh; }
        .box { background:white; padding:40px 50px; border-radius:15px; text-align:center; box-shadow:0 10px 30px rgba(0,0,0,0.15); }
        h2 { color:#16a34a; margin-bottom:10px; }
        p  { color:#555; font-size:14px; margin-bottom:20px; }
        .links { display:flex; gap:12px; justify-content:center; flex-wrap:wrap; }
        a { display:inline-block; padding:12px 20px; background:#2563eb; color:white; text-decoration:none; border-radius:8px; font-size:14px; }
        a.green { background:#16a34a; }
        a:hover { opacity:0.85; }
    </style>
</head>
<body>
<div class="box">
    <h2>✔ Ordre de réparation enregistré !</h2>
    <p>OR n° <?= $intervention_id ?> créé avec succès.</p>
    <div class="links">
        <a class="green" href="pdf_or.php?id=<?= $intervention_id ?>" target="_blank">📄 Voir le PDF</a>
        <a href="ordre_reparation.php">➕ Nouvel OR</a>
        <?php if (($_SESSION['role'] ?? '') === 'prof'): ?>
            <a href="prof.php">← Accueil prof</a>
        <?php elseif (($_SESSION['role'] ?? '') === 'eleve'): ?>
            <a href="eleve.php">← Accueil élève</a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
