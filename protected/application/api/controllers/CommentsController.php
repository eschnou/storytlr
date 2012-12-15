<?php
class Api_CommentsController extends Api_BaseController
{
	protected $_atomProcessor;
	
	// The rest director
	public function restAction() {
		
    	$request	= $this->getRequest(); 
    	
    	$item 		= $request->getParam('item');
    	$commentId	= $request->getParam('commentid');
    	
    	if (isset($item)) {
    		if (isset($commentId)) {	    	
		    	switch ($request->getMethod()) {
		    		case 'GET':
		    			$this->_forward('get');
		    			break;
		    		case 'POST':
		    		case 'PUT':
		    			$this->_forward('put');
		    			break;
		    		case 'DELETE':
		    			$this->_forward('delete');
		    			break;
		    	}
    		}
    		else {	    	
		    	switch ($request->getMethod()) {
		    		case 'GET':
		    			$this->_forward('index');
		    			break;
		    		case 'POST':
		    		case 'PUT':
		    			$this->_forward('post');
		    			break;
		    		case 'DELETE':
		    			$this->_forward('delete');
		    			break;
		    	}
    			
    		}
    	}  
    	// Set responses and response code 
    	$this->_buildResponse(Api_BaseController::HTTP_FAILED, 'Invalid URL');
    
	}

	// Returns a feed of comments of requested item
    public function indexAction()
    { 
        if (!$this->_authenticateUser(true)) {
        	return;	
        }     
         
    	// Get the item    
    	$item 	= $this->_getItemByIri($this->_getItemIri('item'));
    	if (!$this->_isItemExists($item)) {
    		return;
    	}
    	
    	// Get the comments
		$comments  	= new Comments();
		$comments->setUser($this->_application->user);
		
    	// Build the Activities Feed from the latest items
    	$atomProcessor = new AtomProcessor();
    	$feed = $atomProcessor->buildCommentsFeed($comments->getComments($item->getSource(), $item->getID()), $item);

    	// Set responses and response code 
    	$this->_buildResponse(Api_BaseController::HTTP_SUCCESS, $feed->getXml(), Api_BaseController::CONTENT_TYPE_ATOM);
    }

    // Returns a feed of comments of requested item --> requested by item IRI
    public function getAction()
    {    
        if (!$this->_authenticateUser(true)) {
        	return;	
        }     
         
    	// Get the item    
    	$item 	= $this->_getItemByIri($this->_getItemIri('item'));
    	if (!$this->_isItemExists($item)) {
    		return;
    	}
    	
    	// Get the comment
    	$comment = $this->_getCommentByIri($this->getRequest()->getParam('commentid'));
    	if ($comment instanceof Comment) {
    		// Build the Activities entry from the comment
	    	$atomProcessor = new AtomProcessor();
	    	$entry = $atomProcessor->buildCommentEntry($comment, $item);
			
	    	// Set responses and response code 
    		$this->_buildResponse(Api_BaseController::HTTP_SUCCESS, $entry->getXml(), Api_BaseController::CONTENT_TYPE_ATOM);
			return;
    	}
		
		// Set responses and response code 
    	$this->_buildResponse(Api_BaseController::HTTP_FAILED, 'Comment does not exists');
    }

    // Create new comment
    public function postAction()
    {      
        if (!$this->_authenticateUser()) {
        	return;	
        }  	
        
    	// Get the content-type of the request
    	$contentType = $this->getRequest()->getHeader('Content-Type');
    	$contentType = explode(';', $contentType, 2);  
    	
    	// Only application/atom+xml content type is allowed
    	if ($contentType[0] != 'application/atom+xml') {
    		$this->_buildResponse(Api_BaseController::HTTP_FAILED, 'Unsupported content-type');
    		return;
    	}
    	
    	// Get the item    
    	$item 	= $this->_getItemByIri($this->_getItemIri('item'));
    	if (!$this->_isItemExists($item)) {
    		return;
    	}
    	
		// Get the raw body of the request
    	$rawBody = $this->getRequest()->getRawBody();
    	
        $comment = $this->_postCommentEntry($rawBody, $item);
        
        // Check if the post process is successful
        if ($comment instanceof Comment) {
        	// Build the entry for the response
    		$atomProcessor = new AtomProcessor();
    		$entry = $atomProcessor->buildCommentEntry($comment, $item);
    		
	    	// Set responses and response code 
    		$this->_buildResponse(Api_BaseController::HTTP_CREATED, $entry->getXml(), Api_BaseController::CONTENT_TYPE_ATOM);
			return;
        }
    }

    // Edit an item by IRI
    public function putAction()
    {   
    	// Set responses and response code 
    	$this->_buildResponse(Api_BaseController::HTTP_FAILED, 'Invalid URL');
    }

    // Delete an item
    public function deleteAction()
    {     
        if (!$this->_authenticateUser()) {
        	return;	
        }     
         
    	// Get the item    
    	$item 	= $this->_getItemByIri($this->_getItemIri('item'));
    	if (!$this->_isItemExists($item)) {
    		return;
    	}
    	
    	// Get the comment
    	$comment = $this->_getCommentByIri($this->getRequest()->getParam('commentid'));
    	if ($comment instanceof Comment) {
    		// Delete the comment
    		$comments = new Comments();
    		$comments->setUser($this->_application->user);
    		$comments->deleteComment($comment->getCommentId());
			
	    	// Set responses and response code 
    		$this->_buildResponse(Api_BaseController::HTTP_SUCCESS);
			return;
    	}
		
		// Set responses and response code 
    	$this->_buildResponse(Api_BaseController::HTTP_FAILED, 'Comment does not exists');
    }
    
    protected function _postCommentEntry($rawBody, $item) {    	
    	// Get the Atom Adapter
    	$entry = AtomDocumentAdapterFactory::getInstance()->adapt($rawBody);  /* @var $entry AtomEntryAdapter */
    	
    	// Check whether the entry is an atom entry or not
    	if ($entry->getDocumentType() == Atomns::ENTRY_ELEMENT) {
			
    		$atomProcessor = new AtomProcessor();
    		
    		// Read the Entry
    		$data = $atomProcessor->readCommentEntry($entry);
    		
    		
    		if($data['timestamp'] == '') {
    			$data['timestamp'] = time();
    		}
    		
    		$data['source_id']	= $item->getSource();
    		$data['item_id']	= $item->getID();
    		
    		// Add new comment
			$comments  	= new Comments();
			$comments->setUser($this->_application->user);
			$data['id'] = $comments->addComment($data['source_id'], $data['item_id'], $data['comment'], $data['name'], $data['email'], $data['website'], $data['timestamp'], 0);
    		
			
    		return new Comment($data);
    	}
    	else {
    		$this->_buildResponse(Api_BaseController::HTTP_FAILED, 'Invalid Data!!! Atom Entry Document is required!!');
    		return false;
    	}
    }
} 