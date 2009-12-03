#!/usr/bin/php
<?php
// Include the common stuff
include 'common.php';

ini_set('memory_limit', '64M');

// Get a logger
$logger = new Zend_Log();
$logger->addWriter(new Zend_Log_Writer_Stream($root .'/logs/emails.log'));
Zend_Registry::set('logger',$logger);

class Mailpost
{
	private $_user;

	private $_properties;

	public function execute() {

		// get a logger
		$logger = Zend_Registry::get("logger");

		echo "Memory usage on startup: " . memory_get_usage() . "\r\n";

		// Access the email
		$mail = new Zend_Mail_Storage_Pop3(array('host'     => 'xxxxxx',
		'user'     => 'xxxxxx',
		'password' => 'xxxxxx',
		'ssl'      => 'SSL'));

		// If no new email, goodbye
		if ($mail->countMessages()==0) {
			$log = "No new emails to process.";
			echo "$log\r\n";
			$logger->log($log, Zend_Log::INFO);
			exit();
		}

		$email_count = 0;
		foreach ($mail as $messageNum => $message) {

			// A bit of feedback
			$log = "Message to {$message->to}.. (mem: " .memory_get_usage() . ")...";
			echo "$log";
			$logger->log($log, Zend_Log::INFO);

			// Get the user, if not we continue
			if (!($user = $this->getUser($message->to))) {
				$log = "skipped.";
				echo "$log\r\n";
				$logger->log($log, Zend_Log::INFO);
				$mail->removeMessage($messageNum);
				continue;
			}

			// Assign the shard value
			Zend_Registry::set("shard", $user['id']);
			$this->_user = $user;
			$this->_properties = new Properties(array(Stuffpress_Db_Properties::KEY => $user['id']));

			// Get the subject
			try {
				$subject = $this->mimeWordDecode(trim($message->subject));
			} catch(Exception $e) {
				$subject = "";
			};

			// Get the content
			$foundPart 	= null;
			$plain_text = "";
			$html_text 	= "";
			$latitude 	= false;
			$longitude 	= false;
			$image 		= false;
			$audio 		= false;
			$files 		= new Files();
			$timestamp  = time();

			foreach (new RecursiveIteratorIterator($message) as $part) {
				try {
					$part_type = strtok($part->contentType, ';');

					if ($part_type == 'text/plain') {
						$charset = $this->getCharset($part->contentType);
						$plain_text = $this->recode(trim($part->getContent()), $charset);
					}

					else if ($part_type == 'text/html') {
						$charset = $this->getCharset($part->contentType);
						$html_text = $this->recode(trim($part->getContent()), $charset);
					}

					else if (substr_compare($part_type, 'image', 0, 5) == 0) {
						if ($details = $this->getFileDetails($part->contentType)) {
							if ($content = base64_decode($part->getContent())) {
								$file_id = $files->saveFile($content, $details['name'], $details['mime'], "Email upload");
								$file = $files->getFile($file_id);
								$key = $file->key;
								$files->fitWidth($file_id, 240,  'small');
								$files->fitWidth($file_id, 500,  'medium');
								$files->fitWidth($file_id, 1024, 'large');
								$files->fitSquare($file_id, 75,  'thumbnails');
								$exif = $files->readExif($file_id);
								
								// Retrieve the picture date/time
								if (isset($exif['DateTimeOriginal'])) {
									$timestamp = Stuffpress_Date::strToTimezone($exif['DateTimeOriginal'], $this->_properties->getProperty('timezone'));
								}
								
								// Get longitude if provided
								if (!empty($exif['GPSLongitude']) && count($exif['GPSLongitude']) == 3 && !empty($exif['GPSLongitudeRef'])) {
									$longitude = ($exif['GPSLongitudeRef']== 'W' ? '-' : '') . Stuffpress_Exif::exif_gpsconvert( $exif['GPSLongitude'] );
								}

								// Get latitude
								if (!empty($exif['GPSLatitude']) && count($exif['GPSLatitude']) == 3 && !empty($exif['GPSLatitudeRef'])) {
									$latitude = ($exif['GPSLatitudeRef']== 'S' ? '-' : '') . Stuffpress_Exif::exif_gpsconvert( $exif['GPSLatitude'] );
								}

								$image = $key;
							}
						}
					}

					else if (substr_compare($part_type, 'audio', 0, 5) == 0) {
						if ($details = $this->getFileDetails($part->contentType)) {
							if ($content = base64_decode($part->getContent())) {
								$file_id = $files->saveFile($content, $details['name'], $details['mime'], "Email upload");
								$file = $files->getFile($file_id);
								$audio = $file->key;
							}
						}
					}
				} catch (Zend_Mail_Exception $e) {
					// ignore
				}
			}

			$body = strlen($html_text) > 0 ? $html_text : $plain_text;

			// Post the content
			// 1 - a status message
			if (!$image && !$audio && strlen($subject) == 0) {
				$item_id = $this->postStatus($user['id'], $timestamp, $body);
			}
			// Post a blog entry
			else if (!$image && !$audio) {
				$item_id = $this->postBlog($user['id'], $timestamp, $subject, $body);
			}

			// Post a picture
			else if ($image) {
				$item_id = $this->postImage($user['id'], $timestamp, $subject, $body, $image);
			}

			// Post a sound file
			else if ($audio) {
				$item_id = $this->postAudio($user['id'], $timestamp, $subject, $body, $audio);
			}

			// Unsupported
			else {
				echo "Unsupported fotmat\r\n";
			}

			// Set the location of the item if provided
			if ($latitude && $longitude) {
				$source		= StuffpressModel::forUser($this->_user->id);
				$source_id  = $source->getID();
				$data		= new Data();
				$data->setLocation($source_id, $item_id, $latitude, $longitude, 0);
			}

			$email_count++;

			// Delete the email
			$mail->removeMessage($messageNum);
			echo "processed.\r\n";
			$logger->log("Message delivered to {$user['username']}.", Zend_Log::INFO);

			// Clean up before the loop
			unset($content);
		}
		$logger->log("Processed $email_count emails. (max mem: " . memory_get_peak_usage() . ").", Zend_Log::INFO);
	}

