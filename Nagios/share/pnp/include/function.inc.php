<?php
##
## Program: PNP , Performance Data Addon for Nagios(r)
## Version: $Id: function.inc.php 97 2006-11-02 17:49:29Z linge $
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

function doDataArray($display){
	global $conf;
	global $debug;
	global $hostname;
	global $servicedesc;
	global $special;
	global $timerange;
	global $source;
	global $view;
	global $views;
	global $page;
	global $def;
	global $rrddef;
	global $GRAPH;
	global $PAGE;
	$data = Array();
	if($display == "image" || $display == "image_special"){
                if($display == "image"){
		    $NAGIOS = parse_xml($hostname,$servicedesc);
		    $rrdfile = $conf['rrdbase'].$hostname."/".$servicedesc.".rrd";
		    $template = doFindTemplate($NAGIOS['PNP']['TEMPLATE'][1],"normal");
                    foreach(array_keys($NAGIOS['PNP']) as $N){
                        $$N = $NAGIOS['PNP'][$N];
                    }
		    foreach(array_keys($NAGIOS) as $N){
			$$N = $NAGIOS[$N];
		    }
                }else{
		    $template = doFindTemplate($special,"special");
                } 
                $def =  "";
		$opt =  "";
		ob_start();
                include $template;
                ob_end_clean();
		$data[1]['hostname'] = $hostname;
		$data[1]['n_hostname'] = $NAGIOS['NAGIOS_DISP_HOSTNAME'];
		$data[1]['servicedesc'] = $servicedesc;
		$data[1]['n_servicedesc'] = $NAGIOS['NAGIOS_DISP_SERVICEDESC'];
		$data[1]['template'] = $template;
		$data[1]['source'] = $source;
		$data[1]['view'] = $view;
		$data[1]['view_title'] = $views[$view]['title'];
		$data[1]['start'] = $timerange['start'];
		$data[1]['end'] = $timerange['end'];
		$data[1]['f_start'] = $timerange['f_start'];
		$data[1]['f_end'] = $timerange['f_end'];
		$data[1]['ds_name'] = $NAGIOS['PNP']['NAME'][$source];
		$data[1]['ds_names'] = $NAGIOS['PNP']['NAME'];
		if(is_array($def)){
			$data[1]['rrd_opts'] = $timerange['cmd']." ".$opt[$source]." ".$def[$source]; 
		}else{
			$data[1]['rrd_opts'] = $timerange['cmd']." ".$opt." ".$def; 
		}
	}
        if($display == "service" || $display == "special"){
		$ix = 0;
		$iv = 0;
                if($display == "service"){
		    $NAGIOS = parse_xml($hostname,$servicedesc);
		    $template = doFindTemplate($NAGIOS['PNP']['TEMPLATE'][1]);
		    $rrdfile = $conf['rrdbase'].$hostname."/".$servicedesc.".rrd";
                    foreach(array_keys($NAGIOS['PNP']) as $N){
                        $$N = $NAGIOS['PNP'][$N];
                    }
		    foreach(array_keys($NAGIOS) as $N){
			$$N = $NAGIOS[$N];
		    }
                }else{
		    $template = doFindTemplate($special,"special");
                }
                $def =  "";
		$opt =  "";
		ob_start();
                include $template;
		ob_end_clean();
                foreach($views as $v){
			if( sizeof($def) > 1 ){
				$a = 1;
				foreach($def as $d){
					$data[$ix]['NAGIOS'] = $NAGIOS;
					$data[$ix]['special'] = $special;
					$data[$ix]['hostname'] = $hostname;
					$data[$ix]['n_hostname'] = $NAGIOS['NAGIOS_HOSTNAME'];
					$data[$ix]['servicedesc'] = $servicedesc;
                                        if($servicedesc == "_HOST_"){
                                            $data[$ix]['n_servicedesc'] = "Host Perfdata";
                                        }else{
                                            $data[$ix]['n_servicedesc'] = $NAGIOS['NAGIOS_DISP_SERVICEDESC'];
                                        }    
					$data[$ix]['template'] = $template;
					$data[$ix]['source'] = $a;
					$data[$ix]['multi_graph'] = 1;
					$data[$ix]['view'] = $iv;
					$data[$ix]['view_title'] = $v['title'];
					$data[$ix]['start'] = $timerange[$iv]['start'];
					$data[$ix]['end'] = $timerange[$iv]['end'];
					$data[$ix]['f_start'] = $timerange[$iv]['f_start'];
					$data[$ix]['f_end'] = $timerange[$iv]['f_end'];
					if(isset($ds_name[$a])){	
						$data[$ix]['ds_name'] = $ds_name[$a];
					}else{
						$data[$ix]['ds_name'] = $NAGIOS['PNP']['NAME'][$a]; 
					}						
					$data[$ix]['ds_names'] = $NAGIOS['PNP']['NAME'];
					$data[$ix]['rrd_opts'] = $timerange[$iv]['cmd']." ".$opt[$a]." ".$def[$a]; 
					$a++;
					$ix++;
				}			
			}else{
				$data[$ix]['NAGIOS'] = $NAGIOS;
				$data[$ix]['special'] = $special;
				$data[$ix]['hostname'] = $hostname;
				$data[$ix]['n_hostname'] = $NAGIOS['NAGIOS_DISP_HOSTNAME'];
				$data[$ix]['servicedesc'] = $servicedesc;
                                if($servicedesc == "_HOST_"){
                                    $data[$ix]['n_servicedesc'] = "Host Perfdata";
                                }else{
                                    $data[$ix]['n_servicedesc'] = $NAGIOS['NAGIOS_DISP_SERVICEDESC'];
                                }    
				$data[$ix]['template'] = $template;
				$data[$ix]['source'] = $source;
				$data[$ix]['multi_graph'] = 0;
				$data[$ix]['view'] = $iv;
				$data[$ix]['view_title'] = $v['title'];
				$data[$ix]['start'] = $timerange[$iv]['start'];
				$data[$ix]['end'] = $timerange[$iv]['end'];
				$data[$ix]['f_start'] = $timerange[$iv]['f_start'];
				$data[$ix]['f_end'] = $timerange[$iv]['f_end'];
			    	if(isset($ds_name[1])){	
				    $data[$ix]['ds_name'] = $ds_name[1];
				}else{
				    $data[$ix]['ds_name'] = $NAGIOS['PNP']['NAME'][1]; 
				}						
				$data[$ix]['ds_names'] = $NAGIOS['PNP']['NAME'];
				if(is_array($def)){
					$data[$ix]['rrd_opts'] = $timerange[$iv]['cmd']." ".$opt[$source]." ".$def[$source]; 
				}else{
					$data[$ix]['rrd_opts'] = $timerange[$iv]['cmd']." ".$opt." ".$def; 
				}
				$ix++;
			}		
			$iv++;
		}
	}
	if($display == "host_list"){
		$services = getServices($conf['rrdbase'], $hostname);
		$ix = 0;
		foreach($services as $s){
                        list ($service, $st) = explode(";", $s);
			if ($st == "1") {
		            continue;
		        }
                        $servicedesc = $service;
			$NAGIOS = parse_xml($hostname,$servicedesc);
			$template = doFindTemplate($NAGIOS['PNP']['TEMPLATE'][1]);
			$rrdfile = $conf['rrdbase'].$hostname."/".$servicedesc.".rrd";
                        foreach(array_keys($NAGIOS['PNP']) as $N){
                                $$N = $NAGIOS['PNP'][$N];
                        }
			foreach(array_keys($NAGIOS) as $N){
				$$N = $NAGIOS[$N];
			}
			$def =  "";
			$opt =  "";
                        $ds_name = "";
			ob_start();
                        include($template);
			ob_end_clean();
			if( sizeof($def) > 1 ){
			    $a = 1;
			    foreach($def as $d){
                                $data[$ix]['NAGIOS'] = $NAGIOS;
			        $data[$ix]['hostname'] = $hostname;
			        $data[$ix]['n_hostname'] = $NAGIOS['NAGIOS_DISP_HOSTNAME'];
			        $data[$ix]['servicedesc'] = $servicedesc;
                                if($servicedesc == "_HOST_"){
                                     $data[$ix]['n_servicedesc'] = "Host Perfdata";
                                }else{
                                     $data[$ix]['n_servicedesc'] = $NAGIOS['NAGIOS_DISP_SERVICEDESC'];
                                }    
			    	$data[$ix]['multi_graph'] = 1;
			        $data[$ix]['template'] = $template;
			        $data[$ix]['source'] = $a;
			        $data[$ix]['view'] = $view;
			        $data[$ix]['view_title'] = $views[$view]['title'];
			        $data[$ix]['start'] = $timerange[$view]['start'];
			        $data[$ix]['end'] = $timerange[$view]['end'];
			        $data[$ix]['f_start'] = $timerange[$view]['f_start'];
			        $data[$ix]['f_end'] = $timerange[$view]['f_end'];
			    	if(isset($ds_name[$a])){	
				    $data[$ix]['ds_name'] = $ds_name[$a];
				}else{
				    $data[$ix]['ds_name'] = $NAGIOS['PNP']['NAME'][$a]; 
				}						
			        $data[$ix]['ds_names'] = $NAGIOS['PNP']['NAME'];
				if(is_array($def)){
					$data[$ix]['rrd_opts'] = $timerange[$view]['cmd']." ".$opt[$a]." ".$def[$a]; 
				}else{
					$data[$ix]['rrd_opts'] = $timerange[$view]['cmd']." ".$opt." ".$def; 
				}
			        $ix++;
                                $a++;
                            }
                        }else{
                            $data[$ix]['NAGIOS'] = $NAGIOS;
			    $data[$ix]['hostname'] = $hostname;
			    $data[$ix]['n_hostname'] = $NAGIOS['NAGIOS_DISP_HOSTNAME'];
			    $data[$ix]['servicedesc'] = $servicedesc;
                            if($servicedesc == "_HOST_"){
                                 $data[$ix]['n_servicedesc'] = "Host Perfdata";
                            }else{
                                 $data[$ix]['n_servicedesc'] = $NAGIOS['NAGIOS_DISP_SERVICEDESC'];
                            }    
                            $data[$ix]['multi_graph'] = 0;
			    $data[$ix]['template'] = $template;
			    $data[$ix]['source'] = $source;
			    $data[$ix]['view'] = $view;
			    $data[$ix]['view_title'] = $views[$view]['title'];
			    $data[$ix]['start'] = $timerange[$view]['start'];
			    $data[$ix]['end'] = $timerange[$view]['end'];
			    $data[$ix]['f_start'] = $timerange[$view]['f_start'];
			    $data[$ix]['f_end'] = $timerange[$view]['f_end'];
			    if(isset($ds_name[$a])){	
				$data[$ix]['ds_name'] = $ds_name[1];
			    }else{
				$data[$ix]['ds_name'] = $NAGIOS['PNP']['NAME'][1]; 
			    }						
			    $data[$ix]['ds_names'] = $NAGIOS['PNP']['NAME'];
			    $data[$ix]['rrd_opts'] = $timerange[$view]['cmd']." ".$opt[$source]." ".$def[$source]; 
			    if(is_array($def)){
			    	$data[$ix]['rrd_opts'] = $timerange[$view]['cmd']." ".$opt[$source]." ".$def[$source]; 
			    }else{
			    	$data[$ix]['rrd_opts'] = $timerange[$view]['cmd']." ".$opt." ".$def; 
			    }
			    $ix++;
                        }
		}
	}
	if($display == "page"){
		if($page == ""){
			$page = getFirstPage();
		}
		$debug->doCheck("page","$page");
		parse_page_cfg($page);
		
		$hosts = getHosts();
		$l = 0;
            if(isset($PAGE['use_regex']) && $PAGE['use_regex'] == 1){
                    $use_regex = 1;
            }else{
                    $use_regex = 0;
            }
    
            if(isset($PAGE['background_pdf']) && $PAGE['background_pdf'] != ""){
                    $conf['background_pdf'] = $PAGE['background_pdf'];
            }
    
		foreach($GRAPH as $g){
			if($use_regex == 1){
			        foreach($hosts as $h){
			                list ($host, $st) = explode(";", $h);
			                if ($st == "1") {
			                        continue;
			                }
			                if(isset($g['host_name']) && preg_match('/'.$g['host_name'].'/',$host)){
			                        $services=getServices($conf['rrdbase'],$host);
			    	                foreach($services as $s){
			                                list ($service, $st) = explode(";", $s);
			                                if(isset($g['service_desc']) && preg_match('/'.$g['service_desc'].'/',$service)){
			                			if ($st == "1") {
			     				        	continue;
			       				        }
								$hostname = $host;
								$servicedesc = $service;
								$NAGIOS = parse_xml($hostname,$servicedesc);
								$template = doFindTemplate($NAGIOS['PNP']['TEMPLATE'][1]);
								$rrdfile = $conf['rrdbase'].$hostname."/".$servicedesc.".rrd";
                                                                foreach(array_keys($NAGIOS['PNP']) as $N){
                                                                        $$N = $NAGIOS['PNP'][$N];
                                                                }
								foreach(array_keys($NAGIOS) as $N){
									$$N = $NAGIOS[$N];
								}
								$opt="";
								$def="";
								$ds_name="";
								ob_start();
                                                                include($template);
								ob_end_clean();
                                                                if( sizeof($def) > 1 ){
								       $a=1;
					                               foreach($def as $d){
                                       					 	$data[$l]['NAGIOS'] = $NAGIOS;
                                        					$data[$l]['hostname'] = $hostname;
                                        					$data[$l]['n_hostname'] = $NAGIOS['NAGIOS_DISP_HOSTNAME'];
                                        					$data[$l]['servicedesc'] = $servicedesc;
                                        					$data[$l]['n_servicedesc'] = $NAGIOS['NAGIOS_DISP_SERVICEDESC'];
                                        					$data[$l]['template'] = $template;
										$data[$l]['page_name'] = $PAGE['page_name'];
										$data[$l]['background_pdf'] = $conf['background_pdf'];
                                        					$data[$l]['source'] = $a;
                                        					$data[$l]['multi_graph'] = 1;
                                        					$data[$l]['view'] = $view;
                                        					$data[$l]['view_title'] = $d['title'];
                                        					$data[$l]['start'] = $timerange[$view]['start'];
                                        					$data[$l]['end'] = $timerange[$view]['end'];
                                        					$data[$l]['f_start'] = $timerange[$view]['f_start'];
                                        					$data[$l]['f_end'] = $timerange[$view]['f_end'];
                                        					if(isset($ds_name[$a])){
                                                					$data[$l]['ds_name'] = $ds_name[$a];
                                        					}else{
                                                					$data[$l]['ds_name'] = $NAGIOS['PNP']['NAME'][$a];
                                        					}
                                        					$data[$l]['ds_names'] = $NAGIOS['PNP']['NAME'];
								                if(is_array($def)){
								                        $data[$l]['rrd_opts'] = $timerange[$view]['cmd']." ".$opt[$a]." ".$def[$a];
								                }else{
								                        $data[$l]['rrd_opts'] = $timerange[$view]['cmd']." ".$opt." ".$def;
								                }
                                        					$a++;
                                        					$l++;
									}
								}else{
	
									$data[$l]['NAGIOS'] = $NAGIOS;
                                        				$data[$l]['hostname'] = $hostname;
                                        				$data[$l]['n_hostname'] = $NAGIOS['NAGIOS_DISP_HOSTNAME'];
                                        				$data[$l]['servicedesc'] = $servicedesc;
                                        				$data[$l]['n_servicedesc'] = $NAGIOS['NAGIOS_DISP_SERVICEDESC'];
									$data[$l]['template'] = $template;
									$data[$l]['page_name'] = $PAGE['page_name'];
									$data[$l]['background_pdf'] = $conf['background_pdf'];
									$data[$l]['view'] = $view;
									$data[$l]['view_title'] = $views[$view]['title'];
									$data[$l]['source'] = 1;
									$data[$l]['start'] = $timerange[$view]['start'];
									$data[$l]['end'] = $timerange[$view]['end'];
									$data[$l]['f_start'] = $timerange[$view]['f_start'];
									$data[$l]['f_end'] = $timerange[$view]['f_end'];
									$data[$l]['ds_name'] = $NAGIOS['PNP']['NAME'][1]; 
									$data[$l]['ds_names'] = $NAGIOS['PNP']['NAME'];
								        if(is_array($def)){
								                $data[$l]['rrd_opts'] = $timerange[$view]['cmd']." ".$opt[1]." ".$def[1];
								        }else{
								                $data[$l]['rrd_opts'] = $timerange[$view]['cmd']." ".$opt." ".$def;
								        }
									$l++;
								}
			                                }
			                        }
					}
				}
			}elseif($use_regex == 0){
				$hosts_to_graph = explode(",", $g['host_name']);
				$services_to_graph = explode(",", $g['service_desc']);
			        foreach($hosts as $h){
			                list ($host, $state) = explode(";", $h);
			                if ($state >= "1") {
			                        continue;
			                }
			                if(isset($g['host_name']) && in_array($host ,$hosts_to_graph) ){
		       	                 $services=getServices($conf['rrdbase'],$host);
		    		                foreach($services as $s){
		       	                         list ($service, $state) = explode(";", $s);
		       		                         if(isset($g['service_desc']) && in_array($service, $services_to_graph)){
			                			if ($state >= "1") {
			     				        	continue;
			       				        }
								$hostname = $host;
								$servicedesc = $service;
								$NAGIOS = parse_xml($hostname,$servicedesc);
								$template = doFindTemplate($NAGIOS['PNP']['TEMPLATE'][1]);
								$rrdfile = $conf['rrdbase'].$hostname."/".$servicedesc.".rrd";
                                                                foreach(array_keys($NAGIOS['PNP']) as $N){
                                                                        $$N = $NAGIOS['PNP'][$N];
                                                                }
								foreach(array_keys($NAGIOS) as $N){
									$$N = $NAGIOS[$N];
								}
								$opt="";
								$def="";
								$ds_name="";
								ob_start();
                                                                include($template);
								ob_end_clean();
                                                                if( sizeof($def) > 1 ){
								       $a=1;
					                               foreach($def as $d){
                                       					 	$data[$l]['NAGIOS'] = $NAGIOS;
                                        					$data[$l]['hostname'] = $hostname;
                                        					$data[$l]['n_hostname'] = $NAGIOS['NAGIOS_DISP_HOSTNAME'];
                                        					$data[$l]['servicedesc'] = $servicedesc;
                                        					$data[$l]['n_servicedesc'] = $NAGIOS['NAGIOS_DISP_SERVICEDESC'];
                                        					$data[$l]['template'] = $template;
										$data[$l]['page_name'] = $PAGE['page_name'];
								        	$data[$l]['background_pdf'] = $conf['background_pdf'];
                                        					$data[$l]['source'] = $a;
                                        					$data[$l]['multi_graph'] = 1;
                                        					$data[$l]['view'] = $view;
                                        					$data[$l]['view_title'] = $d['title'];
                                        					$data[$l]['start'] = $timerange[$view]['start'];
                                        					$data[$l]['end'] = $timerange[$view]['end'];
                                        					$data[$l]['f_start'] = $timerange[$view]['f_start'];
                                        					$data[$l]['f_end'] = $timerange[$view]['f_end'];
                                        					if(isset($ds_name[$a])){
                                                					$data[$l]['ds_name'] = $ds_name[$a];
                                        					}else{
                                                					$data[$l]['ds_name'] = $NAGIOS['PNP']['NAME'][$a];
                                        					}
                                        					$data[$l]['ds_names'] = $NAGIOS['PNP']['NAME'];
                                        					$data[$l]['rrd_opts'] = $timerange[$view]['cmd']." ".$opt[$a]." ".$def[$a];
                                        					$a++;
                                        					$l++;
									}
								}else{
									$data[$l]['NAGIOS'] = $NAGIOS;
                                        				$data[$l]['hostname'] = $hostname;
                                        				$data[$l]['n_hostname'] = $NAGIOS['NAGIOS_DISP_HOSTNAME'];
                                        				$data[$l]['servicedesc'] = $servicedesc;
                                        				$data[$l]['n_servicedesc'] = $NAGIOS['NAGIOS_DISP_SERVICEDESC'];
									$data[$l]['template'] = $template;
									$data[$l]['page_name'] = $PAGE['page_name'];
									$data[$l]['background_pdf'] = $conf['background_pdf'];
									$data[$l]['view'] = $view;
									$data[$l]['view_title'] = $views[$view]['title'];
									$data[$l]['source'] = 1;
                                        				$data[$l]['multi_graph'] = 0;
									$data[$l]['start'] = $timerange[$view]['start'];
                                        				$data[$l]['end'] = $timerange[$view]['end'];
									$data[$l]['f_start'] = $timerange[$view]['f_start'];
									$data[$l]['f_end'] = $timerange[$view]['f_end'];
                                        				if(isset($ds_name[1])){
                                                				$data[$l]['ds_name'] = $ds_name[1];
                                        				}else{
                                                				$data[$l]['ds_name'] = $NAGIOS['PNP']['NAME'][1];
                                        				}
                                        				$data[$l]['ds_names'] = $NAGIOS['PNP']['NAME'];
									$data[$l]['rrd_opts'] = $timerange[$view]['cmd']." ".$opt[1]." ".$def[1]; 
									$l++;
								}
			                                }
			                        }
					}
				}
			}
			
		}
	}
	if(sizeof($data) == 0){
		$debug->doCheck("no_data","");
	}
	return $data;
}

