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
        $this->sendDemoEmail($params['period']);
        //  $this->sendAll(true);
    }
