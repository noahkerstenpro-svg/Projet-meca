<?php
// INDEX.PHP PROPRE POUR PROJET MECA
// Version 4.0 - Interface user-friendly + IDS/IPS tests

// =====================
// 1️⃣ Paramètres GET
// =====================
$id = $_GET['id'] ?? '';
$name = $_GET['name'] ?? '';
$page = $_GET['page'] ?? 'home';
$download = $_GET['download'] ?? '';

// Logs IDS/IPS
$log_dir = __DIR__ . '/logs';
if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);

if ($id) file_put_contents("$log_dir/sql.log", date('Y-m-d H:i:s') . " - SQL ID=$id\n", FILE_APPEND);
if ($name) file_put_contents("$log_dir/xss.log", date('Y-m-d H:i:s') . " - XSS Name=$name\n", FILE_APPEND);
if ($download) file_put_contents("$log_dir/download.log", date('Y-m-d H:i:s') . " - Download=$download\n", FILE_APPEND);

// =====================
// 2️⃣ Pages sûres pour LFI
// =====================
$safe_pages = ['home', 'about', 'contact', 'downloads'];
$page_file = in_array($page, $safe_pages) ? "./pages/$page.php" : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Projet Meca</title>
<style>
body { font-family: Arial, sans-serif; background: #f0f2f5; color: #333; margin: 0; padding: 0; }
header { background: #1e90ff; color: white; padding: 20px; text-align: center; }
main { padding: 20px; max-width: 900px; margin: auto; }
h2, h3 { color: #1e90ff; }
form { background: white; padding: 15px; border-radius: 5px; box-shadow: 0 0 5px rgba(0,0,0,0.1); }
input[type=text] { width: 100%; padding: 8px; margin: 5px 0 10px; border-radius: 3px; border: 1px solid #ccc; }
input[type=submit] { background: #1e90ff; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; }
input[type=submit]:hover { background: #0b6fc2; }
ul { list-style-type: none; padding-left: 0; }
li { margin: 5px 0; }
a { color: #1e90ff; text-decoration: none; }
a:hover { text-decoration: underline; }
footer { text-align: center; padding: 10px; color: #555; margin-top: 30px; }
</style>
</head>
<body>
<header>
    <h1>Bienvenue dans Projet Meca</h1>
    <p>Laboratoire IDS/IPS – Test SQL, XSS, LFI et Téléchargement</p>
</header>
<main>
    <!-- Affichage résultats -->
    <?php
    if ($id) echo "<h2>SQL test : ID = " . htmlspecialchars($id) . "</h2>";
    if ($name) echo "<h2>XSS test : Name = " . htmlspecialchars($name) . "</h2>";

    if ($page_file && file_exists($page_file)) {
        include($page_file);
    } else {
        echo "<p>Page non trouvée ou interdite</p>";
    }

    if ($download) {
        $filepath = realpath(__DIR__ . "/downloads/$download");
        if ($filepath && strpos($filepath, realpath(__DIR__ . "/downloads/")) === 0) {
            echo "<p>Téléchargement simulé pour : $download</p>";
        } else {
            echo "<p>Accès interdit au fichier : $download</p>";
        }
    }
    ?>

    <!-- Formulaire de test -->
    <h3>Formulaire de test IDS/IPS</h3>
    <form method="get">
        ID (SQL test): <input type="text" name="id">
        Name (XSS test): <input type="text" name="name">
        Page (LFI test): <input type="text" name="page">
        Fichier téléchargement: <input type="text" name="download">
        <input type="submit" value="Envoyer">
    </form>

    <!-- Liens rapides -->
    <h3>Liens rapides de test</h3>
    <ul>
        <li><a href="?id=5&name=Paul">Test SQL/XSS</a></li>
        <li><a href="?page=about">Test LFI page about</a></li>
        <li><a href="?download=test.txt">Test téléchargement</a></li>
    </ul>
</main>
<footer>
    &copy; 2026 Projet Meca – Laboratoire IDS/IPS
</footer>
</body>
</html>