function rrd_execute($rrdtool, $command, $out = "STDOUT") {
        global $debug;
        $descriptorspec = array (
		0 => array ("pipe","r"), // stdin is a pipe that the child will read from
		1 => array ("pipe","w") // stdout is a pipe that the child will write to
	);

	$process = proc_open($rrdtool, $descriptorspec, $pipes);
	$deb = Array();

	if (is_resource($process)) {

		fwrite($pipes[0], $command);
		fclose($pipes[0]);

		$data = fgets($pipes[1]);
		if (preg_match('/^ERROR/', $data)) {
			$deb['data'] = $data;
			$deb['command'] = format_rrd_debug($command);
			$deb['opt'] = $opt;
                        $debug->doCheck("rrdgraph",$deb);
                }
                if( $out == "STDOUT") {
			header("Content-type: image/png");
			echo $data;
			fpassthru($pipes[1]);
		}
                ob_start();
		fpassthru($pipes[1]);
                ob_end_clean();
                fclose($pipes[1]);
		proc_close($process);
	}
}

function format_rrd_debug($data) {
    $data = preg_replace('/(HRULE|VDEF|DEF|CDEF|GPRINT|LINE|AREA|COMMENT)/','\<br>${1}', $data);
    return $data;
}

function getTimeRange($start,$end,$view) {
	global $conf;
	global $views;
        global $debug;
	$view=intval(doClean($view));	
	if($view >= sizeof($views)){
		$view = 1;
	}

        if($end == ""){
            $end = time();
        }elseif(!is_numeric($end)){
            $timestamp = strtotime($end);
            if(!$timestamp){
                $debug->doCheck('print_r',"wrong fmt $timestamp"); 
            }else{
                $end = $timestamp;
            }
        }else{
            $end = doClean($end);
        }
            
        if($start == ""){
	    $start=($end - $views[$view]['start']);
        }elseif(!is_numeric($start)){
            $timestamp = strtotime($start);
            if(!$timestamp){
                $debug->doCheck('print_r',"wrong fmt $timestamp"); 
            }else{
                $start = $timestamp;
            }
        }else{
            $start=doClean($start);
        }
            
	if($start >= $end){
	    $debug->doCheck('print_r',"start ist groesser als end");	
	}
	$timerange['start'] = $start;
	$timerange['f_start'] = date($conf['date_fmt'],$start);
	$timerange['end'] = $end;
	$timerange['f_end'] = date($conf['date_fmt'],$end);
	$timerange['cmd'] = " --start $start --end $end ";
	for ($i = 0; $i < sizeof($views); $i++) {
		$timerange[$i]['start'] = $end - $views[$i]['start'];
		$timerange[$i]['f_start'] = date($conf['date_fmt'],$end - $views[$i]['start']);
		$timerange[$i]['end'] = $end;
		$timerange[$i]['f_end'] = date($conf['date_fmt'],$end);
		$timerange[$i]['cmd'] = " --start " . ($end - $views[$i]['start']) . " --end  $end" ;
	}
	return $timerange;
}

