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

class Admin_PostController extends Admin_BaseController 
{

	protected $_bookmarklet;
	
	protected $_bkpage;
	
	protected $_bkurl;
	
	protected $_bktitle;
	
	protected $_bktext;
	
	public function init() {
		// Run the parent init to initialize all variables
		parent::init();
		
		// If request is Ajax, we disable the layout
		if ($this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout();
			$this->_helper->viewRenderer->setNoRender();
		}
		
		// If request is from a bookmarklet, we use another layout
		if ($this->_getParam('bookmarklet')) { 
			$this->_helper->layout->setlayout('bookmarklet');
			$this->_bookmarklet 	 = 1;
			$this->_bktitle = strip_tags($this->_getParam('t'));
			$this->_bkurl   = $this->_getParam('u');
			$this->_bktext   = $this->_getParam('s');
			$this->view->bookmarklet = true;
			$this->view->url 	 = $this->_bkurl;
			$this->view->title	 = $this->_bktitle;
			$this->view->selection = $this->_bktext;
		}
	}

	public function indexAction() {
		if ($this->_bookmarklet) {
			$type = Stuffpress_Services_Webparse::getTypeFromLink($this->_bkurl);
			switch ($type) {
				case SourceItem::STATUS_TYPE:
					$this->_forward('status');
					break;
				case SourceItem::LINK_TYPE:
					$this->_forward('link');
					break;
				case SourceItem::BLOG_TYPE:
					$this->_forward('blog');
					break;
				case SourceItem::IMAGE_TYPE:
					$this->_forward('image');
					break;
				case SourceItem::AUDIO_TYPE:
					$this->_forward('audio');
					break;
				case SourceItem::VIDEO_TYPE:
					$this->_forward('video');
					break;
			}					
		} else {
			$this->_forward('status');
		}
	}
	
	public function doneAction() {
		$this->common();
	}

	public function statusAction() {
		$this->common();
		
		if (isset($this->view->form)) {
			return;
		}
		
		if ($this->_bookmarklet) {
			$status = "{$this->_bktitle} ({$this->_bkurl})";
			$source		= StuffpressModel::forUser($this->_application->user->id);
			$this->view->form = $this->getFormStatus($source->getID(), 0, $status, false, false);
		} else {
			$this->view->form = $this->getForm('status');
		}
	}
	
	public function linkAction() {
		$this->common();
		$this->tinyMCE();

		if (isset($this->view->form)) {
			return;
		}
		
		if ($this->_bookmarklet) {
			$source		= StuffpressModel::forUser($this->_application->user->id);
			$this->view->form = $this->getFormLink($source->getID(), 0, $this->_bkurl, $this->_bktitle, $this->_bktext, false);
		} else {
			$this->view->form = $this->getForm('link');
		}
	}

	public function blogAction() {
		$this->common();
		$this->tinyMCE();
		
		if (isset($this->view->form)) {
			return;
		}
		
		if ($this->_bookmarklet) {
			$source		= StuffpressModel::forUser($this->_application->user->id);
			$post   = ($this->_bktext) ? "<p>{$this->_bktext}</p>" : "";
			$post  .= "<p>From <a href='{$this->_bkurl}'>{$this->_bktitle}</a></p>";
			$this->view->form = $this->getFormText($source->getID(), 0, $this->_bktitle, $post, false, false);
		} else {
			$this->view->form = $this->getForm('blog');
		}
	}
	
	public function imageAction() {
		$this->common();
		$this->tinyMCE();
			
		if (isset($this->view->form)) {
			return;
		}
		
		if ($this->_bookmarklet) {
			$source		= StuffpressModel::forUser($this->_application->user->id);
			$this->view->images = Stuffpress_Services_Webparse::getImages($this->_bkurl);
			$this->view->form = $this->getFormImage($source->getID(), 0, $this->_bktitle, $this->_bktext, false, false);
		} else {
			$this->view->form = $this->getForm('image');
		}
	}
	
	public function audioAction() {
		$this->common();
		$this->tinyMCE();
				
		if (isset($this->view->form)) {
			return;
		}
		
		if ($this->_bookmarklet) {
			$source		= StuffpressModel::forUser($this->_application->user->id);
			$this->view->form = $this->getFormAudio($source->getID(), 0, $this->_bktitle, $this->_bktext, false, false);
		} else {
			$this->view->form = $this->getForm('audio');
		}
	}
	
