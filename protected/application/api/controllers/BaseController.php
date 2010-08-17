<?php

// I don't why the Zend_Rest_Controller is not recognized here, I use Zend_Controller_Action until I know how to solve it
abstract class Api_BaseController extends Zend_Controller_Action {
	const HTTP_SUCCESS	= 200;
	const HTTP_FAILED	= 400;
	const HTTP_CREATED	= 201;
	
	const CONTENT_TYPE_ATOM		= 'application/atom+xml';
	const CONTENT_TYPE_TEXT		= 'text/html; charset=UTF-8';
	
	protected $_application;
	
	public function init()
    {
    	// Prevent the layout to be rendered
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
            
    }
    
    protected function _authenticateUser($public=false) {
    	
        // Get username and password from the request
        $username = $this->getRequest()->getParam('username');
        $password = $this->getRequest()->getHeader('Password');
    	
    	if (!$username) {
    		$this->_buildResponse(self::HTTP_FAILED, 'No username is provided!!!');
    		return false;
    	}
    	
    	// Login the user
    	$users	= new Users();
		if (!$user = $users->getUserFromUsername($username)) {
    		$this->_buildResponse(self::HTTP_FAILED, 'No user with such username!!!');
			return false;
		}
		
		if (!$public) {
	    	// Validate the password
			if ($user->password != md5($password)) {
				$this->_buildResponse(self::HTTP_FAILED, 'Wrong Password!!!');
				return false;
			}
		}
    	
		// Everything ok, we can log in and assign role
		$this->_application = Stuffpress_Application::getInstance();
		$this->_application->user = $user;
		$this->_application->role = 'member';
		return true;
    }
    
    protected function _buildResponse($responseCode, $body=null, $contentType=self::CONTENT_TYPE_TEXT) {
		header("Content-Type: $contentType" , true, $responseCode);
		echo $body;
		die();
    }
    
    protected function _getItemIri($paramName = 'id') {
    	return $this->getRequest()->getParam($paramName);
    }
    
    protected function _getCommentIri() {
    	return $this->getRequest()->getParam('commentid');
    }
    
    protected function _getIriFromItem($id, $source_id, $type) {
    	return $source_id . "_" . $id . "_" . $type;
    }
    
    // This function should be added to the Data class if possible & the IRI is still the simple IRI
    protected function _getItemByIri($iri) {
    	$id = explode('_', $iri, 3);
    	$source_id  = @$id[0];
    	$item_id	= @$id[1];
    	$item_type	= @$id[2]; 
    
    	$data_table = new Data();
		$item   = $data_table->getItem($source_id, $item_id);	
		
		return $item;
    }

    /**
     * Get comment by IRI
     * 
     * @param string $iri
     * @return Comment
     */
    protected function _getCommentByIri($iri) {
    	$id = explode('_', $iri, 4);
    	$source_id  = @$id[0];
    	$item_id	= @$id[1];
    	$comment_id	= @$id[2]; 
    	$item_type	= @$id[3]; 
    	
    	// Gets the comment
    	$comments  	= new Comments();
		$comments->setUser($this->_application->user);
	    foreach($comments->getComments($source_id, $item_id) as $comment) {
			if ($comment['id'] == $comment_id) {
				return new Comment($comment);
			}
		}
    }
    
    // To Show the information of the request -> will be obsoleted after the API is finished
    protected function _showInfo() {
    	$response	= $this->getResponse();
    	$request	= $this->getRequest();
    	
    	$response->appendBody($request->getActionName() . " of ");
    	$response->appendBody($request->getControllerName() . "<br/>");
    	$response->appendBody("Response:<br/>");
    }
    
	/**
     * @param array $data
     * @return StuffpressItem
     */
    protected function _saveItem($data) {
    	
    	// Add the item
		$source		= StuffpressModel::forUser($this->_application->user->id);
		$data_table = new Data();
		$item_id 	= $source->addItem($data, $data['published'], $data['type'], '', false, false, $data['title']);
		$source_id 	= $source->getID();
		
		// Fetch the newly created item
		$item   = $data_table->getItem($source_id, $item_id);	

		return $item;
    }
    
    /**
     * @param array $data
     * @return StuffpressItem
     */
    protected function _updateItem($data, $source_id, $item_id) {
    	
    	// Update the item
		$source		= StuffpressModel::forUser($this->_application->user->id);
		$data_table = new Data();
		$source->updateItem($item_id, $data, $data['published']);
		
		// Fetch the newly updated item
		$item   = $data_table->getItem($source_id, $item_id);	
		
		return $item;
    }
    
    protected function _deleteItem($source_id, $item_id, $file_key) {
    	$data_table = new Data();
		$data_table->deleteItem($source_id, $item_id);
		
		if (strlen($file_key) >=0) {
			$files = new Files();
			$files->setUser($this->_application->user->username);
			$files->deleteFile($file_key);
		}
    }
    
    protected function _ResizeFile($files, $file_id) {
		@$files->fitWidth($file_id, 240,  'small');
		@$files->fitWidth($file_id, 500,  'medium');
		@$files->fitWidth($file_id, 1024, 'large');
		@$files->fitSquare($file_id, 75,  'thumbnails');
    }
    
    protected function _storeTempFile($filename, $fileData, $filetype) {
    	// Get the temp path for the file
		$root 		= Zend_Registry::get("root");
		$config		= Zend_Registry::get("configuration");
		
		if (isset($config) && isset($config->path->temp)) {
			$temp_path = $config->path->temp . "/$filename";
		} else {
			$temp_path 	= $root . "/temp/" . $filename;
		}
    	
    	// Write a new binnary file to the temp path
		$handle		= fopen($temp_path, 'wb');
		fwrite($handle, $fileData);
		fclose($handle);
		
    	// Create a fake FILES variable
    	$file['tmp_name']	= 'tmp/tempfromchronicleapi';
		$file['name']		= $filename;
		$file['type']		= $filetype;
		
		return $file;
    }
    
    protected function _isItemExists($item) {
    	if (!isset($item)) {
    		$this->_buildResponse(Api_BaseController::HTTP_FAILED, 'Item does not exists');
    		return false;
    	}
    	return true;
    }
    
    protected function _saveFileToDb($type, $url, $file=null) {
    	$files = new Files();
		$files->setUser($this->_application->user->username);

		// the audio file should be handled as well
    	if ((($type == SourceItem::IMAGE_TYPE) || (($type == SourceItem::AUDIO_TYPE))) && (strlen($url) > 0)) {
			$file_id 	= $files->downloadFile($url, "download");
			$file 		= $files->getFile($file_id);
			$key 		= $file->key;
			
			// There should be an exception handle here
			// to handle any possible exception from the processes above
		}
		else if ($file) {
    		try {
				// Process new file
				$file_id = $files->processFile($file['name'], $file, 'Posted file');
				$file = $files->getFile($file_id);
				$key = $file->key;
			}
			catch(Exception $e) {
				$this->_buildResponse(Api_BaseController::HTTP_FAILED, 'Failed to process the data');
				return false;
			}
		}
		else {
			$this->_buildResponse(Api_BaseController::HTTP_FAILED, 'Failed to process the data');
			return false;
		}
		
    	// If an image and file available, resize
		if (($type == SourceItem::IMAGE_TYPE) && ($file_id > 0)) {
			$this->_ResizeFile($files, $file_id);
			//$exif = $files->readExif($file_id);
		}

		return $key;
    }
    
    protected function _deleteFileFromDb($key) {
    	$files = new Files();
    	$files->setUser($this->_application->user->username);
		$files->deleteFile($key);    	
    }
}