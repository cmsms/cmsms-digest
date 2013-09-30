<?php
if (!cmsms()) exit;
if (!$this->CheckAccess()) {
	return $this->DisplayErrorPage($id, $params, $returnid, $this->Lang('accessdenied'));
}

if(isset($params['delete']) && ($params['delete'] > 0) && ($params['delete']+3600 > time()) && isset($params['template_name']) && ($params['delete'] > 0)) 
{
	$this->DeleteTemplate($params['template_name']);
	return $this->Redirect($id,'defaultadmin',$returnid);
}


$form = new CMSForm($this->getName(), $id, 'template_edit', $returnid);
$form->setButtons(array('submit','apply','cancel'));

$form->setWidget('template_name', 'text');
$form->setWidget('template_code', 'codearea', array(
  'default_value' => (isset($params['template_name']) && ($params['template_name'] != ''))?$this->GetTemplate($params['template_name']):$this->GetTemplateFromFile('frontend.email')
  ));

if($form->isCancelled()) {	return $this->Redirect($id,'defaultadmin',$returnid);}

if($form->isPosted())
{
  $form->process();
  if($form->noError())
  {
    if($template_name = $form->getWidget('template_name')->getValue())
    {
      if($template_code = $form->getWidget('template_code')->getValue())
      {
        $this->SetTemplate($template_name, $template_code);
        if($form->isSubmitted())
        {
          return $this->Redirect($id,'defaultadmin',$returnid);
        }
      }
    }
  }
}

echo $form->render();