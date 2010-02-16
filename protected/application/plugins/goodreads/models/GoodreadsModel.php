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
class GoodreadsModel extends SourceModel {

	protected $_name 	= 'goodreads_data';

	protected $_prefix = 'goodreads';

	protected $_search  = 'content, title';

	protected $_update_tweet = "Posted %d things at Goodreads on my lifestream %s";

	public function getServiceName() {
		return "Goodreads";
	}

	public function isStoryElement() {
		return true;
	}

	public function getServiceURL() {
		return 'http://www.goodreads.com/user/show/' . $this->getProperty('userid');
	}

	public function getServiceDescription() {
		return 'Goodreads is the largest social network for readers in the world.';
	}

	public function getAccountName() {
		if ($name = $this->getProperty('userid')) {
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
		$url	= 'http://www.goodreads.com/user/updates_rss/' . $this->getProperty('userid');

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT,'Storytlr/1.0');

		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close ($curl);

		if ( $http_code != 200 ) {
			throw new Stuffpress_Exception( "Goodreads returned http status $http_code for url: $url", $http_code );
		}

		if ( ! ( $xml = simplexml_load_string( $response ) ) ) {
			throw new Stuffpress_Exception( "Goodreads did not return any result", 0 );
		}

		if ( count( $xml->channel->item ) == 0 ) { return; }

		$items = $this->processItems( $xml->channel->item );
		$this->markUpdated();
		return $items;
	}

	private function processItems ( $items ) {
		$result = array();
		foreach( $items as $item ) {
			$data = array();
			$data['guid'] = $item->title;
			$data['title'] = $item->title;
			$data['content'] = $item->description;
			$data['link'] = $item->link;
			$data['published'] = strtotime( $item->pubDate );
			$id = $this->addItem( $data, $data['published'], SourceItem::LINK_TYPE, false, false, false, $data['title'] );
			if ($id) $result[] = $id;
		}
		return $result;
	}

	public function getConfigForm($populate=false) {
		$form = new Stuffpress_Form();

		// Add the username element
		$element = $form->createElement('text', 'userid', array('label' => 'User ID', 'decorators' => $form->elementDecorators));
		$element->setRequired(true);
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
