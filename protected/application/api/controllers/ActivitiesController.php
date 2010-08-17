<?php
class Api_ActivitiesController extends Api_BaseController
{
	protected $_atomProcessor;

	// Returns an activity feed
    public function indexAction()
    {    	    	        
        if (!$this->_authenticateUser(true)) {
        	return;	
        }
        
        $maxResult = $this->getRequest()->getParam('maxResult');
        if (!isset($maxResult)) {
        	$maxResult = 20;
        }
        
    	// Get the Latest Item
    	$items = $this->_getLatestItems($maxResult);
    	
    	// Build the Activities Feed from the latest items
    	$atomProcessor = new AtomProcessor();
    	$feed = $atomProcessor->buildActivitiesFeed($items);

    	// Set responses and response code 
    	$this->_buildResponse(Api_BaseController::HTTP_SUCCESS, $feed->getXml(), Api_BaseController::CONTENT_TYPE_ATOM);
    }

    // Returns an entry of requested item --> requested by IRI
    public function getAction()
    {    	    	        
        if (!$this->_authenticateUser(true)) {
        	return;	
        } 
         
    	// Get the item    
    	$item = $this->_getItemByIri($this->_getItemIri());
    	if (!$this->_isItemExists($item)) {
    		return;
    	}

		// Build the Entry
		$atomProcessor = new AtomProcessor();
		$entry = $atomProcessor->buildItemEntry($item);
		
		// Set responses and response code 
		$this->_buildResponse(Api_BaseController::HTTP_SUCCESS, $entry->getXml(), Api_BaseController::CONTENT_TYPE_ATOM);
    }

    // Create new item
    public function postAction()
    {    	    	        
        if (!$this->_authenticateUser()) {
        	return;	
        }  	
    	
		// Get the raw body of the request
    	$rawBody = $this->getRequest()->getRawBody();
        
    	// Get the content-type of the request
    	$contentType = $this->getRequest()->getHeader('Content-Type');
    	$contentType = explode(';', $contentType, 2);  
    	
    	// Process the request based on the content type of the request
    	if ($contentType[0] == 'application/atom+xml') {
        	$data = $this->_postEntry($rawBody);
        }
        // Assume all post request with content type other than "application/atom+xml" is a media type
        else {
        	
        	// Only image and audio are supported
        	$fileType		= explode('/', $contentType[0], 2);
	        if ($fileType[0] != SourceItem::IMAGE_TYPE && $fileType[0] != SourceItem::AUDIO_TYPE) {
	    		$this->_buildResponse(Api_BaseController::HTTP_FAILED, 'Unsupported media type');
	    		return;
	    	}
        	$data = $this->_postMedia($rawBody);
        }
        
        // Check if the post process is successful
        if ($data != false) {
        	
        	// Save to the database
    		$item = $this->_saveItem($data);
    		
    		// Build the entry for the response
    		$atomProcessor = new AtomProcessor();
    		$newEntry = $atomProcessor->buildItemEntry($item);
    			    	
    		// Build the response
    		$this->_buildResponse(Api_BaseController::HTTP_CREATED, $newEntry->getXml(), Api_BaseController::CONTENT_TYPE_ATOM);	
        }
    }

    // Edit an item by IRI
    public function putAction()
    {   	
    	// Get the content-type of the request
    	$contentType 	= $this->getRequest()->getHeader('Content-Type');
    	$contentType 	= explode(';', $contentType, 2);
    	
    	// Only application/atom+xml content type is allowed
    	if ($contentType[0] != 'application/atom+xml') {
    		$this->_buildResponse(Api_BaseController::HTTP_FAILED, 'Unsupported content-type');
    		return;
    	}
    	
        if (!$this->_authenticateUser()) {
        	return;	
        }
        		
    	// Get the Item from IRI
		$item	= $this->_getItemByIri($this->_getItemIri());
    	if (!$this->_isItemExists($item)) {
    		return;
    	}
    	
		// Get the raw body of the request
    	$rawBody = $this->getRequest()->getRawBody();
    	
    	// Process the request based on the content type of the request
    	$data = $this->_putEntry($item, $rawBody);
        
        // Check if the put process is successful
   		if ($data != false) {
   			
        	// Update the data
    		$item = $this->_updateItem($data, $item->getSource(), $item->getID());
    		
    		// Build the entry for the response
    		$atomProcessor = new AtomProcessor();
    		$newEntry = $atomProcessor->buildItemEntry($item);

    		// Build the response
    		$this->_buildResponse(Api_BaseController::HTTP_SUCCESS, $newEntry->getXml(), Api_BaseController::CONTENT_TYPE_ATOM);	
        }
    }

    // Delete an item
    public function deleteAction()
    {    	    	        
        if (!$this->_authenticateUser()) {
        	return;	
        }
        
    	// Get the Item from IRI
    	$item = $this->_getItemByIri($this->_getItemIri());
    	if (!$this->_isItemExists($item)) {
    		return;
    	}
    	
    	// Only stuffpress item can be deleted
    	if ($item->getPrefix() == 'stuffpress') {
	    	// Delete the item based on the source_id and item_id
			$this->_deleteItem($item->getSource(), $item->getID(), $item->getFile());	

			// Set response code
			$this->_buildResponse(Api_BaseController::HTTP_SUCCESS); 
    	}
    	else {
    		// Set response code
    		$this->_buildResponse(Api_BaseController::HTTP_FAILED, 'The resource cannot be deleted');
    	}
    }
    
