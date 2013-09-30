<?php
if (!cmsms()) exit;
if (!$this->CheckAccess()) {
	return $this->DisplayErrorPage($id, $params, $returnid, $this->Lang('accessdenied'));
}

if(isset($params['item_id']) && $params['item_id'] != '')
{
	$subscription = DSubscription::retrieveByPk($params['item_id']);	
}

if(!isset($subscription) || is_null($subscription))
{
	$subscription = new DSubscription();
}

if(isset($params['delete']) && ($params['delete'] > 0) && ($params['delete']+3600 > time()))
{
	$subscription->delete();
	return $this->Redirect($id,'subscriptions_list',$returnid);
}

$compatible_modules = DSubscription::getCompatibleModules();
$this->smarty->assign('modules', $compatible_modules);

$form = new CMSForm($this->GetName(), $id, 'subscription_edit',$returnid);

if($form->isCancelled()) {	return $this->Redirect($id,'subscriptions_list',$returnid);}

$form->setButtons(array('submit','apply','cancel'));
$form->setWidget('item_id', 'hidden', array('object' => &$subscription, 'field_name' => 'id'));
$form->setWidget('title', 'text', array('object' => &$subscription));
$form->setWidget('module_name', 'select', array('object' => &$subscription));
$form->setWidget('params', 'text', array('object' => &$subscription));
$form->setWidget('template', 'select', array('object' => &$subscription));

if($form->isPosted())
{
		$form->process();

		if (!$form->hasErrors())
		{
			$subscription->save();
		}
	
		if($form->isSubmitted())
		{
				return $this->Redirect($id,'subscriptions_list',$returnid);
		}
		
		$form->getWidget('item_id')->setValue($subscription->getId());
	
}

$this->smarty->assign('id', $id);
$this->smarty->assign('form', $form);
$this->smarty->assign('selected_module', $subscription->module_name);
$this->smarty->assign('selected_template', $subscription->template);

echo $this->ProcessTemplate('admin.subscription_edit.tpl');