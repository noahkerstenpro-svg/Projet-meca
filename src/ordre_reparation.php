<?php
session_start();

if (
    !isset($_SESSION['username']) ||
    !in_array($_SESSION['role'], ['prof', 'eleve'])
) {
    header('Location: login.php');
    exit;
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
    font-size: 13px;
    line-height: 1.4;
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
    margin: 24px auto 60px;
    background: var(--white);
    box-shadow: 0 4px 40px rgba(44,44,110,0.12);
    border: 1px solid var(--border);
  }

  /* ── MAIN HEADER ── */
  .form-header {
    background: var(--header-bg);
    color: var(--header-text);
    text-align: center;
    padding: 10px;
    font-size: 15px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    border-bottom: 3px solid #1a1a5e;
  }

  /* ── IDENTITY ROW ── */
  .identity-row {
    display: grid;
    grid-template-columns: 200px 1fr 1fr;
    border-bottom: 1.5px solid var(--border-strong);
  }
  .logo-cell {
    padding: 10px 12px;
    border-right: 1.5px solid var(--border-strong);
    display: flex;
    flex-direction: column;
    justify-content: center;
    background: #f0f0fa;
  }
  .logo-cell .school-name {
    font-family: 'Source Serif 4', serif;
    font-size: 15px;
    font-weight: 600;
    color: var(--accent);
    letter-spacing: 0.04em;
  }
  .logo-cell .school-addr {
    font-size: 10px;
    color: var(--mid);
    margin-top: 2px;
    line-height: 1.5;
  }
  .date-cell {
    padding: 8px 12px;
    border-right: 1.5px solid var(--border-strong);
    display: flex;
    flex-direction: column;
    gap: 4px;
  }
  .order-cell {
    padding: 8px 12px;
    display: flex;
    flex-direction: column;
    gap: 4px;
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
    grid-template-columns: 1fr 1fr;
    border-bottom: 1.5px solid var(--border-strong);
  }
  .client-cell {
    padding: 8px 12px;
    border-right: 1.5px solid var(--border-strong);
  }
  .prof-cell { padding: 8px 12px; }

  /* ── VEHICLE TABLE ── */
  .vehicle-table {
    width: 100%;
    border-collapse: collapse;
    border-bottom: 1.5px solid var(--border-strong);
  }
  .vehicle-table th {
    background: var(--section-bg);
    padding: 5px 10px;
    text-align: center;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--mid);
    border: 1px solid var(--border);
  }
  .vehicle-table td {
    border: 1px solid var(--border);
    padding: 2px 4px;
    vertical-align: middle;
  }

  /* ── RECEPTION / INFO SPLIT ── */
  .reception-row {
    display: grid;
    grid-template-columns: 220px 1fr;
    border-bottom: 1.5px solid var(--border-strong);
    min-height: 180px;
  }
  .reception-cell {
    border-right: 1.5px solid var(--border-strong);
    padding: 8px 10px;
  }
  .info-cell { padding: 8px 12px; }

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
    padding: 5px 12px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--mid);
    border-top: 1.5px solid var(--border-strong);
    border-bottom: 1px solid var(--border);
  }
  .travaux-area {
    padding: 8px 12px;
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
    padding: 6px 10px;
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

  /* ── CONDITIONS VERSO ── */
  .conditions-section {
    background: #f0f0fa;
    border-top: 2px solid var(--border-strong);
    padding: 16px 20px;
    page-break-before: always;
  }

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
      font-size: 11px !important;
      color: #000 !important;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    textarea {
      border: none !important;
      border-bottom: 1px dashed #ccc !important;
      background: transparent !important;
      font-size: 11px !important;
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

    /* Saut de page avant conditions générales */
    .conditions-section { page-break-before: always; }
  }
</style>
</head>
<body>

<!-- TOOLBAR -->
<div class="toolbar">
  <span class="toolbar-title">📋 Ordre de Réparation — Cité scolaire de Brocéliande</span>
  <div class="toolbar-actions">
    <button class="btn btn-outline" type="button" onclick="resetForm()">🗑 Réinitialiser</button>
    <button class="btn btn-primary" type="button" onclick="genererFacturePDF()" title="Télécharger la facture en PDF">📄 Facture PDF</button>
    <button class="btn btn-primary" type="button" onclick="window.print()">🖨 Imprimer / PDF</button>
    <button class="btn btn-primary" type="submit" form="orForm">💾 Enregistrer</button>
  </div>
