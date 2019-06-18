<?php
##########################################################################
##     	                           NagVis                               ##
##               *** Klasse zum verarbeiten der Config ***              ##
##                               Lizenz GPL                             ##
##########################################################################

/**
* This Class handles the NagVis configuration file
*/
class SiteCfg {
	var $MAINCFG;
	var $conf;	
	var $name;
	var $image;
	var $SiteConfig;
	
	function readConfig() {
		global $conf;
		if($this->name != '') {
			#if($this->checkMapConfigReadable(1)) {
				$this->SiteConfig = Array();
				
				// read file in array
				$file = file($conf['sitebase'].$this->name.'.cfg');
				
				$type = array("global","host","service","site",'title');
				$createArray = array("allowed_user","allowed_for_config");
				$l = 0;
				$a = 0;
						
				while (isset($file[$l]) && $file[$l] != "") {
					if(!preg_match("/^#/",$file[$l]) && !preg_match("/^;/",$file[$l])) {
						$defineCln = explode("{", $file[$l]);
						$define = explode(" ",$defineCln[0]);
						if (isset($define[1]) && in_array(trim($define[1]),$type)) {
							$l++;
							$nrOfType = count($this->SiteConfig[$define[1]]);
							$this->SiteConfig[$define[1]][$nrOfType]['type'] = $define[1];
							while (trim($file[$l]) != "}") {
								$entry = explode("=",$file[$l], 2);
								
								if(isset($entry[1])) {
									if(in_array(trim($entry[0]),$createArray)) {
										$this->SiteConfig[$define[1]][$nrOfType][trim($entry[0])] = explode(",",str_replace(' ','',trim($entry[1])));
									} else {
										$this->SiteConfig[$define[1]][$nrOfType][trim($entry[0])] = trim($entry[1]);
									}
								}
								$l++;	
							}
						}
					}
					$l++;
				}
				
		} else {
			return FALSE;
		}
	return $this->SiteConfig;
	}
	
}
?>
