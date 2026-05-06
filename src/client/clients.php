<?php
// ─── Configuration de la base de données ───────────────────────────────────
$host     = '192.168.11.11';   // Adresse de ton serveur MySQL
$port     = '3306';
$dbname   = 'Meca';
$user     = 'root';            // Ton utilisateur MySQL
$password = '';                // Ton mot de passe MySQL

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('<div class="alert error">Connexion échouée : ' . htmlspecialchars($e->getMessage()) . '</div>');
}

$message = '';
$editClient = null;

// ─── Traitement des actions ────────────────────────────────────────────────
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// AJOUTER
if ($action === 'add') {
    $stmt = $pdo->prepare("INSERT INTO Clients (prenom, nom, adresse_mail, mots_de_passe, numéro, adresse_postal) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        trim($_POST['prenom']),
        trim($_POST['nom']),
        trim($_POST['adresse_mail']),
        trim($_POST['mots_de_passe']),
        trim($_POST['numero']),
        trim($_POST['adresse_postal']),
    ]);
    $message = '<div class="alert success">✓ Client ajouté avec succès.</div>';
}

// MODIFIER (sauvegarde)
if ($action === 'update') {
    $stmt = $pdo->prepare("UPDATE Clients SET prenom=?, nom=?, adresse_mail=?, mots_de_passe=?, numéro=?, adresse_postal=? WHERE id_clients=?");
    $stmt->execute([
        trim($_POST['prenom']),
        trim($_POST['nom']),
        trim($_POST['adresse_mail']),
        trim($_POST['mots_de_passe']),
        trim($_POST['numero']),
        trim($_POST['adresse_postal']),
        (int)$_POST['id'],
    ]);
    $message = '<div class="alert success">✓ Client mis à jour.</div>';
}

// SUPPRIMER
if ($action === 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM Clients WHERE id_clients = ?");
    $stmt->execute([(int)$_GET['id']]);
    $message = '<div class="alert warning">✗ Client supprimé.</div>';
}

// CHARGER pour édition
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM Clients WHERE id_clients = ?");
    $stmt->execute([(int)$_GET['id']]);
    $editClient = $stmt->fetch(PDO::FETCH_ASSOC);
}