	public function videoAction() {
		$this->common();
		$this->tinyMCE();
		
		if (isset($this->view->form)) {
			return;
		}
		
		if ($this->_bookmarklet) {
			$source	= StuffpressModel::forUser($this->_application->user->id);
			$embed 	= Stuffpress_Services_Webparse::getEmbedFromLink($this->_bkurl, 160, 100);
			$this->view->form = $this->getFormVideo($source->getID(), 0, $this->_bktitle, $this->_bktext, $embed);
		} else {
			$this->view->form = $this->getForm('video');
		}
	}

	public function editAction()
	{
		// Get the item to edit from the parameters
		$item_id   = $this->_getParam('item');
		$source_id = $this->_getParam('source');
		
		//Verify if the requested item exist
		$data		= new Data();
		if (!($item	= $data->getItem($source_id, $item_id))) {
			throw new Stuffpress_NotFoundException("This item does not exist.");
		}

		// Get the user
		$users 			= new Users();
		$attributes		= $item->getAttributes();
		$user			= $users->getUser($attributes['user_id']);

		// Check if we are the owner
		if ($this->_application->user->id != $user->id) {
			throw new Stuffpress_NotFoundException("Not the owner");
		}
		
		// Get the source to provide visual feedback
		$model = SourceModel::newInstance($item->getPrefix());
		$this->view->service = $model->getServiceName();

		// Prepare the form
		$form = $this->getForm($item->getType(), true, $item);
		$this->view->form   = $form;
		$this->view->edit	= true;
		
		// If it is an image, we display it
		if ($item->getType() == 'image') {
			$this->view->image_url = $item->getImageUrl(ImageItem::SIZE_THUMBNAIL);
		}
		
		// If it already has location, tell it
		if ($item->hasLocation()) {
			$this->view->has_location = true;
			$this->view->location = "Lat: " . $item->getLatitude() . " - Lng: " . $item->getLongitude();
		}
		
		// forward to the appropriate action
		$this->_forward($item->getType());
	}

	public function deleteAction() {
		// Get, check and setup the parameters
		$item_id = $this->getRequest()->getParam("id");
		
		// Get the source
		$source_id	= $this->_properties->getProperty('stuffpress_source');
		
		//Verify if the requested item exist
		$data		= new Data();
		if (!($item	= $data->getItem($source_id, $item_id))) {
			throw new Stuffpress_NotFoundException("This item does not exist.");
		}
		
		// Get the user
		$users 			= new Users();
		$attributes		= $item->getAttributes();
		$user			= $users->getUser($attributes['user_id']);
		
		// Check if we are the owner
		if ($this->_application->user->id != $user->id) {
			throw new Stuffpress_NotFoundException("Not the owner");
		}
				
		// Create the source
		$source		= StuffpressModel::forUser($user->id);
		
		// If an image or audio file, delete the files
		$file = $item->getFile();
		if ($file) {
			$files = new Files();
			$files->deleteFile($file);
		}
		
		// All checks ok, we can delete !
		$data->deleteItem($source_id, $item_id);
		$source->deleteItem($item_id);

		// We should also delete the associated comments
		$comments = new Comments();
		$comments->deleteComments($source->getID(), $item_id);

		// return that everything is fine
		return $this->_helper->json->sendJson(true);
	}

	public function verifyAction() {

		if (!$this->getRequest()->isPost()) {
			$this->_helper->json->sendJson(true);
		}

		$type	= $this->_getParam('type');
		$mode	= $this->_getParam('mode');
		$edit	= ($mode == 'edit');
		
		if (!in_array($type, array('status', 'blog', 'link', 'image', 'audio', 'video'))) {
			$this->_helper->json->sendJson(true);
		}

		$form = $this->getForm($type, $edit);
		
		if (!$form->isValid($_POST)) {
			$elements = $form->getMessages();
			$errors   = array();

			foreach($elements as $name => $error) {
				foreach($error as $code => $message) {
					$errors[] = $name.": ".$message;
				}
			}

			$this->_helper->json->sendJson($errors);
		}

		// No errors
		$this->_helper->json->sendJson(false);
	}


