<?php

class AtomProcessor {
	
	private $_user;
	private $_domain;
	private $_config;
	
	public function __construct($user=false, $domain=false) {
		$this->_user = $user ? $user : Zend_Registry::get("user");
		$this->_domain = $domain ? $domain : Stuffpress_Application::getDomain($this->_user, true);
		$this->_config = Zend_Registry::get("configuration");
	}
	
	#region buildActivitiesFeed
	/**
	 * @param array $items
	 * @return AtomFeedAdapter
	 */
	public function buildActivitiesFeed($items, $url) {

		$feed = new AtomFeedAdapter(null);
    	$feed->addNamespace(ActivityNS::PREFIX, ActivityNS::NS);
    	$feed->addNamespace(MediaNS::PREFIX, MediaNS::NS);
    	
    	// Feed id and other parameters
    	$feed->title		= $this->_user->username . "'s activities";
    	$feed->id			= $url;
    	$feed->updated		= toAtomDate(time());

    	$feedLink  = $feed->addLink();
    	$feedLink->rel      = 'self';
    	$feedLink->href     = $url;
    	
    	$feedLink  = $feed->addLink();
    	$feedLink->rel      = 'alternate';
    	$feedLink->type		= 'text/html';
    	$feedLink->href     = 'http://' . $this->_domain;
    	
    	if ($this->_config->push) {
	    	$feedLink  = $feed->addLink();
	    	$feedLink->rel      = 'hub';
	    	$feedLink->href     = $this->_config->push->hub;
    	}
    	
    	$feedAuthor = $feed->addAuthor();
    	$feedAuthor->name	= $this->_user->username;
    	$feedAuthor->uri	= 'http://' . $this->_domain;
    	
		foreach ($items as $item) {
			$this->buildItemEntry($item, $feed->addEntry());
		}
		
		return $feed;
	}
	
	/**
	 * @param unknown_type $item
	 * @param AtomEntryAdapter $entry
	 * @return AtomEntryAdapter
	 */
	public function buildItemEntry($item, $entry=null) {
		$entry = $this->_prepareEntry($entry);
		switch ($item->getType()) {
			case SourceItem::STATUS_TYPE:					
				return $this->buildStatusEntry($item, $entry);
				break;
			case SourceItem::BLOG_TYPE:				
				return $this->buildBlogEntry($item, $entry);
				break;
			case SourceItem::LINK_TYPE:					
				return $this->buildLinkEntry($item, $entry);
				break;
			case SourceItem::IMAGE_TYPE:				
				return $this->buildImageEntry($item, $entry);
				break;
			case SourceItem::VIDEO_TYPE:					
				return $this->buildVideoEntry($item, $entry);
				break;
			case SourceItem::AUDIO_TYPE:				
				return $this->buildAudioEntry($item, $entry);
				break;
		}
	}
	
	/**
	 * @param AtomEntryAdapter $entry
	 * @return AtomEntryAdapter
	 */
	protected function _prepareEntry($entry=null) {
    	if ($entry === null) {
    		$entry	= new AtomEntryAdapter();
			$entry->addNamespace(ActivityNS::PREFIX, ActivityNS::NS);
    	}
    	return $entry;
	}
    
    /**
     * @param unknown_type $item
     * @param unknown_type $entry
     * @return AtomEntryAdapter
     */
    protected function buildStatusEntry($item, $entry=null) {
    	$this->_buildCommonItemEntryElement($entry, $item);
    	$entry->content			= $this->xmlentities($item->getStatus());
    	$entry->content->type	= AtomNS::TYPE_HTML;
    	
		// build the activity entry
    	$activityEntry = $entry->getExtension(ActivityNS::NS);/* @var $activityEntry ActivityEntryExtension */
    	$activityEntry->addVerb(ActivityNS::POST_VERB);
    	
    	//build the activity object
    	$activityObject = $activityEntry->addObject();
    	
    	$application = Stuffpress_Application::getInstance();
    	$objectAuthor = $activityObject->addAuthor();
    	$objectAuthor->name	= $this->_user->username;
    	$objectAuthor->uri	= 'http://' . $this->_domain;
    	//$objectAuthor->email	= $application->user->email;
    	
    	$status = ActivityProcessorFactory::getInstance()->getProcessor($activityObject, ActivityNS::STATUS_OBJECT_TYPE);/* @var $status IActivityStatus */
    	$this->_buildCommonItemActivityObject($status, $item);
    	$status->setContent($entry->content->value);
    	
    	//$this->_getEditLink($activityObject->addLink(), $item, $entry->id->value);
    	
    	return $entry;
    }
    
