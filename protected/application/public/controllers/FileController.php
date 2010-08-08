<?php
/*
 *    Copyright 2008-2009 Laurent Eschenauer and Alard Weisscher
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

class FileController extends Stuffpress_Controller_Action 
{
	protected $_application;
	
    public function preDispatch()
    {
		$this->_application = Stuffpress_Application::getInstance();
    }
    
	public function indexAction()
	{
		$this->_forward('view');
	}
    
	public function viewAction()
	{
		// We view a file, so we should disable layout
		$this->_helper->layout->disableLayout();	
		$this->_helper->viewRenderer->setNoRender();
		
		$key 	= $this->_getParam('key');
		$size 	= $this->_getParam('size');
		$inline = $this->_getParam('inline');
		
		if (strpos($key, '.')) {
			$key = substr($key, 0, strpos($key, '.'));
		}
		
		$files 	= new Files();
		if (!($file = $files->getFileFromKey($key))) {
			throw new Stuffpress_Exception("No such file for key $key");
		}
		
		if ($size == 'thumbnail') {
			$folder = '/thumbnails/';
		}
		else if ($size == 'small') {
			$folder = '/small/';
		}		
		else if ($size == 'medium') {
			$folder = '/medium/';
		}
		else if ($size == 'large') {
			$folder = '/large/';
		}
		else {
			$folder = '/';
		}
		
		$root = Zend_Registry::get("root");
		$config	= Zend_Registry::get("configuration");
		
		if (isset($config) && isset($config->path->upload)) {
			$upload = $config->path->upload ;
		} else {
			$upload = $root . "/upload/";
		}
		
	    $path = $upload . "/{$folder}{$file->key}";
	    
	    if ($folder != '/' && ! file_exists($path)) {
	    	$path = $upload . "/{$file->key}";
	    }
	    
	    // Dump the file
		if (!$inline) header("Content-Disposition: attachment; filename=\"{$file->name}\"");
		header("Content-type: {$file->type}");
		header('Content-Length: '. filesize($path)); 
      	
		/*$this->getResponse()->setHeader('Expires', '', true);
   	    $this->getResponse()->setHeader('Cache-Control', 'public', true);
      	$this->getResponse()->setHeader('Cache-Control', 'max-age=3800');
        $this->getResponse()->setHeader('Pragma', '', true);*/

		
		// We turn off output buffering to avoid memory issues
	    // when dumping the file
	    ob_end_flush();
	  	readfile($path);
	  	
	  	// Die to make sure that we don't screw up the file
	  	die();
	}
	
	public function uploadimageAction() {
		// Where we come from
		$source		= $this->_getParam('source');
		
		// Verify that it is authorized
		if (!in_array($source, array('design', 'profile'))) {
			throw new Stuffpress_Exception("Invalid source specified $source");
		}
		
		// What are we uploading 
		$image		= $this->_getParam('image');
		$property 	= "{$image}_image";
		 
		// Was a file uploaded ?
		if (!isset($_FILES['file'])) {
			$this->addErrorMessage('Upload failed: no files received on server end.');
			return $this->_forward('index', $source, 'admin');
		}
		
		// Validate the uploaded file
		$tmp_file   = $_FILES['file']['tmp_name'];
		$file_name  = basename($_FILES['file']['name']);
		$file_type  = $_FILES['file']['type'];
		$file_ext   = substr(trim(substr($file_name, strrpos($file_name, '.')), '.'), 0, 4); // returns the ext only

		// Check file size
		if ($_SERVER['CONTENT_LENGTH'] > 2000000) {		
			$this->addErrorMessage('Upload failed: your file size is above 2Mbytes.');
			return $this->_forward('index', $source, 'admin');
		}

		// Check file extension
		if (!in_array(strtolower($file_ext), array("gif","jpg","png","jpeg"))) {
			$this->addErrorMessage('Upload failed: we only support jpg, gif and png files.');
			return $this->_forward('index', $source, 'admin');
		}
		
		// Assign a random name to the file
		$key	  	= Stuffpress_Token::create(32);
		$root = Zend_Registry::get("root");
		$config	= Zend_Registry::get("configuration");
		
		if (isset($config) && isset($config->path->upload)) {
			$upload = $config->path->upload ;
		} else {
			$upload = $root . "/upload/";
		}
		
		$uploadfile = $upload . '/'. $key;

		// Move the file to the upload folder
		if (!move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
			$this->addErrorMessage('Upload failed: your file size is above 2Mbytes.');
			return $this->_forward('index', $source, 'admin');
		}	
		
		// Store the file in the database
		$files 		= new Files(array(Stuffpress_Db_Table::USER => $this->_application->user->id));
		$file_id 	= $files->addFile($key, $file_name, "Lifestream custom image", $file_type, $file_ext);
		
		// Build a thumbnail of the file
		try {
			$files->fitSquare($file_id, 75,  'thumbnails');
		}
		catch (Exception $e) {
			$message = $e->getMessage();
			$this->addErrorMessage("Upload failed: could not process image ($message)");
			$files->deleteFile($key);
			return $this->_forward('index', $source, 'admin');
		}
		
		// Replace the user property with the new file and delete the older one
		$properties = new Properties(array(Properties::KEY => $this->_application->user->id));
		$old_file 	= $properties->getProperty($property);
		$properties->setProperty($property, $key);
		if ($old_file) $files->deleteFile($old_file);

		// If we are here, everything went smooth
		$this->addStatusMessage('Your file was successfully uploaded');
		return $this->_forward('index', $source, 'admin');
	}
	
}