	public function submitAction() {
		
		// Is this a post request ?
		if (!$this->getRequest()->isPost()) {
			throw new Stuffpress_Exception("Action can only be triggered by POST");
		}

		// Is this an allowed item type ?
		$type	= $this->_getParam('type');
		if (!in_array($type, array('status', 'blog', 'link', 'image', 'audio', 'video'))) {
			throw new Stuffpress_Exception("Not a valid item type $type.");
		}
		
		// Check if it is a new item or an edit 
		$mode	= $this->_getParam('mode');
		$edit	= ($mode == 'edit');

		// Is the form valid ?
		$form = $this->getForm($type, $edit);		
		if (!$form->isValid($_POST)) {
			$this->view->form = $form;
			return $this->_forward($type);
		}
		
		$values = $form->getValues();
		
		// Process the item differently if it is new or old
		if (($values['item']==0)) {
			$this->newItem($values);
		} else {
			$this->editItem($values['source'], $values['item'], $values);	
		}
	}

	private function newItem($values) {
		// Local variables
		$files = new Files();
				
		// If an image and the URL was given, we download the image and store it
		if ((@$values['type'] == 'image') && ($url = @$values['url']) && (strlen($url) > 0)) {
			$file_id = $files->downloadFile($url, "download");
			$file = $files->getFile($file_id);
			$key = $file->key;
		}
		
		// If a file was uploaded, we process it
		else if (@$_FILES['file']['tmp_name']) {
			try {
				$file_id = $files->processFile($values['file'], $_FILES['file'], 'Posted file');
				$file = $files->getFile($file_id);
				$key = $file->key;
			}
			catch(Exception $e) {
				$this->addErrorMessage("Unknown error occured");
				return $this->_forward(@$values['type']);
			}
		}
		
		// If an image and file available, resize
		if ((@$values['type'] == 'image') && ($file_id > 0)) {
			$files->fitWidth($file_id, 240,  'small');
			$files->fitWidth($file_id, 500,  'medium');
			$files->fitWidth($file_id, 1024, 'large');
			$files->fitSquare($file_id, 75,  'thumbnails');
			$exif = $files->readExif($file_id);
		}
		
		// Process the date if available		
		$date_type = @$values['date_type'];
		if ($date_type == 'other') {
			$timestamp = Stuffpress_Date::strToTimezone($values['date'], $this->_properties->getProperty('timezone'));
		} 
		else if  ($date_type == 'taken' && $exif) {
			if (isset($exif['DateTimeOriginal'])) {
				$timestamp = Stuffpress_Date::strToTimezone($exif['DateTimeOriginal'], $this->_properties->getProperty('timezone'));
			} else {
				$timestamp = time();
			}
		}
		else {
			$timestamp = time();
		}
		
		// Process the tags if available
		$tags	= @explode(',', $values['tags']);
		
		// Prepare the values for the database
		$data = array();
		$data['published']  = @$timestamp;
		$data['type']	    = @$values['type'];
		$data['file']	    = @$key;
		$data['url']	    = @$values['url'];
		$data['title'] 		= @$values['title'];
		$data['link']  		= @$values['link'];
		$data['embed']  	= @$values['embed'];		
		$data['text']  		= @$values['text'];
		
		// Add or update the item
		$source		= StuffpressModel::forUser($this->_application->user->id);
		$data_table = new Data();
		$item_id 	= $source->addItem($data, $data['published'], $data['type'], $tags, false, false, $data['title']);
		$source_id 	= $source->getID();
		
		// fetch the new item
		$item   = $data_table->getItem($source_id, $item_id);
		
		// Get longitude if provided
		if (!empty($exif['GPSLongitude']) && count($exif['GPSLongitude']) == 3 && !empty($exif['GPSLongitudeRef'])) {
            $longitude = ($exif['GPSLongitudeRef']== 'W' ? '-' : '') . Stuffpress_Exif::exif_gpsconvert( $exif['GPSLongitude'] );
		} else {
			$longitude = @$values['longitude'];	
		}
		
		// Get latitude
        if (!empty($exif['GPSLatitude']) && count($exif['GPSLatitude']) == 3 && !empty($exif['GPSLatitudeRef'])) {
            $latitude = ($exif['GPSLatitudeRef']== 'S' ? '-' : '') . Stuffpress_Exif::exif_gpsconvert( $exif['GPSLatitude'] );
        } else {
 			$latitude  = @$values['latitude'];       	
        }

		// Set it
		if ($latitude && $longitude) {
			$data_table->setLocation($source_id, $item_id, $latitude, $longitude, 0);
		}
		
		// Send notification if twitter post is enabled
		if ($this->_properties->getProperty('twitter_auth') && $values['twitter_notify']) {
			$this->notifyTwitter($item);
		}
		
		// Ping blog search engines
		$this->ping();

		// Redirect to the timeline
		$username	= $this->_application->user->username;
		$url	= $this->getUrl($username, "/entry/" . $item->getSlug());

		// If a bookmarklet, we show the 'done screen'
		if ($this->_bookmarklet) {
			$this->view->user_url = $url;
			return $this->_forward('done');
		}
		
		return $this->_redirect($url);		
	}
	
