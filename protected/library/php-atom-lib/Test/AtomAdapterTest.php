<?php

require_once 'PHPUnit/Framework.php';
require_once './../AtomFeedAdapter.php';
require_once './../ActivityExtension/ActivityExtensionFactory.php';
require_once './../ThreadingExtension/ThreadingExtensionFactory.php';
require_once './../MediaExtension/MediaExtensionFactory.php';

class AtomAdapterTest1 extends PHPUnit_Framework_TestCase {
	private $_atomNode;
	private $_feed;
	private $_xManager;
	
	protected function setUp() {
		$this->_atomNode = new SimpleXMLElement('xml/Activity.xml', null, true);
		$this->_feed = new AtomFeedAdapter($this->_atomNode);
		$this->_xManager = AtomExtensionManager::getInstance();
	}
		
	/**
     * @expectedException AtomAdapterException
     */
	public function testInvalidDataType() {
		$feed = new AtomFeedAdapter(5);
	}
	
	/**
     * @expectedException AtomAdapterException
     */
	public function testNonAtomDocument () {
		$nonAtom = new AtomFeedAdapter('xml/NonAtom.xml', true);
	}
	
	public function testFeedContents() {
		$this->assertEquals($this->_feed->id->value				, 'http://localhost:8080/resources/timezones');
		
		$this->assertEquals($this->_feed->title->value			, 'Time Zones');
		$this->assertEquals($this->_feed->title->type			, 'text');
		
		$this->assertEquals($this->_feed->link[0]->href			, 'http://localhost:8080/resources/timezones');
		$this->assertEquals($this->_feed->link[0]->rel			, 'self');
		$this->assertEquals($this->_feed->link[0]->title		, null);
		$this->assertEquals($this->_feed->link[0]->hreflang		, null);
		$this->assertEquals($this->_feed->link[0]->type			, null);
		$this->assertEquals($this->_feed->link[0]->length		, null);
		
		$this->assertEquals($this->_feed->author[0]->name		, 'sandra');
		$this->assertEquals($this->_feed->author[0]->uri		, 'sandra.com');
		$this->assertEquals($this->_feed->author[0]->email		, 'sandra@sandra.com');
		
		$this->assertEquals($this->_feed->updated				, null);
		
		$this->assertEquals($this->_feed->category[0]->term		, 'feed category1 term');
		$this->assertEquals($this->_feed->category[0]->scheme	, 'feed category1 scheme');
		$this->assertEquals($this->_feed->category[0]->label	, 'feed category1 label');
		
		$this->assertEquals($this->_feed->generator				, null);
		
		$this->assertEquals(count($this->_feed->entry)			, 4);
	}
	
	public function testPrefixedAtomFeed() {
		$atomNode = new SimpleXMLElement('xml/AtomPrefixedActivity.xml', null, true);
		$pFeed = new AtomFeedAdapter($atomNode);
		
		$this->assertEquals($pFeed->id->value				, 'http://localhost:8080/resources/timezones');
		
		$this->assertEquals($pFeed->title->value			, 'Time Zones');
		$this->assertEquals($pFeed->title->type				, 'text');
		
		$this->assertEquals($pFeed->link[0]->href			, 'http://localhost:8080/resources/timezones');
		$this->assertEquals($pFeed->link[0]->rel			, 'self');
		$this->assertEquals($pFeed->link[0]->title			, null);
		$this->assertEquals($pFeed->link[0]->hreflang		, null);
		$this->assertEquals($pFeed->link[0]->type			, null);
		$this->assertEquals($pFeed->link[0]->length			, null);
		
		$this->assertEquals($pFeed->author[0]->name			, 'sandra');
		$this->assertEquals($pFeed->author[0]->uri			, 'sandra.com');
		$this->assertEquals($pFeed->author[0]->email		, 'sandra@sandra.com');
		
		$this->assertEquals($pFeed->updated					, null);
		
		$this->assertEquals(count($pFeed->entry)			, 1);
		
		$this->assertEquals($pFeed->entry[0]->id->value			, 'tag:versioncentral.example.org,2009:/commit/1643245');
		
		$this->assertEquals($pFeed->entry[0]->title->value		, 'Geraldine committed a change to yate');
		
		$this->assertEquals($pFeed->entry[0]->link[0]->href			, 'http://versioncentral.example.org/geraldine/yate/commit/1643245');
		$this->assertEquals($pFeed->entry[0]->link[0]->type			, 'text/html');
		$this->assertEquals($pFeed->entry[0]->link[0]->rel			, 'alternate');	
		$this->assertEquals($pFeed->entry[0]->link[0]->length		, null);	
		$this->assertEquals($pFeed->entry[0]->link[0]->title		, null);	
		$this->assertEquals($pFeed->entry[0]->link[0]->hreflang		, null);	
		
		$this->assertEquals($pFeed->entry[0]->published->value	, '2009-06-01T12:54:00Z');	
		
		$this->assertEquals($pFeed->entry[0]->updated				, null);
		
		$this->assertEquals(count($pFeed->entry[0]->author)			,0);
		
		$this->assertEquals($pFeed->entry[0]->updated				, null);
		
		$pFeed->entry[0]->updated = 'New AtomPrefixed Entry updated';
		$this->assertEquals($pFeed->entry[0]->updated->value		, 'New AtomPrefixed Entry updated');
		
		$newAuthor = $pFeed->entry[0]->addAuthor();
		$newAuthor->name  = 'New Entry Author Name';
		$newAuthor->uri   = 'New Entry Author Uri';
		$newAuthor->email = 'New Entry Author Email';
	
	
		$this->assertEquals($pFeed->entry[0]->author[0]->name		, 'New Entry Author Name');	
		$this->assertEquals($pFeed->entry[0]->author[0]->uri		, 'New Entry Author Uri');
		$this->assertEquals($pFeed->entry[0]->author[0]->email		, 'New Entry Author Email');
	}
	
