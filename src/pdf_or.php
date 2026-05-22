<?php
session_start();

if (
    !isset($_SESSION['username']) ||
    !in_array($_SESSION['role'], ['prof', 'eleve'])
) {
    header('Location: login.php');
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=192.168.11.11;dbname=Meca;charset=utf8mb4",
        "root", "root",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$id = intval($_GET['id'] ?? 0);
if ($id === 0) die("ID manquant.");

$sql = "
    SELECT
        i.id_intervention,
        i.date_intervention,
        i.`heure_de_préstation`  AS heure,
        i.commentaire,
        i.info_client,
        i.travaux,
        i.prof,
        i.ordre_num,
        i.mo_heures,
        i.taux_horaire,
        i.reservoir,
        i.damages,
        i.date_restitution,
        c.nom, c.prenom,
        c.adresse_postal,
        c.`numéro`               AS telephone,
        c.adresse_mail,
        v.`marque/modèle`        AS marque_modele,
        v.vin,
        v.immatriculation,
        v.km,
        v.mise_circulation,
        v.type_veh
    FROM intervention i
    JOIN Vehicules v ON i.vehicule_id = v.id_vehicules
    JOIN Clients c   ON v.client_id   = c.id_clients
    WHERE i.id_intervention = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$d = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$d) die("Ordre de réparation introuvable.");

function h($v) { return htmlspecialchars($v ?? '—'); }

$client        = h($d['prenom'] . ' ' . strtoupper($d['nom']));
$adresse       = h($d['adresse_postal']);
$tel           = h($d['telephone']);
$email         = h($d['adresse_mail']);
$marque_modele = h($d['marque_modele']);
$vin           = h($d['vin']);
$immat         = h($d['immatriculation']);
$km            = h($d['km']);
$mise_circ     = h($d['mise_circulation']);
$type_veh      = h($d['type_veh']);
$date          = h($d['date_intervention']);
$heure         = h($d['heure']);
$prof          = h($d['prof']);
$ordre_num     = h($d['ordre_num'] ?: 'N° ' . $d['id_intervention']);
$info_client   = nl2br(h($d['info_client'] ?: $d['commentaire']));
$travaux       = nl2br(h($d['travaux']));
$mo_heures     = $d['mo_heures']  ?? '—';
$taux_horaire  = $d['taux_horaire'] ?? '—';
$reservoir     = $d['reservoir']  ?? '';
$date_rest     = h($d['date_restitution']);
$num_or        = $d['id_intervention'];