	private function editItem($source_id, $item_id, $values) {
		//Verify if the requested item exist
		$data		= new Data();
		if (!($item	= $data->getItem($source_id, $item_id))) {
			throw new Stuffpress_NotFoundException("This item does not exist.");
		}

		// Get the user
		$users 			= new Users();
		$attributes		= $item->getAttributes();
		$user			= $users->getUser($attributes['user_id']);

		// Check if we are the owner
		if ($this->_application->user->id != $user->id) {
			throw new Stuffpress_NotFoundException("Not the owner");
		}
		
		// Process the date if available
		if (@$values['date_type'] == 'other') {
			$timestamp = Stuffpress_Date::strToTimezone($values['date'], $this->_properties->getProperty('timezone'));
		} 
		else {
			$timestamp = time();
		}
		
		// Process the tags if available
		$tag_input	= @$values['tags'];
		$tags		= explode(',',$tag_input);
		$data->setTags($source_id, $item_id, $tags);
		
		// Process the location if available
		$latitude 	= 		@$values['latitude'];
		$longitude 	= 		@$values['longitude'];
		if ($latitude && $longitude) {
			$data->setLocation($source_id, $item_id, $latitude, $longitude, 0);
		}
		
		// Update the item data
		$data->setTimestamp($source_id, $item_id, $timestamp);
	
		switch ($item->getType()) {
			case SourceItem::STATUS_TYPE:
				$status = html_entity_decode(@$values['title']);
				$item->setStatus($status);
				break;
			
			case SourceItem::BLOG_TYPE:
				$title = @$values['title'];
				$text  = @$values['text'];
				$item->setTitle($title);
				$item->setContent($text);
				break;
				
			case SourceItem::LINK_TYPE:
				$title = @$values['title'];
				$link  = @$values['link'];
				$desc  = @$values['text'];
				$item->setTitle($title);
				$item->setLink($link);
				$item->setDescription($desc);
				break;
				
		case SourceItem::IMAGE_TYPE:
				$title = @$values['title'];
				$desc  = @$values['text'];
				$item->setTitle($title);
				$item->setDescription($desc);
				break;
				
		case SourceItem::AUDIO_TYPE:
				$title = @$values['title'];
				$desc  = @$values['text'];
				$item->setTitle($title);
				$item->setDescription($desc);
				break;			

		case SourceItem::VIDEO_TYPE:
				$title = @$values['title'];
				$desc  = @$values['text'];
				$item->setTitle($title);
				$item->setDescription($desc);
				break;	
		}
		
		// Send notification if twitter post is enabled
		if ($this->_properties->getProperty('twitter_auth') && $values['twitter_notify']) {
			$this->notifyTwitter($item);
		}
		
		// Redirect to the timeline
		$host	= $this->getHostname();
		$username	= $this->_application->user->username;		
		$url	= $this->getUrl($username, "/entry/" . $item->getSlug());
		return $this->_redirect($url);
	}
	