	public function testEntryContents() {
		$this->assertEquals($this->_feed->entry[0]->id->value			, 'tag:versioncentral.example.org,2009:/commit/1643245');
		
		$this->assertEquals($this->_feed->entry[0]->title->value		, 'Geraldine committed a change to yate');
		
		$this->assertEquals($this->_feed->entry[0]->link[0]->href		, 'http://versioncentral.example.org/geraldine/yate/commit/1643245');
		$this->assertEquals($this->_feed->entry[0]->link[0]->type		, 'text/html');
		$this->assertEquals($this->_feed->entry[0]->link[0]->rel		, 'alternate');	
		$this->assertEquals($this->_feed->entry[0]->link[0]->length		, null);	
		$this->assertEquals($this->_feed->entry[0]->link[0]->title		, null);	
		$this->assertEquals($this->_feed->entry[0]->link[0]->hreflang	, null);	
		
		$this->assertEquals($this->_feed->entry[0]->published->value	, '2009-06-01T12:54:00Z');	
		
		$this->assertEquals($this->_feed->entry[0]->updated->value		, null);
		
		$this->assertEquals($this->_feed->entry[0]->summary->value		, 'entry1 summary');
		
		$this->assertEquals($this->_feed->entry[0]->author[0]->name		, null);	
		$this->assertEquals($this->_feed->entry[0]->author[0]->uri		, null);
		$this->assertEquals($this->_feed->entry[0]->author[0]->email	, null);
		
		$this->assertEquals($this->_feed->entry[0]->content->value		, 'Geraldine just committed a change to yate on VersionCentral');

		$this->assertEquals($this->_feed->entry[1]->author[0]->name		, 'Geraldine');	
		$this->assertEquals($this->_feed->entry[1]->author[0]->uri		, 'http://example.com/geraldine');
		$this->assertEquals($this->_feed->entry[1]->author[0]->email	, null);	
		
		$this->assertEquals($this->_feed->entry[1]->category[0]			, null);
		
		$this->assertEquals($this->_feed->entry[1]->published			, null);
		
		$this->assertEquals($this->_feed->entry[1]->summary->value		, null);
		
		$this->assertEquals($this->_feed->entry[3]->title->value		, 'atom with prefix');
		
		$this->assertEquals($this->_feed->entry[4]						, null);
	}
	
	/**
     * @expectedException ExtensionManagerException
     */
	public function testNoActivityEntryAdapter() {
		$this->_activityEntry[0] = $this->_feed->entry[0]->getExtension('http://activitystrea.ms/spec/1.0/');
	}
	
	/**
     * @expectedException ExtensionFactoryException
     */
	public function testNoActivityFeedAdapter() {
		$this->_xManager->registerExtensionAdapter(new ActivityExtensionFactory());
		$this->_xManager->registerExtensionAdapter(new MediaExtensionFactory());
		$feedEntry = $this->_feed->getExtension('http://activitystrea.ms/spec/1.0/');
	}
	
