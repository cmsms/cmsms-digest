<?php
if (!cmsms()) exit;
/* @var $this Digest */
if (!$this->CheckAccess()) {
    return $this->DisplayErrorPage($id, $params, $returnid, $this->Lang('accessdenied'));
}

$this->sendAll();