	private function getForm($type='blog', $edit=false, $item=false) {
		$source		= StuffpressModel::forUser($this->_application->user->id);
		$item_id   = $item ? $item->getID() : 0;
		$source_id = $item ? $item->getSource() : $source->getID();
		$date      = $item ? $item->getTimestamp() : false;
		
		// Crappy code !! TODO
		$lat  	  	= $item ? $item->getLatitude() : false;
		$lon  	  	= $item ? $item->getLongitude() : false;
		
		// get the tags if any
		if ($item && $item->getTagCount()>0) {
			$tags_table = new Tags();
			$tags 		= $tags_table->getTags($source_id, $item_id);
			$strings 	= array();
			foreach($tags as $tag) {
				$strings[] = $tag['tag']; 
			}
			$tags = implode(', ', $strings);
		} else {
			$tags = false;
		}
		
		if ($type == SourceItem::STATUS_TYPE) {
			$status = $item ? $item->getStatus() : '';
			$form = $this->getFormStatus($source_id,$item_id, $status, $date, $edit, $tags, $lat, $lon);
		} elseif ($type == SourceItem::BLOG_TYPE) {
			$title = $item ? $item->getTitle() : '';
			$text  = $item ? $item->getContent() : '';
			$form = $this->getFormText($source_id,$item_id, $title, $text, $date, $edit, $tags, $lat, $lon);
		} elseif ($type == SourceItem::LINK_TYPE) {
			$title = $item ? $item->getTitle() : '';
			$link  = $item ? $item->getLink() : '';
			$desc  = $item ? $item->getDescription() : '';
			$form  = $this->getFormLink($source_id, $item_id, $link, $title, $desc, $date, $edit, $tags, $lat, $lon);
		} elseif ($type == SourceItem::IMAGE_TYPE) {
			$title = $item ? $item->getTitle() : '';
			$desc  = $item ? $item->getDescription() : '';
			$form = $this->getFormImage($source_id, $item_id, $title, $desc, $date, $edit, $tags, $lat, $lon);
		} elseif ($type == SourceItem::AUDIO_TYPE) {
			$title = $item ? $item->getTitle() : '';
			$desc  = $item ? $item->getDescription() : '';
			$form  = $this->getFormAudio($source_id, $item_id, $title, $desc, $date, $edit, $tags, $lat, $lon);
		} elseif ($type == SourceItem::VIDEO_TYPE) {
			$title = $item ? $item->getTitle() : '';
			$desc  = $item ? $item->getDescription() : '';
			$embed = $item ? $item->getEmbedCode() : '';			
			$form = $this->getFormVideo($source_id, $item_id, $title, $desc, $embed, $date, $edit, $tags, $lat, $lon);
		}
		
		return $form;
	}
	
	private function getFormStatus($source_id, $item_id, $status, $date=false, $edit=false,$tags=false, $lat=false, $lon=false) {
		// Get a basic form 
		$form = $this->getFormCommon($source_id, $item_id, 'status', $date, $edit,$tags,$lat,$lon);

		// Create and configure comment element:
		$content = $form->createElement('textarea', 'title',  array('label' => 'Status:', 'rows'=> 3, 'cols' => 60, 'decorators' => array('ViewHelper', 'Errors')));
		$content->addFilter('StripTags');
		$content->setValue($status);
		$content->setRequired(true);
		$form->addElement($content);
				
		return $form;
	}
	
	private function getFormText($source_id,$item_id, $title, $text, $date=false, $edit=false,$tags=false, $lat=false, $lon=false) {

		// Get a basic form 
		$form = $this->getFormCommon($source_id, $item_id, 'blog', $date, $edit,$tags,$lat,$lon);
		
		// Create and configure title element:
		$element = $form->createElement('text', 'title',  array('label' => 'Title:', 'decorators' => array('ViewHelper', 'Errors')));
		$element->addValidator('stringLength', false, array(0, 256));
		$element->setValue($title);
		$element->setRequired(true);
		$form->addElement($element);
		
		// Create and configure comment element:
		$element = $form->createElement('textarea', 'text',  array('label' => 'Content:', 'rows'=> 15, 'cols' => 60, 'class' => 'mceEditor', 'decorators' => array('ViewHelper', 'Errors')));
		$element->setRequired(false);
		$element->setValue($text);
		$form->addElement($element);

		return $form;
	}
	
	
	private function getFormLink($source_id, $item_id, $link, $title, $description, $date=false, $edit=false,$tags=false, $lat=false, $lon=false) {

		// Get a basic form 
		$form = $this->getFormCommon($source_id, $item_id, 'link', $date, $edit,$tags,$lat,$lon);
		
		// Create and configure title element:
		$element = $form->createElement('text', 'title',  array('label' => 'Title:', 'decorators' => array('ViewHelper', 'Errors')));
		$element->addValidator('stringLength', false, array(0, 256));
		$element->addFilter('StripTags');
		$element->setRequired(true);
		$element->setValue($title);
		$form->addElement($element);
		
		// Create and configure link element:
		$element = $form->createElement('text', 'link',  array('label' => 'Link:', 'decorators' => array('ViewHelper', 'Errors')));
		$element->addValidator('stringLength', false, array(0, 256));
		$element->setRequired(true);
		$element->setValue($link);
		$form->addElement($element);
		
		// Create and configure description element:
		$element = $form->createElement('textarea', 'text',  array('label' => 'Note:', 'rows'=> 3, 'cols' => 60,  'class' => 'mceEditor',  'class' => 'mceEditor', 'decorators' => array('ViewHelper', 'Errors')));
		$element->setValue($description);
		$element->setRequired(false);
		$form->addElement($element);

		return $form;
	}
	
