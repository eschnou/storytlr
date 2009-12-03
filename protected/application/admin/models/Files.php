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

class Files extends Stuffpress_Db_Table
{

	protected $_name = 'files';

	protected $_primary = 'id';

	public function getFile($id) {
		$rowset = $this->find($id);
		$result  = $rowset->current();
		return $result;
	}

	public function getFileFromKey($key) {
		$result = $this->fetchRow($this->select()->where('`key` = ?', $key));
		return $result;
	}

	public function getFiles() {
		$result = $this->fetchAll($this->select()->where('user_id = ?', $this->_user));
		return $result;
	}

	public function addFile($key, $name, $description='', $type='', $ext='') {
		$data 		= array("user_id" 	=> $this->_user,
							"key"		=> $key,
							"name"		=> $name,
							"description"	=> $description,
							"type"		=> $type,
							"ext"		=> $ext
		);
			
		$this->insert($data);
		$id = $this->_db->lastInsertId();

		return $id;
	}

	public function deleteFile($key) {
		// Delete the file physicaly
		$file  	= $this->getFileFromKey($key);
		if (!$file) return;
		$name 	= $file->name;
		$ext 	= substr($name, strrpos($name, '.') + 1);
		$root 	= Zend_Registry::get("root");

		@unlink($root . "/upload/{$file->key}");
		@unlink($root . "/upload/thumbnails/{$file->key}");
		@unlink($root . "/upload/small/{$file->key}");
		@unlink($root . "/upload/medium/{$file->key}");
		@unlink($root . "/upload/large/{$file->key}");

		// Remove the pointer from the database
		$where = $this->getAdapter()->quoteInto('id = ?', $file->id);
		$this->delete($where);
	}

	public function fitSquare($id, $size=50, $folder='thumbnails') {
		$root 		= Zend_Registry::get("root");
		$path 		= $root . "/upload";

		$file		= $this->getFile($id);
		$file_type	= $file->type;
		$file_key	= $file->key;
		$file_ext	= $file->ext;
		$file_tmp	= "$path/$file_key";

		if($file_type == "image/pjpeg" || $file_type == "image/jpeg" || $file_type == "image/jpg"){
			$new_img = imagecreatefromjpeg($file_tmp);
		}elseif($file_type == "image/x-png" || $file_type == "image/png"){
			$new_img = imagecreatefrompng($file_tmp);
		}elseif($file_type == "image/gif"){
			$new_img = imagecreatefromgif($file_tmp);
		}else {
			throw new Stuffpress_Exception("Unknown or unsupported file type ($file_type)");
		}

		//List the width and height
		list($width, $height) = getimagesize($file_tmp);

		//Keep the smallest part of the image
		$o_side	= min($width, $height);

		//Create a new image at the right size
		$resized_img = imagecreatetruecolor($size,$size);

		// Resize the image
		imagecopyresampled($resized_img, $new_img, 0, 0, 0, 0, $size, $size, $o_side, $o_side);

		// Create folder if required
		if (!file_exists("$path/$folder")) {
			if (!mkdir("$path/$folder", 0777)) {
				die ("Could not create picture folder $folder");
			}
		}
		
		// Save the image
		imagejpeg($resized_img,"$path/$folder/$file_key", 90);

		// Clean up
		ImageDestroy ($resized_img);
		ImageDestroy ($new_img);
	}

