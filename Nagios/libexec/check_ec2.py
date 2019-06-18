#!/usr/bin/env python

import os
import sys
import optparse
import boto3 #Import the SDK
import uuid
import json
import collections
import signal
import time
import botocore
from pprint import pprint
from datetime import datetime
from datetime import timedelta
from os.path import expanduser


__VERSION__ = '1.0.0'


#====================================
#
#        Initialize Variables
#
#====================================

# Strings
return_units = ''
userdata = ''
perfdata = ''
metric_name = ''

# Arrays
statistics = []
warning = []
critical = []
alerts = []

# Dictionaries
aws_response = {}

# Numbers
low = 0
high = float('inf')
highest_return_code = 0

# Booleans
inclusive = False


#================================
#
#     Get Options Function
#
#================================

def parse_args():

    global options

    version = 'check_ec2.py, Version %s' %__VERSION__

    parser = optparse.OptionParser()

    parser.add_option("-w", "--warning", default='', help="Warning threshold value to be passed for the check. For multiple warning thresholds, enter a comma separated list.")
    parser.add_option("-c", "--critical", default='', help="Critical threshold value to be passed for the check. For multiple critical thresholds, enter a comma separated list.")
    parser.add_option("-V", "--version", action='store_true', help="Display the current version of check_ec2")
    parser.add_option("-v", "--verbose", action='store_true', help="Display more information for troubleshooting.")
    parser.add_option("-S", "--statistics", default="Average,Minimum,Maximum,Sum", help="The metric statistics. For multiple statistics, Enter a comma delimited list. (Average | Sum | Minimum | Maximum)")
    parser.add_option("-P", "--period", default="5", help="The period of time you would like to check against in minutes. (Default: 5 minutes)")
    parser.add_option("-t", "--timeout", default="10", help="Set the timeout duration in seconds. Defaults to never timing out.")
    parser.add_option("-n", "--metricname", help="The name of the metric you want to check")
    parser.add_option("-I", "--instanceid", help="The unique ID of the instance you want to monitor")
    parser.add_option("-m", "--minimum", default='', help="The minimum value used for performance data graphing.")
    parser.add_option("-M", "--maximum", default='', help="The maximum value used for performance data graphing.")
    parser.add_option("-k", "--accesskeyid", help="Your Amazon Access Key ID.")
    parser.add_option("-K", "--secretaccesskey", help="Your Amazon Secret Access Key.")
    parser.add_option("-r", "--region", default='us-east-1', help="Your Amazon Region Name.")
    parser.add_option("-F", "--configfile", help="The file path of your AWS configuration file.")
    parser.add_option("-f", "--credfile", help="The file path of your AWS credentials file.")

    parser.add_option("-T", "--debugtimeout", action='store_true', help="Toggle to add wait time to the plugin. Used to test the timeout function.")

    parser.add_option("-g", "--getinstances", action='store_true', help="Retrieve all instances associated with the given credentials.")

    options, _ = parser.parse_args()

    set_globals()

    if options.getinstances:
        get_instances(options.accesskeyid, options.secretaccesskey, options.region)

    # Set timeout value
    set_timeout(int(options.timeout))

    if options.debugtimeout:
        time.sleep(15)

    if options.version:
        print(version)
        sys.exit(0)

    if not options.metricname:
        parser.error("Metric Name is required for use")

    if not options.instanceid:
        parser.error("Instance ID is required for use")

    if not options.statistics:
        parser.error("At least one statistic must be specified for use")

    # Convert period to an integer for checking purposes
    options.period = (int(options.period) * 60)

    return options

#================================
#
#     Get Instances Function
#
#================================

def get_instances(access_key_id, secret_access_key, region_name):

    region_list = get_regions(access_key_id, secret_access_key, region_name)
    instance_list = {}
    raw_instances = []

    for region in region_list:

        regional_instance_list = []

        ec2 = boto3.client(
            'ec2',
            aws_access_key_id=options.accesskeyid,
            aws_secret_access_key=options.secretaccesskey,
            region_name=region
        )

        raw_response = ec2.describe_instances()

        # pprint(raw_response['Reservations'])

        if not raw_response['Reservations']:
            continue

        # raw_instances = raw_response['Reservations'][1]['Instances']
        for reservation in raw_response['Reservations']:
            for instance in reservation['Instances']:
                raw_instances.append(instance)

        for instance in raw_instances:
            if (instance['InstanceId'] != '' and instance['PublicIpAddress'] != ''):
                instance_to_append = {
                    "instance_id" : instance['InstanceId'],
                    "ip_address" : instance["PublicIpAddress"]
                }
                regional_instance_list.append(instance_to_append)

        instance_list[region] = regional_instance_list


    instance_list = json.dumps(instance_list)
    print instance_list
    sys.exit(0)

