<?php
##
## Program: PNP4Nagios , Performance Data Addon for Nagios(r)
## Version: $Id: tpl_function.inc.php 566 2008-11-24 13:55:29Z pitchfork $
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

function TPL_getHosts ( $SEARCH_REGEX = "-" ){
    if($SEARCH_REGEX == ""){
        return;
    }
    $allhosts = getHosts();
    foreach($allhosts as $h){
        list ($host, $state) = explode(";", $h);
        if ($state == "1") {
        continue;
        }   
        if( $SEARCH_REGEX != "" ){
            $REGEX = "/".$SEARCH_REGEX."/";
            if(preg_match($REGEX,$host)){
                $TPL_HOSTS[] = $host;
            }
        }      

    }
    return $TPL_HOSTS;
}

function TPL_getServices ( $HOSTS, $SERVICE_REGEX = "", $TPL_REGEX = "" ) {
    global $conf;
    global $debug;
    $COUNT = 1;
    if(sizeof($HOSTS > 0)){
        foreach($HOSTS as $HOST){
            $SERVICES = getServiceList($HOST);
            
            foreach($SERVICES as $SERVICE){
                $SERVICE = $SERVICE['SERVICEDESC'];
    	        $REGEX = "/".$SERVICE_REGEX."/";
                $NAGIOS="";
                #$debug->doCheck('var_dump',$SERVICE);
    	        if(preg_match($REGEX,$SERVICE)){
                    $NAGIOS = parse_xml($HOST,$SERVICE);
    	            $REGEX = "/".$TPL_REGEX."/";
                    if(sizeof($NAGIOS) > 0 && preg_match($REGEX,$NAGIOS['PNP']['TEMPLATE'][1])){
                        $DATA[$COUNT]['HOST'] = $HOST;
                        $DATA[$COUNT]['SERVICE'] = $SERVICE;
                        $DATA[$COUNT]['RRDFILE'] = $conf['rrdbase'] . $HOST."/".$SERVICE.".rrd"; 
                        foreach(array_keys($NAGIOS['PNP']) as $N){
                              $DATA[$COUNT][$N] = $NAGIOS['PNP'][$N];
                        }
                        foreach(array_keys($NAGIOS) as $N){
                            if(preg_match("/^NAGIOS_(.*)/",$N,$NEW_N)){
                                $NEW_N = $NEW_N[1];
                                $DATA[$COUNT][$NEW_N] = $NAGIOS[$N];
                            }
                        }
                    $COUNT++;
                    }
                }
            }
        }   
    }
    return $DATA;
}


#
# user function used in templates
#

function tpl_getdata( $HOST_REGEX, $SERVICE_REGEX = "" , $TPL_REGEX = "") {
    $HOSTS = TPL_getHosts($HOST_REGEX);
    if(sizeof( $HOSTS > 0)){
        $SERVICES = TPL_getServices($HOSTS , $SERVICE_REGEX , $TPL_REGEX );
    }
    if(sizeof($SERVICES > 0 )){
        return $SERVICES;
    }
}

?>