function doXML($rrddef){
    header("Content-Type: application/xml; charset=UTF-8");
    readfile($rrddef);
}

function doExportData($rrdfile){
    header("Content-Type: application/xml; charset=UTF-8");
    print "<pnp>\n";
    $OUT = rrdtool_fetch($rrdfile);
    print $OUT;
    print "</pnp>";
}

function rrdtool_fetch($rrdfile) {
    global $debug;
    global $conf;
    global $timerange;
    $descriptorspec = array (
        0 => array ("pipe","r"), // stdin is a pipe that the child will read from
        1 => array ("pipe","w") // stdout is a pipe that the child will write to
    );

    $process = proc_open($conf['rrdtool']." - ", $descriptorspec, $pipes);
    if (is_resource($process)) {
        $command = " fetch ".$rrdfile." MAX -s ".$timerange['start']." -e ".$timerange['end'];
        fwrite($pipes[0], $command);
        fclose($pipes[0]);

        $buffer = fgets($pipes[1]);
        if (preg_match('/^ERROR/', $buffer)) {
            $deb['data'] = $buffer;
            $deb['command'] = format_rrd_debug($command);
            $deb['opt'] = $opt;
            $debug->doCheck("rrdgraph",$deb);
        }
        ob_start();
        $buffer = "";
        while (!feof($pipes[1])) {
            $array = split(" ", (fgets($pipes[1], 4096)));
            $temp_buffer = "";
            $count = 0;
            if(preg_match("/^\d+:/",$array[0])){
            foreach($array as $key){
                if($count == 0){
                    $temp_buffer .= "<row><time>".date($conf['date_fmt'],$key)."</time>";
                }else{
                    $temp_buffer .= "<ds$count>".floatval($key)."</ds$count>";
                }
                $count++;
            }
            $buffer .= $temp_buffer."</row>\n";
        }
    }
    ob_end_clean();
    fclose($pipes[1]);
    proc_close($process);
    }
    return $buffer;
}



function doImage($out = "STDOUT",$data) {
	global $conf;
	# construct $command to rrdtool
        if(isset($conf['RRD_DAEMON_OPTS']) && $conf['RRD_DAEMON_OPTS'] != '' ){
            $command = " graph --daemon=" . $conf['RRD_DAEMON_OPTS'] . " - ";
        }else{
	    $command = " graph - ";
        }

	if($conf['graph_opt']){
		$command .= $conf['graph_opt'];
	}
	if(is_numeric($conf['graph_width'])){
		$conf['graph_width'] = abs($conf['graph_width']);
		$command .= " --width=".$conf['graph_width'];
	}
	if(is_numeric($conf['graph_height'])){
		$conf['graph_height'] = abs($conf['graph_height']);
		$command .= " --height=".$conf['graph_height'];
	}

	$command .= $data[1]['rrd_opts'];

	#Workaround für neuen Templates ab 0.1.7
	#if (!is_array($def)) {
	#	$command .= " --imgformat PNG " . $opt . " ";
	#	$command .= $def;
	#} else {
	#	$command .= " --imgformat PNG " . $opt[$source] . " ";
	#	$command .= $def[$source];
	#}
	if($out == "STDOUT"){
		$debug = rrd_execute($conf['rrdtool'] . " - ", $command, $out);
	}else{
		$data = rrd_execute($conf['rrdtool'] . " - ", $command, $out);
		return $data;
	}
}

