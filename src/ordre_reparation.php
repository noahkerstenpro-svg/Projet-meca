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
    grid-template-columns: 1fr;
    border-bottom: 1.5px solid var(--border-strong);
  }
  .client-cell {
    padding: 8px 12px;
  }

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

  }
</style>
</head>
<body>

<!-- TOOLBAR -->
<div class="toolbar">
  <span class="toolbar-title">📋 Ordre de Réparation — Cité scolaire de Brocéliande</span>
  <div class="toolbar-actions">
    <button class="btn btn-outline" type="button" onclick="resetForm()">🗑 Réinitialiser</button>
    <input type="text" id="factureRef" placeholder="Référence (ex: 2026/AP/MB147)" style="padding:5px 10px; border-radius:4px; border:1.5px solid rgba(255,255,255,0.5); background:rgba(255,255,255,0.1); color:white; font-family:inherit; font-size:12px; width:200px;" title="Référence à rappeler lors du règlement">
    <button class="btn btn-primary" type="button" onclick="genererFacturePDF()" title="Télécharger la Facture Titre Exécutoire en PDF">📄 Facture PDF</button>
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
        <span class="cell-label" style="margin-top:6px; display:block;">Professeur référent</span>
        <input type="text" name="prof" id="prof" placeholder="Nom du professeur référent" style="font-weight:600;">
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


</div><!-- end .page-wrapper -->

<script>
// ── Lignes de facturation dynamiques ──
let lineCount = 0;
const tbody = document.getElementById('factRows');

function addFactLine() {
  const i = lineCount++;
  const tr = document.createElement('tr');
  tr.id = 'factRow-' + i;
  tr.innerHTML = `
    <td><input type="text" name="fact_desc_${i}" placeholder="Désignation" style="width:100%;"></td>
    <td><input type="number" name="fact_qte_${i}" min="0" step="1" placeholder="0" style="text-align:center; width:100%;" oninput="calcTotal()"></td>
    <td><input type="text" name="fact_ref_${i}" placeholder="REF" style="width:100%;"></td>
    <td><input type="number" name="fact_prix_${i}" min="0" step="0.01" placeholder="0.00" style="text-align:right; width:100%;" oninput="calcTotal()"></td>
    <td style="text-align:center; width:28px;">
      <button type="button" onclick="removeFactLine(${i})" style="background:none; border:none; color:var(--red); cursor:pointer; font-size:14px; padding:0 4px;" title="Supprimer la ligne">×</button>
    </td>
  `;
  tbody.appendChild(tr);
}

function removeFactLine(i) {
  const row = document.getElementById('factRow-' + i);
  if (row) { row.remove(); calcTotal(); }
}