#================================
#
#      Get Regions Function
#
#================================

def get_regions(access_key_id, secret_access_key, region_name):
    ec2 = boto3.client(
        'ec2',
        aws_access_key_id=access_key_id,
        aws_secret_access_key=secret_access_key,
        region_name=region_name
    )

    response = ec2.describe_regions()
    raw_region_list = response['Regions']
    region_list = []

    for region in raw_region_list:
        region_list.append(region['RegionName'])

    return region_list

#================================
#
#        Check Function
#
#================================

def check_ec2(metric_name, instance_id, period, statistics, access_key_id, secret_access_key, region_name):

    utc_current_time = datetime.utcnow()
    utc_start_time = datetime.utcnow() - timedelta(seconds=period)
    current_time = datetime.now()
    start_time = datetime.now() - timedelta(seconds=period)

    print "UTC Current: %s | UTC Start: %s | Current: %s | Start: %s" % (utc_current_time, utc_start_time, current_time, start_time)

    aws = boto3.client(
        'cloudwatch',
        aws_access_key_id=access_key_id,
        aws_secret_access_key=secret_access_key,
        #aws_session_token=SESSION_TOKEN,
        region_name=region_name
    )

    response = aws.get_metric_statistics(
        Namespace='AWS/EC2',
        MetricName=metric_name,
        Dimensions=[
            {
                'Name': 'InstanceId',
                'Value': instance_id
            }   
        ],
        StartTime=(datetime.utcnow() - timedelta(seconds=period)),
        EndTime=datetime.utcnow(),
        Period=(period),
        Statistics=statistics
    )

    aws_response.update(response)

# For some reason unbeknownst to me, some processing will not work in parse_args(), so it happens here.
def process_input():

    global warning
    global critical
    global statistics
    global alerts

    # if (warning != ""):
    warning = options.warning.split(',')
    
    # if (critical != ""):
    critical = options.critical.split(',')

    # Replace any whitespace with empty string (boto3 will not accept spaces when parsing statistics)
    options.statistics = ''.join(options.statistics.split())
    statistics = options.statistics.split(',')

    statistic_conversion_dict = {
        'Avg' : 'Average',
        'Min' : 'Minimum',
        'Max' : 'Maximum'
    }

    # Capitalize the first letter of each list item
    for i, item in enumerate(statistics):
        statistics[i] = statistics[i].title()

    # Replace any statistic shorthand notation with the expanded string
    for i, item in enumerate(statistics):
        for key in statistic_conversion_dict:
            if key == item:
                statistics[i] = statistic_conversion_dict[item]

    # Build a list of return codes, one element for each statistic specified (later we will alert based on the highest return code present)
    for item in statistics:
        alerts.append(0)

    if len(warning) == 1:
        warning = warning * len(statistics)

    if len(critical) == 1:
        critical = critical * len(statistics)

    # Make sure there are an equal amount of warning and critical thresholds specifiec
    if not (len(warning) == len(critical)):
        print "You must specify an equal amount of warning and critical thresholds."
        exit(3)

    # Make sure there are an equal amount of thresholds and statistics specified
    if not (len(warning) == len(statistics)):
        print "You must specify an equal amount of statistics and warning/critcal thresholds."
        exit(3)

    # Make sure the user is giving us a valid AWS statistic
    valid_statistics = ['Average', 'Minimum', 'Maximum', 'Sum', 'SampleCount']
    for item in statistics:
        if item not in valid_statistics:
            print "%s is not a valid statistic. Valid statistics: [ Average | Minimum | Maximum | Sum | SampleCount ]" % (item)
            exit (3)

#==================================
#
#         Helper Functions
#
#==================================

def set_globals():
    # Set the appropriate environment variables for the AWS credentials/config files if the user specifies
    if options.credfile:
        os.environ["AWS_SHARED_CREDENTIALS_FILE"] = options.credfile

    if options.configfile:
        os.environ["AWS_CONFIG_FILE"] = options.configfile

def timeout_handler(signal, frame):
    print "Check_ec2 has timed out. Please try again."
    exit(3)

def set_timeout(seconds):
    signal.signal(signal.SIGALRM, timeout_handler)
    signal.alarm(seconds)

