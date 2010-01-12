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
abstract class BaseController extends Stuffpress_Controller_Action 
{	    
    protected $_application;
    
    protected $_user;
    
    protected $_properties;
    
    protected $_embed;
    
    protected $_section;
    
    protected $_admin;
    
    protected $_cache;
    
    protected $_root;

    public function init()
    {
    	if (!Zend_Registry::isRegistered('user')) {
    		if ($user  = $this->getRequest()->getParam("user")) {
    			$users = new Users();
				if ($user  = $users->getUserFromUsername($user)) {
					Zend_Registry::set("user", $user);	
				}
			}
    	}
		
		$this->_user = Zend_Registry::get("user");
		
        if (!Zend_Registry::isRegistered('shard')) {
			Zend_Registry::set("shard", $this->_user->id);
		}
		
		if (Zend_Registry::isRegistered("cache")) $this->_cache = Zend_Registry::get("cache");
		
		if (Zend_Registry::isRegistered("root")) $this->_root = Zend_Registry::get("root");
		
		if (Zend_Registry::isRegistered("configuration")) $this->_config = Zend_Registry::get("configuration");
		
		// Other global variables
        $this->_application = Stuffpress_Application::getInstance();
		$this->_properties 	= new Properties(array(Stuffpress_Db_Properties::KEY => $this->_user->id));
    	$this->_admin 		= ($this->_application->user && ($this->_application->user->id == $this->_user->id)) ? true : false;

    	// Disable layout if XMLHTTPRequest
        if ($this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout();	
		}
    }

	protected function common() { 
		// Set the timezone to the user timezone
		$timezone =  $this->_properties->getProperty('timezone');
		date_default_timezone_set($timezone);
		
		// Assgin a different layout if embedded
		if ($this->_embed) {
			if ($this->_embed =='page') {
				$this->_helper->layout->setlayout('embed_page');
			}
			else {
				$this->_helper->layout->disableLayout();	
			}
		}
		
		// Fetch the user configured widgets
		if (!$this->_embed) {
			$w = new Widgets();
			$this->view->widgets = $w->getWidgets($this->_user->id);
		}

		// User provided footer (e.g. tracker)
		$user_footer					= $this->_properties->getProperty('footer');
		$this->view->user_footer 		= $user_footer;
		
		// Javascript
		$this->view->headScript()->appendFile('js/prototype/prototype.js');
		$this->view->headScript()->appendFile('js/scriptaculous/builder.js');
		$this->view->headScript()->appendFile('js/scriptaculous/builder.js');
		$this->view->headScript()->appendFile('js/scriptaculous/effects.js');
		$this->view->headScript()->appendFile('js/scriptaculous/dragdrop.js');
		$this->view->headScript()->appendFile('js/scriptaculous/controls.js');
		$this->view->headScript()->appendFile('js/scriptaculous/slider.js');
		$this->view->headScript()->appendFile('js/scriptaculous/sound.js');

		$this->view->headScript()->appendFile('js/storytlr/validateForm.js');
		$this->view->headScript()->appendFile('js/storytlr/common.js');
		$this->view->headScript()->appendFile('js/controllers/adminbar.js');
		$this->view->headScript()->appendFile('js/accordion/accordion.js');
		
		// Meta
		$this->view->headMeta()->appendHttpEquiv('Content-Type', 'text/html; charset=UTF-8');
		
		// CSS
		$this->view->headLink()->appendStylesheet('style/toolbar.css');
		$this->view->headLink()->appendStylesheet('themes/' . $this->_properties->getProperty('theme') . '/style.css');
		
		// Colors
		$colors		= $this->_properties->getProperties(array("color_title", "color_subtitle", "color_sidebar_border", "color_background", "color_link", "color_sidebar_text", "color_sidebar_header"));		
		$this->view->colors				= $colors;
		
		// Error and status messages
		$this->view->status_messages    = $this->getStatusMessages();
		$this->view->error_messages     = $this->getErrorMessages();
								
		// User details
		$this->view->username 	= $this->_user->username;
		$this->view->user_id 	= $this->_user->id;
		$this->view->admin		= $this->_admin;
		
		// Theme
		$themes					= Themes::getAvailableThemes();
		$theme					= $this->_properties->getProperty('theme');
		$this->view->theme		= $theme;
		$this->view->theme_data = $themes[$theme];
		
		// Pages
		$pages 					= new Pages();
		$this->view->pages		= $pages->getPages();
		
		// Page layout
		$this->view->background_image 	= $this->_properties->getProperty('background_image');
		$this->view->header_image 		= $this->_properties->getProperty('header_image');
		$this->view->has_colors			= $this->_properties->getProperty('has_colors');
		$this->view->css_enabled	 	= $this->_properties->getProperty('css_enabled');
		$this->view->css_content	 	= $this->_properties->getProperty('css_content');
		$this->view->title				= $this->_properties->getProperty('title');
		$this->view->subtitle			= $this->_properties->getProperty('subtitle');
		$this->view->disqus				= $this->_properties->getProperty('disqus');
		$this->view->googlefc			= $this->_properties->getProperty('googlefc');
		$this->view->footer				= $this->_properties->getProperty('footer');
	}
	
