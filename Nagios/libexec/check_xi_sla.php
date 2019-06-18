#!/usr/bin/php
<?php
//
// Check SLA Plugin
// Copyright (c) 2016-2018 Nagios Enterprises, LLC. All rights reserved.
//

// Variables
define("PROGRAM", 'check_xi_sla.php');
define("VERSION", '2.0.0');
$debug = false;

// Constants
define("STATUS_OK", 0);
define("STATUS_WARNING",  1);
define("STATUS_CRITICAL", 2);
define("STATUS_UNKNOWN", 3);

// SLA definitions/defaults
define("SLA_DEFAULT_LEVEL", 95);
define("ADVANCED", 0);
define("ASSUMEINITIALSTATE", 'yes');
define("ASSUMESTATERET", 'yes');
define("ASSUMESTATEDURINGDOWN", 'yes');
define("SOFTSTATE", 'no');
define("ASSUMEDHOSTSTATE", 3);
define("ASSUMEDSERVICESTATE", 6);


do_check();


function do_check()
{
    $shortopts = "vh:u:s:g:e:a:w:c:r:A";
    $longopts = array("help", "version", "host:", "username:", "service:", "hostgroup:", "servicegroup:",
                      "warning:", "critical:", "advanced:", "reportperiod:", "average");
    $options = getopt($shortopts, $longopts);

    // Set username to get data with (defaults to nagiosadmin)
    $username = grab_array_var($options, 'username', grab_array_var($options, 'u', 'nagiosadmin'));

    // Return version
    if (isset($options['version']) || isset($options['v'])) {
        xi_sla_exit(VERSION, STATUS_OK);
    }

    // Return help
    if (isset($options['help'])) {
        xi_sla_show_help();
        xi_sla_exit('', STATUS_OK);
    }

    // Get data from options
    $host = grab_array_var($options, 'host', grab_array_var($options, 'h', ''));
    $service = grab_array_var($options, 'service', grab_array_var($options, 's', ''));
    $hostgroup = grab_array_var($options, 'hostgroup', grab_array_var($options, 'g', ''));
    $servicegroup = grab_array_var($options, 'servicegroup', grab_array_var($options, 'e', ''));
    $reportperiod = grab_array_var($options, 'reportperiod', grab_array_var($options, 'r', '24h'));
    $advanced = grab_array_var($options, 'advanced', grab_array_var($options, 'a', ''));
    $average = grab_array_var($options, 'average', grab_array_var($options, 'A', null));

    $args = array('host' => $host,
                  'service' => $service,
                  'hostgroup' => $hostgroup,
                  'servicegroup' => $servicegroup,
                  'starttime' => get_timestamp_from_reportperiod($reportperiod),
                  'average' => $average);

    // Parse advanced options
    if (!empty($advanced)) {
        $advanced = explode(',', $advanced);
        foreach ($advanced as $a) {
            list($o, $v) = explode('=', $a);
            $args[$o] = $v;
        }
    }

    $data = get_sla_data($args, $username);

    $warning = grab_array_var($options, 'warning', grab_array_var($options, 'w', ''));
    $critical = grab_array_var($options, 'critical', grab_array_var($options, 'c', ''));

    // Run plugin with the data
    check_sla($args, $warning, $critical, $data, $reportperiod);
}


/**
 * Get the timestamp string based on time given
 *
 * @param   string  $tp     Time such as 24h or 7d or 2M
 * @return  int             Timestamp
 */
function get_timestamp_from_reportperiod($tp)
{
    $tp = str_replace(' ', '', $tp);
    $unit = substr($tp, -1);
    $amount = substr($tp, 0, strlen($tp) - 1);

    if (!in_array($unit, array('m', 'h', 'd', 'M'))) {
        return 0;
    }

    switch ($unit) 
    {
        case 'm':
            $ts = strtotime("-$amount minutes");
            break;

        case 'h':
            $ts = strtotime("-$amount hours");
            break;

        case 'd':
            $ts = strtotime("-$amount days");
            break;
    
        case 'M':
            $ts = strtotime("-$amount months");
            break;
    }

    return $ts;
}


/**
 * @param   $message
 */
function debug_logging($message)
{
    global $debug;
    if ($debug) {
        echo $message;
    }
}


/**
 * @param   $error_message
 */
function plugin_error($error_message)
{
    if (is_array($error_message)) {
        print("UNKNOWN: ");
        foreach ($error_message as $err => $message) {
            print_r("\n" . $message . "\n");
        }
    } else {
        print("UNKNOWN: {$error_message}\n");
        xi_sla_exit('', STATUS_UNKNOWN);
    }
}


/**
 * @param string $stdout
 * @param int    $exitcode
 */
function xi_sla_exit($stdout = '', $exitcode = 0)
{
    print($stdout."\n");
    exit($exitcode);
}


function check_sla($args, $warning, $critical, $data, $reportperiod)
{
    $output = "";
    $errors = 0;
    $errmsg = array();

    if (empty($data)) {
        xi_sla_exit("Could not get SLA data. Make sure the username has access to the objects you are using.\n", STATUS_UNKNOWN);
    }

    // Parse output and exit
    if (!empty($args['service'])) {

        $sla = array($data['sla'][$args['host']][$args['service']]);
        $response = get_formatted_sla_output($sla, $args['host'] . " - " . $args['service']." last $reportperiod SLA is {$sla[0]}%", $warning, $critical);

    } else if (!empty($args['hostgroup'])) {

        $sla = array($data['sla']['hosts']['average'], $data['sla']['services']['average']);
        $response = get_formatted_sla_output($sla, "Hostgroup ".$args['hostgroup']." last $reportperiod SLA for host average is {$sla[0]}% and service average is {$sla[1]}%", $warning, $critical);

    } else if (!empty($args['servicegroup'])) {

        $sla = array($data['sla']['hosts']['average'], $data['sla']['services']['average']);
        $response = get_formatted_sla_output($sla, "Servicegroup ".$args['servicegroup']." last $reportperiod SLA for host average is {$sla[0]}% and service average is {$sla[1]}%", $warning, $critical);

    } else {

        $extra = "";
        if ($args['average'] !== null) {
            $sla = array($data['sla']['services']['average']);
            $extra = " service average";
        } else {
            $sla = array($data['sla']['hosts'][$args['host']]);
        }

        $response = get_formatted_sla_output($sla, "Host ".$args['host']."$extra last $reportperiod SLA is {$sla[0]}%", $warning, $critical);

    }

    // Response and output of the SLA plugin
    if ($response['error']) {
        plugin_error($response['msg']);
    }

    xi_sla_exit($response['output'], $response['result_code']);
}