// LIRE tous les clients
$clients = $pdo->query("SELECT * FROM Clients ORDER BY id_clients ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestion Clients – Meca</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Syne:wght@400;600;700&display=swap');

  :root {
    --bg: #0f0f11;
    --surface: #18181c;
    --border: #2a2a30;
    --accent: #e8ff47;
    --accent2: #47c8ff;
    --danger: #ff4757;
    --text: #e8e8ec;
    --muted: #6b6b78;
    --radius: 8px;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    background: var(--bg);
    color: var(--text);
    font-family: 'Syne', sans-serif;
    min-height: 100vh;
    padding: 2rem;
  }

  header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2.5rem;
    border-bottom: 1px solid var(--border);
    padding-bottom: 1.5rem;
  }
  header .logo {
    width: 42px; height: 42px;
    background: var(--accent);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem;
  }
  header h1 { font-size: 1.5rem; font-weight: 700; letter-spacing: -0.02em; }
  header span { color: var(--muted); font-size: 0.9rem; margin-left: auto; font-family: 'DM Mono', monospace; }

  .alert {
    padding: 0.75rem 1.25rem;
    border-radius: var(--radius);
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
    font-weight: 600;
  }
  .alert.success { background: #1a2e0f; border: 1px solid #4caf50; color: #8bc34a; }
  .alert.warning { background: #2e1010; border: 1px solid var(--danger); color: var(--danger); }
  .alert.error   { background: #2e1010; border: 1px solid var(--danger); color: var(--danger); }

  .grid { display: grid; grid-template-columns: 340px 1fr; gap: 2rem; }

  /* ── Formulaire ── */
  .card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1.5rem;
    align-self: start;
  }
  .card h2 {
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--accent);
    margin-bottom: 1.25rem;
  }
  .field { margin-bottom: 1rem; }
  .field label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 0.35rem;
  }
  .field input {
    width: 100%;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    color: var(--text);
    font-family: 'DM Mono', monospace;
    font-size: 0.875rem;
    padding: 0.55rem 0.75rem;
    transition: border-color 0.2s;
    outline: none;
  }
  .field input:focus { border-color: var(--accent2); }

  .btn {
    display: inline-block;
    border: none;
    cursor: pointer;
    font-family: 'Syne', sans-serif;
    font-weight: 700;
    font-size: 0.85rem;
    padding: 0.6rem 1.2rem;
    border-radius: var(--radius);
    transition: opacity 0.15s, transform 0.1s;
    text-decoration: none;
  }
  .btn:hover { opacity: 0.85; transform: translateY(-1px); }
  .btn-primary { background: var(--accent); color: #000; }
  .btn-edit    { background: var(--accent2); color: #000; font-size: 0.78rem; padding: 0.35rem 0.8rem; }
  .btn-delete  { background: var(--danger);  color: #fff; font-size: 0.78rem; padding: 0.35rem 0.8rem; }
  .btn-cancel  { background: var(--border);  color: var(--text); margin-top: 0.5rem; }
  .btn-full    { width: 100%; text-align: center; }

  /* ── Tableau ── */
  .table-wrap { overflow-x: auto; }
  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
  }
  thead th {
    text-align: left;
    padding: 0.75rem 1rem;
    color: var(--muted);
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    border-bottom: 1px solid var(--border);
    white-space: nowrap;
  }
  tbody tr {
    border-bottom: 1px solid var(--border);
    transition: background 0.15s;
  }
  tbody tr:hover { background: var(--surface); }
  tbody td {
    padding: 0.7rem 1rem;
    font-family: 'DM Mono', monospace;
    color: var(--text);
  }
  tbody td:first-child { color: var(--muted); }
  .actions { display: flex; gap: 0.4rem; }

  .empty { text-align: center; padding: 3rem; color: var(--muted); }

  @media (max-width: 900px) {
    .grid { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>

<header>
  <div class="logo">🔧</div>
  <h1>Gestion Clients</h1>
  <span>Base : Meca / Table : Clients</span>
</header>

<?= $message ?>

<div class="grid">

  <!-- ── Formulaire Ajout / Édition ── -->
  <div class="card">
    <h2><?= $editClient ? '✏️ Modifier client' : '+ Nouveau client' ?></h2>
    <form method="POST" action="">
      <input type="hidden" name="action" value="<?= $editClient ? 'update' : 'add' ?>">
      <?php if ($editClient): ?>
        <input type="hidden" name="id" value="<?= $editClient['id_clients'] ?>">
      <?php endif; ?>

      <div class="field">
        <label>Prénom</label>
        <input type="text" name="prenom" required value="<?= htmlspecialchars($editClient['prenom'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Nom</label>
        <input type="text" name="nom" required value="<?= htmlspecialchars($editClient['nom'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Adresse e-mail</label>
        <input type="email" name="adresse_mail" required value="<?= htmlspecialchars($editClient['adresse_mail'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Mot de passe</label>
        <input type="text" name="mots_de_passe" required value="<?= htmlspecialchars($editClient['mots_de_passe'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Numéro de téléphone</label>
        <input type="text" name="numero" value="<?= htmlspecialchars($editClient['numéro'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Adresse postale</label>
        <input type="text" name="adresse_postal" value="<?= htmlspecialchars($editClient['adresse_postal'] ?? '') ?>">
      </div>

      <button type="submit" class="btn btn-primary btn-full">
        <?= $editClient ? 'Enregistrer les modifications' : 'Ajouter le client' ?>
      </button>
      <?php if ($editClient): ?>
        <a href="clients.php" class="btn btn-cancel btn-full">Annuler</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- ── Tableau des clients ── -->
  <div class="card">
    <h2>Liste des clients (<?= count($clients) ?>)</h2>
    <?php if (empty($clients)): ?>
      <div class="empty">Aucun client dans la base.</div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Prénom</th>
            <th>Nom</th>
            <th>Email</th>
            <th>Tél.</th>
            <th>Adresse</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($clients as $c): ?>
          <tr>
            <td><?= $c['id_clients'] ?></td>
            <td><?= htmlspecialchars($c['prenom']) ?></td>
            <td><?= htmlspecialchars($c['nom']) ?></td>
            <td><?= htmlspecialchars($c['adresse_mail']) ?></td>
            <td><?= htmlspecialchars($c['numéro']) ?></td>
            <td><?= htmlspecialchars($c['adresse_postal']) ?></td>
            <td>
              <div class="actions">
                <a href="?action=edit&id=<?= $c['id_clients'] ?>" class="btn btn-edit">Éditer</a>
                <a href="?action=delete&id=<?= $c['id_clients'] ?>"
                   class="btn btn-delete"
                   onclick="return confirm('Supprimer ce client ?')">Suppr.</a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div>
</body>
</html>
