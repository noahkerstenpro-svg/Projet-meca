<?php
session_start();

if (
    !isset($_SESSION['username']) ||
    !in_array($_SESSION['role'], ['prof', 'eleve'])
) {
    header('Location: login.php');
    exit;
}

// --- Connexion BDD ---
$host   = 'meca-mysql';
$dbname = 'Meca';
$user   = 'root';
$pass   = 'root';
$pdo = new PDO("mysql:host=$host;port=3306;dbname=$dbname;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --- Pré-remplissage si ?intervention_id=X ---
$prefill  = [];
$donnees  = [];   // champs extras depuis le JSON
$intervention_id = (int)($_GET['intervention_id'] ?? 0);

if ($intervention_id > 0) {
    $stmt = $pdo->prepare("
        SELECT i.*, v.vin, v.marque, v.modele, v.immatriculation,
               v.km, v.mise_circulation, v.type_veh, v.id_vehicules,
               c.prenom AS client_prenom, c.nom AS client_nom,
               c.adresse_postal AS client_adresse, c.`numéro` AS client_tel,
               c.adresse_mail AS client_email, c.id_clients
        FROM intervention i
        LEFT JOIN Vehicules v ON v.id_vehicules = i.vehicule_id
        LEFT JOIN Clients   c ON c.id_clients   = v.client_id
        WHERE i.id_intervention = :id
    ");
    $stmt->execute([':id' => $intervention_id]);
    $prefill = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    // Décoder le JSON des champs extras
    if (!empty($prefill['donnees_or'])) {
        $donnees = json_decode($prefill['donnees_or'], true) ?? [];
    }
}

// Charger les prestations pour le menu déroulant facturation
$prestations = $pdo->query("SELECT id_prestation, designation, reference, prix FROM Prestation ORDER BY id_prestation")
                   ->fetchAll(PDO::FETCH_ASSOC);

// Helper pour pré-remplir un champ (cherche dans prefill puis donnees)
function val($key, $prefill, $default = '') {
    $v = $prefill[$key] ?? $donnees[$key] ?? $default;
    return htmlspecialchars($v ?? $default);
}

// Helper spécifique pour donnees extras
function don($key, $donnees, $default = '') {
    return htmlspecialchars($donnees[$key] ?? $default);
}

function donBool($key, $donnees) {
    return !empty($donnees[$key]) ? 'checked' : '';
}
?>

<?php // ordre.php ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ordre de Réparation — Lycée Brocéliande</title>
<link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@300;400;600;700&family=Source+Serif+4:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --ink: #1a1a2e;
    --mid: #4a4a6a;
    --light: #8888aa;
    --border: #b0b0c8;
    --border-strong: #6060a0;
    --accent: #2c2c6e;
    --bg: #f7f7fb;
    --white: #ffffff;
    --header-bg: #2c2c6e;
    --header-text: #ffffff;
    --section-bg: #e8e8f4;
    --input-bg: #fafafe;
    --red: #c0392b;
  }

  body {
    font-family: 'Source Sans 3', sans-serif;
    background: var(--bg);
    color: var(--ink);
    font-size: 11px;
    line-height: 1.3;
  }

  /* ── TOOLBAR ── */
  .toolbar {
    background: var(--accent);
    color: white;
    padding: 10px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 12px rgba(44,44,110,0.25);
  }
  .toolbar-title { font-size: 14px; font-weight: 600; letter-spacing: 0.04em; }
  .toolbar-actions { display: flex; gap: 10px; }
  .btn {
    padding: 6px 16px; border-radius: 4px; font-family: inherit; font-size: 12px;
    font-weight: 600; cursor: pointer; border: none; letter-spacing: 0.04em;
    transition: all 0.15s;
  }
  .btn-primary { background: white; color: var(--accent); }
  .btn-primary:hover { background: #e8e8f4; }
  .btn-outline { background: transparent; color: white; border: 1.5px solid rgba(255,255,255,0.5); }
  .btn-outline:hover { background: rgba(255,255,255,0.1); }

  /* ── FORM WRAPPER ── */
  .page-wrapper {
    max-width: 820px;
    margin: 16px auto 40px;
    background: var(--white);
    box-shadow: 0 4px 40px rgba(44,44,110,0.12);
    border: 1px solid var(--border);
  }

  /* ── MAIN HEADER ── */
  .form-header {
    background: var(--header-bg);
    color: var(--header-text);
    text-align: center;
    padding: 6px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.10em;
    text-transform: uppercase;
    border-bottom: 2px solid #1a1a5e;
  }

  /* ── IDENTITY ROW ── */
  .identity-row {
    display: grid;
    grid-template-columns: 160px 1fr 1fr;
    border-bottom: 1.5px solid var(--border-strong);
  }
  .logo-cell {
    padding: 6px 8px;
    border-right: 1.5px solid var(--border-strong);
    display: flex;
    flex-direction: column;
    justify-content: center;
    background: #f0f0fa;
  }
  .school-logo {
    max-width: 80px;
    max-height: 55px;
    object-fit: contain;
    display: block;
    margin-bottom: 3px;
  }
  .logo-cell .school-addr {
    font-size: 9px;
    color: var(--mid);
    margin-top: 2px;
    line-height: 1.4;
  }
  .date-cell {
    padding: 5px 8px;
    border-right: 1.5px solid var(--border-strong);
    display: flex;
    flex-direction: column;
    gap: 3px;
  }
  .order-cell {
    padding: 5px 8px;
    display: flex;
    flex-direction: column;
    gap: 3px;
  }
  .cell-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--light);
  }
  .order-num {
    font-size: 16px;
    font-weight: 700;
    color: var(--accent);
    letter-spacing: 0.04em;
  }

  /* ── CLIENT ROW ── */
  .client-row {
    display: grid;
    grid-template-columns: 1fr;
    border-bottom: 1.5px solid var(--border-strong);
  }
  .client-cell {
    padding: 5px 8px;
  }

  /* ── VEHICLE TABLE ── */
  .vehicle-table {
    width: 100%;
    border-collapse: collapse;
    border-bottom: 1.5px solid var(--border-strong);
  }
  .vehicle-table th {
    background: var(--section-bg);
    padding: 3px 6px;
    text-align: center;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--mid);
    border: 1px solid var(--border);
  }
  .vehicle-table td {
    border: 1px solid var(--border);
    padding: 1px 3px;
    vertical-align: middle;
  }

  /* ── RECEPTION / INFO SPLIT ── */
  .reception-row {
    display: grid;
    grid-template-columns: 200px 1fr;
    border-bottom: 1.5px solid var(--border-strong);
    min-height: 140px;
  }
  .reception-cell {
    border-right: 1.5px solid var(--border-strong);
    padding: 5px 8px;
  }
  .info-cell { padding: 5px 8px; }

  /* ── CAR SCHEMA ── */
  .car-schema {
    margin: 6px auto;
    display: block;
    opacity: 0.7;
  }
  .schema-legend {
    display: flex; gap: 12px; font-size: 10px; color: var(--mid);
    margin-bottom: 6px;
  }
  .schema-legend label { display: flex; align-items: center; gap: 4px; cursor: pointer; }

  .checklist { margin-top: 6px; }
  .checklist label {
    display: flex; align-items: center; gap: 6px;
    font-size: 11px; color: var(--mid); margin: 2px 0; cursor: pointer;
  }
  .checklist label input[type=checkbox] { accent-color: var(--accent); }
  .fuel-row {
    display: flex; align-items: center; gap: 6px;
    font-size: 11px; color: var(--mid); margin-top: 4px;
  }
  .fuel-track {
    display: flex; gap: 3px; align-items: center;
  }
  .fuel-track label { font-size: 10px; display: flex; align-items: center; gap: 2px; }

  /* ── SIGNATURES ROW ── */
  .sig-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 1fr;
    border-top: 1px solid var(--border);
  }
  .sig-cell {
    padding: 4px 8px;
    border-right: 1px solid var(--border);
    text-align: center;
  }
  .sig-cell:last-child { border-right: none; }
  .sig-line {
    border-bottom: 1px solid var(--border-strong);
    height: 28px;
    margin-top: 4px;
  }

  /* ── TRAVAUX ── */
  .section-header {
    background: var(--section-bg);
    padding: 3px 8px;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--mid);
    border-top: 1.5px solid var(--border-strong);
    border-bottom: 1px solid var(--border);
  }
  .travaux-area {
    padding: 4px 8px;
    border-bottom: 1.5px solid var(--border-strong);
  }

  /* ── FACTURATION TABLE ── */
  .fact-table {
    width: 100%;
    border-collapse: collapse;
    border-bottom: 1.5px solid var(--border-strong);
  }
  .fact-table th {
    background: var(--section-bg);
    padding: 5px 10px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--mid);
    border: 1px solid var(--border);
    text-align: left;
  }
  .fact-table th:nth-child(2),
  .fact-table th:nth-child(4) { text-align: center; }
  .fact-table td {
    border: 1px solid var(--border);
    padding: 2px 4px;
    vertical-align: middle;
  }
  .fact-table .subtotal-row td {
    background: #f0f0fa;
    font-size: 11px;
    color: var(--mid);
  }
  .fact-table .total-row td {
    background: var(--accent);
    color: white;
    font-weight: 700;
    font-size: 13px;
  }

  /* ── RESTITUTION ROW ── */
  .restitution-row {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    border-top: 1.5px solid var(--border-strong);
  }
  .rest-cell {
    padding: 4px 6px;
    border-right: 1px solid var(--border);
    text-align: center;
  }
  .rest-cell:last-child { border-right: none; }

  /* ── GENERIC INPUTS ── */
  input[type=text], input[type=date], input[type=number], input[type=email], input[type=tel], textarea, select {
    width: 100%;
    border: none;
    border-bottom: 1.5px solid var(--border);
    background: transparent;
    font-family: inherit;
    font-size: 13px;
    color: var(--ink);
    padding: 2px 3px;
    outline: none;
    transition: border-color 0.15s;
  }
  input:focus, textarea:focus, select:focus {
    border-bottom-color: var(--accent);
    background: rgba(44,44,110,0.03);
  }
  textarea { resize: vertical; min-height: 50px; }
  input[type=checkbox] { width: auto; accent-color: var(--accent); }

  .field-group { margin-bottom: 6px; }
  .field-group .cell-label { margin-bottom: 2px; display: block; }

  /* ── INLINE ICONS for client ── */
  .client-fields { display: flex; flex-direction: column; gap: 4px; margin-top: 4px; }
  .client-field-row {
    display: flex; align-items: center; gap: 6px;
  }
  .client-field-row .icon { font-size: 14px; flex-shrink: 0; width: 18px; text-align: center; }

  /* ── PRINT ── */
  @media print {
    @page { size: A4; margin: 8mm; }

    body { background: white; font-size: 11px; }

    /* Masquer toolbar et boutons */
    .toolbar,
    button[type=button],
    button[type=submit],
    .btn { display: none !important; }

    .page-wrapper { box-shadow: none; border: none; margin: 0; max-width: 100%; }

    /* Inputs affichent leur valeur proprement */
    input[type=text],
    input[type=date],
    input[type=number],
    input[type=email],
    input[type=tel] {
      border: none !important;
      border-bottom: 1px solid #ccc !important;
      background: transparent !important;
      font-size: 9px !important;
      color: #000 !important;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    textarea {
      border: none !important;
      border-bottom: 1px dashed #ccc !important;
      background: transparent !important;
      font-size: 9px !important;
      color: #000 !important;
    }

    /* Masquer les placeholders */
    input::placeholder,
    textarea::placeholder { color: transparent !important; }

    /* Dropdown suggestions : masquer */
    #clientSuggestions { display: none !important; }

    /* Conserver les couleurs des en-têtes */
    .form-header,
    .section-header,
    .vehicle-table th,
    .fact-table th,
    .fact-table .total-row td {
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

  }
</style>
</head>
<body>

<!-- TOOLBAR -->
<div class="toolbar">
  <span class="toolbar-title">📋 Ordre de Réparation — Cité scolaire de Brocéliande</span>
  <div class="toolbar-actions">
    <button class="btn btn-outline" type="button" onclick="resetForm()">🗑 Réinitialiser</button>
    <button class="btn btn-primary" type="button" onclick="genererFacturePDF()" title="Télécharger la Facture Titre Exécutoire en PDF">📄 Facture PDF</button>
    <button class="btn btn-primary" type="button" onclick="window.print()">🖨 Imprimer / PDF</button>
    <button class="btn btn-primary" type="submit" form="orForm">💾 Enregistrer</button>
    <?php if ($intervention_id > 0): ?>
    <button class="btn" type="button"
            style="background:#27ae60; color:white;"
            onclick="terminerOR(<?= $intervention_id ?>)">
      ✅ Terminer
    </button>
    <?php endif; ?>
  </div>
</div>

<?php if (isset($_GET['saved'])): ?>
<div style="background:#e6f9e6; color:#2a7a2a; border-bottom:2px solid #a3d9a3; padding:10px 24px; font-size:13px; font-family:'Source Sans 3',sans-serif; display:flex; align-items:center; gap:8px;">
  ✅ <strong>Enregistré avec succès</strong> — Les données ont bien été sauvegardées en base de données.
  <?php if ($intervention_id): ?>
    <span style="color:#555; margin-left:8px;">ID intervention : <strong><?= $intervention_id ?></strong></span>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- FORM -->
<div class="page-wrapper">
  <form id="orForm" action="save_or.php" method="POST" autocomplete="off">
    <input type="hidden" name="intervention_id" value="<?= val('id_intervention', $prefill) ?>">
    <input type="hidden" name="vehicule_id"     value="<?= val('id_vehicules',   $prefill) ?>">

    <!-- ═══ TITRE ═══ -->
    <div class="form-header">Ordre de Réparation</div>

    <!-- ═══ IDENTITÉ ═══ -->
    <div class="identity-row">
      <div class="logo-cell">
        <img src="logo-broceliande.png" alt="Brocéliande" class="school-logo">
        <div class="school-addr">
          Cité scolaire de Brocéliande<br>
          4 avenue de Brocéliande<br>
          Bellevue – Coëtquidan<br>
          56380 GUER
        </div>
      </div>
      <div class="date-cell">
        <span class="cell-label">Date de réception</span>
        <input type="date" name="date_reception" id="date_reception" value="<?= val('date_intervention', $prefill) ?>">
      </div>
      <div class="order-cell">
        <span class="cell-label">Ordre de réparation</span>
        <div class="order-num" id="orderNumDisplay"><?= don('ordre_num', $donnees) ?: 'N 99/°25-26' ?></div>
        <input type="text" name="ordre_num" id="ordre_num" placeholder="ex: N 99/°25-26" style="font-size:11px;" value="<?= don('ordre_num', $donnees) ?>">
        <span class="cell-label" style="margin-top:6px; display:block;">Professeur référent</span>
        <input type="text" name="prof" id="prof" placeholder="Nom du professeur référent" style="font-weight:600;" value="<?= don('prof', $donnees) ?>">
      </div>
    </div>

    <!-- ═══ CLIENT / PROF ═══ -->
    <div class="client-row">
      <div class="client-cell">
        <span class="cell-label">Client</span>
        <!-- Champs prénom / nom avec autocomplétion BDD -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:6px; position:relative;">
          <div style="position:relative;">
            <span class="cell-label" style="font-size:9px;">Prénom</span>
            <input type="text" name="client_prenom" id="client_prenom" placeholder="Prénom"
              autocomplete="off" style="font-weight:600;"
              value="<?= val('client_prenom', $prefill) ?>"
              oninput="rechercheClient()"
              onblur="setTimeout(()=>fermerSuggestions(),200)">
          </div>
          <div style="position:relative;">
            <span class="cell-label" style="font-size:9px;">Nom</span>
            <input type="text" name="client_nom" id="client_nom" placeholder="Nom"
              autocomplete="off" style="font-weight:600; text-transform:uppercase;"
              value="<?= val('client_nom', $prefill) ?>"
              oninput="rechercheClient()"
              onblur="setTimeout(()=>fermerSuggestions(),200)">
          </div>
        </div>
        <!-- Dropdown suggestions -->
        <div id="clientSuggestions" style="
          display:none; position:absolute; z-index:200;
          background:white; border:1.5px solid var(--border-strong);
          box-shadow:0 4px 16px rgba(44,44,110,0.18);
          border-radius:4px; max-height:180px; overflow-y:auto;
          min-width:280px; font-size:12px;
        "></div>
          <input type="hidden" name="client_id" id="client_id" value="<?= val('id_clients', $prefill) ?>">
        <div class="client-fields">
          <div class="client-field-row">
            <span class="icon">📍</span>
            <input type="text" name="client_adresse" id="client_adresse" placeholder="Adresse" value="<?= val('client_adresse', $prefill) ?>">
          </div>
          <div class="client-field-row">
            <span class="icon">📞</span>
            <input type="tel" name="client_tel" id="client_tel" placeholder="Téléphone" value="<?= val('client_tel', $prefill) ?>">
          </div>
          <div class="client-field-row">
            <span class="icon">✉️</span>
            <input type="email" name="client_email" id="client_email" placeholder="E-mail" value="<?= val('client_email', $prefill) ?>">
          </div>
        </div>
      </div>

    </div>

    <!-- ═══ VÉHICULE ═══ -->
    <table class="vehicle-table">
      <thead>
        <tr>
          <th>Marque</th>
          <th>Modèle</th>
          <th>Type</th>
          <th>Date 1<sup>ère</sup> Mise en Circulation</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><input type="text" name="marque" id="marqueInput" placeholder="ex: Renault" value="<?= val('marque', $prefill) ?>"></td>
          <td><input type="text" name="modele" id="modeleInput" placeholder="ex: Clio IV" value="<?= val('modele', $prefill) ?>"></td>
          <td><input type="text" name="type_veh" id="typeVehInput" placeholder="ex: Berline" value="<?= val('type_veh', $prefill) ?>"></td>
          <td><input type="date" name="mise_circulation" id="miseCircInput" value="<?= val('mise_circulation', $prefill) ?>"></td>
        </tr>
      </tbody>
    </table>
    <table class="vehicle-table">
      <thead>
        <tr>
          <th>Immatriculation</th>
          <th>Kilométrage</th>
          <th colspan="2">VIN (N° de châssis)</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><input type="text" name="immat" id="immatInput" placeholder="AB-123-CD" style="text-transform:uppercase; letter-spacing:0.1em;" value="<?= val('immatriculation', $prefill) ?>"></td>
          <td><input type="number" name="km" id="kmInput" placeholder="km" min="0" value="<?= val('km', $prefill) ?>"></td>
          <td colspan="2">
            <div style="display:flex; align-items:center; gap:8px; position:relative;">
              <div style="position:relative; flex:1;">
                <input type="text" name="vin" id="vinInput" placeholder="17 caractères" maxlength="17"
                  style="text-transform:uppercase; letter-spacing:0.08em; font-family:monospace; width:100%;"
                  value="<?= val('vin', $prefill) ?>"
                  oninput="rechercheVin()"
                  onblur="setTimeout(()=>fermerSuggestionsVin(),200)"
                  autocomplete="off">
                <!-- Dropdown VIN -->
                <div id="vinSuggestions" style="
                  display:none; position:absolute; z-index:200;
                  background:white; border:1.5px solid var(--border-strong);
                  box-shadow:0 4px 16px rgba(44,44,110,0.18);
                  border-radius:4px; max-height:160px; overflow-y:auto;
                  min-width:340px; font-size:12px; left:0; top:100%;
                "></div>
              </div>
            </div>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- ═══ RÉCEPTION + INFO CLIENT ═══ -->
    <div class="reception-row">
      <div class="reception-cell">
        <div class="section-header" style="border:none; background:none; padding:0 0 4px 0;">Réception du véhicule</div>

        <!-- Schéma voiture SVG simplifié -->
        <svg class="car-schema" width="160" height="75" viewBox="0 0 180 90" fill="none" xmlns="http://www.w3.org/2000/svg">
          <!-- Body -->
          <rect x="15" y="42" width="150" height="32" rx="6" fill="#dde" stroke="#6060a0" stroke-width="1.5"/>
          <!-- Roof -->
          <path d="M55 42 Q70 18 110 18 Q135 18 145 42" fill="#eef" stroke="#6060a0" stroke-width="1.5"/>
          <!-- Windshield -->
          <path d="M60 42 Q72 24 105 24 Q128 24 140 42" fill="#c8d8f0" stroke="#6060a0" stroke-width="1"/>
          <!-- Wheels -->
          <circle cx="42" cy="74" r="12" fill="#555" stroke="#333" stroke-width="1.5"/>
          <circle cx="42" cy="74" r="5" fill="#aaa"/>
          <circle cx="138" cy="74" r="12" fill="#555" stroke="#333" stroke-width="1.5"/>
          <circle cx="138" cy="74" r="5" fill="#aaa"/>
          <!-- Damage markers (clickable) -->
          <circle id="dmg-front" cx="165" cy="58" r="6" fill="none" stroke="#c0392b" stroke-width="1.5" stroke-dasharray="3,2" style="cursor:pointer" onclick="toggleDmg('front')" title="Avant"/>
          <circle id="dmg-rear" cx="15" cy="58" r="6" fill="none" stroke="#c0392b" stroke-width="1.5" stroke-dasharray="3,2" style="cursor:pointer" onclick="toggleDmg('rear')" title="Arrière"/>
          <circle id="dmg-left" cx="90" cy="80" r="6" fill="none" stroke="#c0392b" stroke-width="1.5" stroke-dasharray="3,2" style="cursor:pointer" onclick="toggleDmg('left')" title="Gauche"/>
          <circle id="dmg-right" cx="90" cy="44" r="6" fill="none" stroke="#c0392b" stroke-width="1.5" stroke-dasharray="3,2" style="cursor:pointer" onclick="toggleDmg('right')" title="Droite"/>
          <text x="162" y="55" font-size="7" fill="#c0392b" text-anchor="middle">Av</text>
          <text x="15" y="55" font-size="7" fill="#c0392b" text-anchor="middle">Ar</text>
          <text x="90" y="92" font-size="7" fill="#c0392b" text-anchor="middle">G</text>
          <text x="90" y="42" font-size="7" fill="#c0392b" text-anchor="middle">D</text>
        </svg>
        <div style="font-size:10px; color:var(--light); text-align:center; margin-bottom:6px;">Cliquez sur les zones endommagées</div>
        <input type="hidden" name="damages" id="damagesInput" value="">

        <div class="schema-legend">
          <label><input type="checkbox" name="type_griffe"> Griffe</label>
          <label><input type="checkbox" name="type_coup" checked> Coup</label>
        </div>

        <div class="checklist">
          <label><input type="checkbox" name="roue_secours"  <?= donBool('roue_secours',  $donnees) ?>> Roue de secours</label>
          <label><input type="checkbox" name="ecrou_antivol" <?= donBool('ecrou_antivol', $donnees) ?>> Écrou antivol</label>
          <label><input type="checkbox" name="alarme"        <?= donBool('alarme',        $donnees) ?>> Alarme</label>
          <label style="align-items:flex-start; flex-direction:column; gap:1px;">
            <span>Code alarme :</span>
            <input type="text" name="code_alarme" placeholder="code alarme" value="<?= don('code_alarme', $donnees) ?>">
          </label>
        </div>
        <div class="fuel-row">
          <span>Réservoir :</span>
          <div class="fuel-track">
            <label><input type="radio" name="reservoir" value="0"> 0</label>
            <label><input type="radio" name="reservoir" value="0.25"> ¼</label>
            <label><input type="radio" name="reservoir" value="0.5"> ½</label>
            <label><input type="radio" name="reservoir" value="0.75"> ¾</label>
            <label><input type="radio" name="reservoir" value="1"> 1</label>
          </div>
        </div>
      </div>

      <div class="info-cell">
        <div class="section-header" style="border:none; background:none; padding:0 0 4px 0;">Informations client (symptômes ou travaux demandés)</div>
        <textarea name="info_client" rows="5" placeholder="Décrire les symptômes ou travaux demandés par le client..."><?= val('Probleme', $prefill) ?></textarea>
        <div style="font-size:10px; color:var(--light); margin-top:8px;">
          <label><input type="checkbox" name="cg_accepted"> J'accepte les Conditions Générales (Voir Verso)</label>
        </div>
        <div class="sig-row" style="margin-top:10px; display:grid; grid-template-columns:1fr 1fr; border-top:1px solid var(--border);">
          <div class="sig-cell">
            <span class="cell-label">Signature du réceptionnaire</span>
            <div class="sig-line"></div>
          </div>
          <div class="sig-cell" style="border-right:none;">
            <span class="cell-label">Signature du client</span>
            <div class="sig-line"></div>
          </div>
        </div>
        <div class="sig-row" style="display:grid; grid-template-columns:1fr 1fr; border-top:1px solid var(--border); margin-top:4px;">
          <div class="sig-cell">
            <span class="cell-label">Signature du technicien</span>
            <div class="sig-line"></div>
          </div>
          <div class="sig-cell" style="border-right:none;">
            <span class="cell-label">Visa D.D.F</span>
            <div class="sig-line"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ TRAVAUX EFFECTUÉS ═══ -->
    <div class="section-header">Travaux effectués</div>
    <div class="travaux-area">
      <textarea name="travaux" id="travaux" rows="3" placeholder="Décrire les travaux effectués..."><?= val('commentaire', $prefill) ?></textarea>
    </div>

    <!-- ═══ FACTURATION ═══ -->
    <table class="fact-table">
      <thead>
        <tr>
          <th style="width:43%">Forfait / Fournitures / Consommables</th>
          <th style="width:10%; text-align:center;">Qté</th>
          <th style="width:19%">Référence</th>
          <th style="width:24%; text-align:right;">Prix total TTC</th>
          <th style="width:28px;"></th>
        </tr>
      </thead>
      <tbody id="factRows">
        <!-- Lines générées par JS -->
      </tbody>
      <tbody id="addLineRow">
        <tr>
          <td colspan="5" style="padding:4px 8px;">
            <button type="button" onclick="addFactLine()" style="background:none; border:1.5px dashed var(--border-strong); border-radius:4px; color:var(--accent); font-size:11px; font-weight:600; cursor:pointer; padding:4px 14px; width:100%; letter-spacing:0.04em; transition:all 0.15s;"
              onmouseover="this.style.background='var(--section-bg)'" onmouseout="this.style.background='none'">
              + Ajouter une ligne
            </button>
          </td>
        </tr>
      </tbody>
      <tbody>
        <tr class="subtotal-row">
          <td>Forfait recyclage</td>
          <td style="text-align:center;">1</td>
          <td>FRC</td>
          <td style="text-align:right;">2 €</td>
        </tr>
        <tr class="subtotal-row">
          <td>Forfait petite fourniture</td>
          <td style="text-align:center;">1</td>
          <td>FPF</td>
          <td style="text-align:right;">5 €</td>
        </tr>
        <tr>
          <td colspan="2">
            <div style="display:flex; align-items:center; gap:6px;">
              <span style="font-size:11px; color:var(--mid);">Main d'œuvre (nb d'heures)</span>
              <input type="number" name="mo_heures" id="moHeures" placeholder="0" min="0" step="0.5" style="width:60px;" oninput="calcTotal()" value="<?= don('mo_heures', $donnees) ?>">
            </div>
          </td>
          <td>
            <div style="display:flex; align-items:center; gap:4px;">
              <span style="font-size:10px; color:var(--light);">Taux horaire :</span>
              <input type="number" name="taux_horaire" id="tauxH" placeholder="0" min="0" style="width:60px;" oninput="calcTotal()" value="<?= don('taux_horaire', $donnees) ?>">
              <span style="font-size:10px;">€</span>
            </div>
          </td>
          <td></td>
        </tr>
        <tr class="total-row">
          <td colspan="3" style="text-align:right; padding-right:12px; letter-spacing:0.1em;">TOTAL TTC</td>
          <td style="text-align:right; padding-right:8px; font-size:16px;" id="totalTTC">— €</td>
        </tr>
      </tbody>
    </table>

    <!-- ═══ RESTITUTION ═══ -->
    <div class="restitution-row">
      <div class="rest-cell">
        <span class="cell-label">Date de restitution</span>
        <input type="date" name="date_restitution" id="date_restitution" value="<?= don('date_restit', $donnees) ?>">
      </div>
      <div class="rest-cell">
        <span class="cell-label">Signature référent</span>
        <div class="sig-line"></div>
      </div>
      <div class="rest-cell">
        <span class="cell-label">Signature client</span>
        <div class="sig-line"></div>
      </div>
      <div class="rest-cell">
        <span class="cell-label">Signature DDF</span>
        <div class="sig-line"></div>
      </div>
      <div class="rest-cell" style="border-right:none;">
        <span class="cell-label">Visa Gestion</span>
        <div class="sig-line"></div>
      </div>
    </div>

    <!-- Bouton Enregistrer en bas -->
    <div style="margin:12px 10px 20px; text-align:right;">
      <button class="btn btn-primary" type="submit">💾 Enregistrer</button>
    </div>

  </form><!-- end #orForm -->