	public function fitWidth($id, $width=500, $folder='medium') {
		$root 		= Zend_Registry::get("root");
		$path 		= $root . "/upload";

		$file		= $this->getFile($id);
		$file_type	= $file->type;
		$file_key	= $file->key;
		$file_ext	= $file->ext;
		$file_tmp	= "$path/$file_key";

		if($file_type == "image/pjpeg" || $file_type == "image/jpeg" || $file_type == "image/jpg"){
			$new_img = imagecreatefromjpeg($file_tmp);
		}elseif($file_type == "image/x-png" || $file_type == "image/png"){
			$new_img = imagecreatefrompng($file_tmp);
		}elseif($file_type == "image/gif"){
			$new_img = imagecreatefromgif($file_tmp);
		}else {
			throw new Stuffpress_Exception("Unknown or unsupported file type ($file_type)");
		}

		//List the width and height
		list($o_width, $o_height) = getimagesize($file_tmp);

		// If smaller, just retur
		if ($o_width < $width) return;

		//Keep the smallest part of the image
		$n_width  = $width;
		$n_height = $width * ($o_height / $o_width);

		//Create a new image at the right size
		$resized_img = imagecreatetruecolor($n_width, $n_height);

		// Resize the image
		imagecopyresampled($resized_img, $new_img, 0, 0, 0, 0, $n_width, $n_height, $o_width, $o_height);

		// Save the image
		imagejpeg($resized_img,"$path/$folder/$file_key", 90);

		// Clean up
		ImageDestroy ($resized_img);
		ImageDestroy ($new_img);
	}

	public function processFile($path, $file, $description='') {
		// Validate the uploaded file
		$file_tmp	= $file['tmp_name'];
		$file_name	= $file['name'];
		$file_type	= $file['type'];
		$file_ext   = substr(trim(substr($file_name, strrpos($file_name, '.')), '.'), 0, 4); // returns the ext only

		// Assign a random name to the file
		$key	  	= Stuffpress_Token::create(32);
		$root 		= Zend_Registry::get("root");
		$from_path 	= $root . "/temp/" . $path;
		$to_path 	= $root . "/upload/" . $key;

		// Move the file to the upload folder
		if (!rename($from_path, $to_path)) {
			throw new Stuffpress_Exception('Upload failed: could not proceed to upload.');
		}

		// Store the file in the database
		$file_id 	= $this->addFile($key, $file_name, $description, $file_type, $file_ext);

		return $file_id;
	}

	public function downloadFile($url, $description="") {
		$key	  	= Stuffpress_Token::create(32);
		$root 		= Zend_Registry::get("root");
		$to_path 	= $root . "/upload/" . $key;

		$matches	= array();
		if (preg_match("/.*(?<name>[\w|_|-]+)\.(?<ext>\w{3,4})$/", $url, $matches)) {
			$name = $matches['name'];
			$ext  = $matches['ext'];
		} else {
			$name = "file";
			$ext  = "";
		}

		$ch = curl_init($url);
		$fp = fopen($to_path, "w");

		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		curl_exec($ch);
		curl_close($ch);
		fclose($fp);

		// Get the mime type
		if (extension_loaded('fileinfo')) {
			if ($finfo = new finfo(FILEINFO_MIME)) {
				$mimetype = $finfo->file($to_path);
			}	
		}

		if (!$mimetype) $mimetype = "image/$ext";

		// Store the file in the database
		$file_id 	= $this->addFile($key, "$name.$ext", $description, $mimetype, $ext);
		return $file_id;
	}
	
	public function saveFile($content, $filename, $mime, $description="") {
		$key	  	= Stuffpress_Token::create(32);
		$root 		= Zend_Registry::get("root");
		$to_path 	= $root . "/upload/" . $key;

		$matches	= array();
		if (preg_match("/(?<name>.+)\.(?<ext>\w{3,4})$/", $filename, $matches)) {
			$name = $matches['name'];
			$ext  = $matches['ext'];
		} else {
			$name = "file";
			$ext  = "";
		}

		$fp = fopen($to_path, "w");
		fwrite($fp, $content);
		fclose($fp);

		// Get the mime type
		if ($finfo = new finfo(FILEINFO_MIME)) {
			$mimetype = $finfo->file($to_path);
		}

		if (!$mimetype) $mimetype = $mime;

		// Store the file in the database
		$file_id 	= $this->addFile($key, $filename, $description, $mimetype, $ext);
		return $file_id;
	}
	

	public function readExif($id) {
		$root 		= Zend_Registry::get("root");
		$path 		= $root . "/upload";

		$file		= $this->getFile($id);
		$file_key	= $file->key;
		$file_path	= "$path/$file_key";

		$data		= exif_read_data ($file_path,'IFD0' ,0 );
		return 		$data;
	}
}