function saveImage($rrd_opts, $out = ""){
	global $conf;
	global $timerange;

	$img = array();
	$img['name'] = tempnam($conf['temp'],"PNP");
	$command = " graph " . $img['name'] . " ";

	if($conf['graph_opt']){
		$command .= $conf['graph_opt'];
	}
	$width = 0;
	if(is_numeric($conf['graph_width'])){
		$width = abs($conf['graph_width']);
	}
	if (($out == 'PDF') && isset($conf['pdf_width']) && (is_numeric($conf['pdf_width']))){
		$width = abs($conf['pdf_width']);
	}
	if ($width > 0){
		$command .= " --width=".$width;
	}
	$height = 0;
	if(is_numeric($conf['graph_height'])){
		$height = abs($conf['graph_height']);
	}
	if (($out == 'PDF') && isset($conf['pdf_height']) && (is_numeric($conf['pdf_height']))){
		$height = abs($conf['pdf_height']);
	}
	if ($height > 0){
		$command .= " --height=".$height;
	}


	$command .= $conf['graph_opt'] . " --imgformat PNG " . $rrd_opts . " ";
	#print $command."\n";
	rrd_execute($conf['rrdtool'] . " - ", $command, "quiet");
	if (function_exists('imagecreatefrompng')) {
		$image = imagecreatefrompng($img['name']);
		imagepng($image, $img['name']);
		list ($img['width'], $img['height'], $img['type'], $img['attr']) = getimagesize($img['name']);
		
	}

	return $img;
}

function doClean($string) {
    if (1 == get_magic_quotes_gpc()){
        $string = stripslashes($string);
    }
    $string = preg_replace('/[ :\/\\\\]/', "_", $string);
    return $string;
}

function doCleanPage($string) {
    if (1 == get_magic_quotes_gpc()){
        $string = stripslashes($string);
    }
    $string = preg_replace('/[\/]/', "_", $string);
    $string = urldecode($string);
    return $string;
}


function getServices($rrdbase, $hostname) {
	global $conf;
	$services = array ();
	$host = array();
	$i = 0;
	$path = $rrdbase . $hostname;
	if (is_dir($path)) {
		if ($dh = opendir($path)) {
			while (($file = readdir($dh)) !== false) {
				$NAGIOS_SERVICEDESC = "";
				if ($file == "." || $file == "..")
					continue;
				if (!preg_match("/(.*)\.xml$/", $file, $servicedesc))
					continue;
				$fullpath = $path . "/" . $file;
				$stat = stat("$fullpath");
				$age = (time() - $stat['mtime']);
				if ($age > $conf['max_age']) { # 6Stunden
					if($servicedesc[1] == "_HOST_"){
						$host = $servicedesc[1].";1";
					}else{
						$services[] = $servicedesc[1] . ";1";
					}
				} else {
					if($servicedesc[1] == "_HOST_"){
						$host = $servicedesc[1].";0";
					}else{
						$services[] = $servicedesc[1] . ";0";
					}
				}
			}
			closedir($dh);
		}
	}	
	natsort($services);
	if($host){
		array_unshift($services, $host);
	}
	return $services;
}

function getFirstService($rrdbase, $hostname) {
	$services = getServices($rrdbase, $hostname);
	foreach ($services as $srv) {
		list ($service, $state) = explode(";", $srv);
		if ($state == "0" ) {
			break;
		}
	}
	return $service;
}

function getServiceList($hostname){
    global $conf;
    $rrdbase = $conf['rrdbase'];
    $service_list = array();
    $multi_services = array();
    $services = getServices($rrdbase, $hostname);
    $count = 0;
    foreach ($services as $srv) {
        list ($service, $state) = explode(";", $srv);
        $XML = parse_xml($hostname, $service);
        if($XML['PNP']['IS_MULTI'][1] == 2){
            $sort = strtoupper($XML['NAGIOS_MULTI_PARENT'].$XML['PNP']['IS_MULTI'][1].$service);
        }else{
            $sort = strtoupper($service);
        }
        if($service == "_HOST_"){ # Host Perfdata on Pos 1
            $sort = "1".$sort;
            $XML['NAGIOS_SERVICEDESC'] = _HOSTPERFDATA;
        }
        $service_list[$sort]['N_HOSTNAME'] = $XML['NAGIOS_HOSTNAME'];
        $service_list[$sort]['SERVICEDESC'] = $service;
        $service_list[$sort]['N_SERVICEDESC'] = $XML['NAGIOS_SERVICEDESC'] ;
        $service_list[$sort]['IS_MULTI'] = $XML['PNP']['IS_MULTI'][1];
        $service_list[$sort]['MULTI_PARENT'] = $XML['NAGIOS_MULTI_PARENT'];
        $service_list[$sort]['IS_INVALID'] = $state;
        $count++;
    }
    ksort($service_list);
    return $service_list;
}

function isAuthorizedFor($auth) {
	global $conf;
	if ($auth == "service_links") {

		$users = explode(",", $conf['allowed_for_service_links']);
		if (in_array('EVERYONE', $users)) {
			return 1;
		}
		elseif (in_array('NONE', $users)) {
			return 0;
		}
		elseif (in_array($_SERVER["REMOTE_USER"], $users)) {
			return 1;
		} else {
			return 0;
		}
	}
	if ($auth == "host_search") {
		$users = explode(",", $conf['allowed_for_host_search']);
		if (in_array('EVERYONE', $users)) {
			return 1;
		}
		elseif (in_array('NONE', $users)) {
			return 0;
		}
		elseif (in_array($_SERVER["REMOTE_USER"], $users)) {
			return 1;
		} else {
			return 0;
		}
	}
	if ($auth == "host_overview") {
		$users = explode(",", $conf['allowed_for_host_overview']);
		if (in_array('EVERYONE', $users)) {
			return 1;
		}
		elseif (in_array('NONE', $users)) {
			return 0;
		}
		elseif (in_array($_SERVER["REMOTE_USER"], $users)) {
			return 1;
		} else {
			return 0;
		}
	}
	if ($auth == "pages") {
		$users = explode(",", $conf['allowed_for_pages']);
		if (in_array('EVERYONE', $users)) {
			return 1;
		}
		elseif (in_array('NONE', $users)) {
			return 0;
		}
		elseif (in_array($_SERVER["REMOTE_USER"], $users)) {
			return 1;
		} else {
			return 0;
		}
	}
}

function doServiceList($hostname,$data) {
	global $conf;

	if (isAuthorizedFor('service_links') == 1) {

		$services = getServiceList($hostname);
		print "<hr>\n";
		foreach ($services as $XML) {
                        $css_class = "int";
                        $css_class .= $XML['IS_MULTI'];
                        $disp_service = $XML['N_SERVICEDESC'];
                        $title_service = $XML['N_SERVICEDESC'];
                        $service = $XML['SERVICEDESC'];

			if (strlen($service) > 20) {
				$disp_service = substr($disp_service, 0, 20) . "...";
			}
			if($disp_service == "_HOST_"){
				$disp_service = _HOSTPERFDATA;
			}
			if ($XML['IS_INVALID'] == 1) {
                            $css_class = "inact_".$css_class;
			    print "<li><span class=\"$css_class\" title=\""._OVERINACT."\">" . $disp_service . "</span></li>\n";
			}else{
			    print "<li><a href=\"" . $_SERVER['PHP_SELF'] . "?host=$hostname&srv=" . $service . "\" class=\"$css_class\" ";
                            print "title=\"Graph for Service $title_service\" >" . $disp_service . "</a></li>\n";
		        }
                }
	}
}

function getHosts() {
	global $conf;
	$hosts = array();
	if (is_dir($conf['rrdbase'])) {
		if ($dh = opendir($conf['rrdbase'])) {
			while (($file = readdir($dh)) !== false) {
				if ($file == "." || $file == "..")
					continue;
				$stat = stat($conf['rrdbase'] . "/" . $file);
				$age = (time() - $stat['mtime']);
				if ($age < $conf['max_age']) {
					$hosts[] = $file . ";0";
				} else {
					$hosts[] = $file . ";1";
				}
			}

			closedir($dh);
		} else
			die("Cannot open directory:  $path");
	}
	if(sizeof($hosts)>0){
		natsort($hosts);
	}
	return $hosts;
}

function getPages() {
	global $conf;
	global $debug;
	$pages = array();
	if (is_dir($conf['page_dir'])) {
		if ($dh = opendir($conf['page_dir'])) {
			while (($file = readdir($dh)) !== false) {
				if(preg_match('/(.*)\.cfg$/',basename($file),$page)){
					$pages[] = urlencode($page[1]);
				}
			}
			closedir($dh);
		} else
			die("Cannot open directory:  $path");
	}
	if(sizeof($pages)>0){
		natsort($pages);
	}else{
		$debug->doCheck("page_no_data","");
	}
	return $pages;
}

function getFirstPage(){
	$pages = getPages();
	if(sizeof($pages) > 0 ){
		return urldecode($pages[0]);
	}
}

function getFirstHost() {

	if (isAuthorizedFor('host_search') == "1") {
		$hosts = getHosts();
		foreach ($hosts as $host) {
			list ($host, $state) = explode(";", $host);
			if ($state == "0") {
				break;
			}
		}
		return $host;
	}
}
	

