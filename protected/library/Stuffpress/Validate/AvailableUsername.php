<?php

class Stuffpress_Validate_AvailableUsername extends Zend_Validate_Abstract
{
    const  UNAVAILABLE = 'unavailable';
    
    /**
     * Database Connection
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_zendDb = null;

    /**
     * $_tableName - the table name to check
     *
     * @var string
     */
    protected $_tableName = null;

    /**
     * $_identityColumn - the column to use as the identity
     *
     * @var string
     */
    protected $_identityColumn = null;
    

    protected $_messageTemplates = array(
        self::UNAVAILABLE => "'%value%' is already taken"
    );

    /**
     * __construct() - Sets configuration options
     *
     * @param  Zend_Db_Adapter_Abstract $zendDb
     * @param  string                   $tableName
     * @param  string                   $identityColumn
     * @param  string                   $credentialColumn
     * @param  string                   $credentialTreatment
     * @return void
     */
    public function __construct(Zend_Db_Adapter_Abstract $zendDb, $tableName = null, $identityColumn = null)
    {
        $this->_zendDb = $zendDb;

        if (null !== $tableName) {
            $this->setTableName($tableName);
        }

        if (null !== $identityColumn) {
            $this->setIdentityColumn($identityColumn);
        }
    }

    /**
     * setTableName() - set the table name to be used in the select query
     *
     * @param  string $tableName
     * @return Zend_Auth_Adapter_DbTable Provides a fluent interface
     */
    public function setTableName($tableName)
    {
        $this->_tableName = $tableName;
        return $this;
    }

    /**
     * setIdentityColumn() - set the column name to be used as the identity column
     *
     * @param  string $identityColumn
     * @return Zend_Auth_Adapter_DbTable Provides a fluent interface
     */
    public function setIdentityColumn($identityColumn)
    {
        $this->_identityColumn = $identityColumn;
        return $this;
    }
    
    
    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if and only if $value is an available username
     *
     * @param  string $value
     * @throws Zend_Validate_Exception if there is a fatal error
     * @return boolean
     */        
    public function isValid($value)
    {
        $this->_setValue($value);
        $dbSelect = $this->_zendDb->select();
        $dbSelect->from($this->_tableName);
        $dbSelect->where($this->_zendDb->quoteIdentifier($this->_identityColumn, true) . ' = ?', $value);
        $users = $this->_zendDb->fetchAll($dbSelect->__toString());
        if (count($users)) {
            $this->_error();
            return false;
        }
        
        if (in_array($value, array('storytlr', 'mail', 'search', 'support', 'feedback', 'admin',
        						   'info', 'host', 'contact', 'root', 'webmaster', 'dns', 'fuck', 
        						   'suck', 'forum', 'wiki', 'bugs', 'beta', 'user', 'users', 'username'))) {
        	$this->_error();
            return false;
        }

        return true;
    }
}

