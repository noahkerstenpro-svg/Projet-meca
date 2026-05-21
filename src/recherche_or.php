<?php
/**
 * recherche_client.php
 * Endpoint AJAX — Recherche de clients dans la BDD Meca
 * Retourne un tableau JSON de clients correspondant à la saisie
 */

session_start();

// Vérification de session (même protection que ordre_reparation.php)
if (
    !isset($_SESSION['username']) ||
    !in_array($_SESSION['role'], ['prof', 'eleve'])
) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

// ── Paramètres de connexion à la BDD ──────────────────────────────────────────
// Adaptez ces valeurs à votre configuration locale
$host   = '192.168.11.11';
$port   = '8080';
$dbname = 'Meca';
$user   = 'root';        // ← à adapter
$pass   = 'root';            // ← à adapter

// ── Lecture des paramètres de recherche ───────────────────────────────────────
$prenom = trim($_GET['prenom'] ?? '');
$nom    = trim($_GET['nom']    ?? '');

// Sécurité : on exige au moins 2 caractères dans l'un des champs
if (strlen($prenom) < 2 && strlen($nom) < 2) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([]);
    exit;
}

// ── Connexion PDO ─────────────────────────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['erreur' => 'Connexion BDD impossible']);
    exit;
}

// ── Construction de la requête dynamique ─────────────────────────────────────
// On filtre sur prénom ET/OU nom selon ce qui est renseigné
$conditions = [];
$params     = [];

if ($prenom !== '') {
    $conditions[] = 'prenom LIKE :prenom';
    $params[':prenom'] = $prenom . '%';   // recherche "commence par"
}
if ($nom !== '') {
    $conditions[] = 'nom LIKE :nom';
    $params[':nom'] = $nom . '%';
}

// Si les deux sont renseignés on cherche les clients qui matchent les deux
$where = implode(' AND ', $conditions);

$sql = "
    SELECT
        id_clients,
        prenom,
        nom,
        adresse_mail,
        numero,
        adresse_postal
    FROM Clients
    WHERE $where
    ORDER BY nom ASC, prenom ASC
    LIMIT 10
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $clients = $stmt->fetchAll();
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['erreur' => 'Erreur requête']);
    exit;
}

// ── Réponse JSON ─────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
echo json_encode($clients, JSON_UNESCAPED_UNICODE);
