#!/bin/bash
# ---
# $Id: notify_otrs.sh 6 2008-09-08 14:13:52Z wob $
# ---

if [ -z "$WEBSERVER" ]; then
   WEBSERVER=`hostname -f`
fi

LOGFILE=/var/nagios/otrs.log
DATUM=`date "+%Y-%m-%d_%H:%M:%S"`
echo "$DATUM $NAGIOS_NOTIFICATIONTYPE $NAGIOS_HOSTNAME/$NAGIOS_SERVICEDESC: State $NAGIOS_SERVICESTATE" >> $LOGFILE
case "$NAGIOS_NOTIFICATIONTYPE" in
   ACKNOWLEDGEMENT*|RECOVERY)

      BODY=$( /bin/cat <<- EOF_TEXT
	***** Nagios $NAGIOS_LONGDATETIME *****
	Notification Type: $NAGIOS_NOTIFICATIONTYPE

	Service: $NAGIOS_SERVICEDESC
	Host:    $NAGIOS_HOSTNAME
	Address: $NAGIOS_HOSTADDRESS
	State:   $NAGIOS_SERVICESTATE

	->$NAGIOS_SERVICEOUTPUT 

	# $NAGIOS_SERVICEACKCOMMENT

	Duration: $NAGIOS_SERVICEDURATION
	Link:    http://$WEBSERVER/nagios/cgi-bin/extinfo.cgi?type=2&host=$NAGIOS_HOSTNAME&service=$NAGIOS_SERVICEDESC

	EOF_TEXT)

      printf "%s\n" "$BODY" >> $LOGFILE
      printf "%s\n" "$BODY" | \
      /usr/bin/mail -s "** $NAGIOS_NOTIFICATIONTYPE - $NAGIOS_HOSTNAME/$NAGIOS_SERVICEDESC is $NAGIOS_SERVICESTATE **" $NAGIOS_CONTACTEMAIL
      ;;

   *)
      exit 0;
      ;;
esac