	public function testSetFeedContents() {
		$this->_feed->id		 			= 'Changed Feed Id';
		
		$this->_feed->title			 		= 'Changed Feed Title';
		$this->_feed->title->type			= 'Changed Feed Title Type';
		
		$this->_feed->link[0]->href			= 'Changed Feed Link Href';
		$this->_feed->link[0]->rel			= 'Changed Feed Link Rel';
		$this->_feed->link[0]->title		= 'Changed Feed Link Title';
		$this->_feed->link[0]->hreflang		= 'Changed Feed Link Hreflang';
		$this->_feed->link[0]->type			= 'Changed Feed Link Type';
		$this->_feed->link[0]->length		= 'Changed Feed Link Length';
		
		$this->_feed->author[0]->name		= 'Changed Feed Author Names';
		$this->_feed->author[0]->uri		= 'Changed Feed Author Uri';
		$this->_feed->author[0]->email		= 'Changed Feed Author Email';
		
		$this->_feed->category[0]->term		= 'Changed Feed Category Term';
		$this->_feed->category[0]->scheme	= 'Changed Feed Category Scheme';
		$this->_feed->category[0]->label	= 'Changed Feed Category Label';
		
		$this->_feed->generator				= 'New Feed Generator';
		
		$this->_feed->updated			= 'Changed Feed Updated';
		
		$newAuthor = $this->_feed->addAuthor();
		$newAuthor->name				= 'New Feed Author Name';
		$newAuthor->email				= 'New Feed Author Email';
		$newAuthor->uri					= 'New Feed Author Uri';
		
		$newLink = $this->_feed->addLink();
		$newLink->href					= 'New Feed Link Href';
		$newLink->rel					= 'New Feed Link Rel';
		$newLink->title					= 'New Feed Link Title';
		
		$newCategory = $this->_feed->addCategory();
		$newCategory->term				= 'New Feed Category Term';
		$newCategory->scheme			= 'New Feed Category Scheme';
		$newCategory->label				= 'New Feed Category Label';
		
		$this->assertEquals($this->_feed->id->value			, 'Changed Feed Id');
		
		$this->assertEquals($this->_feed->title->value		, 'Changed Feed Title');
		$this->assertEquals($this->_feed->title->type			, 'Changed Feed Title Type');
		
		$this->assertEquals($this->_feed->link[0]->href			, 'Changed Feed Link Href');
		$this->assertEquals($this->_feed->link[0]->rel			, 'Changed Feed Link Rel');
		$this->assertEquals($this->_feed->link[0]->title		, 'Changed Feed Link Title');
		$this->assertEquals($this->_feed->link[0]->hreflang		, 'Changed Feed Link Hreflang');
		$this->assertEquals($this->_feed->link[0]->type			, 'Changed Feed Link Type');
		$this->assertEquals($this->_feed->link[0]->length		, 'Changed Feed Link Length');
		
		$this->assertEquals($this->_feed->author[0]->name		, 'Changed Feed Author Names');
		$this->assertEquals($this->_feed->author[0]->uri		, 'Changed Feed Author Uri');
		$this->assertEquals($this->_feed->author[0]->email		, 'Changed Feed Author Email');
		
		$this->assertEquals($this->_feed->category[0]->term		, 'Changed Feed Category Term');
		$this->assertEquals($this->_feed->category[0]->scheme	, 'Changed Feed Category Scheme');
		$this->assertEquals($this->_feed->category[0]->label	, 'Changed Feed Category Label');
		
		$this->assertEquals($this->_feed->category[2]->term		, 'New Feed Category Term');
		$this->assertEquals($this->_feed->category[2]->scheme	, 'New Feed Category Scheme');
		$this->assertEquals($this->_feed->category[2]->label	, 'New Feed Category Label');
		
		$this->assertEquals($this->_feed->updated->value		, 'Changed Feed Updated');
		
		$this->assertEquals(count($this->_feed->entry)			, 4);
		
		$this->assertEquals($this->_feed->author[1]->name		, 'New Feed Author Name');
		$this->assertEquals($this->_feed->author[1]->email		, 'New Feed Author Email');
		$this->assertEquals($this->_feed->author[1]->uri		, 'New Feed Author Uri');
		
		$this->assertEquals($this->_feed->link[1]->href			, 'New Feed Link Href');
		$this->assertEquals($this->_feed->link[1]->rel			, 'New Feed Link Rel');
		$this->assertEquals($this->_feed->link[1]->title		, 'New Feed Link Title');
		$this->assertEquals($this->_feed->link[1]->hreflang		, null);
		$this->assertEquals($this->_feed->link[1]->type			, null);
		$this->assertEquals($this->_feed->link[1]->length		, null);
		
		$this->assertEquals($this->_feed->generator->value		, 'New Feed Generator');
		
	}
	
