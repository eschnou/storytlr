<?php
/*
*    Copyright 2008-2009 Laurent Eschenauer and Alard Weisscher
*    Copyright 2010 John Hobbs
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
class GoogleplusModel extends SourceModel {

	protected $_name 	= 'googleplus_data';

	protected $_prefix = 'googleplus';

	protected $_search  = 'content';

	protected $_update_tweet = "Did %d things on Google+ on my lifestream %s";

	public function getServiceName() {
		return "Google+";
	}

	public function isStoryElement() {
		return true;
	}

	public function getServiceURL() {
		return 'https://plus.google.com/' . $this->getProperty('userid');
	}

	public function getServiceDescription() {
		return "Google+ is Google's newest and shiniest social network.";
	}

	public function getAccountName() {
		if( $name = $this->getProperty( 'userid' ) ) {
			return $name;
		}
		else {
			return false;
		}
	}

	public function getTitle() {
		return $this->getServiceName();
	}

	public function importData() {
		$items = $this->updateData();
		$this->setImported( true );
		return $items;
	}

	public function updateData() {
		$url = 'https://plus.google.com/_/profiles/get/' . $this->getProperty( 'userid' );

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT,'Storytlr/1.0');

		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close ($curl);

		if ($http_code != 200) {
			throw new Stuffpress_Exception( "Google+ returned http status $http_code for url: $url", $http_code );
		}

		$data = substr($response,4);
		$pattern = '/\["up","(.*?)","(.*?)","(.*?)","([^"\\\\]*(?:\\\\.[^"\\\\]*)*)",(\d*),.*?,"(\d*)\/posts\/(\w*)",.*?1,0\]/s';
		preg_match_all($pattern, $data, $items,PREG_SET_ORDER);
			
		if (!$items) {
			throw new Stuffpress_Exception( "Google+ did not return any result", 0 );
		}

		$items = $this->processItems($items);

		$this->markUpdated();
		return $items;
	}

	private function processItems($items) {
		$result = array();
		foreach ($items as $item) {
			$data = array();
			
			$content = $item[4];
			$content = str_replace("\\\"",   "\"", $content);
			$content = str_replace("\u003c", "<", $content);
			$content = str_replace("\u003d", "=", $content);
			$content = str_replace("\u003e", ">", $content);
			$content = str_replace("\u0026", "&", $content);
			
			$data['title'] = $content;
			$data['published'] = (int) substr($item[5], 0, 10);
			$data['content'] = $content;
			$data['link'] = "http://plus.google.com/" . $item[6] . "/posts/" . $item[7];
			$data['plus_id'] = $item[7];
			$id = $this->addItem( $data, $data['published'], SourceItem::STATUS_TYPE, false, false, false, $data['title'] );
			if ($id) $result[] = $id;
		}
		return $result;
	}

	public function getConfigForm($populate=false) {
		$form = new Stuffpress_Form();

		// Add the username element
		$element = $form->createElement('text', 'userid', array('label' => 'User ID', 'decorators' => $form->elementDecorators));
		$element->setRequired(true);
		$element->setDescription( '<div class="help">You Google+ id is a very long number that appears at the end of your profile link. If you are logged into +, you can just look <a href="http://www.google.com/profiles/me">here</a>.' );
		$form->addElement($element);

		// Populate
		if($populate) {
			$values  = $this->getProperties();
			$form->populate($values);
		}

		return $form;
	}

	public function processConfigForm($form) {
		$values = $form->getValues();
		$update	= false;

		if($values['userid'] != $this->getProperty('userid')) {
			$this->_properties->setProperty('userid',   $values['userid']);
			$update = true;
		}

		return $update;
	}
}
