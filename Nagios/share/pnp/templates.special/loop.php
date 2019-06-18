<?php
#
# Sample special Template
# FIND AND LOOP

# call tpl_getdata() with 3 keys and store the result in $_list
# key 1 -> the hostname to search for by regex
# key 2 -> the service description to search for. In this case an empty string to find every service
# key 3 -> the name of the check_command also specified by regex
$_list = tpl_getdata("db","","check_ora_redo_io");

# print the contents of array $_list while developing.
# you will find every value stored in the PNP XML Files
#$debug->doCheck('print_r',$_list);

# initialize $opt and $def
$opt[1] = "--title=\"Loop\" ";
$def[1] = "";
# some helper vars
$_count = 0;
$_colors = array("#ff0000","#00ffff","#0000ff");

# Loop through $_list 
foreach($_list as $_item){

    $_h = $_item['HOSTNAME'];
    $_s = $_item['SERVICEDESC'];
    
    $def[1] .= "DEF:var$_count=";
        $def[1] .= $_item['RRDFILE'].":1:AVERAGE ";
    $def[1] .= "LINE1:var";
        $def[1] .= $_count.$_colors[$_count];
        $def[1] .= ":\"$_h $_s\\n\" " ;

    $_count++;
}

# Dump $def before sendingthe data to rrdtool
# $debug->doCheck('var_dump',$def);
?>
