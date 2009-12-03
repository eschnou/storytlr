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

class DashboardPage extends Stuffpress_PageModel {

	protected $_prefix	= 'dashboard';
	
	protected $_name 	= 'Dashboard';
	
	protected $_description = 'Overview page with latest items of all your content.';
	
	public function getDefaultValues() {
		$values = array();
		$values['title'] = 'Home';
		$values['id']	 = '0';
		
		$types = $this->getAvailableTypes();
		$t_ids	 = array();
		foreach($types as $k => $v) {
			$t_ids[] = $k;
		}
		
		$values['types'] 	= $t_ids;		
		
		return $values;
	}
	
	public function getPageValues($page_id) {
		$properties = new PagesProperties(array(PagesProperties::KEY => $page_id));
		$values		= $properties->getPropertiesArray();
		$values['types'] = @unserialize($values['types']);			
		return $values;
	}
	
	public function processForm($page_id, $values) {
		$properties = new PagesProperties(array(PagesProperties::KEY => $page_id));
		$properties->setProperty('types', serialize($values['types']));		
	}
	
	public function getForm() {
		$form = new Stuffpress_PageForm();
			
		// Add the form element details
		$form->setMethod('post');
		$form->setName("form_page");
		$form->setTemplate('dashboard.phtml');
		
		// Create and configure title element:
		$e = $form->createElement('text', 'title',  array('label' => 'Title:', 'class' => 'width1', 'decorators' => array('ViewHelper', 'Errors')));
		$e->setRequired(true);
		$e->addValidator('stringLength', false, array(0, 32));
		$e->addFilter('StripTags');		
		$e->addFilter('StripTags');
		$form->addElement($e);
			
		// Type
		$e = new Zend_Form_Element_MultiCheckbox('types', array(
			'label' => 'Types:',
			'multiOptions' => $this->getAvailableTypes(),
			'class' => 'checkbox',
			'decorators' => array('ViewHelper', 'Errors')
		));
		$form->addElement($e);
		
		// Add a hidden element with the page id
		$e = $form->createElement('hidden', 'id');
		$e->removeDecorator('HtmlTag');
		$form->addElement($e);
		
		// Add a hidden element with the  page type
		$e = $form->createElement('hidden', 'type');
		$e->setValue($this->_prefix);
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
	
	private function getAvailableTypes() {
		$types = array();
		$types[SourceItem::STATUS_TYPE] = "Status";
		$types[SourceItem::LINK_TYPE] 	= "Links";
		$types[SourceItem::BLOG_TYPE] 	= "Posts";
		$types[SourceItem::IMAGE_TYPE]  = "Photos";
		$types[SourceItem::AUDIO_TYPE] 	= "Audio";
		$types[SourceItem::VIDEO_TYPE] 	= "Videos";
		$types[SourceItem::STORY_TYPE] 	= "Stories";
		
		return $types;
	}
}