<?php
/**
 * Contains an ACL plugin.
 *
 * @category    Zfe
 * @package     Zfe_Controller
 * @version     $Id: Acl.php,v 1.1 2008-08-27 15:13:30 weshupne Exp $
 * @author      Jordan Moore <jordan.moore@sanctusstudios.com>
 * @copyright   Copyright © 2007-2008, Sanctus Studios LLC. All rights reserved.
 * @license     http://code.google.com/p/zfe/wiki/NewBSDLicense New BSD License
 */

/**
 * @see Zend_Controller_Plugin_Abstract
 */
require_once 'Zend/Controller/Plugin/Abstract.php';

/**
 * @see Zend_Controller_Front
 */
require_once 'Zend/Controller/Front.php';

/**
 * @see Zend_Acl
 */
require_once 'Zend/Acl.php';

/**
 * @see Zend_Acl_Resource
 */
require_once 'Zend/Acl/Resource.php';

/**
 * An ACL plugin.
 *
 * @category    Zfe
 * @package     Zfe_Controller
 * @author      Jordan Moore <jordan.moore@sanctusstudios.com>
 * @copyright   Copyright © 2007-2008, Sanctus Studios LLC. All rights reserved.
 * @license     http://code.google.com/p/zfe/wiki/NewBSDLicense New BSD License
 */
class Zfe_Controller_Plugin_Acl extends Zend_Controller_Plugin_Abstract {
    /**
     * Action performed when error occurs.
     *
     * @var string
     */
    const EXCEPTION_NOT_ALLOWED     = 'EXCEPTION_NOT_ALLOWED';
    const EXCEPTION_ROLE_NOT_FOUND  = 'EXCEPTION_ROLE_NOT_FOUND';
    const EXCEPTION_OTHER           = 'EXCEPTION_OTHER';

    /**
     * The ACL.
     *
     * @var Zend_Acl
     */
    protected $_acl;

    /**
     * The ACL role.
     *
     * @var Zend_Acl_Role_Interface
     */
    protected $_role;

    /**
     * The resource prefix.
     *
     * @var string
     */
    protected $_resourcePrefix = 'Zfe_Controller_Plugin_Acl-';

    /**
     * The resource separator.
     *
     * @var string
     */
    protected $_resourceSeparator = '~';

    /**
     * The error handler module.
     *
     * @var string
     */
    protected $_errorModule;

    /**
     * The error handler controller.
     *
     * @var string
     */
    protected $_errorController;

    /**
     * The error handler action.
     *
     * @var string
     */
    protected $_errorAction;

    /**
     * Creates an ACL plugin.
     *
     * @param   mixed   $options    An array of options.
     */
    public function __construct($options = array()) {
        $this->setOptions($options);
    }

