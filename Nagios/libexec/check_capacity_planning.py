#!/usr/bin/env python

# Copyright (c) 2018 Nagios Enterprises, LLC.

import sys # exit()
import os
# Don't eliminate symbolic links: 
relative_capacityplanning = os.path.dirname(__file__) + '/../../nagiosxi/html/includes/components/capacityplanning/backend/'
if not relative_capacityplanning in sys.path:
    sys.path.insert(0, relative_capacityplanning)
import optparse 
import capacityplanning
# from os.path import expanduser # Handles the conflict between ~ as -inf and ~ as root directory
# import subprocess
# import urllib # Just for urlencode()
import json
# import shlex
import signal

import StringIO
import re
from math import ceil
from time import time
from operator import lt

#could be made to inherit from enum, but not really necessary.
class check_status():
    OK = 0
    WARNING = 1
    CRITICAL = 2
    UNKNOWN = 3

# Strings from capacityplanning.py
class methods():
    HOLT_WINTERS = 'Holt-Winters'
    LINEAR = 'Linear Fit'
    QUADRATIC = 'Quadratic Fit'
    CUBIC = 'Cubic Fit'

class forecast_value_mapping():
    WARNING = 0
    CRITICAL = 1
    CUSTOM_GT = 2
    CUSTOM_LT = 3

# Globals
__PLUGIN_NAME__ = "check_capacity_planning.py"
__VERSION__ = "1.0.0"
timeout_status = check_status.UNKNOWN
options = None


# Dictionary of valid check options mapped to their handler function names, ctrl+f for "valid_checks"

