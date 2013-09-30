<?php
if (!cmsms()) exit;
if (!$this->CheckAccess()) {
	return $this->DisplayErrorPage($id, $params, $returnid, $this->Lang('accessdenied'));
}

$this->smarty->assign('templates', $this->ListTemplates());

$this->smarty->assign('create_template', $this->CreateLink($id, 'template_edit', $returnid, '', array('template_name' => '_TEMPLATE_'), '', true));
$this->smarty->assign('delete_template', $this->CreateLink($id, 'template_edit', $returnid, '', array('template_name' => '_TEMPLATE_', 'delete' => time()), '', true));



echo $this->ProcessTemplate('admin.templates_list.tpl');