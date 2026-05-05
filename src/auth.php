<?php
// =============================================
//  auth.php — Backend Inscription / Connexion
//  Base de données : Meca > table Clients
// =============================================

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// ---- CONFIGURATION BDD ----
// Adaptez ces valeurs selon votre environnement
$host     = "192.168.11.11";
$dbname   = "Meca";
$db_user  = "root";
$db_pass  = "root";

// ---- CONNEXION PDO ----
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $db_user,
        $db_pass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Erreur de connexion à la base de données : " . $e->getMessage()
    ]);
    exit;
}

// ---- RÉCUPÉRATION DE L'ACTION ----
$action = $_POST['action'] ?? '';

// ============================================================
//  INSCRIPTION
// ============================================================
if ($action === "inscription") {

    $prenom  = trim($_POST['prenom']  ?? '');
    $nom     = trim($_POST['nom']     ?? '');
    $adresse = trim($_POST['adresse'] ?? '');  // adresse mail
    $mdp_raw = trim($_POST['mdp']     ?? '');

    // Validation des champs
    if (!$prenom || !$nom || !$adresse || !$mdp_raw) {
        echo json_encode(["success" => false, "message" => "Tous les champs sont obligatoires."]);
        exit;
    }

    // Vérification format email
    if (!filter_var($adresse, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "message" => "Adresse mail invalide."]);
        exit;
    }

    // Vérifier si l'email existe déjà
    $check = $pdo->prepare("SELECT id_clients FROM Clients WHERE adresse = :adresse");
    $check->execute([':adresse' => $adresse]);
    if ($check->fetch()) {
        echo json_encode(["success" => false, "message" => "Cette adresse mail est déjà utilisée."]);
        exit;
    }

    // Hash du mot de passe (sécurité)
    $mdp_hash = password_hash($mdp_raw, PASSWORD_DEFAULT);

    // Insertion en base de données
    $stmt = $pdo->prepare("INSERT INTO Clients (prenom, nom, adresse, mdp) VALUES (:prenom, :nom, :adresse, :mdp)");
    $stmt->execute([
        ':prenom'  => $prenom,
        ':nom'     => $nom,
        ':adresse' => $adresse,
        ':mdp'     => $mdp_hash,
    ]);

    echo json_encode(["success" => true, "message" => "Compte créé avec succès."]);
    exit;
}

// ============================================================
//  CONNEXION
// ============================================================
if ($action === "connexion") {

    $adresse = trim($_POST['adresse'] ?? '');
    $mdp_raw = trim($_POST['mdp']     ?? '');

    if (!$adresse || !$mdp_raw) {
        echo json_encode(["success" => false, "message" => "Tous les champs sont obligatoires."]);
        exit;
    }

    // Recherche de l'utilisateur par email
    $stmt = $pdo->prepare("SELECT * FROM Clients WHERE adresse = :adresse");
    $stmt->execute([':adresse' => $adresse]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        echo json_encode(["success" => false, "message" => "Identifiants incorrects."]);
        exit;
    }

    // Vérification du mot de passe
    // NOTE : si votre BDD stocke encore les mots de passe en clair (comme "test"),
    // utilisez la comparaison directe ci-dessous temporairement :
    //
    //   if ($mdp_raw !== $client['mdp']) { ... }
    //
    // Une fois tous les comptes recréés avec le formulaire, le hash fonctionnera.

    $mdp_ok = password_verify($mdp_raw, $client['mdp']);

    // Fallback temporaire pour les anciens comptes en clair (ex: compte "test")
    if (!$mdp_ok && $mdp_raw === $client['mdp']) {
        $mdp_ok = true;
    }

    if (!$mdp_ok) {
        echo json_encode(["success" => false, "message" => "Identifiants incorrects."]);
        exit;
    }

    // Démarrage de session (optionnel mais recommandé)
    session_start();
    $_SESSION['id_client'] = $client['id_clients'];
    $_SESSION['prenom']    = $client['prenom'];
    $_SESSION['nom']       = $client['nom'];

    echo json_encode([
        "success" => true,
        "message" => "Connexion réussie.",
        "prenom"  => $client['prenom'],
        "nom"     => $client['nom']
    ]);
    exit;
}

// Action inconnue
echo json_encode(["success" => false, "message" => "Action inconnue."]);
