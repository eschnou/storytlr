<?php
/*
 *    Copyright 2008-2009 Laurent Eschenauer and Alard Weisscher
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 *  
 */

class Admin_DesignController extends Admin_BaseController
{

	protected $_section = 'config';
	
	public function indexAction() {
		// Get the user properties
		$values 	= $this->_properties->getProperties(array("title", "subtitle", "background_image", "header_image", "theme", "css_enabled", "css_content", "has_colors"));
		$colors		= $this->_properties->getProperties(array("color_title", "color_subtitle", "color_sidebar_border", "color_background", "color_link", "color_sidebar_text", "color_sidebar_header"));	

		// Get the form and assign the values
		$form = $this->getForm();
		$form->populate($values);
		$this->view->form = $form;
				
		// Get the color form
		$form = $this->getFormColors();		
		$this->view->formtheme = $form;
		
		// Get the css form
		$form = $this->getFormCss();
		$form->populate($values);		
		$this->view->formcss = $form;
		
		// Do we have a background image ?
		if (isset($values['background_image'])) {
			$this->view->background_image = $values['background_image'];
		}
		
		// Do we have a header image ?
		if (isset($values['header_image'])) {
			$this->view->header_image = $values['header_image'];
		}
		
		// Get available themes
		$this->view->themes = Themes::getAvailableThemes();
		$this->view->theme	= $this->_properties->getProperty('theme');
		
		// Custom css & colors
		$this->view->css_enabled = $values['css_enabled'];
		$this->view->css_content = $values['css_content'];
		$this->view->colors		 = $colors;
		$this->view->has_colors	 = $values['has_colors'];
		$this->view->onload		 = "onDesignLoad();";
		
		// Get errror and status messages
		$this->view->status_messages	= $this->getStatusMessages();
		$this->view->error_messages		= $this->getErrorMessages();
		
		// Common view elements
		$this->common();
		
		// Specific scripts
		$this->view->headScript()->appendFile('js/tab/tab.js');
		$this->view->headScript()->appendFile('js/yahoo/yahoo.color.js');
		$this->view->headScript()->appendFile('js/colorpicker/colorpicker.js');
		$this->view->headScript()->appendFile('js/controllers/design.js');
		
		// Specific CSS
		$this->view->headLink()->appendStylesheet('style/colorpicker.css');
	}

	public function submitAction()
	{
		// Validate the form and extract the values
		if (!$this->getRequest()->isPost()) {
			return $this->_helper->json->sendJson(true);
		}

		$form = $this->getForm();
		if (!$form->isValid($_POST)) {
			return $this->_helper->json->sendJson($form->getErrorMessages());
		}

		// Get the values and proceed
		$values 	= $form->getValues();
		$title		= $values['title'];
		$subtitle	= $values['subtitle'];		

		// Save the new values
		$this->_properties->setProperty('title', $title);
		$this->_properties->setProperty('subtitle', $subtitle);
			
		// Ok
		return $this->_helper->json->sendJson(false);
	}
	
	public function savecolorsAction()
	{
		// Validate the form and extract the values
		if (!$this->getRequest()->isPost()) {
			return $this->_helper->json->sendJson(true);
		}

		$form = $this->getFormColors();
		if (!$form->isValid($_POST)) {
			return $this->_helper->json->sendJson($form->getErrorMessages());
		}

		// Get the values and proceed
		$values 	= $form->getValues();
		$c0			= $values['color_title'];				
		$c1			= $values['color_subtitle'];		
		$c2			= $values['color_sidebar_border'];			
		$c3			= $values['color_background'];	
		$c4			= $values['color_link'];	
		$c5			= $values['color_sidebar_text'];
		$c6			= $values['color_sidebar_header'];
								
		// Save the new values
		$this->_properties->setProperty('has_colors', 1);
		$this->_properties->setProperty('color_title', $c0);
		$this->_properties->setProperty('color_subtitle', $c1);
		$this->_properties->setProperty('color_sidebar_border', $c2);
		$this->_properties->setProperty('color_background', $c3);		
		$this->_properties->setProperty('color_link', $c4);
		$this->_properties->setProperty('color_sidebar_text', $c5);
		$this->_properties->setProperty('color_sidebar_header', $c6);
						
		// Ok
		return $this->_helper->json->sendJson(false);
	}
	
	public function savethemeAction()
	{
		// Setting the theme
		if ($theme = $this->_getParam('theme')) {
			$this->_properties->setProperty('theme', $theme);	
			$this->_properties->setProperty('has_colors', 0);
		}
		
		// Done
		return $this->_helper->json->sendJson(false);
	}
	
