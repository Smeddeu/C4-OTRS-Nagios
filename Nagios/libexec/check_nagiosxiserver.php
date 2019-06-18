<?php
//
// Copyright (c) 2008-2019 Nagios Enterprises, LLC. All rights reserved.
// Portions Copyright (c) others (see below)
//


// Authentication settings
$username = "";
$ticket = "";
$url = "";
$warn = "";
$crit = "";
$apikey = "";

// Default settings for the plugin
$timeout = 10;
$mode = "daemons";
$debug = 0;


check_server();


////////////////////////////////////////////////////////////////////////
// MAIN FUNCTIONS
////////////////////////////////////////////////////////////////////////

function check_server()
{
    global $username, $ticket, $url, $timeout, $mode, $warn, $crit, $apikey;
    global $debug;

    $components = array(
        "nagios" => "Monitoring Engine",
        "ndo2db" => "Database Backend",
        "npcd" => "Performance Grapher",
    );

    $jobs = array(
        "reportengine" => "Report Engine",
        "sysstat" => "System Statistics",
        "eventman" => "Event Manager",
        "feedprocessor" => "Feed Processor",
        "cmdsubsys" => "Command Subsystem",
        "nom" => "Nonstop Operations Manager",
        "dbmaint" => "Database Maintenance",
        "cleaner" => "Cleaner",
    );

    $returncode = 0;

    check_args();

    // Get XI URL
    if (!empty($apikey)) {

        $url_base = $url . "/api/v1/system/statusdetail";
        $xiurl = $url_base . "?apikey=" . $apikey;
        $method = 'GET';
        $type = 'json';

    } else {

        $url_base = $url . "/backend/";
        $query = "&username=" . $username . "&ticket=" . $ticket;
        $xiurl = $url_base . "?cmd=getsysstat" . $query;
        $method = 'POST';
        $type = 'xml';

    }

    // Replace double /
    $xiurl = str_replace('nagiosxi//', 'nagiosxi/', $xiurl);

    if ($debug) {
        echo "ACCESSING URL: $xiurl\n";
    }

    $opts = array(
        "method" => $method,
        "return_info" => true,
        "timeout" => $timeout,
    );

    $result = load_url($xiurl, $opts);

    if ($debug) {
        echo "RESULT:\n";
        print_r($result);
    }

    // Check for errors
    if (isset($result['error']) && $result['error'] == true) {
        echo "Error: Unable to contact XI server backend at " . $url . "\n";
        exit(2);
    }

    // Parse output
    $body = $result['body'];
    if ($type == 'xml') {
        $data = simplexml_load_string($body);
    } else if ($type == 'json') {
        $data = json_decode($body);
    }

    if (!$data) {
        echo "Error: Could not parse " . strtoupper($type) . " from " . $url . " (" . $body . ")\n";
        exit(2);
    }

    // Check for authentication, command errors
    if (!empty($data->error)) {
        if ($type == 'xml') {
            $msg = strval($data->error->errormsg);
        } else if ($type == 'json') {
            $msg = $data->error;
        }
        echo "Error: Received error from XI server: '" . $msg . "'\n";
        exit(2);
    }

    if ($debug) {
        echo "XML DATA LOOKS OK\n";
    }

    // Handle the actual check against the data
    switch ($mode) {

        case "daemons":

            $output = "";
            $total = 0;
            $daemonlist = "";

            foreach ($data->daemons->daemon as $d) {

                $total++;

                if ($debug)
                    echo "DAEMON: " . $d->name . "=" . $d->status . "\n";

                $name = strval($d->name);
                $status = intval($d->status);

                if ($daemonlist != "")
                    $daemonlist .= ", ";
                $daemonlist .= $name;

                // get a friendly name
                $fname = "";
                if (array_key_exists($name, $components))
                    $fname = $components[$name];

                // handle errors;
                if ($status != 0) {
                    if ($output != "")
                        $output .= ", ";
                    $output .= $name;
                    if ($fname != "")
                        $output .= " (" . $fname . ")";
                    $output .= " stopped";
                    // performance grapher gets a warning
                    if ($name == "npcd")
                        $returncode = 1;
                    // everything else gets a critical
                    else
                        $returncode = 2;
                }
            }

            // no daemons were found
            if ($total == 0) {
                $output = "No daemon information found.";
                $returncode = 3;
            } // no problems
            else if ($output == "") {
                //$output="All daemons (".$daemonlist.") are running okay.";
                $output = "All daemons are running okay.";
            }

            echo $output . "\n";;

            break;

        case "jobs":

            $output = "";
            $total = 0;
            $joblist = "";

            $now = time();

            foreach ($jobs as $jn => $jfn) {

                if ($debug)
                    echo "CHECKING JOB $jn ($jfn)\n";

                $lci = intval($data->$jn->last_check);

                // error
                if ($lci == 0) {
                    if ($output != "")
                        $output .= ", ";
                    $output .= $jfn . " (" . $jn . ") stale";
                } else {

                    $total++;

                    $diff = $now - $lci;

                    // defaults
                    $warn = 90;
                    $crit = 300;

                    // db maintenance has longer interval
                    $warn = 360;
                    $crit = 900;

                    // warning
                    if ($diff > $warn) {
                        $returncode = 1;
                        if ($output != "")
                            $output .= ", ";
                        $output .= $jfn . " (" . $jn . ") stale (" . $diff . " seconds old)";
                    }

                    // critical
                    if ($diff > $crit) {
                        $returncode = 2;
                        if ($output != "")
                            $output .= ", ";
                        $output .= $jfn . " (" . $jn . ") stale (" . $diff . " seconds old)";
                    }
                }
            }

            // no daemons were found
            if ($total == 0) {
                $output = "No job information found.";
                $returncode = 3;
            } // no problems
            else if ($output == "") {
                $output = "All jobs are running okay.";
            }

            echo $output . "\n";;

            break;

        case "iowait":

            $output = "";

            $iowait = floatval($data->iostat->iowait);

            $w = floatval($warn);
            $c = floatval($crit);

            if ($iowait >= $c) {
                $returncode = 2;
                $output = "Critical:";
            } else if ($iowait >= $w) {
                $returncode = 1;
                $output = "Warning:";
            } else
                $output = "Ok:";

            $output .= " I/O Wait = " . $iowait . "%|iowait=" . $iowait . "%;" . $w . ";" . $c . ";;\n";

            echo $output;

            break;

        case "load":

            $output = "";

            $load1 = floatval($data->load->load1);
            $load5 = floatval($data->load->load5);
            $load15 = floatval($data->load->load15);

            $wvals = explode(",", $warn);
            $cvals = explode(",", $crit);

            for ($x = 0; $x <= 2; $x++) {
                $w = floatval($wvals[$x]);
                $c = floatval($cvals[$x]);

                if ($x == 0) {
                    if ($load1 >= $c)
                        $returncode = 2;
                    else if ($load1 >= $w)
                        $returncode = 1;
                } else if ($x == 1) {
                    if ($load5 >= $c)
                        $returncode = 2;
                    else if ($load5 >= $w)
                        $returncode = 1;
                } else if ($x == 2) {
                    if ($load15 >= $c)
                        $returncode = 2;
                    else if ($load15 >= $w)
                        $returncode = 1;
                }
            }

            if ($returncode == 2)
                $output = "Load Critical:";
            else if ($returncode == 1)
                $output = "Load Warning:";
            else
                $output = "Load Ok:";

            $output .= " load1=" . $load1 . ", load5=" . $load5 . ", load15=" . $load15 . "|load1=" . $load1 . ";" . $wvals[0] . ";" . $cvals[0] . ";; load5=" . $load5 . ";" . $wvals[1] . ";" . $cvals[1] . ";; load15=" . $load15 . ";" . $wvals[2] . ";" . $cvals[2] . ";;\n";

            echo $output;

            break;

        default:
            echo "Error: Unknown mode '" . $mode . "' specified.\n";
            $returncode = 3;
            break;
    }


    // everything is okay, but we should never get here...
    exit($returncode);
}


