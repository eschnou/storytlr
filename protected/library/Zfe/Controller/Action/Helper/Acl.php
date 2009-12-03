<?php
/**
 * Contains an ACL action helper.
 *
 * @category    Zfe
 * @package     Zfe_Controller
 * @version     $Id: Acl.php,v 1.1 2008-08-27 15:13:30 weshupne Exp $
 * @author      Jordan Moore <jordan.moore@sanctusstudios.com>
 * @copyright   Copyright © 2007-2008, Sanctus Studios LLC. All rights reserved.
 * @license     http://code.google.com/p/zfe/wiki/NewBSDLicense New BSD License
 */

/**
 * @see Zend_Controller_Action_Helper_Abstract
 */
require_once 'Zend/Controller/Action/Helper/Abstract.php';

/**
 * @see Zfe_Controller_Plugin_Acl
 */
require_once 'Zfe/Controller/Plugin/Acl.php';

/**
 * An ACL action helper.
 *
 * @category    Zfe
 * @package     Zfe_Controller
 * @author      Jordan Moore <jordan.moore@sanctusstudios.com>
 * @copyright   Copyright © 2007-2008, Sanctus Studios LLC. All rights reserved.
 * @license     http://code.google.com/p/zfe/wiki/NewBSDLicense New BSD License
 */
class Zfe_Controller_Action_Helper_Acl extends Zend_Controller_Action_Helper_Abstract {
    /**
     * An ACL plugin.
     *
     * @var Zfe_Controller_Plugin_Acl
     */
    protected $_plugin;

    /**
     * Relays all calls to the ACL plugin.
     *
     * @param   string  $name       The name of the method.
     * @param   array   $arguments  Arguments to pass to the method.
     *
     * @throws  Zend_Controller_Action_Exception    If the ACL helper and plugin don't implement the method.
     *
     * @return  mixed   The return value of the method.
     */
    public function __call($name, array $arguments) {
        $callback = array($this->getPlugin(), $name);

        if (is_callable($callback)) {
            return call_user_func_array($callback, $arguments);
        }

        /**
         * @see Zend_Controller_Action_Exception
         */
        require_once 'Zend/Controller/Action/Exception.php';
        throw new Zend_Controller_Action_Exception(
            'Call to ' . __CLASS__ . '::' . $name . ' could not be handled by ' .
            get_class($this) . ' or ' . get_class($this->getPlugin())
        );
    }

    /**
     * Returns an ACL plugin.
     *
     * @return  Zfe_Controller_Plugin_Acl
     */
    public function getPlugin() {
        if (null === $this->_plugin) {
            $frontController = $this->getFrontController();

            if (!$frontController->hasPlugin('Zfe_Controller_Plugin_Acl')) {
                $this->_plugin = new Zfe_Controller_Plugin_Acl();

                $frontController->registerPlugin($this->_plugin);
            } else {
                $this->_plugin = $frontController->getPlugin('Zfe_Controller_Plugin_Acl');
            }
        }

        return $this->_plugin;
    }
}