	public function savecssAction()
	{
		// Validate the form and extract the values
		if (!$this->getRequest()->isPost()) {
			return $this->_helper->json->sendJson(true);
		}

		$form = $this->getFormCss();
		if (!$form->isValid($_POST)) {
			return $this->_helper->json->sendJson($form->getErrorMessages());
		}

		// Get the values and proceed
		$values 	 = $form->getValues();
		$css_enabled = $values['css_enabled'];
		$css_content = $values['css_content'];
		
		// Filter the input
		$css_enabled = $css_enabled ? 1 : 0;
		$css_content = strip_tags($css_content);

		// Save the new values
		$this->_properties->setProperty('css_enabled', $css_enabled);
		$this->_properties->setProperty('css_content', $css_content);
			
		// Ok
		return $this->_helper->json->sendJson(false);
	}

	public function clearimageAction() {
		// What are we deleting ? 
		$image		= $this->_getParam('image');
		$property 	= "{$image}_image";
		
		// Delete file and property
		$files = new Files();
		$file  = $this->_properties->getProperty($property);
		$this->_properties->deleteProperty($property);
		if ($file) $files->deleteFile($file);
		return $this->_helper->json->sendJson(false);
	}
	
	public function uploadimageAction() {
		$this->_forward('uploadimage', 'file', 'public', array('source' => 'design'));
	}

	private function getForm() {
		$form = new Stuffpress_Form();
   
		// Add the form element details
		$form->setMethod('post');
		$form->setName('formDesign');

		// Title
		$e = $form->createElement('text', 'title',  array('label' => 'Title', 'class' => 'width1', 'decorators' => $form->elementDecorators));
		$form->addElement($e);
		
		// Subtitle
		$e = $form->createElement('text', 'subtitle',  array('label' => 'Subtitle', 'class' => 'width1', 'decorators' => $form->elementDecorators));
		$form->AddElement($e);
		
		// use addElement() as a factory to create 'Post' button:
		$form->addElement('button', 'save', array('label' => 'Save', 'onclick' => "submitFormDesign();", 'decorators' => $form->buttonDecorators));
		
		return $form;
	}
	
	private function getFormCss() {
		$form = new Stuffpress_Form();
   
		// Add the form element details
		$form->setMethod('post');
		$form->setName('formCss');

		// Enabled
		$e = $form->createElement('checkbox', 'css_enabled',  array('label' => 'Enable CSS', 'decorators' => $form->elementDecorators));
		$form->addElement($e);
		
		// Content
		$e = $form->createElement('textarea', 'css_content',  array('label' => 'User css', 'decorators' => $form->elementDecorators));
		$form->AddElement($e);
		
		// use addElement() as a factory to create 'Post' button:
		$form->addElement('button', 'save', array('label' => 'Save', 'onclick' => "submitFormCss();", 'decorators' => $form->buttonDecorators));
		
		return $form;
	}
	
	private function getFormColors() {
		$form = new Stuffpress_Form();
   
		// Add the form element details
		$form->setMethod('post');
		$form->setName('formTheme');

		// Colors
		$element = $form->createElement('text', 'color_title', array('title' => 'Title', 'value' => "ffffff"));
		$element->setDecorators(array(array('ViewHelper')));
		$form->addElement($element);	
		
		$element = $form->createElement('text', 'color_subtitle', array('title' => 'Subtitle', 'value' => "ffffff"));
		$element->setDecorators(array(array('ViewHelper')));
		$form->addElement($element);

		$element = $form->createElement('text', 'color_sidebar_border', array('title' => 'Sidebar border', 'value' => "ffffff"));
		$element->setDecorators(array(array('ViewHelper')));
		$form->addElement($element);
				
		$element = $form->createElement('text', 'color_background', array('title' => 'Sidebar background', 'value' => "ffffff"));
		$element->setDecorators(array(array('ViewHelper')));
		$form->addElement($element);
		
		$element = $form->createElement('text', 'color_link', array('title' => 'Link color', 'value' => "ffffff"));
		$element->setDecorators(array(array('ViewHelper')));
		$form->addElement($element);
		
		$element = $form->createElement('text', 'color_sidebar_text', array('title' => 'Sidebar text', 'value' => "ffffff"));
		$element->setDecorators(array(array('ViewHelper')));
		$form->addElement($element);
		
		$element = $form->createElement('text', 'color_sidebar_header', array('title' => 'Sidebar headers', 'value' => "ffffff"));
		$element->setDecorators(array(array('ViewHelper')));
		$form->addElement($element);
		
		$form->addElement('button', 'save', array('label' => 'Save colors', 'onclick' => "submitFormColors();", 'decorators' => array('ViewHelper')));
		$form->addElement('button', 'reset', array('label' => 'Reset', 'onclick' => "resetFormColors();", 'decorators' => array('ViewHelper')));
		
		return $form;
	}
}