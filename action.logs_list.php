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
	$query = array('order_by' => 'timestamp DESC');
}

if(isset($params['limit']))
{
	$query['limit'] = $params['limit'];
}

$logs = DLog::doSelect($query);

$this->smarty->assign('logs', $logs);

echo $this->ProcessTemplate('admin.logs_list.tpl');