# Convert warning/critical threshold user input to tuples we can run comparisons against
def threshold_string_to_tuple(threshold_string):

    global options
    global low
    global high
    global inclusive

    # Need to reset each time we call the function for multiple statistic checks
    low = 0
    high = float('inf')
    inclusive = False

    if threshold_string[0] == "@":
        inclusive = True
        threshold_string = threshold_string[1:]

    threshold_list = threshold_string.split(":")

    # This block distills the string one piece at a time to get a final useable tuple
    length = len(threshold_list)
    if length < 1 or length > 2:
        print "Thresholds must be composed of 1-2 arguments."
        exit(3)
    elif length == 1:
        try:
            high = float(threshold_list[0])
        except ValueError:
            print "%s could not be converted to a number." % (threshold_list[0])
            exit(3)
    elif length == 2:
        if(threshold_list[0] == "~"):
            threshold_list[0] = '-inf'
        if(threshold_list[0] == ""):
            threshold_list[0] = '0'

        if(threshold_list[1] == ""):
            threshold_list[1] = 'inf'
        try:
            low = float(threshold_list[0])
            high = float(threshold_list[1])
        except ValueError:
            print "One of %s could not be converted to a number." % (threshold_list)
            exit(3)

    # Can't have a minimum higher than the maximum
    if low > high:
        print "The threshold minimum %s must be smaller than maximum %s" % (low, high)
        exit(3)

def check_against_thresholds(value):

    global inclusive
    global low
    global high

    if inclusive:
        if low <= value <= high:
            return True
        else:
            return False
    else:
        if (value < low) or (value > high):
            return True 
        else:
            return False


def get_perfdata_string(uom, label, value, warning_threshold, critical_threshold):

    global options

    # The format of the returned string needs to change slightly based on what we are checking
    if (options.metricname == 'CPUUtilization'):
        ret_string = "%s=%s%s;%s;%s;%s;%s; " % (options.metricname, value, uom, warning_threshold, critical_threshold, options.minimum, options.maximum)
    elif (options.metricname == 'StatusCheckFailed'):
        ret_string = "%s=%s%s;%s;%s;%s;%s; " % (options.metricname, value, uom, warning_threshold, critical_threshold, options.minimum, options.maximum)
    elif (options.metricname =='StatusCheckFailed_Instance'):
        ret_string = "%s=%s%s;%s;%s;%s;%s; " % (options.metricname, value, uom, warning_threshold, critical_threshold, options.minimum, options.maximum)
    elif (options.metricname =='StatusCheckFailed_System'):
        ret_string = "%s=%s%s;%s;%s;%s;%s; " % (options.metricname, value, uom, warning_threshold, critical_threshold, options.minimum, options.maximum)
    else:
        ret_string = "%s=%s%s;%s;%s;%s;%s; " % (options.metricname, value, uom, warning_threshold, critical_threshold, options.minimum, options.maximum)
    
    return ret_string


