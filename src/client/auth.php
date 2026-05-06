<?php
// ─── Configuration BDD ─────────────────────────────────────────────────────
$host     = 'meca-mysql';   // nom du container Docker MySQL
$port     = '3306';
$dbname   = 'Meca';
$user     = 'root';
$password = '';             // ton mot de passe MySQL

header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

// ─── CONNEXION ─────────────────────────────────────────────────────────────
if ($action === 'login') {
    $email    = trim($data['username'] ?? '');
    $password = trim($data['password'] ?? '');

    if (!$email || !$password) {
        echo json_encode(['success' => false, 'message' => 'Champs manquants.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM Clients WHERE adresse_mail = ?");
    $stmt->execute([$email]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        echo json_encode(['success' => false, 'message' => 'Adresse mail introuvable.']);
        exit;
    }

    // Vérification mot de passe (texte brut pour l'instant)
    if ($client['mots_de_passe'] !== $password) {
        echo json_encode(['success' => false, 'message' => 'Mot de passe incorrect.']);
        exit;
    }

    session_start();
    $_SESSION['client_id']  = $client['id_clients'];
    $_SESSION['client_nom'] = $client['prenom'] . ' ' . $client['nom'];

    echo json_encode(['success' => true, 'redirect' => 'clients.php']);
    exit;
}

// ─── INSCRIPTION ───────────────────────────────────────────────────────────
if ($action === 'register') {
    $prenom   = trim($data['firstname'] ?? '');
    $nom      = trim($data['lastname']  ?? '');
    $email    = trim($data['username']  ?? '');
    $password = trim($data['password']  ?? '');

    if (!$prenom || !$nom || !$email || !$password) {
        echo json_encode(['success' => false, 'message' => 'Tous les champs sont obligatoires.']);
        exit;
    }

    // Vérifier si l'email existe déjà
    $stmt = $pdo->prepare("SELECT id_clients FROM Clients WHERE adresse_mail = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Cette adresse mail est déjà utilisée.']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO Clients (prenom, nom, adresse_mail, mots_de_passe, numéro, adresse_postal) VALUES (?, ?, ?, ?, '', '')");
    $stmt->execute([$prenom, $nom, $email, $password]);

    echo json_encode(['success' => true, 'message' => 'Compte créé avec succès !']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action inconnue.']);