	private function getUser($email) {
		$matches = array();
		if (!preg_match("/(?<username>\w+)\.(?<secret>\w+)@submit.storytlr.com/i",$email, $matches)) {
			echo "Did not find any user in $email \r\n";
			return false;
		}

		$username = $matches['username'];
		$secret   = $matches['secret'];

		$users = new Users();
		$user  = $users->getUserFromUsername($username);

		if (strlen($user['email_secret']) > 0 && ($user['email_secret'] == $secret)) {
			return $user;
		} else {
			return false;
		}
	}

	private function getFileDetails($type) {
		$matches = array();
		if (preg_match("/(?<mime>.*)\; name=\"(?<name>.*)\"/", $type, $matches)) {
			return $matches;
		} else {
			return false;
		}
	}

	private function getCharset($type) {
		$matches = array();
		if (preg_match("/charset=(?<charset>.*)$/", $type, $matches)) {
			return $matches['charset'];
		} else {
			return false;
		}
	}

	private function postStatus($user_id, $timestamp, $status) {
		$data = array();
		$data['published']  = $timestamp;
		$data['type']	    = 'status';
		$data['title']   	= strip_tags($status);
		return $this->post($user_id, $data);
	}

	private function postBlog($user_id, $timestamp, $title, $body) {
		$data = array();
		$data['published']  = $timestamp;
		$data['type']	    = 'blog';
		$data['title']   	= strip_tags($title);
		$data['text']   	= $body;
		return $this->post($user_id, $data);
	}

	private function postImage($user_id, $timestamp, $title, $body, $key) {
		$data = array();
		$data['published']  = $timestamp;
		$data['type']	    = 'image';
		$data['file']	    = $key;
		$data['title']   	= strip_tags($title);
		$data['text']   	= $body;
		return $this->post($user_id, $data);
	}

	private function postAudio($user_id, $timestamp, $title, $body, $key) {
		$data = array();
		$data['published']  = $timestamp;
		$data['type']	    = 'audio';
		$data['file']	    = $key;
		$data['title']   	= strip_tags($title);
		$data['text']   	= $body;
		return $this->post($user_id, $data);
	}

	private function post($user_id, $data) {
		$source		= StuffpressModel::forUser($user_id);
		$item_id 	= $source->addItem($data, $data['published'], $data['type'], false, false, false, $data['title']);
		$source_id 	= $source->getID();

		// Send notification if twitter post is enabled
		if ($this->_properties->getProperty('twitter_auth') && in_array($source_id, unserialize($this->_properties->getProperty('twitter_services')))) {
			try {
				$this->notifyTwitter($item_id, $source_id);
			} catch (Exception $e) {
				//
			}
		}

		// Ping blog search engines
		$this->ping();

		// Return the id so we can move on !
		return $item_id;
	}

	private function notifyTwitter($item_id, $source_id) {
		// Get twitter credentials
		$username   = $this->_properties->getProperty('twitter_username');
		$password	= $this->_properties->getProperty('twitter_password');
		$preamble   = $this->_properties->getProperty('preamble', true);

		// Get item
		$data		= new Data();
		$item		= $data->getItem($source_id, $item_id);
		$pream		= $preamble ? $item->getPreamble() : "";
		$title		= $pream . $item->getTitle();

		// Assemble tweet depending on type
		if (($item->getType() == SourceItem::STATUS_TYPE ) && strlen($title) < 140) {
			$tweet = $title;
		} else {
			if (strlen($title) > 121) $title = substr($title, 0, 117) . "[..]";
			$db_ShortUrls 	= new ShortUrls();
			$hash 	= $db_ShortUrls->addUrlForItem($this->_user->id, $source_id, $item_id);
			$tweet 	= "$title http://st.tl/$hash";
		}

		try {
			$twitter = new Stuffpress_Services_Twitter($username, $password);
			$twitter->sendTweet($tweet);
		} catch (Exception $e) {
			throw new Stuffpress_Exception("Twitter notification generated exception $e");
		}
	}

	private function ping() {
		// Ping google blog search
		if ($this->_user->domain) {
			$url = "http://{$this->_user->domain}";
		} else {
			$url = "http://{$this->_user->username}.storytlr.com";
		}

		$maintitle 	= $this->_properties->getProperty('title');
		$subtitle 	= $this->_properties->getProperty('subtitle');
		$separator	= $subtitle ? " | " : "";
		$title 		= $maintitle . $separator . $subtitle;
		$rss	 	= "$url/rss/feed.xml";

		Stuffpress_Services_Blogsearch::ping($title, $url, $rss);
	}

	private function recode($string, $from=false) {
		$binary = quoted_printable_decode($string);
		if ($from) {
			$result = mb_convert_encoding($binary, 'UTF-8', $from);
		} else {
			$result = mb_convert_encoding($binary, 'UTF-8');
		}
		return $result;
	}

	private function mimeWordDecode($string) {
		$matches = array();
		if (preg_match("/=\?(?<charset>.*)\?(?<encoding>\w)\?(?<content>.*)\?=/", $string, $matches)) {
			if ($matches['encoding'] == 'B' && ($binary = base64_decode($matches['content']))) {
				$result = mb_convert_encoding($binary, 'UTF-8', $matches['charset']);
				return $result;
			}
		}

		return $string;
	}
}

$mailpost = new Mailpost();
$mailpost->execute();