</div><!-- end .page-wrapper -->

<script>
// ── Lignes de facturation dynamiques ──
let lineCount = 0;
const tbody = document.getElementById('factRows');

// ── Prestations chargées depuis la BDD ──
const PRESTATIONS = <?= json_encode(array_values($prestations), JSON_UNESCAPED_UNICODE) ?>;

function addFactLine(savedDesc, savedQte, savedRef, savedPrix) {
  const i = lineCount++;
  const tr = document.createElement('tr');
  tr.id = 'factRow-' + i;

  // Construire les options du select
  let options = '<option value="">— Choisir une prestation —</option>';
  PRESTATIONS.forEach(p => {
    const sel = (savedDesc && savedDesc === p.designation) ? 'selected' : '';
    options += `<option value="${p.designation}" data-ref="${p.reference}" data-prix="${p.prix}" ${sel}>${p.designation}</option>`;
  });
  options += `<option value="Autre" ${savedDesc === 'Autre' ? 'selected' : ''}>✏️ Autre (saisie libre)</option>`;

  tr.innerHTML = `
    <td style="position:relative;">
      <select name="fact_desc_${i}" onchange="selectionnerPrestation(${i}, this)" style="width:100%; padding:3px 6px; border:1px solid var(--border); border-radius:3px; font-size:12px; font-family:'Source Sans 3',sans-serif; background:var(--input-bg);">
        ${options}
      </select>
      <input type="text" name="fact_desc_libre_${i}" id="fact_desc_libre_${i}"
        placeholder="Désignation libre..."
        style="display:none; width:100%; margin-top:4px;"
        value="${savedDesc && savedDesc !== 'Autre' && !PRESTATIONS.find(p=>p.designation===savedDesc) ? (savedDesc||'') : ''}">
    </td>
    <td><input type="number" name="fact_qte_${i}" min="0" step="1" placeholder="0"
      style="text-align:center; width:100%;" oninput="calcTotal()"
      value="${savedQte||''}"></td>
    <td><input type="text" name="fact_ref_${i}" id="fact_ref_${i}" placeholder="REF"
      style="width:100%;"
      value="${savedRef||''}"></td>
    <td><input type="number" name="fact_prix_${i}" id="fact_prix_${i}" min="0" step="0.01"
      placeholder="0.00" style="text-align:right; width:100%;" oninput="calcTotal()"
      value="${savedPrix||''}"></td>
    <td style="text-align:center; width:28px;">
      <button type="button" onclick="removeFactLine(${i})"
        style="background:none; border:none; color:var(--red); cursor:pointer; font-size:14px; padding:0 4px;" title="Supprimer">×</button>
    </td>
  `;
  tbody.appendChild(tr);

  // Si c'est "Autre" à la restauration, afficher le champ libre
  if (savedDesc && !PRESTATIONS.find(p => p.designation === savedDesc) && savedDesc !== '') {
    const sel   = tr.querySelector(`[name=fact_desc_${i}]`);
    const libre = document.getElementById('fact_desc_libre_' + i);
    sel.value = 'Autre';
    libre.style.display = 'block';
    libre.value = savedDesc;
  }
}