def final_create_return_data(dictionary):

    global options
    global userdata
    global perfdata
    global fulldata
    global highest_return_code
    global return_units

    userdata_list = []

    return_list = ['OK', 'WARNING', 'CRITICAL', 'UNKNOWN']

    metric_dictionary = {
        "CPUCreditUsage" : "CPU Credit Usage",
        "CPUCreditBalance" : "CPU Credit Balance",
        "CPUUtilization" : "CPU Utilization",
        "DiskReadOps" : "Disk Read Ops",
        "DiskWriteOps" : "Disk Write Ops",
        "DiskReadBytes" : "Disk Read Bytes",
        "DiskWriteBytes" : "Disk Write Bytes",
        "NetworkIn" : "Network In",
        "NetworkOut" : "Network Out",
        "NetworkPacketsIn" : "Network Packets In",
        "NetworkPacketsOut" : "Network Packets Out",
        "StatusCheckFailed" : "Status Check Failed",
        "StatusCheckFailed_Instance" : "Status Check Failed Instance",
        "StatusCheckFailed_System" : "Status Check Failed System"
    }

    # Get the highest return code of all statistics checked (pass to fulldata string interpolation and get corresponding return_list index)
    # We want the entire check to return the highest code of the individual
    highest_return_code = max(dictionary.values())['return_code']

    if (options.metricname == 'CPUUtilization'):

        for key in dictionary:
            if (key == "Average"):
                continue
            else:
                returnstring = return_list[dictionary[key]['return_code']]
                userdata_list.append("%s: %s%s" % (key, dictionary[key]['value'], return_units))
        
        perfdata = "%s=%s%s;%s;%s;%s;%s; " % (options.metricname, dictionary['Average']['value'], return_units, options.warning, options.critical, options.minimum, options.maximum)

        fulldata = "%s: %s %s - %s%s (%s) | %s" % (return_list[highest_return_code], metric_dictionary[options.metricname], "Average", dictionary['Average']['value'], return_units , ", ".join(userdata_list), perfdata)

    elif (options.metricname == 'StatusCheckFailed'):

        for key in dictionary:
            if (key == "Maximum"):
                continue
            else:
                returnstring = return_list[dictionary[key]['return_code']]
                userdata_list.append("%s: %s%s" % (key, dictionary[key]['value'], return_units))

        perfdata = "%s=%s%s;%s;%s;%s;%s; " % (options.metricname, dictionary['Maximum']['value'], return_units, options.warning, options.critical, options.minimum, options.maximum)

        fulldata = "%s: %s - %s | %s" % (return_list[highest_return_code], metric_dictionary[options.metricname], dictionary['Maximum']['value'] , perfdata)

    elif (options.metricname =='StatusCheckFailed_Instance'):

        for key in dictionary:
            if (key == "Maximum"):
                continue
            else:
                returnstring = return_list[dictionary[key]['return_code']]
                userdata_list.append("%s: %s%s" % (key, dictionary[key]['value'], return_units))

        perfdata = "%s=%s%s;%s;%s;%s;%s; " % (options.metricname, dictionary['Maximum']['value'], return_units, options.warning, options.critical, options.minimum, options.maximum)

        fulldata = "%s: %s - %s | %s" % (return_list[highest_return_code], metric_dictionary[options.metricname], dictionary['Maximum']['value'] , perfdata)

    elif (options.metricname =='StatusCheckFailed_System'):

        for key in dictionary:
            if (key == "Maximum"):
                continue
            else:
                returnstring = return_list[dictionary[key]['return_code']]
                userdata_list.append("%s: %s%s" % (key, dictionary[key]['value'], return_units))

        perfdata = "%s=%s%s;%s;%s;%s;%s; " % (options.metricname, dictionary['Maximum']['value'], return_units, options.warning, options.critical, options.minimum, options.maximum)

        fulldata = "%s: %s - %s | %s" % (return_list[highest_return_code], metric_dictionary[options.metricname], dictionary['Maximum']['value'] , perfdata)

    elif (options.metricname =='CPUCreditBalance'):

        for key in dictionary:
            if (key == "Average"):
                continue
            else:
                returnstring = return_list[dictionary[key]['return_code']]
                userdata_list.append("%s: %s%s" % (key, dictionary[key]['value'], return_units))
        
        perfdata = "%s=%s%s;%s;%s;%s;%s; " % (options.metricname, dictionary['Average']['value'], return_units, options.warning, options.critical, options.minimum, options.maximum)

        fulldata = "%s: %s - %s%s | %s" % (return_list[highest_return_code], metric_dictionary[options.metricname], dictionary['Average']['value'], return_units, perfdata)

    else:
        for key in dictionary:
            if (key == "Sum"):
                continue
            else:
                returnstring = return_list[dictionary[key]['return_code']]
                userdata_list.append("%s: %s%s" % (key, dictionary[key]['value'], return_units))
        
        perfdata = "%s=%s%s;%s;%s;%s;%s; " % (options.metricname, dictionary['Sum']['value'], return_units, options.warning, options.critical, options.minimum, options.maximum)

        fulldata = "%s: %s %s - %s (%s) | %s" % (return_list[highest_return_code], metric_dictionary[options.metricname], "Sum", dictionary['Sum']['value'],", ".join(userdata_list), perfdata)


#================================
#
#          Main Function
#
#================================