    /**
     * @param unknown_type $item
     * @param unknown_type $entry
     * @return AtomEntryAdapter
     */
    protected function buildBlogEntry($item, $entry=null) {
    	$this->_buildCommonItemEntryElement($entry, $item);
    	$entry->content			= $this->xmlentities($item->getContent());
    	$entry->content->type	= AtomNS::TYPE_HTML;
    	
		// build the activity entry    	
    	$activityEntry = $entry->getExtension(ActivityNS::NS);
    	$activityEntry->addVerb(ActivityNS::POST_VERB);

    	// build the activity object
    	$activityObject = $activityEntry->addObject();
    	
    	$application = Stuffpress_Application::getInstance();
    	$objectAuthor = $activityObject->addAuthor();
    	$objectAuthor->name	= $this->_user->username;
    	$objectAuthor->uri	= 'http://' . $this->_domain;
    	$objectAuthor->email	= $application->user->email;
    	
    	$article = ActivityProcessorFactory::getInstance()->getProcessor($activityObject, ActivityNS::ARTICLE_OBJECT_TYPE);/* @var $article IActivityArticle */
    	$this->_buildCommonItemActivityObject($article, $item);
    	$article->setSummary($this->xmlentities(substr($item->getContent(), 0, 50) . "..."));
    	$article->setContent($entry->content->value);	
    
    	//$this->_getEditLink($activityObject->addLink(), $item, $entry->id->value);
    	
    	return $entry;
    }
    
    /**
     * @param unknown_type $item
     * @param unknown_type $entry
     * @return AtomEntryAdapter
     */
    protected function buildLinkEntry($item, $entry=null) {
    	$this->_buildCommonItemEntryElement($entry, $item);
    	$entry->addNamespace(MediaNS::PREFIX, MediaNS::NS);
    	
    	$entry->content			= $this->xmlentities("<a href='" . $item->getLink() . "'>" . $item->getLink() . "</a><br/>" . $item->getDescription());
		$entry->content->type	= AtomNS::TYPE_HTML;
    	
		// build the activity entry    	
    	$activityEntry = $entry->getExtension(ActivityNS::NS);/* @var $activityEntry ActivityEntryExtension */
    	$activityEntry->addVerb(ActivityNS::SHARE_VERB);
    	
    	// build the activity object
    	$activityObject = $activityEntry->addObject();
    	
    	$application = Stuffpress_Application::getInstance();
    	$objectAuthor = $activityObject->addAuthor();
    	$objectAuthor->name	= $this->_user->username;
    	$objectAuthor->uri	= 'http://' . $this->_domain;
    	$objectAuthor->email	= $application->user->email;
    	
    	$this->_getEditLink($activityObject->addLink(), $item, $entry->id->value);
    	
    	$link = ActivityProcessorFactory::getInstance()->getProcessor($activityObject, ActivityNS::BOOKMARK_OBJECT_TYPE);/* @var $link IActivityBookmark */
    	$this->_buildCommonItemActivityObject($link, $item);
    	$link->setDescription($this->xmlentities($item->getDescription()));
    	$link->setTargetUrl($item->getLink());
    	$link->setTargetTitle($item->getTitle());
    	//$link->setThumbnail('not available yet', 'image/jpeg', 'unknown', 'unknown');
    	$link->setContent($entry->content->value);
    
    	//$this->_getEditLink($activityObject->addLink(), $item, $entry->id->value);
    	
    	return $entry;
    }
    
