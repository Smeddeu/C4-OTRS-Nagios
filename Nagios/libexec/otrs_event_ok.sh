#!/bin/bash
# ---
# $Id: otrs_event_ok.sh 6 2008-09-08 14:13:52Z wob $
# ---

if [ -z "$WEBSERVER" ]; then
   WEBSERVER=`hostname -f`
fi

if [ -z "$OTRS_EMAIL" ]; then
   OTRS_EMAIL=otrs@localhost
fi

LOGFILE=/var/nagios/otrs.log
DATUM=`date "+%Y-%m-%d_%H:%M:%S"`
echo "$DATUM EVENTHANDLER $NAGIOS_HOSTNAME/$NAGIOS_SERVICEDESC: State $NAGIOS_SERVICESTATE/$NAGIOS_SERVICESTATETYPE/$NAGIOS_SERVICEATTEMPT" >> $LOGFILE

case "$NAGIOS_SERVICESTATE" in
  OK)
    case "$NAGIOS_SERVICESTATETYPE" in
      HARD)

        BODY=$( /bin/cat <<- EOF_TEXT
		***** Nagios $NAGIOS_LONGDATETIME *****
		Event: $NAGIOS_SERVICESTATE/$NAGIOS_SERVICESTATETYPE/$NAGIOS_SERVICEATTEMPT

		Service: $NAGIOS_SERVICEDESC
		Host:    $NAGIOS_HOSTNAME
		Address: $NAGIOS_HOSTADDRESS
		State:   $NAGIOS_SERVICESTATE

		->$NAGIOS_SERVICEOUTPUT 

		#E# $NAGIOS_SERVICEACKCOMMENT

		Duration: $NAGIOS_SERVICEDURATION
		Link:    http://$WEBSERVER/nagios/cgi-bin/extinfo.cgi?type=2&host=$NAGIOS_HOSTNAME&service=$NAGIOS_SERVICEDESC

		EOF_TEXT)

        printf "%s\n" "$BODY" >> $LOGFILE
        printf "%s\n" "$BODY" | \
        /usr/bin/mail -s "** Event - $NAGIOS_HOSTNAME/$NAGIOS_SERVICEDESC is $NAGIOS_SERVICESTATE **" $OTRS_EMAIL
      ;;
      *)
      ;;
    esac
    ;;
  *)
    exit 0;
    ;;
esac

