<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

$data   = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

// ── CONNEXION ──────────────────────────────────────────────────────────────
if ($action === 'login') {

    $email    = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';

    if (!$email || !$password) {
        echo json_encode(['success' => false, 'message' => 'Tous les champs sont obligatoires.']);
        exit;
    }

    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT * FROM Clients WHERE adresse = :adresse LIMIT 1');
    $stmt->execute([':adresse' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['mdp'])) {
        $_SESSION['auth']      = true;
        $_SESSION['id']        = $user['id_clients'];
        $_SESSION['prenom']    = $user['prenom'];
        $_SESSION['nom']       = $user['nom'];
        $_SESSION['adresse']   = $user['adresse'];
        echo json_encode(['success' => true, 'redirect' => 'reservation.html']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Identifiant ou mot de passe incorrect.']);
    }
    exit;
}

// ── INSCRIPTION ────────────────────────────────────────────────────────────
if ($action === 'register') {

    $prenom   = trim($data['firstname'] ?? '');
    $nom      = trim($data['lastname']  ?? '');
    $email    = trim($data['username']  ?? '');
    $password = $data['password'] ?? '';

    if (!$prenom || !$nom || !$email || !$password) {
        echo json_encode(['success' => false, 'message' => 'Tous les champs sont obligatoires.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Adresse mail invalide.']);
        exit;
    }

    $pdo  = getDB();

    // Vérifie si l'email existe déjà
    $check = $pdo->prepare('SELECT id_clients FROM Clients WHERE adresse = :adresse LIMIT 1');
    $check->execute([':adresse' => $email]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Cette adresse mail est déjà utilisée.']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare(
        'INSERT INTO Clients (prenom, nom, adresse, mdp) VALUES (:prenom, :nom, :adresse, :mdp)'
    );
    $stmt->execute([
        ':prenom'  => $prenom,
        ':nom'     => $nom,
        ':adresse' => $email,
        ':mdp'     => $hash,
    ]);

    echo json_encode(['success' => true, 'message' => 'Compte créé avec succès !']);
    exit;
}

// Action inconnue
echo json_encode(['success' => false, 'message' => 'Action invalide.']);
?>
