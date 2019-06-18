# OTRS config file (automatically generated)
# VERSION:1.1
package Kernel::Config::Files::ZZZAuto;
use strict;
use warnings;
no warnings 'redefine';
use utf8;
sub Load {
    my ($File, $Self) = @_;
$Self->{'PostmasterDefaultQueue'} =  'Postmaster';
$Self->{'Nagios::Acknowledge::HTTP::Password'} =  'NIETUPTODATE';
$Self->{'Nagios::Acknowledge::HTTP::User'} =  'nagiosadmin';
$Self->{'Nagios::Acknowledge::HTTP::URL'} =  'http://192.168.13.100/nagios/cgi-bin/cmd.cgi?cmd_typ=<CMD_TYP>&cmd_mod=2&host=<HOST_NAME>&service=<SERVICE_NAME>&sticky_ack=on&send_notification=on&persistent=on&com_data=<TicketNumber>&btnSubmit=Commit';
$Self->{'PostMaster::PreFilterModule'}->{'1-SystemMonitoring'} =  {
  'ArticleType' => 'email-external',
  'CloseActionState' => 'closed successful',
  'ClosePendingTime' => '172800',
  'CloseTicketRegExp' => 'OK|UP',
  'DefaultService' => 'Host',
  'FreeTextHost' => '1',
  'FreeTextService' => '2',
  'FromAddressRegExp' => 'nagiosintercon@telenet.be',
  'HostRegExp' => '\\s*Host:\\s+(\\S+)\\s*',
  'Module' => 'Kernel::System::PostMaster::Filter::SystemMonitoring',
  'NewTicketRegExp' => 'CRITICAL|DOWN|WARNING|UNKNOWN|UNREACHABLE',
  'SenderType' => 'system',
  'ServiceRegExp' => '\\s*Service:\\s+(.*)\\s*',
  'StateRegExp' => '\\s*State:\\s+(\\S+)'
};
$Self->{'Process::DefaultQueue'} =  'Postmaster';
$Self->{'Daemon::SchedulerCronTaskManager::Task'}->{'MailAccountFetch'} =  {
  'Function' => 'Execute',
  'MaximumParallelInstances' => '1',
  'Module' => 'Kernel::System::Console::Command::Maint::PostMaster::MailAccountFetch',
  'Params' => [],
  'Schedule' => '*/1 * * * *',
  'TaskName' => 'MailAccountFetch'
};
delete $Self->{'PreferencesGroups'}->{'SpellDict'};
$Self->{'SendmailModule::AuthPassword'} =  'NIETUPTODATE';
$Self->{'SendmailModule::AuthUser'} =  'otrs@telenet.be';
$Self->{'SendmailModule::Port'} =  '587';
$Self->{'SendmailModule::Host'} =  'smtp.telenet.be';
$Self->{'SendmailModule'} =  'Kernel::System::Email::SMTPTLS';
delete $Self->{'NodeID'};
$Self->{'SecureMode'} =  '1';
}
1;
