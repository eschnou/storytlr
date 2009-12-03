<?php
class Stuffpress_PageForm extends Stuffpress_Form
{
	private $_template;
	
	public function setTemplate($name) {
		$this->_template   = $name;
	}
	
	public function render(Zend_View_Interface $view=null) {
		if (!$this->_template) {
			return parent::render($view);
		} else {
			$root = Zend_Registry::get('root');
			$view = new Zend_View();
			$view->setEncoding('UTF-8');
			$view->setScriptPath("$root/application/pages/views/forms/");
			$view->form = $this;

			return $view->render($this->_template);
		}
	}
	
}