function doLink($type,$data) {
	global $conf;
	#global $NAGIOS;
        $NAGIOS = $data[0]['NAGIOS'];

	if ($type == "NAGIOS_HOST") {
		print "<li><strong>" . _HOST . "</strong>\n";
		printf("<a href=%s/status.cgi?host=%s title=\"Nagios Host Details\" class=\"nagios\">%s</a></li>\n", $conf['nagios_base'], $NAGIOS['NAGIOS_HOSTNAME'], doStrip($NAGIOS['NAGIOS_HOSTNAME']));
	}
	if ($type == "NAGIOS_SERVICE") {
		print "<li><strong>" . _SERVICE . "</strong>\n";
		printf("<a href=\"%s/extinfo.cgi?host=%s&service=%s&type=2\" title=\"Nagios Service Details\" class=\"nagios\"t>%s</a></li>\n", $conf['nagios_base'], $NAGIOS['NAGIOS_HOSTNAME'], $NAGIOS['NAGIOS_SERVICEDESC'], doStrip($NAGIOS['NAGIOS_SERVICEDESC']));
	}
	if ($type == "NAGIOS_HOSTSTATE") {
		print "<li><strong>" . _HOSTSTATE . "</strong>".$NAGIOS['NAGIOS_HOSTSTATE']."&nbsp;[".$NAGIOS['NAGIOS_HOSTSTATETYPE']."]</li>\n";
	}
	if ($type == "NAGIOS_SERVICESTATE") {
		print "<li><strong>" . _SERVICESTATE . "</strong>".$NAGIOS['NAGIOS_SERVICESTATE']."&nbsp;[".$NAGIOS['NAGIOS_SERVICESTATETYPE']."]</li>\n";
	}
	if ($type == "NAGIOS_TIMET") {
		print "<li><strong>" . _TIMET . "</strong>" . date($conf['date_fmt'],$NAGIOS['NAGIOS_TIMET']) . "</li>\n";
	}
	if ($type == "NAGIOS_SERVICENOTESURL" ) {
		if(isset($NAGIOS['NAGIOS_SERVICENOTESURL']) && $NAGIOS['NAGIOS_SERVICENOTESURL'] != "" ){
			print "<li><strong>" . _NOTES . "</strong><a href=\"".$NAGIOS['NAGIOS_SERVICENOTESURL']."\" title=\"Notes\" class=int>" . _DOKU . "</a></li>\n";
		}
	}

}

function doPDF($display,$data) {
	require ('fpdi.php');
	global $NAGIOS;
	global $conf;
        if(file_exists($conf['background_pdf'])){
                $use_bg=1;
        }
	class PDF extends FPDI {
		//Page header
		function Header() {
			//Arial bold 10 
			$this->SetFont('Arial', 'B', 10);
			//Move to the right
			$this->Cell(80);
			//Title
			$this->Cell(30, 10, _PDFTITLE, 0, 1, 'C');
			//Line break
			#$this->Ln(20);
		}

		//Page footer
		function Footer() {
			//Position at 1.5 cm from bottom
			$this->SetY(-20);
			//Arial italic 8
			$this->SetFont('Arial', 'I', 8);
			//Page number
			$this->Cell(0, 10, _PDFPAGE . $this->PageNo() . '/{nb}', 0, 0, 'C');
		}
	}

	$pdf = new PDF('P', 'mm', 'A4');
	$pdf->AliasNbPages();
        $pdf->SetAutoPageBreak('off');
	$pdf->SetMargins(12.5,25,10);
	$pdf->AddPage();
	if($use_bg){
		$pdf->setSourceFile($conf['background_pdf']);
		$tplIdx = $pdf->importPage(1,'/MediaBox');
		$pdf->useTemplate($tplIdx);
	}
	$pdf->SetCreator('Created with PNP');
	$pdf->SetAuthor($_PHP['REMOTE_USER']);
	$pdf->SetFont('Arial', '', 10);
	$pdf->Cell(120, 4, '', 0, 1, 'L');
	if($display == "service"){
		foreach($data as $d) {
			if ($pdf->GetY() > 220) {
				$pdf->AddPage();
				if($use_bg){$pdf->useTemplate($tplIdx);}	
			}
                        if($d['source'] == 1){
                            $pdf->SetFont('Arial', '', 10);
                            $pdf->CELL(120, 5, $d["n_hostname"]." / ".$d["n_servicedesc"] , 0, 1);
                            $pdf->SetFont('Arial', '', 8);
                            $pdf->CELL(120, 5, $d["view_title"]. " (" . $d["f_start"]." - ".$d["f_end"].")", 0, 1);
                            $pdf->CELL(120, 5, _DATASOURCE ." ".$d["ds_name"], 0, 1);
                        }else{
                            $pdf->SetFont('Arial', '', 8);
                            $pdf->CELL(120, 5, _DATASOURCE ." ".$d["ds_name"], 0, 1);
                        }
			$img = saveImage($d['rrd_opts'],'PDF');
                        $Y = $pdf->GetY();
			$cell_height = ($img['height'] * 0.23);
			$cell_width = ($img['width'] * 0.23);
                        $pdf->Image($img['name'], 12.5, $Y, $cell_width, $cell_height, 'PNG');
                        $pdf->CELL(120, $cell_height, '', 0, 1);
			unlink($img['name']);
		}
	}
	if($display == "special"){
		foreach($data as $d) {
			if ($pdf->GetY() > 220) {
				$pdf->AddPage();
				if($use_bg){$pdf->useTemplate($tplIdx);}	
			}
                        if($d['source'] == 1){
                            $pdf->SetFont('Arial', '', 10);
                            $pdf->CELL(120, 5, $d["special"], 0, 1);
                            $pdf->SetFont('Arial', '', 8);
                            $pdf->CELL(120, 5, $d["view_title"]. " (" . $d["f_start"]." - ".$d["f_end"].")", 0, 1);
                            $pdf->CELL(120, 5, _DATASOURCE ." ".$d["ds_name"], 0, 1);
                        }else{
                            $pdf->SetFont('Arial', '', 8);
                            $pdf->CELL(120, 5, _DATASOURCE ." ".$d["ds_name"], 0, 1);
                        }
			$img = saveImage($d['rrd_opts'],'PDF');
                        $Y = $pdf->GetY();
			$cell_height = ($img['height'] * 0.23);
			$cell_width = ($img['width'] * 0.23);
                        $pdf->Image($img['name'], 12.5, $Y, $cell_width, $cell_height, 'PNG');
                        $pdf->CELL(120, $cell_height, '', 0, 1);
			unlink($img['name']);
		}
	}
	if($display == "page"){
                foreach($data as $d) {
			if ($pdf->GetY() > 220) {
				$pdf->AddPage();
				if($use_bg){$pdf->useTemplate($tplIdx);}
			}

			$pdf->SetFont('Arial', '', 10);
			$pdf->CELL(120, 5, $d["hostname"]." / ".$d["servicedesc"] , 0, 1);
			$pdf->SetFont('Arial', '', 8);
			$pdf->CELL(120, 5, $d["f_start"]." - ".$d["f_end"], 0, 1);

			$img = saveImage($d['rrd_opts'],'PDF');
                        $Y = $pdf->GetY();
			$cell_height = ($img['height'] * 0.23);
			$cell_width = ($img['width'] * 0.23);
                        $pdf->Image($img['name'], 12.5, $Y, $cell_width, $cell_height, 'PNG');
                        $pdf->CELL(120, $cell_height, '', 0, 1);
			unlink($img['name']);
               }
        }
        if($display == "host_list"){
                foreach($data as $d) {
                        if ($pdf->GetY() > 220) {
                                $pdf->AddPage();
				if($use_bg){$pdf->useTemplate($tplIdx);}
                        }
                        if($d['source'] == 1){
                            $pdf->SetFont('Arial', '', 10);
                            $pdf->CELL(120, 5, $d["n_hostname"]." / ".$d["n_servicedesc"] , 0, 1);
                            $pdf->SetFont('Arial', '', 8);
                            $pdf->CELL(120, 5, $d["view_title"]. " (" . $d["f_start"]." - ".$d["f_end"].")", 0, 1);
                            $pdf->CELL(120, 5, _DATASOURCE ." ".$d["ds_name"], 0, 1);
                        }else{
                            $pdf->SetFont('Arial', '', 8);
                            $pdf->CELL(120, 5, _DATASOURCE ." ".$d["ds_name"], 0, 1);
                        }

			$img = saveImage($d['rrd_opts'],'PDF');
                        $Y = $pdf->GetY();
			$cell_height = ($img['height'] * 0.23);
			$cell_width = ($img['width'] * 0.23);
                        $pdf->Image($img['name'], 12.5, $Y, $cell_width, $cell_height, 'PNG');
                        $pdf->CELL(120, $cell_height, '', 0, 1);
			unlink($img['name']);
                }
        }
	$pdf->Output("pnp4nagios.pdf","I");
	exit;
}

function doStrip($string) {
	if (strlen($string) > 20) {
		$string = substr($string, 0, 20) . '...';
	}
	return $string;
}

