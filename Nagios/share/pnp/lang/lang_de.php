<?php

# Deutsche Sprachdatei
# $Id: lang_de.php 623 2009-04-16 13:40:58Z Le_Loup $
#
#
define("_RRDFILE", "RRD Datenbank");
define("_ERRHOSTNAMENOTSET", "Die Variable <b>host</b> ist in der URL nicht angegeben<br><b>Beispiel:</b> index.php?srv=ping&host=server1<br>");
define("_ERRSERVICEDESCNOTSET", "Die Variable <b>srv</b> ist in der URL nicht angegeben<br><b>Beispiel:</b> index.php?srv=ping?host=server1<br>");
define("_ERRORSFOUND", "<H3>Es wurden Fehler gefunden ...</H3><br>");
define("_NOTREADABLE", "ist nicht lesbar");
define("_NOTEXIST", "existiert nicht");
define("_PERFDATA_DIR_EMPTY"," ist leer.");
define("_PERFDATA_DIR_HINT","Keine RRD-Datenbanken bis jetzt verfuegbar. Siehe <a href='http://www.pnp4nagios.org/pnp/de/verify'>http://www.pnp4nagios.org/de/pnp/verify</a>");

# Service List
define("_HOST", "Host: ");
define("_HOSTSTATE", "Hoststate: ");
define("_SERVICE", "Service: ");
define("_SERVICESTATE", "Servicestate: ");
define("_TIMET", "Erstellt: ");
define("_SEARCH", "Suche: ");
define("_NOTES", "Notes: ");
define("_DOKU", "Dokumentation");
define("_DATASOURCE", "Datasource: ");
define("_HOSTPERFDATA", "Host Perfdata");

# PDF
define("_PDFPAGE", "Seite ");
define("_PDFTITLE", "Performance Graphen");
define("_PDFHOST", "Host: ");
define("_PDFSERVICE", "Service: ");
define("_PDFTIMET", "Last Check: ");
#MouseOver
define("_OVERNOTPL", "Fuer diesen Service wurde kein Template gefunden<br><strong>Link wird deaktiviert</strong>");
define("_OVERINACT", "Dieser Service wurde nicht regelmaeﬂig aktualisiert.");
#Debugger
define("_STYLE_CRIT_S", "<p id=\"critical\">");
define("_STYLE_WARN_S", "<p id=\"warning\">");
define("_STYLE_OK_S", "<p id=\"ok\">");
define("_STYLE_E", "</p>");
define('_CRIT', 'Critical: ');
define('_WARN', 'Warning: ');
define('_OK', 'Ok: ');
define('_NOTFOUND', ' nicht gefunden.');
define('_FOUND', ' gefunden.');
define("_NOTEXECUTABLE"," ist nicht ausfuehrbar ");
define("_EXECUTABLE"," ist ausfuehrbar ");
define('_SET', ' ist gesetzt.');
define('_NOTSET', ' ist nicht gesetzt.');
define('_RRDBASE', ' RRD Daten Verzeichnis ');
define('_RRDDEF', ' RRD Definition Datei ');
define('_TEMPLATE', ' Template ');
define('_HOSTNAME', ' Host Name ');
define('_SERVICEDESC', ' Service Description ');
define('_RRDTOOL', ' RRDTool ');
define('_FUNCTION', ' PHP Function ');
define('_DISABLED', ' ist nicht verfuegbar ');
define('_ENABLED', ' ist verfuegbar ');
define('_DIRECTORY', ' Verzeichnis ');
define('_USEING', ' benutze ');
define('_INIT', ' Initalisierung ');
define('_INC', ' Include ');
define('_TEMPLATE_ERR', ' Template enthaelt Fehler. Moeglicherweise Leerzeichen ausserhalb der PHP Tags ');
define('_DATA_FOUND', ' Ausgabe gefunden ');
define('_RRDTOOL_ERR', ' RRDTool beendet mit Fehlern ->  ');
define('_RRDTOOL_CALL', ' RRDTool wurde wie folgt aufgerufen -><br>  ');
# 0.4
define('_NO_RRD_FILES', ' Es wurden keine RRD-Dateien gefunden ');
define('_ZLIB_SUPPORT', ' PHP zlib Erweiterung ');
define('_GDLIB_SUPPORT', ' PHP GD Lib Erweiterung ');
?>
