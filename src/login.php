<?php
session_start();
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($username && $password) {

        $ldap = ldap_connect(LDAP_HOST, LDAP_PORT);

        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

        // 🔥 Format AD validé
        $userPrincipal = $username . '@' . LDAP_DOMAIN;

        if (@ldap_bind($ldap, $userPrincipal, $password)) {

            // 🔍 Recherche utilisateur
            $filter = "(sAMAccountName=" . ldap_escape($username, '', LDAP_ESCAPE_FILTER) . ")";
            $attributes = ['cn', 'memberOf'];

            $search = ldap_search($ldap, LDAP_BASE_DN, $filter, $attributes);
            $entries = ldap_get_entries($ldap, $search);

            if ($entries['count'] > 0) {

                $user = $entries[0];

                $_SESSION['username'] = $username;
                $_SESSION['name'] = $user['cn'][0] ?? $username;
                $_SESSION['role'] = 'inconnu';

                // 🔥 Gestion des rôles via groupes AD
                if (isset($user['memberof'])) {
                    for ($i = 0; $i < $user['memberof']['count']; $i++) {

                        if ($user['memberof'][$i] === GROUP_ELEVES) {
                            $_SESSION['role'] = 'eleve';
                        }

                        if ($user['memberof'][$i] === GROUP_PROFS) {
                            $_SESSION['role'] = 'prof';
                        }
                    }
                }

                // 🔀 Redirection
                if ($_SESSION['role'] === 'eleve') {
                    header('Location: eleve.php');
                } elseif ($_SESSION['role'] === 'prof') {
                    header('Location: prof.php');
                } else {
                    header('Location: accueil.php');
                }

                exit;

            } else {
                $error = "Utilisateur non trouvé dans l'AD.";
            }

        } else {
            $error = "Identifiant ou mot de passe incorrect.";
        }

        ldap_close($ldap);

    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion Atelier</title>
</head>
<body>

<h1>Connexion</h1>

<?php if ($error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="post">
    <label>Identifiant :</label><br>
    <input type="text" name="username" required><br><br>

    <label>Mot de passe :</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">Se connecter</button>
</form>

</body>
</html>