	public function testSetEntryContents() {
		$this->_feed->entry[0]->id					= 'Changed Entry Id';
		
		$this->_feed->entry[0]->title		 		= 'Changed Entry Title';
		
		$this->_feed->entry[0]->link[0]->href		= 'Changed Entry Link Href';
		$this->_feed->entry[0]->link[0]->type		= 'Changed Entry Link Type';
		$this->_feed->entry[0]->link[0]->rel		= 'Changed Entry Link Rel';
		$this->_feed->entry[0]->link[0]->length		= 'Changed Entry Link Length';
		$this->_feed->entry[0]->link[0]->title		= 'Changed Entry Link Title';
		$this->_feed->entry[0]->link[0]->hreflang	= 'Changed Entry Link Hreflang';
		
		$this->_feed->entry[0]->published			= 'Changed Entry Published';
		
		$this->_feed->entry[0]->summary				= 'Changed Entry Summary';
		
		$this->_feed->entry[0]->updated				= 'Changed Entry Updated';
		
		//the author for the first entry is not exist at this moment, maybe it's better to throw new exception later
		$this->_feed->entry[0]->author[0]->name		= 'Changed Entry Author Name';
		$this->_feed->entry[0]->author[0]->uri		= 'Changed Entry Author Uri';
		$this->_feed->entry[0]->author[0]->email	= 'Changed Entry Author Email';
		
		$this->_feed->entry[0]->content				= 'Changed Entry Content';
		
		$this->_feed->entry[1]->author[0]->name		= 'Changed Entry1 Author Name';
		$this->_feed->entry[1]->author[0]->uri		= 'Changed Entry1 Author Uri';
		$this->_feed->entry[1]->author[0]->email	= 'Changed Entry1 Author Email';
		
		$this->_feed->entry[1]->published			= 'New Entry1 Published';
		
		$this->_feed->entry[1]->summary				= 'New Entry2 Summary';
		
		$newEntryLink = $this->_feed->entry[0]->addLink();
		$newEntryLink->href					= 'New Entry Link Href';
		$newEntryLink->rel					= 'New Entry Link Rel';
		$newEntryLink->title				= 'New Entry Link Title';
		
		$this->assertEquals($this->_feed->entry[0]->id->value			, 'Changed Entry Id');
		
		$this->assertEquals($this->_feed->entry[0]->title->value		, 'Changed Entry Title');
		
		$this->assertEquals($this->_feed->entry[0]->link[0]->href		, 'Changed Entry Link Href');
		$this->assertEquals($this->_feed->entry[0]->link[0]->type		, 'Changed Entry Link Type');
		$this->assertEquals($this->_feed->entry[0]->link[0]->rel		, 'Changed Entry Link Rel');	
		$this->assertEquals($this->_feed->entry[0]->link[0]->length		, 'Changed Entry Link Length');	
		$this->assertEquals($this->_feed->entry[0]->link[0]->title		, 'Changed Entry Link Title');	
		$this->assertEquals($this->_feed->entry[0]->link[0]->hreflang	, 'Changed Entry Link Hreflang');	
		
		$this->assertEquals($this->_feed->entry[0]->published->value	, 'Changed Entry Published');

		$this->assertEquals($this->_feed->entry[0]->summary->value		, 'Changed Entry Summary');
		
		$this->assertEquals($this->_feed->entry[0]->updated->value	, 'Changed Entry Updated');
		
		$this->assertEquals($this->_feed->entry[0]->author[0]->name		, null);	
		$this->assertEquals($this->_feed->entry[0]->author[0]->uri		, null);
		$this->assertEquals($this->_feed->entry[0]->author[0]->email	, null);
		
		$this->assertEquals($this->_feed->entry[0]->content->value		, 'Changed Entry Content');
		
		$newEntryAuthor = $this->_feed->entry[0]->addAuthor();
		$newEntryAuthor->name	= 'New Entry Author Name';
		$newEntryAuthor->email	= 'New Entry Author Email';
		$newEntryAuthor->uri	= 'New Entry Author Uri';
		
		$newEntryCategory = $this->_feed->entry[1]->addCategory();
		$newEntryCategory->term		= 'New Entry1 Category Term';
		$newEntryCategory->scheme	= 'New Entry1 Category Scheme';
		$newEntryCategory->label	= 'New Entry1 Category Label';
		
		$this->assertEquals($this->_feed->entry[0]->author[0]->name		, 'New Entry Author Name');	
		$this->assertEquals($this->_feed->entry[0]->author[0]->uri		, 'New Entry Author Uri');
		$this->assertEquals($this->_feed->entry[0]->author[0]->email	, 'New Entry Author Email');

		$this->assertEquals($this->_feed->entry[1]->author[0]->name		, 'Changed Entry1 Author Name');	
		$this->assertEquals($this->_feed->entry[1]->author[0]->uri		, 'Changed Entry1 Author Uri');
		$this->assertEquals($this->_feed->entry[1]->author[0]->email	, 'Changed Entry1 Author Email');

		$this->assertEquals($this->_feed->entry[1]->category[0]->term	, 'New Entry1 Category Term');	
		$this->assertEquals($this->_feed->entry[1]->category[0]->scheme	, 'New Entry1 Category Scheme');
		$this->assertEquals($this->_feed->entry[1]->category[0]->label	, 'New Entry1 Category Label');
		
		$this->assertEquals($this->_feed->entry[1]->published->value	, 'New Entry1 Published');
		
		$this->assertEquals($this->_feed->entry[1]->summary->value		, 'New Entry2 Summary');
		
		$this->assertEquals($this->_feed->entry[0]->link[1]->href		, 'New Entry Link Href');
		$this->assertEquals($this->_feed->entry[0]->link[1]->rel		, 'New Entry Link Rel');
		$this->assertEquals($this->_feed->entry[0]->link[1]->title		, 'New Entry Link Title');
		$this->assertEquals($this->_feed->entry[0]->link[1]->hreflang	, null);
		$this->assertEquals($this->_feed->entry[0]->link[1]->type		, null);
		$this->assertEquals($this->_feed->entry[0]->link[1]->length		, null);			
	}
	
