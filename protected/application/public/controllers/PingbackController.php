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

use mf2\Parser;

class PingbackController extends BaseController 
{
		
	protected $_logger;
	
    public function preDispatch()
    {
		$this->_logger = Zend_Registry::get("logger");
    }
	
	public function pingAction() {
		// We encapsulate xmlrpc, disable everything
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		// Get the request
		$request = Pingback_Utility::getRawPostData();
		$this->_logger->log("Received pingback request: $request", Zend_Log::DEBUG);
		
		// Process the request
		$server = new Pingback_Server();
		$server->execute($request);
		
		if ($server->isValid()) {
			$source = $server->getSourceURL();
			$target = $server->getTargetURL();
			$this->process($source,$target);
		}
				
		// Log the response
		$response = $server->getResponse();
		$this->_logger->log("Response: $response", Zend_Log::DEBUG);
		
		// We turn off output buffering to avoid memory issues
	    // when dumping the response 
		ob_end_flush();
		print $response;
		
		// Die to make sure that we don't screw up the response
		die();
	}
	
	private function process($source, $target) {
		$this->_logger->log("Processing pingback from $source for $target", Zend_Log::INFO);
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $source);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT,'Storytlr/1.0');
		
		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close ($curl);
		
		if ($http_code != 200) {
			$this->_logger->log("Failed to get content for $target", Zend_Log::DEBUG);
			return;
		}
		
		// Parse the source page for microformats content
		$parser = new Parser($response);
		$output = $parser->parse();
		$this->_logger->log("Parsed output: " . var_export($output, true), Zend_Log::DEBUG);
		
		// Extract relevant data
		$content = $this->processItems($output["items"]);
		$timestamp	= time();
	
		$this->_logger->log("Extracted data: " . var_export($content, true), Zend_Log::DEBUG);
		
		// Lookup if existing entry
		preg_match('/(?P<source>\d+)\-(?P<item>\d+)\.html$/', $target, $matches);
		$this->_logger->log("Matches: " . var_export($matches, true), Zend_Log::DEBUG);
		$source_id = $matches["source"];
		$item_id = $matches["item"];
		
		// Get the source and the user owning it
		$data		= new Data();
		$sources    = new Sources();
		$users      = new Users();
		
		// Does it relate to an item ?
		if ($source_id && $item_id) {
			$s = $sources->getSource($source_id);
			$i = $data->getItem($source_id, $item_id);
			if ($s && $i) {
				$user = $users->getUser($s['user_id']);
			}
		}
		
		// Otherwise, can we relate to a user ?
		if (!$user) {
			$user = $this->lookupUser($target);
		}
		
		// No user ? We have to giveup
		if (!$user) {
			throw new Exception('Failed to find corresponding storytlr user.'); 
		}
		
		// Lookup author
		if (count ($content["hcard"]) > 0) {
			$hcard = $content["hcard"][0];
		}
		
		// Lookup entry
		if (count ($content["hentry"]) > 0) {
			$hentry = $content["hentry"][0];
			if (!$hcard && count($hentry["hcard"])>0) {
				$hcard = $hentry["hcard"][0];
			}
		}
		
		// Add the mention to the database
		$mentions  	= new Mentions();
		$mentions->addMention($source_id, $item_id, $user->id, $source, $hentry["title"], $hcard["name"], $hcard["url"], "", $hcard["avatar"], $timestamp);
		
		// Send an email alert to owner
		try {
			$on_comment		= $this->_properties->getProperty('on_comment');
			if ($on_comment) {
				Stuffpress_Emails::sendMentionEmail($user->email, $user->username, $hcard["name"], $hcard["url"], $hentry["title"], $source, $target);
			}				
		} catch (Exception $e) {
			$logger	= Zend_Registry::get("logger");
			$logger->log("Sending mention notification exception: " . $e->getMessage(), Zend_Log::ERR);
		}
	}
	
	private function lookupUser($url) {
		$config		= Zend_Registry::get('configuration');
		$our_host	= $config->web->host;
		$this_host	= $url;
	
		$this_host  = str_replace("http://", "", $this_host);
		$this_host  = trim($this_host, " /");
		
		$users  = new Users();
		
		// Do we hit our main domain ?
		if (($our_host == $this_host)) {
	
			// Is a user specified in the config ?
			if (isset($config->app->user)) {
				if ($user = $users->getUserFromUsername($config->app->user)) {
					return $user;
				}
			}
	
			return;
		}
	
		// A user storytlr page
		if (preg_match("/(?<user>[a-zA-Z0-9]+).{$our_host}$/", $this_host, $matches)) {
			$username = $matches['user'];
			if ($user = $users->getUserFromUsername($username)) {
				return $user;
			}
		}
	
		// A or CNAME ?
		if ($user = $users->getUserFromDomain($this_host)) {
			return $user;
		}
	
		// Maybe we should strip the www in front ?
		$matches = array();
		if (preg_match("/www.(.*)/", $this_host, $matches)) {
			if ($user = $users->getUserFromDomain($matches[1])) {
				return $user;
			}
		}
		
		// Is a user specified in the config ?
		if (isset($config->app->user)) {
			if ($user = $users->getUserFromUsername($config->app->user)) {
				return $user;
			}
		}
	
		return;
	}
	
	private function processItems($items) {
		$result = array();
		$result["hcard"] = array();
		$result["hentry"] = array();
		foreach ($items as $item) {
			$this->_logger->log("Item: " . var_export($item, true), Zend_Log::DEBUG);
			if (in_array("h-card", $item["type"])) {  
				array_push($result["hcard"], $this->processHCard($item));
			} else if (in_array("h-entry", $item["type"])) {
				array_push($result["hentry"], $this->processHEntry($item));
			} else if (in_array("h-feed", $item["type"])) {
				if ($item["children"] && count($item["children"]) > 0) {
					foreach ($item["children"] as $item) {
						if (in_array("h-card", $item["type"])) {
							array_push($hentry["hcard"], $this->processHCard($item));
						}
					}
				}			
			}
		}
		
		return $result;
	}
	
	private function processHcard($item) {
		$this->_logger->log("Process hcard: " . var_export($item, true), Zend_Log::DEBUG);
		$hcard = array();
		$props = @$item["properties"];
		$hcard["name"] = @$props["name"][0];
		$hcard["adr"] = @$props["adr"][0];
		$hcard["bio"] = @$props["note"][0];
		$hcard["url"] = @$props["url"][0];
		$hcard["avatar"] = @$props["photo"][0];		
		return $hcard;
	}
	
	private function processHentry($item) {
		$this->_logger->log("Process hentry: " . var_export($item, true), Zend_Log::DEBUG);
		$hentry = array();
		$hentry["hcard"] = array();
		$props = @$item["properties"];
		$content = @$props["content"][0];
		$name = @$props["name"][0];
		$hentry["title"] = $content ? $content : $name;
		$hentry["published"] = @$props["published"][0];

		if ($item["children"] && count($item["children"]) > 0) {
			foreach ($item["children"] as $item) {
				if (in_array("h-card", $item["type"])) {
					array_push($hentry["hcard"], $this->processHCard($item));
				}
			}
		}
		
		return $hentry;
	}
}