function get_formatted_sla_output($sla, $text, $warning, $critical)
{
    if (!$sla) {
        $response['result_code'] = 3;
        $response['output'] = "UNKNOWN: Could not get data from Nagios XI Server.";
    }

    $result_code = 0;
    $result_prefix = "OK: ";
    
    try {
        if (!empty($warning)) {
            foreach ($sla as $s) {
                if (check_sla_threshold($warning, $s)) {
                    $result_code = 1;
                }
            }
        }
        if (!empty($critical)) {
            foreach ($sla as $s) {
                if (check_sla_threshold($critical, $s)) {
                    $result_code = 2;
                }
            }
        }
    } catch (Exception $e) {
        $response['error'] = true;
        $response['msg'] = $e->getMessage();
        return $response;
    }

    $response['error'] = false;
    $response['result_code'] = $result_code;
    if ($result_code == 1) {
        $result_prefix = "WARNING: ";
    } else if ($result_code == 2) {
        $result_prefix = "CRITICAL: ";
    }

    if (count($sla) > 1) {
        $response['output'] = $result_prefix . "$text";
        $response['output'] .= " | sla_host={$sla[0]}%;$warning;$critical; sla_service={$sla[1]}%;$warning;$critical;";
    } else {
        $response['output'] = $result_prefix . "$text | sla={$sla[0]}%;$warning;$critical";
    }

    return $response;
}


/**
 * Verify that the threshold type including range thresholds
 */
function check_sla_threshold($threshold, $value)
{
    $inside = ((substr($threshold, 0, 1) == '@') ? true : false);
    $range = str_replace('@','', $threshold);
    $parts = explode(':', $range);

    if (count($parts) > 1) {
        $start = $parts[0];
        $end = $parts[1];
    } else {
        $start = 0;
        $end = $range;
    }

    if (substr($start, 0, 1) == "~") {
        $start = -999999999;
    }
    if (!is_numeric($end)) {
        $end = 999999999;
    }
    if ($start > $end) {
        throw new Exception("In range threshold START:END, START must be less than or equal to END");
    }

    if ($inside > 0) {
        if ($start <= $value && $value <= $end) {
            return 1;
        }
    } else {
        if ($value < $start || $end < $value) {
            return 1;
        }
    }

    return 0;
}


