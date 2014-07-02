<?php
/*
Module: Digest: This module allows users to subscribe to module notifications to get a digest of all the latest updates

Copyrights: Jean-Christophe Cuvelier <jcc@atomseeds.com>

License: GPL
*/

class Digest extends CMSModule
{
    public function GetName()
    {
        return 'Digest';
    }

    public function GetFriendlyName()
    {
        return 'Digest';
    }

    public function GetVersion()
    {
        return '0.10.0';
    }

    public function GetAuthor()
    {
        return 'Jean-Christophe Cuvelier';
    }

    public function GetAuthorEmail()
    {
        return 'jcc@atomseeds.com';
    }

    public function HasAdmin()
    {
        return true;
    }

    public function VisibleToAdminUser()
    {
        return $this->CheckAccess();
    }

    public function CheckAccess()
    {
        return $this->CheckPermission('Manage Digest');
    }

    public function GetDependencies()
    {
        return array(
            'Cron' => '0.0.6',
            'CMSForms' => '1.10.10',
            'CMSUsers' => '1.0.13',
            'CMSMailer' => '2.0.2'
        );
    }

    public function GetAdminSection()
    {
        return 'usersgroups';
    }

    public function GetHelp()
    {
        return $this->lang('help');
    }

    public function IsPluginModule()
    {
        return true;
    }

    public function MinimumCMSVersion()
    {
        return "1.10";
    }

    public function InitializeFrontend()
    {
        $this->RegisterModulePlugin();
    }

    function GetEventDescription($eventname)
    {
        return $this->Lang('event_info_' . $eventname);
    }

    function GetEventHelp($eventname)
    {
        return $this->Lang('event_help_' . $eventname);
    }

    function HandlesEvents()
    {
        return true;
    }

    public function DoEvent($originator, $eventname, &$params)
    {
        if ($eventname == 'CronHourly') {
            // Do desired action here
            if ($this->GetPreference('send_emails', false) == 1) {
                $this->Audit(0, $this->Lang('friendlyname'), 'Sending email fired');
                $this->sendAll();
            } else {
                $this->Audit(0, $this->Lang('friendlyname' ), 'Sending email not fired');
            }
        }
    }


    public function Install()
    {
        $db = cms_utils::get_db();
        $dict = NewDataDictionary($db);

        // Subscriptions
        $flds = '
    id I KEY AUTOINCREMENT,
    title C(255),
    module_name C(255),
    params C(255),
    template C(255)
    ';
        $taboptarray = array();
        $sqlarray = $dict->CreateTableSQL(cms_db_prefix() . "module_digest_subscriptions",
            $flds,
            $taboptarray);
        $dict->ExecuteSQLArray($sqlarray);

        // Subscribers
        $flds = '
    id I KEY AUTOINCREMENT,
    timestamp I,
    user_id I,
    subscription_id I,
    period C(255)
    ';
        $sqlarray = $dict->CreateTableSQL(cms_db_prefix() . "module_digest_subscribers",
            $flds,
            $taboptarray);
        $dict->ExecuteSQLArray($sqlarray);

        // Annoucements
        $flds = '
    id I KEY AUTOINCREMENT,
    subscription_id I,
    timestamp I,
    title C(255),
    announcement XL
    ';
        $sqlarray = $dict->CreateTableSQL(cms_db_prefix() . "module_digest_announcements",
            $flds,
            $taboptarray);
        $dict->ExecuteSQLArray($sqlarray);

        // Logs
        $flds = '
    id I KEY AUTOINCREMENT,
    timestamp I,
    period C(255),
    title C(255),
    summary XL,
    details XL
    ';
        $sqlarray = $dict->CreateTableSQL(cms_db_prefix() . "module_digest_logs",
            $flds,
            $taboptarray);
        $dict->ExecuteSQLArray($sqlarray);

        // Preferences
        $this->SetPreference('email_subject', '%PERIOD% updates');
        $this->SetPreference('email_template', 'default');
        $this->SetPreference('send_emails', 'false');
        $this->SetPreference('send_weekly_on', '7');
        $this->SetPreference('send_monthly_on', 'first');

        // REGISTER EVENTS

        $this->AddEventHandler('Cron', 'CronHourly', false);


        // Permission
        $this->CreatePermission('Use NotificationTool', 'Use NotificationTool');

        $this->RegisterModulePlugin(true);

        $this->Audit(0,
            $this->Lang('friendlyname'),
            $this->Lang('installed', $this->GetVersion()));

    }