function doFindTemplate($template,$tpltype = "normal") {
        global $conf;
        global $debug;


        if($tpltype == 'normal'){
            $r_template = doFindRecursiveTemplate($template,"templates");
            $r_template_dist = doFindRecursiveTemplate($template,"templates.dist");
            
            $debug->doCheck("template_dir",$conf['template_dir'].'/templates');
            $debug->doCheck("template_dir",$conf['template_dir'].'/templates.dist');
            if (is_readable($conf['template_dir'].'/templates/' . $template . '.php')) {
                $template_file = $conf['template_dir'].'/templates/' . $template . '.php';
            }elseif (is_readable($conf['template_dir'].'/templates.dist/' . $template . '.php')) {
                $template_file = $conf['template_dir'].'/templates.dist/' . $template . '.php';
            }elseif($r_template != "" ){
                $template_file = $conf['template_dir'].'/templates/'. $r_template . '.php';
            }elseif($r_template_dist != "" ){
                $template_file = $conf['template_dir'].'/templates.dist/'. $r_template_dist . '.php';
            }elseif (is_readable($conf['template_dir'].'/templates/default.php')) {
                $template_file = $conf['template_dir'].'/templates/default.php';
            }else { 
                $template_file = $conf['template_dir'].'/templates.dist/default.php';
            }
        }

        if($tpltype == 'special'){
            $debug->doCheck("template_dir",$conf['template_dir'].'/templates.special');
            $template_file = $conf['template_dir'].'/templates.special/' . $template . '.php';
            $debug->doCheck("template","$template_file");
        }
        return $template_file;
}

/*
* doFindRecursiveTemplate() by Mattias Ryrlén op5 AB
*/

function doFindRecursiveTemplate($template, $dir="templates") {
    global $conf;
    global $debug;
    $template_file = "";
    $r_template = "";
    $recursive = explode("_", $template);
    if($conf['enable_recursive_template_search'] == 1){
        $i = 0;
        foreach ($recursive as $value) {
            if ($i == 0) {
                $r_template = $value;
            } else {
                $r_template = $r_template . '_' . $value;
            }
            if (is_readable($conf['template_dir']. '/' . $dir . '/' . $r_template . '.php')) {
                $template_file = $r_template;
            }
            $i++;
        }
    }
    return $template_file;
}

function doHead($title) {
	global $conf;

	$graph_width="500";
	$graph_height="100";

	if(is_numeric($conf['graph_width'])){
	        $graph_width = abs($conf['graph_width'])+120;
	}
	if(is_numeric($conf['graph_height'])){
	        $graph_height = abs($conf['graph_height'])+170;
	}

	print "<html>\n";
	print "<head>\n";
	print "<meta http-equiv=\"refresh\" content=\"" . $conf['refresh'] . "; URL=" . $_SERVER['REQUEST_URI'] . "\">\n";
	print "<title>$title</title>\n";
	print "<script src=\"include/js/prototype.js\" type=\"text/javascript\"></script>\n";
	print "<script src=\"include/js/scriptaculous.js\" type=\"text/javascript\"></script>\n";
	print "<style type=\"text/css\">@import url(include/js/calendar-blue.css);</style>\n";
	print "<script type=\"text/javascript\" src=\"include/js/calendar.js\"></script>\n";
	print "<script type=\"text/javascript\" src=\"include/js/calendar-en.js\"></script>\n";
	print "<script type=\"text/javascript\" src=\"include/js/calendar-setup.js\"></script>\n";
	print "<LINK REL='stylesheet' TYPE='text/css' HREF='include/style.css'>\n";
	print "<script type=\"text/javascript\">\n";
	print "function Gzoom (url) {\n";
	print "  GzoomWindow = window.open(url, \"PNP\", \"width=$graph_width,height=$graph_height,resizable=yes,scrollbars=yes\");\n";
	print "  GzoomWindow.focus();\n";
	print "}\n";
	print "</script>\n";
	print "</head>";

}
function doOpenPage() {
	print "<body>\n";
	print "<table cellpadding=\"6\" cellspacing=\"0\"><tr valign=\"top\" ><td width=\"500\">\n";
	print "<div id=\"Inhalt\">\n";
}

function doErrPage() {
	global $debug;
	print "<li>Errors found</li>\n";
	print "</ul><div>\n";
	print $debug['text'];
	print "</div>\n";
}

function doClosepage() {
	print "</div>\n";
	print "</td></tr></table>\n";
	print "</body>\n";
	print "</html>\n";
}

function doFooterLink() {
	print "<hr>\n";
	print "<li><a href=\"http://www.pnp4nagios.org/pnp\"><img src=\"images/pnp.png\"></a>\n";
	print "<a href=\"http://www.rrdtool.org\"><img src=\"images/rrdtool.png\"></a></li>\n";
	print "</ul>\n";
}


function doPageLinks() {
	global $conf;
	global $views;
	global $view;
	global $timerange;
	global $page;
	global $PAGE;
	$i=0;
	print "<strong><p> Timeranges </p></strong>\n";
	foreach($views as $v){
		print "<li><a href=\"" . $_SERVER['PHP_SELF'] . "?view=$i&page=$page\" class=\"int\"";
		print " title=\"Timerange: ".$timerange[$i]['f_start']." - ".$timerange[$i]['f_end']."\">".$v['title']."</a></li>\n";
		$i++;
	}
	$pages = getPages();
	print "<hr>\n";
	foreach($pages as $p){
		parse_page_cfg(urldecode($p));
		print "<li><a href=\"" . $_SERVER['PHP_SELF'] . "?page=$p&view=$view\" class=\"int\"";
		print "title=\"".$PAGE['page_name']."\">".urldecode($p)."</a></li>\n";
	}
}
		
function doHostLinks($hostname) {
	global $conf;
	global $views;
	global $timerange;
	$i=0;
	print "<hr><strong><p> Timeranges </p></strong>\n";
	foreach($views as $view){
		print "<li><a href=\"" . $_SERVER['PHP_SELF'] . "?host=$hostname&view=$i\" class=\"int\"";
		print " title=\"Timerange: ".$timerange[$i]['f_start']." - ".$timerange[$i]['f_end']."\">".$view['title']."</a></li>\n";
		$i++;
	}
}
		
function doNavigation($type,$hostname,$servicedesc,$data) {
    print "</td><td>\n";
    print "<ul id=\"Navigation\">\n";	
    if($type == 'SERVICE'){
        doSearchBox($hostname);
	doPDFIcon($type);
	doXMLIcon();
	doPageIcon();
	doCalendarIcon($type);
	if($servicedesc == "_HOST_"){
	    doLink('NAGIOS_HOST',$data);
	    doLink('NAGIOS_HOSTSTATE',$data);
	    doLink('NAGIOS_TIMET',$data);
	}else{
	    doLink('NAGIOS_HOST',$data);
	    doLink('NAGIOS_SERVICE',$data);
	    doLink('NAGIOS_HOSTSTATE',$data);
	    doLink('NAGIOS_SERVICESTATE',$data);
	    doLink('NAGIOS_TIMET',$data);
	    doLink('NAGIOS_SERVICENOTESURL',$data);
	}
	doServiceList($hostname,$data);
	doFooterLink();
    }
    if($type == 'SPECIAL'){
        doSearchBox($hostname);
	doPDFIcon($type);
	doPageIcon();
	doCalendarIcon($type);
	doFooterLink();
    }
    if($type == 'HOST_OVERVIEW'){
	doSearchBox($hostname);
	doPDFIcon($type);
        doPageIcon();
        doCalendarIcon($type);
	doLink('NAGIOS_HOST',$data);
	doLink('NAGIOS_HOSTSTATE',$data);
	doLink('NAGIOS_TIMET',$data);
	doHostLinks($hostname);
	doServiceList($hostname,$data);
	doFooterLink();
    }

    if($type == 'HOST'){
	doSearchBox($hostname);
	doPDFIcon($type);
        doCalendarIcon($type);
	doLink('NAGIOS_HOST',$data);
	doLink('NAGIOS_HOSTSTATE',$data);
	doLink('NAGIOS_TIMET',$data);
	doFooterLink();
    }

    if($type == 'PAGE'){
	doPDFIcon($type);
	doPageIcon();
	doCalendarIcon($type);
	doPageLinks();
	doFooterLink();
    }

}

function doSearchBox($hostname) {

	if (isAuthorizedFor('host_search') == "1") {

		print "<form name=\"host\" method=\"get\" action=\"index.php\" >\n";
		print "<li><strong>" . _SEARCH . "</strong><input value=\"$hostname\" autocomplete=\"off\" type='text' id=\"host_name\" name='host' size='16' class=\"NavBarSearchItem\"/></li>";
		print "<div class=\"auto_complete\" id=\"host_name_auto_complete\">";
		print "</div></form>\n";
		print "<script type=\"text/javascript\">new Ajax.Autocompleter('host_name', 'host_name_auto_complete', 'ajax.php', {minChars: 1});</script>\n";
		print "<hr>\n";
	}
}

