<?php
 
// INDEX.PHP VULNÉRABLE POUR TEST IDS

// SQL Injection
$id = $_GET['id'] ?? '';
echo "<h2>SQL test : ID = $id</h2>";

// XSS
$name = $_GET['name'] ?? '';
echo "<h2>XSS test : Name = $name</h2>";

// Local File Inclusion (LFI)
$page = $_GET['page'] ?? 'home';
if (file_exists("./pages/$page.php")) {
    include("./pages/$page.php");
} else {
    echo "<p>Page non trouvée</p>";
}
?>