    public function Uninstall()
    {
        $db = cms_utils::get_db();
        $dict = NewDataDictionary($db);

        $sqlarray = $dict->DropTableSQL(cms_db_prefix() . "module_digest_subscriptions");
        $dict->ExecuteSQLArray($sqlarray);
        $sqlarray = $dict->DropTableSQL(cms_db_prefix() . "module_digest_subscribers");
        $dict->ExecuteSQLArray($sqlarray);
        $sqlarray = $dict->DropTableSQL(cms_db_prefix() . "module_digest_announcements");
        $dict->ExecuteSQLArray($sqlarray);
        $sqlarray = $dict->DropTableSQL(cms_db_prefix() . "module_digest_logs");
        $dict->ExecuteSQLArray($sqlarray);

        // Remove preferences
        $this->RemovePreference();

        // remove the permissions
        $this->RemovePermission('Use NotificationTool');

        // EVENTS
        $this->RemoveEventHandler('Cron', 'CronDaily');

        // put mention into the admin log
        $this->Audit(0, $this->Lang('friendlyname'), $this->Lang('uninstalled'));
    }

    public function sendAll()
    {
        $this->Audit(0, $this->Lang('friendlyname'), 'Sending all mails.');

        if ($this->sendable()) {
            foreach (DSubscriber::getPeriodsForSend() as $period) {
                    $this->sendDigestForPeriod($period);
            }
        }
    }

    private function sendDigestForPeriod($period, $demo_email = NULL)
    {
        if($period != 'None')
        {
            $digest = new DDigest($period);
            $digest->loadSubscribers();
            $digest->loadSubscriptionsContent();
            $digest->sendEmails();
        }
    }

    public function sendUpdateImmediately($module_name)
    {
        // $this->getUpdateSubject(), $this->getUpdateBody()
        $subscriptions = DSubscription::doSelect(array('where' => array('module_name' => $module_name)));
        $cmsmailer = cms_utils::get_module('CMSMailer');

        foreach ($subscriptions as $subscription) {
            $subscribers = $subscription->getSubscribers('Directly');
            if (count($subscribers)) {
                $items = $subscription->getModuleItemsForDirectUpdate();

                foreach ($items as $item) {
                    $subject = $item->getUpdateSubject();
                    $body = $item->getUpdateBody();
                    foreach ($subscribers as $subscriber) {
                        $cmsmailer->reset();
                        $cmsmailer->SetCharSet('UTF-8');
                        $cmsmailer->SetSubject($subject);
                        $cmsmailer->AddAddress($subscriber->getUser()->email);
                        $cmsmailer->SetBody($body);
                        $cmsmailer->IsHTML(true);
                        if ($this->GetPreference('send_emails') == 1) {
                            $cmsmailer->Send();
                        }
                    }

                    $item->send_update_immediately = NULL;
                    $item->save();
                }
            }
        }
    }

    private function sendable()
    {
        $time = $this->GetPreference('send_at', '0:0:0');

        $now = new DateTime();

        $start = new DateTime();
        $start->setTimestamp(strtotime($time));

        $end = clone $start;
        $end->add(new DateInterval('PT1H'));

        if (($now < $start) || ($now > $end) ) {
            echo '<p><em>Darth Vader:</em> The force is with you, young Skywalker, but you are not a Jedi yet.</p>';

            return false;
        }

        $latest = DLog::getLatestLog();

        $limit = new DateTime();
        $limit->setTimestamp(strtotime(strtotime('-23 hours')));

        if ($latest->getDateTime() > $limit) {
            echo '<p><em>Yoda:</em> Too early another mail to send</p>';

            return false;
        } else {
            echo "<p>Sendable</p>";
            return true;
        }
    }

    public function sendDemoEmail($period)
    {
        if ($email = $this->GetPreference('demo_email')) {
            $this->sendDigestForPeriod($period, $email);
        }
    }
}