	public function testAddEntry() {
		$this->assertEquals($this->_feed->entry[4]		, null);		
		
		$this->_feed->addEntry();
		
		$this->assertEquals($this->_feed->entry[4]->id			, null);
		$this->assertEquals($this->_feed->entry[4]->title		, null);
		$this->assertEquals($this->_feed->entry[4]->link[0]		, null);
		$this->assertEquals($this->_feed->entry[4]->published	, null);	
		$this->assertEquals($this->_feed->entry[4]->updated		, null);
		$this->assertEquals($this->_feed->entry[4]->author[0]	, null);	
		
		$this->_feed->entry[4]->id					= 'New Entry Id';
		$this->_feed->entry[4]->title		 		= 'New Entry Title';
		$this->_feed->entry[4]->published			= 'New Entry Published';
		$this->_feed->entry[4]->updated				= 'New Entry Updated';
		
		$newAuthor = $this->_feed->entry[4]->addAuthor();
		$newAuthor->name							= 'New Entry Author Name';
		$newAuthor->email							= 'New Entry Author Email';
		$newAuthor->uri								= 'New Entry Author Uri';
		
		$newLink = $this->_feed->entry[4]->addLink();
		$newLink->href								= 'New Entry Link Href';
		$newLink->rel								= 'New Entry Link Rel';
		$newLink->title								= 'New Entry Link Title';
		$newLink->hreflang							= 'New Entry Link Hreflang';
		$newLink->type								= 'New Entry Link Type';
		$newLink->length							= 'New Entry Link Length';
		
		$this->assertEquals($this->_feed->entry[4]->id->value			, 'New Entry Id');
		
		$this->assertEquals($this->_feed->entry[4]->title->value		, 'New Entry Title');
		
		$this->assertEquals($this->_feed->entry[4]->published->value	, 'New Entry Published');
		
		$this->assertEquals($this->_feed->entry[4]->updated->value	, 'New Entry Updated');
		
		$this->assertEquals($this->_feed->entry[4]->author[0]->name		, 'New Entry Author Name');
		$this->assertEquals($this->_feed->entry[4]->author[0]->uri		, 'New Entry Author Uri');
		$this->assertEquals($this->_feed->entry[4]->author[0]->email	, 'New Entry Author Email');
		
		$this->assertEquals($this->_feed->entry[4]->link[0]->href		, 'New Entry Link Href');
		$this->assertEquals($this->_feed->entry[4]->link[0]->rel		, 'New Entry Link Rel');
		$this->assertEquals($this->_feed->entry[4]->link[0]->title		, 'New Entry Link Title');
		$this->assertEquals($this->_feed->entry[4]->link[0]->hreflang	, 'New Entry Link Hreflang');
		$this->assertEquals($this->_feed->entry[4]->link[0]->type		, 'New Entry Link Type');
		$this->assertEquals($this->_feed->entry[4]->link[0]->length		, 'New Entry Link Length');
	}
	