	private function getFormImage($source_id, $item_id, $title='', $description='', $date=false, $edit=false,$tags=false, $lat=false, $lon=false) {

		// Get a basic form 
		$form = $this->getFormCommon($source_id, $item_id, 'image', $date, $edit,$tags,$lat,$lon);
		$form->setAttrib('enctype', 'multipart/form-data');

		if (isset($this->_config->path->temp)) {
			$path = $this->_config->path->temp;
		} else {
			$path = $this->_root . '/temp';
		}
		
		// Create and configure title element:
		if (!$edit) {
			$element = new Zend_Form_Element_File('file');
			$element->setLabel('Upload an image:')
					->setRequired(false)
					->setDecorators(array(array('File'), array('Errors'))) 
			        ->setDestination($path)
			        ->addValidator('Count', false, 1)     // ensure only 1 file
			        ->addValidator('Size', false, 4000000) // limit to 2M
			        ->addValidator('Extension', false, 'jpg,png,gif'); // only JPEG, PNG, and GIFs
			$form->addElement($element, 'file');
			
						
			// Create and configure url element:
			$element = $form->createElement('text', 'url',  array('label' => 'Url:', 'decorators' => array('ViewHelper', 'Errors')));
			$element->addValidator('stringLength', false, array(0, 256));
			$element->addValidator(new Stuffpress_Validate_Uri());
			$element->setRequired(false);
			$form->addElement($element);
		}
		
		// Create and configure title element:
		$element = $form->createElement('text', 'title',  array('label' => 'Title:'));
		$element->setRequired(false)
			    ->addValidator('stringLength', false, array(0, 256))
		        ->addFilter('StripTags')
		        ->setValue($title)
		        ->setDecorators(array('ViewHelper', 'Errors'));
		$form->addElement($element);
		
		// Create and configure description element:
		$element = $form->createElement('textarea', 'text',  array('label' => 'Note:', 'rows'=> 3, 'cols' => 60, 'class' => 'mceEditor', 'decorators' => array('ViewHelper', 'Errors')));
		$element->setValue($description);
		$element->setRequired(false);
		$form->addElement($element);
		
		// Add the 'time taken' option because it is an image
		$element = $form->getElement('date_type');		
		if (!$date) {
			$element->setValue('taken');
			$this->view->date_text = "When the picture was taken";
		}		
				
		return $form;
	}
	
	private function getFormAudio($source_id, $item_id, $title, $description, $date=false, $edit=false, $tags=false, $lat=false, $lon=false) {

		// Get a basic form 
		$form = $this->getFormCommon($source_id, $item_id, 'audio', $date, $edit,$tags,$lat,$lon);
		$form->setAttrib('enctype', 'multipart/form-data');

		if (isset($this->_config->path->temp)) {
			$path = $this->_config->path->temp;
		} else {
			$path = $this->_root . '/temp';
		}
		
		// Create and configure title element:
		if (!$edit) {
			$element = new Zend_Form_Element_File('file');
			$element->setLabel('Upload an mp3 file:')
					->setDecorators(array(array('File'), array('Errors'))) 
					->setRequired(false)
			        ->setDestination($path)
			        ->addValidator('Count', false, 1)     // ensure only 1 file
			        ->addValidator('Size', false, 12000000) // limit to 2M
			        ->addValidator('Extension', false, 'mp3'); // only JPEG, PNG, and GIFs
			$form->addElement($element, 'file');
			
			// Create and configure url element:
			$element = $form->createElement('text', 'url',  array('label' => 'Url:', 'decorators' => array('ViewHelper', 'Errors')));
			$element->addValidator('stringLength', false, array(0, 256));
			$element->addValidator(new Stuffpress_Validate_Uri());
			$element->setRequired(false);
			$form->addElement($element);
		}

		// Create and configure title element:
		$element = $form->createElement('text', 'title',  array('label' => 'Caption:', 'decorators' => array('ViewHelper', 'Errors')));
		$element->addValidator('stringLength', false, array(0, 256));
		$element->addFilter('StripTags');
		$element->setRequired(false);
		$element->setValue($title);
		$form->addElement($element);
		
		// Create and configure description element:
		$element = $form->createElement('textarea', 'text',  array('label' => 'Note:', 'rows'=> 3, 'cols' => 60,  'class' => 'mceEditor', 'decorators' => array('ViewHelper', 'Errors')));
		$element->setValue($description);
		$element->setRequired(false);
		$form->addElement($element);

		return $form;
	}
	
