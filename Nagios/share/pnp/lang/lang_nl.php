<?php
# 
# $Id: lang_nl.php 413 2008-03-21 12:42:45Z pitchfork $
#
# Thanks to Ton for this language File
#
define("_RRDFILE","RRD Database");
define("_ERRHOSTNAMENOTSET","Variabele <b>host</b> niet gedefinieerd.<br><b>Voorbeeld:</b> index.php?srv=ping&host=server1<br>");
define("_ERRSERVICEDESCNOTSET","Variabele <b>srv</b> niet gedefinieerd<br><b>Voorbeeld:</b> index.php?srv=ping?host=server1<br>");
define("_ERRORSFOUND","<H3>Fouten gevonden...</H3><br>");
define("_NOTREADABLE","niet leesbaar ");
define("_NOTEXIST","bestaat niet");
# Service List
define("_HOST","Systeem: ");
define("_HOSTSTATE","Host status: ");
define("_SERVICE","Service: ");
define("_SERVICESTATE","Service status: ");
define("_TIMET","Gemaakt: ");
define("_SEARCH","Zoeken: ");
define("_NOTES","Notities: ");
define("_DOKU","Documentatie");
define("_DATASOURCE", "Datasource: ");
define("_HOSTPERFDATA", "Host Perfdata");

# PDF
define("_PDFPAGE","Pagina ");
define("_PDFTITLE","Performance grafiek");
define("_PDFHOST",   "Hostnaam : ");
define("_PDFSERVICE","Service : ");
define("_PDFTIMET",  "Laatste controle : ");

#MouseOver
define("_OVERNOTPL","Geen template gevonden voor deze service<br><strong>Link gedeactiveerd</strong>");
define("_OVERINACT","Data voor deze service is te oud");
#Debugger
define("_STYLE_CRIT_S", "<p id=\"kritiek\">");
define("_STYLE_WARN_S", "<p id=\"waarschuwing\">");
define("_STYLE_OK_S", "<p id=\"ok\">");
define("_STYLE_E", "</p>");
define('_CRIT', 'Kritiek: ');
define('_WARN', 'Waarschuwing: ');
define('_OK', 'Ok: ');
define('_NOTFOUND', ' niet gevonden.');
define('_FOUND', ' gevonden.');
define("_NOTEXECUTABLE"," not executable ");
define("_EXECUTABLE"," is executable ");
define('_SET', ' is gezet.');
define('_NOTSET', ' is niet gezet.');
define('_RRDBASE', ' RRD basis directory ');
define('_RRDDEF', ' RRD definitie bestand ');
define('_TEMPLATE', ' Template ');
define('_HOSTNAME', ' Hostnaam ');
define('_SERVICEDESC', ' Service omschrijving ');
define('_RRDTOOL', ' RRDTool ');
define('_DIRECTORY', ' Directory ');
define('_FUNCTION', ' PHP functie ');
define('_DISABLED', ' is niet beschikbaar ');
define('_ENABLED', ' is beschikbaar ');
define('_USEING', ' Gebruikt ');
define('_INIT', ' Initialiseren ');
define('_INC', ' Invoegen ');
define('_TEMPLATE_ERR', ' Template bevat fouten. Mogelijke syntax fout of spaties buiten de PHP Tags ');
define('_DATA_FOUND', ' Output gevonden ');
define('_RRDTOOL_ERR', ' RRDTool sloot af met fouten ->  ');
define('_RRDTOOL_CALL', ' RRDTool is aangeroepen met -><br>  ');
# 0.4
define('_NO_RRD_FILES', ' No RRD Files found ');
define('_ZLIB_SUPPORT', ' PHP zlib Support ');
define('_GDLIB_SUPPORT', ' PHP GD Support ');
?>
