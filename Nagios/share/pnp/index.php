<?php
##
## Program: PNP , Performance Data Addon for Nagios(r)
## Version: $Id: index.php.in 591 2009-02-19 17:25:15Z pitchfork $ 
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

# Debugger init
$debug = new check;

$debug->doCheck("rrdtool");
$debug->doCheck("p_open");
$debug->doCheck("fpassthru");
$debug->doCheck("xml_parser_create");
$debug->doCheck("zlib");
$debug->doCheck("gd");
$debug->doCheck("rrdbase");

# Variablen aus der URL auslesen 
if(isset($_GET['display'])){
	$display = doClean($_GET['display']);
}else{
	$display = "service";
}
#
# Special Templates
#
if(isset($_GET['special'])){
        $special = doCleanPage($_GET['special']);
	if($display == "image"){
            $display = "image_special";
        }else{
            $display = "special";
        }
}

if(isset($_GET['page'])){
        $page = doCleanPage($_GET['page']);
	$display = "page";
}


# Get the Hostname
if(isset($_GET['host'])){
	$hostname = doClean($_GET['host']);
}else{
	$hostname = getFirstHost();
}
# Get the service description
if($display != 'special' && $display != 'image_special' && $display != 'page' ){
    if( isset($_GET['srv']) ){
        $servicedesc = doClean($_GET['srv']);
    }else{
	if (isAuthorizedFor('host_overview') == 1){
	    $servicedesc = getFirstService($conf['rrdbase'], $hostname);
	    $display = "host_list";
	}else{
	    $servicedesc = getFirstService($conf['rrdbase'], $hostname);
	    $display = "service";
	}	
    }
}


# Get the source for mutigraphs
if(isset($_GET['source'])){
	$source = doClean($_GET['source']);
}else{
	$source = "1";
}
# Get the timerange or default to $conf['overview-range']
if(isset($_GET['view'])){
	$view = doClean($_GET['view']);
	if($view >= sizeof($views)){
		$view = $conf['overview-range'];
	}
}else{
	$view = $conf['overview-range'];
}

if(isset($_GET['start'])){
	$start = doClean($_GET['start']);
}else{
	$start = "";
}

if(isset($_GET['end'])){
	$end = doClean($_GET['end']);
}else{
	$end = "";
}


$timerange = getTimeRange($start,$end,$view);

if(isset($_GET['do'])){
        $do = doClean($_GET['do']);
}else{
	$do = 'html';
}



if($display == "special"){
	$data = doDataArray($display);
        $debug_data = doImage("QUIET",$data);
	doPage($display,$do,$data);
	exit;
}

if($display == "page"){
	$data = doDataArray($display);
        $debug_data = doImage("QUIET",$data);
	doPage($display,$do,$data);
	exit;
}

if($display == "service"){
	$debug->doCheck("hostname",$hostname);
	$debug->doCheck('directory',$conf['rrdbase'].$hostname);
	$debug->doCheck("servicedesc",$servicedesc);
	$rrdfile = $conf['rrdbase'] . "$hostname/$servicedesc.rrd";
	$debug->doCheck("rrdfile",$rrdfile);
	$data = doDataArray($display);
        $debug_data = doImage("QUIET",$data);
	doPage($display,$do,$data);
	exit;
}


if($display == "host_list"){
	$debug->doCheck("hostname",$hostname);
	$debug->doCheck('directory',$conf['rrdbase'].$hostname);
	$debug->doCheck("servicedesc",$servicedesc);
	$data = doDataArray($display);
        doPage($display,$do,$data); 
	exit;
}

if($display == "image"){
	$rrdfile = $conf['rrdbase'] . "$hostname/$servicedesc.rrd";
	$debug->doCheck("rrdfile",$rrdfile);
	$rrddef = $conf['rrdbase'] . $hostname . "/" . $servicedesc . ".xml";
	$debug->doCheck("rrddef",$rrddef);
	$data = doDataArray("image");
	doImage('STDOUT',$data);
	exit;
}

if($display == "image_special"){
        $data = doDataArray("image_special");
	doImage('STDOUT',$data);
	exit;
}

if($display == "export_data"){
	$rrdfile = $conf['rrdbase'] . "$hostname/$servicedesc.rrd";
	$debug->doCheck("rrdfile",$rrdfile);
	$rrddef = $conf['rrdbase'] . $hostname . "/" . $servicedesc . ".xml";
	$debug->doCheck("rrddef",$rrddef);
	#$data = doDataArray("image");
	doExportData($rrdfile);
	exit;
}

if($display == "xml"){
        $rrdfile = $conf['rrdbase'] . "$hostname/$servicedesc.rrd";
        $debug->doCheck("rrdfile",$rrdfile);
        $rrddef = $conf['rrdbase'] . $hostname . "/" . $servicedesc . ".xml";
        doXML($rrddef);
        exit;
}

?>
