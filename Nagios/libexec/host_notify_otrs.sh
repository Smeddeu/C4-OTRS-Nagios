#!/bin/bash
# ---
# $Id: host_notify_otrs.sh 6 2008-09-08 14:13:52Z wob $
# ---

if [ -z "$WEBSERVER" ]; then
   WEBSERVER=`hostname -f`
fi

LOGFILE=/var/nagios/otrs.log
echo  "$NAGIOS_NOTIFICATIONTYPE" >> $LOGFILE
case "$NAGIOS_NOTIFICATIONTYPE" in
   ACKNOWLEDGEMENT*|RECOVERY)

      BODY=$( /bin/cat <<- EOF_TEXT
	***** Nagios $NAGIOS_LONGDATETIME *****
	Notification Type: $NAGIOS_NOTIFICATIONTYPE
	Host:    $NAGIOS_HOSTNAME
	State:   $NAGIOS_HOSTSTATE
	Address: $NAGIOS_HOSTADDRESS

	->$NAGIOS_HOSTOUTPUT

	# $NAGIOS_HOSTACKCOMMENT

	Link:    http://$WEBSERVER/nagios/cgi-bin/status.cgi?host=$NAGIOS_HOSTNAME

	EOF_TEXT)

      printf "%s\n" "$BODY" >> $LOGFILE
      printf "%s\n" "$BODY" | \
      /usr/bin/mail -s "Host $NAGIOS_HOSTSTATE alert for $NAGIOS_HOSTNAME!" $NAGIOS_CONTACTEMAIL
      ;;

   *)
      exit 0;
      ;;
esac

