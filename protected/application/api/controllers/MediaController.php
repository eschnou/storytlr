<?php
class Api_MediaController extends Api_BaseController
{
	protected $_atomProcessor;

    public function indexAction()
    {
    	// Set responses and response code 
    	$this->_buildResponse(Api_BaseController::HTTP_FAILED, 'Invalid URL');
    }

    public function getAction()
    {  
    	// Set responses and response code 
    	$this->_buildResponse(Api_BaseController::HTTP_FAILED, 'Invalid URL');
    }

   public function postAction()
    {
    	// Set responses and response code 
    	$this->_buildResponse(Api_BaseController::HTTP_FAILED, 'Invalid URL');
    }

    // Edit an item by IRI
    public function putAction()
    {     	
    	// Get the content-type of the request
    	$contentType 	= $this->getRequest()->getHeader('Content-Type');
    	$contentType 	= explode(';', $contentType, 2);
    	$fileType		= explode('/', $contentType[0], 2);
    	
    	// Only image and audio file are supported
    	if ($fileType[0] != SourceItem::IMAGE_TYPE && $fileType[0] != SourceItem::AUDIO_TYPE) {
    		$this->_buildResponse(Api_BaseController::HTTP_FAILED, 'Unsupported media type');
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
    	
    	// Only stuffpress media item can be edited
    	if ($item->getPrefix() != 'stuffpress') {
    		$this->_buildResponse(Api_BaseController::HTTP_FAILED, 'Uneditable media resource'.$item->getPrefix());
    		return;
    	}
    	
		// Get the raw body of the request
    	$rawBody = $this->getRequest()->getRawBody();
    	
    	
        $data = $this->_putMedia($item, $rawBody);
        
		
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
    	// Set responses and response code 
    	$this->_buildResponse(Api_BaseController::HTTP_FAILED, 'Invalid URL');
    }
    
    protected function _putMedia($item, $rawBody) {
    	// Get the file information from header
    	$filename	= $this->getRequest()->getHeader('SLUG');
    	$filetype	= $this->getRequest()->getHeader('Content-Type');
    	    	
    	// Get the other information
    	$type = explode('/', $filetype, 2);
    	$valuestype		= $type[0];
    	$specific_type	= $type[1]; 
    	
    	// Check whether the new resource has different type
    	if ($valuestype != $item->getType()) {
    		$this->_buildResponse(Api_BaseController::HTTP_FAILED, 'The updated resource has different type than the old one!!!');
    		return false;
    	}
    	
    	$file = $this->_storeTempFile($filename, $rawBody, $filetype);
		
    	$this->_deleteFileFromDb($item->getFile());
    	
    	$key = $this->_saveFileToDb($valuestype, '', $file);
    	if ($key == false) {
    		return false;
    	}   	
    	
//		// Create a fake $values variable
//		$values['file']		= $file['name'];
//		$values['type']		= $valuestype;
		
		// Prepare the values for the database
		$data = array();
		$data['file']	    = $key;
		$data['published']	= $item->getTimestamp();
		
		return $data;
    }
} 
 

