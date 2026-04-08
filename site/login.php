<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = "";

// Si l'utilisateur est déjà connecté, redirige vers le dashboard
if (isset($_SESSION["user"])) {
    header("Location: dashboard.php");
    exit;
}

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST["username"];
    $password = $_POST["password"];

    $ldapServer = "ldap://192.168.11.75"; // IP ou nom du DC
    $ldapBaseDn = "DC=ciel,DC=com";

    $conn = ldap_connect($ldapServer);
    ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
    ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, 5);

    $userDn = $username . "@ciel.com"; // format UPN

    if (@ldap_bind($conn, $userDn, $password)) {

        // Récupérer infos utilisateur
        $filter = "(sAMAccountName=$username)";
        $search = ldap_search($conn, $ldapBaseDn, $filter);

        if ($search !== false) {
            $entries = ldap_get_entries($conn, $search);

            if ($entries["count"] > 0) {
                $_SESSION["user"] = $username;
                $_SESSION["dn"] = $entries[0]["dn"];

                // Récupérer groupes AD
                $groups = [];
                if (isset($entries[0]["memberof"])) {
                    for ($i = 0; $i < $entries[0]["memberof"]["count"]; $i++) {
                        $groups[] = $entries[0]["memberof"][$i];
                    }
                }
                $_SESSION["groups"] = $groups;

                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Utilisateur trouvé mais informations introuvables";
            }
        } else {
            $error = "Erreur LDAP search : " . ldap_error($conn);
        }

    } else {
        $error = "Identifiants invalides ou impossible de joindre LDAP : " . ldap_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Connexion AD</title>
</head>
<body>
    <h2>Connexion Active Directory</h2>
    <form method="post">
        <input type="text" name="username" placeholder="Utilisateur AD" required><br>
        <input type="password" name="password" placeholder="Mot de passe" required><br>
        <button type="submit">Connexion</button>
    </form>
    <?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
</body>
</html>