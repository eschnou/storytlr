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
class GooglebuzzModel extends SourceModel {

	protected $_name 	= 'googlebuzz_data';

	protected $_prefix = 'googlebuzz';

	protected $_search  = 'content';

	protected $_update_tweet = "Did %d things on Google Buzz on my lifestream %s";

	public function getServiceName() {
		return "Google Buzz";
	}

	public function isStoryElement() {
		return true;
	}

	public function getServiceURL() {
		return 'http://www.google.com/profiles/' . $this->getProperty('username');
	}

	public function getServiceDescription() {
		return "Google Buzz is Google's social sharing network.";
	}

	public function getAccountName() {
		if( $name = $this->getProperty( 'username' ) ) {
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
		$url = 'http://buzz.googleapis.com/feeds/' . $this->getProperty( 'username' ) . '/public/posted';

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT,'Storytlr/1.0');

		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close ($curl);

		if ($http_code != 200) {
			throw new Stuffpress_Exception( "Google Buzz returned http status $http_code for url: $url", $http_code );
		}

		if (!($items = simplexml_load_string($response))) {
			throw new Stuffpress_Exception( "Google Buzz did not return any result", 0 );
		}

		if ( count( $items->entry ) == 0 ) { return; }

		$items = $this->processItems( $items->entry );
		$this->markUpdated();
		return $items;
	}

	private function processItems( $items ) {
		$result = array();
		foreach ($items as $item) {
			$data = array();
			$data['title'] = $item->title;
			$data['published'] = strtotime( $item->published );
			$data['content'] = $item->content;
			$data['link'] = $item->link['href'];
			$data['buzz_id'] = $item->id;
			$id = $this->addItem( $data, $data['published'], SourceItem::STATUS_TYPE, false, false, false, $data['title'] );
			if ($id) $result[] = $id;
		}
		return $result;
	}

	public function getConfigForm($populate=false) {
		$form = new Stuffpress_Form();

		// Add the username element
		$element = $form->createElement('text', 'username', array('label' => 'Username', 'decorators' => $form->elementDecorators));
		$element->setRequired(true);
		$element->setDescription( '<div class="help">This is either your Google username, or a very long number. Depending if you have activated or not the pretty profile URL feature. In either case, just look <a href="http://www.google.com/profiles/me">here</a> and use the string appearing after the http://www.google.com/profiles/.' );
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

		if($values['username'] != $this->getProperty('username')) {
			$this->_properties->setProperty('username',   $values['username']);
			$update = true;
		}

		return $update;
	}
}
