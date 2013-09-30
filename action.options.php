<?php
if (!cmsms()) exit;
/* @var $this Digest */
if (!$this->CheckAccess()) {
	return $this->DisplayErrorPage($id, $params, $returnid, $this->Lang('accessdenied'));
}

$tpls = array();
foreach ($this->ListTemplates() as $template)
{
	$tpls[$template] = $template;
}

$form = new CMSForm($this->getName(), $id, 'options', $returnid);

if($form->isCancelled()) {	return $this->Redirect($id,'defaultadmin',$returnid);}

$form->setWidget('email_subject', 'text', array('preference' => 'email_subject'));
$form->setWidget('email_template', 'select', array('preference' => 'email_template', 'values' => $tpls));
$form->setWidget('send_emails', 'checkbox', array('preference' => 'send_emails'));
//$form->setWidget('send_fixed_time', 'checkbox', array('preference' => 'send_fixed_time'));
$form->setWidget('send_at', 'time', array('preference' => 'send_at', 'tips' => 'Average value. Precision is given regarding cron setup.'));

$form->setWidget('send_weekly_on', 'select', array('preference' => 'send_weekly_on', 'values' => DSubscriber::$week));
$form->setWidget('send_monthly_on', 'select', array('preference' => 'send_monthly_on', 'values' => DSubscriber::$month));


$form->setWidget('demo_email', 'text', array('preference' => 'demo_email'));

if($form->isSent())
{
  $form->process();
  return $this->Redirect($id,'defaultadmin',$returnid);
}

$this->smarty->assign('test', $this->CreateLink($id, 'test', $returnid, '', array(), '', true));
$this->smarty->assign('form', $form);

echo $this->ProcessTemplate('admin.options.tpl');

// echo $form->render();