</div>

<!-- FORM -->
<div class="page-wrapper">
  <form id="orForm" action="save.php" method="POST" autocomplete="off">

    <!-- ═══ TITRE ═══ -->
    <div class="form-header">Ordre de Réparation</div>

    <!-- ═══ IDENTITÉ ═══ -->
    <div class="identity-row">
      <div class="logo-cell">
        <div class="school-name">Brocéliande</div>
        <div class="school-addr">
          Cité scolaire de Brocéliande<br>
          4 avenue de Brocéliande<br>
          Bellevue – Coëtquidan<br>
          56380 GUER
        </div>
      </div>
      <div class="date-cell">
        <span class="cell-label">Date de réception</span>
        <input type="date" name="date_reception" id="date_reception">
      </div>
      <div class="order-cell">
        <span class="cell-label">Ordre de réparation</span>
        <div class="order-num" id="orderNumDisplay">N 99/°25-26</div>
        <input type="text" name="ordre_num" id="ordre_num" placeholder="ex: N 99/°25-26" style="font-size:11px;">
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
              oninput="rechercheClient()"
              onblur="setTimeout(()=>fermerSuggestions(),200)">
          </div>
          <div style="position:relative;">
            <span class="cell-label" style="font-size:9px;">Nom</span>
            <input type="text" name="client_nom" id="client_nom" placeholder="Nom"
              autocomplete="off" style="font-weight:600; text-transform:uppercase;"
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
        <input type="hidden" name="client_id" id="client_id">
        <div class="client-fields">
          <div class="client-field-row">
            <span class="icon">📍</span>
            <input type="text" name="client_adresse" id="client_adresse" placeholder="Adresse">
          </div>
          <div class="client-field-row">
            <span class="icon">📞</span>
            <input type="tel" name="client_tel" id="client_tel" placeholder="Téléphone">
          </div>
          <div class="client-field-row">
            <span class="icon">✉️</span>
            <input type="email" name="client_email" id="client_email" placeholder="E-mail">
          </div>
        </div>
      </div>
      <div class="prof-cell">
        <span class="cell-label">Professeur Référent</span>
        <input type="text" name="prof" placeholder="Nom du professeur référent" style="margin-bottom:6px; font-weight:600;">
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
          <td><input type="text" name="marque" placeholder="ex: Renault"></td>
          <td><input type="text" name="modele" placeholder="ex: Clio IV"></td>
          <td><input type="text" name="type_veh" placeholder="ex: Berline"></td>
          <td><input type="date" name="mise_circulation"></td>
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
          <td><input type="text" name="immat" placeholder="AB-123-CD" style="text-transform:uppercase; letter-spacing:0.1em;"></td>
          <td><input type="number" name="km" placeholder="km" min="0"></td>
          <td colspan="2">
            <div style="display:flex; align-items:center; gap:8px;">
              <input type="text" name="vin" id="vinInput" placeholder="17 caractères" maxlength="17" style="text-transform:uppercase; letter-spacing:0.08em; font-family:monospace;">
              <button type="button" onclick="readVinOBD()" title="Lecture OBD" style="background:var(--accent);color:white;border:none;border-radius:4px;padding:4px 10px;cursor:pointer;font-size:11px;white-space:nowrap;">🔌 OBD</button>
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
        <svg class="car-schema" width="180" height="90" viewBox="0 0 180 90" fill="none" xmlns="http://www.w3.org/2000/svg">
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
          <label><input type="checkbox" name="roue_secours"> Roue de secours</label>
          <label><input type="checkbox" name="ecrou_antivol"> Écrou antivol</label>
          <label><input type="checkbox" name="alarme"> Alarme</label>
          <label style="align-items:flex-start; flex-direction:column; gap:1px;">
            <span>Code alarme :</span>
            <input type="text" name="code_alarme" placeholder="code alarme">
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
        <textarea name="info_client" rows="7" placeholder="Décrire les symptômes ou travaux demandés par le client..."></textarea>
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
      <textarea name="travaux" rows="4" placeholder="Décrire les travaux effectués..."></textarea>
    </div>

    <!-- ═══ FACTURATION ═══ -->
    <table class="fact-table">
      <thead>
        <tr>
          <th style="width:45%">Forfait / Fournitures / Consommables</th>
          <th style="width:10%; text-align:center;">Qté</th>
          <th style="width:20%">Référence</th>
          <th style="width:25%; text-align:right;">Prix total TTC</th>
        </tr>
      </thead>
      <tbody id="factRows">
        <!-- Lines générées par JS -->
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
              <input type="number" name="mo_heures" id="moHeures" placeholder="0" min="0" step="0.5" style="width:60px;" oninput="calcTotal()">
            </div>
          </td>
          <td>
            <div style="display:flex; align-items:center; gap:4px;">
              <span style="font-size:10px; color:var(--light);">Taux horaire :</span>
              <input type="number" name="taux_horaire" id="tauxH" placeholder="0" min="0" style="width:60px;" oninput="calcTotal()">
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
        <input type="date" name="date_restitution">
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

  <!-- ═══ CONDITIONS GÉNÉRALES ═══ -->
  <div class="conditions-section">
    <div class="section-header" style="border:none; background:none; padding:0 0 10px 0; font-size:13px; color:var(--accent);">* CONDITIONS GÉNÉRALES</div>
    <p style="margin-bottom:8px;">Les interventions de maintenance et d'entretien sont <strong>réalisées par les élèves</strong> sous la supervision de l'enseignant référent dans un but pédagogique.</p>
    <p style="margin-bottom:8px;">Pour cette raison les délais d'intervention ne sont en aucun cas garantis.</p>
    <p style="margin-bottom:8px;">Il est fortement conseillé de <strong>ne laisser aucun effet personnel ou de valeur</strong> dans son véhicule.</p>
    <p style="margin-bottom:8px;">L'établissement se dégage de toutes responsabilités en cas de vols.</p>
    <p style="margin-bottom:8px;">Le client s'engage à fournir les pièces et consommables nécessaires à l'intervention.</p>
    <p style="margin-bottom:12px;">Le forfait petites fournitures couvre uniquement le petit matériel (petits colliers, étain, gaine...) nécessaire à la réalisation d'une intervention de qualité, il ne comprend pas le nettoyant frein ou tout autre matériel engendrant des coûts importants pour l'établissement.</p>
    <div style="text-align:right;">
      <label style="font-size:11px; display:flex; align-items:center; gap:6px; justify-content:flex-end;">
        <input type="checkbox" name="cgv_accept"> J'accepte les conditions générales
      </label>
      <div style="margin-top:8px; font-size:10px; color:var(--light); font-style:italic;">Signature du client (précédée de la mention lu et approuvé)</div>
      <div style="border-bottom:1px solid var(--border-strong); width:200px; height:40px; margin-left:auto; margin-top:4px;"></div>
    </div>

    <!-- CONTRÔLES QUALITÉ -->
    <div class="section-header" style="border:none; background:none; padding:16px 0 8px 0; font-size:13px; color:var(--accent); letter-spacing:0.08em;">CONTRÔLES QUALITÉ AVANT RESTITUTION</div>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:4px 30px;">
      <?php
        $checks = [
          "Éclairage","Essuie-glace","Avertisseur sonore","Ceinture de sécurité",
          "Pression pneumatique","Usure pneumatique","Roue de secours","Échappement",
          "État batterie","État courroie accessoire","Niveau huile moteur","Niveau huile transmission",
          "Niveau refroidissement","Niveau liquide de frein","Amortisseurs","Train avant"
        ];
        foreach($checks as $c): ?>
      <label style="display:flex; align-items:center; gap:6px; font-size:11px; padding:2px 0; border-bottom:1px solid #e8e8f4;">
        <input type="checkbox" name="ctrl_<?= strtolower(preg_replace('/[^a-zA-Z]/', '_', $c)) ?>">
        <span><?= $c ?></span>
        <input type="text" name="etat_<?= strtolower(preg_replace('/[^a-zA-Z]/', '_', $c)) ?>" style="flex:1; min-width:40px; border-bottom:1px solid #ccc; font-size:10px;" placeholder="état">
      </label>
      <?php endforeach; ?>
    </div>
    <div style="margin-top:10px; display:flex; align-items:center; gap:10px; font-size:11px;">
      <label><input type="checkbox" name="livret_entretien"> Présence Livret Entretien :</label>
      <input type="date" name="livret_date" style="width:130px;">
    </div>

    <!-- TRAVAUX À PRÉVOIR -->
    <div class="section-header" style="border:none; background:none; padding:14px 0 8px 0; font-size:13px; color:var(--accent); letter-spacing:0.08em;">TRAVAUX À PRÉVOIR ET ÉCHÉANCE</div>
    <div style="border:1px solid var(--border); padding:8px 12px;">
      <div style="font-size:11px; color:var(--mid); margin-bottom:4px;">Travaux à prévoir :</div>
      <textarea name="travaux_a_prevoir" rows="6" style="border:none; border-bottom:1px dashed var(--border); padding:2px; font-size:11px; width:100%;"></textarea>
    </div>
    <div style="display:grid; grid-template-columns:1fr 1fr; margin-top:8px; border:1px solid var(--border);">
      <div style="padding:6px 10px; border-right:1px solid var(--border);">
        <span class="cell-label">Date du prochain CT</span>
        <input type="date" name="prochain_ct">
      </div>
      <div style="padding:6px 10px;">
        <span class="cell-label">Date ou kilométrage de la prochaine révision</span>
        <input type="text" name="prochaine_revision" placeholder="date ou km">
      </div>
    </div>
  </div>