function selectionnerPrestation(i, sel) {
  const opt   = sel.options[sel.selectedIndex];
  const ref   = opt.dataset.ref  || '';
  const prix  = opt.dataset.prix || '';
  const libre = document.getElementById('fact_desc_libre_' + i);

  if (sel.value === 'Autre') {
    libre.style.display = 'block';
    libre.focus();
    document.getElementById('fact_ref_'  + i).value = '';
    document.getElementById('fact_prix_' + i).value = '';
  } else {
    libre.style.display = 'none';
    libre.value = '';
    document.getElementById('fact_ref_'  + i).value = ref;
    document.getElementById('fact_prix_' + i).value = prix;
  }
  calcTotal();
}

function removeFactLine(i) {
  const row = document.getElementById('factRow-' + i);
  if (row) { row.remove(); calcTotal(); }
}

// 1 ligne par défaut au chargement — ou restauration depuis BDD
<?php if (!empty($donnees['fact_lines'])): ?>
(function() {
  const lines = <?= json_encode($donnees['fact_lines'], JSON_UNESCAPED_UNICODE) ?>;
  lines.forEach(l => {
    addFactLine(l.desc || '', l.qte || '', l.ref || '', l.prix || '');
  });
  calcTotal();
})();
<?php else: ?>
addFactLine();
<?php endif; ?>

