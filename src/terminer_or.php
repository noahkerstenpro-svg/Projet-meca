<?php
// terminer_or.php — Marque un OR comme terminé (appelé en AJAX depuis ordre_reparation.php)
// Différence : terminer = travaux finis (élève/prof), valider = approbation finale (prof uniquement)
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['prof', 'eleve'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

$host   = 'meca-mysql';
$dbname = 'Meca';
$user   = 'root';
$pass   = 'root';

try {
    $pdo = new PDO("mysql:host=$host;port=3306;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $intervention_id = (int)($_POST['intervention_id'] ?? 0);

    if ($intervention_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID invalide']);
        exit;
    }

    // Passe statut → 'termine' (sera visible dans validation.php, invisible dans recherche_or.php)
    $stmt = $pdo->prepare("
        UPDATE intervention
        SET statut = 'termine'
        WHERE id_intervention = :id
          AND statut = 'en_cours'
    ");
    $stmt->execute([':id' => $intervention_id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'OR introuvable ou déjà terminé']);
        exit;
    }

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
