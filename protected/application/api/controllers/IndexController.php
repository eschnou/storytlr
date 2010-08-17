<?php
class Api_IndexController extends Zend_Rest_Controller
{
	public function init()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
    }

    public function indexAction()
    {
    	$Response = $this->getResponse();
    	
    	//$Response->appendBody(getServiceDocument());
    	//$this->getResponse()->appendBody('indexAction() of '.$this->getRequest()->getControllerName());
    }

    public function getAction()
    {
    	$this->getResponse()->appendBody('getAction() of '.$this->getRequest()->getControllerName());
    }

    public function postAction()
    {
    	$this->getResponse()->appendBody('postAction() of '.$this->getRequest()->getControllerName());
    }

    public function putAction()
    {
    	$this->getResponse()->appendBody('putAction() of '.$this->getRequest()->getControllerName());
    }

    public function deleteAction()
    {
    	$this->getResponse()->appendBody('deleteAction() of '.$this->getRequest()->getControllerName());
    }

}