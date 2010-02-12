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
class FoursquareModel extends SourceModel {

	protected $_name 	= 'foursquare_data';

	protected $_prefix = 'foursquare';

	protected $_search  = 'content, title';

	protected $_update_tweet = "Checked in %d times at foursquare on my lifestream %s";

	public function getServiceName() {
		return "foursquare";
	}

	public function isStoryElement() {
		return true;
	}

	public function getServiceURL() {
		return 'http://foursquare.com/user/' . $this->getProperty('username');
	}

	public function getServiceDescription() {
		return "foursquare is a location-based social networking website";
	}

	public function getAccountName() {
		if ($name = $this->getProperty('username')) {
			return $name;
		}
		else {
			return false;
		}
	}

	public function getTitle() { return $this->getServiceName(); }

	public function importData() {
		$items = $this->updateData();
		$this->setImported( true );
		return $items;
	}

	public function updateData() {
		$url = $this->getProperty( 'url' );

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT,'Storytlr/1.0');

		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close ($curl);

		if ($http_code != 200) {
			throw new Stuffpress_Exception( "foursquare returned http status $http_code for url: $url", $http_code );
		}

		if (!($rss = simplexml_load_string($response))) {
			throw new Stuffpress_Exception( "foursquare did not return any result", 0 );
		}

		if ( count( $rss->channel->item ) == 0 ) { return; }

		$items = $this->processItems( $rss->channel->item );
		$this->markUpdated();
		return $items;
	}

	private function processItems ( $items ) {
		$venues = array();
		$result = array();
		foreach ($items as $item) {
			$data = array();
			$data['title'] = $item->title;
			$data['guid'] = $item->guid;
			$data['published'] = strtotime( $item->pubDate );
			$data['content'] = $item->description;
			$data['link'] = $item->link;

			$venue_id = str_replace( 'http://foursquare.com/venue/', '', $data['link'] ); // Hacky...
			$location = false;
			try {
				if( ! isset( $venues[$venue_id] ) )
					$venues[$venue_id] = $this->getVenue( $venue_id );

				$location = array(
					'latitude' => floatval( $venues[$venue_id]->venue->geolat ),
					'longitude' => floatval( $venues[$venue_id]->venue->geolong ),
					'elevation' => 0, // These default as we don't have data.
					'accuracy' => 0
				);
			}
			catch ( Stuffpress_Exception $e ) {}

			$id = $this->addItem( $data, $data['published'], SourceItem::STATUS_TYPE, false, $location, false, $data['title'] );
			if ($id) $result[] = $id;
		}
		return $result;
	}

	private function getVenue ( $venue_id ) {
		$url = "http://api.foursquare.com/v1/venue?vid=" . $venue_id;

		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $url );
		curl_setopt( $curl, CURLOPT_HEADER, false );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_USERAGENT,'Storytlr/1.0' );

		$response = curl_exec( $curl );
		$http_code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
		curl_close( $curl );

		if( $http_code != 200 ) {
			throw new Stuffpress_Exception( "foursquare returned http status $http_code for url: $url", $http_code );
		}

		if( ! ( $venue = simplexml_load_string( $response ) ) ) {
			throw new Stuffpress_Exception( "foursquare did not return any result", 0 );
		}

		return $venue;
	}

	public function getConfigForm($populate=false) {
		$form = new Stuffpress_Form();

		// Add the username element
		$element = $form->createElement( 'text', 'username', array('label' => 'Username', 'decorators' => $form->elementDecorators ) );
		$element->setRequired(true);
		$form->addElement($element);

		// Add the feed element
		$element = $form->createElement( 'text', 'url', array('label' => 'RSS Feed URL', 'decorators' => $form->elementDecorators ) );
		$element->setRequired(true);
		$element->setDescription( '<div class="help">Your RSS Feed URL from <a href="http://foursquare.com/feeds">http://foursquare.com/feeds</a>.' );
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

		if($values['url'] != $this->getProperty('url')) {
			$this->_properties->setProperty('url',   $values['url']);
			$update = true;
		}

		return $update;
	}
}
