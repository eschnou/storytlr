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

class LinkPage extends Stuffpress_PageModel {

	protected $_prefix	= 'link';
	
	protected $_name 	= 'External link';
	
	protected $_description = 'Not a real page but a link to an external page e.g. another blog.';

	public function getDefaultValues() {
		$values = array();
		$values['title'] = '';
		$values['id']	 = '0';	
		$values['url']	 = '';	
		return $values;
	}
	
	public function processForm($page_id, $values) {
		$properties = new PagesProperties(array(PagesProperties::KEY => $page_id));
		$properties->setProperty('url', $values['url']);
	}
	
	public function getForm() {
		$form = new Stuffpress_Form();
			
		// Add the form element details
		$form->setMethod('post');
		$form->setName("form_page");
		
		// Create and configure title element:
		$e = $form->createElement('text', 'title',  array('label' => 'Title:', 'class' => 'width1'));
		$e->setRequired(true);
		$e->addValidator('stringLength', false, array(0, 32));
		$e->addFilter('StripTags');		
		$form->addElement($e);		

		// Create and configure title element:
		$e = $form->createElement('text', 'url',  array('label' => 'Url:', 'class' => 'width1'));
		$e->setRequired(true);
		$e->addValidator('stringLength', false, array(0, 128));
		$e->addFilter('StripTags');		
		$form->addElement($e);	
		
		// Add a hidden element with the page id
		$e = $form->createElement('hidden', 'id');
		$e->removeDecorator('HtmlTag');
		$form->addElement($e);
		
		// Add a hidden element with the  page type
		$e = $form->createElement('hidden', 'type');
		$e->setValue('link');
		$e->removeDecorator('HtmlTag');
		$form->addElement($e);

		// Add a submit button
		$button = $form->createElement('submit', 'save', array('onClick' => 'onFormSubmit(); return false;' , 'decorators' => array('ViewHelper')));
		$form->addElement($button);
		
		// Add a cancel button
		$element = $form->createElement('button', 'cancel', array('decorators' => array('ViewHelper')));
		$element->setAttrib('onClick', 'history.go(-1);return false;');
		$form->addElement($element);
		
		// Group elements
		$form->addDisplayGroup(array('save', 'cancel'), 'buttons', array('decorators' => $form->groupDecorators));							 

		return $form;
	}
}