def main():

    global alerts
    global return_units
    global warning
    global critical
    global statistics
    global userdata
    global highest_return_code

    # Get options
    options = parse_args()

    ##############################################################################################
    #   
    #   Pertaining to the period interval, there is a disjunct between the local server time
    #   and Amazon's server time, which causes some issues in small intervals. There also 
    #   seems to be a 5 minute delay before data can be accessed via the CloudWatch API.
    #
    #   That being said, 5 minute intervals will always work - meaning for at least the initial
    #   release, customers should be limited to 5 minute checks. There are also some
    #   metrics that CANNOT be checked on an interval less than 300 seconds.
    #
    ###############################################################################################

    process_input()

    # Run the check, catch certain expected errors and output a more useable message instead
    try:
        check_ec2(options.metricname, options.instanceid, options.period, statistics, options.accesskeyid, options.secretaccesskey, options.region)
    except botocore.exceptions.NoRegionError:
        print "Please specify a region. This can be specified as an argument, or using the AWS config file."
        exit(3)
    except botocore.exceptions.NoCredentialsError:
        print "Credentials not found, please specify. This can be done with arguments, or using the AWS credentials file."
        exit(3)
    except botocore.exceptions.PartialCredentialsError as e:
        print "Partial credentials found. Missing credential: %s" % (e.kwargs['cred_var'])
        exit(3)
    except botocore.exceptions.ClientError as e:
        if e.response['Error']['Code'] == 'SignatureDoesNotMatch':
            print "Your Secret Access Key's signature does not match the Access Key ID provided. Check your AWS Secret Access Key and ID."
            print "Access Key ID: %s" % (options.accesskeyid)
            print "Secret Access Key: %s \n" % (options.secretaccesskey)
            exit(3)
        if e.response['Error']['Code'] == 'InvalidClientTokenId':
            print "Your Access Key ID does not match any known ID's. Please ensure your Access Key ID is correct."
            print "Access Key ID: %s \n" % (options.accesskeyid)
            exit(3)
        else:
            print "Unexpected error: %s" % e.response['Error']['Code']
            exit(3)

    # Convert our individual lists into a list of tuples
    check_list = zip(statistics, warning, critical, alerts)

    # Initialize a temporary defaultdict - makes it easier to create a nested dictionary
    temp_dict = collections.defaultdict(dict)

    # We only want the datapoints from the AWS API response
    try:
        datapoints = aws_response['Datapoints'][0]
    except IndexError:
        print "The check has received a response with no data. This is generally caused by an incorrect region name, invalid metric name, or invalid instance ID."
        print "Please verify the following:"
        print "Region: %s" % (options.region)
        print "Metric: %s" % (options.metricname)
        print "Instance ID: %s \n" % (options.instanceid)
        exit(3)

    for check in check_list:
        temp_dict[check[0]]['warning'] = check[1]
        temp_dict[check[0]]['critical'] = check[2]
        temp_dict[check[0]]['return_code'] = check[3]
        temp_dict[check[0]]['value'] = datapoints[check[0]]

    datapoint_dict = dict(temp_dict)

    # Convert certain unit names to symbolic form, or remove entirely
    return_units = datapoints['Unit']
    if return_units == "Percent":
        return_units = "%"
    elif return_units == "Count":
        return_units = ""
    elif return_units == "Bytes":
        return_units = "B"

    # Check the values for each item, and set the corresponding return code
    for key in datapoint_dict:
        if not options.warning:
            datapoint_dict[key]['warning'] = '0:' # If options.warning is not set, alert on values less than zero and greater than infinity
        if not options.critical:
            datapoint_dict[key]['critical'] = '0:'  
        threshold_string_to_tuple(datapoint_dict[key]['warning'])
        warning = check_against_thresholds(datapoint_dict[key]['value'])
        threshold_string_to_tuple(datapoint_dict[key]['critical'])
        critical = check_against_thresholds(datapoint_dict[key]['value'])

        if warning:
            datapoint_dict[key]['return_code'] = 1

        if critical:
            datapoint_dict[key]['return_code'] = 2

    final_create_return_data(datapoint_dict)

    # Verbosity output for debugging
    if options.verbose:
        print "################ Raw AWS Response ################ \n"
        pprint(aws_response)

        print "\n################ AWS Response Data ################ \n"
        print "Namespace: AWS/EC2"
        print "Instance ID: %s" % (options.instanceid)
        print "Metric Name: %s" % (options.metricname)
        print "Period: %s seconds" % (options.period)
        print "Unit of measure: %s" % (datapoints['Unit'])
        print "Timestamp: %s" % (aws_response['Datapoints'][0]['Timestamp'])

        print "\n################ Datapoints ################ \n"
        for point in datapoint_dict:
            print "Statistic: %s" % (point)
            print "\t Value: %s" % (datapoint_dict[point]['value'])
            print "\t Warning Threshold: %s" % (datapoint_dict[point]['warning'])
            print "\t Critical Threshold: %s" % (datapoint_dict[point]['critical'])
            print "\t Return Code: %s \n" % (datapoint_dict[point]['return_code'])

    print fulldata
    exit(highest_return_code)

main()
