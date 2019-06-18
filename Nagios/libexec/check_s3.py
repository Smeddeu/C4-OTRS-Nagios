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
bucket_list = {}
metric_list = {}

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

    version = 'check_s3.py, Version %s' %__VERSION__

    parser = optparse.OptionParser()

    parser.add_option("-w", "--warning", default='', help="Warning threshold value to be passed for the check. For multiple warning thresholds, enter a comma separated list.")
    parser.add_option("-c", "--critical", default='', help="Critical threshold value to be passed for the check. For multiple critical thresholds, enter a comma separated list.")
    parser.add_option("-V", "--version", action='store_true', help="Display the current version of check_s3")
    parser.add_option("-v", "--verbose", action='store_true', help="Display more information for troubleshooting.")
    parser.add_option("-S", "--statistics", default="Average,Minimum,Maximum,Sum", help="The metric statistics. For multiple statistics, Enter a comma delimited list. (Average | Sum | Minimum | Maximum)")
    parser.add_option("-P", "--period", default="5", help="The period of time you would like to check against in minutes. (Default: 5 minutes)")
    parser.add_option("-t", "--timeout", default="3", help="Set the timeout duration in seconds. Defaults to three second timeout.")
    parser.add_option("-n", "--metricname", help="The name of the metric you want to check")
    parser.add_option("-m", "--minimum", default='', help="The minimum value used for performance data graphing.")
    parser.add_option("-M", "--maximum", default='', help="The maximum value used for performance data graphing.")
    parser.add_option("-k", "--accesskeyid", help="Your Amazon Access Key ID.")
    parser.add_option("-K", "--secretaccesskey", help="Your Amazon Secret Access Key.")
    parser.add_option("-r", "--region", help="Your Amazon Region Name.")
    parser.add_option("-F", "--configfile", help="The file path of your AWS configuration file.")
    parser.add_option("-f", "--credfile", help="The file path of your AWS credentials file.")
    parser.add_option("-b", "--bucketname", help="The name of the AWS Bucket you want to monitor.")
    parser.add_option("-s", "--storagetype", help="The StorageType dimension, filters the data you have stored in a bucket by the type of storage specified here.")
    parser.add_option("-C", "--changemode", help="Change the overall function of the plugin based on the value passed here.")
    parser.add_option("-p", "--ping", help="Check for existence of specified bucket.")
    parser.add_option("-i", "--filterid", default='', help="Filters metrics configurations that you specify for request metrics on a bucket. You specify a filter id when you create a metrics configuraion.")

    parser.add_option("-T", "--debugtimeout", action='store_true', help="Toggle to add wait time to the plugin. Used to test the timeout function.")

    options, _ = parser.parse_args()

    # Set timeout value
    set_timeout(int(options.timeout))

    if options.debugtimeout:
        time.sleep(15)

    if options.version:
        print(version)
        sys.exit(0)

    # if not options.bucketname:
    #     parser.error("Bucket name is required for use.")

    # if not options.storagetype:
    #     parser.error("Storage type is required for use.")

    # if not options.metricname:
    #     parser.error("Metric Name is required for use.")

    # if not options.statistics:
    #     parser.error("At least one statistic must be specified for use.")

    # Convert period to an integer for checking purposes (Amazon CloudWatch only accepts seconds as a UOM)
    options.period = (int(options.period) * 60)

    return options


#================================
#
#        Check Function
#
#================================