function doCalendarIcon($type) {
	global $hostname;
	global $servicedesc;
	global $page;
	global $start;
	global $end;
	global $source;
	global $view;
	global $display;
	global $conf;
	global $timerange;
	global $source;
	if($conf['use_calendar'] == 1){
		print "<input value=\"".$timerange['end']."\" type=\"hidden\" name=\"end\" id=\"end\"/>\n";
		print "<img src=\"images/calendar.png\" HEIGHT=\"32px\" WIDTH=\"32px\" title=\"Open Calendar\" id=\"f_trigger_b\">\n";
		print "<script type=\"text/javascript\">\n";
		print "    Calendar.setup({\n";
		print "        inputField     :    \"end\",\n"; 
		print "        ifFormat       :    \"%s\",\n";
		print "        date           :    \"".date("Y/m/d",$timerange['end'])."\",\n";
		print "        showsTime      :    true,\n";
		print "        button         :    \"f_trigger_b\",\n";
		print "        singleClick    :    true,\n";
		print "        onClose        :    redirect,\n";
		print "        step           :    1,\n"; 
		print "        electric       :    false\n";
		print "    });\n";
		print "function redirect() {\n"; 
		print "	var end = document.getElementById(\"end\").value;\n"; 
                if($type == "SERVICE"){
                    print " window.location.href = \"index.php?host=$hostname&srv=$servicedesc&view=$view&end=\" + end;\n"; 
                }
                if($type == "HOST"){
                    print " window.location.href = \"index.php?host=$hostname&view=$view&end=\" + end;\n"; 
                }
                if($type == "HOST_OVERVIEW"){
                    print " window.location.href = \"index.php?host=$hostname&view=$view&end=\" + end;\n"; 
                }
                if($type == "PAGE"){
                    print " window.location.href = \"index.php?page=$page&view=$view&end=\" + end;\n"; 
                }

                print "}\n</script>\n";	
	}
	print "<hr>\n";
}

function doZoomIcon($hostname,$servicedesc,$start,$end,$source,$view){
	print "<a href=\"javascript:Gzoom('zoom.php?host=$hostname&srv=$servicedesc&display=image&view=$view&source=$source&end=$end&start=$start');\"><img src=\"images/zoom.png\" title=\"Zoom into the Graph\" ></a><br>\n";
}

function doZoomIconSpecial($special,$start,$end,$source,$view,$hostname){
	print "<a href=\"javascript:Gzoom('zoom.php?special=$special&display=image&view=$view&source=$source&end=$end&start=$start&host=$hostname');\"><img src=\"images/zoom.png\" title=\"Zoom into the Graph\" ></a><br>\n";
}

function doPDFIcon($type){
	global $hostname;
	global $servicedesc;
	global $special;
	global $start;
	global $end;
	global $source;
	global $view;
	global $display;
	global $page;

	switch($type){
		case "PAGE":
			print "<a href=\"index.php?page=$page&display=$display&view=$view&end=$end&start=$start&do=pdf\"><img src=\"images/pdf.png\" HEIGHT=\"32px\" WIDTH=\"32px\" title=\"Display PDF\" ></a>\n";
			break;
		case "SPECIAL":
			print "<a href=\"index.php?special=$special&display=$display&view=$view&end=$end&start=$start&do=pdf\"><img src=\"images/pdf.png\" HEIGHT=\"32px\" WIDTH=\"32px\" title=\"Display PDF\" ></a>\n";
			break;
		default:
			print "<a href=\"index.php?host=$hostname&srv=$servicedesc&display=$display&view=$view&source=$source&end=$end&start=$start&do=pdf\"><img src=\"images/pdf.png\" HEIGHT=\"32px\" WIDTH=\"32px\" title=\"Display PDF\" ></a>\n";
			break;
	}
}

function doXMLIcon(){
        global $conf;
	global $hostname;
	global $servicedesc;
        if($conf['show_xml_icon'] == 1){
            print "<a href=\"index.php?host=$hostname&srv=$servicedesc&display=xml\"><img src=\"images/xml.png\" HEIGHT=\"32px\" WIDTH=\"32px\" title=\"Display XML Definition\" ></a>\n";
        }
}

function doPageIcon(){
        $page = getFirstPage();
        if(isAuthorizedFor("pages") && $page!=""){
            print "<a href=\"index.php?page\"><img src=\"images/pages.png\" HEIGHT=\"32px\" WIDTH=\"32px\" title=\"GoTo Pages\" ></a>\n";
        }
}

function parse_xml($hostname,$servicedesc){
	global $conf;
	global $debug;
	$rrddef=$conf['rrdbase'].$hostname."/".$servicedesc.".xml";
        if(!is_readable($rrddef)){
            return;
        } 
        $data = "";
	$value = "";
	$level = "";
	$tag = "";
        $NAGIOS = array();
        $xml_parser = xml_parser_create();
        if(($handle = fopen($rrddef, "rb")) === false ){
		return $NAGIOS;
	}
        $contents = '';
        while (!feof($handle)) {
          $data .= fread($handle, 8192);
        }
        fclose($handle);

        if (!xml_parse_into_struct($xml_parser, $data, $vals, $index)) {
            $debug->doCheck("xml_err","XML error: ".xml_error_string(xml_get_error_code($xml_parser))." at line ".xml_get_current_line_number($xml_parser)." in ".$rrddef);
        }

        $dsl=0;
        foreach ($vals as $xml_elem) {
            $tag = $xml_elem['tag'];
            $level = $xml_elem['level'];
            $type = $xml_elem['type'];

            if($type == "open" && $tag == "DATASOURCE"){
                $last_tag = $tag;
                $dsl++;
            }

            if($type == "open" && $tag == "RRD"){
                $last_tag = $tag;
                $dsl = 1;
            }

            if($level == 3 &&  $type == "complete" && $last_tag == "DATASOURCE"){
                if(isset($xml_elem['value'])){
                    $value = $xml_elem['value'];
                }else{
                    $value = "";
                }
                $NAGIOS['PNP'][$tag][$dsl] = urldecode($value);
            }

            if($level == 3 &&  $type == "complete" && $last_tag == "RRD"){
                $value = $xml_elem['value'];
                $NAGIOS['RRD'][$tag][$dsl] = urldecode($value);
            }

            if($level == 2 && $type == "complete" && preg_match("/^NAGIOS_/i",$tag)){
                if(isset($xml_elem['value'])){
                    $value = $xml_elem['value'];
                }else{
                    $value = "";
                }
                $NAGIOS[$tag] = urldecode($value);
            }
        }
    return $NAGIOS;
}

function parse_page_cfg($page){
	global $conf;
	global $GRAPH;
	global $PAGE;
	global $debug;
	$page_cfg = $conf['page_dir'].$page.".cfg";
	if(is_readable($page_cfg)){
		$data = file($page_cfg);
	}else{
		$debug->doCheck("page_not_readable","");
		return;
	}
	$l = 0;
	$line = "";
	$tag = "";
	$inside=0;
	$PAGE="";
	$allowed_tags = array("page", "graph");
	foreach($data as $line){
		if(preg_match('/(^#|^;)/',$line)) {
			continue;
		}

		preg_match('/define\s+(\w+)\W+{/' ,$line, $tag);
		if(isset($tag[1]) && in_array($tag[1],$allowed_tags)){
			$inside = 1;
			$t = $tag[1];
			$l++; 
			continue;
		}
		if(preg_match('/\s?(\w+)\s+(.*$)/',$line, $key) && $inside == 1){
			$k=$key[1];
			$v=$key[2];
			if($t=='page'){	
				$PAGE[$k] = trim($v);
			}elseif($t=='graph'){
				$GRAPH[$l][$k] = trim($v);
			}
		}
		if(preg_match('/}/',$line)){
			$inside=0;
			$t = "";
			continue;
		}
	}
}


