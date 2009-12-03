<?php class Stuffpress_Controller_Plugin_Acl extends Zend_Controller_Plugin_Abstract
{
	protected $_acl;
	
	protected $_role;
 
    public function __construct(Zend_Acl $acl, $role)
    {
        $this->_acl 	= $acl;
        $this->_role 	= $role;
    }
 
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
		$module 	= $request->getModuleName();
		$controller = $request->getControllerName();
		$action		= $request->getActionName();
		
		$resource	= "$module:$controller";
		$privilege  = $action;
		
		// If not dispatchable, no need to check the ACL
        $dispatcher	= Zend_Controller_Front::getInstance()->getDispatcher();
        if (!$dispatcher->isDispatchable($this->getRequest())) {
        	return;
        }

		// If the resource does not exist, revert to the root
		if (!$this->_acl->has($resource)) {
			$resource = 'root';
		}
		
		if (!$this->_acl->isAllowed($this->_role, $resource, $action)) {
			// If access is not allowed and we are a guest, we forward to the auth controller
			if ($this->_role == 'guest' && Zend_Registry::isRegistered('uri')) {
				$request->setModuleName('admin')
		      			->setControllerName('auth')
		        		->setActionName('index')
		        		->setParams(array('target' => Zend_Registry::get('uri')))
		        		->setDispatched(false);				
			} else {
			// Otherwise we forward to the error controller 
			$request->setModuleName('public')
	      			->setControllerName('error')
	        		->setActionName('denied')
	        		->setParams(array('message' => "Access denied for {$this->_role} to resource $resource with privilege $privilege."))
	        		->setDispatched(false);
			}
		}
    }
}