    /**
     * @param unknown_type $item
     * @param unknown_type $entry
     * @return AtomEntryAdapter
     */
    protected function buildImageEntry($item, $entry=null) {
    	$this->_buildCommonItemEntryElement($entry, $item);
    	$entry->addNamespace(MediaNS::PREFIX, MediaNS::NS);
    	
    	if ($item->getPrefix() == 'stuffpress') {
    		$this->_getEditMediaLink($entry->addLink(), $item, $entry->id->value);
    	}
    	
		$entry->content			= $this->xmlentities("<img src='" . $item->getImageUrl(ImageItem::SIZE_MEDIUM) . "'/><br/>" . $item->getDescription());
		$entry->content->type	= AtomNS::TYPE_HTML;
		    	
		// build the activity entry    	
    	$activityEntry = $entry->getExtension(ActivityNS::NS);/* @var $activityEntry ActivityEntryExtension */
    	$activityEntry->addVerb(ActivityNS::POST_VERB);
    	
    	// build the activity object
    	$activityObject = $activityEntry->addObject();
    	
    	$application = Stuffpress_Application::getInstance();
    	$objectAuthor = $activityObject->addAuthor();
    	$objectAuthor->name	= $this->_user->username;
    	$objectAuthor->uri	= 'http://' . $this->_domain;
    	//$objectAuthor->email	= $application->user->email;
    	
    	$image = ActivityProcessorFactory::getInstance()->getProcessor($activityObject, ActivityNS::PHOTO_OBJECT_TYPE);/* @var $image IActivityPhoto */
    	$this->_buildCommonItemActivityObject($image, $item);
    	$image->setThumbnail($item->getImageUrl(ImageItem::SIZE_THUMBNAIL), 'image/jpeg', 'unknown', 'unknown');
    	$image->setLargerImage($item->getImageUrl(ImageItem::SIZE_LARGE), 'image/jpeg', 'unknown', 'unknown');
    	$image->setDescription($this->xmlentities($item->getDescription()));
    	$image->setContent($entry->content->value);
    	
    	//$this->_getEditLink($activityObject->addLink(), $item, $entry->id->value);
    	
    	if ($item->getPrefix() == 'stuffpress') {
    		$this->_getEditMediaLink($activityObject->addLink(), $item, $entry->id->value);	
    	}
    
    	return $entry;
    }
    
    /**
     * @param unknown_type $item
     * @param unknown_type $entry
     * @return AtomEntryAdapter
     */
    protected function buildAudioEntry($item, $entry=null) {
    	$this->_buildCommonItemEntryElement($entry, $item);
    	$entry->addNamespace(MediaNS::PREFIX, MediaNS::NS);
    	
   		$entryLink = $entry->addLink();
   		$entryLink->rel		= AtomNS::REL_ENCLOSURE;
   		$entryLink->type	= 'audio/mp3';
   		$entryLink->href	= $item->getAudioUrl();
   		
   		$entry->content			= '';
   		$entry->content->type	= 'audio/*';
   		$entry->content->src	= $item->getAudioUrl();
   		$entry->summary			= $this->xmlentities(strip_tags($item->getDescription()));
    	
		// build the activity entry    	
    	$activityEntry = $entry->getExtension(ActivityNS::NS);/* @var $activityEntry ActivityEntryExtension */
    	$activityEntry->addVerb(ActivityNS::SHARE_VERB);
    	
    	// build the activity object
    	$activityObject = $activityEntry->addObject();
    	
    	$application = Stuffpress_Application::getInstance();
    	$objectAuthor = $activityObject->addAuthor();
    	$objectAuthor->name	= $this->_user->username;
    	$objectAuthor->uri	= 'http://' . $this->_domain;
    	$objectAuthor->email	= $application->user->email;
    	
    	$audio = ActivityProcessorFactory::getInstance()->getProcessor($activityObject, ActivityNS::AUDIO_OBJECT_TYPE);/* @var $audio IActivityAudio */
    	$this->_buildCommonItemActivityObject($audio, $item);
    	
    	$audio->setAudioStream($item->getAudioUrl(), 'audio/mp3', 'unknown');
    	//$audio->setPlayerApplet('unknown', 'unknown', 'unknown', 'unknown');
    	$audio->setDescription($this->xmlentities($item->getDescription()));
    
    	//$this->_getEditLink($activityObject->addLink(), $item, $entry->id->value);
    	
    	//if ($item->getPrefix() == 'stuffpress') {
    	//	$this->_getEditMediaLink($activityObject->addLink(), $item, $entry->id->value);	
    	//}
    	
    	return $entry;
    }
    
