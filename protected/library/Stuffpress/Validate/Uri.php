<?php
require_once 'Zend/Validate/Abstract.php';
require_once 'Zend/Uri.php';


class Stuffpress_Validate_Uri extends Zend_Validate_Abstract
{
    const MSG_URI = 'msgUri';

    protected $_messageTemplates = array(
        self::MSG_URI => "Invalid URI",
    );

    public function isValid($value)
    {
        $this->_setValue($value);

        //Validate the URI
        $valid = Zend_Uri::check($value);
        
        //Return validation result TRUE|FALSE   
        if ($valid)  {
            return true;
        } else {
            $this->_error(self::MSG_URI);
            return false;
        
        }

    }
}