<?php
// recherche_or.php
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
        "root",
        "root",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// ════════════════════════════════════════════════════════
// MODE AJAX — autocomplétion client depuis ordre_reparation
// Appelé avec ?ajax=1&prenom=...&nom=...
// Retourne du JSON et stoppe l'exécution
// ════════════════════════════════════════════════════════
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    $prenom = trim($_GET['prenom'] ?? '');
    $nom    = trim($_GET['nom']    ?? '');

    if (strlen($prenom) < 2 && strlen($nom) < 2) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([]);
        exit;
    }

    $conditions = [];
    $params     = [];
    if ($prenom !== '') { $conditions[] = 'prenom LIKE :prenom'; $params[':prenom'] = $prenom . '%'; }
    if ($nom    !== '') { $conditions[] = 'nom LIKE :nom';       $params[':nom']    = $nom    . '%'; }
    $where = implode(' AND ', $conditions);

    $sql = "
        SELECT id_clients, prenom, nom, adresse_mail,
               `numéro` AS numero, adresse_postal
        FROM Clients
        WHERE $where
        ORDER BY nom ASC, prenom ASC
        LIMIT 10
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
    exit;
}

// ════════════════════════════════════════════════════════
// MODE NORMAL — interface de recherche HTML
// ════════════════════════════════════════════════════════
$q = $_GET['q'] ?? '';
$resultats = [];

if ($q !== '') {
    $like = "%$q%";
    $sql = "
        SELECT
            i.id_intervention,
            i.date_intervention,
            i.commentaire,
            c.nom,
            c.prenom,
            `c`.`numéro`,
            c.adresse_mail,
            v.`marque/modèle`,
            v.vin
        FROM intervention i
        JOIN Vehicules v ON i.vehicule_id = v.id_vehicules
        JOIN Clients c ON v.client_id = c.id_clients
        WHERE c.nom LIKE ?
        OR c.prenom LIKE ?
        OR v.vin LIKE ?
        OR v.`marque/modèle` LIKE ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$like, $like, $like, $like]);
    $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Recherche OR</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: Arial, sans-serif;
    background: #f4f4f8;
    padding: 30px 20px;
  }
  h2 {
    color: #2c2c6e;
    margin-bottom: 20px;
    font-size: 22px;
  }
  .search-bar {
    display: flex;
    gap: 10px;
    margin-bottom: 24px;
  }
  .search-bar input[type=text] {
    padding: 9px 12px;
    width: 320px;
    border: 1.5px solid #bbb;
    border-radius: 6px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s;
  }
  .search-bar input[type=text]:focus {
    border-color: #2c2c6e;
  }
  .search-bar button {
    padding: 9px 18px;
    background: #2c2c6e;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: background 0.15s;
  }
  .search-bar button:hover {
    background: #1a1a5e;
  }
  h3 {
    color: #2c2c6e;
    margin-bottom: 14px;
    font-size: 16px;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(44,44,110,0.10);
  }
  th {
    background: #2c2c6e;
    color: white;
    padding: 12px 14px;
    text-align: left;
    font-size: 13px;
    letter-spacing: 0.04em;
  }
  td {
    padding: 11px 14px;
    border-bottom: 1px solid #eeeef6;
    font-size: 13px;
    color: #333;
  }
  tr:last-child td {
    border-bottom: none;
  }
  tr:hover td {
    background: #f0f0fa;
  }
  .empty {
    color: #888;
    font-style: italic;
    margin-top: 10px;
  }
  .action-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    background: #4a4a9a;
    color: white;
    border-radius: 5px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    transition: background 0.15s;
  }
  .action-btn:hover {
    background: #2c2c6e;
  }
</style>
</head>
<body>

<h2>🔍 Recherche d'un ordre de réparation</h2>

<form class="search-bar" method="GET">
    <input type="text" name="q"
           placeholder="Nom, prénom, VIN, modèle..."
           value="<?= htmlspecialchars($q) ?>"
           autofocus>
    <button type="submit">Rechercher</button>
</form>

<?php if ($q !== ''): ?>
  <h3>Résultats pour « <?= htmlspecialchars($q) ?> »</h3>

  <?php if (count($resultats) === 0): ?>
    <p class="empty">Aucun résultat trouvé.</p>

  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>ID OR</th>
          <th>Client</th>
          <th>Véhicule</th>
          <th>VIN</th>
          <th>Date</th>
          <th>PDF</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($resultats as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['id_intervention']) ?></td>
          <td><?= htmlspecialchars($r['prenom'] . ' ' . strtoupper($r['nom'])) ?></td>
          <td><?= htmlspecialchars($r['marque/modèle'] ?? '—') ?></td>
          <td style="font-family:monospace; letter-spacing:0.05em;">
            <?= htmlspecialchars($r['vin'] ?? '—') ?>
          </td>
          <td><?= htmlspecialchars($r['date_intervention']) ?></td>
          <td>
            <a class="action-btn"
               href="pdf_or.php?id=<?= $r['id_intervention'] ?>"
               target="_blank">
              📄 Voir PDF
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
<?php endif; ?>

</body>
</html>