	public function testActivityEntryContents() {
		$this->_activityEntry[0] = $this->_feed->entry[0]->getExtension('http://activitystrea.ms/spec/1.0/');
		
		$this->assertEquals($this->_activityEntry[0]->verb[0]->value						, 'http://activitystrea.ms/schema/1.0/post');
		$this->assertEquals($this->_activityEntry[0]->verb[1]->value						, 'http://versioncentral.example.org/activity/commit');
		
		$this->assertEquals($this->_activityEntry[0]->object[0]->objectType[0]->value		, 'http://versioncentral.example.org/activity/changeset');
		$this->assertEquals($this->_activityEntry[0]->object[0]->title->value				, 'Punctuation Changeset');
		$this->assertEquals($this->_activityEntry[0]->object[0]->id->value					, 'tag:versioncentral.example.org,2009:/change/1643245');
		$this->assertEquals($this->_activityEntry[0]->object[1]								, null);
		
		$newEntryVerb[0] = $this->_activityEntry[0]->addVerb();
		$newEntryVerb[0]->value = 'New Activity Entry Verb';
		$this->assertEquals($this->_activityEntry[0]->verb[2]->value						, 'New Activity Entry Verb');
		
		$newEntryObject 			= $this->_activityEntry[0]->addObject();
		$newEntryObjectObjectType 	= $newEntryObject->addObjectType();
		$newEntryObjectObjectType->value = 'New Activity Entry Object Object Type';
		$this->assertEquals($this->_activityEntry[0]->object[1]->objectType[0]->value		, 'New Activity Entry Object Object Type');
		
		$newEntryAuthor = $this->_feed->entry[0]->addAuthor();
		$newEntryAuthor->name	= 'New Entry Author Name';
		$newEntryAuthor->email	= 'New Entry Author Email';
		$newEntryAuthor->uri	= 'New Entry Author Uri';
		
		$this->_activityAuthor[0] = $this->_feed->entry[0]->author[0]->getExtension('http://activitystrea.ms/spec/1.0/');
		$this->assertEquals($this->_activityAuthor[0]->objectType[0]						, null);
		
		$newAuthorObjectType 			= $this->_activityAuthor[0]->addObjectType();
		$newAuthorObjectType->value 	= 'New Author Object Type Content';
		$this->assertEquals($this->_activityAuthor[0]->ObjectType[0]->value				, 'New Author Object Type Content');
				
		$this->_activityAuthor[1] = $this->_feed->entry[1]->author[0]->getExtension('http://activitystrea.ms/spec/1.0/');
		$this->assertEquals($this->_activityAuthor[1]->ObjectType[0]->value				, 'http://activitystrea.ms/schema/1.0/person');
	
		$this->_activityEntry[1] = $this->_feed->entry[1]->getExtension('http://activitystrea.ms/spec/1.0/');
		
		$this->assertEquals($this->_activityEntry[1]->verb[0]								, null);
		$this->assertEquals($this->_activityEntry[1]->object[0]->objectType[0]->value		, 'http://activitystrea.ms/schema/1.0/photo');
		$this->assertEquals($this->_activityEntry[1]->object[0]->objectType[1]->value		, 'http://activitystrea.ms/schema/1.0/image');
		$this->assertEquals($this->_activityEntry[1]->object[1]->objectType[0]->value		, 'http://activitystrea.ms/schema/1.0/image');

		$this->assertEquals($this->_activityEntry[1]->object[1]->objectType[1]->value		, 'http://activitystrea.ms/schema/1.0/photo');
		$this->assertEquals($this->_activityEntry[1]->object[1]->title->value				, '');
		$this->assertEquals($this->_activityEntry[1]->object[1]->id->value				, 'tag:photopanic.example.com,2009:/Photo/2519358/60764844');
		
		$newEntryVerb[1] = $this->_activityEntry[1]->addVerb();
		$newEntryVerb[1]->value = 'New Activity Entry Verb1';
		$this->assertEquals($this->_activityEntry[1]->verb[0]->value						, 'New Activity Entry Verb1');
	}
	
	public function testBuildFeed() {
		$new = new AtomFeedAdapter(null);
		
		$new->title					= 'New Title';
		$new->title->type			= 'New Title Type';
		
		$new->id					= 'New Id';
		$new->updated				= 'New Updated';
		
		$new->generator				= 'New Generator';
		$new->generator->uri		= 'New Generator Uri';
		$new->generator->version	= 'New Generator Version';
		
		$newAuthor = $new->addAuthor();
		$newAuthor->name	= 'New Author Name';
		$newAuthor->uri		= 'New Author Uri';
		$newAuthor->email	= 'New Author Email';
		
		$newLink = $new->addLink();
		$newLink->href		= 'New Link Href';
		$newLink->type		= 'New Link Type';
		$newLink->rel		= 'New Link Rel';
		$newLink->title		= 'New Link Title';
		$newLink->hreflang	= 'New Link Hreflang';
		$newLink->length	= 'New Link Length';
		
		$newCategory = $new->addCategory();
		$newCategory->term	= 'New Category Term';
		$newCategory->scheme= 'New Category Scheme';
		$newCategory->label	= 'New Category Label';
		
		$newEntry = $new->addEntry();
		$newEntry->title		= 'New Entry Title';
		$newEntry->title->type	= 'New Entry Title Type';
		
		$newEntry->id			= 'New Entry Id';
		$newEntry->updated		= 'New Entry Updated';
		$newEntry->published	= 'New Entry Published';
		
		$newEntry->summary		= 'New Entry Summary';
		$newEntry->summary->type= 'New Entry Summary Type';
		
		$newEntry->content		= 'New Entry Content';
		$newEntry->content->type= 'New Entry Content Type';
		$newEntry->content->src	= 'New Entry Content Src';
		
		$newEntryAuthor = $newEntry->addAuthor();
		$newEntryAuthor->name	= 'New Entry Author Name';
		$newEntryAuthor->uri	= 'New Entry Author Uri';
		$newEntryAuthor->email	= 'New Entry Author Email';
		
		$newEntryLink = $newEntry->addLink();
		$newEntryLink->href		= 'New Entry Link Href';
		$newEntryLink->type		= 'New Entry Link Type';
		$newEntryLink->rel		= 'New Entry Link Rel';
		$newEntryLink->title	= 'New Entry Link Title';
		$newEntryLink->hreflang	= 'New Entry Link Hreflang';
		$newEntryLink->length	= 'New Entry Link Length';
		
		$newEntryCategory = $newEntry->addCategory();
		$newEntryCategory->term		= 'New Entry Category Term';
		$newEntryCategory->scheme	= 'New Entry Category Scheme';
		$newEntryCategory->label	= 'New Entry Category Label';
		
		$expectedResult = '<?xml version="1.0"?>
<feed xmlns="http://www.w3.org/2005/Atom"><title type="New Title Type">New Title</title><id>New Id</id><updated>New Updated</updated><generator uri="New Generator Uri" version="New Generator Version">New Generator</generator><author><name>New Author Name</name><uri>New Author Uri</uri><email>New Author Email</email></author><link href="New Link Href" type="New Link Type" rel="New Link Rel" title="New Link Title" hreflang="New Link Hreflang" length="New Link Length"/><category term="New Category Term" scheme="New Category Scheme" label="New Category Label"/><entry><title type="New Entry Title Type">New Entry Title</title><id>New Entry Id</id><updated>New Entry Updated</updated><published>New Entry Published</published><summary type="New Entry Summary Type">New Entry Summary</summary><content type="New Entry Content Type" src="New Entry Content Src">New Entry Content</content><author><name>New Entry Author Name</name><uri>New Entry Author Uri</uri><email>New Entry Author Email</email></author><link href="New Entry Link Href" type="New Entry Link Type" rel="New Entry Link Rel" title="New Entry Link Title" hreflang="New Entry Link Hreflang" length="New Entry Link Length"/><category term="New Entry Category Term" scheme="New Entry Category Scheme" label="New Entry Category Label"/></entry></feed>';
		
		$this->assertEquals(trim($new->getXml()), trim($expectedResult));
		
		return $new;
	}
	