# Add program options here. Defaults are already populated.
def get_options():
    version = '%s, Version %s. See --help for more info.' % (__PLUGIN_NAME__, __VERSION__)
    global options
    usage = "%prog: alert based on the time until a perfdata value is expected to become CRITICAL.\nusage: %prog -H <host-name> -d <perdata-name>[,<perfdata-name>...] --use-warning|--use-critical|--min <min>|--max <max> [options...]"
    parser = optparse.OptionParser(usage=usage)

    # Stored strings
    parser.add_option("-H", "--host-name", default="", 
        help="The name of the host which you're monitoring (incompatible with hostgroup/servicegroup)")
    parser.add_option("-S", "--service-description", default="_HOST_",
        help="The name of the service which you're monitoring (requires host-name, incompatible with hostgroup/servicegroup)")
    parser.add_option("-t", "--timeout", default="0", 
        help="Set the timeout duration in seconds. Defaults to never timing out.")
    parser.add_option('-w', '--warning', default='',
        help="How far in advance of the predicted threshold to start returning WARNING. "
        "Valid units are d, w, m, y (for days, weeks, months, and years respectively). "
        "If left empty, plugin does not alert WARNING.")
    parser.add_option('-c', '--critical', default='',
        help="How far in advance of the predicted threshold to start returning CRITICAL. "
        "Valid units are d, w, m, y (for days, weeks, months, and years respectively). "
        "If left empty, plugin does not alert CRITICAL.")
    parser.add_option('-m', '--method', default='h',
        help="The extrapolation method used for prediction. Should be one of 'holt-winters', 'linear', 'quadratic', 'cubic' (only the first character is checked). Defaults to holt-winters")
    parser.add_option('-d', '--data-source', default="",
        help="The perfdata name for which you are planning")
    parser.add_option('-l', '--lookahead', default="8w",
        help="How far in advance to look ahead when calculating predicted values. Defaults to 8w (8 weeks).")

    # Booleans
    parser.add_option("-v", "--verbose", action="store_true", default=False, 
        help="Print more verbose error messages.")
    parser.add_option("-V", "--version", action="store_true", default=False, 
        help="Print the version number and exit.")

    parser.add_option("--debug", action="store_true", default=False,
        help="Prints additional text in place of service output.")

    parser.add_option('--warning-uses-critical', action="store_true", default=False,
        help="If this flag is set, this plugin will alert WARNING based on the forecast against the performance data's CRITICAL value")
    parser.add_option('--warning-uses-custom', action="store_true", default=False,
        help="If this is set to a number, this plugin will alert WARNING based on the forecast against a custom value."
            "Requires that --custom-value is set.")
    parser.add_option('--warning-is-minimal', action="store_true", default=False,
        help="If this flag is set, this plugin will alert based on when forecasted data goes below the desired value.")

    parser.add_option('--critical-uses-warning', action="store_true", default=False,
        help="If this flag is set, this plugin will alert CRITICAL based on the forecast against the performance data's WARNING value")
    parser.add_option('--critical-uses-custom', action="store_true", default=False,
        help="If this is set to a number, this plugin will alert CRITICAL based on the forecast against a custom value."
            "Requires that --custom-value is set.")
    parser.add_option('--critical-is-minimal', action="store_true", default=False,
        help="If this flag is set, this plugin will alert based on when forecasted data goes below the desired value.")

    parser.add_option('--custom-value', default='',
        help="The value to use with --warning-uses-custom, --critical-uses-custom.")


    # Help is implemented by default by optparse
    options, _ = parser.parse_args()

    # Argument/option checking occurs here.
    if options.version or len(sys.argv) == 1:
        nagios_exit(version, check_status.OK)

    if not options.host_name:
        nagios_exit("Please specify a host with -H", 3)

    if not options.service_description:
        nagios_exit("Please specify a service description. Use '_HOST_' if the performance data is for a host check", 3)

    if not options.data_source:
        nagios_exit("Please specify the perfdata metrics to monitor with -d")

    method = options.method[0].lower()
    if method == 'h':
        options.method = methods.HOLT_WINTERS
    elif method == 'l':
        options.method = methods.LINEAR
    elif method == 'q':
        options.method = methods.QUADRATIC
    else:
        options.method = methods.CUBIC

    if not options.warning:
        options.warning = '1d'
    if not options.critical:
        options.critical = '1d'
    options.warning = time_scale(options.warning, 'd')
    options.critical = time_scale(options.critical, 'd')
    options.lookahead = time_scale(options.lookahead, 'd')

    options.thresholds = ''
    add_warn = False
    add_crit = False
    add_lt = False
    add_gt = False

    if options.warning_uses_critical:
        add_crit = True
        options.warning_checks = forecast_value_mapping.CRITICAL
    elif options.warning_uses_custom:
        if options.warning_is_minimal:
            add_lt = True
            options.warning_checks = forecast_value_mapping.CUSTOM_LT
        else:
            add_gt = True
            options.warning_checks = forecast_value_mapping.CUSTOM_GT
    else:
        add_warn = True
        options.warning_checks = forecast_value_mapping.WARNING

    if options.critical_uses_warning:
        add_warn = True
        options.critical_checks = forecast_value_mapping.WARNING
    elif options.critical_uses_custom:
        if options.critical_is_minimal:
            add_lt = True
            options.critical_checks = forecast_value_mapping.CUSTOM_LT
        else:
            add_gt = True
            options.critical_checks = forecast_value_mapping.CUSTOM_GT
    else:
        add_crit = True
        options.critical_checks = forecast_value_mapping.CRITICAL

    if add_warn:
        options.thresholds += '--warn '
    if add_crit:
        options.thresholds += '--crit '
    if add_lt:
        options.thresholds += '--lt ' + options.custom_value
    if add_gt:
        options.thresholds += '--gt ' + options.custom_value

    options.thresholds = options.thresholds.split(' ')

    if not options.thresholds:
        nagios_exit("Please specify one of --use-warning, --use-critical, --min, or --max", 3)

    # in weeks,
    options.period = 1
    # minimum 1 week
    options.steps = int(ceil(max(options.warning, options.critical, options.lookahead) / 7.0))

    set_timeout(int(options.timeout))

    return options

# This should get called in options processing only!
# timeout should default to zero

def timeout_handler(signal, frame):
    global timeout_status
    nagios_exit("check timed out", timeout_status)

def set_timeout(seconds):
    signal.signal(signal.SIGALRM, timeout_handler)
    signal.alarm(seconds)

time_scaling_units = {
    'd': 1,
    'w': 7,
    'm': 30,
    'y': 365
}

# Convert a string with scalar and unit to a different unit
# e.g. time_scale('3w', 'd') => 21
def time_scale(original_string, unit):
    global time_scaling_units
    original_unit = re.sub(r'[^a-zA-Z]', '', original_string)
    original_scalar = float(re.sub(r'[^0-9\.]', '', original_string))
    return original_scalar * time_scaling_units[original_unit] / time_scaling_units[unit]

# Takes the message to print to stdout and the exit code,
# prints, then exits.
def nagios_exit(out, code=3, perfdata=None, verbose_out=None):
    if options.debug: print "hit " + "nagios_exit"
    if verbose_out is None:
        verbose_out = ""
    if perfdata is None:
        perfdata = ""
    prefix = code_to_status(code)
    out = prefix + ": " + out
    if options.verbose:
        out += " " + verbose_out + "\n"
    out += perfdata
    print out
    exit(code)

def code_to_status(return_code):
    if return_code < check_status.OK or return_code > check_status.UNKNOWN:
        return_code = check_status.UNKNOWN
    words = ["OK", "WARNING", "CRITICAL", "UNKNOWN"]
    return words[return_code]

