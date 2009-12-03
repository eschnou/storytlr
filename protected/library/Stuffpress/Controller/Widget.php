<?php
class Stuffpress_Controller_Widget extends Stuffpress_Controller_Action
{
	
	protected $_prefix;
	
	public function formAction() {
		// Get, check and setup the parameters
		if (!($widget_id = $this->getRequest()->getParam("id"))) {
			throw new Stuffpress_Exception("No widget id provided to the widget controller"); 
		}
		
		// Get the current values
		$properties = new WidgetsProperties(array(Properties::KEY => $widget_id));
		$data		= $properties->getPropertiesArray(array('title'));
		
		// Get the form and populate with the current values
		$form = $this->getForm($widget_id);
		$form->populate($data);
		$this->view->form = $form;
	}
	
	public function submitAction() {
		if (!$this->getRequest()->isPost()) {
			return $this->_helper->json->sendJson(true);
		}

		$form = $this->getForm();
		if (!$form->isValid($_POST)) {
			return $this->_helper->json->sendJson($form->getErrorArray());
		}

		// Get the values and proceed
		$values 	= $form->getValues();
		$title		= $values['title'];
		$id			= $values['id'];	
		
		// Get the user
		$application = Stuffpress_Application::getInstance();
			
		// Get the widget properties
		$properties = new WidgetsProperties(array(Properties::KEY => $id, Properties::USER => $application->user->id));
		
		// Save the new properties
		$properties->setProperty('title', $title);
				
		// Ok send the result
		return $this->_helper->json->sendJson(false);
	}	
	
	private function getForm($widget_id=0) {
		$form = new Stuffpress_Form();
			
		// Add the form element details
		$form->setMethod('post');
		$form->setName("form_widget_{$widget_id}");
		
		// Create and configure title element:
		$e = $form->createElement('text', 'title',  array('label' => 'Title:', 'class' => 'width1'));
		$e->addFilter('StripTags');
		$form->addElement($e);
			
		// Add a hidden element with the widget id
		$e = $form->createElement('hidden', 'id');
		$e->setValue($widget_id);
		$e->removeDecorator('HtmlTag');
		$form->addElement($e);

		// use addElement() as a factory to create 'Post' button:
		$e = $form->createElement('button', 'save', array('label' => 'Save', 'onclick' => "onSubmitFormWidget('$this->_prefix', $widget_id);"));
		$e->setDecorators(array(
			array('ViewHelper'),
			array('HtmlTag', array('tag' => 'dd'))
		));
		$form->addElement($e);
		
		return $form;
	}
	
}
?>