	private function getFormVideo($source_id, $item_id, $title, $description, $embed=false, $date=false, $edit=false, $tags=false, $lat=false, $lon=false) {

		// Get a basic form 
		$form = $this->getFormCommon($source_id, $item_id, 'video', $date, $edit, $tags,$lat,$lon);
		$form->setAttrib('enctype', 'multipart/form-data');

		// Create and configure embed element:
		$element = $form->createElement('textarea', 'embed',  array('label' => 'Note:', 'rows'=> 3, 'cols' => 60, 'decorators' => array('ViewHelper', 'Errors')));
		$element->setValue($embed);
		$element->setRequired(false);
		$form->addElement($element);

		// Create and configure title element:
		$element = $form->createElement('text', 'title',  array('label' => 'Caption:', 'decorators' => array('ViewHelper', 'Errors')));
		$element->addValidator('stringLength', false, array(0, 256));
		$element->addFilter('StripTags');
		$element->setValue($title);
		$element->setRequired(false);
		$form->addElement($element);
		
		// Create and configure description element:
		$element = $form->createElement('textarea', 'text',  array('label' => 'Note:', 'rows'=> 3, 'cols' => 60,  'class' => 'mceEditor', 'decorators' => array('ViewHelper', 'Errors')));
		$element->setValue($description);
		$element->setRequired(false);
		$form->addElement($element);

		return $form;
	}
	
	private function getFormCommon($source_id = 0, $item_id=0, $type='text', $date=false, $edit=false, $tags=false, $lat=false, $long=false, $elev=false) {
		// Create the basic form		
		$form = new Stuffpress_Form();

		// Add the form element details
		$form->setAction('admin/post/submit');
		$form->setMethod('post');
		$form->setName("formPost");
		
		// Create and configure tags element:
		$element = $form->createElement('text', 'tags',  array('label' => 'Tags:', 'decorators' => array('ViewHelper', 'Errors')));
		$element->addValidator('stringLength', false, array(0, 256));
		$element->addFilter('StripTags');
		$element->setValue($tags);
		$element->setRequired(false);
		$form->addElement($element);
		
		// Create and configure latitude element:
		$element = $form->createElement('hidden', 'latitude',  array('label' => 'Latitude:', 'decorators' => array('ViewHelper', 'Errors')));
		$element->addValidator('between', false, array(-180.0, 180.0));
		$element->setValue($lat);
		$element->setRequired(false);
		$form->addElement($element);

		// Create and configure longitude element:
		$element = $form->createElement('hidden', 'longitude',  array('label' => 'Longitude:', 'decorators' => array('ViewHelper', 'Errors')));
		$element->addValidator('between', false, array(-180.0, 180.0));
		$element->setValue($long);
		$element->setRequired(false);
		$form->addElement($element);
		
		// Add a radio button element for the date_type
		$element = $form->createElement('hidden', 'date_type');
		$element->setRequired(false);
		$element->setDecorators(array('ViewHelper'));
		$form->addElement($element);
				
		if ($date) {
			$timestamp = $date;
			$this->view->date_text = Stuffpress_Date::date("F d, Y h:i A", $timestamp, $this->_properties->getProperty('timezone'));
			$element->setValue('other');
		} else {
			$timestamp = time();
			$this->view->date_text = "Now";
			$element->setValue('now');
		}		
				
		$form->addElement($element);
		
		// Create and configure date element:
		$element = $form->createElement('hidden', 'date');
		$element->setRequired(false);
		$element->setDecorators(array('ViewHelper'));
		$element->setValue(Stuffpress_Date::date("F d, Y h:i A", $timestamp, $this->_properties->getProperty('timezone')));
		$form->addElement($element);
		
		// Add a twitter element if required
		if ($this->_properties->getProperty('twitter_auth')) {
			$checked = (!$item_id && in_array($source_id, unserialize($this->_properties->getProperty('twitter_services')))) ? true : false;
			$element = $form->createElement('checkbox', 'twitter_notify',  array('label' => 'Twitter:', 'decorators' => array('ViewHelper', 'Errors'), 'class' => 'css'));
			$element->setValue($checked);
			$element->setRequired(true);
			$form->addElement($element);		
		}
		
		// Add a hidden element with the item id
		$element = $form->createElement('hidden', 'item');
		$element->setDecorators(array(array('ViewHelper')));
		$element->setValue($item_id);
		$form->addElement($element);

		// Add a hidden element with the item id
		$element = $form->createElement('hidden', 'source');
		$element->setDecorators(array(array('ViewHelper')));
		$element->setValue($source_id);
		$form->addElement($element);		
		
		// Add a hidden element with the type
		$element = $form->createElement('hidden', 'type');
		$element->setDecorators(array(array('ViewHelper')));
		$element->setValue($type);
		$form->addElement($element);
		
		// If a bookmarklet, we also need to remember it
		$element = $form->createElement('hidden', 'bookmarklet');
		$element->setDecorators(array(array('ViewHelper')));
		$element->setValue($this->_bookmarklet);
		$form->addElement($element);
		
		// Add a hidden element with action
		$element = $form->createElement('hidden', 'mode');
		$element->setDecorators(array(array('ViewHelper')));
		$element->setValue($edit ? 'edit' : 'create');
		$form->addElement($element);

		// use addElement() as a factory to create 'Post' button:
		$form->addElement('button', 'post', array('label' => ($edit ? 'Save' : 'Post'), 'onclick' => "submitFormPost();", 'decorators' => $form->buttonDecorators));

		return $form;
	}

