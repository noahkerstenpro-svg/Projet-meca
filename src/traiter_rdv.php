<?php
session_start();

// Sécurité : seul un prof connecté peut accéder
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'prof') {
    http_response_code(403);
    exit('Accès refusé');
}

$host   = 'meca-mysql';
$dbname = 'Meca';
$user   = 'root';
$pass   = 'root';

try {
    $pdo = new PDO("mysql:host=$host;port=3306;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Erreur BDD : ' . $e->getMessage());
}

$id     = intval($_POST['id']     ?? 0);
$action = $_POST['action'] ?? '';

if (!$id || !in_array($action, ['confirmer', 'annuler'])) {
    header('Location: rdv_client.php');
    exit;
}

// Nouveau statut selon l'action
$nouveauStatut = ($action === 'confirmer') ? 'confirme' : 'annule';

$stmt = $pdo->prepare("UPDATE intervention SET statut = ? WHERE id_intervention = ? AND source = 'reservation'");
$stmt->execute([$nouveauStatut, $id]);

header('Location: rdv_client.php?action=' . $action . '&ok=1');
exit;