// Restauration réservoir
<?php if (!empty($donnees['reservoir'])): ?>
(function() {
  const radios = document.querySelectorAll('input[name="reservoir"]');
  radios.forEach(r => { if (r.value === '<?= $donnees['reservoir'] ?>') r.checked = true; });
})();
<?php endif; ?>

// Restauration zones endommagées
<?php if (!empty($donnees['damages'])): ?>
(function() {
  const zones = '<?= addslashes($donnees['damages']) ?>'.split(',').filter(Boolean);
  zones.forEach(z => toggleDmg(z));
  document.getElementById('damagesInput').value = '<?= addslashes($donnees['damages']) ?>';
})();
<?php endif; ?>

// Restauration checkboxes type dommage
<?php if (!empty($donnees['type_griffe'])): ?>
document.querySelector('input[name="type_griffe"]').checked = true;
<?php endif; ?>
<?php if (!empty($donnees['cg_accepted'])): ?>
document.querySelector('input[name="cg_accepted"]').checked = true;
<?php endif; ?>

// ── Calcul du total ──
function calcTotal() {
  let total = 7; // FRC 2€ + FPF 5€
  tbody.querySelectorAll('tr').forEach(tr => {
    const idx = tr.id.replace('factRow-', '');
    const qte  = parseFloat(tr.querySelector(`[name=fact_qte_${idx}]`)?.value) || 0;
    const prix = parseFloat(tr.querySelector(`[name=fact_prix_${idx}]`)?.value) || 0;
    total += qte * prix;
  });
  const h = parseFloat(document.getElementById('moHeures')?.value) || 0;
  const t = parseFloat(document.getElementById('tauxH')?.value) || 0;
  total += h * t;
  document.getElementById('totalTTC').textContent = total.toFixed(2) + ' €';
}