    /**
     * @param unknown_type $item
     * @param unknown_type $entry
     * @return AtomEntryAdapter
     */
    protected function buildVideoEntry($item, $entry=null) {
    	$this->_buildCommonItemEntryElement($entry, $item);
    	$entry->addNamespace(MediaNS::PREFIX, MediaNS::NS);
     	
     	$entry->content			= $this->xmlentities($item->getEmbedCode(100, 100) . "<br/>" . $item->getDescription() . "<br/>If you don't see the video, watch it <a href='" . $item->getVideoUrl() . "'>here</a>");
     	$entry->content->type	= AtomNS::TYPE_HTML;
     	
		// build the activity entry    	
    	$activityEntry = $entry->getExtension(ActivityNS::NS);/* @var $activityEntry ActivityEntryExtension */
    	$activityEntry->addVerb(ActivityNS::SHARE_VERB);
    	
    	// build the activity object
    	$activityObject = $activityEntry->addObject();
    	
    	$application = Stuffpress_Application::getInstance();
    	$objectAuthor = $activityObject->addAuthor();
    	$objectAuthor->name	= $this->_user->username;
    	$objectAuthor->uri	= 'http://' . $this->_domain;
    	//$objectAuthor->email	= $application->user->email;
    	
    	$video = ActivityProcessorFactory::getInstance()->getProcessor($activityObject, ActivityNS::VIDEO_OBJECT_TYPE);/* @var $video IActivityVideo */
		$this->_buildCommonItemActivityObject($video, $item);
    	
    	$video->setThumbnail($item->getImageUrl(ImageItem::SIZE_THUMBNAIL), 'image/jpeg', 'unknown', 'unknown');
    	$video->setVideoStream($item->getVideoUrl(), 'videos/*', 'unknown');
    	//$video->setPlayerApplet('unknown', 'unknown', 'unknown', 'unknown');
    	$video->setDescription($this->xmlentities($item->getDescription()));
    	$video->setContent($this->xmlentities($item->getEmbedCode(100, 100) . "<br/>" . $item->getDescription() . "<br/>If you don't see the video, watch it <a href='" . $item->getVideoUrl() . "'>here</a>"));
    
    	//$this->_getEditLink($activityObject->addLink(), $item, $entry->id->value);
    	
    	return $entry;
    }
	
	protected function _buildCommonItemEntryElement(AtomEntryAdapter $entry, $item) {
    	
    	$entry->id				= 'http://' . $this->_domain . "/entry/" . $item->getSlug();
    	$entry->title			= $this->xmlentities($item->getPreamble() . " " . $item->getTitle());
		$entry->updated			= toAtomDate($item->getTimestamp()); //actually this is published
		
		$this->_getAlternativeLink($item, $entry->addLink());
		//$this->_getEditLink($entry->addLink(), $item, $this->_getObjectId($item));
	}
    
    protected function _buildCommonItemActivityObject(IActivityDefault $object, $item) {
    	$object->id			= 'http://' . $this->_domain . "/object/" . $item->getSlug();
    	$object->title		= $this->xmlentities(strip_tags($item->getTitle()));
    	//$object->permalink	= $this->_getAlternativeLink($item);
    }	
    #endregion buildActivitiesFeed
	