// 1 ligne par défaut au chargement
addFactLine();

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

  // Palette
  const BLACK  = [0, 0, 0];
  const DARK   = [30, 30, 30];
  const MID    = [80, 80, 80];
  const LIGHT  = [200, 200, 200];
  const HEADER_BG = [220, 220, 230];
  const ROW_ODD   = [255, 255, 204]; // jaune pâle comme sur le document
  const WHITE  = [255, 255, 255];
  const RED_TEXT = [192, 0, 0];
  const BLUE_DARK = [0, 0, 128];

  function sc(r,g,b)  { doc.setTextColor(r,g,b); }
  function sf(r,g,b)  { doc.setFillColor(r,g,b); }
  function sd(r,g,b)  { doc.setDrawColor(r,g,b); }
  function lw(t)      { doc.setLineWidth(t); }
  function rect(x,y,w,h,s) { doc.rect(x,y,w,h,s||'D'); }

  // ── Récupération des données du formulaire ──
  const prenom   = document.getElementById('client_prenom')?.value?.trim() || '';
  const nom      = document.getElementById('client_nom')?.value?.trim() || '';
  const adresse  = document.getElementById('client_adresse')?.value?.trim() || '';
  const nomComplet = ('M.' + (prenom + ' ' + nom).trim()).toUpperCase();

  const dateRec  = document.getElementById('date_reception')?.value || '';
  const annee    = dateRec ? new Date(dateRec).getFullYear() : new Date().getFullYear();
  const dateAff  = dateRec
    ? new Date(dateRec).toLocaleDateString('fr-FR', {day:'2-digit',month:'long',year:'numeric'})
    : new Date().toLocaleDateString('fr-FR', {day:'2-digit',month:'long',year:'numeric'});

  const orNum    = document.getElementById('ordre_num')?.value?.trim() || '—';
  const refField = document.getElementById('factureRef')?.value?.trim() || '';

  // Calculer les lignes & total
  const factTbody = document.getElementById('factRows');
  const factRows  = factTbody ? Array.from(factTbody.querySelectorAll('tr')) : [];
  const lignes = [];
  let grandTotal = 0;

  for (const tr of factRows) {
    const idx  = tr.id.replace('factRow-', '');
    const desc = tr.querySelector('[name=fact_desc_' + idx + ']')?.value?.trim() || '';
    const qte  = parseFloat(tr.querySelector('[name=fact_qte_'  + idx + ']')?.value) || 0;
    const prix = parseFloat(tr.querySelector('[name=fact_prix_' + idx + ']')?.value) || 0;
    if (!desc && qte === 0 && prix === 0) continue;
    const total = qte * prix;
    grandTotal += total;
    lignes.push({ desc, qte, prix, total });
  }
  // Forfait recyclage toujours présent
  lignes.push({ desc: 'Frais fixe (Recyclage)', qte: 1, prix: 2.00, total: 2.00, isFRC: true });
  grandTotal += 2.00;

  // MO
  const moH = parseFloat(document.getElementById('moHeures')?.value) || 0;
  const moT = parseFloat(document.getElementById('tauxH')?.value) || 0;
  if (moH > 0 && moT > 0) {
    const moTotal = moH * moT;
    lignes.push({ desc: "Main d'œuvre (" + moH + " h × " + moT.toFixed(2) + " €/h)", qte: 1, prix: moTotal, total: moTotal, isMO: true });
    grandTotal += moTotal;
  }

  // Conversion nombre en lettres (simplifié)
  function nombreEnLettres(n) {
    const units = ['','un','deux','trois','quatre','cinq','six','sept','huit','neuf','dix',
      'onze','douze','treize','quatorze','quinze','seize','dix-sept','dix-huit','dix-neuf'];
    const tens  = ['','','vingt','trente','quarante','cinquante','soixante','soixante','quatre-vingt','quatre-vingt'];
    if (n === 0) return 'zéro';
    if (n < 20) return units[n];
    if (n < 100) {
      const t = Math.floor(n/10), u = n%10;
      if (t===7) return 'soixante-' + units[10+u];
      if (t===9) return 'quatre-vingt-' + (u===0?'s':units[u]);
      return tens[t] + (u===1&&t!==8?' et ':u>0?'-':'') + (u>0?units[u]:'');
    }
    return n + '';
  }

  const totalEntier = Math.round(grandTotal);
  const totalLettres = nombreEnLettres(totalEntier).charAt(0).toUpperCase() + nombreEnLettres(totalEntier).slice(1) + ' euro' + (totalEntier > 1 ? 's' : '');

  // ════════════════════════════════════════
  // MISE EN PAGE — TITRE EXÉCUTOIRE
  // ════════════════════════════════════════

  let y = 8;
  sd(...DARK); lw(0.4);

  // ── Bloc titre centré ──
  rect(10, y, W - 20, 7);
  sf(...HEADER_BG); doc.rect(10, y, W - 20, 7, 'F');
  sd(...DARK); doc.rect(10, y, W - 20, 7, 'D');
  doc.setFont('helvetica', 'bold'); doc.setFontSize(8); sc(...DARK);
  doc.text('ETABLISSEMENT PUBLIC', W / 4, y + 4.5, { align: 'center' });
  doc.text('NOM ET ADRESSE DU DEBITEUR', 3 * W / 4, y + 4.5, { align: 'center' });
  y += 7;

  // ── Ligne séparation verticale dans le bloc émetteur/débiteur ──
  const midX = W / 2;
  const blkH = 42;
  rect(10, y, W - 20, blkH); // contour global

  // Colonne gauche : établissement
  doc.line(midX, y, midX, y + blkH); // séparation verticale

  // Logo / nom école
  doc.setFont('helvetica', 'bold'); doc.setFontSize(11); sc(...BLUE_DARK);
  doc.text('cité scolaire', 14, y + 8);
  doc.text('Brocéliande', 14, y + 14);
  doc.setFont('helvetica', 'normal'); doc.setFontSize(7); sc(...MID);
  doc.text('académie', 14, y + 19);
  doc.text('de Rennes', 14, y + 23);
  doc.text('Éducation nationale', 14, y + 27);

  // Coordonnées établissement
  doc.setFont('helvetica', 'normal'); doc.setFontSize(7.5); sc(...DARK);
  doc.text('Tel. : 02.97.70.70.00', 14, y + 33);
  doc.text('Fax : 02.97.75.73.53', 14, y + 37);
  doc.text('Couriel : gestion.0560018n@ac-rennes.fr', 14, y + 41);

  // Nom débiteur (colonne droite)
  doc.setFont('helvetica', 'bold'); doc.setFontSize(13); sc(...DARK);
  doc.text(nomComplet, midX + 4, y + 18);
  if (adresse) {
    doc.setFont('helvetica', 'normal'); doc.setFontSize(8.5); sc(...MID);
    const adrLines = doc.splitTextToSize(adresse, midX - 18);
    doc.text(adrLines, midX + 4, y + 26);
  }
  y += blkH;

  // ── Titre TITRE EXÉCUTOIRE ──
  y += 6;
  doc.setFont('helvetica', 'bold'); doc.setFontSize(14); sc(...DARK);
  doc.text('TITRE EXECUTOIRE', W / 2, y, { align: 'center' });
  y += 5;
  doc.setFont('helvetica', 'bolditalic'); doc.setFontSize(10); sc(...DARK);
  doc.text('valant facture', W / 2, y, { align: 'center' });
  y += 5;
  doc.setFont('helvetica', 'normal'); doc.setFontSize(7); sc(...MID);
  const legalText = "Le présent titre est exécutoire en application de l'article L 252A du livre des procédures fiscales\npris, émis et rendu exécutoire conformément aux dispositions de l'article R421-68 du code de l'Education.";
  doc.text(legalText, W / 2, y, { align: 'center' });
  y += 10;

  // ── Tableau 4 colonnes : exercice / date / imputation / références ──
  const tblX = 10, tblW = W - 20, tblH = 8;
  const cols4 = [tblW * 0.25, tblW * 0.25, tblW * 0.25, tblW * 0.25];
  let cx = tblX;
  const hdrs4 = ["exercice d'origine", "émis ou rendu exécutoire le", "imputation budgétaire", "références\nà rappeler lors du règlement"];
  sf(...HEADER_BG); doc.rect(tblX, y, tblW, tblH, 'F');
  sd(...DARK); lw(0.3); doc.rect(tblX, y, tblW, tblH, 'D');
  doc.setFont('helvetica', 'normal'); doc.setFontSize(6.5); sc(...DARK);
  hdrs4.forEach((h, i) => {
    if (i > 0) doc.line(cx, y, cx, y + tblH);
    const lines = h.split('\n');
    lines.forEach((l, li) => doc.text(l, cx + cols4[i]/2, y + 3 + li * 3.5, {align:'center'}));
    cx += cols4[i];
  });
  y += tblH;

  // Valeurs de la ligne exercice
  cx = tblX;
  sf(...WHITE); doc.rect(tblX, y, tblW, 8, 'FD');
  sd(...DARK); doc.rect(tblX, y, tblW, 8, 'D');
  doc.setFont('helvetica', 'bold'); doc.setFontSize(9); sc(...DARK);
  const vals4 = [String(annee), dateAff, '', refField || '— à compléter —'];
  vals4.forEach((v, i) => {
    if (i > 0) { sd(...DARK); doc.line(cx, y, cx, y + 8); }
    sc(i === 3 && !refField ? [192, 0, 0] : DARK);
    doc.text(v, cx + cols4[i]/2, y + 5.5, {align:'center'});
    cx += cols4[i];
  });
  y += 8;

  // ── Texte de demande de versement ──
  y += 4;
  doc.setFont('helvetica', 'normal'); doc.setFontSize(8); sc(...DARK);
  doc.text('Je vous prie de bien vouloir verser à réception du présent titre exécutoire, la somme dont le montant figure dans la colonne', W/2, y, {align:'center'});
  y += 4.5;
  doc.text('"somme due" selon les indications données en dessous du présent titre.', W/2, y, {align:'center'});
  y += 8;

  // ── Section OBJET ET DÉCOMPTE DE LA RECETTE ──
  sf(...HEADER_BG); doc.rect(tblX, y, tblW, 7, 'F');
  sd(...DARK); lw(0.3); doc.rect(tblX, y, tblW, 7, 'D');
  doc.setFont('helvetica', 'bold'); doc.setFontSize(8.5); sc(...DARK);
  doc.text('OBJET ET DECOMPTE DE LA RECETTE', W/2, y + 4.5, {align:'center'});
  y += 7;

  // Ligne objet (nom client + réf OR)
  const travaux = document.querySelector('[name=travaux]')?.value?.trim() || '';
  const objetText = nomComplet.replace('M.','').trim() + ' - réparation du ' + dateAff;
  sf(...WHITE); doc.rect(tblX, y, tblW, 7, 'FD');
  doc.setFont('helvetica', 'normal'); doc.setFontSize(8); sc(...DARK);
  doc.text(objetText, W/2, y + 4.5, {align:'center'});
  y += 7;

  // En-têtes tableau CALCUL
  const cW = { nature: tblW*0.48, prix: tblW*0.20, nb: tblW*0.12, somme: tblW*0.20 };
  sf(...HEADER_BG); doc.rect(tblX, y, tblW, 7, 'F');
  sd(...DARK); doc.rect(tblX, y, tblW, 7, 'D');
  doc.setFont('helvetica', 'bold'); doc.setFontSize(7); sc(...DARK);
  doc.text('CALCUL', W/2, y + 4.5, {align:'center'});
  y += 7;

  sf(...HEADER_BG); doc.rect(tblX, y, tblW, 6, 'F');
  sd(...DARK); doc.rect(tblX, y, tblW, 6, 'D');
  doc.text('NATURE', tblX + cW.nature/2, y + 4, {align:'center'});
  doc.line(tblX + cW.nature, y, tblX + cW.nature, y + 6);
  doc.text('PRIX UNITAIRE TTC', tblX + cW.nature + cW.prix/2, y + 4, {align:'center'});
  doc.line(tblX + cW.nature + cW.prix, y, tblX + cW.nature + cW.prix, y + 6);
  doc.text('NOMBRE', tblX + cW.nature + cW.prix + cW.nb/2, y + 4, {align:'center'});
  doc.line(tblX + cW.nature + cW.prix + cW.nb, y, tblX + cW.nature + cW.prix + cW.nb, y + 6);
  doc.text('SOMME DUE', tblX + cW.nature + cW.prix + cW.nb + cW.somme/2, y + 4, {align:'center'});
  y += 6;

  // Lignes de détail
  const rowH2 = 7;
  lignes.forEach((lg, idx) => {
    sf(idx % 2 === 0 ? ROW_ODD : WHITE);
    doc.rect(tblX, y, tblW, rowH2, 'F');
    sd(...DARK); lw(0.2); doc.rect(tblX, y, tblW, rowH2, 'D');
    doc.setFont('helvetica', lg.isFRC ? 'bold' : 'normal'); doc.setFontSize(8); sc(...DARK);
    doc.text(lg.desc, tblX + 3, y + 4.8);
    doc.line(tblX + cW.nature, y, tblX + cW.nature, y + rowH2);
    doc.text(lg.prix.toFixed(2) + ' €', tblX + cW.nature + cW.prix/2, y + 4.8, {align:'center'});
    doc.line(tblX + cW.nature + cW.prix, y, tblX + cW.nature + cW.prix, y + rowH2);
    doc.text(String(lg.qte), tblX + cW.nature + cW.prix + cW.nb/2, y + 4.8, {align:'center'});
    doc.line(tblX + cW.nature + cW.prix + cW.nb, y, tblX + cW.nature + cW.prix + cW.nb, y + rowH2);
    doc.setFont('helvetica', 'bold');
    doc.text(lg.total.toFixed(2) + ' €', tblX + cW.nature + cW.prix + cW.nb + cW.somme/2, y + 4.8, {align:'center'});
    y += rowH2;
  });

  // 2 lignes vides
  for (let e = 0; e < 2; e++) {
    sf(...WHITE); doc.rect(tblX, y, tblW, rowH2, 'FD');
    sd(...DARK); lw(0.2); doc.rect(tblX, y, tblW, rowH2, 'D');
    [cW.nature, cW.nature+cW.prix, cW.nature+cW.prix+cW.nb].forEach(x => doc.line(tblX+x, y, tblX+x, y+rowH2));
    y += rowH2;
  }

  // Total des sommes dues
  sf(...HEADER_BG); doc.rect(tblX, y, tblW, 7, 'F');
  sd(...DARK); lw(0.3); doc.rect(tblX, y, tblW, 7, 'D');
  [cW.nature, cW.nature+cW.prix, cW.nature+cW.prix+cW.nb].forEach(x => {
    sd(...DARK); doc.line(tblX+x, y, tblX+x, y+7);
  });
  doc.setFont('helvetica', 'bold'); doc.setFontSize(8); sc(...DARK);
  doc.text('TOTAL DES SOMMES DUES', tblX + (cW.nature+cW.prix+cW.nb)/2, y + 4.5, {align:'center'});
  doc.text(grandTotal.toFixed(2) + ' €', tblX + cW.nature + cW.prix + cW.nb + cW.somme/2, y + 4.5, {align:'center'});
  y += 7;

  // ── Arrêté en lettres ──
  y += 6;
  doc.setFont('helvetica', 'normal'); doc.setFontSize(9); sc(...DARK);
  doc.text('Arrêtez le présent titre à la somme de :', tblX, y + 4);
  doc.setFont('helvetica', 'bold'); doc.setFontSize(10); sc(...RED_TEXT);
  doc.text(totalLettres, tblX + 72, y + 4);
  y += 12;

  // Date de l'émission
  const today = new Date();
  const dateFaite = today.toLocaleDateString('fr-FR', {day:'numeric', month:'long', year:'numeric'});
  doc.setFont('helvetica', 'normal'); doc.setFontSize(8.5); sc(...DARK);
  doc.text('Fait à Guer, le ' + dateFaite, W - 12, y, {align:'right'});
  y += 8;

  // ── Bloc signatures : Proviseur / Gestionnaire ──
  const sigW2 = (W - 20) / 2;
  doc.setFont('helvetica', 'bold'); doc.setFontSize(8); sc(...DARK);
  doc.text("L'ordonnateur : le Proviseur", tblX + sigW2/2, y, {align:'center'});
  doc.text("le Gestionnaire", tblX + sigW2 + sigW2/2, y, {align:'center'});
  y += 5;
  // Cases de signature
  sd(...LIGHT); lw(0.3);
  doc.rect(tblX + 10, y, sigW2 - 20, 28, 'D');
  doc.rect(tblX + sigW2 + 10, y, sigW2 - 20, 28, 'D');
  doc.setFont('helvetica', 'italic'); doc.setFontSize(7); sc(...MID);
  doc.text('Signature', tblX + sigW2/2, y + 16, {align:'center'});
  doc.text('Signature', tblX + sigW2 + sigW2/2, y + 16, {align:'center'});
  y += 34;

  // ── Séparateur ──
  sd(...DARK); lw(0.5);
  doc.line(tblX, y, tblX + tblW, y);
  y += 4;

  // ── Section comptable ──
  const compW = tblW * 0.48, moyW = tblW * 0.52;
  sf(...HEADER_BG); doc.rect(tblX, y, tblW, 6, 'F');
  sd(...DARK); lw(0.3); doc.rect(tblX, y, tblW, 6, 'D');
  doc.setFont('helvetica', 'bold'); doc.setFontSize(7.5); sc(...DARK);
  doc.text('COMPTABLE CHARGÉ DU RECOUVREMENT', tblX + compW/2, y + 4, {align:'center'});
  doc.line(tblX + compW, y, tblX + compW, y + 6);
  doc.text('Moyens de règlement', tblX + compW + moyW/2, y + 4, {align:'center'});
  y += 6;

  // Infos comptable
  doc.setFont('helvetica', 'normal'); doc.setFontSize(7); sc(...DARK);
  const compLines = [
    'Monsieur l\'agent comptable',
    'LPO BROCELIANDE',
    '4 avenue de Brocéliande',
    '56380 GUER',
    'TELEPHONE    02 97 70 70 00',
    'SIRET :    195600 168 000 15',
    'IBAN – FR76 1007 1560 00 00 0010 0204 182',
    'BIC – TRPUFRP1'
  ];
  let yc = y + 5;
  compLines.forEach(l => { doc.text(l, tblX + 3, yc); yc += 4; });

  // Moyens de règlement
  doc.setFont('helvetica', 'bold'); doc.setFontSize(7.5); sc(...DARK);
  let ym = y + 5;
  doc.text('La somme due est à verser dès réception à l\'agent comptable de l\'établissement, au choix :', tblX + compW + 3, ym, {maxWidth: moyW - 6});
  ym += 7;
  doc.setFont('helvetica', 'normal'); doc.setFontSize(7);
  doc.text('- par chèque bancaire ou postal à l\'ordre de l\'A.C. du lycée Brocéliande', tblX + compW + 3, ym, {maxWidth: moyW - 6});
  ym += 4.5;
  doc.text('- en espèces à la caisse du lycée Brocéliande', tblX + compW + 3, ym);
  ym += 7;
  doc.setFont('helvetica', 'normal'); doc.setFontSize(6.5);
  const infoText = 'Pour tout renseignement, ou si vous avez une réclamation amiable à formuler,\nadressez-vous au secrétariat de la gestion du lycée (02.97.70.70.16)\nLa contestation amiable ne suspend pas le délai de saisine du juge';
  doc.text(infoText, tblX + compW + 3, ym, {maxWidth: moyW - 6});

  // Boîte délais de voies de recours
  const dely = y + 42;
  if (dely < H - 10) {
    sf(...WHITE); sd(...DARK); lw(0.3);
    doc.rect(tblX, dely, compW, 26, 'FD');
    doc.setFont('helvetica', 'bold'); doc.setFontSize(7); sc(...DARK);
    doc.text('Délais et voies de recours', tblX + 3, dely + 5);
    doc.setFont('helvetica', 'normal'); doc.setFontSize(6);
    const recours = 'Le recouvrement des titres exécutoires est poursuivi jusqu\'à opposition devant la juridiction compétente\n(article R 421-68 du code de l\'Éducation)\nToute contestation sur le bien fondé d\'une créance de nature administrative doit être portée, dans le délai de deux\nmois suivant sa notification, devant la juridiction administrative compétente (décret n° 85-29 du 11/01/1985).';
    doc.text(recours, tblX + 3, dely + 10, {maxWidth: compW - 6});
  }

  // ── Pied de page ──
  doc.setFont('helvetica', 'italic'); doc.setFontSize(6.5); sc(160, 160, 190);
  doc.text('Cité Scolaire de Brocéliande — 4 avenue de Brocéliande, Bellevue – Coëtquidan, 56380 GUER', W/2, H - 3, {align:'center'});

  const nomFichier = 'TitreExecutoire_' + (nom || 'client').replace(/[^a-zA-Z0-9]/g,'_') + '_' + annee + '.pdf';
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
