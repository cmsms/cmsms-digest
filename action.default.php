<?php
if (!cmsms()) exit;

$user = CMSUsers::getUser();

// If not logged, redirect to login form
if(is_null($user))
{
	$nparams['redirect_url'] = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	return cms_utils::get_module('CMSUsers')->DoAction('signin', $id, $nparams, $returnid);
	exit;
}

// User is logged, we can proceed

$subscriptions = DSubscription::doSelect(array('order_by' => 'title'));
$periods = DSubscriber::$periods;
$subscriber_subscriptions = DSubscriber::getSubscriptionsForUser($user->getId());

$form = new CMSForm($this->GetName(), $id, 'default',$returnid);
$form->setButtons(array('submit'));
$form->setLabels(array('submit' => 'Save'));

foreach($subscriptions as $subscription)
{
	$form->setWidget('subscription_'.$subscription->getId(), 'select', array(
		'label' => $subscription->title,
		'values' => $periods,		
		'default_value' => (isset($subscriber_subscriptions[$subscription->getId()]))?$subscriber_subscriptions[$subscription->getId()]->period:null
	));
	
}

if($form->isPosted())
{
	$form->process();

	if ($form->noError())
	{
		$updates = array();
			
		foreach($subscriptions as $subscription)
		{
			$updates[$subscription->getId()] = $form->getWidget('subscription_'.$subscription->getId())->getValue();
		}
		
		DSubscriber::updateSubscriptions($user->getId(), $updates);
	}
}

$this->smarty->assign('form', $form);
echo $this->ProcessTemplate('frontend.default.tpl');