<?php
require 'vendor/autoload.php';
use Dompdf\Dompdf;

$pdo = new PDO("mysql:host=192.168.11.11;dbname=Meca;charset=utf8mb4", "root", "root");

$id = $_GET['id'] ?? 0;

$sql = "
    SELECT 
        i.*, 
        c.nom, c.prenom, c.adresse_postal, c.numéro,
        v.marque, v.modele, v.vin
    FROM intervention i
    JOIN Vehicules v ON i.vehicule_id = v.id_vehicules
    JOIN Clients c ON v.client_id = c.id_clients
    WHERE i.id_intervention = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("OR introuvable");
}

$html = "
<h2>Ordre de Réparation n° {$data['id_intervention']}</h2>
<p><strong>Client :</strong> {$data['prenom']} {$data['nom']}</p>
<p><strong>Adresse :</strong> {$data['adresse_postal']}</p>
<p><strong>Téléphone :</strong> {$data['numéro']}</p>
<br>
<p><strong>Véhicule :</strong> {$data['marque']} {$data['modele']}</p>
<p><strong>VIN :</strong> {$data['vin']}</p>
<br>
<p><strong>Date :</strong> {$data['date_intervention']}</p>
<p><strong>Commentaire :</strong><br>" . nl2br($data['commentaire']) . "</p>
";

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4');
$dompdf->render();
$dompdf->stream("OR_{$id}.pdf", ["Attachment" => false]);
