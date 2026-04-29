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

        // 🔥 IMPORTANT
        $userPrincipal = $username . '@ciel.com';

        if (@ldap_bind($ldap, $userPrincipal, $password)) {

            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'user'; // on ajoutera après élève/prof

            header('Location: accueil.php');
            exit;

        } else {
            $error = "Identifiant ou mot de passe incorrect.";
        }

        ldap_close($ldap);
    } else {
        $error = "Champs manquants.";
    }
}
?>