// ── Zones endommagées ──
const dmgState = {};
function toggleDmg(zone) {
  dmgState[zone] = !dmgState[zone];
  const el = document.getElementById('dmg-' + zone);
  if (dmgState[zone]) {
    el.setAttribute('fill', 'rgba(192,57,43,0.3)');
    el.setAttribute('stroke', '#c0392b');
  } else {
    el.setAttribute('fill', 'none');
    el.setAttribute('stroke', '#c0392b');
  }
  document.getElementById('damagesInput').value = Object.keys(dmgState).filter(k => dmgState[k]).join(',');
}

// ── Affichage N° OR ──
document.getElementById('ordre_num').addEventListener('input', function() {
  document.getElementById('orderNumDisplay').textContent = this.value || 'N 99/°25-26';
});

// ── Simulation lecture VIN OBD ──
function readVinOBD() {
  const btn = event.target;
  btn.textContent = '⏳...';
  btn.disabled = true;
  // Simuler une réponse OBD — à remplacer par votre appel API réel
  setTimeout(() => {
    // Ici vous brancherez votre appel fetch('/api/obd/vin')
    const simulatedVIN = 'VF1RFD00' + Math.random().toString(36).substring(2,11).toUpperCase();
    document.getElementById('vinInput').value = simulatedVIN.substring(0,17);
    btn.textContent = '✅ OK';
    btn.disabled = false;
    setTimeout(() => { btn.textContent = '🔌 OBD'; }, 2000);
  }, 1500);
}

// ── Réinitialisation ──
function resetForm() {
  if (confirm('Réinitialiser tous les champs ?')) {
    document.getElementById('orForm').reset();
    document.getElementById('totalTTC').textContent = '— €';
    document.getElementById('orderNumDisplay').textContent = 'N 99/°25-26';
    Object.keys(dmgState).forEach(k => { dmgState[k] = false; });
    ['front','rear','left','right'].forEach(z => {
      const el = document.getElementById('dmg-' + z);
      if (el) { el.setAttribute('fill', 'none'); }
    });
  }
}