	protected function common() {
		// Run the parent
		parent::common();
		
		// And now we can customize
		$this->view->section	= 'post';
		$this->view->gmap_key   = isset($this->_config->gmap->key) ? $this->_config->gmap->key : false;
		
		// Add required javascript files
		$this->view->headScript()->appendFile('js/calendar_date_select/calendar_date_select.js');				
		$this->view->headScript()->appendFile('js/storytlr/validateForm.js');
		$this->view->headScript()->appendFile('js/controllers/post.js');
		
		// Add required css files
		$this->view->headLink()->appendStylesheet('style/calendar.css');	
		$this->view->headLink()->appendStylesheet('style/calendar_date_select/blue.css');		
	}
	
	private function tinyMCE() {
		Stuffpress_TinyMCE::append($this->view);
	}
	
	private function createArray($from, $to) {
		$result = array();
		for ($i=$from; $i < $to; $i++) {
			$result[$i] = $i;
		}
		return $result;
	}
	
	private function notifyTwitter($item) {		
		// Get twitter credentials
		$username   = $this->_properties->getProperty('twitter_username');
		$password	= $this->_properties->getProperty('twitter_password');
		$has_preamble   = $this->_properties->getProperty('preamble', true);		
		
		// Get item
		$preamble	= $has_preamble ? $item->getPreamble() : "";
		$title		= $preamble . $item->getTitle();
		
		// Assemble tweet depending on type
		if (($item->getType() == SourceItem::STATUS_TYPE ) && strlen($title) < 140) {
			$tweet = $title;
		} else {
			if (strlen($title) > 121) $title = substr($title, 0, 110) . "[..]";
			$link 	= $this->getUrl($this->_application->user->username, "/entry/" . $item->getSlug());
			$tweet 	= "$title $link";
		}
		
		try {
			$twitter = new Stuffpress_Services_Twitter($username, $password);
			$twitter->sendTweet($tweet);
		} catch (Exception $e) {
			//
		}
	}
	
	private function ping() {
		// Ping google blog search
		if ($this->_application->user->domain) {
			$url = "http://{$this->_application->user->domain}";
		} else {
			$url = "http://{$this->_application->user->username}.storytlr.com";
		}
		
		$maintitle 	= $this->_properties->getProperty('title');
		$subtitle 	= $this->_properties->getProperty('subtitle');
		$separator	= $subtitle ? " | " : "";		
		$title 		= $maintitle . $separator . $subtitle;
		$rss	 	= "$url/rss/types/blog/nopre/1/feed.xml"; 
		
		Stuffpress_Services_Blogsearch::ping($title, $url, $rss);
	}
}
