<?php
session_start();

// ─── Configuration BDD ─────────────────────────────────────────────────────
$host     = 'meca-mysql';
$port     = '3306';
$dbname   = 'Meca';
$user     = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Erreur BDD : ' . $e->getMessage());
}

$message = '';
$editClient = null;
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add') {
    $stmt = $pdo->prepare("INSERT INTO Clients (prenom, nom, adresse_mail, mots_de_passe, numéro, adresse_postal) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([trim($_POST['prenom']), trim($_POST['nom']), trim($_POST['adresse_mail']), trim($_POST['mots_de_passe']), trim($_POST['numero']), trim($_POST['adresse_postal'])]);
    $message = ['type' => 'success', 'text' => 'Client ajouté avec succès.'];
}
if ($action === 'update') {
    $stmt = $pdo->prepare("UPDATE Clients SET prenom=?, nom=?, adresse_mail=?, mots_de_passe=?, numéro=?, adresse_postal=? WHERE id_clients=?");
    $stmt->execute([trim($_POST['prenom']), trim($_POST['nom']), trim($_POST['adresse_mail']), trim($_POST['mots_de_passe']), trim($_POST['numero']), trim($_POST['adresse_postal']), (int)$_POST['id']]);
    $message = ['type' => 'success', 'text' => 'Client mis à jour.'];
}
if ($action === 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM Clients WHERE id_clients = ?");
    $stmt->execute([(int)$_GET['id']]);
    $message = ['type' => 'error', 'text' => 'Client supprimé.'];
}
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM Clients WHERE id_clients = ?");
    $stmt->execute([(int)$_GET['id']]);
    $editClient = $stmt->fetch(PDO::FETCH_ASSOC);
}

