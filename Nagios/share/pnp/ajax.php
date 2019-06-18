<?php
#
## Program: PNP , Performance Data Addon for Nagios(r)
## Version: $Id: ajax.php.in 617 2009-04-02 18:03:35Z pitchfork $ 
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
require ('include/function.inc.php');
require ('include/tpl_function.inc.php');
require ('include/debug.php');

$do = "search";
$hostname = "";
$servicedesc = "";

if(getenv('PNP_CONFIG_FILE') != ""){
    $config = getenv('PNP_CONFIG_FILE');
}else{
    $config = "/usr/local/nagios/etc/pnp/config";
}

if (is_readable($config . ".php")) {
        include ($config . ".php");
} else {
        die("<b>$config.php</b> not found");
}

if (is_readable($config . "_local.php")) {
        include ($config . "_local.php");
}

if(!isset($conf['template_dir'])){
        $conf['template_dir'] = dirname(__file__);
}


if (is_readable('./lang/lang_' . $conf['lang'] . '.php')) {
        include ('./lang/lang_' . $conf['lang'] . '.php');
} else {
        include ('./lang/lang_en.php');
}

if(isset($_GET['do'])){
    $do = doClean($_GET['do']);
}
# Get the Hostname
if(isset($_GET['host'])){
    $hostname = doClean($_GET['host']);
}
# Get the service description
if(isset($_GET['srv'])){
    $servicedesc = doClean($_GET['srv']);
}else{
    $servicedesc = "_HOST_";
}
#
if(isset($_POST['host'])){
    $search = doClean($_POST['host']);
}

if($do == "popup"){
    $timet = time();
    $debug = new check;
    $debug->doCheck("hostname",$hostname);
    $debug->doCheck("servicedesc",$servicedesc);
    $rrdfile = $conf['rrdbase'] . "$hostname/$servicedesc.rrd";
    $debug->doCheck("rrdfile",$rrdfile);
    $rrddef = $conf['rrdbase'] . $hostname . "/" . $servicedesc . ".xml";
    $debug->doCheck("rrddef",$rrddef);
    print "<img src=\"/nagios/pnp/index.php?host=$hostname&srv=$servicedesc&display=image&$timet\">";

}elseif($do == "search"){
    if(strlen($search)>=1) {
        $hosts = getHosts($host);
        print "<ul>\n";
        foreach($hosts as $host){
                list($host,$state) = explode(";",$host);
                if(preg_match("/$search/i",$host) && $state == 0 ){
                        print "<li>$host</li>\n";
                }
        }
        print "</ul>\n";
    }
}
?>
