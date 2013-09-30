<?php
if (!cmsms()) exit;
if (!$this->CheckAccess()) {
	return $this->DisplayErrorPage($id, $params, $returnid, $this->Lang('accessdenied'));
}

if(isset($params['order_by']))
{
	$query = array('order_by' => $params['order_by']);
}
else
{
	$query = array('order_by' => 'id');
}

if(isset($params['limit']))
{
	$query['limit'] = $params['limit'];
}

$subscriptions = DSubscription::doSelect($query);

$this->smarty->assign('subscriptions', $subscriptions);

$this->smarty->assign('create_link', $this->CreateLink($id, 'subscription_edit', $returnid, '', array('item_id' => '_ITEM_ID_'), '', true));
$this->smarty->assign('delete_link', $this->CreateLink($id, 'subscription_edit', $returnid, '', array('item_id' => '_ITEM_ID_', 'delete' => time()), '', true));

echo $this->ProcessTemplate('admin.subscriptions_list.tpl');