// ══════════════════════════════════════════════
// GÉNÉRATION FACTURE PDF — TITRE EXÉCUTOIRE
// ══════════════════════════════════════════════
function genererFacturePDF() {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ unit: 'mm', format: 'a4' });
  const W = doc.internal.pageSize.getWidth();
  const H = doc.internal.pageSize.getHeight();

  // ── Palette ──
  const DARK      = [20, 20, 20];
  const MID       = [90, 90, 90];
  const LIGHT     = [190, 190, 190];
  const GREY_BG   = [235, 235, 235];   // remplace le jaune — gris clair neutre
  const WHITE     = [255, 255, 255];
  const RED_TEXT  = [192, 0, 0];
  const BLUE_DARK = [10, 10, 100];

  const sc = (r,g,b) => doc.setTextColor(r,g,b);
  const sf = (r,g,b) => doc.setFillColor(r,g,b);
  const sd = (r,g,b) => doc.setDrawColor(r,g,b);
  const lw = t       => doc.setLineWidth(t);
  const bx = (x,y,w,h,s) => doc.rect(x,y,w,h,s||'D');

  // ── Données formulaire ──
  const prenom     = document.getElementById('client_prenom')?.value?.trim() || '';
  const nom        = document.getElementById('client_nom')?.value?.trim() || '';
  const adresse    = document.getElementById('client_adresse')?.value?.trim() || '';
  const nomComplet = ('M.' + (prenom ? ' ' + prenom : '') + (nom ? ' ' + nom : '')).toUpperCase().trim();

  const dateRec = document.getElementById('date_reception')?.value || '';
  const annee   = dateRec ? new Date(dateRec).getFullYear() : new Date().getFullYear();
  const dateAff = dateRec
    ? new Date(dateRec).toLocaleDateString('fr-FR', {day:'2-digit', month:'long', year:'numeric'})
    : new Date().toLocaleDateString('fr-FR', {day:'2-digit', month:'long', year:'numeric'});

  // ── Lignes de facturation ──
  const factTbody = document.getElementById('factRows');
  const lignes = [];
  let grandTotal = 0;
  if (factTbody) {
    Array.from(factTbody.querySelectorAll('tr')).forEach(tr => {
      const i    = tr.id.replace('factRow-', '');
      const desc = tr.querySelector('[name=fact_desc_'+i+']')?.value?.trim() || '';
      const qte  = parseFloat(tr.querySelector('[name=fact_qte_'+i+']')?.value) || 0;
      const prix = parseFloat(tr.querySelector('[name=fact_prix_'+i+']')?.value) || 0;
      if (!desc && qte === 0 && prix === 0) return;
      const total = qte * prix;
      grandTotal += total;
      lignes.push({ desc, qte, prix, total });
    });
  }
  // Forfait recyclage fixe
  lignes.push({ desc: 'Frais fixe (Recyclage)', qte: 1, prix: 2.00, total: 2.00 });
  grandTotal += 2.00;
  // Main d'oeuvre
  const moH = parseFloat(document.getElementById('moHeures')?.value) || 0;
  const moT = parseFloat(document.getElementById('tauxH')?.value) || 0;
  if (moH > 0 && moT > 0) {
    const mt = moH * moT;
    lignes.push({ desc: "Main d'oeuvre (" + moH + "h x " + moT.toFixed(2) + "€/h)", qte: 1, prix: mt, total: mt });
    grandTotal += mt;
  }

  // ── Montant en lettres ──
  function enLettres(n) {
    const u = ['','un','deux','trois','quatre','cinq','six','sept','huit','neuf','dix',
               'onze','douze','treize','quatorze','quinze','seize','dix-sept','dix-huit','dix-neuf'];
    const d = ['','','vingt','trente','quarante','cinquante','soixante','soixante','quatre-vingt','quatre-vingt'];
    if (n === 0) return 'zero';
    if (n < 20)  return u[n];
    if (n < 100) {
      const t = Math.floor(n/10), r = n%10;
      if (t===7) return 'soixante-' + u[10+r];
      if (t===9) return 'quatre-vingt-' + (r===0 ? 's' : u[r]);
      return d[t] + (r===1 && t!==8 ? ' et ' : r>0 ? '-' : '') + (r>0 ? u[r] : '');
    }
    return String(n);
  }
  const ti = Math.round(grandTotal);
  const totalLettres = enLettres(ti).charAt(0).toUpperCase() + enLettres(ti).slice(1) + ' euro' + (ti > 1 ? 's' : '');

  // ════════════════════════════════════════════
  // RENDU — PAGE A4
  // ════════════════════════════════════════════
  let y = 7;
  const X = 10, TW = W - 20;

  // ── 1. EN-TÊTE : ETABLISSEMENT  |  NOM DU DEBITEUR ──
  sf(...GREY_BG); sd(...DARK); lw(0.3);
  bx(X, y, TW, 5, 'FD');
  doc.setFont('helvetica','bold'); doc.setFontSize(6.5); sc(...DARK);
  doc.text('ETABLISSEMENT PUBLIC', X + TW*0.25, y+3.5, {align:'center'});
  doc.line(X + TW/2, y, X + TW/2, y+5);
  doc.text('NOM ET ADRESSE DU DEBITEUR', X + TW*0.75, y+3.5, {align:'center'});
  y += 5;

  // ── 2. BLOC ETABLISSEMENT | DEBITEUR (hauteur compacte) ──
  const blkH = 44;
  sf(...WHITE); bx(X, y, TW, blkH, 'FD');
  doc.line(X + TW/2, y, X + TW/2, y+blkH);

  // Colonne gauche — logo école
  const logoB64 = 'data:image/jpeg;base64,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';
  doc.addImage(logoB64, 'JPEG', X+3, y+2, 22, 28);
  doc.setFont('helvetica','normal'); doc.setFontSize(6); sc(...DARK);
  doc.text('Tel. : 02.97.70.70.00', X+4, y+32);
  doc.text('Fax : 02.97.75.73.53', X+4, y+36);
  doc.text('Couriel : gestion.0560018n@ac-rennes.fr', X+4, y+40);
  doc.text('4 avenue de Broceliande — 56380 GUER', X+4, y+44);

  // Colonne droite — débiteur
  const dX = X + TW/2 + 4;
  doc.setFont('helvetica','bold'); doc.setFontSize(11); sc(...DARK);
  doc.text(nomComplet, dX, y+12);
  if (adresse) {
    doc.setFont('helvetica','normal'); doc.setFontSize(7); sc(...MID);
    doc.splitTextToSize(adresse, TW/2 - 10).forEach((l,i) => doc.text(l, dX, y+18+i*3.5));
  }
  y += blkH;

  // ── 3. TITRE ──
  y += 3;
  doc.setFont('helvetica','bold'); doc.setFontSize(11); sc(...DARK);
  doc.text('TITRE EXECUTOIRE', W/2, y, {align:'center'});
  y += 4;
  doc.setFont('helvetica','bolditalic'); doc.setFontSize(8); sc(...DARK);
  doc.text('valant facture', W/2, y, {align:'center'});
  y += 3.5;
  doc.setFont('helvetica','normal'); doc.setFontSize(6); sc(...MID);
  doc.text("Le present titre est executoire en application de l'article L 252A du livre des procedures fiscales", W/2, y, {align:'center'});
  y += 3;
  doc.text("pris, emis et rendu executoire conformement aux dispositions de l'article R421-68 du code de l'Education.", W/2, y, {align:'center'});
  y += 5;

  // ── 4. TABLEAU 4 COLONNES ──
  const c4 = [TW*0.20, TW*0.28, TW*0.27, TW*0.25];
  const h4 = ["exercice d'origine", "emis ou rendu executoire le", "imputation budgetaire", "references\n(a rappeler lors du reglement)"];
  // En-têtes
  sf(...GREY_BG); sd(...DARK); lw(0.3); bx(X, y, TW, 6, 'FD');
  doc.setFont('helvetica','normal'); doc.setFontSize(5.5); sc(...DARK);
  let cx = X;
  c4.forEach((cw, i) => {
    if (i > 0) doc.line(cx, y, cx, y+6);
    const wrapped = doc.splitTextToSize(h4[i].replace('\n',' '), cw-3);
    wrapped.forEach((l,li) => doc.text(l, cx+cw/2, y+2+li*2.5, {align:'center'}));
    cx += cw;
  });
  y += 6;
  // Valeurs (référence laissée vide pour remplissage manuel)
  sf(...WHITE); bx(X, y, TW, 6, 'FD');
  doc.setFont('helvetica','bold'); doc.setFontSize(7.5); sc(...DARK);
  cx = X;
  const v4 = [String(annee), dateAff, '', ''];
  c4.forEach((cw, i) => {
    if (i > 0) { sd(...DARK); doc.line(cx, y, cx, y+6); }
    doc.text(v4[i], cx+cw/2, y+4.2, {align:'center'});
    cx += cw;
  });
  y += 6;

  // ── 5. TEXTE DEMANDE ──
  y += 3;
  doc.setFont('helvetica','normal'); doc.setFontSize(6.5); sc(...DARK);
  doc.text('Je vous prie de bien vouloir verser a reception du present titre executoire, la somme dont le montant figure dans la colonne', W/2, y, {align:'center'});
  y += 3.5;
  doc.text('"somme due" selon les indications donnees en dessous du present titre.', W/2, y, {align:'center'});
  y += 5;

  // ── 6. OBJET ET DÉCOMPTE ──
  sf(...GREY_BG); sd(...DARK); lw(0.3); bx(X, y, TW, 5, 'FD');
  doc.setFont('helvetica','bold'); doc.setFontSize(7); sc(...DARK);
  doc.text('OBJET ET DECOMPTE DE LA RECETTE', W/2, y+3.5, {align:'center'});
  y += 5;

  // Ligne objet
  sf(...WHITE); bx(X, y, TW, 5, 'FD');
  doc.setFont('helvetica','normal'); doc.setFontSize(6.5); sc(...DARK);
  const objetText = (prenom + ' ' + nom).trim().toUpperCase() + ' - reparation du ' + dateAff;
  doc.text(objetText, W/2, y+3.5, {align:'center'});
  y += 5;

  // Sous-titre CALCUL
  sf(...GREY_BG); bx(X, y, TW, 4, 'FD');
  doc.setFont('helvetica','bold'); doc.setFontSize(6); sc(...DARK);
  doc.text('CALCUL', W/2, y+3, {align:'center'});
  y += 4;

  // En-têtes colonnes tableau
  const cN = TW*0.50, cP = TW*0.18, cNb = TW*0.12, cS = TW*0.20;
  sf(...GREY_BG); bx(X, y, TW, 4, 'FD');
  doc.setFont('helvetica','bold'); doc.setFontSize(6); sc(...DARK);
  doc.text('NATURE', X+cN/2, y+3, {align:'center'});
  doc.line(X+cN, y, X+cN, y+4);
  doc.text('PRIX UNITAIRE TTC', X+cN+cP/2, y+3, {align:'center'});
  doc.line(X+cN+cP, y, X+cN+cP, y+4);
  doc.text('NOMBRE', X+cN+cP+cNb/2, y+3, {align:'center'});
  doc.line(X+cN+cP+cNb, y, X+cN+cP+cNb, y+4);
  doc.text('SOMME DUE', X+cN+cP+cNb+cS/2, y+3, {align:'center'});
  y += 4;

  // Lignes articles — fond blanc uni (pas de jaune)
  const rH = 5;
  lignes.forEach(lg => {
    sf(...WHITE); sd(...DARK); lw(0.2); bx(X, y, TW, rH, 'FD');
    doc.setFont('helvetica','normal'); doc.setFontSize(6.5); sc(...DARK);
    doc.text(lg.desc, X+2, y+3.5);
    doc.line(X+cN, y, X+cN, y+rH);
    doc.text(lg.prix.toFixed(2)+' EUR', X+cN+cP/2, y+3.5, {align:'center'});
    doc.line(X+cN+cP, y, X+cN+cP, y+rH);
    doc.text(String(lg.qte), X+cN+cP+cNb/2, y+3.5, {align:'center'});
    doc.line(X+cN+cP+cNb, y, X+cN+cP+cNb, y+rH);
    doc.setFont('helvetica','bold');
    doc.text(lg.total.toFixed(2)+' EUR', X+cN+cP+cNb+cS/2, y+3.5, {align:'center'});
    y += rH;
  });
  // Total
  sf(...GREY_BG); sd(...DARK); lw(0.3); bx(X, y, TW, 5, 'FD');
  [cN, cN+cP, cN+cP+cNb].forEach(ox => doc.line(X+ox, y, X+ox, y+5));
  doc.setFont('helvetica','bold'); doc.setFontSize(6.5); sc(...DARK);
  doc.text('TOTAL DES SOMMES DUES', X+(cN+cP+cNb)/2, y+3.5, {align:'center'});
  doc.text(grandTotal.toFixed(2)+' EUR', X+cN+cP+cNb+cS/2, y+3.5, {align:'center'});
  y += 5;

  // ── 7. ARRÊTÉ EN LETTRES ──
  y += 3;
  doc.setFont('helvetica','normal'); doc.setFontSize(7.5); sc(...DARK);
  doc.text('Arrete le present titre a la somme de :', X, y+3);
  doc.setFont('helvetica','bold'); doc.setFontSize(9); sc(...RED_TEXT);
  doc.text(totalLettres, X+62, y+3);
  y += 8;

  // ── 8. DATE + SIGNATURES ──
  const today    = new Date();
  const dateFait = today.toLocaleDateString('fr-FR', {day:'numeric', month:'long', year:'numeric'});
  doc.setFont('helvetica','normal'); doc.setFontSize(8); sc(...DARK);
  doc.text('Fait a Guer, le ' + dateFait, W-12, y, {align:'right'});
  y += 5;

  const sigW = TW/2;
  doc.setFont('helvetica','bold'); doc.setFontSize(7); sc(...DARK);
  doc.text("L'ordonnateur : le Proviseur", X+sigW/2, y, {align:'center'});
  doc.text('le Gestionnaire', X+sigW+sigW/2, y, {align:'center'});
  y += 3;
  sd(...LIGHT); lw(0.3);
  bx(X+8, y, sigW-16, 20, 'D');
  bx(X+sigW+8, y, sigW-16, 20, 'D');
  doc.setFont('helvetica','italic'); doc.setFontSize(6); sc(...MID);
  doc.text('Signature', X+sigW/2, y+12, {align:'center'});
  doc.text('Signature', X+sigW+sigW/2, y+12, {align:'center'});
  y += 24;

  // ── 9. SÉPARATEUR ──
  sd(...DARK); lw(0.5);
  doc.line(X, y, X+TW, y);
  y += 4;

  // ── 10. SECTION COMPTABLE ──
  const cmpW = TW*0.47, mW = TW*0.53;
  sf(...GREY_BG); sd(...DARK); lw(0.3); bx(X, y, TW, 5.5, 'FD');
  doc.setFont('helvetica','bold'); doc.setFontSize(7); sc(...DARK);
  doc.text('COMPTABLE CHARGE DU RECOUVREMENT', X+cmpW/2, y+3.8, {align:'center'});
  doc.line(X+cmpW, y, X+cmpW, y+5.5);
  doc.text('Moyens de reglement', X+cmpW+mW/2, y+3.8, {align:'center'});
  y += 5.5;

  const yBase = y;
  doc.setFont('helvetica','normal'); doc.setFontSize(6); sc(...DARK);
  const cmpLines = [
    "Monsieur l'agent comptable", 'LPO BROCELIANDE',
    '4 avenue de Broceliande', '56380 GUER',
    '', 'TELEPHONE  02 97 70 70 00',
    'SIRET : 195600 168 000 15',
    'IBAN : FR76 1007 1560 00 00 0010 0204 182',
    'BIC : TRPUFRP1'
  ];
  cmpLines.forEach((l,i) => { if(l) doc.text(l, X+2, yBase+3.5+i*3.3); });

  doc.setFont('helvetica','bold'); doc.setFontSize(6); sc(...DARK);
  let ym = yBase+3.5;
  const mxW = mW-5;
  doc.text("La somme due est a verser des reception a l'agent comptable,", X+cmpW+2, ym, {maxWidth:mxW});
  ym += 3.5; doc.text("au choix :", X+cmpW+2, ym);
  ym += 3.5; doc.setFont('helvetica','normal');
  doc.text("- par cheque bancaire ou postal a l'ordre de l'A.C. du lycee Broceliande", X+cmpW+2, ym, {maxWidth:mxW});
  ym += 3.5; doc.text("- en especes a la caisse du lycee Broceliande", X+cmpW+2, ym, {maxWidth:mxW});
  ym += 4; doc.setFontSize(5.8);
  doc.text("Pour tout renseignement ou reclamation amiable :", X+cmpW+2, ym);
  ym += 3; doc.text("secretariat de gestion du lycee — 02.97.70.70.16", X+cmpW+2, ym, {maxWidth:mxW});
  ym += 3; doc.text("La contestation amiable ne suspend pas le delai de saisine du juge.", X+cmpW+2, ym, {maxWidth:mxW});
  y += 32;

  // ── 11. DÉLAIS ET VOIES DE RECOURS ──
  sf(...WHITE); sd(...DARK); lw(0.3); bx(X, y, TW, 22, 'FD');
  doc.setFont('helvetica','bold'); doc.setFontSize(6.5); sc(...DARK);
  doc.text('Delais et voies de recours', X+3, y+4.5);
  doc.setFont('helvetica','normal'); doc.setFontSize(5.8); sc(...MID);
  const recours = [
    "Le recouvrement des titres executoires est poursuivi jusqu'a opposition devant la juridiction competente (article R 421-68 du code de l'Education).",
    "Toute contestation sur le bien fonde d'une creance de nature administrative doit etre portee, dans le delai de deux mois suivant",
    "sa notification, devant la juridiction administrative competente (decret n 85-29 du 11/01/1985).",
    "Tout recours contentieux relatif a la regularite du present titre doit etre porte, dans un delai de deux mois a compter de sa",
    "notification, devant le tribunal administratif competent."
  ];
  recours.forEach((l,i) => doc.text(l, X+3, y+8+i*3.2, {maxWidth:TW-6}));

  const nomFichier = 'TitreExecutoire_' + (nom||'client').replace(/[^a-zA-Z0-9]/g,'_') + '_' + annee + '.pdf';
  doc.save(nomFichier);
}

