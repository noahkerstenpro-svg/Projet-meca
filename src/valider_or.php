<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'prof') {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=meca-mysql;port=3306;dbname=Meca;charset=utf8mb4", 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $intervention_id = (int)($_POST['intervention_id'] ?? 0);

    if ($intervention_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID invalide']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE intervention SET statut = 'valide' WHERE id_intervention = :id AND statut = 'termine'");
    $stmt->execute([':id' => $intervention_id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'OR introuvable ou pas encore terminé']);
        exit;
    }

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
