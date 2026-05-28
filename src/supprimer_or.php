<?php
// supprimer_or.php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['prof', 'eleve'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

$id = (int)($_POST['intervention_id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID invalide']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=meca-mysql;port=3306;dbname=Meca;charset=utf8mb4", 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("DELETE FROM intervention WHERE id_intervention = :id");
    $stmt->execute([':id' => $id]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