	public function testBuildEntry() {
		$newEntry = new AtomEntryAdapter(null);
		
		$newEntry->title		= 'New Entry Title';
		$newEntry->title->type	= 'New Entry Title Type';
		
		$newEntry->id			= 'New Entry Id';
		$newEntry->updated		= 'New Entry Updated';
		$newEntry->published	= 'New Entry Published';
		
		$newEntry->summary		= 'New Entry Summary';
		$newEntry->summary->type= 'New Entry Summary Type';
		
		$newEntry->content		= 'New Entry Content';
		$newEntry->content->type= 'New Entry Content Type';
		$newEntry->content->src	= 'New Entry Content Src';
		
		$newEntryAuthor = $newEntry->addAuthor();
		$newEntryAuthor->name	= 'New Entry Author Name';
		$newEntryAuthor->uri	= 'New Entry Author Uri';
		$newEntryAuthor->email	= 'New Entry Author Email';
		
		$newEntryLink = $newEntry->addLink();
		$newEntryLink->href		= 'New Entry Link Href';
		$newEntryLink->type		= 'New Entry Link Type';
		$newEntryLink->rel		= 'New Entry Link Rel';
		$newEntryLink->title	= 'New Entry Link Title';
		$newEntryLink->hreflang	= 'New Entry Link Hreflang';
		$newEntryLink->length	= 'New Entry Link Length';
		
		$newEntryCategory = $newEntry->addCategory();
		$newEntryCategory->term		= 'New Entry Category Term';
		$newEntryCategory->scheme	= 'New Entry Category Scheme';
		$newEntryCategory->label	= 'New Entry Category Label';
		
		$expectedResult = '<?xml version="1.0"?>
<entry xmlns="http://www.w3.org/2005/Atom"><title type="New Entry Title Type">New Entry Title</title><id>New Entry Id</id><updated>New Entry Updated</updated><published>New Entry Published</published><summary type="New Entry Summary Type">New Entry Summary</summary><content type="New Entry Content Type" src="New Entry Content Src">New Entry Content</content><author><name>New Entry Author Name</name><uri>New Entry Author Uri</uri><email>New Entry Author Email</email></author><link href="New Entry Link Href" type="New Entry Link Type" rel="New Entry Link Rel" title="New Entry Link Title" hreflang="New Entry Link Hreflang" length="New Entry Link Length"/><category term="New Entry Category Term" scheme="New Entry Category Scheme" label="New Entry Category Label"/></entry>';
		
		$this->assertEquals(trim($newEntry->getXml()), trim($expectedResult));
	}
	
