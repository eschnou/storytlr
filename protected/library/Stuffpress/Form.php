<?php
class Stuffpress_Form extends Zend_Form
{
	public $elementDecorators = array(
        'ViewHelper',
        'Errors',
        array('Description',array('escape' => false)),
        array(array('data' 	=> 'HtmlTag'), 	array('tag' => 'td', 'class' => 'element')),
        array(array('label' => 'Label')),
        array(array('wrap' 	=> 'HtmlTag'), 	array('tag' => 'td', 'class' => 'label')),
        array(array('row' 	=> 'HtmlTag'), 	array('tag' => 'tr')),
    );
    
    public $noDecorators = array(
    	'ViewHelper', 
    	'Errors');

    public $buttonDecorators = array(
        'ViewHelper',
        array(array('data'  => 'HtmlTag'), 	array('tag' => 'td', 'class' => 'element')),
        array(array('label' => 'HtmlTag'), 	array('tag' => 'td', 'class' => 'label', 'placement' => 'prepend')),
        array(array('row'   => 'HtmlTag'), 	array('tag' => 'tr')),
    );
    
    public $groupDecorators = array(
		'FormElements', 
		array(array('data'	=> 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
		array(array('label' => 'HtmlTag'), array('tag' => 'td', 'class' => 'label', 'placement' => 'prepend'))
    );
	
    public function loadDefaultDecorators()
    {
        $this->setDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'table')),
            'Form',
        ));
    }
    
	public function getErrorArray() {

		$elements = $this->getMessages();
		$errors   = array();

		foreach($elements as $name => $error) {
			foreach($error as $code => $message) {
				$errors[] = $name.": ".$message;
			}
		}

		return $errors;
	}
}