////////////////////////////////////////////////////////////////////////
// ARG FUNCTIONS
////////////////////////////////////////////////////////////////////////


function print_usage()
{
    global $argc, $argv;
    global $timeout;
?>

check_nagiosxiserver - Copyright (c) 2010-2018 Nagios Enterprises, LLC.
Portions Copyright(c) others (see source code).

This plugin checks the status of a remote Nagios XI server.

Usage:
<?php echo $argv[0]; ?>
<option>

Options:

--url=<url>
    The URL used to access the Nagios XI web interface.

--apikey=<apikey>
    The API key for an administrator on the XI server you want to check.

--username=<username>
    (DEPRECATED) The username used for accessing the server.
    Not needed if you are using an apikey.

--ticket=<ticket>
    (DEPRECATED) The ticket used for accessing the server.
    Not needed if you are using an apikey.

--timeout=<seconds>
    Seconds before plugin times out. (default=<? echo $timeout; ?>)

--debug=<0/1>
    Enables/disables debugging output.

--mode=<mode>
    Operating mode of the plugin. Valid modes include:
        daemons     Checks the status of the core Nagios XI daemons to ensure
                    they're running properly.
        jobs        Checks the status of the core Nagios XI jobs to ensure
                    they're running properly.
        iowait      Checks the I/O wait CPU statistics.
        load        Checks the 1,5,15 minutes load statistics.

--warn=<warning>
    The warning values used for some modes. (iowait, load)

--crit=<critical>
    The critical values used for some modes. (iowait, load)

<?php
    exit(3);
}