// Calcul total MO
$total_mo = ($mo_heures !== '—' && $taux_horaire !== '—')
    ? number_format((float)$mo_heures * (float)$taux_horaire, 2) . ' €'
    : '—';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>OR <?= $ordre_num ?> — <?= $client ?></title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; font-size: 12px; color: #1a1a2e; background: #f7f7fb; }

  .toolbar {
    background: #2c2c6e; color: white;
    padding: 10px 24px; display: flex;
    align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 100;
  }
  .toolbar span { font-weight: 700; font-size: 14px; }
  .btn { padding: 7px 18px; border-radius: 4px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; }
  .btn-print { background: white; color: #2c2c6e; }
  .btn-back  { background: transparent; color: white; border: 1.5px solid rgba(255,255,255,0.5); margin-right: 8px; }

  .page {
    max-width: 820px; margin: 24px auto 60px;
    background: white;
    box-shadow: 0 4px 40px rgba(44,44,110,0.12);
    border: 1px solid #b0b0c8;
  }

  .header-bar {
    background: #2c2c6e; color: white;
    text-align: center; padding: 10px;
    font-size: 15px; font-weight: 700;
    letter-spacing: 0.12em; text-transform: uppercase;
  }

  /* Grilles */
  .row-3 { display: grid; grid-template-columns: 200px 1fr 1fr; border-bottom: 1.5px solid #6060a0; }
  .row-2 { display: grid; grid-template-columns: 1fr 1fr; border-bottom: 1.5px solid #6060a0; }

  .cell {
    padding: 10px 14px;
    border-right: 1.5px solid #6060a0;
  }
  .cell:last-child { border-right: none; }
  .cell-bg { background: #f0f0fa; }

  .lbl {
    font-size: 9px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.08em;
    color: #8888aa; display: block; margin-bottom: 3px;
  }
  .val { font-size: 14px; font-weight: 700; color: #2c2c6e; }

  .info-line { display: flex; align-items: center; gap: 8px; margin-top: 4px; font-size: 11px; color: #333; }
  .icon { font-size: 12px; width: 16px; text-align: center; flex-shrink: 0; }

  /* Table véhicule */
  .veh-table { width: 100%; border-collapse: collapse; border-bottom: 1.5px solid #6060a0; }
  .veh-table th {
    background: #e8e8f4; padding: 5px 10px;
    font-size: 9px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.07em; color: #4a4a6a;
    border: 1px solid #b0b0c8; text-align: left;
  }
  .veh-table td { padding: 7px 10px; border: 1px solid #b0b0c8; font-size: 12px; }

  /* Sections */
  .section-title {
    background: #e8e8f4; padding: 5px 14px;
    font-size: 9px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.1em; color: #4a4a6a;
    border-top: 1.5px solid #6060a0; border-bottom: 1px solid #b0b0c8;
  }
  .section-body {
    padding: 12px 14px; min-height: 70px;
    border-bottom: 1.5px solid #6060a0;
    font-size: 12px; line-height: 1.6; color: #333;
  }

  /* Réservoir */
  .reservoir-bar { display: flex; gap: 4px; margin-top: 4px; }
  .res-seg {
    width: 24px; height: 12px;
    border: 1px solid #6060a0; border-radius: 2px;
    background: #eee;
  }
  .res-seg.filled { background: #2c2c6e; }

  /* MO */
  .mo-row { display: flex; gap: 20px; align-items: center; font-size: 12px; padding: 8px 14px; border-bottom: 1.5px solid #6060a0; }
  .mo-row strong { color: #2c2c6e; }

  /* Signatures */
  .sig-row { display: grid; grid-template-columns: repeat(3,1fr); }
  .sig-cell { padding: 8px 14px; border-right: 1.5px solid #6060a0; text-align: center; }
  .sig-cell:last-child { border-right: none; }
  .sig-line { border-bottom: 1px solid #6060a0; height: 36px; margin-top: 6px; }

  .footer {
    background: #f0f0fa; padding: 8px 14px;
    font-size: 10px; color: #8888aa; text-align: center;
    border-top: 1px solid #b0b0c8;
  }

  @media print {
    @page { size: A4; margin: 8mm; }
    body { background: white; }
    .toolbar { display: none !important; }
    .page { box-shadow: none; border: none; margin: 0; max-width: 100%; }
    .header-bar, .section-title, .veh-table th,
    .cell-bg { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style>
</head>
<body>

<div class="toolbar">
  <span>📄 OR <?= $ordre_num ?> — <?= $client ?></span>
  <div>
    <button class="btn btn-back" onclick="history.back()">← Retour</button>
    <button class="btn btn-print" onclick="window.print()">🖨 Imprimer / PDF</button>
  </div>
</div>

<div class="page">

  <div class="header-bar">Ordre de Réparation</div>

  <!-- ÉTABLISSEMENT / DATE / N° OR -->
  <div class="row-3">
    <div class="cell cell-bg">
      <div style="font-size:15px;font-weight:700;color:#2c2c6e;">Brocéliande</div>
      <div style="font-size:10px;color:#4a4a6a;margin-top:3px;line-height:1.6;">
        Cité scolaire de Brocéliande<br>4 avenue de Brocéliande<br>Bellevue – Coëtquidan<br>56380 GUER
      </div>
    </div>
    <div class="cell">
      <span class="lbl">Date de réception</span>
      <div class="val"><?= $date ?></div>
      <div style="font-size:11px;color:#4a4a6a;margin-top:3px;">⏱ <?= $heure ?></div>
    </div>
    <div class="cell">
      <span class="lbl">Ordre de réparation</span>
      <div class="val" style="font-size:20px;"><?= $ordre_num ?></div>
    </div>
  </div>

  <!-- CLIENT / PROF -->
  <div class="row-2">
    <div class="cell">
      <span class="lbl">Client</span>
      <div style="font-size:15px;font-weight:700;color:#2c2c6e;margin-bottom:5px;"><?= $client ?></div>
      <div class="info-line"><span class="icon">📍</span><?= $adresse ?></div>
      <div class="info-line"><span class="icon">📞</span><?= $tel ?></div>
      <div class="info-line"><span class="icon">✉️</span><?= $email ?></div>
    </div>
    <div class="cell">
      <span class="lbl">Professeur référent</span>
      <div style="font-size:13px;font-weight:600;color:#2c2c6e;margin-top:4px;"><?= $prof ?></div>
    </div>
  </div>

  <!-- VÉHICULE ligne 1 -->
  <table class="veh-table">
    <thead>
      <tr>
        <th>Marque / Modèle</th>
        <th>Type</th>
        <th>Date 1ère mise en circulation</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td style="font-weight:600;"><?= $marque_modele ?></td>
        <td><?= $type_veh ?></td>
        <td><?= $mise_circ ?></td>
      </tr>
    </tbody>
  </table>

  <!-- VÉHICULE ligne 2 -->
  <table class="veh-table">
    <thead>
      <tr>
        <th>Immatriculation</th>
        <th>Kilométrage</th>
        <th>VIN (N° de châssis)</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td style="font-family:monospace;letter-spacing:0.1em;"><?= $immat ?></td>
        <td><?= $km !== '—' ? $km . ' km' : '—' ?></td>
        <td style="font-family:monospace;letter-spacing:0.08em;"><?= $vin ?></td>
      </tr>
    </tbody>
  </table>

  <!-- RÉSERVOIR -->
  <div style="display:flex;align-items:center;gap:12px;padding:8px 14px;border-bottom:1.5px solid #6060a0;font-size:11px;color:#4a4a6a;">
    <span>Réservoir :</span>
    <div class="reservoir-bar">
      <?php
        $res = (float)($reservoir ?: 0);
        $segs = [0, 0.25, 0.5, 0.75, 1];
        foreach ($segs as $s):
          $filled = $res >= $s && $s > 0 ? 'filled' : '';
      ?>
        <div class="res-seg <?= $filled ?>" title="<?= $s ?>"></div>
      <?php endforeach; ?>
    </div>
    <span><?= $res > 0 ? ($res * 100) . ' %' : '0 %' ?></span>
  </div>

  <!-- INFORMATIONS CLIENT -->
  <div class="section-title">Informations client / Travaux demandés</div>
  <div class="section-body"><?= $info_client ?: '<span style="color:#aaa;">—</span>' ?></div>

  <!-- TRAVAUX EFFECTUÉS -->
  <div class="section-title">Travaux effectués</div>
  <div class="section-body"><?= $travaux ?: '<span style="color:#aaa;">—</span>' ?></div>

  <!-- MAIN D'ŒUVRE -->
  <div class="mo-row">
    <span>Main d'œuvre : <strong><?= $mo_heures ?> h</strong></span>
    <span>Taux horaire : <strong><?= $taux_horaire ?> €</strong></span>
    <span>Total MO : <strong><?= $total_mo ?></strong></span>
  </div>

  <!-- SIGNATURES -->
  <div class="sig-row" style="border-top:1.5px solid #6060a0;">
    <div class="sig-cell">
      <span class="lbl">Signature réceptionnaire</span>
      <div class="sig-line"></div>
    </div>
    <div class="sig-cell">
      <span class="lbl">Signature client</span>
      <div class="sig-line"></div>
    </div>
    <div class="sig-cell">
      <span class="lbl">Visa D.D.F</span>
      <div class="sig-line"></div>
    </div>
  </div>

  <!-- RESTITUTION -->
  <div style="padding:8px 14px;border-top:1.5px solid #6060a0;font-size:11px;color:#4a4a6a;border-bottom:1.5px solid #6060a0;">
    <span class="lbl">Date de restitution</span>
    <span style="font-weight:600;color:#2c2c6e;"><?= $date_rest ?></span>
  </div>

  <div class="footer">
    Les interventions sont réalisées par les élèves sous supervision pédagogique.
    L'établissement décline toute responsabilité en cas de vol ou dommage.
  </div>

</div>
</body>
</html>
