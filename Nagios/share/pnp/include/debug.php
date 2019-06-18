<?php
##
## Program: PNP , Performance Data Addon for Nagios(r)
## Version: $Id: debug.php 97 2006-11-02 17:49:29Z linge $
## License: GPL
## Copyright (c) 2006-2008 Joerg Linge (http://www.pnp4nagios.org)
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

class check {
	var $state;
	var $conf;

	function check() {
		global $conf;
		global $view;

		$this->state['txt'] = "<html>\n";
		$this->state['txt'] .= "<head>\n";
		$this->state['txt'] .= "<title>PNP Debugger</title>\n";
		$this->state['txt'] .= "<script src=\"include/js/prototype.js\" type=\"text/javascript\"></script>\n";
		$this->state['txt'] .= "<script src=\"include/js/scriptaculous.js\" type=\"text/javascript\"></script>\n";
		$this->state['txt'] .= "<LINK REL='stylesheet' TYPE='text/css' HREF='include/style.css'>\n";
		$this->state['txt'] .= "</head>";
		$this->state['txt'] .= "<body>\n";
		$this->state['txt'] .= "<div id=\"Inhalt\">\n";
		$this->state['txt'] .= _STYLE_OK_S . _INIT . _STYLE_E . "\n";
		$this->state['txt'] .= _STYLE_OK_S . _USEING . $conf['rrdbase'] . _STYLE_E . "\n";
		$this->state['rc'] = 0;
	}

