<?php
    if (!cmsms()) exit;
    /* @var $this Digest */
    if (!$this->CheckAccess()) {
        return $this->DisplayErrorPage($id, $params, $returnid, $this->Lang('accessdenied'));
    }

    $form = new CMSForm($this->GetName(), $id, 'test', $returnid);
    $form->setButtons(array('submit'));
    $form->setLabel('submit', 'Send');
    $form->setWidget('period', 'select', array('values' => DSubscriber::$periods));

    echo $form;

    if (isset($params['period']) && $params['period'] != '') {

        $period = $params['period'];
        $digest = new DDigest($period);
        $digest->demo();

        $user = new CMSUser();
        $user->email = $this->GetPreference('demo_email');

        $subscriber = new DSubscriber();
        $subscriber->setUser($user);

        $subscriptions = DSubscription::doSelect(array());

        foreach($subscriptions as &$subscription)
        {
            $subscription->period = $period;
            $subscriber->injectSubscription($subscription, $period);
            $digest->addSubscription($subscription);
        }

        $digest->addSubscriber($subscriber);

//        echo '<pre>';
//        var_dump($digest);
//        echo '</pre>';

        $digest->loadSubscriptionsContent();
        $digest->sendEmails();
    }