function doPage($display,$style,$data){
	global $conf;
	global $views;
	global $view;
	global $page;
	global $hostname;
	global $servicedesc;
	#global $NAGIOS;
	if($display=="page"){
		if($style=='pdf'){
			doPDF($display,$data);
		}
		doHead("Page: $page");
		doOpenPage();
		print "<h1>Page: ".$data[0]['page_name']." </h1>\n";
		foreach($data as $d){
			$host=$d['hostname'];
			$n_host=$d['n_hostname'];
			$srv=$d['servicedesc'];
			$n_srv=$d['n_servicedesc'];
			$source=$d['source'];
			$view_title=$d['view_title'];
			$view=$d['view'];
			$start=$d['start'];
			$end=$d['end'];
			#$NAGIOS = $d['NAGIOS'];
			if($d['source'] == 1){
				print "<p>Host: <a href=\"index.php?host=$host&view=$view\" class=\"int\" ";
				print "title=\"Jump to the Host Overview\">$host</a>\n";	
				print " Service: <a href=\"index.php?host=$host&srv=$srv&view=$view\" class=\"int\" ";
				print "title=\"Jump to the Service Details\">$srv</a></p>\n";	
				print "<p><strong> Datasource: </strong>".$d['ds_name']."</p>\n";	
			}
			print "<div class=\"mg\"><table><tr valign=\"top\" ><td><img src=\"index.php?host=$host&srv=$srv&source=$source&view=$view&end=$end&display=image\"></td>\n";
			print "<td>";
			doZoomIcon($host,$srv,$start,$end,$source,$view);
			print "<p>";
			doSummaryLink($n_host,$start,$end);
			print "</p><p>";
			doAvailLink($n_host,$n_srv,$start,$end);
			print "</p></td></tr></table></div>\n";
		}
		doNavigation("PAGE",$hostname,$servicedesc,$data);
		doClosePage();
	}elseif($display=="service"){
		if($style=='pdf'){
			doPDF($display,$data);
		}
		doHead("Service Overview $hostname / $servicedesc");
		doOpenPage();
		print "<h1>Service Overview </h1>\n";
		foreach($data as $d){
			$host=$d['hostname'];
			$srv=$d['servicedesc'];
			$n_host=$d['n_hostname'];
			$n_srv=$d['n_servicedesc'];
			$source=$d['source'];
			$view_title=$d['view_title'];
			$view=$d['view'];
			$start=$d['start'];
			$end=$d['end'];
			$f_start=$d['f_start'];
			$f_end=$d['f_end'];
			if( $source == 1){	
				print "<p>$view_title ( $f_start - $f_end )</p>\n";	
				if($d['multi_graph'] == 1){
					print " <strong> Datasource: </strong>".$d['ds_name']."</p>\n";	
				}
				print "<div class=\"mg\">\n";
				print " <table><tr valign=\"top\" ><td>\n";
				print "  <img src=\"index.php?host=$host&srv=$srv&source=$source&view=$view&end=$end&display=image\"></td>\n";
				print " <td>";
				doZoomIcon($host,$srv,$start,$end,$source,$view);
				print "<p>";
				doSummaryLink($n_host,$start,$end);
				print "</p><p>";
				doAvailLink($n_host,$n_srv,$start,$end);
				print "</p></td></tr>\n</table>\n</div>\n";
			}else{

				if($d['multi_graph'] == 1){
					print " <strong> Datasource: </strong>".$d['ds_name']."</p>\n";	
				}
				print "<div class=\"mg\">\n";
				print "<table><tr valign=\"top\"><td><img src=\"index.php?host=$host&srv=$srv&source=$source&view=$view&end=$end&display=image\"></td>\n";
				print "<td>\n";
				doZoomIcon($host,$srv,$start,$end,$source,$view);
				print "</p></td></tr></table></div>\n";
			}
		}
		doNavigation("SERVICE",$hostname,$servicedesc,$data);
		doClosePage();
	}elseif($display=="special"){
		if($style=='pdf'){
			doPDF($display,$data);
		}
		doHead("Special Template");
		doOpenPage();
		print "<h1>Special Template</h1>\n";
		foreach($data as $d){
                        $special=$d['special'];
			$host=$d['hostname'];
			$srv=$d['servicedesc'];
			$n_host=$d['n_hostname'];
			$n_srv=$d['n_servicedesc'];
			$source=$d['source'];
			$view_title=$d['view_title'];
			$view=$d['view'];
			$start=$d['start'];
			$end=$d['end'];
			$f_start=$d['f_start'];
			$f_end=$d['f_end'];
			if( $source == 1){	
				print "<p>$view_title ( $f_start - $f_end )</p>\n";	
				if($d['multi_graph'] == 1){
					print " <strong> Datasource: </strong>".$d['ds_name']."</p>\n";	
				}
				print "<div class=\"mg\">\n";
				print " <table><tr valign=\"top\" ><td>\n";
				print "  <img src=\"index.php?special=$special&source=$source&view=$view&end=$end&display=image&host=$n_hostname\"></td>\n";
				print " <td>";
				doZoomIconSpecial($special,$start,$end,$source,$view,$n_hostname);
				print "</td></tr>\n</table>\n</div>\n";
			}else{

				if($d['multi_graph'] == 1){
					print " <strong> Datasource: </strong>".$d['ds_name']."</p>\n";	
				}
				print "<div class=\"mg\">\n";
				print "<table><tr valign=\"top\"><td><img src=\"index.php?special=$special&source=$source&view=$view&end=$end&display=image&host=$n_hostname\"></td>\n";
				print "<td>\n";
				doZoomIconSpecial($special,$start,$end,$source,$view,$n_hostname);
				print "</p></td></tr></table></div>\n";
			}
		}
                doNavigation('SPECIAL',"","",$data);
		doClosePage();
	}elseif($display=="host_list"){
		if($style == 'pdf'){
			doPDF($display,$data);
		}
		doHead("Host Overview $hostname");
		doOpenPage();
		print "<h1>Host Overview</h1>\n";
		foreach($data as $d){
			$host=$d['hostname'];
			$srv=$d['servicedesc'];
			$n_host=$d['n_hostname'];
			$n_srv=$d['n_servicedesc'];
			$source=$d['source'];
			$start=$d['start'];
			$end=$d['end'];
			$f_start=$d['f_start'];
			$f_end=$d['f_end'];
			$view_title=$d['view_title'];
			$view=$d['view'];
			$NAGIOS = $d['NAGIOS'];
                        if( $source == 1 ){
			    print "<p>$view_title ( $f_start - $f_end )</p>\n";	
                            print "<p>Service: $n_srv</p>";
			    #print "<p>$view_title ( $f_start - $f_end )</p>\n";	
		            if($d['multi_graph'] == 1){
	            	        print "<strong> Datasource: </strong>".$d['ds_name']."</p>\n";	
                            }
                            print "<div class=\"mg\"><table><tr valign=\"top\" ><td>";
			    print "<a href=\"index.php?host=$host&srv=$srv&source=$source&view=$view\"><img src=\"index.php?host=$host&srv=$srv&source=$source&view=$view&end=$end&start=$start&display=image\" ";
			    print "title=\"Jump to $host - $srv \"></a></td>\n";
			    print "<td>";
			    doZoomIcon($host,$srv,$start,$end,$source,$view);
			    print "<p>";
			    doSummaryLink($n_host,$start,$end);
			    print "</p><p>";
			    doAvailLink($n_host,$n_srv,$start,$end);
			    print "</p></td></tr></table></div>\n";
                        }else{
		            if($d['multi_graph'] == 1){
	            	        print "<strong> Datasource: </strong>".$d['ds_name']."</p>\n";	
                            }
			    print "<div class=\"mg\"><table><tr valign=\"top\" ><td>";
			    print "<a href=\"index.php?host=$host&srv=$srv&source=$source&view=$view\"><img src=\"index.php?host=$host&srv=$srv&source=$source&view=$view&end=$end&start=$start&display=image\" ";
			    print "title=\"Jump to $host - $srv \"></a></td>\n";
			    print "<td>";
			    doZoomIcon($host,$srv,$start,$end,$source,$view);
			    print "</p></td></tr></table></div>\n";
                        }

		}
		doNavigation("HOST_OVERVIEW",$hostname,$servicedesc,$data);
		doClosePage();
	}else{
		doHead("NOOP");
		doOpenPage();
		print "Not implemented";
		doClosePage();
	}	
	
}

function doAvailLink($hostname,$servicedesc,$start,$end){
	global $conf;
	$smon = date('m' , $start);
	$sday = date('d' , $start);
	$syear = date('Y' , $start);
	$shour = date('G' , $start);
	$smin = date('i' , $start);
	$ssec = date('s' , $start);
	$emon = date('m' , $end);
	$eday = date('d' , $end);
	$eyear = date('Y' , $end);
	$ehour = date('G' , $end);
	$emin = date('i' , $end);
	$esec = date('s' , $end);
	$nagios_base = $conf['nagios_base']; 
	if($servicedesc == ""){
		print "<a href=\"$nagios_base/avail.cgi?show_log_entries=&host=$hostname&timeperiod=custom&smon=$smon&sday=$sday&syear=$syear&shour=$shour&smin=$smin&ssec=$ssec&emon=$emon&eday=$eday&eyear=$eyear&ehour=$ehour&emin=$emin&esec=$esec&rpttimeperiod=&assumeinitialstates=yes&assumestateretention=yes&assumestatesduringnotrunning=yes&includesoftstates=yes&initialassumedservicestate=6&backtrack=4\"";
	}else{
		print "<a href=\"$nagios_base/avail.cgi?show_log_entries=&host=$hostname&service=$servicedesc&timeperiod=custom&smon=$smon&sday=$sday&syear=$syear&shour=$shour&smin=$smin&ssec=$ssec&emon=$emon&eday=$eday&eyear=$eyear&ehour=$ehour&emin=$emin&esec=$esec&rpttimeperiod=&assumeinitialstates=yes&assumestateretention=yes&assumestatesduringnotrunning=yes&includesoftstates=yes&initialassumedservicestate=6&backtrack=4\"";

	}

	print " title=\"Nagios Availability Report for this Timerange\"><img src=\"images/trends.gif\" ></a>\n";
}

function doSummaryLink($hostname,$start,$end){
	global $conf;
	$smon = date('m' , $start);
	$sday = date('d' , $start);
	$syear = date('Y' , $start);
	$shour = date('G' , $start);
	$smin = date('i' , $start);
	$ssec = date('s' , $start);
	$emon = date('m' , $end);
	$eday = date('d' , $end);
	$eyear = date('Y' , $end);
	$ehour = date('G' , $end);
	$emin = date('i' , $end);
	$esec = date('s' , $end);
	$nagios_base = $conf['nagios_base']; 
	print "<a href=\"$nagios_base/summary.cgi?report=1&displaytype=1&timeperiod=custom&smon=$smon&sday=$sday&syear=$syear&shour=$shour&smin=$smin&ssec=$ssec&emon=$emon&eday=$eday&eyear=$eyear&ehour=$ehour&emin=$emin&esec=$esec&hostgroup=all&servicegroup=all&host=$hostname&alerttypes=3&statetypes=3&hoststates=7&servicestates=120&limit=999\"";
	print " title=\"Most Recent Alerts for this Timerange\"><img src=\"images/notify.gif\"></a>\n";
}

function doExportLink($hostname,$servicedesc,$start,$end){
    if($servicedesc == ""){
        $servicedesc = "_HOST_";
    }
    print "<a href=\"index.php?host=$hostname&srv=$servicedesc&start=$start&end=$end&display=export_data\" target=\"_blank\"";
    print " title=\"RRDTool XML Export\">&lt;/xml&gt;</a>\n";
}
?>
