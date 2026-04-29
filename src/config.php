<?php

// Serveur Active Directory
define('LDAP_HOST', 'ldap://192.168.11.75');
define('LDAP_PORT', 389);

// Domaine
define('LDAP_DOMAIN', 'ciel.com');

// Base DN (vu dans ton PowerShell)
define('LDAP_BASE_DN', 'DC=ciel,DC=com');

// ⚠️ À adapter selon tes groupes réels
define('GROUP_ELEVES', 'CN=Eleves,OU=Groupes,DC=ciel,DC=com');
define('GROUP_PROFS', 'CN=Profs,OU=Groupes,DC=ciel,DC=com');
