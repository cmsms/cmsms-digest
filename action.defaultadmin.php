<?php
if (!cmsms()) exit;
if (!$this->CheckAccess()) {
	return $this->DisplayErrorPage($id, $params, $returnid, $this->Lang('accessdenied'));
}

$subscriptions = DSubscription::doSelect();

$this->smarty->assign('lenght', 5*count($subscriptions));
$this->smarty->assign('subscriptions_list', $this->CreateLink($id, 'subscriptions_list', $returnid, '', array(), '', true));
$this->smarty->assign('subscribers_list', $this->CreateLink($id, 'subscribers_list', $returnid, '', array(), '', true));
$this->smarty->assign('templates_list', $this->CreateLink($id, 'templates_list', $returnid, '', array(), '', true));
$this->smarty->assign('logs_list', $this->CreateLink($id, 'logs_list', $returnid, '', array(), '', true));
$this->smarty->assign('options', $this->CreateLink($id, 'options', $returnid, '', array(), '', true));

$this->smarty->assign('id',$id);

echo $this->ProcessTemplate('admin.default.tpl');