#!/bin/bash
# ---
# $Id: host_otrs_event_ok.sh 6 2008-09-08 14:13:52Z wob $
# ---

if [ -z "$WEBSERVER" ]; then
   WEBSERVER=`hostname -f`
fi

if [ -z "$OTRS_EMAIL" ]; then
   OTRS_EMAIL=otrs@localhost
fi

LOGFILE=/var/nagios/otrs.log
DATUM=`date "+%Y-%m-%d_%H:%M:%S"`
echo "$DATUM EVENTHANDLER $NAGIOS_HOSTNAME: State $NAGIOS_HOSTSTATE/$NAGIOS_HOSTSTATETYPE/$NAGIOS_HOSTATTEMPT" >> $LOGFILE

case "$NAGIOS_HOSTSTATE" in
  OK)
    case "$NAGIOS_HOSTSTATETYPE" in
      HARD)

        BODY=$( /bin/cat <<- EOF_TEXT
		***** Nagios $NAGIOS_LONGDATETIME *****
		Event: $NAGIOS_HOSTSTATE/$NAGIOS_HOSTSTATETYPE/$NAGIOS_HOSTATTEMPT

		Host:    $NAGIOS_HOSTNAME
		Address: $NAGIOS_HOSTADDRESS
		State:   $NAGIOS_HOSTSTATE

		->$NAGIOS_HOSTOUTPUT 

		Duration: $NAGIOS_HOSTDURATION
		Link:    http://$WEBSERVER/nagios/cgi-bin/status.cgi?host=$NAGIOS_HOSTNAME

		EOF_TEXT)

        printf "%s\n" "$BODY" >> $LOGFILE
        printf "%s\n" "$BODY" | \
        /usr/bin/mail -s "** Event - $NAGIOS_HOSTNAME is $NAGIOS_HOSTSTATE **" $OTRS_EMAIL
      ;;
      *)
      ;;
    esac
    ;;
  *)
    exit 0;
    ;;
esac