	protected function getModels() {
		$sources = new Sources();
		$s = $sources->getSources();
		$models	 = array();
		if ($s) { 
			foreach ($s as $source) {
				$model = SourceModel::newInstance($source['service'], $source);
				$models[$source['id']] = $model;
			}
		}
		return $models;
	}
	
	protected function generateRss($key, $items, $title, $nopre=false) {
		// This is not a layout page
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();

		// Set the timezone to the user timezone
		$timezone =  $this->_properties->getProperty('timezone');
		date_default_timezone_set($timezone);

		// Get a few user preferences
		$config 	= Zend_Registry::get("configuration");
		$root 		= Zend_Registry::get("root");
		$host		= $this->getHostname();
		$preamble   = $nopre ? false : $this->_properties->getProperty('preamble', true);
		$username   = $this->_user->username;

		// Get the domain right
		$domain = Zend_Registry::get("host");
		
		// Get the cache path for feeds
		if (isset($config->path->feeds)) {
			$path = $config->path->feeds;
		} else {
			$path = dirname($root)."/feeds/";
		}

		// Render the RSS feed
		// Go ahead and display the page
		$rss = new UniversalFeedCreator();
		$rss->encoding = "UTF-8";
		
		// Get the cached object
		$rss_key = "RSS_" . $username . "_" . md5($key);
		$rss->useCached("RSS2.0", $path . "/$rss_key.xml", 300); // use cached version if age<10 minutes

		// Get all the items and build rss
		$rss->title 		= $title;
		$rss->descriptionTruncSize = 0;
		$rss->descriptionHtmlSyndicated = true;
		$rss->link = "http://$domain";

		if (isset($items) && is_array($items)) {
			for ($i=0; $i < count($items); $i++) {
				$item = $items[$i];
	
				$source_id = $item->getSource();
				$item_id = $item->getID();
				$type = $item->getType();
				$slug = $item->getSlug();
				$title 	= $item->getTitle();
				$pream  = $preamble ? $item->getPreamble() : "";
				$entry = new FeedItem();
				$entry->guid = "/entry/$source_id/$item_id";
				$entry->title = $pream . ($title ? $title : "Untitled");
				$entry->link = "http://$domain/entry/$slug";
				$entry->description = $item->getRssBody();
				$entry->descriptionHtmlSyndicated = true;
				$entry->source = $item->getLink();
				$entry->descriptionTruncSize = 0;
				$entry->date = (int) $item->getTimestamp();
				$entry->author = $username;
				
				if ($type == SourceItem::AUDIO_TYPE) {
					$enclosure = array();
					$enclosure['url'] = $item->getAudioUrl();
					$enclosure['type'] = "audio/mpeg";
					$entry->enclosure = $enclosure; 
				}
					
				$rss->addItem($entry);
			}
		}

		$rss->saveFeed("RSS2.0", $path . "/$rss_key.xml");
	}
}