    #region readEntry
	/**
	 * @param AtomEntryAdapter $item
	 * @return array
	 */
	public function readEntry(AtomEntryAdapter $entry) {	
    	// Get the activity entry
    	$activityEntry	= $entry->getExtension(ActivityNS::NS);
    	
    	$data = array();
    	
		$published = $entry->getPublished(); 
    	if (isset($published)) {
    		// The published value of the data is only obtained from the atom:published, otherwise it is not exists
    		$data['published'] = toTimestamp($published->value); 
    	}
    	else {
    		$data['published'] = '';
    	}
    	
    	// process the entry
    	if(isset($activityEntry->object[0])) {  

    		if (isset($activityEntry->object[0]->id)) {
	    		// Get the Object IRI
	    		$data['iri'] = $activityEntry->object[0]->id->value;
    		}
    		else {
    			$data['iri'] = '';
    		}
    		
    		// Get the Object processor
    		$activityProcessor = ActivityProcessorFactory::getInstance();
    		$object		= $activityProcessor->getProcessor($activityEntry->object[0]);
    	
			// Process the entry according to its type
			switch ($object->getType()) {
		    	case ActivityNS::STATUS_OBJECT_TYPE:
		    		$data = $this->_readStatusEntry($data, $object);
		    		break;
		    	case ActivityNS::ARTICLE_OBJECT_TYPE:
		    		$data = $this->_readBlogEntry($data, $object);
		    		break;
		    	case ActivityNS::BOOKMARK_OBJECT_TYPE:
		    		$data = $this->_readLinkEntry($data, $object);
		    		break;
		    	case ActivityNS::PHOTO_OBJECT_TYPE:
		    		$data = $this->_readImageEntry($data, $object);
		    		break;
		    	case ActivityNS::AUDIO_OBJECT_TYPE:
		    		$data = $this->_readAudioEntry($data, $object);
		    		break;
		    	case ActivityNS::VIDEO_OBJECT_TYPE:
		    		$data = $this->_readVideoEntry($data, $object);
		    		break;
		    	default:
		    		$data = $this->_readDefaultEntry($data, $entry);
		    }
    	}
    	else {
    		$data = $this->_readDefaultEntry($data, $entry);
    	}    	
    	return $data;
	}
	
	/**
	 * @param AtomEntryAdapter $item
	 * @return array
	 */
	public function readCommentEntry(AtomEntryAdapter $entry) {	
    	// Get the activity entry
    	$activityEntry	= $entry->getExtension(ActivityNS::NS);
    	
    	$data = array();
    	
		$published = $entry->getPublished(); 
    	if (isset($published)) {
    		// The published value of the data is only obtained from the atom:published, otherwise it is not exists
    		$data['timestamp'] = toTimestamp($published->value); 
    	}
    	else {
    		$data['timestamp'] = '';
    	}
    	
    	// process the entry
    	if(isset($activityEntry->object[0])) {  

    		$object = $activityEntry->object[0];
    		
    		if (isset($activityEntry->object[0]->id)) {
	    		// Get the Object IRI
	    		$data['iri'] = $activityEntry->object[0]->id->value;
    		}
    		else {
    			$data['iri'] = '';
    		}
    		
    		$data['comment'] = $object->content->value;
    		
    		$author = $object->author[0];
    		
    		$data['name']		= @$author->name;
    		$data['email']		= @$author->email;
    		$data['website']	= @$author->uri;
    	}    	
    	return $data;
	}
	
    /**
     * @param array $data
     * @param AtomEntryAdapter $entry
     * @return AtomEntryAdapter
     */
    protected function _readDefaultEntry($data, AtomEntryAdapter $entry) {
    	$data['type']	= SourceItem::BLOG_TYPE;
    	$data['title']	= $entry->getTitle()->value;
    	$data['text']	= $entry->getContent()->value;    	
    	return $data;
    }
    
    /**
     * @param array $data
     * @param IActivityStatus $object
     * @return array
     */
    protected function _readStatusEntry($data, IActivityStatus $object) {
    	$data['type']	= SourceItem::STATUS_TYPE;
    	$data['title']	= $object->getContent();    	
    	return $data;
    }
    
