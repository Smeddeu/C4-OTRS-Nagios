<?php
# 
# $Id: lang_fr.php 623 2009-04-16 13:40:58Z Le_Loup $
# Contributed by Jean-Marie Le Borgne 
#
define("_RRDFILE","Base RRD");
define("_ERRHOSTNAMENOTSET","Variable <b>host</b> non définie.<br><b>Exemple :</b> index.php?srv=ping&host=server1<br>");
define("_ERRSERVICEDESCNOTSET","Variable <b>srv</b> non définie<br><b>Exemple :</b> index.php?srv=ping?host=server1<br>");
define("_ERRORSFOUND","<H3>Erreurs trouvées...</H3><br>");
define("_NOTREADABLE","non lisible ");
define("_NOTEXIST","n'existe pas");
define("_PERFDATA_DIR_EMPTY"," est vide.");
define("_PERFDATA_DIR_HINT","Ne pas des bases RRD jusqu'à maintenant. Voir <a href='http://www.pnp4nagios.org/pnp/verify'>http://www.pnp4nagios.org/pnp/verify</a>");
# Service List
define("_HOST","Hôte : ");
define("_HOSTSTATE","Etat de l'hôte : ");
define("_SERVICE","Service : ");
define("_SERVICESTATE","Etat du service : ");
define("_TIMET","Créé le : ");
define("_SEARCH","Rechercher : ");
define("_NOTES","Notes : ");
define("_DOKU","Documentation");
define("_DATASOURCE", "Source de données : ");
define("_HOSTPERFDATA", "Données de performances de l'hôte");

# PDF
define("_PDFPAGE","Page ");
define("_PDFTITLE","Graphique de Performance");
define("_PDFHOST","Hôte : ");
define("_PDFSERVICE","Service : ");
define("_PDFTIMET","Dernière vérification : ");
#MouseOver
define("_OVERNOTPL","Aucun modèle trouvé pour ce service<br><strong>Lien désactivé</strong>");
define("_OVERINACT","Les données de ce service sont trop anciennes");
#Debugger
define("_STYLE_CRIT_S", "<p id=\"critical\">");
define("_STYLE_WARN_S", "<p id=\"warning\">");
define("_STYLE_OK_S", "<p id=\"ok\">");
define("_STYLE_E", "</p>");
define('_CRIT', 'Critique : ');
define('_WARN', 'Alerte : ');
define('_OK', 'OK : ');
define('_NOTFOUND', ' non trouvé(e).');
define('_FOUND', ' trouvé(e).');
define("_NOTEXECUTABLE"," non exécutable ");
define("_EXECUTABLE"," est exécutable ");
define('_SET', ' est fixé(e).');
define('_NOTSET', ' n\'est pas fixé(e).');
define('_RRDBASE', ' Répertoire de la base RRD ');
define('_RRDDEF', ' Fichier de définition RRD ');
define('_TEMPLATE', ' Modèle ');
define('_HOSTNAME', ' Nom d\'hôte ');
define('_SERVICEDESC', ' Description du Service ');
define('_RRDTOOL', ' RRDTool ');
define('_FUNCTION', ' Fonction PHP ');
define('_DISABLED', ' est désactivé(e) ');
define('_ENABLED', ' est activé(e) ');
define('_DIRECTORY', ' Répertoire ');
define('_USEING', ' Utilise ');
define('_INIT', ' Initialisation ');
define('_INC', ' Inclus ');
define('_TEMPLATE_ERR', ' Le modèle comporte des erreurs. Sans doute des erreurs de syntaxe ou des espaces en dehors des tags PHP ');
define('_DATA_FOUND', ' Résultats trouvés ');
define('_RRDTOOL_ERR', ' RRDTool a quitté avec des erreurs ->  ');
define('_RRDTOOL_CALL', ' RRDTool a été appelé en tant que -><br>  ');
#0.4
define('_NO_RRD_FILES', ' Pas de fichier RRD trouvé ');
define('_ZLIB_SUPPORT', ' Bibliothèque zlib pour PHP ');
define('_GDLIB_SUPPORT', ' Bibliothèque GD pour PHP ');
?>