function check_args()
{
    global $argc, $argv;
    global $username, $ticket, $url, $timeout, $mode, $warn, $crit, $apikey;
    global $debug;

    if ($argc < 2 || in_array($argv[1], array('--help', '-help', '-h', '-?'))) {
        print_usage();
    } else {
        $args = parse_args($argv);
        foreach ($args["options"] as $option) {
            switch ($option[0]) {

                case "username":
                    $username = $option[1];
                    break;

                case "ticket":
                    $ticket = $option[1];
                    break;

                case "apikey":
                    $apikey = $option[1];
                    break;

                case "url":
                    $url = $option[1];
                    break;

                case "warn":
                    $warn = $option[1];
                    break;

                case "crit":
                    $crit = $option[1];
                    break;

                case "timeout":
                    $timeout = intval($option[1]);
                    break;

                case "debug":
                    $debug = intval($option[1]);
                    break;

                case "mode":
                    $mode = $option[1];
                    break;

            }
        }
    }

    // Make sure we have prereqs
    if ($url == "") {
        echo "ERROR - Insufficient arguments specified.\n";
        print_usage();
    }
}


////////////////////////////////////////////////////////////////////////
// HELPER FUNCTIONS
////////////////////////////////////////////////////////////////////////


/**
 * Parse arguments (from anonymous and thomas harding)
 * See: http://us3.php.net/manual/en/features.commandline.php#86616
 *
 * @param   $args
 * @return  array
 */
function parse_args($args)
{
    $ret = array(
        'exec' => '',
        'options' => array(),
        'flags' => array(),
        'arguments' => array()
    );

    $ret['exec'] = array_shift($args);

    while (($arg = array_shift($args)) != NULL) {

        // Is it a option? (prefixed with --)
        if (substr($arg, 0, 2) === '--') {
            $option = substr($arg, 2);

            // Is it the syntax '--option=argument'?
            if (strpos($option, '=') !== FALSE) {
                array_push($ret['options'], explode('=', $option, 2));
            } else {
                array_push($ret['options'], $option);
            }

            continue;
        }

        // Is it a flag or a serial of flags? (prefixed with -)
        if (substr($arg, 0, 1) === '-') {
            for ($i = 1; isset($arg[$i]); $i++) {
                $ret['flags'][] = $arg[$i];
            }

            continue;
        }

        // Finally, it is not option, nor flag
        $ret['arguments'][] = $arg;
        continue;
    }

    return $ret;
}


/**
 * See http://www.bin-co.com/php/scripts/load/
 * Version : 1.00.A
 * License: BSD
 */
