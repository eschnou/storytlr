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

class LifestreamPage extends Stuffpress_PageModel {

	protected $_prefix	= 'lifestream';
	
	protected $_name 	= 'Lifestream';
	
	protected $_description = 'Page where you can choose which sources or media types to include. Default is all.';
	
	public function getDefaultValues() {
		$values = array();
		$values['title'] = 'Lifestream';
		$values['id']	 = '0';
		
		$sources = $this->getAvailableSources();
		$s_ids	 = array();
		foreach($sources as $k => $v) {
			$s_ids[] = $k;
		}
		
		$types = $this->getAvailableTypes();
		$t_ids	 = array();
		foreach($types as $k => $v) {
			$t_ids[] = $k;
		}
		
		$values['sources'] 	= $s_ids;
		$values['types'] 	= $t_ids;		
		$values['sources_filter'] = 0;
		
		return $values;
	}
	
	public function getPageValues($page_id) {
		$properties = new PagesProperties(array(PagesProperties::KEY => $page_id));
		$values		= $properties->getPropertiesArray();
		$values['sources'] = @unserialize($values['sources']);	
		$values['types'] = @unserialize($values['types']);			
		return $values;
	}
	
	public function processForm($page_id, $values) {
		$properties = new PagesProperties(array(PagesProperties::KEY => $page_id));
		$properties->setProperty('sources', serialize($values['sources']));
		$properties->setProperty('types', serialize($values['types']));		
		$properties->setProperty('sources_filter', $values['sources_filter']);
	}
	
	public function getForm() {
		$form = new Stuffpress_PageForm();
			
		// Add the form element details
		$form->setMethod('post');
		$form->setName("form_page");
		$form->setTemplate('lifestream.phtml');
		
		// Create and configure title element:
		$e = $form->createElement('text', 'title',  array('label' => 'Title:', 'class' => 'width1', 'decorators' => array('ViewHelper', 'Errors')));
		$e->setRequired(true);
		$e->addValidator('stringLength', false, array(0, 32));
		$e->addFilter('StripTags');		
		$e->addFilter('StripTags');
		$form->addElement($e);
		
		// Checkbox sources_filter
		$e = $form->createElement('hidden', 'sources_filter',  array('label' => 'Sources filter:', 'decorators' => array('ViewHelper', 'Errors')));
		$form->addElement($e);	
			
		// Sources
		$e = new Zend_Form_Element_MultiCheckbox('sources', array(
			'label' => 'Sources:',
			'multiOptions' => $this->getAvailableSources(),
			'class' => 'checkbox',
			'decorators' => array('ViewHelper', 'Errors')
		));
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
		$e->setValue('lifestream');
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
	
	private function getAvailableSources() {
		$sourcesTable 	= new Sources();
		$sources 		= $sourcesTable->getSources();
		$s 				= array();
		if ($sources) foreach ($sources as $source) {
			$model = SourceModel::newInstance($source['service'], $source);
			$account = $model->getAccountName() ? "(" . $model->getAccountName() . ")" : "";
			$s[$source['id']] = $model->getServiceName() . $account;		
		}
		
		return $s;
	}
	
	private function getAvailableTypes() {
		$types = array();
		$types[SourceItem::STATUS_TYPE] = "Status";
		$types[SourceItem::LINK_TYPE] 	= "Links";
		$types[SourceItem::BLOG_TYPE] 	= "Posts";
		$types[SourceItem::IMAGE_TYPE]  = "Photos";
		$types[SourceItem::AUDIO_TYPE] 	= "Audio";
		$types[SourceItem::VIDEO_TYPE] 	= "Videos";

		return $types;
	}
}