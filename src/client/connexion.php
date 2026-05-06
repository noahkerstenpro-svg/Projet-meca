<?php
session_start();

// ─── Configuration BDD ─────────────────────────────────────────────────────
$host     = 'meca-mysql';
$port     = '3306';
$dbname   = 'Meca';
$user        = 'root';
$db_password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Erreur BDD : ' . $e->getMessage());
}

$erreur = '';
$succes = '';
$mode   = $_POST['mode'] ?? 'login'; // 'login' ou 'register'

// ─── Inscription ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'register') {
    $prenom         = trim($_POST['prenom']        ?? '');
    $nom            = trim($_POST['nom']           ?? '');
    $adresse_postal = trim($_POST['adresse_postal']?? '');
    $numero         = trim($_POST['numero']        ?? '');
    $adresse_mail   = trim($_POST['adresse_mail']  ?? '');
    $mots_de_passe  = trim($_POST['mots_de_passe'] ?? '');

    if (!$prenom || !$nom || !$adresse_mail || !$mots_de_passe) {
        $erreur = 'Les champs Prénom, Nom, Email et Mot de passe sont obligatoires.';
    } else {
        // Vérifier si l'email existe déjà
        $check = $pdo->prepare("SELECT id_clients FROM Clients WHERE adresse_mail = ?");
        $check->execute([$adresse_mail]);
        if ($check->fetch()) {
            $erreur = 'Un compte existe déjà avec cette adresse mail.';
        } else {
            $hash = password_hash($mots_de_passe, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO Clients (prenom, nom, adresse_mail, mots_de_passe, numéro, adresse_postal) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$prenom, $nom, $adresse_mail, $hash, $numero, $adresse_postal]);
            $succes = 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.';
            $mode   = 'login'; // Revenir en mode connexion après inscription
        }
    }
}

// ─── Connexion ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'login') {
    $adresse_mail  = trim($_POST['adresse_mail']  ?? '');
    $mots_de_passe = trim($_POST['mots_de_passe'] ?? '');

    if (!$adresse_mail || !$mots_de_passe) {
        $erreur = 'Tous les champs sont obligatoires.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM Clients WHERE adresse_mail = ?");
        $stmt->execute([$adresse_mail]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($client && password_verify($mots_de_passe, $client['mots_de_passe'])) {
            // Connexion réussie : on stocke les infos en session
            $_SESSION['client_id']    = $client['id_clients'];
            $_SESSION['client_prenom']= $client['prenom'];
            $_SESSION['client_nom']   = $client['nom'];
            header('Location: reservation.php');
            exit;
        } else {
            $erreur = 'Adresse mail ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Connexion / Inscription</title>

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background-color: #f1f2f3;
}

header {
    background-color: #525151;
    color: white;
    padding: 20px;
    text-align: center;
}

header h1 {
    margin: 0;
    font-size: 1.4rem;
}

.container {
    max-width: 500px;
    margin: 60px auto;
    background: white;
    padding: 50px;
    border-radius: 25px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

h2 {
    text-align: center;
    font-size: 40px;
    margin-top: 0;
    margin-bottom: 10px;
    color: #333;
}

.alert {
    text-align: center;
    padding: 12px 20px;
    border-radius: 15px;
    margin-bottom: 15px;
    font-size: 14px;
    font-weight: bold;
}
.alert.error   { background: #fdf0f0; color: #c0392b; border: 1px solid #f5b7b1; }
.alert.success { background: #eafaf1; color: #27ae60; border: 1px solid #a9dfbf; }

input {
    width: 100%;
    padding: 10px;
    margin-top: 15px;
    border-radius: 15px;
    border: 1px solid #ccc;
    box-sizing: border-box;
    font-size: 14px;
}

input:focus {
    outline: none;
    border-color: #eb5e00;
}

/* Champs cachés en mode connexion */
.register-only {
    display: <?= $mode === 'register' ? 'block' : 'none' ?>;
}

button {
    margin-top: 25px;
    width: 200px;
    padding: 12px;
    margin-left: calc(50% - 100px);
    display: block;
    background-color: #eb5e00;
    color: white;
    border: none;
    border-radius: 50px;
    cursor: pointer;
    font-size: 15px;
    font-weight: bold;
}

button:hover {
    background-color: #d65300;
}

.switch {
    text-align: center;
    margin-top: 20px;
    cursor: pointer;
    color: #525151;
    font-size: 14px;
    text-decoration: underline;
}

.switch:hover {
    color: #eb5e00;
}

footer {
    margin-top: auto;
    padding: 19px;
    text-align: center;
    color: #888;
    font-size: 13px;
}
</style>

</head>
<body>

<header>
    <h1>Atelier Mécanique - Bac Professionnel de Brocéliande</h1>
</header>

<div class="container">

    <h2 id="title"><?= $mode === 'register' ? 'Inscription' : 'Connexion' ?></h2>

    <?php if ($erreur): ?>
        <div class="alert error"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>
    <?php if ($succes): ?>
        <div class="alert success"><?= htmlspecialchars($succes) ?></div>
    <?php endif; ?>

    <form method="POST" action="" id="authForm">
        <input type="hidden" name="mode" id="modeInput" value="<?= htmlspecialchars($mode) ?>">

        <!-- Champs inscription uniquement -->
        <div class="register-only" id="field-prenom">
            <input type="text" name="prenom" placeholder="Prénom" value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
        </div>
        <div class="register-only" id="field-nom">
            <input type="text" name="nom" placeholder="Nom" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
        </div>
        <div class="register-only" id="field-adresse_postal">
            <input type="text" name="adresse_postal" placeholder="Adresse postale" value="<?= htmlspecialchars($_POST['adresse_postal'] ?? '') ?>">
        </div>
        <div class="register-only" id="field-numero">
            <input type="text" name="numero" placeholder="Numéro de téléphone" value="<?= htmlspecialchars($_POST['numero'] ?? '') ?>">
        </div>

        <!-- Champs communs connexion / inscription -->
        <input type="email" name="adresse_mail"  placeholder="Adresse mail"  value="<?= htmlspecialchars($_POST['adresse_mail']  ?? '') ?>">
        <input type="password" name="mots_de_passe" placeholder="Mot de passe">

        <button type="submit" id="submitBtn">
            <?= $mode === 'register' ? "S'inscrire" : 'Se connecter' ?>
        </button>
    </form>

    <div class="switch" id="switchLink" onclick="toggleMode()">
        <?= $mode === 'register' ? 'Déjà un compte ? Se connecter' : "Pas de compte ? S'inscrire" ?>
    </div>

</div>

<script>
let isLogin = <?= $mode === 'login' ? 'true' : 'false' ?>;

const registerFields = document.querySelectorAll('.register-only');
const title          = document.getElementById('title');
const submitBtn      = document.getElementById('submitBtn');
const switchLink     = document.getElementById('switchLink');
const modeInput      = document.getElementById('modeInput');

function toggleMode() {
    isLogin = !isLogin;

    // Textes
    title.innerText     = isLogin ? 'Connexion'    : 'Inscription';
    submitBtn.innerText = isLogin ? 'Se connecter' : "S'inscrire";
    switchLink.innerText= isLogin ? "Pas de compte ? S'inscrire" : 'Déjà un compte ? Se connecter';

    // Champ caché pour indiquer au PHP quel mode traiter
    modeInput.value = isLogin ? 'login' : 'register';

    // Afficher / masquer les champs inscription
    registerFields.forEach(el => {
        el.style.display = isLogin ? 'none' : 'block';
    });
}
</script>

<footer>
    <p>© 2026 Méca Brocéliande</p>
</footer>

</body>
</html>