// ══════════════════════════════════════════════
// TERMINER L'OR — Validation + envoi AJAX
// ══════════════════════════════════════════════
function terminerOR(id) {

  const manquants = [];

  // ── Champs texte / date / number obligatoires ──
  const champs = [
    { id: 'date_reception',  label: 'Date de réception' },
    { id: 'ordre_num',       label: "Numéro d'ordre de réparation" },
    { id: 'prof',            label: 'Professeur référent' },
    { id: 'client_prenom',   label: 'Prénom client' },
    { id: 'client_nom',      label: 'Nom client' },
    { id: 'marqueInput',     label: 'Marque du véhicule' },
    { id: 'modeleInput',     label: 'Modèle du véhicule' },
    { id: 'typeVehInput',    label: 'Type du véhicule' },
    { id: 'miseCircInput',   label: 'Date 1ère mise en circulation' },
    { id: 'immatInput',      label: 'Immatriculation' },
    { id: 'kmInput',         label: 'Kilométrage' },
    { id: 'vinInput',        label: 'VIN (N° de châssis)' },
    { id: 'info_client',     label: 'Informations client (symptômes)' },
    { id: 'travaux',         label: 'Travaux effectués' },
    { id: 'moHeures',        label: "Main d'œuvre (nb d'heures)" },
    { id: 'date_restitution', label: 'Date de restitution' },
  ];

  champs.forEach(champ => {
    const el = document.getElementById(champ.id) || document.querySelector(`[name="${champ.id}"]`);
    if (!el || !el.value.trim()) {
      manquants.push(champ.label);
      if (el) {
        el.style.borderBottomColor = '#c0392b';
        el.style.background = 'rgba(192,57,43,0.06)';
        setTimeout(() => { el.style.borderBottomColor = ''; el.style.background = ''; }, 3000);
      }
    }
  });

  // ── Réservoir (radio) ──
  if (!document.querySelector('input[name="reservoir"]:checked')) {
    manquants.push('Niveau de réservoir');
  }

  // ── Au moins une ligne de facturation (Forfait/Fournitures) ──
  const lignesFact = document.querySelectorAll('#factRows tr');
  let auMoinsUneLigne = false;
  lignesFact.forEach(tr => {
    const desc  = tr.querySelector('select[name^="fact_desc_"]');
    const libre = tr.querySelector('input[name^="fact_desc_libre_"]');
    const prix  = tr.querySelector('input[name^="fact_prix_"]');
    const val   = desc && desc.value === 'Autre' ? (libre && libre.value.trim()) : (desc && desc.value.trim());
    if (val && prix && prix.value.trim()) auMoinsUneLigne = true;
  });
  if (!auMoinsUneLigne) manquants.push('Forfait / Fournitures / Consommables (au moins une ligne)');

  // ── Taux horaire si heures > 0 ──
  const moH  = document.getElementById('moHeures');
  const tauxH = document.getElementById('tauxH');
  if (moH && parseFloat(moH.value) > 0 && tauxH && !tauxH.value.trim()) {
    manquants.push('Taux horaire (obligatoire si heures > 0)');
    tauxH.style.borderBottomColor = '#c0392b';
    tauxH.style.background = 'rgba(192,57,43,0.06)';
    setTimeout(() => { tauxH.style.borderBottomColor = ''; tauxH.style.background = ''; }, 3000);
  }

  // ── Afficher les erreurs ──
  const ancienne = document.getElementById('alerte-terminer');
  if (ancienne) ancienne.remove();

  if (manquants.length > 0) {
    const alerte = document.createElement('div');
    alerte.id = 'alerte-terminer';
    alerte.style.cssText = 'background:#fdecea;color:#b00020;border-bottom:2px solid #f5c2c7;padding:12px 24px;font-size:13px;display:flex;align-items:flex-start;gap:10px;';
    alerte.innerHTML = `
      <span style="font-size:18px;flex-shrink:0;">❌</span>
      <div>
        <strong>Impossible de terminer l'OR — champs manquants :</strong>
        <ul style="margin:6px 0 0 16px; columns:2; gap:16px;">
          ${manquants.map(c => `<li>${c}</li>`).join('')}
        </ul>
      </div>
      <button onclick="this.parentElement.remove()" style="margin-left:auto;background:none;border:none;font-size:18px;cursor:pointer;color:#b00020;flex-shrink:0;">✕</button>
    `;
    document.querySelector('.toolbar').insertAdjacentElement('afterend', alerte);
    alerte.scrollIntoView({ behavior: 'smooth', block: 'start' });
    return;
  }

  if (!confirm('Marquer cet OR comme terminé ?\nIl sera transféré dans la page Validation pour être validé par le professeur.')) return;

  fetch('terminer_or.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'intervention_id=' + id
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      const btn = document.querySelector('button[onclick="terminerOR(' + id + ')"]');
      if (btn) {
        btn.disabled = true;
        btn.textContent = '✅ Terminé';
        btn.style.opacity = '0.6';
        btn.style.cursor = 'default';
      }
      const ancienneAlerte = document.getElementById('alerte-terminer');
      if (ancienneAlerte) ancienneAlerte.remove();
      const bar = document.createElement('div');
      bar.style.cssText = 'background:#e6f9e6;color:#2a7a2a;border-bottom:2px solid #a3d9a3;padding:10px 24px;font-size:13px;display:flex;align-items:center;gap:8px;';
      bar.innerHTML = '✅ <strong>OR marqué comme terminé</strong> — Il apparaît maintenant dans la page Validation.';
      document.querySelector('.toolbar').insertAdjacentElement('afterend', bar);
    } else {
      alert('Erreur : ' + (data.error || 'inconnue'));
    }
  })
  .catch(() => alert('Erreur réseau.'));
}

