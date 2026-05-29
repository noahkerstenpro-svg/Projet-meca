<?php
// save_or.php — Sauvegarde / mise à jour d'un ordre de réparation
session_start();

if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['prof', 'eleve'])) {
    header('Location: login.php');
    exit;
}

$host   = 'meca-mysql';
$dbname = 'Meca';
$user   = 'root';
$pass   = 'root';

try {
    $pdo = new PDO("mysql:host=$host;port=3306;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ── Récupération des champs POST ──
    $intervention_id = (int)($_POST['intervention_id'] ?? 0);
    $vehicule_id     = (int)($_POST['vehicule_id']     ?? 0);
    $client_id       = (int)($_POST['client_id']       ?? 0);

    // Champs client
    $client_prenom  = trim($_POST['client_prenom']  ?? '');
    $client_nom     = trim($_POST['client_nom']     ?? '');
    $client_adresse = trim($_POST['client_adresse'] ?? '');
    $client_tel     = trim($_POST['client_tel']     ?? '');
    $client_email   = trim($_POST['client_email']   ?? '');

    // Champs véhicule
    $vin             = strtoupper(trim($_POST['vin']             ?? ''));
    $marque          = trim($_POST['marque']          ?? '');
    $modele          = trim($_POST['modele']          ?? '');
    $marque_modele   = trim("$marque $modele");
    $immat           = strtoupper(trim($_POST['immat'] ?? ''));
    $km              = $_POST['km']   !== '' ? (int)$_POST['km']   : null;
    $type_veh        = trim($_POST['type_veh']        ?? '');
    $mise_circ       = trim($_POST['mise_circulation'] ?? '') ?: null;

    // Champs intervention
    $date_reception  = trim($_POST['date_reception'] ?? '') ?: date('Y-m-d');
    $info_client     = trim($_POST['info_client']    ?? '');
    $travaux         = trim($_POST['travaux']        ?? '');

    // ════════════════════════════════════════
    // 1) CLIENT — mise à jour si client_id connu
    // ════════════════════════════════════════
    if ($client_id > 0) {
        $pdo->prepare("
            UPDATE Clients
            SET prenom = :prenom, nom = :nom,
                adresse_postal = :adresse, `numéro` = :tel, adresse_mail = :email
            WHERE id_clients = :id
        ")->execute([
            ':prenom'  => $client_prenom,
            ':nom'     => $client_nom,
            ':adresse' => $client_adresse,
            ':tel'     => $client_tel,
            ':email'   => $client_email,
            ':id'      => $client_id,
        ]);
    } elseif ($client_prenom || $client_nom) {
        // Nouveau client créé depuis un OR — mot de passe placeholder (non utilisable pour connexion)
        $mdp_placeholder = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("
            INSERT INTO Clients (prenom, nom, adresse_postal, `numéro`, adresse_mail, mots_de_passe)
            VALUES (:prenom, :nom, :adresse, :tel, :email, :mdp)
        ");
        $stmt->execute([
            ':prenom'  => $client_prenom,
            ':nom'     => $client_nom,
            ':adresse' => $client_adresse,
            ':tel'     => $client_tel,
            ':email'   => $client_email,
            ':mdp'     => $mdp_placeholder,
        ]);
        $client_id = $pdo->lastInsertId();
    }

    // ════════════════════════════════════════
    // 2) VÉHICULE — mise à jour ou création
    // ════════════════════════════════════════
    if ($vehicule_id > 0) {
        // Véhicule existant → on met à jour
        $pdo->prepare("
            UPDATE Vehicules
            SET vin = :vin, `marque/modèle` = :marque, immatriculation = :immat,
                km = :km, type_veh = :type_veh, mise_circulation = :mise_circ,
                client_id = :client_id
            WHERE id_vehicules = :id
        ")->execute([
            ':vin'       => $vin ?: null,
            ':marque'    => $marque_modele ?: null,
            ':immat'     => $immat ?: null,
            ':km'        => $km,
            ':type_veh'  => $type_veh ?: null,
            ':mise_circ' => $mise_circ,
            ':client_id' => $client_id ?: null,
            ':id'        => $vehicule_id,
        ]);
    } else {
        // Nouveau véhicule (ou VIN inconnu)
        // Vérifier si le VIN existe déjà pour éviter les doublons
        if ($vin) {
            $check = $pdo->prepare("SELECT id_vehicules FROM Vehicules WHERE vin = :vin LIMIT 1");
            $check->execute([':vin' => $vin]);
            $existing = $check->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                $vehicule_id = $existing['id_vehicules'];
                // Mettre à jour avec les nouvelles infos
                $pdo->prepare("
                    UPDATE Vehicules
                    SET `marque/modèle` = :marque, immatriculation = :immat,
                        km = :km, type_veh = :type_veh, mise_circulation = :mise_circ,
                        client_id = COALESCE(:client_id, client_id)
                    WHERE id_vehicules = :id
                ")->execute([
                    ':marque'    => $marque_modele ?: null,
                    ':immat'     => $immat ?: null,
                    ':km'        => $km,
                    ':type_veh'  => $type_veh ?: null,
                    ':mise_circ' => $mise_circ,
                    ':client_id' => $client_id ?: null,
                    ':id'        => $vehicule_id,
                ]);
            }
        }

        if (!$vehicule_id && ($vin || $marque_modele)) {
            $vinFinal = $vin ?: strtoupper(substr(md5(uniqid()), 0, 10));
            $stmt = $pdo->prepare("
                INSERT INTO Vehicules (vin, `marque/modèle`, immatriculation, km, type_veh, mise_circulation, client_id)
                VALUES (:vin, :marque, :immat, :km, :type_veh, :mise_circ, :client_id)
            ");
            $stmt->execute([
                ':vin'       => $vinFinal,
                ':marque'    => $marque_modele ?: null,
                ':immat'     => $immat ?: null,
                ':km'        => $km,
                ':type_veh'  => $type_veh ?: null,
                ':mise_circ' => $mise_circ,
                ':client_id' => $client_id ?: null,
            ]);
            $vehicule_id = $pdo->lastInsertId();
        }
    }

    // ════════════════════════════════════════
    // 3) INTERVENTION — mise à jour ou création
    // ════════════════════════════════════════
    if ($intervention_id > 0) {
        // Mise à jour d'une intervention existante
        $pdo->prepare("
            UPDATE intervention
            SET vehicule_id      = :vehicule_id,
                date_intervention = :date,
                Probleme          = :probleme,
                commentaire       = :commentaire
            WHERE id_intervention = :id
        ")->execute([
            ':vehicule_id'  => $vehicule_id,
            ':date'         => $date_reception,
            ':probleme'     => $info_client,
            ':commentaire'  => $travaux,
            ':id'           => $intervention_id,
        ]);
    } else {
        // Nouvelle intervention
        $stmt = $pdo->prepare("
            INSERT INTO intervention (vehicule_id, prestation_id, date_intervention, `heure_de_préstation`, Probleme, commentaire)
            VALUES (:vehicule_id, NULL, :date, '08:00', :probleme, :commentaire)
        ");
        $stmt->execute([
            ':vehicule_id'  => $vehicule_id ?: null,
            ':date'         => $date_reception,
            ':probleme'     => $info_client,
            ':commentaire'  => $travaux,
        ]);
        $intervention_id = $pdo->lastInsertId();
    }

    // ── Succès : redirection vers l'ordre avec l'id pour permettre futures modifs ──
    header("Location: ordre_reparation.php?intervention_id={$intervention_id}&saved=1");
    exit;

} catch (PDOException $e) {
    // Affichage d'une erreur simple
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Erreur</title></head><body>';
    echo '<div style="font-family:Arial;max-width:600px;margin:60px auto;background:#fdecea;border:1px solid #f5c2c7;border-radius:12px;padding:30px;color:#b00020;">';
    echo '<h2>❌ Erreur lors de l\'enregistrement</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<a href="javascript:history.back()" style="color:#b00020;">← Retour</a>';
    echo '</div></body></html>';
    exit;
}
