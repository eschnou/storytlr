<?php

require_once 'PHPUnit/Framework.php';
require_once './../AtomFeedAdapter.php';
require_once './../MediaExtension/MediaExtensionFactory.php';
require_once './../ActivityExtension/ActivityNS.php';

class MediaExtensionTest extends PHPUnit_Framework_TestCase {
	private $_xManager;
	
	protected function setUp() {
		$this->_xManager = AtomExtensionManager::getInstance();
		$this->_xManager->registerExtensionAdapter(new MediaExtensionFactory());
	}
	
	public function testBuildMediaEntry() {
		$feed = new AtomFeedAdapter(null);
		$feed->addNamespace('m', MediaNS::NS);
		
		$entry = $feed->addEntry();
		
		$mediaEntry = $entry->getExtension(MediaNS::NS);
		$mediaEntry->description = 'this is the media description';
		
		$link = $entry->addLink();
		$mediaLink = $link->getExtension(MediaNS::NS);
		$mediaLink->rel			= 'this is the media link rel';
		$mediaLink->href		= 'this is the media link href';
		$mediaLink->type		= 'this is the media link type';
		$mediaLink->width		= 'this is the media link width';
		$mediaLink->width		= 'this is the media link width';
		$mediaLink->height		= 'this is the media link height';
		$mediaLink->duration	= 'this is the media link duration';
		
		$expectedResult = '<?xml version="1.0"?>
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:m="http://purl.org/syndication/atommedia"><entry><m:description>this is the media description</m:description><link rel="this is the media link rel" href="this is the media link href" type="this is the media link type" m:width="this is the media link width" m:height="this is the media link height" m:duration="this is the media link duration"/></entry></feed>';
		
		$this->assertEquals(trim($feed->getXml()), trim($expectedResult));
		
		return $feed;
	}
	
	/**
	 * @depends testBuildMediaEntry
	 */
	public function testMediaContents(AtomFeedAdapter $feed) {
		$mediaEntry = $feed->entry[0]->getExtension(MediaNS::NS);
		$mediaLink  = $feed->entry[0]->link[0]->getExtension(MediaNS::NS);
		
		$this->assertEquals($mediaEntry->description->value	, 'this is the media description');
		$this->assertEquals($mediaLink->rel					, 'this is the media link rel');
		$this->assertEquals($mediaLink->href				, 'this is the media link href');
		$this->assertEquals($mediaLink->type				, 'this is the media link type');
		$this->assertEquals($mediaLink->width				, 'this is the media link width');
		$this->assertEquals($mediaLink->height				, 'this is the media link height');
		$this->assertEquals($mediaLink->duration			, 'this is the media link duration');
	
		return $feed;
	}
	
	/**
	 * @depends testMediaContents
	 */
	public function testSetMediaContents(AtomFeedAdapter $feed) {
		$mediaEntry = $feed->entry[0]->getExtension(MediaNS::NS);
		$mediaEntry->description = 'changed media description';
		
		$mediaLink  = $feed->entry[0]->link[0]->getExtension(MediaNS::NS);
		$mediaLink->rel			= 'changed media link rel';
		$mediaLink->href		= 'changed media link href';
		$mediaLink->type		= 'changed media link type';
		$mediaLink->width		= 'changed media link width';
		$mediaLink->height		= 'changed media link height';
		$mediaLink->duration	= 'changed media link duration';
		
		$this->assertEquals($mediaEntry->description->value	, 'changed media description');
		$this->assertEquals($mediaLink->rel					, 'changed media link rel');
		$this->assertEquals($mediaLink->href				, 'changed media link href');
		$this->assertEquals($mediaLink->type				, 'changed media link type');
		$this->assertEquals($mediaLink->width				, 'changed media link width');
		$this->assertEquals($mediaLink->height				, 'changed media link height');
		$this->assertEquals($mediaLink->duration			, 'changed media link duration');
	}
}