def check_s3(metric_name, bucket_name, storage_type, period, statistics, access_key_id, secret_access_key, region_name, filterid):

    # Build our request
    aws = boto3.client(
        'cloudwatch',
        aws_access_key_id=access_key_id,
        aws_secret_access_key=secret_access_key,
        region_name=region_name
    )

    if (options.filterid and not options.storagetype):
        # Make the request
        response = aws.get_metric_statistics(
            Namespace='AWS/S3',
            MetricName=metric_name,
            Dimensions=[
                {
                    'Name': 'BucketName',
                    'Value': bucket_name 
                },
                {
                    'Name': 'FilterId',
                    'Value': filterid
                }
            ],
            StartTime=(datetime.now() - timedelta(seconds=period)),
            EndTime=datetime.now(),
            Period=(period),
            Statistics=statistics
        )   

    elif (options.filterid and options.storagetype):
        # Make the request
        response = aws.get_metric_statistics(
            Namespace='AWS/S3',
            MetricName=metric_name,
            Dimensions=[
                {
                    'Name': 'BucketName',
                    'Value': bucket_name 
                },
                {
                    'Name': 'StorageType',
                    'Value': storage_type
                },
                {
                    'Name': 'FilterId',
                    'Value': filterid
                }
            ],
            StartTime=(datetime.now() - timedelta(seconds=period)),
            EndTime=datetime.now(),
            Period=(period),
            Statistics=statistics
        )

    elif (options.storagetype and not options.filterid):
        # Make the request
        response = aws.get_metric_statistics(
            Namespace='AWS/S3',
            MetricName=metric_name,
            Dimensions=[
                {
                    'Name': 'BucketName',
                    'Value': bucket_name 
                }, 
                {
                    'Name': 'StorageType',
                    'Value': storage_type
                }
            ],
            StartTime=(datetime.now() - timedelta(seconds=period)),
            EndTime=datetime.now(),
            Period=(period),
            Statistics=statistics
        )

    else:
        # Make the request
        response = aws.get_metric_statistics(
            Namespace='AWS/S3',
            MetricName=metric_name,
            Dimensions=[
                {
                    'Name': 'BucketName',
                    'Value': bucket_name 
                }
            ],
            StartTime=(datetime.now() - timedelta(seconds=period)),
            EndTime=datetime.now(),
            Period=(period),
            Statistics=statistics
        )

    # Update our global response
    aws_response.update(response)

#================================
#
#       Get Buckets Function
#      
#================================

def get_available_buckets():

    bucket_list = {}

    # Build our AWS request
    aws = boto3.client(
        's3',
        aws_access_key_id=options.accesskeyid,
        aws_secret_access_key=options.secretaccesskey
    )

    # Make the request
    response = aws.list_buckets()

    # Initialize counter to fake a foreach loop w/ indices
    counter = 0

    for key, value in response['Buckets']:
        if (value == 'Name'):
            # bucketName = bucketRegion
            bucket_list[response['Buckets'][counter]['Name']] = get_bucket_region(response['Buckets'][counter]['Name']);
            counter += 1
        else:
            continue

    bucket_list = json.dumps(bucket_list);

    print bucket_list
    return bucket_list


#================================
#
#      Check Alive Function
#
#================================

def check_alive():
    s3 = boto3.resource(
        's3',
        aws_access_key_id=options.accesskeyid,
        aws_secret_access_key=options.secretaccesskey
    );

    bucket = s3.Bucket('nagiosxi-official')
    exists = True
    try:
        s3.meta.client.head_bucket(
            Bucket=options.bucketname,
        )
    except botocore.exceptions.ClientError as e:
        # If a client error is thrown, then check that it was a 404 error.
        # If it was a 404 error, then the bucket does not exist.
        error_code = int(e.response['Error']['Code'])
        if error_code == 404:
            exists = False

    if (exists == True):
        print "Bucket is currently accessible"
        exit(0);
    else:
        print "Bucket is currently unaccessible"
        exit(2);

#================================
#
#      Get Metrics Function
#
#================================

def get_available_metrics(bucket):

    metric_list = {}

    # Build our request
    aws = boto3.client(
        'cloudwatch',
        aws_access_key_id=options.accesskeyid,
        aws_secret_access_key=options.secretaccesskey,
        region_name=options.region
    )

    # Make the request
    response = aws.list_metrics(
        Namespace='AWS/S3',
        Dimensions=[
            {
                'Name' : 'BucketName',
                'Value' : bucket
            }
        ]
    )

    response = response['Metrics']

    # Create initial dictionary of metric names
    for key, value in enumerate(response):
        metric_list[response[key]['MetricName']] = {}

    for key, value in enumerate(response):
        for item in response[key]['Dimensions']:
            metric_list[response[key]['MetricName']][item['Name']] = item['Value']

    metric_list = json.dumps(metric_list)

    print metric_list
    return metric_list


def print_json(data):
    json_output = ""
    json_output = json.dumps(data)
    print json_output

#================================
#
#       Get Buckets Function
#      
#================================