function load_url($url, $options = array('method' => 'get', 'return_info' => false))
{
    // Added 04-28-08 EG added a default timeout of 15 seconds
    if (!isset($options['timeout'])) {
        $options['timeout'] = 15;
    }

    $url_parts = parse_url($url);
    if (!array_key_exists('port', $url_parts)) {
        $url_parts['port'] = ($url_parts['scheme'] == "https") ? 443 : 80;
    }
    $info = array(
        'http_code' => 200
    );
    $response = '';

    $send_header = array(
        'Accept' => 'text/*',
        'User-Agent' => 'BinGet/1.00.A'
    );

    ///////////////////////////// Curl /////////////////////////////////////
    // If curl is available, use curl to get the data.
    if (function_exists("curl_init") and (!(isset($options['use']) and $options['use'] == 'fsocketopen'))) {

        // Don't user curl if it is specifically stated to user fsocketopen in the options
        if (isset($options['method']) and $options['method'] == 'post') {
            $page = $url_parts['scheme'] . '://' . $url_parts['host'] . ":" . $url_parts['port'] . $url_parts['path'];
        } else {
            $page = $url;
        }

        $ch = curl_init($url_parts['host'] . ":" . $url_parts['port']);

        // Added 04-28-08 EG set a timeout
        if (isset($options['timeout'])) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $options['timeout']);
        }

        curl_setopt($ch, CURLOPT_URL, $page);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //Just return the data - not print the whole thing.
        curl_setopt($ch, CURLOPT_HEADER, true); //We need the headers
        curl_setopt($ch, CURLOPT_NOBODY, false); //The content - if true, will not download the contents
        if (isset($options['method']) and $options['method'] == 'post' and $url_parts['query']) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $url_parts['query']);
        }

        // Set the headers our spiders sends
        curl_setopt($ch, CURLOPT_USERAGENT, $send_header['User-Agent']); // The Name of the UserAgent we will be using ;)
        $custom_headers = array("Accept: " . $send_header['Accept']);
        if (isset($options['modified_since'])) {
            array_push($custom_headers, "If-Modified-Since: " . gmdate('D, d M Y H:i:s \G\M\T', strtotime($options['modified_since'])));
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $custom_headers);

        curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt"); // If ever needed...
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);


        if (isset($url_parts['user']) and isset($url_parts['pass'])) {
            $custom_headers = array("Authorization: Basic " . base64_encode($url_parts['user'] . ':' . $url_parts['pass']));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $custom_headers);
        }

        $response = curl_exec($ch);
        $info = curl_getinfo($ch); // Some information on the fetch

        curl_close($ch);

    //////////////////////////////////////////// FSockOpen //////////////////////////////
    } else {

        // If there is no curl, use fsocketopen
        if (isset($url_parts['query'])) {
            if (isset($options['method']) and $options['method'] == 'post')
                $page = $url_parts['path'];
            else
                $page = $url_parts['path'] . '?' . $url_parts['query'];
        } else {
            $page = $url_parts['path'];
        }

        $fp = fsockopen($url_parts['host'], 80, $errno, $errstr, 30);
        if ($fp) {

            // Added 04-28-08 EG set a timeout
            if (isset($options['timeout']))
                stream_set_timeout($fp, $options['timeout']);

            $out = '';
            if (isset($options['method']) and $options['method'] == 'post' and isset($url_parts['query'])) {
                $out .= "POST $page HTTP/1.1\r\n";
            } else {
                $out .= "GET $page HTTP/1.0\r\n"; //HTTP/1.0 is much easier to handle than HTTP/1.1
            }
            $out .= "Host: $url_parts[host]\r\n";
            $out .= "Accept: $send_header[Accept]\r\n";
            $out .= "User-Agent: {$send_header['User-Agent']}\r\n";
            if (isset($options['modified_since']))
                $out .= "If-Modified-Since: " . gmdate('D, d M Y H:i:s \G\M\T', strtotime($options['modified_since'])) . "\r\n";

            $out .= "Connection: Close\r\n";

            // HTTP Basic Authorization support
            if (isset($url_parts['user']) and isset($url_parts['pass'])) {
                $out .= "Authorization: Basic " . base64_encode($url_parts['user'] . ':' . $url_parts['pass']) . "\r\n";
            }

            // If the request is post - pass the data in a special way.
            if (isset($options['method']) and $options['method'] == 'post' and $url_parts['query']) {
                $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
                $out .= 'Content-Length: ' . strlen($url_parts['query']) . "\r\n";
                $out .= "\r\n" . $url_parts['query'];
            }
            $out .= "\r\n";

            fwrite($fp, $out);
            while (!feof($fp)) {
                $response .= fgets($fp, 128);
            }
            fclose($fp);
        }
    }

    // Get the headers in an associative array
    $headers = array();

    if ($info['http_code'] == 404) {
        $body = "";
        $headers['Status'] = 404;
    } else {

        // Seperate header and content
        $separator_position = strpos($response, "\r\n\r\n");
        $header_text = substr($response, 0, $separator_position);

        $body = substr($response, $separator_position + 4);

        // Added 04-28-2008 EG if we get a 301 (moved), another set of headers is received,
        if (substr($body, 0, 5) == "HTTP/") {
            $separator_position = strpos($body, "\r\n\r\n");
            $header_text = substr($body, 0, $separator_position);
            $body = substr($body, $separator_position + 4);
        }

        foreach (explode("\n", $header_text) as $line) {
            $parts = explode(": ", $line);
            if (count($parts) == 2) $headers[$parts[0]] = chop($parts[1]);
        }
    }

    if ($options['return_info'])
        return array('headers' => $headers, 'body' => $body, 'info' => $info);
    return $body;
}
