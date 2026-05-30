<?php
// save_or.php — Sauvegarde complète de l'ordre de réparation
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

    // ── Champs de base ──
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
    $vin           = strtoupper(trim($_POST['vin']              ?? ''));
    $marque        = trim($_POST['marque']           ?? '');
    $modele        = trim($_POST['modele']           ?? '');
    $marque_modele = trim("$marque $modele");
    $immat         = strtoupper(trim($_POST['immat'] ?? ''));
    $km            = $_POST['km'] !== '' ? (int)$_POST['km'] : null;
    $type_veh      = trim($_POST['type_veh']         ?? '');
    $mise_circ     = trim($_POST['mise_circulation'] ?? '') ?: null;

    // Champs intervention principaux
    $date_reception = trim($_POST['date_reception'] ?? '') ?: date('Y-m-d');
    $info_client    = trim($_POST['info_client']    ?? '');
    $travaux        = trim($_POST['travaux']        ?? '');

    // ── Collecte des lignes de facturation dynamiques ──
    $fact_lines = [];
    $i = 0;
    while (isset($_POST["fact_desc_$i"]) || isset($_POST["fact_qte_$i"])) {
        $desc_select = trim($_POST["fact_desc_$i"]       ?? '');
        $desc_libre  = trim($_POST["fact_desc_libre_$i"] ?? '');
        // Si "Autre" sélectionné → prendre la saisie libre
        $desc = ($desc_select === 'Autre') ? $desc_libre : $desc_select;
        $qte  = trim($_POST["fact_qte_$i"]  ?? '');
        $ref  = trim($_POST["fact_ref_$i"]  ?? '');
        $prix = trim($_POST["fact_prix_$i"] ?? '');
        if ($desc || $qte || $ref || $prix) {
            $fact_lines[] = [
                'desc' => $desc,
                'qte'  => $qte,
                'ref'  => $ref,
                'prix' => $prix,
            ];
        }
        $i++;
    }

    // ── Tous les autres champs → JSON ──
    $donnees = [
        'ordre_num'      => trim($_POST['ordre_num']      ?? ''),
        'prof'           => trim($_POST['prof']           ?? ''),
        'date_restit'    => trim($_POST['date_restitution'] ?? ''),
        'reservoir'      => trim($_POST['reservoir']      ?? ''),
        'damages'        => trim($_POST['damages']        ?? ''),
        'type_griffe'    => isset($_POST['type_griffe'])  ? 1 : 0,
        'type_coup'      => isset($_POST['type_coup'])    ? 1 : 0,
        'roue_secours'   => isset($_POST['roue_secours']) ? 1 : 0,
        'ecrou_antivol'  => isset($_POST['ecrou_antivol'])? 1 : 0,
        'alarme'         => isset($_POST['alarme'])       ? 1 : 0,
        'code_alarme'    => trim($_POST['code_alarme']    ?? ''),
        'cg_accepted'    => isset($_POST['cg_accepted'])  ? 1 : 0,
        'mo_heures'      => trim($_POST['mo_heures']      ?? ''),
        'taux_horaire'   => trim($_POST['taux_horaire']   ?? ''),
        'fact_lines'     => $fact_lines,
        'modele'         => $modele,
    ];

    // ════════════════════════════════════════
    // 1) CLIENT
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
        // Vérifier si ce client existe déjà (par email, ou par prénom+nom)
        $clientExist = null;
        if ($client_email) {
            $chk = $pdo->prepare("SELECT id_clients FROM Clients WHERE adresse_mail = :email LIMIT 1");
            $chk->execute([':email' => $client_email]);
            $clientExist = $chk->fetch(PDO::FETCH_ASSOC);
        }
        if (!$clientExist && $client_prenom && $client_nom) {
            $chk = $pdo->prepare("SELECT id_clients FROM Clients WHERE prenom = :prenom AND nom = :nom LIMIT 1");
            $chk->execute([':prenom' => $client_prenom, ':nom' => $client_nom]);
            $clientExist = $chk->fetch(PDO::FETCH_ASSOC);
        }

        if ($clientExist) {
            // Client trouvé — on met à jour sans créer de doublon
            $client_id = $clientExist['id_clients'];
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
        } else {
            // Nouveau client réel — on crée avec mot de passe placeholder
            $mdp = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
            $pdo->prepare("
                INSERT INTO Clients (prenom, nom, adresse_postal, `numéro`, adresse_mail, mots_de_passe)
                VALUES (:prenom, :nom, :adresse, :tel, :email, :mdp)
            ")->execute([
                ':prenom'  => $client_prenom,
                ':nom'     => $client_nom,
                ':adresse' => $client_adresse,
                ':tel'     => $client_tel,
                ':email'   => $client_email,
                ':mdp'     => $mdp,
            ]);
            $client_id = $pdo->lastInsertId();
        }
    }

    // ════════════════════════════════════════
    // 2) VÉHICULE
    // ════════════════════════════════════════
    if ($vehicule_id > 0) {
        $pdo->prepare("
            UPDATE Vehicules
            SET vin = :vin, marque = :marque, modele = :modele, immatriculation = :immat,
                km = :km, type_veh = :type_veh, mise_circulation = :mise_circ,
                client_id = :client_id
            WHERE id_vehicules = :id
        ")->execute([
            ':vin'       => $vin ?: null,
            ':marque'    => $marque ?: null,
                ':modele'    => $modele ?: null,
            ':immat'     => $immat ?: null,
            ':km'        => $km,
            ':type_veh'  => $type_veh ?: null,
            ':mise_circ' => $mise_circ,
            ':client_id' => $client_id ?: null,
            ':id'        => $vehicule_id,
        ]);
    } else {
        if ($vin) {
            $check = $pdo->prepare("SELECT id_vehicules FROM Vehicules WHERE vin = :vin LIMIT 1");
            $check->execute([':vin' => $vin]);
            $existing = $check->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                $vehicule_id = $existing['id_vehicules'];
                $pdo->prepare("
                    UPDATE Vehicules
                    SET marque = :marque, modele = :modele, immatriculation = :immat,
                        km = :km, type_veh = :type_veh, mise_circulation = :mise_circ,
                        client_id = COALESCE(:client_id, client_id)
                    WHERE id_vehicules = :id
                ")->execute([
                    ':marque'    => $marque ?: null,
                ':modele'    => $modele ?: null,
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
            $pdo->prepare("
                INSERT INTO Vehicules (vin, marque, modele, immatriculation, km, type_veh, mise_circulation, client_id)
                VALUES (:vin, :marque, :modele, :immat, :km, :type_veh, :mise_circ, :client_id)
            ")->execute([
                ':vin'       => $vinFinal,
                ':marque'    => $marque ?: null,
                ':modele'    => $modele ?: null,
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
    // 3) INTERVENTION — avec donnees_or JSON
    //    Règle : une seule ligne par véhicule.
    //    Si une intervention existe déjà pour ce
    //    vehicule_id (ex: réservation), on la met
    //    à jour au lieu d'en créer une nouvelle.
    // ════════════════════════════════════════
    $donnees_json = json_encode($donnees, JSON_UNESCAPED_UNICODE);

    if ($intervention_id > 0) {
        // Modification d'un OR existant connu
        $pdo->prepare("
            UPDATE intervention
            SET vehicule_id           = :vehicule_id,
                date_intervention     = :date,
                Probleme              = :probleme,
                commentaire           = :commentaire,
                donnees_or            = :donnees,
                source                = 'ordre'
            WHERE id_intervention = :id
        ")->execute([
            ':vehicule_id'  => $vehicule_id,
            ':date'         => $date_reception,
            ':probleme'     => $info_client,
            ':commentaire'  => $travaux,
            ':donnees'      => $donnees_json,
            ':id'           => $intervention_id,
        ]);

    } elseif ($vehicule_id > 0) {
        // Chercher si une intervention existe déjà pour ce véhicule
        $chk = $pdo->prepare("
            SELECT id_intervention FROM intervention
            WHERE vehicule_id = :vid
            ORDER BY id_intervention DESC
            LIMIT 1
        ");
        $chk->execute([':vid' => $vehicule_id]);
        $existing_intervention = $chk->fetchColumn();

        if ($existing_intervention) {
            // Mettre à jour la ligne existante (réservation → OR)
            $intervention_id = (int)$existing_intervention;
            $pdo->prepare("
                UPDATE intervention
                SET date_intervention     = :date,
                    Probleme              = :probleme,
                    commentaire           = :commentaire,
                    donnees_or            = :donnees,
                    source                = 'ordre'
                WHERE id_intervention = :id
            ")->execute([
                ':date'        => $date_reception,
                ':probleme'    => $info_client,
                ':commentaire' => $travaux,
                ':donnees'     => $donnees_json,
                ':id'          => $intervention_id,
            ]);
        } else {
            // Aucune intervention pour ce véhicule → nouvelle ligne
            $pdo->prepare("
                INSERT INTO intervention
                    (vehicule_id, prestation_id, date_intervention, `heure_de_préstation`, Probleme, commentaire, donnees_or, source)
                VALUES
                    (:vehicule_id, NULL, :date, '08:00', :probleme, :commentaire, :donnees, 'ordre')
            ")->execute([
                ':vehicule_id' => $vehicule_id,
                ':date'        => $date_reception,
                ':probleme'    => $info_client,
                ':commentaire' => $travaux,
                ':donnees'     => $donnees_json,
            ]);
            $intervention_id = (int)$pdo->lastInsertId();
        }

    } else {
        // Aucun véhicule identifié → INSERT minimal
        $pdo->prepare("
            INSERT INTO intervention
                (vehicule_id, prestation_id, date_intervention, `heure_de_préstation`, Probleme, commentaire, donnees_or, source)
            VALUES
                (NULL, NULL, :date, '08:00', :probleme, :commentaire, :donnees, 'ordre')
        ")->execute([
            ':date'        => $date_reception,
            ':probleme'    => $info_client,
            ':commentaire' => $travaux,
            ':donnees'     => $donnees_json,
        ]);
        $intervention_id = (int)$pdo->lastInsertId();
    }

    header("Location: ordre_reparation.php?intervention_id={$intervention_id}&saved=1");
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Erreur</title></head><body>';
    echo '<div style="font-family:Arial;max-width:600px;margin:60px auto;background:#fdecea;border:1px solid #f5c2c7;border-radius:12px;padding:30px;color:#b00020;">';
    echo '<h2>❌ Erreur lors de l\'enregistrement</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<a href="javascript:history.back()" style="color:#b00020;">← Retour</a>';
    echo '</div></body></html>';
    exit;
}