function xi_sla_show_help()
{
    $advmask =  "        |%-42.42s | %-11.11s | %-55.55s |\n";
    print("check_xisla.php - v" . VERSION . "

This plugin checks Service Level Agreement (SLA) status on a Nagios XI server and monitors if it has been met by using target
and/ or threshold SLA.

Copyright (c) 2016-2018 Nagios Enterprises, LLC. All rights reserved.

Usage: " . PROGRAM . " [-h <hostname> -s <service> -g <hostgroup> -e <servicegroup>] 
       [-w <SLA % warning>] [-c <SLA % critical>] [-a '<advanced option 1>,<2..>']
    
Options:
    -u | --username
        Username to use to gather host/service data with. Defaults to nagiosadmin.
    -h | --host
        Target hostname.
    -s | --service
        Target service. Must include a target hostname.
    -g | --hostgroup
        Target Hostgroup.
    -e | --servicegroup
        Target Servicegroup.
    -r | --reportperiod
        The lookback period for the report. Defaults to '24h'.
        (Units: m = minutes, h = hours, d = days, M = months | 12h | 5d | 1M | 7d | must use only one unit)
    -w | --warning
        SLA percentage (%) to result in a Warning status.
        (Target - @95 [x <= 95] | Range - 85:95 [85 > x > 95] | Target Range - @85:95 [85 >= x >= 95])
    -c | --critical
        SLA percentage (%) to result in a Critical status. (See Warning)
    -A | --average
        Get the SLA for all servies on a host and average it.
    -a | --advanced
        Advanced SLA Report Options.  Comma seperated list of advanced SLA Report options:
        ---------------------------------------------------------------------------------------------------------------------\n");

    // SLA Advanced options table
    printf($advmask, "        Options (argument name)", "Default", "Value");
    print("        ---------------------------------------------------------------------------------------------------------------------\n");
    printf($advmask, "Assume Initial States (assumeinitialstates)", ASSUMEINITIALSTATE, "yes/no");
    printf($advmask, "Assume State Retention (assumestateretention)", ASSUMESTATERET, "yes/no");
    printf($advmask, "Assume States During Downtime (assumestatesduringdowntime)", ASSUMESTATEDURINGDOWN, "yes/no");
    printf($advmask, "Include Soft States (includesoftstates)", SOFTSTATE, "yes/no");
    printf($advmask, "First Assumed Host State (assumedhoststate)", ASSUMEDHOSTSTATE, "0 = Unspecified, -1 = Current State, ");
    printf($advmask, "", "", "3 = Host Up, 4 = Host Down, 5 = Host Unreachable");
    printf($advmask, "First Assumed Service State (assumedservicestate)", ASSUMEDSERVICESTATE, "0 = Unspecified, -1 = Current State, 6 = Service OK, ");
    printf($advmask, "", "", "7 = Service Warning, 8 = Service Unknown, 9 = Service");
    printf($advmask, "Time Period (timeperiod)", "", "Choose a time period from the Nagios XI server by name, ");
    printf($advmask, "", "", "must be a valid time period in Nagios Core definitions.");
    print("        ---------------------------------------------------------------------------------------------------------------------

        Example: -a 'assumeinitialstates=no,assumedhoststate=-1,timeperiod=24x7'
    --help
        Print this help and usage message\n\n");
}


// Gets value from array and give default
function grab_array_var($arr, $varname, $default = "")
{
    $v = $default;
    if (is_array($arr)) {
        if (array_key_exists($varname, $arr)) {
            $v = $arr[$varname];
        }
    }
    return $v;
}


// ==================================
// SLA Functions
// ==================================


/**
 * Get an array of SLA data (mostly used in API and check_xi_sla.php)
 *
 */
function get_sla_data($opts, $username)
{
    global $request;
    $data = array();

    $host = grab_array_var($opts, "host", "");
    $service = grab_array_var($opts, "service", "");
    $hostgroup = grab_array_var($opts, "hostgroup", "");
    $servicegroup = grab_array_var($opts, "servicegroup", "");

    $dont_count_downtime = grab_array_var($opts, "dont_count_downtime", 0);
    $dont_count_warning = grab_array_var($opts, "dont_count_warning", 0);
    $dont_count_unknown = grab_array_var($opts, "dont_count_unknown", 0);
    $show_options = grab_array_var($opts, "showopts", 0);

    $starttime = grab_array_var($opts, "starttime", time() - 60*60*24);
    $endtime = grab_array_var($opts, "endtime", time());

    // ADVANCED OPTIONS
    $timeperiod = grab_array_var($opts, "timeperiod", "");
    $assumeinitialstates = grab_array_var($opts, "assumeinitialstates", "yes");
    $assumestateretention = grab_array_var($opts, "assumestateretention", "yes");
    $assumestatesduringdowntime = grab_array_var($opts, "assumestatesduringdowntime", "yes");
    $includesoftstates = grab_array_var($opts, "includesoftstates", "no");
    $assumedhoststate = grab_array_var($opts, "assumedhoststate", 3);
    $assumedservicestate = grab_array_var($opts, "assumedservicestate", 6);

    // Validate passed data
    if (empty($host) && empty($service) && empty($hostgroup) && empty($servicegroup)) {
        return array('error' => _('You must enter a host, service, hostgroup, or servicegroup.'));
    }

    ///////////////////////////////////////////////////////////////////////////
    // SPECIFIC SERVICE
    ///////////////////////////////////////////////////////////////////////////
    if ($service != "" && $service != "average") {

        // Get service availability
        $args = array(
            "host" => $host,
            "service" => $service,
            "starttime" => $starttime,
            "endtime" => $endtime,
            "timeperiod" => $timeperiod,
            "assume_initial_states" => $assumeinitialstates,
            "assume_state_retention" => $assumestateretention,
            "assume_states_during_not_running" => $assumestatesduringdowntime,
            "include_soft_states" => $includesoftstates,
            "initial_assumed_host_state" => $assumedhoststate,
            "initial_assumed_service_state" => $assumedservicestate,
            'dont_count_downtime' => $dont_count_downtime,
            'dont_count_warning' => $dont_count_warning,
            'dont_count_unknown' => $dont_count_unknown
        );
        $servicedata = get_xml_availability('service', $args, $username);

        // Check if we have data
        $have_data = false;
        if ($servicedata && intval($servicedata->havedata) == 1)
            $have_data = true;

        if ($have_data == false) {
            print_r("Availability data is not available when monitoring engine is not running");
        } else {
            // We have data
            if ($servicedata) {

                $lasthost = "";
                foreach ($servicedata->serviceavailability->service as $s) {

                    $hn = strval($s->host_name);
                    $sd = strval($s->service_description);

                    if (!$dont_count_downtime) {
                        $ok = floatval($s->percent_known_time_ok);
                    } else {
                        $ok = (($ok = floatval($s->percent_known_time_ok) + floatval($s->percent_known_time_warning_scheduled) + floatval($s->percent_known_time_critical_scheduled) + floatval($s->percent_known_time_unkown_scheduled)) > 100 ? 100 : $ok);
                    }

                    $data['sla'][$hn][$sd] = number_format($ok, 3);
                }
            }
        }

        if ($show_options) {
            $data['options'] = $args;
        }
        return $data;
    }

    ///////////////////////////////////////////////////////////////////////////
    // SPECIFIC HOST
    ///////////////////////////////////////////////////////////////////////////
    else if ($host != "") {

        // Get host availability
        $args = array(
            "host" => $host,
            "starttime" => $starttime,
            "endtime" => $endtime,
            "timeperiod" => $timeperiod,
            "assume_initial_states" => $assumeinitialstates,
            "assume_state_retention" => $assumestateretention,
            "assume_states_during_not_running" => $assumestatesduringdowntime,
            "include_soft_states" => $includesoftstates,
            "initial_assumed_host_state" => $assumedhoststate,
            "initial_assumed_service_state" => $assumedservicestate,
            'dont_count_downtime' => $dont_count_downtime,
            'dont_count_warning' => $dont_count_warning,
            'dont_count_unknown' => $dont_count_unknown
        );
        $hostdata = get_xml_availability("host", $args, $username);

        // Get service availability
        $args = array(
            "host" => $host,
            "starttime" => $starttime,
            "endtime" => $endtime,
            "timeperiod" => $timeperiod,
            "assume_initial_states" => $assumeinitialstates,
            "assume_state_retention" => $assumestateretention,
            "assume_states_during_not_running" => $assumestatesduringdowntime,
            "include_soft_states" => $includesoftstates,
            "initial_assumed_host_state" => $assumedhoststate,
            "initial_assumed_service_state" => $assumedservicestate,
            'dont_count_downtime' => $dont_count_downtime,
            'dont_count_warning' => $dont_count_warning,
            'dont_count_unknown' => $dont_count_unknown
        );
        $servicedata = get_xml_availability("service", $args, $username);

        // Check if we have data
        $have_data = false;
        if ($hostdata && $servicedata && intval($hostdata->havedata) == 1 && intval($servicedata->havedata) == 1)
            $have_data = true;

        if ($have_data == false) {
            print_r("Availability data is not available when monitoring engine is not running");
        } else {

            // We have data let's do it!

            $avg_service_ok = 0;
            $avg_service_warning = 0;
            $avg_service_unknown = 0;
            $avg_service_critical = 0;
            $count_service_critical = 0;
            $count_service_warning = 0;
            $count_service_unknown = 0;

            if ($servicedata) {
                foreach ($servicedata->serviceavailability->service as $s) {
                    if (!$dont_count_downtime) {
                        $service_ok = floatval($s->percent_known_time_ok);
                        $service_warning = floatval($s->percent_known_time_warning);
                        $service_unknown = floatval($s->percent_known_time_unknown);
                        $service_critical = floatval($s->percent_known_time_critical);
                    } else {
                        $service_ok = (($ok = floatval($s->percent_known_time_ok) + floatval($s->percent_known_time_warning_scheduled) + floatval($s->percent_known_time_critical_scheduled) + floatval($s->percent_known_time_unkown_scheduled)) > 100 ? 100 : $ok);
                        $service_warning = floatval($s->percent_known_time_warning_unscheduled);
                        $service_unknown = floatval($s->percent_known_time_unkown_unscheduled);
                        $service_critical = floatval($s->percent_known_time_critical_unscheduled);
                    }

                    update_avail_avg($avg_service_ok, $service_ok, $count_service_ok);
                    update_avail_avg($avg_service_warning, $service_warning, $count_service_warning);
                    update_avail_avg($avg_service_unknown, $service_unknown, $count_service_unknown);
                    update_avail_avg($avg_service_critical, $service_critical, $count_service_critical);
                }
            }

            if ($hostdata) {
                foreach ($hostdata->hostavailability->host as $h) {

                    $hn = strval($h->host_name);

                    if (!$dont_count_downtime) {
                        $up = floatval($h->percent_known_time_up);
                    } else {
                        $up = floatval($h->percent_known_time_up) + floatval($h->percent_known_time_down_scheduled) + floatval($h->percent_known_time_unreachable_scheduled);
                    }

                    $data['sla']['hosts'][$hn] = number_format($up, 3);
                }
            }

            if ($servicedata) {
                foreach ($servicedata->serviceavailability->service as $s) {

                    $hn = strval($s->host_name);
                    $sd = strval($s->service_description);

                    if (!$dont_count_downtime) {
                        $ok = floatval($s->percent_known_time_ok);
                    } else {
                        $ok = (($ok = floatval($s->percent_known_time_ok) + floatval($s->percent_known_time_warning_scheduled) + floatval($s->percent_known_time_critical_scheduled) + floatval($s->percent_known_time_unkown_scheduled)) > 100 ? 100 : $ok);
                    }

                    // send average only
                    $data['sla']['services'][$sd] = number_format($ok, 3);
                }
            }

            $data['sla']['services']['average'] = number_format($avg_service_ok, 3);
        }

        if ($show_options) {
            $data['options'] = $args;
        }
        return $data;
    }

    ///////////////////////////////////////////////////////////////////////////
    // SPECIFIC HOSTGROUP OR SERVICEGROUP
    ///////////////////////////////////////////////////////////////////////////
    else if ($hostgroup != "" || $servicegroup != "") {

        // get host availability
        $args = array(
            "host" => "",
            "starttime" => $starttime,
            "endtime" => $endtime,
            "timeperiod" => $timeperiod,
            "assume_initial_states" => $assumeinitialstates,
            "assume_state_retention" => $assumestateretention,
            "assume_states_during_not_running" => $assumestatesduringdowntime,
            "include_soft_states" => $includesoftstates,
            "initial_assumed_host_state" => $assumedhoststate,
            "initial_assumed_service_state" => $assumedservicestate,
            'dont_count_downtime' => $dont_count_downtime,
            'dont_count_warning' => $dont_count_warning,
            'dont_count_unknown' => $dont_count_unknown
        );
        if ($hostgroup != "")
            $args["hostgroup"] = $hostgroup;
        else
            $args["servicegroup"] = $servicegroup;
        $hostdata = get_xml_availability("host", $args, $username);

        // get service availability
        $args = array(
            "host" => "",
            "starttime" => $starttime,
            "endtime" => $endtime,
            "timeperiod" => $timeperiod,
            "assume_initial_states" => $assumeinitialstates,
            "assume_state_retention" => $assumestateretention,
            "assume_states_during_not_running" => $assumestatesduringdowntime,
            "include_soft_states" => $includesoftstates,
            "initial_assumed_host_state" => $assumedhoststate,
            "initial_assumed_service_state" => $assumedservicestate,
            'dont_count_downtime' => $dont_count_downtime,
            'dont_count_warning' => $dont_count_warning,
            'dont_count_unknown' => $dont_count_unknown
        );
        if ($hostgroup != "")
            $args["hostgroup"] = $hostgroup;
        else
            $args["servicegroup"] = $servicegroup;
        $servicedata = get_xml_availability("service", $args, $username);

        // check if we have data
        $have_data = false;
        if ($hostdata && $servicedata && intval($hostdata->havedata) == 1 && intval($servicedata->havedata) == 1)
            $have_data = true;
        if ($have_data == false) {
            print_r("Availability data is not available when monitoring engine is not running");
        } // we have data..
        else {
            $avg_host_up = 0;
            $avg_host_down = 0;
            $avg_host_unreachable = 0;
            $count_host_up = 0;
            $count_host_down = 0;
            $count_host_unreachable = 0;

            if ($hostdata) {
                foreach ($hostdata->hostavailability->host as $h) {
                    if (!$dont_count_downtime) {
                        $host_up = floatval($h->percent_known_time_up);
                        $host_down = floatval($h->percent_known_time_down);
                        $host_unreachable = floatval($h->percent_known_time_unreachable);
                    } else {
                        $host_up = (($ok = floatval($h->percent_known_time_up) + floatval($h->percent_known_time_down_scheduled) + floatval($h->percent_known_time_unreachable_scheduled)) > 100 ? 100 : $ok);
                        $host_down = floatval($h->percent_known_time_down_unscheduled);
                        $host_unreachable = floatval($h->percent_known_time_unreachable_unscheduled);
                    }

                    update_avail_avg($avg_host_up, $host_up, $count_host_up);
                    update_avail_avg($avg_host_down, $host_down, $count_host_down);
                    update_avail_avg($avg_host_unreachable, $host_unreachable, $count_host_unreachable);
                }
            }

            $avg_service_ok = 0;
            $avg_service_warning = 0;
            $avg_service_unknown = 0;
            $avg_service_critical = 0;
            $count_service_critical = 0;
            $count_service_warning = 0;
            $count_service_unknown = 0;

            if ($servicedata) {
                foreach ($servicedata->serviceavailability->service as $s) {
                    if (!$dont_count_downtime) {
                        $service_ok = floatval($s->percent_known_time_ok);
                        $service_warning = floatval($s->percent_known_time_warning);
                        $service_unknown = floatval($s->percent_known_time_unknown);
                        $service_critical = floatval($s->percent_known_time_critical);
                    } else {
                        $service_ok = (($ok = floatval($s->percent_known_time_ok) + floatval($s->percent_known_time_warning_scheduled) + floatval($s->percent_known_time_critical_scheduled) + floatval($s->percent_known_time_unkown_scheduled)) > 100 ? 100 : $ok);
                        $service_warning = floatval($s->percent_known_time_warning_unscheduled);
                        $service_unknown = floatval($s->percent_known_time_unkown_unscheduled);
                        $service_critical = floatval($s->percent_known_time_critical_unscheduled);
                    }

                    update_avail_avg($avg_service_ok, $service_ok, $count_service_ok);
                    update_avail_avg($avg_service_warning, $service_warning, $count_service_warning);
                    update_avail_avg($avg_service_unknown, $service_unknown, $count_service_unknown);
                    update_avail_avg($avg_service_critical, $service_critical, $count_service_critical);
                }
            }

            // host table
            if ($hostdata) {

                foreach ($hostdata->hostavailability->host as $h) {

                    $hn = strval($h->host_name);

                    if (!$dont_count_downtime) {
                        $up = floatval($h->percent_known_time_up);
                    } else {
                        $up = floatval($h->percent_known_time_up) + floatval($h->percent_known_time_down_scheduled) + floatval($h->percent_known_time_unreachable_scheduled);
                    }
                }

                // send average only
                $data['sla']['hosts']['average'] = number_format($avg_host_up, 3);
            }

            // service table
            if ($servicedata) {
                foreach ($servicedata->serviceavailability->service as $s) {

                    $hn = strval($s->host_name);
                    $sd = strval($s->service_description);

                    if (!$dont_count_downtime) {
                        $ok = floatval($s->percent_known_time_ok);
                    } else {
                        $ok = (($ok = floatval($s->percent_known_time_ok) + floatval($s->percent_known_time_warning_scheduled) + floatval($s->percent_known_time_critical_scheduled) + floatval($s->percent_known_time_unkown_scheduled)) > 100 ? 100 : $ok);
                    }
                }

                // send average only
                $data['sla']['services']['average'] = number_format($avg_service_ok, 3);
            }
        }

        if ($show_options) {
            $data['options'] = $args;
        }
        return $data;
    }
}


/**
 * @param $apct
 * @param $npct
 * @param $cnt
 */
function update_avail_avg(&$apct, $npct, &$cnt)
{
    $newpct = (($apct * $cnt) + $npct) / ($cnt + 1);
    $cnt++;
    $apct = $newpct;
}


/**
 * @param   string  $type   Type of object (host or service)
 * @param   array   $args   Array of arguments to pass
 * @return  object          A SimpleXMLElement object
 */
function get_xml_availability($type = "host", $args, $username)
{
    $x = simplexml_load_string(get_parsed_nagioscore_csv_availability_xml_output($type, $args, $username));
    return $x;
}


////////////////////////////////////////////////////////////////////////////////
// AVAILABILITY FUNCTIONS
////////////////////////////////////////////////////////////////////////////////


/**
 * @param   string  $type
 * @param   array   $args
 * @return  string
 */
function get_parsed_nagioscore_csv_availability_xml_output($type = "host", $args, $username)
{
    $havedata = false;
    $hostdata = array();
    $servicedata = array();

    $host = grab_array_var($args, "host", "");
    $hostgroup = grab_array_var($args, "hostgroup", "");
    $servicegroup = grab_array_var($args, "servicegroup", "");
    $service = grab_array_var($args, "service", "");

    // Special "all" stuff
    if ($hostgroup == "all")
        $hostgroup = "";
    if ($servicegroup == "all")
        $servicegroup = "";
    if ($host == "all")
        $host = "";
    if ($service == "all")
        $service = "";

    // Hostgroup members
    $hostgroup_members = array();
    if (!empty($hostgroup)) {
        $hostgroup_members = nagioscore_json_get_objects_in_hostgroup($username, $hostgroup);
    }

    // Servicegroup members
    $servicegroup_members = array();
    if (!empty($servicegroup)) {
        $servicegroup_members = nagioscore_json_get_objects_in_servicegroup($username, $servicegroup);
    }

    // Get the data
    $d = get_raw_nagioscore_csv_availability($type, $args, $username);

    // Explode by lines
    $lines = explode("\n", $d);
    $x = 0;
    foreach ($lines as $line) {
        $x++;

        // Make sure we have expected data in first line, otherwise bail
        if ($x == 1) {
            $pos = strpos($line, "HOST_NAME,");
            // Doesn't look like proper CSV output - Nagios Core may not be running
            if ($pos === FALSE) {
                $havedata = false;
                break;
            } else {
                $havedata = true;
            }
        } else {
            $cols = explode(",", $line);

            // Trim whitespace from data
            foreach ($cols as $i => $c) {
                trim($cols[$i]);
            }

            $parts = count($cols);

            // Make sure we have good data
            if ($type == "host") {

                if ($parts != 34) {
                    continue;
                }

                $hn = str_replace("\"", "", $cols[0]);
                $hn = trim($hn);

                // Filter by host name
                if ($host != "" && ($host != $hn)) {
                    continue;
                }

                // Filter by hostgroup
                if ($hostgroup != "" && !in_array($hn, $hostgroup_members)) {
                    continue;
                }

                // Filter by servicegroup
                if ($servicegroup != "" && !array_key_exists($hn, $servicegroup_members)) {
                    continue;
                }

                $hostdata[] = array(

                    "host_name" => $hn,

                    "time_up_scheduled" => $cols[1],
                    "percent_time_up_scheduled" => floatval($cols[2]),
                    "percent_known_time_up_scheduled" => floatval($cols[3]),
                    "time_up_unscheduled" => $cols[4],
                    "percent_time_up_unscheduled" => floatval($cols[5]),
                    "percent_known_time_up_unscheduled" => floatval($cols[6]),
                    "total_time_up" => $cols[7],
                    "percent_total_time_up" => floatval($cols[8]),
                    "percent_known_time_up" => floatval($cols[9]),

                    "time_down_scheduled" => $cols[10],
                    "percent_time_down_scheduled" => floatval($cols[11]),
                    "percent_known_time_down_scheduled" => floatval($cols[12]),
                    "time_down_unscheduled" => $cols[13],
                    "percent_time_down_unscheduled" => floatval($cols[14]),
                    "percent_known_time_down_unscheduled" => floatval($cols[15]),
                    "total_time_down" => $cols[16],
                    "percent_total_time_down" => floatval($cols[17]),
                    "percent_known_time_down" => floatval($cols[18]),

                    "time_unreachable_scheduled" => $cols[19],
                    "percent_time_unreachable_scheduled" => floatval($cols[20]),
                    "percent_known_time_unreachable_scheduled" => floatval($cols[21]),
                    "time_unreachable_unscheduled" => $cols[22],
                    "percent_time_unreachable_unscheduled" => floatval($cols[23]),
                    "percent_known_time_unreachable_unscheduled" => floatval($cols[24]),
                    "total_time_unreachable" => $cols[25],
                    "percent_total_time_unreachable" => floatval($cols[26]),
                    "percent_known_time_unreachable" => floatval($cols[27]),

                    "time_undetermined_not_running" => $cols[28],
                    "percent_time_undetermined_not_running" => floatval($cols[29]),
                    "time_undetermined_no_data" => $cols[30],
                    "percent_time_undetermined_no_data" => floatval($cols[31]),
                    "total_time_undetermined" => $cols[32],
                    "percent_total_time_undetermined" => floatval($cols[33]),
                );
            }

//          HOST_NAME, TIME_UP_SCHEDULED, PERCENT_TIME_UP_SCHEDULED, PERCENT_KNOWN_TIME_UP_SCHEDULED, TIME_UP_UNSCHEDULED, PERCENT_TIME_UP_UNSCHEDULED, PERCENT_KNOWN_TIME_UP_UNSCHEDULED, TOTAL_TIME_UP, PERCENT_TOTAL_TIME_UP, PERCENT_KNOWN_TIME_UP, TIME_DOWN_SCHEDULED, PERCENT_TIME_DOWN_SCHEDULED, PERCENT_KNOWN_TIME_DOWN_SCHEDULED, TIME_DOWN_UNSCHEDULED, PERCENT_TIME_DOWN_UNSCHEDULED, PERCENT_KNOWN_TIME_DOWN_UNSCHEDULED, TOTAL_TIME_DOWN, PERCENT_TOTAL_TIME_DOWN, PERCENT_KNOWN_TIME_DOWN, TIME_UNREACHABLE_SCHEDULED, PERCENT_TIME_UNREACHABLE_SCHEDULED, PERCENT_KNOWN_TIME_UNREACHABLE_SCHEDULED, TIME_UNREACHABLE_UNSCHEDULED, PERCENT_TIME_UNREACHABLE_UNSCHEDULED, PERCENT_KNOWN_TIME_UNREACHABLE_UNSCHEDULED, TOTAL_TIME_UNREACHABLE, PERCENT_TOTAL_TIME_UNREACHABLE, PERCENT_KNOWN_TIME_UNREACHABLE, TIME_UNDETERMINED_NOT_RUNNING, PERCENT_TIME_UNDETERMINED_NOT_RUNNING, TIME_UNDETERMINED_NO_DATA, PERCENT_TIME_UNDETERMINED_NO_DATA, TOTAL_TIME_UNDETERMINED, PERCENT_TOTAL_TIME_UNDETERMINED

            // Service
            else {

                if ($parts != 44)
                    continue;

                $hn = str_replace("\"", "", $cols[0]);
                $sn = str_replace("\"", "", $cols[1]);
                $hn = trim($hn);
                $sn = trim($sn);

                // Filter by host name
                if ($host != "" && ($host != $hn)) {
                    continue;
                }

                // Filter by hostgroup
                if ($hostgroup != "" && (!in_array($hn, $hostgroup_members))) {
                    continue;
                }

                // Fiiter by service
                if ($service != "" && ($service != $sn)) {
                    continue;
                }

                // Filter by servicegroup
                $sga = array($hn, $sn);
                if ($servicegroup != "" && (!in_array($sga, $servicegroup_members))) {
                    continue;
                }

                $servicedata[] = array(

                    "host_name" => $hn,
                    "service_description" => $sn,

                    "time_ok_scheduled" => $cols[2],
                    "percent_time_ok_scheduled" => floatval($cols[3]),
                    "percent_known_time_ok_scheduled" => floatval($cols[4]),
                    "time_ok_unscheduled" => $cols[5],
                    "percent_time_ok_unscheduled" => floatval($cols[6]),
                    "percent_known_time_ok_unscheduled" => floatval($cols[7]),
                    "total_time_ok" => $cols[8],
                    "percent_total_time_ok" => floatval($cols[9]),
                    "percent_known_time_ok" => floatval($cols[10]),

                    "time_warning_scheduled" => $cols[11],
                    "percent_time_warning_scheduled" => floatval($cols[12]),
                    "percent_known_time_warning_scheduled" => floatval($cols[13]),
                    "time_warning_unscheduled" => $cols[14],
                    "percent_time_warning_unscheduled" => floatval($cols[15]),
                    "percent_known_time_warning_unscheduled" => floatval($cols[16]),
                    "total_time_warning" => $cols[17],
                    "percent_total_time_warning" => floatval($cols[18]),
                    "percent_known_time_warning" => floatval($cols[19]),

                    "time_unknown_scheduled" => $cols[20],
                    "percent_time_unknown_scheduled" => floatval($cols[21]),
                    "percent_known_time_unknown_scheduled" => floatval($cols[22]),
                    "time_unknown_unscheduled" => $cols[23],
                    "percent_time_unknown_unscheduled" => floatval($cols[24]),
                    "percent_known_time_unknown_unscheduled" => floatval($cols[25]),
                    "total_time_unknown" => $cols[26],
                    "percent_total_time_unknown" => floatval($cols[27]),
                    "percent_known_time_unknown" => floatval($cols[28]),

                    "time_critical_scheduled" => $cols[29],
                    "percent_time_critical_scheduled" => floatval($cols[30]),
                    "percent_known_time_critical_scheduled" => floatval($cols[31]),
                    "time_critical_unscheduled" => $cols[32],
                    "percent_time_critical_unscheduled" => floatval($cols[33]),
                    "percent_known_time_critical_unscheduled" => floatval($cols[34]),
                    "total_time_critical" => $cols[35],
                    "percent_total_time_critical" => floatval($cols[36]),
                    "percent_known_time_critical" => floatval($cols[37]),

                    "time_undetermined_not_running" => $cols[38],
                    "percent_time_undetermined_not_running" => floatval($cols[39]),
                    "time_undetermined_no_data" => $cols[40],
                    "percent_time_undetermined_no_data" => floatval($cols[41]),
                    "total_time_undetermined" => $cols[42],
                    "percent_total_time_undetermined" => floatval($cols[43]),
                );
            }
        }
    }

    $output = "";
    $output .= "<availability>\n";
    $output .= "<havedata>";
    if ($havedata == true) {
        $output .= "1";
    } else {
        $output .= "0";
    }
    $output .= "</havedata>\n";
    $output .= "<" . $type . "availability>\n";
    if ($type == "host") {
        foreach ($hostdata as $hd) {
            $output .= "   <host>\n";

            $output .= "   <host_name>" . xmlentities($hd["host_name"]) . "</host_name>\n";

            $output .= "   <time_up_scheduled>" . xmlentities($hd["time_up_scheduled"]) . "</time_up_scheduled>\n";
            $output .= "   <percent_time_up_scheduled>" . xmlentities($hd["percent_time_up_scheduled"]) . "</percent_time_up_scheduled>\n";
            $output .= "   <percent_known_time_up_scheduled>" . xmlentities($hd["percent_known_time_up_scheduled"]) . "</percent_known_time_up_scheduled>\n";
            $output .= "   <time_up_unscheduled>" . xmlentities($hd["time_up_unscheduled"]) . "</time_up_unscheduled>\n";
            $output .= "   <percent_time_up_unscheduled>" . xmlentities($hd["percent_time_up_unscheduled"]) . "</percent_time_up_unscheduled>\n";
            $output .= "   <percent_known_time_up_unscheduled>" . xmlentities($hd["percent_known_time_up_unscheduled"]) . "</percent_known_time_up_unscheduled>\n";
            $output .= "   <total_time_up>" . xmlentities($hd["total_time_up"]) . "</total_time_up>\n";
            $output .= "   <percent_total_time_up>" . xmlentities($hd["percent_total_time_up"]) . "</percent_total_time_up>\n";
            $output .= "   <percent_known_time_up>" . xmlentities($hd["percent_known_time_up"]) . "</percent_known_time_up>\n";

            $output .= "   <time_down_scheduled>" . xmlentities($hd["time_down_scheduled"]) . "</time_down_scheduled>\n";
            $output .= "   <percent_time_down_scheduled>" . xmlentities($hd["percent_time_down_scheduled"]) . "</percent_time_down_scheduled>\n";
            $output .= "   <percent_known_time_down_scheduled>" . xmlentities($hd["percent_known_time_down_scheduled"]) . "</percent_known_time_down_scheduled>\n";
            $output .= "   <time_down_unscheduled>" . xmlentities($hd["time_down_unscheduled"]) . "</time_down_unscheduled>\n";
            $output .= "   <percent_time_down_unscheduled>" . xmlentities($hd["percent_time_down_unscheduled"]) . "</percent_time_down_unscheduled>\n";
            $output .= "   <percent_known_time_down_unscheduled>" . xmlentities($hd["percent_known_time_down_unscheduled"]) . "</percent_known_time_down_unscheduled>\n";
            $output .= "   <total_time_down>" . xmlentities($hd["total_time_down"]) . "</total_time_down>\n";
            $output .= "   <percent_total_time_down>" . xmlentities($hd["percent_total_time_down"]) . "</percent_total_time_down>\n";
            $output .= "   <percent_known_time_down>" . xmlentities($hd["percent_known_time_down"]) . "</percent_known_time_down>\n";

            $output .= "   <time_unreachable_scheduled>" . xmlentities($hd["time_unreachable_scheduled"]) . "</time_unreachable_scheduled>\n";
            $output .= "   <percent_time_unreachable_scheduled>" . xmlentities($hd["percent_time_unreachable_scheduled"]) . "</percent_time_unreachable_scheduled>\n";
            $output .= "   <percent_known_time_unreachable_scheduled>" . xmlentities($hd["percent_known_time_unreachable_scheduled"]) . "</percent_known_time_unreachable_scheduled>\n";
            $output .= "   <time_unreachable_unscheduled>" . xmlentities($hd["time_unreachable_unscheduled"]) . "</time_unreachable_unscheduled>\n";
            $output .= "   <percent_time_unreachable_unscheduled>" . xmlentities($hd["percent_time_unreachable_unscheduled"]) . "</percent_time_unreachable_unscheduled>\n";
            $output .= "   <percent_known_time_unreachable_unscheduled>" . xmlentities($hd["percent_known_time_unreachable_unscheduled"]) . "</percent_known_time_unreachable_unscheduled>\n";
            $output .= "   <total_time_unreachable>" . xmlentities($hd["total_time_unreachable"]) . "</total_time_unreachable>\n";
            $output .= "   <percent_total_time_unreachable>" . xmlentities($hd["percent_total_time_unreachable"]) . "</percent_total_time_unreachable>\n";
            $output .= "   <percent_known_time_unreachable>" . xmlentities($hd["percent_known_time_unreachable"]) . "</percent_known_time_unreachable>\n";

            $output .= "   </host>\n";
        }
    } else {
        foreach ($servicedata as $sd) {
            $output .= "   <service>\n";

            $output .= "   <host_name>" . xmlentities($sd["host_name"]) . "</host_name>\n";
            $output .= "   <service_description>" . xmlentities($sd["service_description"]) . "</service_description>\n";

            $output .= "   <time_ok_scheduled>" . xmlentities($sd["time_ok_scheduled"]) . "</time_ok_scheduled>\n";
            $output .= "   <percent_time_ok_scheduled>" . xmlentities($sd["percent_time_ok_scheduled"]) . "</percent_time_ok_scheduled>\n";
            $output .= "   <percent_known_time_ok_scheduled>" . xmlentities($sd["percent_known_time_ok_scheduled"]) . "</percent_known_time_ok_scheduled>\n";
            $output .= "   <time_ok_unscheduled>" . xmlentities($sd["time_ok_unscheduled"]) . "</time_ok_unscheduled>\n";
            $output .= "   <percent_time_ok_unscheduled>" . xmlentities($sd["percent_time_ok_unscheduled"]) . "</percent_time_ok_unscheduled>\n";
            $output .= "   <percent_known_time_ok_unscheduled>" . xmlentities($sd["percent_known_time_ok_unscheduled"]) . "</percent_known_time_ok_unscheduled>\n";
            $output .= "   <total_time_ok>" . xmlentities($sd["total_time_ok"]) . "</total_time_ok>\n";
            $output .= "   <percent_total_time_ok>" . xmlentities($sd["percent_total_time_ok"]) . "</percent_total_time_ok>\n";
            $output .= "   <percent_known_time_ok>" . xmlentities($sd["percent_known_time_ok"]) . "</percent_known_time_ok>\n";

            $output .= "   <time_warning_scheduled>" . xmlentities($sd["time_warning_scheduled"]) . "</time_warning_scheduled>\n";
            $output .= "   <percent_time_warning_scheduled>" . xmlentities($sd["percent_time_warning_scheduled"]) . "</percent_time_warning_scheduled>\n";
            $output .= "   <percent_known_time_warning_scheduled>" . xmlentities($sd["percent_known_time_warning_scheduled"]) . "</percent_known_time_warning_scheduled>\n";
            $output .= "   <time_warning_unscheduled>" . xmlentities($sd["time_warning_unscheduled"]) . "</time_warning_unscheduled>\n";
            $output .= "   <percent_time_warning_unscheduled>" . xmlentities($sd["percent_time_warning_unscheduled"]) . "</percent_time_warning_unscheduled>\n";
            $output .= "   <percent_known_time_warning_unscheduled>" . xmlentities($sd["percent_known_time_warning_unscheduled"]) . "</percent_known_time_warning_unscheduled>\n";
            $output .= "   <total_time_warning>" . xmlentities($sd["total_time_warning"]) . "</total_time_warning>\n";
            $output .= "   <percent_total_time_warning>" . xmlentities($sd["percent_total_time_warning"]) . "</percent_total_time_warning>\n";
            $output .= "   <percent_known_time_warning>" . xmlentities($sd["percent_known_time_warning"]) . "</percent_known_time_warning>\n";

            $output .= "   <time_critical_scheduled>" . xmlentities($sd["time_critical_scheduled"]) . "</time_critical_scheduled>\n";
            $output .= "   <percent_time_critical_scheduled>" . xmlentities($sd["percent_time_critical_scheduled"]) . "</percent_time_critical_scheduled>\n";
            $output .= "   <percent_known_time_critical_scheduled>" . xmlentities($sd["percent_known_time_critical_scheduled"]) . "</percent_known_time_critical_scheduled>\n";
            $output .= "   <time_critical_unscheduled>" . xmlentities($sd["time_critical_unscheduled"]) . "</time_critical_unscheduled>\n";
            $output .= "   <percent_time_critical_unscheduled>" . xmlentities($sd["percent_time_critical_unscheduled"]) . "</percent_time_critical_unscheduled>\n";
            $output .= "   <percent_known_time_critical_unscheduled>" . xmlentities($sd["percent_known_time_critical_unscheduled"]) . "</percent_known_time_critical_unscheduled>\n";
            $output .= "   <total_time_critical>" . xmlentities($sd["total_time_critical"]) . "</total_time_critical>\n";
            $output .= "   <percent_total_time_critical>" . xmlentities($sd["percent_total_time_critical"]) . "</percent_total_time_critical>\n";
            $output .= "   <percent_known_time_critical>" . xmlentities($sd["percent_known_time_critical"]) . "</percent_known_time_critical>\n";

            $output .= "   <time_unknown_scheduled>" . xmlentities($sd["time_unknown_scheduled"]) . "</time_unknown_scheduled>\n";
            $output .= "   <percent_time_unknown_scheduled>" . xmlentities($sd["percent_time_unknown_scheduled"]) . "</percent_time_unknown_scheduled>\n";
            $output .= "   <percent_known_time_unknown_scheduled>" . xmlentities($sd["percent_known_time_unknown_scheduled"]) . "</percent_known_time_unknown_scheduled>\n";
            $output .= "   <time_unknown_unscheduled>" . xmlentities($sd["time_unknown_unscheduled"]) . "</time_unknown_unscheduled>\n";
            $output .= "   <percent_time_unknown_unscheduled>" . xmlentities($sd["percent_time_unknown_unscheduled"]) . "</percent_time_unknown_unscheduled>\n";
            $output .= "   <percent_known_time_unknown_unscheduled>" . xmlentities($sd["percent_known_time_unknown_unscheduled"]) . "</percent_known_time_unknown_unscheduled>\n";
            $output .= "   <total_time_unknown>" . xmlentities($sd["total_time_unknown"]) . "</total_time_unknown>\n";
            $output .= "   <percent_total_time_unknown>" . xmlentities($sd["percent_total_time_unknown"]) . "</percent_total_time_unknown>\n";
            $output .= "   <percent_known_time_unknown>" . xmlentities($sd["percent_known_time_unknown"]) . "</percent_known_time_unknown>\n";

            $output .= "   </service>\n";
        }
    }
    $output .= "</" . $type . "availability>\n";
    $output .= "</availability>\n";

    return $output;
}


/**
 * Like htmlentities but for XML
 * (XML Entity Mandatory Escape Characters code copied from http://www.php.net/htmlentities)
 *
 * @param   string  $string     The string you want to escape
 * @return  string              XML-escaped string
 */
function xmlentities($string)
{
    $length = strlen($string);
    $clean_output = '';

    // If it's less than 4k then don't bother looping
    if ($length < 4000) {
        $clean_output = do_xml_replace($string);
    } else {

        // Loop through 4k chunks (substr doesnt care if length > string)
        $start = 0;
        $len = 4000;
        while ($start < $length) {
            $tmp = substr($string, $start, $len);
            $clean_output .= do_xml_replace($tmp);
            $start += $len;
        }

    }

    return $clean_output;
}


/**
 * Does the actual XML escaping... do not pass this more than 4k characters at a time
 * for some reason Apache can crash if you do...
 */
function do_xml_replace($string)
{
    $data = str_replace(array('&', '"', "'", '<', '>'), array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;'), $string);
    preg_match_all('/([\x09\x0a\x0d\x20-\x7e]' . // ASCII characters
        '|[\xc2-\xdf][\x80-\xbf]' . // 2-byte (except overly longs)
        '|\xe0[\xa0-\xbf][\x80-\xbf]' . // 3 byte (except overly longs)
        '|[\xe1-\xec\xee\xef][\x80-\xbf]{2}' . // 3 byte (except overly longs)
        '|\xed[\x80-\x9f][\x80-\xbf])+/', // 3 byte (except UTF-16 surrogates)
        $data, $clean_pieces);
    $clean_output = join('?', $clean_pieces[0]);
    return $clean_output;
}


/**
 * Get the raw CSV availability data for a host with arguments
 *
 * @param   string  $type   Type of object (host or service)
 * @param   array   $args   Array of arguments to pass
 * @return  string
 */
function get_raw_nagioscore_csv_availability($type = "host", $args, $username)
{
    global $cfg;
    global $request;

    // Get args
    $assume_initial_states = grab_array_var($args, "assume_initial_states", "yes");
    $assume_state_retention = grab_array_var($args, "assume_state_retention", "yes");
    $assume_states_during_not_running = grab_array_var($args, "assume_states_during_not_running", "yes");
    $include_soft_states = grab_array_var($args, "include_soft_states", "no");
    $initial_assumed_host_state = grab_array_var($args, "initial_assumed_host_state", 3);
    $initial_assumed_service_state = grab_array_var($args, "initial_assumed_service_state", 6);
    $backtrack = grab_array_var($args, "backtrack", 4);
    $timeperiod = grab_array_var($args, "timeperiod", "");

    $starttime = grab_array_var($args, "starttime", time() - (60 * 60 * 24));
    $endtime = grab_array_var($args, "endtime", time());

    // Query string
    $query_string = "show_log_entries=&" . $type . "=all";
    $query_string .= "&t1=" . $starttime . "&t2=" . $endtime;

    $qs2 = "&assumeinitialstates=" . $assume_initial_states . "&assumestateretention=" . $assume_state_retention . "&assumestatesduringnotrunning=" . $assume_states_during_not_running . "&includesoftstates=" . $include_soft_states . "&initialassumedhoststate=" . $initial_assumed_host_state . "&initialassumedservicestate=" . $initial_assumed_service_state . "&backtrack=" . $backtrack . "&csvoutput=&rpttimeperiod=" . $timeperiod;

    $query_string .= $qs2;

    // See if cached data exists
    $fname = "avail-" . $type . "-" . $starttime . "-" . $endtime . "-" . md5($qs2) . ".dat";
    $fdir = "/usr/local/nagiosxi/var/components/";

    // Use cached data if it exists
    if (file_exists($fdir . $fname)) {
        $output = file_get_contents($fdir . $fname);
    } else {
        putenv("REQUEST_METHOD=GET");
        putenv("REMOTE_USER=" . $username);
        putenv("QUERY_STRING=" . $query_string);

        $binpath = "/usr/local/nagios/sbin/avail.cgi";

        $rawoutput = "";
        $fp = popen($binpath, "r");
        while (!feof($fp)) {
            $rawoutput .= fread($fp, 1024);
        }
        pclose($fp);

        // Separate HTTP headers from content
        $a = strpos($rawoutput, "Content-type:");
        $pos = strpos($rawoutput, "\r\n\r\n", $a);
        if ($pos === false) {
            $pos = strpos($rawoutput, "\n\n", $a);
        }

        $output = substr($rawoutput, $pos + 4);
    }

    return $output;
}


function nagioscore_json_get_objects_in_hostgroup($username, $hostgroup)
{
    $query_string = "query=hostgroup&hostgroup=".$hostgroup;

    putenv("REQUEST_METHOD=GET");
    putenv("REMOTE_USER=" . $username);
    putenv("QUERY_STRING=" . $query_string);

    $binpath = "/usr/local/nagios/sbin/objectjson.cgi";

    $rawoutput = "";
    $fp = popen($binpath, "r");
    while (!feof($fp)) {
        $rawoutput .= fread($fp, 1024);
    }
    pclose($fp);

    // Separate HTTP headers from content
    $a = strpos($rawoutput, "Content-type:");
    $pos = strpos($rawoutput, "\r\n\r\n", $a);
    if ($pos === false) {
        $pos = strpos($rawoutput, "\n\n", $a);
    }

    $json = substr($rawoutput, $pos + 4);
    if (!empty($json)) {
        $output = json_decode($json);
    }

    $members = array();
    if (!empty($output->data->hostgroup->members)) {
        $members = $output->data->hostgroup->members;
    }

    return $members;
}


function nagioscore_json_get_objects_in_servicegroup($username, $servicegroup)
{
    $query_string = "query=servicegroup&servicegroup=".$servicegroup;

    putenv("REQUEST_METHOD=GET");
    putenv("REMOTE_USER=" . $username);
    putenv("QUERY_STRING=" . $query_string);

    $binpath = "/usr/local/nagios/sbin/objectjson.cgi";

    $rawoutput = "";
    $fp = popen($binpath, "r");
    while (!feof($fp)) {
        $rawoutput .= fread($fp, 1024);
    }
    pclose($fp);

    // Separate HTTP headers from content
    $a = strpos($rawoutput, "Content-type:");
    $pos = strpos($rawoutput, "\r\n\r\n", $a);
    if ($pos === false) {
        $pos = strpos($rawoutput, "\n\n", $a);
    }

    $json = substr($rawoutput, $pos + 4);
    if (!empty($json)) {
        $output = json_decode($json);
    }

    $members = array();
    if (!empty($output->data->servicegroup->members)) {
        foreach ($output->data->servicegroup->members as $m) {
            $members[$m->host_name] = $m->service_description;
        }
    }

    return $members;
}