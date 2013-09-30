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
            return '0.1.0';
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
                'Cron'      => '0.0.6',
                'CMSForms'  => '1.10.10',
                'CMSUsers'  => '1.0.13',
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

        public function setParameters()
        {
            if(!isset($this->initialized))
            {
                $this->InitializeFrontend();
            }
            $this->initialized = true;
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
                if ($this->GetPreference('send_emails') == 1) {
                    $this->Audit(0, $this->Lang('friendlyname'), 'Sending email fired');
                    $this->sendAll();
                } else {
                    $this->Audit(0, $this->Lang('friendlyname'), 'Sending email not fired');
                }
            }
        }


        public function GetHeaderHTML()
        {
//            return '<link rel="stylesheet" type="text/css" href="' . $this->config['root_url'] . '/lib/jquery/css/smoothness/jquery-ui-1.8.4.custom.css" />';
        }

        public function Install()
        {
            $db   = cms_utils::get_db();
            $dict = NewDataDictionary($db);

            // Subscriptions
            $flds     = '
    id I KEY AUTOINCREMENT,
    title C(255),
    module_name C(255),
    params C(255),
    template C(255)
    ';
            $sqlarray = $dict->CreateTableSQL(cms_db_prefix() . "module_digest_subscriptions",
                $flds,
                $taboptarray);
            $dict->ExecuteSQLArray($sqlarray);

            // Subscribers
            $flds     = '
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
            $flds     = '
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
            $flds     = '
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
            $this->CreatePermission('Use NotificationTool');

            $this->Audit(0,
                $this->Lang('friendlyname'),
                $this->Lang('installed', $this->GetVersion()));

        }

        public function Uninstall()
        {
            $db   = cms_utils::get_db();
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


        private function sendDigestForPeriod($period, $demo_email = NULL)
        {
            if (in_array($period, DSubscriber::$periods) && $period != 'None') {

                $datas = DSubscriber::getDatasForPeriod($period);

                if(!is_null($demo_email))
                {
                    $subscriptions = DSubscription::doSelect();

                    $datas = array(
                        'subscribers' => array(array('subscriber' => $demo_email, 'subscriptions' => DSubscription::getIds($subscriptions))),
                        'subscriptions' => DSubscription::doSelect()
                    );
                }

                $content = array();

                foreach ($datas['subscriptions'] as $subscription) {
                    /* @var $subscription DSubscription */
                    if ($subscription->haveContent($period)) {
                        $content[$subscription->getId()]['title']       = $subscription->title;
                        $content[$subscription->getId()]['module_name'] = $subscription->module_name;
                        $content[$subscription->getId()]['announces']   = $subscription->getAnnounces($period);
                        $content[$subscription->getId()]['content']     = $subscription->getContent($period);
                    }
                }

                $sent_for_period = 0;
                $logs            = '';

                if (count($content)) {

                    $cmsmailer = cms_utils::get_module('CMSMailer');
                    $subject   = str_replace('%PERIOD%', $period, $this->GetPreference('email_subject'));

                    foreach ($datas['subscribers'] as $subscriber) {

                        $subscriber_content = array();
                        foreach ($subscriber['subscriptions'] as $subscription) {
                            if (isset($content[$subscription])) {
                                $subscriber_content[$subscription] = $content[$subscription];
                            }
                        }

                        if (count($subscriber_content)) {
                            $this->smarty->assign('modules', $subscriber_content);
                            $this->smarty->assign('period', $period);
                            $template = $this->getPreference('email_template');

                            if (is_null($template)) {
                                $body = $this->ProcessTemplate('frontend.email.tpl');
                            } else {
                                $body = $this->ProcessTemplateFromDatabase($template);
                            }

                            $cmsmailer->reset();
                            $cmsmailer->SetCharSet('UTF-8');
                            $cmsmailer->SetSubject($subject);

                            if (is_object($subscriber['subscriber'])) {
                                $email = $subscriber['subscriber']->getUser()->email;
                            } else {
                                $email = $subscriber['subscriber'];
                            }

                            $logs .= 'Send content for ' . $email . ': ' . $subject . "\n";

                            $cmsmailer->AddAddress($email);
                            $cmsmailer->SetBody($body);
                            $cmsmailer->IsHTML(true);
                        }

                        if ($this->GetPreference('send_emails') == 1 || $demo_email) {
                            if (!$demo_email) {
                                $sent_for_period++;
                            }
                            $cmsmailer->Send();
                            echo 'An email has been sent to ' . $email . '<br />';
                            // debug_display('Content: ' . htmlentities($content));
                            // debug_display('Email: ' . htmlentities($email));
                        }
                    }
                } else {
                    if ($demo_email) {
                        echo '<p>No content for period ' . $period . ' with email ' . $demo_email . '</p>';
                    }
                }

                if ($sent_for_period) {
                    $logs .= 'CONTENT: [[[' . serialize($content) . "]]]\n";
                    // Log it
                    $log          = new DLog();
                    $log->title   = $subject;
                    $log->period  = $period;
                    $log->summary = $sent_for_period . ' emails sent for ' . $period;
                    $log->details = $logs;
                    $log->save();
                }
            }
        }

        public function sendUpdateImmediately($module_name)
        {
            // $this->getUpdateSubject(), $this->getUpdateBody()
            $subscriptions = DSubscription::doSelect(array('where' => array('module_name' => $module_name)));
            $cmsmailer     = cms_utils::get_module('CMSMailer');

            foreach ($subscriptions as $subscription) {
                $subscribers = $subscription->getSubscribers('Directly');
                if (count($subscribers)) {
                    $items = $subscription->getModuleItemsForDirectUpdate();

                    foreach ($items as $item) {
                        $subject = $item->getUpdateSubject();
                        $body    = $item->getUpdateBody();
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

        public function sendAll($demo = false)
        {
            $this->Audit(0, $this->Lang('friendlyname'), 'Sending all mails. ');
            if ($demo) {
                $email = $this->GetPreference('demo_email');
            } else {
                $email = NULL;
            }

            if ($demo || $this->sendable()) {
                foreach (DSubscriber::getPeriodsForSend() as $period) {
                    $this->sendDigestForPeriod($period, $email);
                }
            }
        }

        private function sendable()
        {

            if (time() < strtotime($this->GetPreference('send_at'))) {
                echo '<p><em>Darth Vader:</em> The force is with you, young Skywalker, but you are not a Jedi yet.</p>';

                return false;
            }
            $latest = DLog::getLatestLog();
            $limit  = strtotime('-23 hours');
            if ($latest->timestamp > $limit) {
                echo '<p><em>Yoda:</em> Too early another mail to send</p>';

                return false;
            } else {
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