	/**
	 * @depends testBuildFeed
	 */
	public function testBuildActivityFeed(AtomFeedAdapter $new) {
		$new->addNamespace('a',ActivityNS::NS);
		
		$newActivityEntry = $new->entry[0]->getExtension(ActivityNS::NS);
		$newActivityEntry->addVerb()->value						= 'New Activity Entry Verb';
		$newActivityEntry->addObject()->addObjectType()->value	= 'New Activity Entry Object Object Type';
		
		$newActivityEntry->object[0]->addLink()->duration 		= 'New Activity Entry Object Link Duration';		
		
		$newActivityEntry->generator							= 'New Activity Entry Generator';
		$newActivityEntry->generator->uri						= 'New Activity Entry Generator Uri';
		$newActivityEntry->generator->version					= 'New Activity Entry Generator Version';
		
		$newEntryActivityTarget = $newActivityEntry->addTarget();
		$newEntryActivityTarget->addObjectType()->value			= 'New Activity Entry Target Object Type';
		$newEntryActivityTarget->id								= 'New Activity Entry Target Id';
		$newEntryActivityTarget->title							= 'New Activity Entry Target Title';
		$newEntryActivityTarget->addObjectType()->value			= 'New Activity Entry Target Object Type';
		
		$newEntryActivityTargetLink = $newEntryActivityTarget->addLink();
		$newEntryActivityTargetLink->href		= 'New Entry Activity Target Link Href';
		$newEntryActivityTargetLink->type		= 'New Entry Activity Target Link Type';
		$newEntryActivityTargetLink->rel		= 'New Entry Activity Target Link Rel';
		$newEntryActivityTargetLink->title		= 'New Entry Activity Target Link Title';
		$newEntryActivityTargetLink->hreflang	= 'New Entry Activity Target Link Hreflang';
		$newEntryActivityTargetLink->length		= 'New Entry Activity Target Link Length';
		
		$newEntryActivityAuthor = $new->entry[0]->author[0]->getExtension(ActivityNS::NS);
		$newEntryActivityAuthor->addObjectType()->value				= 'New Entry Activity Author Object Type';
		$newEntryActivityAuthor->id									= 'New Entry Activity Author Id';
		
		$newEntryActivityAuthorLink = $newEntryActivityAuthor->addLink();
		$newEntryActivityAuthorLink->href		= 'New Entry Activity Author Link Href';
		$newEntryActivityAuthorLink->type		= 'New Entry Activity Author Link Type';
		$newEntryActivityAuthorLink->rel		= 'New Entry Activity Author Link Rel';
		$newEntryActivityAuthorLink->title		= 'New Entry Activity Author Link Title';
		$newEntryActivityAuthorLink->hreflang	= 'New Entry Activity Author Link Hreflang';
		$newEntryActivityAuthorLink->length		= 'New Entry Activity Author Link Length';
		
		$expectedResult = '<?xml version="1.0"?>
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:a="http://activitystrea.ms/spec/1.0/"><title type="New Title Type">New Title</title><id>New Id</id><updated>New Updated</updated><generator uri="New Generator Uri" version="New Generator Version">New Generator</generator><author><name>New Author Name</name><uri>New Author Uri</uri><email>New Author Email</email></author><link href="New Link Href" type="New Link Type" rel="New Link Rel" title="New Link Title" hreflang="New Link Hreflang" length="New Link Length"/><category term="New Category Term" scheme="New Category Scheme" label="New Category Label"/><entry><title type="New Entry Title Type">New Entry Title</title><id>New Entry Id</id><updated>New Entry Updated</updated><published>New Entry Published</published><summary type="New Entry Summary Type">New Entry Summary</summary><content type="New Entry Content Type" src="New Entry Content Src">New Entry Content</content><author><name>New Entry Author Name</name><uri>New Entry Author Uri</uri><email>New Entry Author Email</email><a:object-type>New Entry Activity Author Object Type</a:object-type><id>New Entry Activity Author Id</id><link href="New Entry Activity Author Link Href" type="New Entry Activity Author Link Type" rel="New Entry Activity Author Link Rel" title="New Entry Activity Author Link Title" hreflang="New Entry Activity Author Link Hreflang" length="New Entry Activity Author Link Length"/></author><link href="New Entry Link Href" type="New Entry Link Type" rel="New Entry Link Rel" title="New Entry Link Title" hreflang="New Entry Link Hreflang" length="New Entry Link Length"/><category term="New Entry Category Term" scheme="New Entry Category Scheme" label="New Entry Category Label"/><a:verb>New Activity Entry Verb</a:verb><a:object><a:object-type>New Activity Entry Object Object Type</a:object-type><link xmlns:media="http://purl.org/syndication/atommedia" media:duration="New Activity Entry Object Link Duration"/></a:object><generator uri="New Activity Entry Generator Uri" version="New Activity Entry Generator Version">New Activity Entry Generator</generator><a:target><a:object-type>New Activity Entry Target Object Type</a:object-type><id>New Activity Entry Target Id</id><title>New Activity Entry Target Title</title><a:object-type>New Activity Entry Target Object Type</a:object-type><link href="New Entry Activity Target Link Href" type="New Entry Activity Target Link Type" rel="New Entry Activity Target Link Rel" title="New Entry Activity Target Link Title" hreflang="New Entry Activity Target Link Hreflang" length="New Entry Activity Target Link Length"/></a:target></entry></feed>';
		$this->assertEquals(trim($new->getXml()), trim($expectedResult));
	}
}