    protected function _postEntry($rawBody) {    	
    	// Get the Atom Adapter
    	$entry = AtomDocumentAdapterFactory::getInstance()->adapt($rawBody);  /* @var $entry AtomEntryAdapter */
    	
    	// Check whether the entry is an atom entry or not
    	if ($entry->getDocumentType() == Atomns::ENTRY_ELEMENT) {
			
    		$atomProcessor = new AtomProcessor();
    		
    		// Read the Entry
    		$data = $atomProcessor->readEntry($entry);
    		
    		if ($data['type'] == SourceItem::VIDEO_TYPE) {
    			$this->_buildResponse(Api_BaseController::HTTP_FAILED, 'Create new Video item is not supported by the API');
    			return;
    		}
    		
    		if($data['published'] == '') {
    			$data['published'] = time();
    		}
    		
	    	// If an image (should be audio as well) and the URL was given, we download the image and store it
			if (($data['type'] == SourceItem::IMAGE_TYPE) || ($data['type'] == SourceItem::AUDIO_TYPE)){
				
				if(($url = $data['url']) && (strlen($url) > 0)) {
					$key = $this->_saveFileToDb($data['type'], $url);
					if ($key == false) {
						return false;
					}
				
				
					$data['file'] = $key;
				}
				else {
					$this->_buildResponse(Api_BaseController::HTTP_FAILED, 'Incomplete data!!!');
    				return false;
				}
			}
    		
    		return $data;
    	}
    	else {
    		$this->_buildResponse(Api_BaseController::HTTP_FAILED, 'Invalid Data!!! Atom Entry Document is required!!');
    		return false;
    	}
    }
    
    protected function _postMedia($rawBody) {
    	// Get the file information from header
    	$filename	= $this->getRequest()->getHeader('SLUG');
    	$filetype	= $this->getRequest()->getHeader('Content-Type');
    	    	
    	// Get the other information
    	list($valuestype, $specific_type) = explode('/', $filetype, 2);
    	
    	$file = $this->_storeTempFile($filename, $rawBody, $filetype);
		
		// Save the posted file to database
		$key = $this->_saveFileToDb($valuestype, '', $file);
		if ($key==false) {
			return false;
		}
		// Process the date if available - I don't understand how to use the values['date-type'], I'll figure it out later		
		$timestamp = time();
		
		// Prepare the data
		$data = array();
		$data['published']  = @$timestamp;
		$data['type']	    = @$valuestype;
		$data['file']	    = $key;
		$data['url']	    = '';
		$data['title'] 		= 'uploaded ' . $valuestype . ':' . $filename; // the only information I have is only the filename
		$data['link']  		= '';
		$data['embed']  	= '';		
		$data['text']  		= $data['title'];
		$data['tags']		= '';
		$data['latitude']	= ''; // it is not provided in the
		$data['longitude']	= ''; // chronicle version in my computer
		
		return $data;
    }
    
    protected function _putEntry($item, $rawBody) {
    	
    	// Get the Atom Adapter
    	$entry = AtomDocumentAdapterFactory::getInstance()->adapt($rawBody);/* @var $entry AtomEntryAdapter */
    	
    	if ($entry->getDocumentType() == Atomns::ENTRY_ELEMENT) {
    		// Read the Entry
			$atomProcessor = new AtomProcessor();    		
    		$data = $atomProcessor->readEntry($entry);
    		if ($data['published'] == '') { 
	    		$data['published'] = $item->getTimestamp();
    		}
    		
    		// Check whether the new resource has different type
    		if ($data['type'] != $item->getType()) {
    			$this->_buildResponse(Api_BaseController::HTTP_FAILED, 'The updated resource has different type than the old one!!!');
    			return false;
    		}
    		
    		// Check whether the new resource has the same iri with the old one - it will be implemented later if the unit testing is ready (I think it works)
//    		if ($this->_getItemIri() != $data['iri']) {
//    			$this->_buildResponse(Api_BaseController::HTTP_FAILED, 'The updated resource has different IRI than the old one!!!');
//    			return false;
//    		}
    		unset ($data['iri']);
    		
    		// If an image (should be audio as well) and the URL was given and it is a stuffpress item, we change the image
    		if (($item->getPrefix() == 'stuffpress') && (($data['type'] == SourceItem::IMAGE_TYPE)  && ($data['type'] == SourceItem::AUDIO_TYPE)) && ($url = $data['url']) && (strlen($url) > 0)) {
				
    			// Check whether the url is the same as the old one - not tested yet
    			if ($item->getImageUrl(ImageItem::SIZE_LARGE) != $url) {
						    			
	    			$this->_deleteFileFromDb($item->getFile());
					
					$key = $this->_saveFileToDb($data['type'], $url);
					if ($key == false) {
						return false;
					}
					
					$data['file'] = $key;
					
    			}
			}
    		
    		return $data;
    	}
    	else {
    		$this->_buildResponse(Api_BaseController::HTTP_FAILED, 'Invalid Data!!! Atom Entry Document is required!!');
    		return false;
    	}
    }
    
    protected function _getLatestItems($latest) {
    	$data = new Data();
    	$data->setUser($this->_application->user->id);
    	return $data->getLastItems($latest);
    }
} 
 

//	$this->_authenticateUser(true);
//    	
//    // Prepare the value for the comment
//    $source_id	= 4;
//    $item_id	= 300;
//    $comment	= 'wow';
//    $name		= 'anonim';
//    $email		= 'anonim@anonim.anonim';
//    $website	= '';
//    $timestamp	= time();
//    $notify		= true;
//    
//    // Add the comment to the database
//	$comments  	= new Comments();
//	$comments->setUser($this->_application->user);
//	//echo print_r($comments->getComments(4, 301),true);
//	$comments->addComment($source_id, $item_id, $comment, $name, $email, null, $timestamp, $notify);
//    //$comments->deleteComment(9);