    /**
     * @param array $data
     * @param IActivityArticle $object
     * @return array
     */
    protected function _readBlogEntry($data, IActivityArticle $object) {
    	$data['type']	= SourceItem::BLOG_TYPE;
    	$data['title']	= $object->getTitle();
    	$data['text']	= $object->getContent();    	
    	return $data;
    }
    
    /**
     * @param array $data
     * @param IActivityBookmark $object
     * @return array
     */
    protected function _readLinkEntry($data, IActivityBookmark $object) {
    	$data['type']	= SourceItem::LINK_TYPE;
		$data['title']	= $object->getTitle();
		$data['link']	= $object->getTargetUrl();
		$data['text']	= $object->getDescription();    	
    	return $data;
    }
    
    /**
     * @param array $data
     * @param IActivityPhoto $object
     * @return array
     */
    protected function _readImageEntry($data, IActivityPhoto $object) {
    	$data['type']	= SourceItem::IMAGE_TYPE;
		$data['title']	= $object->getTitle();
		$data['text']	= $object->getDescription(); 
		$url = $object->getLargerImage();
		if (isset($url)) {
			$data['url']	= $object->getLargerImage()->getHref();
		} 
		else {
			$data['url'] = '';
		}  	
    	return $data;
    }
    
    /**
     * @param array $data
     * @param IActivityAudio $object
     * @return array
     */
    protected function _readAudioEntry($data, IActivityAudio $object) {
    	$data['type']	= SourceItem::AUDIO_TYPE;
		$data['title']	= $object->getTitle();
		$data['text']	= $object->getDescription(); 
		$url =  $object->getAudioStream();
		if (isset($url)) {
			$data['url']	= $object->getAudioStream()->getHref();
		}  	
		else {
			$data['url'] = '';
		}
    	return $data;
    }	
    
    /**
     * @param array $data
     * @param IActivityVideo $object
     * @return array
     */
    protected function _readVideoEntry($data, IActivityVideo $object) {
    	$data['type']	= SourceItem::VIDEO_TYPE;
		$data['title']	= $object->getTitle();
		$data['text']	= $object->getDescription();    	
    	return $data;
    }
    #endregion readEntry
    
    protected function _getObjectId($item) { // the id should be changed into an IRI - later
    	return $item->getSource() . "_" . $item->getID() . "_" . $item->getType();
    }
    
    protected function _getAlternativeLink($item, AtomLinkAdapter $linkAdapter=null, $type=null) {
    	
    	$application = Stuffpress_Application::getInstance();
    	
		// Get the url of the logged in user
		$currentUrl = $this->_domain;
    	
		// Construct the alternative link
    	$href = 'http://' . $currentUrl . "/entry/" . $item->getSlug();
    	
    	// Set the type and href attribute if the $linkAdapter is passed in the parameter
    	if ($linkAdapter !== null) {
	    	$linkAdapter->rel	= AtomNS::REL_ALTERNATE;
	    	$linkAdapter->type	= $type;
	    	$linkAdapter->href	= $href;
    	}
    	
    	// return the alternative link
    	return $href;
    }
    	
    protected function _getEditLink(AtomLinkAdapter $linkAdapter, $item, $id) {
    	
    	$application = Stuffpress_Application::getInstance();
    	
    	$linkAdapter->rel	= AtomNS::REL_EDIT;
    	//$linkAdapter->href	= 'http://' . $application->getPublicDomain() . '/api/' . $item->getType() . '/' . $id . '?source=' . $item->getSource() . '&id=' . $item->getID(); 
    	$linkAdapter->href	= 'http://' . $this->_domain . '/api/activities/' . $id;
    }
    	
    protected function _getEditMediaLink(AtomLinkAdapter $linkAdapter, $item, $id) {
    	
    	$application = Stuffpress_Application::getInstance();
    	
    	//$linkAdapter->rel	= AtomNS::REL_EDIT_MEDIA;
    	//$linkAdapter->href	= 'http://' . $application->getPublicDomain() . '/api/' . $item->getType() . '/' . $id . '?source=' . $item->getSource() . '&id=' . $item->getID(); 
    	$linkAdapter->href	= 'http://' . $this->_domain . '/api/media/' . $id;
    }
    