$clients = $pdo->query("SELECT * FROM Clients ORDER BY id_clients ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestion Clients – Atelier Mécanique</title>
<style>
  body {
    margin: 0;
    font-family: Arial, sans-serif;
    background-color: #f1f2f3;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
  }

  header {
    background-color: #525151;
    color: white;
    padding: 20px;
    text-align: center;
  }

  header h1 { margin: 0; font-size: 1.4rem; }

  .page-content {
    max-width: 1100px;
    margin: 40px auto;
    padding: 0 20px;
    width: 100%;
  }

  .grid {
    display: grid;
    grid-template-columns: 360px 1fr;
    gap: 30px;
    align-items: start;
  }

  .card {
    background: white;
    padding: 40px;
    border-radius: 25px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
  }

  h2 {
    text-align: center;
    font-size: 28px;
    margin-top: 0;
    margin-bottom: 10px;
    color: #333;
  }

  .card-title {
    text-align: center;
    font-size: 22px;
    font-weight: bold;
    margin-bottom: 20px;
    color: #333;
  }

  input {
    width: 100%;
    padding: 10px;
    margin-top: 12px;
    border-radius: 15px;
    border: 1px solid #ccc;
    box-sizing: border-box;
    font-size: 14px;
  }

  input:focus {
    outline: none;
    border-color: #eb5e00;
  }

  .btn-submit {
    margin-top: 20px;
    width: 100%;
    padding: 12px;
    background-color: #eb5e00;
    color: white;
    border: none;
    border-radius: 50px;
    cursor: pointer;
    font-size: 15px;
    font-weight: bold;
  }

  .btn-submit:hover { background-color: #d65300; }

  .btn-cancel {
    margin-top: 10px;
    width: 100%;
    padding: 12px;
    background-color: #ccc;
    color: #333;
    border: none;
    border-radius: 50px;
    cursor: pointer;
    font-size: 14px;
    text-align: center;
    display: block;
    text-decoration: none;
  }
  .btn-cancel:hover { background-color: #bbb; }

  .alert {
    text-align: center;
    padding: 12px 20px;
    border-radius: 15px;
    margin-bottom: 20px;
    font-size: 14px;
    font-weight: bold;
  }
  .alert.success { background: #eafaf1; color: #27ae60; border: 1px solid #a9dfbf; }
  .alert.error   { background: #fdf0f0; color: #c0392b; border: 1px solid #f5b7b1; }

  /* Table */
  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
    margin-top: 10px;
  }
  thead th {
    background: #525151;
    color: white;
    padding: 10px 12px;
    text-align: left;
  }
  thead th:first-child { border-radius: 12px 0 0 0; }
  thead th:last-child  { border-radius: 0 12px 0 0; }
  tbody tr:nth-child(even) { background: #f8f8f8; }
  tbody tr:hover { background: #fde8d8; }
  tbody td { padding: 10px 12px; border-bottom: 1px solid #eee; }

  .actions { display: flex; gap: 6px; }

  .btn-edit {
    padding: 5px 14px;
    background: #eb5e00;
    color: white;
    border: none;
    border-radius: 20px;
    cursor: pointer;
    font-size: 13px;
    text-decoration: none;
  }
  .btn-edit:hover { background: #d65300; }

  .btn-delete {
    padding: 5px 14px;
    background: #c0392b;
    color: white;
    border: none;
    border-radius: 20px;
    cursor: pointer;
    font-size: 13px;
    text-decoration: none;
  }
  .btn-delete:hover { background: #a93226; }

  .empty {
    text-align: center;
    color: #999;
    padding: 30px;
    font-size: 15px;
  }

  footer {
    margin-top: auto;
    padding: 19px;
    text-align: center;
    color: #888;
    font-size: 13px;
  }

  @media (max-width: 800px) {
    .grid { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>

<header>
  <h1>Atelier Mécanique - Bac Professionnel de Brocéliande</h1>
</header>

<div class="page-content">

  <?php if ($message): ?>
    <div class="alert <?= $message['type'] ?>"><?= htmlspecialchars($message['text']) ?></div>
  <?php endif; ?>

  <div class="grid">

    <!-- Formulaire -->
    <div class="card">
      <div class="card-title"><?= $editClient ? '✏️ Modifier client' : 'Nouveau client' ?></div>
      <form method="POST" action="">
        <input type="hidden" name="action" value="<?= $editClient ? 'update' : 'add' ?>">
        <?php if ($editClient): ?>
          <input type="hidden" name="id" value="<?= $editClient['id_clients'] ?>">
        <?php endif; ?>

        <input type="text"     name="prenom"        placeholder="Prénom"           required value="<?= htmlspecialchars($editClient['prenom']          ?? '') ?>">
        <input type="text"     name="nom"            placeholder="Nom"              required value="<?= htmlspecialchars($editClient['nom']              ?? '') ?>">
        <input type="email"    name="adresse_mail"   placeholder="Adresse mail"     required value="<?= htmlspecialchars($editClient['adresse_mail']     ?? '') ?>">
        <input type="text"     name="mots_de_passe"  placeholder="Mot de passe"     required value="<?= htmlspecialchars($editClient['mots_de_passe']    ?? '') ?>">
        <input type="text"     name="numero"         placeholder="Numéro de tél."           value="<?= htmlspecialchars($editClient['numéro']            ?? '') ?>">
        <input type="text"     name="adresse_postal" placeholder="Adresse postale"          value="<?= htmlspecialchars($editClient['adresse_postal']    ?? '') ?>">

        <button type="submit" class="btn-submit">
          <?= $editClient ? 'Enregistrer' : 'Ajouter le client' ?>
        </button>
        <?php if ($editClient): ?>
          <a href="clients.php" class="btn-cancel">Annuler</a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Tableau -->
    <div class="card">
      <div class="card-title">Liste des clients (<?= count($clients) ?>)</div>
      <?php if (empty($clients)): ?>
        <div class="empty">Aucun client enregistré.</div>
      <?php else: ?>
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
                <a href="?action=edit&id=<?= $c['id_clients'] ?>" class="btn-edit">Éditer</a>
                <a href="?action=delete&id=<?= $c['id_clients'] ?>" class="btn-delete"
                   onclick="return confirm('Supprimer ce client ?')">Suppr.</a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

  </div>
</div>

<footer>
  <p>© 2026 Méca Brocéliande</p>
</footer>

</body>
</html>