	function doCheck($type, $value = "") {
		global $conf;

		switch ($type) {
			case "rrdtool":
				$value = $conf['rrdtool'];
				if (!is_readable($value)) {
					$this->state['txt'] .= _STYLE_CRIT_S . _RRDTOOL . $value . _NOTFOUND . _STYLE_E . "\n";
					$this->state['rc'] = 1;
				} else {
					$this->state['txt'] .= _STYLE_OK_S . _RRDTOOL . $value . _FOUND . _STYLE_E . "\n";
				}
				if (!is_executable($value)) {
					$this->state['txt'] .= _STYLE_CRIT_S . _RRDTOOL . $value . _NOTEXECUTABLE . _STYLE_E . "\n";
					$this->state['rc'] = 1;
				} else {
					$this->state['txt'] .= _STYLE_OK_S . _RRDTOOL . $value . _EXECUTABLE . _STYLE_E . "\n";
				}
				$this->printState();
				break;
			case "p_open":
				if(!function_exists('proc_open')){
					$this->state['txt'] .= _STYLE_CRIT_S . _FUNCTION . " proc_open " . _DISABLED . _STYLE_E . "\n";
					$this->state['rc'] = 1;
				}else{
					$this->state['txt'] .= _STYLE_OK_S . _FUNCTION . " proc_open " . _ENABLED . _STYLE_E . "\n";
				}
				$this->printState();
				break;
			case "fpassthru":
				if(!function_exists('fpassthru')){
					$this->state['txt'] .= _STYLE_CRIT_S . _FUNCTION . " fpassthru " . _DISABLED . _STYLE_E . "\n";
					$this->state['rc'] = 1;
				}else{
					$this->state['txt'] .= _STYLE_OK_S . _FUNCTION . " fpassthru " . _ENABLED . _STYLE_E . "\n";
				}
				$this->printState();
				break;
			case "xml_parser_create":
				if(!function_exists('xml_parser_create')){
					$this->state['txt'] .= _STYLE_CRIT_S . _FUNCTION . " xml_parser_create " . _DISABLED . _STYLE_E . "\n";
					$this->state['rc'] = 1;
				}else{
					$this->state['txt'] .= _STYLE_OK_S . _FUNCTION . " xml_parser_create " . _ENABLED . _STYLE_E . "\n";
				}
				$this->printState();
				break;
			case 'zlib':
				if(!function_exists("gzuncompress")){
					if($conf['use_fpdf'] == 1 ){
						$this->state['txt'] .= _STYLE_CRIT_S . _ZLIB_SUPPORT . _NOTFOUND . _STYLE_E . "\n";
						$this->state['rc'] = 1;
					}
				}else{
					$this->state['txt'] .= _STYLE_OK_S . _ZLIB_SUPPORT . _FOUND . _STYLE_E . "\n";
				}
				$this->printState();
				break;
			case 'gd':
				if(!function_exists("imagecreatefrompng")){
					if($conf['use_fpdf'] == 1 ){
						$this->state['txt'] .= _STYLE_CRIT_S . _GDLIB_SUPPORT . _NOTFOUND  . _STYLE_E . "\n";
						$this->state['rc'] = 1;
					}
				}else{
					$this->state['txt'] .= _STYLE_OK_S . _GDLIB_SUPPORT . _FOUND  . _STYLE_E . "\n";
				}
				$this->printState();
				break;
			case "rrdbase":
				$value = $conf['rrdbase'];
				if (!is_readable($value)) {
					$this->state['txt'] .= _STYLE_CRIT_S . _RRDBASE ." " . $value . _NOTFOUND . _STYLE_E . "\n";
					$this->state['rc'] = 1;
				} else {
					$this->state['txt'] .= _STYLE_OK_S . _RRDBASE . " " . $value . _FOUND . _STYLE_E . "\n";
				}
				$this->printState();
				break;
			case "directory" :
				if (!is_readable($value)) {
					$this->state['txt'] .= _STYLE_CRIT_S . _DIRECTORY . " " .  $value . _NOTFOUND . _STYLE_E . "\n";
					$this->state['rc'] = 1;
				} else {
					$this->state['txt'] .= _STYLE_OK_S . _DIRECTORY . " " . $value . _FOUND . _STYLE_E . "\n";
				}
				$this->printState();
				break;
			case "xml_err" :
			        $this->state['txt'] .= _STYLE_CRIT_S . " " .  $value . _STYLE_E . "\n";
			        $this->state['rc'] = 1;
				$this->printState();
				break;
			case "template_dir" :
				if (!is_readable($value)) {
					$this->state['txt'] .= _STYLE_CRIT_S . _TEMPLATE . " " . _DIRECTORY . " ". $value . _NOTFOUND . _STYLE_E . "\n";
					$this->state['rc'] = 1;
				} else {
					$this->state['txt'] .= _STYLE_OK_S . _TEMPLATE . " " . _DIRECTORY . " " . $value . _FOUND . _STYLE_E . "\n";
				}
				$this->printState();
				break;
			case "template" :
				if (!is_readable($value)) {
					$this->state['txt'] .= _STYLE_CRIT_S . _TEMPLATE . " " . $value . _NOTFOUND . _STYLE_E . "\n";
					$this->state['rc'] = 1;
				} else {
					$this->state['txt'] .= _STYLE_OK_S . _TEMPLATE . " " . $value . _FOUND . _STYLE_E . "\n";
				}
				$this->printState();
				break;
			case "incl" :
				if ($value != "") {
					$this->state['txt'] .= _STYLE_OK_S . _INC . _TEMPLATE . _STYLE_E . "\n";
					$this->state['txt'] .= _STYLE_CRIT_S . _TEMPLATE_ERR."\n";
					$this->state['txt'] .= _STYLE_CRIT_S . _DATA_FOUND ." = \"" . nl2br($value) . "\"\n";
					$this->state['rc'] = 1;
				} else {
					$this->state['txt'] .= _STYLE_OK_S . _TEMPLATECLEAN . _STYLE_E . "\n";
				}
				$this->printState();
				break;
			case "rrddef" :
				if (!is_readable($value)) {
					$this->state['txt'] .= _STYLE_CRIT_S . _RRDDEF . $value . _NOTFOUND . _STYLE_E . "\n";
					$this->state['rc'] = 1;
				} else {
					$this->state['txt'] .= _STYLE_OK_S . _RRDDEF . $value . _FOUND . _STYLE_E . "\n";
				}
				$this->printState();
				break;
			case "rrdfile" :
				if (!is_readable($value)) {
					$this->state['txt'] .= _STYLE_CRIT_S . _RRDFILE . $value . _NOTFOUND . _STYLE_E . "\n";
					$this->state['rc'] = 1;
				} else {
					$this->state['txt'] .= _STYLE_OK_S . _RRDFILE . $value . _FOUND . _STYLE_E . "\n";
				}
				$this->printState();
				break;
			case "hostname" :
				if ($value == "") {
					$this->state['txt'] .= _STYLE_CRIT_S . _HOSTNAME . $value . _NOTSET . _STYLE_E . "\n";
					$this->state['txt'] .= _STYLE_CRIT_S . _DIRECTORY . $conf['rrdbase'] . " " . _PERFDATA_DIR_EMPTY . _STYLE_E . "\n";
					$this->state['txt'] .= _STYLE_CRIT_S .  _PERFDATA_DIR_HINT . _STYLE_E . "\n";
					$this->state['rc'] = 1;
				} else {
					$this->state['txt'] .= _STYLE_OK_S . _HOSTNAME . $value . _SET . _STYLE_E . "\n";
				}
				$this->printState();
				break;
			case "servicedesc" :
				if ($value == "") {
					$this->state['txt'] .= _STYLE_CRIT_S . _SERVICEDESC . $value . _NOTSET . _STYLE_E . "\n";
					$this->state['rc'] = 1;
				} elseif ($value == "NULL"){
					$this->state['txt'] .= _STYLE_CRIT_S . "No valid RRD Files found" . _STYLE_E ."\n";
					$this->state['rc'] = 1;
				} else {
					$this->state['txt'] .= _STYLE_OK_S . _SERVICEDESC . $value . _SET . _STYLE_E . "\n";
				}
				$this->printState();
				break;
			case "rrdgraph" :
				if (isset ($value['data'])) {
					$this->state['txt'] .= _STYLE_CRIT_S . _RRDTOOL_ERR . $value['data'] . _STYLE_E . "\n";
					$this->state['txt'] .= _STYLE_CRIT_S . _RRDTOOL_CALL . $conf['rrdtool'] . " " . $value['command'] . _STYLE_E . "\n";
					$this->state['rc'] = 1;
				} else {
					$this->state['txt'] .= _STYLE_OK_S . $value . _SET . _STYLE_E . "\n";
				}
				$this->printState();
				break;
			case "page" :
                                if ($value == "") {
                                        $this->state['txt'] .= _STYLE_CRIT_S . "Page " . $value . _NOTFOUND . _STYLE_E . "\n";
                                        $this->state['rc'] = 1;
                                } else {
                                        $this->state['txt'] .= _STYLE_OK_S . "Page " . $value . _SET . _STYLE_E . "\n";
                                }

				$this->printState();
				break;
			case "no_data" :
                                if ($value == "") {
                                        $this->state['txt'] .= _STYLE_CRIT_S . "Nothing to do. No Data found for current selection". _STYLE_E . "\n";
                                        $this->state['rc'] = 1;
				}
				$this->printState();
				break;
			case "var_dump" :
                                if ($value) {
                                        ob_start();
                                        var_dump($value);
                                        $result = ob_get_contents();
                                        ob_get_clean();
                                        $this->state['txt'] .= _STYLE_CRIT_S . " var_dump() ". _STYLE_E . "\n";
                                        $this->state['txt'] .= "<pre>".$result."</pre>\n";
                                        $this->state['rc'] = 1;
				}
				$this->printState();
				break;
			case "print_r" :
                                if ($value) {
                                        $result = print_r($value,true);
                                        $this->state['txt'] .= _STYLE_CRIT_S . " print_r() ". _STYLE_E . "\n";
                                        $this->state['txt'] .= "<pre>".$result."</pre>\n";
                                        $this->state['rc'] = 1;
				}
				$this->printState();
				break;
		}
	}

	function printState() {
		if ($this->state['rc'] > 0) {
			print $this->state['txt'];
			exit;
		}
	}
}
?>