    /**
     * Called before an action is dispatched by Zend_Controller_Dispatcher.
     *
     * @param   Zend_Controller_Request_Abstract    $request    The request.
     *
     * @throws  Zend_Exception  If the request is not allowed and the front controller is configured
     *                          to throw exceptions.
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request) {
        try {
            if (!$this->isRequestAllowed($request)) {
                /**
                 * @see Zend_Controller_Exception
                 */
                require_once 'Zend/Controller/Exception.php';
                throw new Zend_Controller_Exception('Role does not have access to requested resource');
            }
        } catch (Zend_Exception $e) {
            if (Zend_Controller_Front::getInstance()->throwExceptions()) {
                throw $e;
            }

            $errorHandler = new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);

            switch (get_class($e)) {
                case 'Zend_Acl_Exception':
                    $errorHandler->type = self::EXCEPTION_ROLE_NOT_FOUND;
                    break;
                case 'Zend_Controller_Exception':
                    $errorHandler->type = self::EXCEPTION_NOT_ALLOWED;
                    break;
                default:
                    $errorHandler->type = self::EXCEPTION_OTHER;
            }

            $errorHandler->exception    = $e;
            $errorHandler->request      = clone $request;

            $request->setParam('error_handler', $errorHandler)
                    ->setModuleName($this->getErrorHandlerModule())
                    ->setControllerName($this->getErrorHandlerController())
                    ->setActionName($this->getErrorHandlerAction());
        }
    }

    /**
     * Sets the ACL, role, resource prefix/separator, and error handler options.
     *
     * @param   array   $options    An array of options.
     *
     * @return  Zfe_Controller_Plugin_Acl
     */
    public function setOptions(array $options = array()) {
        $validOptions = array(
            'acl',
            'role',
            'resourcePrefix',
            'resourceSeparator',
            'errorHandlerModule',
            'errorHandlerController',
            'errorHandlerAction',
        );

        foreach ($validOptions as $option) {
            if (array_key_exists($option, $options)) {
                $this->{'set' . $option}($options[$option]);
            }
        }

        return $this;
    }

    /**
     * Sets the ACL.
     *
     * @param   Zend_Acl    $acl    An ACL.
     *
     * @return  Zfe_Controller_Plugin_Acl
     */
    public function setAcl(Zend_Acl $acl) {
        $this->_acl = $acl;

        return $this;
    }

    /**
     * Returns the ACL.
     *
     * @return  Zend_Acl    The ACL.
     */
    public function getAcl() {
        if (null === $this->_acl) {
            $this->_acl = new Zend_Acl();
        }

        return $this->_acl;
    }

    /**
     * Sets the ACL role.
     *
     * @param   Zend_Acl_Role_Interface $role   An ACL role.
     *
     * @return  Zfe_Controller_Plugin_Acl
     */
    public function setRole(Zend_Acl_Role_Interface $role) {
        $this->_role = $role;

        if (!$this->getAcl()->hasRole($role)) {
            $this->getAcl()->addRole($role);
        }

        return $this;
    }

    /**
     * Returns the ACL role.
     *
     * @return  Zend_Acl_Role_Interface An ACL role.
     */
    public function getRole() {
        return $this->_role;
    }

    /**
     * Sets the resource prefix.
     *
     * @param   string  $prefix A resource ID prefix.
     *
     * @return  Zfe_Controller_Plugin_Acl
     */
    public function setResourcePrefix($prefix) {
        $this->_resourcePrefix = (string) $prefix;

        return $this;
    }

    /**
     * Returns the resource prefix.
     *
     * @return  string  The resource ID prefix.
     */
    public function getResourcePrefix() {
        return $this->_resourcePrefix;
    }

    /**
     * Sets the resource separator.
     *
     * @param   string  $separator  A resource ID separator.
     *
     * @return  Zfe_Controller_Plugin_Acl
     */
    public function setResourceSeparator($separator) {
        $this->_resourceSeparator = (string) $separator;

        return $this;
    }

    /**
     * Returns the resource separator.
     *
     * @return  string  A resource ID separator.
     */
    public function getResourceSeparator() {
        return $this->_resourceSeparator;
    }

    /**
     * Sets the error handler module.
     *
     * @param   string  $module The module to forward to when an error occurs.
     *
     * @return  Zfe_Controller_Plugin_Acl
     */
    public function setErrorHandlerModule($module) {
        $this->_errorModule = (string) $module;

        return $this;
    }

    /**
     * Returns the error handler module, defaulting to the Zend_Controller_Plugin_ErrorHandler plugin
     * or the dispatcher's default module.
     *
     * @return  string  The module to forward to when an error occurs.
     */
    public function getErrorHandlerModule() {
        if (null === $this->_errorModule) {
            $frontController = Zend_Controller_Front::getInstance();

            if ($errorHandler = $frontController->getPlugin('Zend_Controller_Plugin_ErrorHandler')) {
                $this->_errorModule = $errorHandler->getErrorHandlerModule();
            } else {
                $this->_errorModule = $frontController->getDispatcher()->getDefaultModule();
            }
        }

        return $this->_errorModule;
    }

    /**
     * Sets the error handler controller.
     *
     * @param   string  $controller The controller to forward to when an error occurs.
     *
     * @return  Zfe_Controller_Plugin_Acl
     */
    public function setErrorHandlerController($controller) {
        $this->_errorController = (string) $controller;

        return $this;
    }

    /**
     * Returns the error handler controller, defaulting to the Zend_Controller_Plugin_ErrorHandler plugin..
     *
     * @return  string  The controller to forward to when an error occurs.
     */
    public function getErrorHandlerController() {
        if (null === $this->_errorController) {
            $frontController = Zend_Controller_Front::getInstance();

            if ($errorHandler = $frontController->getPlugin('Zend_Controller_Plugin_ErrorHandler')) {
                $this->_errorController = $errorHandler->getErrorHandlerController();
            } else {
                $this->_errorController = 'error';
            }
        }

        return $this->_errorController;
    }

    /**
     * Sets the error handler action.
     *
     * @param   string  $action The action to forward to when an error occurs.
     *
     * @return  Zfe_Controller_Plugin_Acl
     */
    public function setErrorHandlerAction($action) {
        $this->_errorAction = (string) $action;

        return $this;
    }

    /**
     * Returns the error handler action, defaulting to the Zend_Controller_Plugin_ErrorHandler plugin..
     *
     * @return  string  The action to forward to when an error occurs.
     */
    public function getErrorHandlerAction() {
        if (null === $this->_errorAction) {
            $frontController = Zend_Controller_Front::getInstance();

            if ($errorHandler = $frontController->getPlugin('Zend_Controller_Plugin_ErrorHandler')) {
                $this->_errorAction = $errorHandler->getErrorHandlerAction();
            } else {
                $this->_errorAction = 'error';
            }
        }

        return $this->_errorAction;
    }

    /**
     * Returns a resource ID for the given module/controller/action.
     *
     * @param   string  $module     A module.
     * @param   string  $controller A controller.
     * @param   string  $action     An action.
     *
     * @return  string  The ACL resource ID for the given module/controller/action.
     */
    public function getResourceId($module, $controller = false, $action = false) {
        $frontController = Zend_Controller_Front::getInstance();

        if (empty($module)) {
            $module = $frontController->getDefaultModule();
        }

        $resourceId = $this->getResourcePrefix() . ((string) $module);

        if (false !== $controller) {
            if (empty($controller)) {
                $controller = $frontController->getDefaultControllerName();
            }

            $resourceId .= $this->getResourceSeparator() . ((string) $controller);

            if (false !== $action) {
                if (empty($action)) {
                    $action = $frontController->getDefaultAction();
                }

                $resourceId .= $this->getResourceSeparator() . ((string) $action);
            }
        }

        return $resourceId;
    }

    /**
     * Checks if the role is allowed to access to the given ACL resource or resource ID.
     *
     * @param   Zend_Acl_Resource_Interface|string  $resource   An optional ACL resource or resource ID.
     * @param   string                              $privilege  An optional privilege.
     *
     * @return  bool    If the role has access to the given ACL resource or resource ID.
     */
    public function isAllowed($resource = null, $privilege = null) {
        return $this->getAcl()->isAllowed($this->getRole(), $resource, $privilege);
    }

    /**
     * Checks if the role is allowed to access to the given module/controller/action.
     *
     * @param   string  $module     A module.
     * @param   string  $controller A controller.
     * @param   string  $action     An action.
     * @param   string  $privilege  An optional privilege.
     *
     * @return  bool    If the role is allowed to access the given module/controller/action.
     */
    public function isActionAllowed($module, $controller, $action, $privilege = null) {
        if (($this->getErrorHandlerModule() == $module)
         && ($this->getErrorHandlerController() == $controller)
         && ($this->getErrorHandlerAction() == $action)) {
            return true;
        }

        $acl        = $this->getAcl();
        $action     = $this->getResourceId($module, $controller, $action);
        $controller = $this->getResourceId($module, $controller);
        $module     = $this->getResourceId($module);

        if (!$acl->has($module)) {
            $acl->add(new Zend_Acl_Resource($module));
        }

        if (!$acl->has($controller)) {
            $acl->add(new Zend_Acl_Resource($controller), $module);
        }

        if (!$acl->has($action)) {
            $acl->add(new Zend_Acl_Resource($action), $controller);
        }

        return $this->isAllowed($action, $privilege);
    }

    /**
     * Checks if the role is allowed to access the given request.
     *
     * @param   Zend_Controller_Request_Abstract    $request    A request.
     * @param   string                              $privilege  An optional privilege.
     *
     * @return  bool    If the role is allowed to access the given request.
     */
    public function isRequestAllowed(Zend_Controller_Request_Abstract $request, $privilege = null) {
        if (!Zend_Controller_Front::getInstance()->getDispatcher()->isDispatchable($request)) {
            return true;
        }

        return $this->isActionAllowed(
            $request->getModuleName(),
            $request->getControllerName(),
            $request->getActionName(),
            $privilege
        );
    }

    /**
     * Checks if the role is allowed to access the given route.
     *
     * @param   Zend_Controller_Router_Route|string $route      A route or route name.
     * @param   array                               $data       Optional parameters to assemble the route.
     * @param   mixed                               $privilege  An optional privilege.
     *
     * @return  bool    If the role is allowed to access the given route.
     */
    public function isRouteAllowed($route, array $data = array(), $privilege = null) {
        $router = Zend_Controller_Front::getInstance()->getRouter();

        if (!$route instanceof Zend_Controller_Router_Route) {
            $route = $router->getRoute((string) $route);
        }

        $request = new Zend_Controller_Request_Http();

        $request->setPathInfo($route->assemble($data));

        $router->route($request);

        return $this->isRequestAllowed($request, $privilege);
    }
}