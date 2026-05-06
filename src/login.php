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

        $userPrincipal = $username . '@' . LDAP_DOMAIN;

        if (@ldap_bind($ldap, $userPrincipal, $password)) {
            $filter = "(sAMAccountName=" . ldap_escape($username, '', LDAP_ESCAPE_FILTER) . ")";
            $attributes = ['cn', 'memberOf'];

            $search = ldap_search($ldap, LDAP_BASE_DN, $filter, $attributes);
            $entries = ldap_get_entries($ldap, $search);

            if ($entries['count'] > 0) {
                $user = $entries[0];

                $_SESSION['username'] = $username;
                $_SESSION['name'] = $user['cn'][0] ?? $username;
                $_SESSION['role'] = 'inconnu';

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

                if ($_SESSION['role'] === 'prof') {
                    header('Location: prof.php');
                    exit;
                }

                if ($_SESSION['role'] === 'eleve') {
                    header('Location: eleve.php');
                    exit;
                }

                $error = "Compte connecté, mais aucun rôle autorisé.";
            } else {
                $error = "Utilisateur introuvable dans l'annuaire.";
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
    <title>Connexion - Atelier mécanique</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #1e3a8a, #111827);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: white;
            border-radius: 18px;
            padding: 35px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.35);
        }

        .logo {
            width: 70px;
            height: 70px;
            margin: 0 auto 18px;
            border-radius: 50%;
            background: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: bold;
        }

        h1 {
            text-align: center;
            margin: 0;
            color: #111827;
        }

        .subtitle {
            text-align: center;
            color: #6b7280;
            margin: 10px 0 30px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 14px;
            margin-bottom: 18px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 15px;
        }

        input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.2);
        }

        button {
            width: 100%;
            padding: 14px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }

        .footer {
            margin-top: 22px;
            text-align: center;
            color: #9ca3af;
            font-size: 13px;
        }
    </style>
</head>

<body>

<div class="login-card">
    <div class="logo">🔧</div>

    <h1>Connexion</h1>
    <p class="subtitle">Atelier mécanique - Lycée Brocéliande</p>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <label for="username">Identifiant AD</label>
        <input
            type="text"
            id="username"
            name="username"
            placeholder="ex : prénom"
            required
        >

        <label for="password">Mot de passe</label>
        <input
            type="password"
            id="password"
            name="password"
            placeholder="Mot de passe Active Directory"
            required
        >

        <button type="submit">Se connecter</button>
    </form>

    <div class="footer">
        Accès réservé aux élèves et professeurs
    </div>
</div>

</body>
</html>
