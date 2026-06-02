<?php
// confirmer_rdv.php — Confirme ou annule un RDV (appelé en AJAX depuis rdv_client.php)
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'prof') {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

$pdo = new PDO("mysql:host=meca-mysql;port=3306;dbname=Meca;charset=utf8mb4", 'root', 'root');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$intervention_id = (int)($_POST['intervention_id'] ?? 0);
$action          = $_POST['action'] ?? ''; // 'confirme' ou 'annule'

if ($intervention_id <= 0 || !in_array($action, ['confirme', 'annule'])) {
    echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE intervention
        SET statut_rdv = :action
        WHERE id_intervention = :id
          AND source = 'reservation'
    ");
    $stmt->execute([':action' => $action, ':id' => $intervention_id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'RDV introuvable']);
        exit;
    }

    // Retourner le nouveau compteur de RDV en attente (pour mettre à jour le badge)
    $nb = $pdo->query("SELECT COUNT(*) FROM intervention WHERE source = 'reservation' AND statut_rdv = 'en_attente'")->fetchColumn();

    echo json_encode(['success' => true, 'nb_attente' => (int)$nb]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