    /**
     * Returns a feed of comment of an item
     * 
     * @param array $comments
     * @param mixed $item
     * @return AtomFeedAdapter
     */
    public function buildCommentsFeed($comments, $item) {
    	
		$application = Stuffpress_Application::getInstance();
		
		$feed = new AtomFeedAdapter(null);
    	$feed->addNamespace(ActivityNS::PREFIX, ActivityNS::NS);
    	
    	$feed->title		= "Comments of " . $this->_getObjectId($item) ;
    	$feed->id			= "http://" . $this->_domain . "/api/comments"; // 
    	$feed->updated		= toAtomDate(time());
    	
    	$feedLink	= $feed->addLink();
    	$feedLink->rel		= 'self';
    	$feedLink->href		= 'http://' . $this->_domain . "/api/comments/" . $this->_getObjectId($item);
    	
		foreach ($comments as $comment) {
			$this->buildCommentEntry(new Comment($comment), $item, $feed->addEntry());
		}
		
		return $feed;
    }
    
    /**
     * Returns an entry of comment
     * 
     * @param Comment 				$comment
     * @param AtomEntryAdapter|null $entry
     * @return AtomEntryAdapter
     */
    public function buildCommentEntry($comment, $item, $entry=null) {
    	$entry = $this->_prepareEntry($entry);    	
    	
    	$entry->id				= $this->_getCommentId($comment);
    	$entry->title			= $comment->getAuthorName() . "\'s comment";
		$entry->updated			= toAtomDate(strtotime($comment->getPublished())); //actually this is published
		$entry->published		= toAtomDate(strtotime($comment->getPublished()));
    	
    	$entry->content			= $this->xmlentities($comment->getText());
    	$entry->content->type	= AtomNS::TYPE_HTML;
    	
		// build the activity entry
    	$activityEntry = $entry->getExtension(ActivityNS::NS);/* @var $activityEntry ActivityEntryExtension */
    	$activityEntry->addVerb(ActivityNS::POST_VERB);
    	
    	//build the activity object
    	$activityObject = $activityEntry->addObject();
    	
    	$activityObject->addObjectType(ActivityNS::COMMENT_OBJECT_TYPE);
    	
    	$activityObject->id		= $comment->getCommentId($comment);
    	$activityObject->title	= '';
    	
    	$author = $activityObject->addAuthor();
    	
    	$name 	= $comment->getAuthorName();
    	$uri	= $comment->getAuthorWebsite();
    	$email	= $comment->getAuthorEmail();
    	
    	if ($name != '')	{$author->name 	= $name;}
    	if ($uri != '')		{$author->uri	= $uri;}
    	if ($email != '')	{$author->email	= $email;}
    	
    	$activityObject->content		= $comment->getText();
    	$activityObject->content->type	= AtomNS::TYPE_HTML;
    	
    	
		// Set Alternative Link
		$objectAltLink = $activityObject->addLink();
		$objectAltLink->setRel(AtomNS::REL_ALTERNATE);
		$objectAltLink->setType('text/html');
		$objectAltLink->setHref('');
		
		
    	$application = Stuffpress_Application::getInstance();
    	$href	= 'http://' . $this->_domain . '/api/comments/' . $this->_getObjectId($item) . '/' . $this->_getCommentId($comment);
    
		
		// Set Edit Link
		$ObjectEditLink = $activityObject->addLink();
		$ObjectEditLink->setRel(AtomNS::REL_EDIT);
		$ObjectEditLink->setHref($href);
		return $entry;
    }
    
	protected function _getCommentId($comment) { // the id should be changed into an IRI - later
    	return $comment->getItemSourceId() . "_" . $comment->getItemId() . "_" . $comment->getCommentId() . "_comment";
    }
    
	private function xmlentities($string)
	{
		$string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
		return str_replace ( array ( '&', '"', "'", '<', '>' ), array ( '&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;' ), $string);
	}
}