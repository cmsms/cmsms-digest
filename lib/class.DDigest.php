<?php

class DDigest
{

    protected $period;
    protected $subscribers;
    protected $subscriptions;

    protected $demo = false;

    public function __construct($period)
    {
        if (in_array($period, DSubscriber::$periods)) {
            $this->period = $period;
        }
    }

    public function getSubscribers()
    {
        if (empty($this->subscribers)) $this->loadSubscribers();
        return $this->subscribers;
    }

    public function addSubscriber(DSubscriber $subscriber)
    {
        $this->subscribers[$subscriber->user_id] = $subscriber;
    }

    public function loadSubscribers()
    {
        $subscriptions = DSubscriber::doSelect(array(
            'where' => array(
                'period' => $this->period
            )
        ));

        foreach ($subscriptions as $subscription) {
            if (!isset($this->subscribers[$subscription->user_id])) $this->subscribers[$subscription->user_id] = $subscription;

            $this->subscribers[$subscription->user_id]->injectSubscription($this->getSubscription($subscription->subscription_id), $this->period);
        }
    }

    private function getSubscription($subscription_id)
    {
        if (!isset($this->subscriptions[$subscription_id])) $this->fetchSubscription($subscription_id);
        return $this->subscriptions[$subscription_id];
    }

    private function fetchSubscription($subscription_id)
    {
        if ($subscription = DSubscription::retrieveByPk($subscription_id)) {
            $this->subscriptions[$subscription_id] = $subscription;
        } else {
            throw new Exception('Subscription not found');
        }
    }

    public function addSubscription(DSubscription $subscription)
    {
        $this->subscriptions[$subscription->getId()] = $subscription;
    }

    public function getSubscriptions()
    {
        return $this->subscriptions;
    }


    public function loadSubscriptionsContent()
    {
        foreach ($this->subscriptions as &$subscription) {
            /** @var DSubscription $subscription */
            $subscription->getContent($this->period);
        }
    }

    public function sendEmails()
    {
        /** @var CMSMailer $cmsmailer */
        $cmsmailer = cms_utils::get_module('CMSMailer');
        /** @var Digest $digest */
        $digest = cms_utils::get_module('Digest');

        $subject = str_replace('%PERIOD%', $this->period, $digest->GetPreference('email_subject'));
        $send_emails = $digest->GetPreference('send_emails');

        $sent_subscribers = array();

        foreach ($this->subscribers as $subscriber) {
            /** @var $subscriber DSubscriber */
            if ($subscriber->hasContent($this->period)) {
                $cmsmailer->reset();

                $digest->smarty->assign('modules', $subscriber->getContent($this->period));
                $digest->smarty->assign('period', $this->period);
                $template = $digest->getPreference('email_template');

                if (is_null($template)) {
                    $body = $digest->ProcessTemplate('frontend.email.tpl');
                } else {
                    $body = $digest->ProcessTemplateFromDatabase($template);
                }

                $cmsmailer->SetCharSet('UTF-8');
                $cmsmailer->SetSubject($subject);

                $email = $subscriber->getUser()->email;

                $cmsmailer->AddAddress($email);
                $cmsmailer->SetBody($body);
                $cmsmailer->IsHTML(true);

                if ($send_emails == 1 || $this->demo) {
                    $cmsmailer->Send();
                    $sent_subscribers[$subscriber->user_id] = $subscriber;
                }
            }
        }

        if(count($sent_subscribers))
        {
            $log = new DLog();
            $log->title = $subject;
            if($this->demo)
            {
                $log->period = 'DEMO ' . $this->period;
            }
            else
            {
                $log->period = $this->period;
            }

            $log->summary = count($sent_subscribers) . ' emails sent for ' . $this->period;

            $log_detail = "The following subscribers received an update:
=============================================

";
            foreach($sent_subscribers as $subscriber)
            {
                $log_detail .= $subscriber->getUser()->email . "\n";
            }

            $log_detail .= "\n\n\nserialized\n\n\n" . serialize($this);

            $log->details = $log_detail;
            $log->save();

            if($this->demo)
            {
                echo '<pre>';
                echo $log_detail;
                echo '</pre>';
            }
        }

    }

    public function demo()
    {
        $this->demo = true;
    }

}