####################################################################################################################################
#                                                                                                                                  #
# Start domain-specific code                                                                                                       #
#                                                                                                                                  #
####################################################################################################################################


# Note: Since capacity planning believes it's being called from the command line and prints directly to stdout,
# we need to hijack sys.argv and sys.stdout. I still think this is better than just using Popen, since this only uses one python process,
# and it's not that different in complexity
def call_capacity_planning(options):

    old_argv = sys.argv
    old_stdout = sys.stdout

    # These are the common opts that will be passed to capacityplanning.py.
    new_argv = ['capacityplanning.py', 
        '-H', str(options.host_name), 
        '-S', str(options.service_description), 
        '-M', str(options.method),
        '-P', str(options.period),
        '-s', str(options.steps),
        '-T', str(options.data_source),
        '--no-highcharts']

    new_argv.extend(options.thresholds)

    if (options.warning_is_minimal and not options.warning_uses_custom) or\
       (options.critical_is_minimal and not options.critical_uses_custom):
        new_argv.extend('--lt-threshold')

    # Hijack stdout. getvalue() to feed into a string, close() to free
    sys.argv = new_argv
    sys.stdout = StringIO.StringIO()

    try:
        capacityplanning.main()
    except Exception as e:
        sys.stdout = old_stdout
        nagios_exit('Error in capacity planning backend: %s' % e, 3)

    json_output = sys.stdout.getvalue()
    sys.stdout.close()

    # Restore old values
    sys.stdout = old_stdout
    sys.argv = old_argv

    # Convert json to usable data
    try:
        json_output = json.loads(json_output)
    except Exception as e:
        nagios_exit('Got output from capacity planning backend: %s' % e, 3)

    # Get all four threshold lists
    warning = { 'time': float('Inf'), 'value': 0.00 }
    if 'warn' in json_output:
        new_warn = json_output['warn']
        if new_warn['time'] > 0:
            warning = new_warn

    critical = { 'time': float('Inf'), 'value': 0.00 }
    if 'crit' in json_output:
        new_crit = json_output['crit']
        if new_crit['time'] > 0:
            critical = new_crit

    # --gt/--lt accepts/returns a list of inputs, but we're just using one item.
    maximum = { 'time': float('Inf'), 'value': 0.00 }
    if 'gt' in json_output:
        new_max = json_output['gt'][0]
        if new_max['time'] > 0:
            maximum = new_max

    minimum = { 'time': float('Inf'), 'value': 0.00 }
    if 'lt' in json_output:
        new_min = json_output['lt'][0]
        if new_min['time'] > 0:
            minimum = new_min

    final_data = (options.data_source, warning, critical, maximum, minimum)
    return final_data

# Tuples are of the form (threshold_value, crossing_times_index), 
# where crossing_times_index is a member of the forecast_value_mapping enum.
def process_values(values, warning_tuple, critical_tuple):

    epoch = time()

    warning, warning_checks = warning_tuple
    critical, critical_checks = critical_tuple
    crossing_times = values[1:]

    warning_element = crossing_times[warning_checks]
    critical_element = crossing_times[critical_checks]
    
    warning_value = round(warning_element['value'], 2)
    warning_time_days = round((warning_element['time'] - epoch) / 86400.0, 2)
    critical_value = round(critical_element['value'], 2)
    critical_time_days = round((critical_element['time'] - epoch) / 86400.0, 2)

    rc = check_status.OK
    ret_str = "%s will reach value %s in %s days, %s in %s days" % (
        values[0], warning_value, warning_time_days, 
        critical_value, critical_time_days)

    if warning_time_days < warning:
        rc = check_status.WARNING
        ret_str = "%s will reach value %s in %s days" % (values[0], warning_value, warning_time_days)
    if critical_time_days < critical:
        rc = check_status.CRITICAL
        ret_str = "%s will reach value %s in %s days" % (values[0], critical_value, critical_time_days)

    if warning_time_days == float('Inf') and critical_time_days == float('Inf'):
        ret_str = "%s will not cross either threshold in lookahead period" % values[0]

    return (ret_str, rc, '')

def main():
    try:
        options = get_options()
        values = call_capacity_planning(options)
        out, exit, perfdata = process_values(values, (options.warning, options.warning_checks), (options.critical, options.critical_checks))
        nagios_exit(out, exit, perfdata)
    except Exception as e:
        nagios_exit("%s" % e, check_status.UNKNOWN)

if __name__ == "__main__":
    _ = main()
    nagios_exit("Plugin escaped the main function. Please report this error to the plugin maintainer.", check_status.UNKNOWN)
    sys.exit(check_status.UNKNOWN)
