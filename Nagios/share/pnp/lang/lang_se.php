<?php
#
# $Id: lang_se.php 413 2008-03-21 12:42:45Z pitchfork $
#
#
define("_RRDFILE","RRD Databas");
define("_ERRHOSTNAMENOTSET","Variabel <b>host</b> är inte definerat.<br><b>Exempel:</b> index.php?srv=ping&host=server1<br>");
define("_ERRSERVICEDESCNOTSET","Variabel <b>srv</b> är inte definerat<br><b>Exempel:</b> index.php?srv=ping?host=server1<br>");
define("_ERRORSFOUND","<H3>Hittat fel...</H3><br>");
define("_NOTREADABLE","kan inte läsa ");
define("_NOTEXIST","existera inte ");
# Service List
define("_HOST","Värd: ");
define("_HOSTSTATE","Värdstatus: ");
define("_SERVICE","Tjänst: ");
define("_SERVICESTATE","Tjänststatus: ");
define("_TIMET","Skapad: ");
define("_SEARCH","Sök: ");
define("_NOTES","Anteckning: ");
define("_DOKU","Dokumentation");
define("_DATASOURCE", "Datasource: ");
define("_HOSTPERFDATA", "Host Perfdata");

# PDF
define("_PDFPAGE","Sida ");
define("_PDFTITLE","Performance Graph");
define("_PDFHOST","Värd: ");
define("_PDFSERVICE","Tjänst: ");
define("_PDFTIMET","Sista Check: ");
#MouseOver
define("_OVERNOTPL","Hittade ingen Mall för denna tjänst<br><strong>Link deaktiverat</strong>");
define("_OVERINACT","Data för denna tjänst är för gammal");
#Debugger
define("_STYLE_CRIT_S", "<p id=\"kritiskt\">");
define("_STYLE_WARN_S", "<p id=\"varning\">");
define("_STYLE_OK_S", "<p id=\"ok\">");
define("_STYLE_E", "</p>");
define('_CRIT', 'kritiskt: ');
define('_WARN', 'varning: ');
define('_OK', 'Ok: ');
define('_NOTFOUND', ' finns inte .');
define('_FOUND', ' finns.');
define("_NOTEXECUTABLE"," not executable ");
define("_EXECUTABLE"," is executable ");
define('_SET', ' är satt.');
define('_NOTSET', ' är inte satt.');
define('_RRDBASE', ' RRD Bas Katalog ');
define('_RRDDEF', ' RRD Definitions Fil ');
define('_TEMPLATE', ' Mall ');
define('_HOSTNAME', ' värdnamn ');
define('_SERVICEDESC', ' Service beskrivning ');
define('_RRDTOOL', ' RRDTool ');
define('_DIRECTORY', ' katalog ');
define('_USEING', ' Använder ');
define('_INIT', ' Initialisering ');
define('_INC', ' Inkludera ');
define('_TEMPLATE_ERR', ' Mallen innehåller fel. Synatx x Fel eller mellanslag untanför PHP Tags ');
define('_DATA_FOUND', ' Output hittat ');
define('_RRDTOOL_ERR', ' RRDTool avslutade med Fel ->  ');
define('_RRDTOOL_CALL', ' RRDTool anrop som -><br>  ');
# 0.4
define('_NO_RRD_FILES', ' No RRD Files found ');
define('_ZLIB_SUPPORT', ' PHP zlib Support ');
define('_GDLIB_SUPPORT', ' PHP GD Support ');
?>