</div><!-- end .page-wrapper -->

<script>
// ── Génération des lignes de facturation ──
const NB_LINES = 8;
const tbody = document.getElementById('factRows');
for (let i = 0; i < NB_LINES; i++) {
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td><input type="text" name="fact_desc_${i}" placeholder="Désignation"></td>
    <td><input type="number" name="fact_qte_${i}" min="0" step="1" placeholder="0" style="text-align:center;" oninput="calcTotal()"></td>
    <td><input type="text" name="fact_ref_${i}" placeholder="REF"></td>
    <td><input type="number" name="fact_prix_${i}" min="0" step="0.01" placeholder="0.00" style="text-align:right;" oninput="calcTotal()"></td>
  `;
  tbody.appendChild(tr);
}

// ── Calcul du total ──
function calcTotal() {
  let total = 7; // FRC 2€ + FPF 5€
  for (let i = 0; i < NB_LINES; i++) {
    const qte  = parseFloat(document.querySelector(`[name=fact_qte_${i}]`)?.value) || 0;
    const prix = parseFloat(document.querySelector(`[name=fact_prix_${i}]`)?.value) || 0;
    total += qte * prix;
  }
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
// GÉNÉRATION FACTURE PDF
// ══════════════════════════════════════════════
function genererFacturePDF() {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ unit: 'mm', format: 'a4' });
  const W = doc.internal.pageSize.getWidth();
  const H = doc.internal.pageSize.getHeight();

  const ACCENT   = [44, 44, 110];
  const LIGHT_BG = [232, 232, 244];
  const BORDER   = [176, 176, 200];
  const MID      = [74, 74, 106];
  const WHITE    = [255, 255, 255];
  const INK      = [26, 26, 46];

  function setColor(r, g, b)  { doc.setTextColor(r, g, b); }
  function setFill(r, g, b)   { doc.setFillColor(r, g, b); }
  function setDraw(r, g, b)   { doc.setDrawColor(r, g, b); }

  // ── HEADER ──
  setFill(...ACCENT);
  doc.rect(0, 0, W, 26, 'F');

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(18);
  setColor(...WHITE);
  doc.text('FACTURE', W / 2, 11, { align: 'center' });
  doc.setFontSize(8);
  doc.setFont('helvetica', 'normal');
  doc.text('ORDRE DE RÉPARATION', W / 2, 17, { align: 'center' });

  const orNum = document.getElementById('ordre_num')?.value?.trim() || '—';
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(9);
  doc.text('N° OR : ' + orNum, W - 10, 10, { align: 'right' });

  const dateRec = document.getElementById('date_reception')?.value || '';
  const dateAff = dateRec
    ? new Date(dateRec).toLocaleDateString('fr-FR', { day:'2-digit', month:'2-digit', year:'numeric' })
    : new Date().toLocaleDateString('fr-FR', { day:'2-digit', month:'2-digit', year:'numeric' });
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(8);
  doc.text('Date : ' + dateAff, W - 10, 16, { align: 'right' });

  // ── ÉMETTEUR / CLIENT ──
  let y = 32;

  setFill(...LIGHT_BG);
  setDraw(...BORDER);
  doc.setLineWidth(0.3);
  doc.roundedRect(10, y, 82, 38, 2, 2, 'FD');

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(7.5);
  setColor(...ACCENT);
  doc.text('ÉMETTEUR', 14, y + 6);
  setColor(...INK);
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(10);
  doc.text('Cité Scolaire de Brocéliande', 14, y + 13);
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(8.5);
  setColor(...MID);
  doc.text('4 avenue de Brocéliande', 14, y + 19);
  doc.text('Bellevue – Coëtquidan', 14, y + 24.5);
  doc.text('56380 GUER', 14, y + 30);

  const prof = document.querySelector('[name=prof]')?.value?.trim() || '—';
  doc.setFontSize(7.5);
  setColor(...ACCENT);
  doc.setFont('helvetica', 'normal');
  doc.text('Réf : ', 14, y + 36);
  doc.setFont('helvetica', 'bold');
  setColor(...INK);
  doc.text(prof, 24, y + 36);

  setFill(...WHITE);
  setDraw(...ACCENT);
  doc.setLineWidth(0.5);
  doc.roundedRect(W / 2 + 4, y, W / 2 - 14, 38, 2, 2, 'FD');

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(7.5);
  setColor(...ACCENT);
  doc.text('CLIENT', W / 2 + 8, y + 6);

  const prenom  = document.getElementById('client_prenom')?.value?.trim() || '';
  const nom     = document.getElementById('client_nom')?.value?.trim() || '';
  const adresse = document.getElementById('client_adresse')?.value?.trim() || '—';
  const tel     = document.getElementById('client_tel')?.value?.trim() || '—';
  const email   = document.getElementById('client_email')?.value?.trim() || '—';
  const nomComplet = (prenom + ' ' + nom).trim() || '—';

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(10);
  setColor(...INK);
  doc.text(nomComplet.toUpperCase(), W / 2 + 8, y + 13);
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(8.5);
  setColor(...MID);
  doc.text(adresse, W / 2 + 8, y + 20);
  doc.text('Tél : ' + tel,   W / 2 + 8, y + 26.5);
  doc.text('Mail : ' + email, W / 2 + 8, y + 33);
  y += 44;

  // ── VÉHICULE ──
  setFill(...LIGHT_BG);
  setDraw(...BORDER);
  doc.setLineWidth(0.3);
  doc.rect(10, y, W - 20, 8, 'FD');
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(8);
  setColor(...ACCENT);
  doc.text('VÉHICULE', 14, y + 5.5);
  const marque = document.querySelector('[name=marque]')?.value?.trim() || '—';
  const modele = document.querySelector('[name=modele]')?.value?.trim() || '—';
  const immat  = document.querySelector('[name=immat]')?.value?.trim() || '—';
  const km     = document.querySelector('[name=km]')?.value?.trim() || '—';
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(8.5);
  setColor(...INK);
  doc.text(marque + ' ' + modele + '   |   Immat : ' + immat + '   |   Km : ' + km, 45, y + 5.5);
  y += 14;

  // ── TABLEAU FACTURATION ──
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(9);
  setColor(...ACCENT);
  doc.text('DÉTAIL DE LA FACTURATION', 10, y);
  y += 5;

  const colX = [10, 88, 106, 130, 160];
  const colW = [78, 18, 24, 30, W - 160 - 10];
  const headers = ['Désignation', 'Qté', 'Référence', 'Prix unit. TTC', 'Total TTC'];
  const rowH = 7;

  setFill(...ACCENT);
  doc.rect(10, y, W - 20, rowH, 'F');
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(8);
  setColor(...WHITE);
  headers.forEach((h, i) => {
    const align = i >= 3 ? 'right' : (i === 1 ? 'center' : 'left');
    const tx = i >= 3 ? colX[i] + colW[i] - 2 : (i === 1 ? colX[i] + colW[i] / 2 : colX[i] + 2);
    doc.text(h, tx, y + 4.8, { align });
  });
  y += rowH;

  let totalPieces = 0;
  let rowIndex = 0;
  const NB = 8;

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(8.5);

  for (let i = 0; i < NB; i++) {
    const desc = document.querySelector('[name=fact_desc_' + i + ']')?.value?.trim() || '';
    const qte  = parseFloat(document.querySelector('[name=fact_qte_' + i + ']')?.value) || 0;
    const ref  = document.querySelector('[name=fact_ref_' + i + ']')?.value?.trim() || '';
    const prix = parseFloat(document.querySelector('[name=fact_prix_' + i + ']')?.value) || 0;
    if (!desc && qte === 0 && prix === 0) continue;
    const total = qte * prix;
    totalPieces += total;
    if (rowIndex % 2 === 0) {
      setFill(245, 245, 252);
      doc.rect(10, y, W - 20, rowH, 'F');
    }
    setDraw(...BORDER);
    doc.setLineWidth(0.2);
    doc.line(10, y + rowH, W - 10, y + rowH);
    setColor(...INK);
    doc.text(desc || '—', colX[0] + 2, y + 4.8);
    doc.text(String(qte), colX[1] + colW[1] / 2, y + 4.8, { align: 'center' });
    doc.text(ref || '—', colX[2] + 2, y + 4.8);
    doc.text(prix.toFixed(2) + ' €', colX[3] + colW[3] - 2, y + 4.8, { align: 'right' });
    doc.setFont('helvetica', 'bold');
    doc.text(total.toFixed(2) + ' €', colX[4] + colW[4] - 2, y + 4.8, { align: 'right' });
    doc.setFont('helvetica', 'normal');
    y += rowH;
    rowIndex++;
  }

  // FRC
  if (rowIndex % 2 === 0) { setFill(245, 245, 252); doc.rect(10, y, W - 20, rowH, 'F'); }
  setDraw(...BORDER); doc.setLineWidth(0.2); doc.line(10, y + rowH, W - 10, y + rowH);
  setColor(...MID); doc.setFont('helvetica', 'italic'); doc.setFontSize(8);
  doc.text('Forfait recyclage', colX[0] + 2, y + 4.8);
  doc.text('1', colX[1] + colW[1] / 2, y + 4.8, { align: 'center' });
  doc.text('FRC', colX[2] + 2, y + 4.8);
  doc.setFont('helvetica', 'normal'); doc.text('2.00 €', colX[3] + colW[3] - 2, y + 4.8, { align: 'right' });
  doc.setFont('helvetica', 'bold');  doc.text('2.00 €', colX[4] + colW[4] - 2, y + 4.8, { align: 'right' });
  y += rowH; rowIndex++;

  // FPF
  if (rowIndex % 2 === 0) { setFill(245, 245, 252); doc.rect(10, y, W - 20, rowH, 'F'); }
  setDraw(...BORDER); doc.setLineWidth(0.2); doc.line(10, y + rowH, W - 10, y + rowH);
  setColor(...MID); doc.setFont('helvetica', 'italic'); doc.setFontSize(8);
  doc.text('Forfait petite fourniture', colX[0] + 2, y + 4.8);
  doc.text('1', colX[1] + colW[1] / 2, y + 4.8, { align: 'center' });
  doc.text('FPF', colX[2] + 2, y + 4.8);
  doc.setFont('helvetica', 'normal'); doc.text('5.00 €', colX[3] + colW[3] - 2, y + 4.8, { align: 'right' });
  doc.setFont('helvetica', 'bold');  doc.text('5.00 €', colX[4] + colW[4] - 2, y + 4.8, { align: 'right' });
  y += rowH + 3;

  // ── MAIN D'OEUVRE ──
  const moH = parseFloat(document.getElementById('moHeures')?.value) || 0;
  const moT = parseFloat(document.getElementById('tauxH')?.value)    || 0;
  const moTotal = moH * moT;
  setFill(...LIGHT_BG);
  setDraw(...BORDER);
  doc.setLineWidth(0.3);
  doc.rect(10, y, W - 20, 12, 'FD');
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(8.5);
  setColor(...ACCENT);
  doc.text("Main d'oeuvre", 14, y + 5);
  doc.setFont('helvetica', 'normal');
  setColor(...INK);
  doc.setFontSize(8);
  if (moH > 0 && moT > 0) {
    doc.text(moH + ' h  x  ' + moT.toFixed(2) + ' €/h', 14, y + 10);
  } else {
    doc.text('Non renseignée', 14, y + 10);
  }
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(11);
  setColor(...INK);
  doc.text(moTotal.toFixed(2) + ' €', W - 12, y + 8, { align: 'right' });
  y += 18;

  // ── TOTAL TTC ──
  const grandTotal = totalPieces + 7 + moTotal;
  setFill(...ACCENT);
  doc.rect(10, y, W - 20, 14, 'F');
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(12);
  setColor(...WHITE);
  doc.text('TOTAL TTC', 16, y + 9.5);
  doc.setFontSize(16);
  doc.text(grandTotal.toFixed(2) + ' €', W - 14, y + 9.5, { align: 'right' });
  y += 20;

  // ── TRAVAUX EFFECTUÉS ──
  const travaux = document.querySelector('[name=travaux]')?.value?.trim() || '';
  if (travaux) {
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8);
    setColor(...ACCENT);
    doc.text('TRAVAUX EFFECTUÉS', 10, y);
    y += 5;
    const lines = doc.splitTextToSize(travaux, W - 24);
    const boxH = Math.max(12, lines.length * 5 + 6);
    setFill(250, 250, 255);
    setDraw(...BORDER);
    doc.setLineWidth(0.3);
    doc.rect(10, y, W - 20, boxH, 'FD');
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8.5);
    setColor(...INK);
    doc.text(lines, 14, y + 5.5);
    y += boxH + 6;
  }

  // ── SIGNATURES ──
  const dateRest = document.querySelector('[name=date_restitution]')?.value || '';
  const dateRestAff = dateRest
    ? new Date(dateRest).toLocaleDateString('fr-FR', { day:'2-digit', month:'2-digit', year:'numeric' })
    : '—';

  const sigY = Math.max(y + 4, H - 52);
  const sigCols = ['Date de restitution', 'Signature référent', 'Signature client', 'Signature DDF', 'Visa Gestion'];
  const sigW = (W - 20) / sigCols.length;
  const sigH = 28;

  sigCols.forEach((label, i) => {
    const sx = 10 + i * sigW;
    setFill(...LIGHT_BG);
    setDraw(...BORDER);
    doc.setLineWidth(0.3);
    doc.rect(sx, sigY, sigW, 8, 'FD');
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(7);
    setColor(...ACCENT);
    doc.text(label, sx + sigW / 2, sigY + 5.2, { align: 'center' });
  });
  sigCols.forEach((_, i) => {
    const sx = 10 + i * sigW;
    setFill(...WHITE);
    setDraw(...BORDER);
    doc.rect(sx, sigY + 8, sigW, sigH, 'FD');
    if (i === 0) {
      doc.setFont('helvetica', 'bold');
      doc.setFontSize(9);
      setColor(...INK);
      doc.text(dateRestAff, sx + sigW / 2, sigY + 8 + sigH / 2 + 1.5, { align: 'center' });
    }
  });

  // ── PIED DE PAGE ──
  doc.setFont('helvetica', 'italic');
  doc.setFontSize(6.5);
  setColor(160, 160, 190);
  doc.text('Cité Scolaire de Brocéliande — 4 avenue de Brocéliande, Bellevue – Coëtquidan, 56380 GUER', W / 2, H - 6, { align: 'center' });
  doc.text('Les interventions sont réalisées par des élèves à titre pédagogique.', W / 2, H - 3, { align: 'center' });

  const nomFichier = 'Facture_OR_' + orNum.replace(/[^a-zA-Z0-9]/g, '_') + '.pdf';
  doc.save(nomFichier);
}

// ── Date du jour par défaut ──
document.getElementById('date_reception').valueAsDate = new Date();

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