// ── Date du jour par défaut ──
document.getElementById('date_reception').valueAsDate = new Date();

// ══════════════════════════════════════════════
// AUTOCOMPLETE VIN — Recherche BDD
// ══════════════════════════════════════════════
let vinTimeout = null;

function rechercheVin() {
  clearTimeout(vinTimeout);
  const vin = document.getElementById('vinInput').value.trim();

  if (vin.length < 3) {
    fermerSuggestionsVin();
    return;
  }

  vinTimeout = setTimeout(() => {
    fetch(`recherche_vin.php?vin=${encodeURIComponent(vin)}`)
      .then(r => r.json())
      .then(vehicules => afficherSuggestionsVin(vehicules))
      .catch(() => fermerSuggestionsVin());
  }, 250);
}

function afficherSuggestionsVin(vehicules) {
  const box = document.getElementById('vinSuggestions');
  box.innerHTML = '';

  if (!vehicules || vehicules.length === 0) {
    fermerSuggestionsVin();
    return;
  }

  vehicules.forEach(v => {
    const item = document.createElement('div');
    item.style.cssText = 'padding:8px 12px; cursor:pointer; border-bottom:1px solid #e8e8f4; transition:background 0.1s;';
    item.innerHTML = `
      <strong style="color:var(--accent); font-family:monospace; letter-spacing:0.08em;">${v.vin}</strong>
      <span style="color:var(--mid); font-size:11px; margin-left:8px;">${v.marque_modele || '—'}</span>
      <div style="font-size:11px; color:var(--light); margin-top:2px;">
        🚗 ${v.immatriculation || 'Sans immat'} &nbsp;|&nbsp; 👤 ${v.client_prenom || ''} ${v.client_nom || ''}
      </div>
    `;
    item.addEventListener('mouseenter', () => item.style.background = '#f0f0fa');
    item.addEventListener('mouseleave', () => item.style.background = 'white');
    item.addEventListener('mousedown', () => selectionnerVin(v));
    box.appendChild(item);
  });

  box.style.display = 'block';
}

function selectionnerVin(v) {
  // Remplir VIN
  document.getElementById('vinInput').value = v.vin;

  // Remplir les champs véhicule
  if (document.getElementById('marqueInput'))
    document.getElementById('marqueInput').value = v.marque || '';
  if (document.getElementById('modeleInput'))
    document.getElementById('modeleInput').value = v.modele || '';
  if (document.getElementById('immatInput'))
    document.getElementById('immatInput').value = v.immatriculation || '';
  if (document.getElementById('kmInput'))
    document.getElementById('kmInput').value = v.km || '';
  if (document.getElementById('typeVehInput'))
    document.getElementById('typeVehInput').value = v.type_veh || '';
  if (document.getElementById('miseCircInput'))
    document.getElementById('miseCircInput').value = v.mise_circulation || '';

  // Remplir les champs client si disponibles
  if (v.client_prenom) {
    document.getElementById('client_prenom').value = v.client_prenom;
    document.getElementById('client_nom').value    = (v.client_nom || '').toUpperCase();
    document.getElementById('client_id').value     = v.client_id   || '';
    document.getElementById('client_adresse').value = v.client_adresse || '';
    document.getElementById('client_tel').value    = v.client_tel   || '';
    document.getElementById('client_email').value  = v.client_email  || '';
  }

  fermerSuggestionsVin();

  // Flash vert sur les champs remplis
  ['marqueInput','modeleInput','immatInput','kmInput'].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    el.style.borderBottomColor = '#27ae60';
    el.style.background = 'rgba(39,174,96,0.07)';
    setTimeout(() => { el.style.borderBottomColor = ''; el.style.background = ''; }, 1500);
  });
}

function fermerSuggestionsVin() {
  const box = document.getElementById('vinSuggestions');
  if (box) box.style.display = 'none';
}

// ══════════════════════════════════════════════
// AUTOCOMPLETE CLIENT — Recherche BDD
// ══════════════════════════════════════════════
let rechercheTimeout = null;

function rechercheClient() {
  clearTimeout(rechercheTimeout);
  const prenom = document.getElementById('client_prenom').value.trim();
  const nom    = document.getElementById('client_nom').value.trim();

  // On cherche dès qu'il y a au moins 2 caractères dans l'un des champs
  if (prenom.length < 2 && nom.length < 2) {
    fermerSuggestions();
    return;
  }

  rechercheTimeout = setTimeout(() => {
    fetch(`recherche_or.php?ajax=1&prenom=${encodeURIComponent(prenom)}&nom=${encodeURIComponent(nom)}`)
      .then(r => r.json())
      .then(clients => afficherSuggestions(clients))
      .catch(() => fermerSuggestions());
  }, 250); // délai anti-spam 250ms
}

function afficherSuggestions(clients) {
  const box = document.getElementById('clientSuggestions');
  box.innerHTML = '';

  if (!clients || clients.length === 0) {
    fermerSuggestions();
    return;
  }

  clients.forEach(c => {
    const item = document.createElement('div');
    item.style.cssText = 'padding:8px 12px; cursor:pointer; border-bottom:1px solid #e8e8f4; transition:background 0.1s;';
    item.innerHTML = `
      <strong style="color:var(--accent);">${c.prenom} ${c.nom.toUpperCase()}</strong>
      <span style="color:var(--light); font-size:11px; margin-left:6px;">${c.numero || ''}</span>
      <div style="font-size:11px; color:var(--mid); margin-top:2px;">📍 ${c.adresse_postal || '—'} &nbsp; ✉️ ${c.adresse_mail || '—'}</div>
    `;
    item.addEventListener('mouseenter', () => item.style.background = '#f0f0fa');
    item.addEventListener('mouseleave', () => item.style.background = 'white');
    item.addEventListener('mousedown', () => selectionnerClient(c));
    box.appendChild(item);
  });

  // Positionner le dropdown sous le champ client
  const cellule = document.querySelector('.client-cell');
  const rect = cellule.getBoundingClientRect();
  box.style.top  = (rect.bottom + window.scrollY) + 'px';
  box.style.left = (rect.left  + window.scrollX) + 'px';
  box.style.position = 'fixed';
  box.style.top = '';
  box.style.position = 'absolute';

  box.style.display = 'block';
}

function selectionnerClient(c) {
  // Remplir prénom / nom
  document.getElementById('client_prenom').value = c.prenom;
  document.getElementById('client_nom').value    = c.nom.toUpperCase();
  document.getElementById('client_id').value     = c.id_clients;

  // Remplir les autres champs automatiquement
  document.getElementById('client_adresse').value = c.adresse_postal || '';
  document.getElementById('client_tel').value     = c.numero         || '';
  document.getElementById('client_email').value   = c.adresse_mail   || '';

  fermerSuggestions();

  // Feedback visuel : flash vert sur les champs remplis
  ['client_adresse','client_tel','client_email'].forEach(id => {
    const el = document.getElementById(id);
    el.style.borderBottomColor = '#27ae60';
    el.style.background = 'rgba(39,174,96,0.07)';
    setTimeout(() => {
      el.style.borderBottomColor = '';
      el.style.background = '';
    }, 1500);
  });
}

function fermerSuggestions() {
  document.getElementById('clientSuggestions').style.display = 'none';
}
</script>
</body>
</html>