def get_bucket_region(bucket):
    aws = boto3.client(
        's3',
        aws_access_key_id=options.accesskeyid,
        aws_secret_access_key=options.secretaccesskey
    )

    response = aws.get_bucket_location(
        Bucket=bucket
    )

    if (response['LocationConstraint'] == None):
        region = "us-east-1"
        return region
    else:
        return response['LocationConstraint']


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

    valid_statistics = ['Average', 'Minimum', 'Maximum', 'Sum', 'SampleCount']
    for item in statistics:
        if item not in valid_statistics:
            print "%s is not a valid statistic. Valid statistics: [ Average | Minimum | Maximum | Sum | SampleCount ]" % (item)
            exit (3)

    if options.credfile:
        os.environ["AWS_SHARED_CREDENTIALS_FILE"] = options.credfile

    if options.configfile:
        os.environ["AWS_CONFIG_FILE"] = options.configfile


#==================================
#
#         Helper Functions
#
#==================================

def timeout_handler(signal, frame):
    print "Check_s3 has timed out. Please try again."
    exit(3)

def set_timeout(seconds):
    signal.signal(signal.SIGALRM, timeout_handler)
    signal.alarm(seconds)

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

    if (options.metricname == 'CPUUtilization'):
        ret_string = "%s=%s%s;%s;%s;%s;%s; " % (options.metricname, value, uom, warning_threshold, critical_threshold, options.minimum, options.maximum)
    else:
        ret_string = "%s=%s%s;%s;%s;%s;%s; " % (options.metricname, value, uom, warning_threshold, critical_threshold, options.minimum, options.maximum)
    
    return ret_string


def create_return_data(dictionary):

    global options
    global userdata
    global perfdata
    global fulldata
    global highest_return_code
    global return_units

    userdata_list = []

    return_list = ['OK', 'WARNING', 'CRITICAL', 'UNKNOWN']

    metric_dictionary = {
        "BucketSizeBytes" : "Bucket Size Bytes",
        "NumberOfObjects" : "Number of Objects",
        "AllRequests" : "All Requests",
        "GetRequests" : "Get Requests",
        "PutRequests" : "Put Requests",
        "DeleteRequests" : "Delete Requests",
        "HeadRequests" : "Head Requests",
        "PostRequests" : "Post Requests",
        "ListRequests" : "List Requests",
        "BytesDownloaded" : "Bytes Downloaded",
        "BytesUploaded" : "Bytes Uploaded",
        "4xxErrors" : "4xx Errors",
        "5xxErrors" : "5xx Errors",
        "FirstByteLatency" : "First Byte Latency",
        "TotalRequestLatency" : "Total Request Latency"
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
    #   This could also be an issue with the way I am calculating the time, however I'm pretty
    #   sure it has to do with the way CloudWatch aggregates data.
    #
    #   That being said, 5 minute intervals will always work - meaning for at least the initial
    #   release, customers should be limited to 5 minute checks. There are also some
    #   metrics that CANNOT be checked on an interval less than 300 seconds.
    #
    ###############################################################################################

    process_input()

    # Run the check
    try:
        if options.changemode == 'getbuckets':
            get_available_buckets()
            exit(0)
        elif options.changemode == 'getmetrics':
            get_available_metrics(options.bucketname)
            exit(0)
        elif options.changemode == 'getregion':
            get_bucket_region(options.bucketname)
            exit(0)
        elif options.changemode == 'checkalive':
            check_alive();
        else:
            check_s3(options.metricname, options.bucketname, options.storagetype, options.period, statistics, options.accesskeyid, options.secretaccesskey, options.region, options.filterid)
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
        if options.verbose:
            print "################ Raw AWS Response ################ \n"
            pprint(aws_response)

            print "\n################ AWS Response Data ################ \n"
            print "Namespace: AWS/EC2"
            print "Bucket Name: %s" % (options.bucketname)
            print "Metric Name: %s" % (options.metricname)
            print "Period: %s seconds" % (options.period)
            exit(0);

        print "No data in the current check period. Try increasing the check period to see data."
        exit(0);

    for check in check_list:
        temp_dict[check[0]]['warning'] = check[1]
        temp_dict[check[0]]['critical'] = check[2]
        temp_dict[check[0]]['return_code'] = check[3]
        temp_dict[check[0]]['value'] = datapoints[check[0]]

    datapoint_dict = dict(temp_dict)

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

    if options.verbose:
        print "################ Raw AWS Response ################ \n"
        pprint(aws_response)

        print "\n################ AWS Response Data ################ \n"
        print "Namespace: AWS/EC2"
        print "Bucket Name: %s" % (options.bucketname)
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

    create_return_data(datapoint_dict)

    print fulldata
    exit(highest_return_code)

main()
