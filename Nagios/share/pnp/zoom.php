<?php
##
## Program: PNP , Performance Data Addon for Nagios(r)
## Version: $Id: zoom.php.in 591 2009-02-19 17:25:15Z pitchfork $
## License: GPL
## Copyright (c) 2006-2009 Joerg Linge (http://www.pnp4nagios.org)
##
## This program is free software; you can redistribute it and/or
## modify it under the terms of the GNU General Public License
## as published by the Free Software Foundation; either version 2
## of the License, or (at your option) any later version.
##
## This program is distributed in the hope that it will be useful,
## but WITHOUT ANY WARRANTY; without even the implied warranty of
## MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
## GNU General Public License for more details.
##
## You should have received a copy of the GNU General Public License
## along with this program; if not, write to the Free Software
## Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
##
error_reporting(E_ALL ^E_NOTICE);
include ('include/function.inc.php');

if(getenv('PNP_CONFIG_FILE') != ""){
    $config = getenv('PNP_CONFIG_FILE');
}else{
    $config = "/usr/local/nagios/etc/pnp/config";
}

if (is_readable($config . ".php")) {
        include ($config . ".php");
} else {
        die("<b>$config.php</b> not found ");
}

if (is_readable($config . "_local.php")) {
        include ($config . "_local.php");
}


if (is_readable('lang/lang_' . $conf['lang'] . '.php')) {
        include ('lang/lang_' . $conf['lang'] . '.php');
} else {
        include ('lang/lang_en.php');
}


# Variablen aus der URL auslesen
$display = doClean($_GET['display']);
$hostname = doClean($_GET['host']);
$start = doClean($_GET['start']);
$end = doClean($_GET['end']);
$servicedesc = doClean($_GET['srv']);
$special = doClean($_GET['special']);

$timerange=getTimerange($start,$end,$view);

if(!$start){
	$start=$timerange['start'];
}

if(!$end){
	$end=$timerange['end'];
}

if ($_GET['source'] == "") {
        $source = "1";
} else {
        $source = doClean($_GET['source']);
}

$graph_width="500";
$graph_height="100";

if(is_numeric($conf['graph_width'])){
        $graph_width = abs($conf['graph_width']);
}
if(is_numeric($conf['graph_height'])){
        $graph_height = abs($conf['graph_height']);
}

print "<html><head>\n";
print "</head><body leftmargin=\"0\" topmargin=\"0\" marginwidth=\"0\" marginheight=\"0\">\n";
print "<table>\n";
print "<div id='zoomBox' style='position:absolute; overflow:none; left:0px; top:0px; width:0px; height:0px; visibility:visible; background:red; filter:alpha(opacity=50); -moz-opacity:0.5; -khtml-opacity:0.5; opacity:0.5'></div>\n";
print "<div id='zoomSensitiveZone' style='position:absolute; overflow:none; left:0px; top:0px; width:0px; height:0px; visibility:visible; cursor:crosshair; background:blue; filter:alpha(opacity=0); -moz-opacity:0; -khtml-opacity:0;opacity:0' oncontextmenu='return false'></div>\n";
print "<STYLE MEDIA=\"print\">\n";
print "  div#zoomBox, div#zoomSensitiveZone {display: none}\n";
print "  #why {position: static; width: auto}\n";
print "</STYLE>\n";
#
# Switch for special templates
#
if( $special == "" || $special == "undefined" ){
    print "<tr><td><img id=\"zoomGraphImage\" src=\"index.php?host=$hostname&srv=$servicedesc&source=$source&start=$start&end=$end&display=image&graph_height=$graph_height&graph_width=$graph_width&title_font_size=10\">";
}else{
    print "<tr><td><img id=\"zoomGraphImage\" src=\"index.php?special=$special&source=$source&start=$start&end=$end&display=image&graph_height=$graph_height&graph_width=$graph_width&title_font_size=10\">";
}
print "</tr></td><tr><td>".$timerange['f_start']." - ".$timerange['f_end']."</tr></td></table>\n";
include("include/js/zoom.js");
print "</body></html>\n";
?>
