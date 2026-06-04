<?php

// Serveur Active Directory
define('LDAP_HOST', 'ldap://192.168.11.75');
define('LDAP_PORT', 389);

//Nom de domaine
define('LDAP_DOMAIN', 'ciel.com');

define('LDAP_BASE_DN', 'DC=ciel,DC=com');
//Groupes
define('GROUP_ELEVES', 'CN=Eleves,CN=Users,DC=ciel,DC=com');
define('GROUP_PROFS', 'CN=Profs,CN=Users,DC=ciel,DC=com');
define('GROUP_ADMINSITE', 'CN=AdminSite,CN=Users,DC=ciel,DC=com');
